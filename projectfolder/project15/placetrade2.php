<?php
/**
 * placetrade.php — Demo trading page (single-file)
 * - Uses same DB and session model as index.php
 * - Seeds markets if missing
 * - Place buy/sell limit orders (reserves funds immediately)
 * - Attempts to match orders (simple matching engine)
 * - Updates wallets and writes transactions
 *
 * DEMO ONLY — not production-ready.
 */

/* ========== CONFIG (match your index.php) ========== */
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'tradding_db';
date_default_timezone_set('Africa/Lagos');

/* ========== SESSION ========== */
session_set_cookie_params([
    'lifetime'=>0,
    'path'=>'/',
    'domain'=>'',
    'secure'=>false,
    'httponly'=>true,
    'samesite'=>'Lax'
]);
if (session_status()===PHP_SESSION_NONE) session_start();

/* ========== CONNECT ========== */
try {
    $dsnNoDb = "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4";
    $pdo0 = new PDO($dsnNoDb, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    // ensure database exists (harmless if index.php already created)
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    die("DB server connection failed: ".htmlspecialchars($e->getMessage()));
}

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB connect failed: ".htmlspecialchars($e->getMessage()));
}

/* ========== Ensure tables (safe if they already exist) ========== */
$create = [
"CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(12) NOT NULL,
  balance DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  hold_amount DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_currency (user_id, currency),
  CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(12) NOT NULL,
  type VARCHAR(50) NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS markets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  symbol VARCHAR(64) NOT NULL UNIQUE,
  base_currency VARCHAR(12) NOT NULL,
  quote_currency VARCHAR(12) NOT NULL,
  tick_size DECIMAL(28,8) DEFAULT 0.01,
  min_size DECIMAL(28,8) DEFAULT 0.00000001,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  market_id INT NOT NULL,
  side ENUM('buy','sell') NOT NULL,
  type ENUM('limit','market') NOT NULL DEFAULT 'limit',
  price DECIMAL(28,8) NULL,
  size DECIMAL(28,8) NOT NULL,
  filled DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  status ENUM('open','partial','filled','cancelled') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_market FOREIGN KEY (market_id) REFERENCES markets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS trades (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  market_id INT NOT NULL,
  buy_order_id BIGINT,
  sell_order_id BIGINT,
  price DECIMAL(28,8) NOT NULL,
  size DECIMAL(28,8) NOT NULL,
  buy_user_id INT,
  sell_user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_trades_market FOREIGN KEY (market_id) REFERENCES markets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($create as $sql) {
    $pdo->exec($sql);
}

/* ========== Ensure orders.expire_at column exists ========== */
$colCheck = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'expire_at'");
$colCheck->execute([$DB_NAME]);
if ($colCheck->fetchColumn() == 0) {
    // add expire_at column to store explicit expiration time for order (demo)
    $pdo->exec("ALTER TABLE orders ADD COLUMN expire_at DATETIME NULL");
}

/* ========== Seed markets list requested by user ========== */
$marketSymbols = array_values(array_unique([
  'BTC/USDT','ETH/USDT','DOGE/USDT','BCH/USDT','LTC/USDT','IOTA/USDT','FLOW/USDT','TRX/USDT','BNB/USDT','ETC/USDT','JST/USDT','DOT/USDT',
  'BTC/NGN','USDT/NGN'
]));
$msStmt = $pdo->prepare("SELECT COUNT(*) FROM markets WHERE symbol = ?");
$insMarket = $pdo->prepare("INSERT INTO markets (symbol, base_currency, quote_currency, tick_size, min_size) VALUES (?, ?, ?, ?, ?)");

foreach ($marketSymbols as $s) {
    $parts = explode('/', $s);
    $base = $parts[0] ?? $s;
    $quote = $parts[1] ?? 'USDT';
    $msStmt->execute([$s]);
    if ($msStmt->fetchColumn() == 0) {
        $insMarket->execute([$s,$base,$quote,0.01,0.00001]);
    }
}

/* ========== Helpers ========== */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}
function check_csrf($t) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$t);
}
function require_login() {
    if (empty($_SESSION['user_id'])) { header('Location: index.php?view=login'); exit; }
}
function money_fmt($a) { return number_format((float)$a, 2, '.', ','); }
function ensure_wallet(PDO $pdo, $uid, $currency) {
    $ins = $pdo->prepare("INSERT IGNORE INTO wallets (user_id, currency, balance, hold_amount) VALUES (?, ?, 0, 0)");
    $ins->execute([$uid, $currency]);
}
function get_market($pdo, $market_id) {
    $s = $pdo->prepare("SELECT * FROM markets WHERE id = ?");
    $s->execute([$market_id]); return $s->fetch();
}
function get_symbol_market($pdo, $symbol) {
    $s = $pdo->prepare("SELECT * FROM markets WHERE symbol = ?");
    $s->execute([$symbol]); return $s->fetch();
}

/* ========== Timed settlement constants & function ========== */
$BUY_SETTLE_MINUTES = 10;
$SELL_SETTLE_MINUTES = 20;
$TIMED_PROFIT_RATE = 0.10; // 10%
$MAX_FEE_RATE = 0.002; // used at reserve time

function process_time_based_settlements(PDO $pdo) {
    global $BUY_SETTLE_MINUTES, $SELL_SETTLE_MINUTES, $TIMED_PROFIT_RATE, $MAX_FEE_RATE;

    // Use expire_at when set; fallback to previous created_at logic
    $sql = "SELECT * FROM orders WHERE status IN ('open','partial') AND (
                (expire_at IS NOT NULL AND expire_at <= NOW())
                OR
                (side='buy' AND created_at <= NOW() - INTERVAL ? MINUTE)
                OR
                (side='sell' AND created_at <= NOW() - INTERVAL ? MINUTE)
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$BUY_SETTLE_MINUTES, $SELL_SETTLE_MINUTES]);
    $candidates = $stmt->fetchAll();

    foreach ($candidates as $ord) {
        try {
            $pdo->beginTransaction();

            // Lock the order row to avoid races
            $oLock = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
            $oLock->execute([$ord['id']]); $order = $oLock->fetch();
            if (!$order) { $pdo->rollBack(); continue; }

            if (!in_array($order['status'], ['open','partial'])) { $pdo->rollBack(); continue; }

            $side = $order['side'];
            $market_id = $order['market_id'];
            $user_id = $order['user_id'];
            $remaining_size = (float)$order['size'] - (float)$order['filled'];
            if ($remaining_size <= 0) {
                $pdo->prepare("UPDATE orders SET status='filled', filled = size WHERE id = ?")->execute([$order['id']]);
                $pdo->commit();
                continue;
            }
            $order_price = (float)$order['price'];
            $notional = $order_price * $remaining_size;

            // last market price (last trade)
            $tq = $pdo->prepare("SELECT price FROM trades WHERE market_id = ? ORDER BY created_at DESC LIMIT 1");
            $tq->execute([$market_id]); $lastTrade = $tq->fetch();
            $last_price = $lastTrade ? (float)$lastTrade['price'] : $order_price;

            if ($side === 'buy') {
                // buyer's quote wallet
                $m = get_market($pdo, $market_id);
                $quote = $m['quote_currency'];
                ensure_wallet($pdo, $user_id, $quote);
                $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wq->execute([$user_id, $quote]); $wqr = $wq->fetch();
                if (!$wqr) { $pdo->rollBack(); continue; }

                $reserved_est = $order_price * $remaining_size * (1 + $MAX_FEE_RATE);
                $release = min((float)$wqr['hold_amount'], $reserved_est);
                if ($release > 0) {
                    $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance + ? WHERE id = ?")
                        ->execute([$release, $release, $wqr['id']]);
                    $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_settlement_release', ?, ?)")->execute([$user_id, $quote, $release, json_encode(['order_id'=>$order['id'],'remaining_size'=>$remaining_size])]);
                }

                $wqCheck = $pdo->prepare("SELECT balance FROM wallets WHERE id = ? FOR UPDATE"); $wqCheck->execute([$wqr['id']]); $cur = $wqCheck->fetch();
                $curBal = $cur ? (float)$cur['balance'] : 0.0;

                if ($last_price > $order_price) {
                    $profit = $notional * $TIMED_PROFIT_RATE;
                    if ($profit > 0) {
                        $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$profit, $wqr['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_profit', ?, ?)")->execute([$user_id, $quote, $profit, json_encode(['order_id'=>$order['id'],'last_price'=>$last_price,'order_price'=>$order_price])]);
                    }
                } else {
                    $loss = $notional * $TIMED_PROFIT_RATE;
                    if ($loss > 0) {
                        $deduct = min($curBal, $loss);
                        $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?")->execute([$deduct, $wqr['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_loss', ?, ?)")->execute([$user_id, $quote, -$deduct, json_encode(['order_id'=>$order['id'],'last_price'=>$last_price,'order_price'=>$order_price])]);
                    }
                }

                $pdo->prepare("UPDATE orders SET filled = size, status = 'filled' WHERE id = ?")->execute([$order['id']]);
                $pdo->commit();

            } else {
                // SELL
                $m = get_market($pdo, $market_id);
                $base = $m['base_currency'];
                $quote = $m['quote_currency'];
                ensure_wallet($pdo, $user_id, $base);
                ensure_wallet($pdo, $user_id, $quote);

                $wb = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wb->execute([$user_id, $base]); $wbr = $wb->fetch();
                $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wq->execute([$user_id, $quote]); $wqr = $wq->fetch();

                if (!$wbr || !$wqr) { $pdo->rollBack(); continue; }

                $releaseBase = min((float)$wbr['hold_amount'], $remaining_size);
                if ($releaseBase > 0) {
                    $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance + ? WHERE id = ?")
                        ->execute([$releaseBase, $releaseBase, $wbr['id']]);
                    $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_settlement_release_base', ?, ?)")->execute([$user_id, $base, $releaseBase, json_encode(['order_id'=>$order['id'],'remaining_size'=>$remaining_size])]);
                }

                $proceeds = $order_price * $remaining_size;
                $wqCheck = $pdo->prepare("SELECT balance FROM wallets WHERE id = ? FOR UPDATE"); $wqCheck->execute([$wqr['id']]); $curQuote = $wqCheck->fetch();
                $curQuoteBal = $curQuote ? (float)$curQuote['balance'] : 0.0;

                if ($last_price < $order_price) {
                    $profit = $proceeds * $TIMED_PROFIT_RATE;
                    if ($profit > 0) {
                        $credit = $proceeds + $profit;
                        $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$credit, $wqr['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_profit', ?, ?)")->execute([$user_id, $quote, $credit, json_encode(['order_id'=>$order['id'],'last_price'=>$last_price,'order_price'=>$order_price])]);
                    }
                } else {
                    $loss = $proceeds * $TIMED_PROFIT_RATE;
                    $credit = $proceeds;
                    if ($credit > 0) {
                        $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$credit, $wqr['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_proceeds', ?, ?)")->execute([$user_id, $quote, $credit, json_encode(['order_id'=>$order['id'],'order_price'=>$order_price])]);
                    }
                    $wqAfter = $pdo->prepare("SELECT balance FROM wallets WHERE id = ? FOR UPDATE"); $wqAfter->execute([$wqr['id']]); $balAfter = $wqAfter->fetch();
                    $balAfter = $balAfter ? (float)$balAfter['balance'] : 0.0;
                    $deduct = min($balAfter, $loss);
                    if ($deduct > 0) {
                        $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?")->execute([$deduct, $wqr['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_loss', ?, ?)")->execute([$user_id, $quote, -$deduct, json_encode(['order_id'=>$order['id'],'last_price'=>$last_price,'order_price'=>$order_price])]);
                    }
                }

                $pdo->prepare("UPDATE orders SET filled = size, status = 'filled' WHERE id = ?")->execute([$order['id']]);
                $pdo->commit();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            continue;
        }
    }
}

/* ========== Simple matching engine (demo) ========== */
function match_orders(PDO $pdo, $market_id) {
    $maker_fee_rate = 0.0;
    $taker_fee_rate = 0.0;
    while (true) {
        try {
            $pdo->beginTransaction();
            $buyStmt = $pdo->prepare("SELECT * FROM orders WHERE market_id = ? AND side='buy' AND status IN ('open','partial') AND price IS NOT NULL ORDER BY price DESC, created_at ASC LIMIT 1 FOR UPDATE");
            $sellStmt = $pdo->prepare("SELECT * FROM orders WHERE market_id = ? AND side='sell' AND status IN ('open','partial') AND price IS NOT NULL ORDER BY price ASC, created_at ASC LIMIT 1 FOR UPDATE");
            $buyStmt->execute([$market_id]); $sellStmt->execute([$market_id]);
            $buy = $buyStmt->fetch(); $sell = $sellStmt->fetch();

            if (!$buy || !$sell) { $pdo->rollBack(); break; }
            if ((float)$buy['price'] < (float)$sell['price']) { $pdo->rollBack(); break; }

            $trade_price = (float)$sell['price'];
            $buy_rem = (float)$buy['size'] - (float)$buy['filled'];
            $sell_rem = (float)$sell['size'] - (float)$sell['filled'];
            $trade_size = min($buy_rem, $sell_rem);
            if ($trade_size <= 0) { $pdo->rollBack(); break; }
            $cost = $trade_price * $trade_size;

            $buyer_id = (int)$buy['user_id'];
            $seller_id = (int)$sell['user_id'];

            $m = get_market($pdo,$market_id);
            $base = $m['base_currency'];
            $quote = $m['quote_currency'];

            $wBuyerQuote = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wSellerBase = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wBuyerQuote->execute([$buyer_id, $quote]); $bq = $wBuyerQuote->fetch();
            $wSellerBase->execute([$seller_id, $base]); $sb = $wSellerBase->fetch();

            if (!$bq || !$sb) { $pdo->rollBack(); break; }

            if ((float)$bq['hold_amount'] + 1e-12 < ($cost)) { $pdo->rollBack(); break; }
            if ((float)$sb['hold_amount'] + 1e-12 < $trade_size) { $pdo->rollBack(); break; }

            $insTrade = $pdo->prepare("INSERT INTO trades (market_id, buy_order_id, sell_order_id, price, size, buy_user_id, sell_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insTrade->execute([$market_id, $buy['id'], $sell['id'], $trade_price, $trade_size, $buyer_id, $seller_id]);
            $trade_id = $pdo->lastInsertId();

            $updOrder = $pdo->prepare("UPDATE orders SET filled = filled + ?, status = CASE WHEN filled + ? >= size THEN 'filled' ELSE 'partial' END WHERE id = ?");
            $updOrder->execute([$trade_size, $trade_size, $buy['id']]);
            $updOrder->execute([$trade_size, $trade_size, $sell['id']]);

            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, update_at = NOW() WHERE id = ?")->execute([$cost, $bq['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_buy', ?, ?)")->execute([$buyer_id, $base, $trade_size, json_encode(['trade_id'=>$trade_id,'price'=>$trade_price])]);
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")->execute([$trade_size, $buyer_id, $base]);

            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, update_at = NOW() WHERE id = ?")->execute([$trade_size, $sb['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_sell', ?, ?)")->execute([$seller_id, $quote, $cost, json_encode(['trade_id'=>$trade_id,'price'=>$trade_price])]);
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")->execute([$cost, $seller_id, $quote]);

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            break;
        }
    }
}

/* ========== POST: handle place order ========== */
$token = csrf_token();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$logged_in = !empty($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;

// Process timed-settlements on each page load (demo). In production prefer a background worker / cron.
try {
    process_time_based_settlements($pdo);
} catch (Exception $e) {
    // ignore errors here for demo; in production log them
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    require_login();
    if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash'] = 'Invalid CSRF'; header('Location: placetrade.php'); exit; }
    $market_id = (int)($_POST['market_id'] ?? 0);
    $side = $_POST['side'] ?? 'buy';
    $type = $_POST['type'] ?? 'limit';
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $size = (float)($_POST['size'] ?? 0);
    $duration = (int)($_POST['duration'] ?? 0); // minutes requested by user for expiry
    if ($size <= 0) { $_SESSION['flash'] = 'Invalid size'; header('Location: placetrade.php'); exit; }
    $market = get_market($pdo,$market_id);
    if (!$market) { $_SESSION['flash'] = 'Market not found'; header('Location: placetrade.php'); exit; }
    $base = $market['base_currency']; $quote = $market['quote_currency'];

    // Determine expire_at (use provided duration or fallback)
    global $BUY_SETTLE_MINUTES, $SELL_SETTLE_MINUTES;
    if ($duration <= 0) {
        $duration = ($side === 'buy') ? $BUY_SETTLE_MINUTES : $SELL_SETTLE_MINUTES;
    }
    $expire_at = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

    $max_fee_rate = 0.002;
    try {
        $pdo->beginTransaction();

        if ($side === 'buy') {
            if (!$price) throw new Exception('Price required for buy limit');
            $cost = $price * $size;
            $reserve = $cost + ($cost * $max_fee_rate);

            ensure_wallet($pdo, $uid, $quote);
            $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wq->execute([$uid,$quote]); $wqr = $wq->fetch();
            if (!$wqr) throw new Exception('Wallet error');

            $available = (float)$wqr['balance'] - (float)$wqr['hold_amount'];
            if ($available < $reserve) {
                // keep original behaviour: ask to deposit (demo)
                $pdo->rollBack();
                $_SESSION['flash'] = 'Insufficient available balance. <a href="deposit.php">Make a deposit</a>';
                header('Location: placetrade.php'); exit;
            }

            $pdo->prepare("UPDATE wallets SET balance = balance - ?, hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")
                ->execute([$reserve, $reserve, $wqr['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'reserve', ?, ?)")->execute([$uid, $quote, -$reserve, json_encode(['market'=>$market['symbol'],'side'=>'buy','price'=>$price,'size'=>$size])]);

        } else {
            ensure_wallet($pdo, $uid, $base);
            $wb = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wb->execute([$uid,$base]); $wbr = $wb->fetch();
            if (!$wbr) throw new Exception('Wallet error');

            $available = (float)$wbr['balance'] - (float)$wbr['hold_amount'];
            if ($available < $size) {
                $pdo->rollBack();
                $_SESSION['flash'] = 'Insufficient available balance to place sell order. <a href="deposit.php">Make a deposit</a>';
                header('Location: placetrade.php'); exit;
            }

            $pdo->prepare("UPDATE wallets SET balance = balance - ?, hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")
                ->execute([$size, $size, $wbr['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'reserve', ?, ?)")->execute([$uid, $base, -$size, json_encode(['market'=>$market['symbol'],'side'=>'sell','price'=>$price,'size'=>$size])]);
        }

        // Insert order with expire_at
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, market_id, side, type, price, size, expire_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $market_id, $side, $type, $price, $size, $expire_at]);
        $order_id = $pdo->lastInsertId();

        $pdo->commit();
        match_orders($pdo, $market_id);

        $_SESSION['flash'] = 'Order placed';
        header('Location: placetrade.php'); exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = 'Order error: '.$e->getMessage();
        header('Location: placetrade.php'); exit;
    }
}

/* ========== Data for rendering ========== */
if (empty($logged_in) && empty($uid)) {
    header('Location: index.php?view=login'); exit;
}
$markets = $pdo->query("SELECT * FROM markets WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

// fetch wallets
$wq = $pdo->prepare("SELECT currency, balance, hold_amount FROM wallets WHERE user_id = ?");
$wq->execute([$uid]); $user_wallets = $wq->fetchAll(PDO::FETCH_ASSOC);

// fetch open orders for user and general
$openOrdersUser = $pdo->prepare("SELECT o.*, m.symbol FROM orders o JOIN markets m ON o.market_id = m.id WHERE o.user_id = ? AND o.status IN ('open','partial') ORDER BY o.created_at DESC");
$openOrdersUser->execute([$uid]); $openOrdersUser = $openOrdersUser->fetchAll();

// fetch trade history recent
$tradeHistory = $pdo->query("SELECT t.*, m.symbol FROM trades t JOIN markets m ON t.market_id = m.id ORDER BY t.created_at DESC LIMIT 50")->fetchAll();

// fetch orderbook snapshot for active pair (client will choose)
$market_snaps = [];
foreach ($markets as $m) {
    $asks = $pdo->prepare("SELECT price, SUM(size-filled) as size FROM orders WHERE market_id = ? AND side='sell' AND status IN ('open','partial') GROUP BY price ORDER BY price ASC LIMIT 10");
    $bids = $pdo->prepare("SELECT price, SUM(size-filled) as size FROM orders WHERE market_id = ? AND side='buy' AND status IN ('open','partial') GROUP BY price ORDER BY price DESC LIMIT 10");
    $asks->execute([$m['id']]); $bids->execute([$m['id']]);
    $market_snaps[$m['symbol']] = ['asks'=>$asks->fetchAll(),'bids'=>$bids->fetchAll()];
}

/* ========== Render HTML & client JS ========== */
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PlaceTrade — Demo</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--accent:#2563eb;--muted:#6b7280}
*{box-sizing:border-box}
body{font-family:Inter,system-ui,Arial,sans-serif;background:var(--bg);margin:0;color:#0f172a}
.topnav{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;background:#0b1220;color:#fff}
.topnav .brand{font-weight:700}
.container{max-width:1200px;margin:20px auto;padding:12px}
.grid{display:grid;grid-template-columns:360px 1fr;gap:18px}
.card{background:var(--card);border-radius:10px;padding:16px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
.table{width:100%;border-collapse:collapse}
.table td,.table th{padding:8px;border-bottom:1px solid #eef2f7;text-align:left;font-size:14px}
.btn{display:inline-block;padding:8px 14px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}
.input,select,textarea{padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%;margin-top:6px;margin-bottom:10px}
.small{font-size:13px;color:#64748b}
.market-item{padding:8px;border-radius:8px;border:1px solid #eef2f7;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;cursor:pointer}
.market-item.active{background:#f0f9ff;border-color:#cfeeff}
.flex{display:flex;gap:12px}
.col{flex:1}
.orderbook{display:flex;gap:12px}
.book-side{flex:1;height:220px;overflow:auto;padding:8px;border-radius:8px;border:1px solid #eef2f7;background:#fff}
.notice{background:#fffbe6;border-left:4px solid #ffe08a;padding:10px;border-radius:6px;margin-bottom:12px}
.flash{background:#eefbe6;border-left:4px solid #b7f0c5;padding:10px;border-radius:6px;margin-bottom:12px}
.countdown {font-weight:700;color:#0b1220}
.countdown.expired {color:#c02626}
.time-label {font-size:12px;color:#6b7280;margin-left:8px}
</style>
</head>
<body>
  <header class="topnav">
    <div class="brand">PlaceTrade Demo</div>
    <div>
      <a href="index.php" style="color:#fff;margin-right:12px">Dashboard</a>
      <a href="deposit.php" style="color:#fff;margin-right:12px">Deposit</a>
      <form method="post" action="index.php" style="display:inline;margin-left:8px">
        <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
        <input type="hidden" name="action" value="logout">
        <button class="btn" style="background:#0b1220">Logout</button>
      </form>
    </div>
  </header>

  <main class="container">
    <?php if(!empty($flash)): ?><div class="flash"><?= $flash ?></div><?php endif; ?>

    <div class="grid">
      <aside class="card">
        <h3>Markets</h3>
        <div id="marketList">
          <?php foreach($markets as $m): ?>
            <div class="market-item" data-symbol="<?=htmlspecialchars($m['symbol'])?>" data-id="<?=intval($m['id'])?>">
              <div><?=htmlspecialchars($m['symbol'])?></div>
              <div class="small"><?=htmlspecialchars($m['base_currency'])?> / <?=htmlspecialchars($m['quote_currency'])?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:12px">
          <h4>Your wallets</h4>
          <table class="table">
            <tr><th>Currency</th><th>Balance</th><th>On Hold</th></tr>
            <?php
              foreach($user_wallets as $w):
                $avail = (float)$w['balance'] - (float)$w['hold_amount'];
            ?>
              <tr>
                <td><?=htmlspecialchars($w['currency'])?></td>
                <td><?=money_fmt($w['balance'])?></td>
                <td><?=money_fmt($w['hold_amount'])?></td>
                <td></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>

        <div style="margin-top:12px">
          <h4>Recent transactions</h4>
          <div style="max-height:220px;overflow:auto">
            <?php
            $tq = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
            $tq->execute([$uid]);
            $txs = $tq->fetchAll();
            foreach ($txs as $tx):
            ?>
              <div style="padding:6px;border-bottom:1px dashed #eee">
                <div class="small"><strong><?=htmlspecialchars($tx['currency'])?> <?=($tx['amount']>=0?'+':'')?><?=number_format($tx['amount'],8)?> </strong></div>
                <div class="small muted"><?=htmlspecialchars($tx['type'])?> — <?=htmlspecialchars($tx['created_at'])?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>

      <section class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <h2 id="activeSymbol">Select a market</h2>
          <div class="small">Open Orders: <?=count($openOrdersUser)?></div>
        </div>

        <div style="margin-top:8px;display:flex;gap:12px">
          <div style="flex:1">
            <canvas id="chart" height="160" style="width:100%;background:#fff;border:1px solid #eef2f7;border-radius:8px"></canvas>
          </div>
          <div style="width:360px">
            <form method="post">
              <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
              <input type="hidden" name="action" value="place_order">
              <input type="hidden" name="market_id" id="market_id" value="">
              <label class="small">Side
                <select class="input" name="side" id="side">
                  <option value="buy">Buy</option>
                  <option value="sell">Sell</option>
                </select>
              </label>
              <label class="small">Type
                <select class="input" name="type">
                  <option value="limit">Limit</option>
                </select>
              </label>
              <label class="small">Price <input class="input" id="priceField" name="price" type="number" step="0.0001" required></label>
              <label class="small">Size <input class="input" id="sizeField" name="size" type="number" step="0.00000001" required></label>

              <!-- Duration selector used to set expire_at for orders -->
              <label class="small">Duration (minutes)
                <select class="input" name="duration" id="duration">
                  <option value="10">10</option>
                  <option value="20">20</option>
                  <option value="30">30</option>
                </select>
              </label>

              <div style="display:flex;gap:8px">
                <button class="btn" type="submit">Place Order</button>
                <a class="btn" style="background:#10b981;text-decoration:none"  href="?index.php=deposit">Deposit</a>
              </div>
              <div class="small" style="margin-top:8px">If available balance is insufficient you'll be prompted to deposit.</div>
            </form>

            <div style="margin-top:12px">
              <h4>Orderbook</h4>
              <div class="orderbook">
                <div class="book-side" id="asksList"><strong>Asks</strong><div id="asksRows"></div></div>
                <div class="book-side" id="bidsList"><strong>Bids</strong><div id="bidsRows"></div></div>
              </div>
            </div>
          </div>
        </div>

        <div style="margin-top:12px">
          <h3>Your open orders</h3>
          <table class="table">
            <tr><th>Time</th><th>Market</th><th>Side</th><th>Price</th><th>Size</th><th>Time left</th><th>Status</th></tr>
            <?php foreach($openOrdersUser as $o):
                // compute expire display: use expire_at if set else created_at + fallback
                $expire = $o['expire_at'];
                if (empty($expire)) {
                    $mins = ($o['side'] === 'buy') ? $BUY_SETTLE_MINUTES : $SELL_SETTLE_MINUTES;
                    $expire = date('Y-m-d H:i:s', strtotime("+{$mins} minutes", strtotime($o['created_at'])));
                }
            ?>
              <tr>
                <td class="small"><?=htmlspecialchars($o['created_at'])?></td>
                <td><?=htmlspecialchars($o['symbol'])?></td>
                <td><?=htmlspecialchars($o['side'])?></td>
                <td><?=money_fmt($o['price'])?></td>
                <td><?=number_format($o['size'] - $o['filled'],8)?></td>
                <td><span class="countdown" data-expires="<?=htmlspecialchars($expire)?>">--:--</span></td>
                <td><?=htmlspecialchars($o['status'])?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>

        <div style="margin-top:12px">
          <h3>Recent trades (global)</h3>
          <table class="table">
            <tr><th>Time</th><th>Market</th><th>Price</th><th>Size</th></tr>
            <?php foreach($tradeHistory as $tr): ?>
              <tr><td class="small"><?=htmlspecialchars($tr['created_at'])?></td><td><?=htmlspecialchars($tr['symbol'])?></td><td><?=money_fmt($tr['price'])?></td><td><?=number_format($tr['size'],8)?></td></tr>
            <?php endforeach; ?>
          </table>
        </div>

      </section>
    </div>
  </main>

  <script>
    // Client-side: small market simulator + UI interactions + countdowns
    const markets = <?php echo json_encode(array_column($markets,'symbol')); ?>;
    const marketData = {};
    markets.forEach((s,i)=>{
      const base = (s.startsWith('BTC')? 3500000 : (s.startsWith('ETH')? 220000 : 50000));
      const start = Math.round(base * (1 + (Math.random()-0.5)*0.2));
      marketData[s] = { prices: Array.from({length:80}, ()=>start), last: start };
    });

    const snaps = <?php echo json_encode($market_snaps); ?>;

    let activeSymbol = markets[0] || null;
    const marketList = document.getElementById('marketList');
    const chart = document.getElementById('chart');
    const ctx = chart.getContext && chart.getContext('2d');
    const activeSymbolEl = document.getElementById('activeSymbol');

    document.querySelectorAll('.market-item').forEach((el,idx)=>{
      if (idx===0) el.classList.add('active');
      el.addEventListener('click', ()=>{
        document.querySelectorAll('.market-item').forEach(x=>x.classList.remove('active'));
        el.classList.add('active');
        activeSymbol = el.dataset.symbol;
        document.getElementById('market_id').value = el.dataset.id;
        activeSymbolEl.textContent = activeSymbol;
        document.getElementById('priceField').value = marketData[activeSymbol].last;
        renderOrderbook();
        drawChart();
      });
    });

    const firstEl = document.querySelector('.market-item');
    if (firstEl) {
      document.getElementById('market_id').value = firstEl.dataset.id;
      activeSymbol = firstEl.dataset.symbol;
      activeSymbolEl.textContent = activeSymbol;
      document.getElementById('priceField').value = marketData[activeSymbol].last;
    }

    function drawChart(){
      if (!ctx) return;
      chart.width = chart.clientWidth;
      chart.height = chart.clientHeight;
      const data = marketData[activeSymbol].prices;
      const min = Math.min(...data), max = Math.max(...data);
      ctx.clearRect(0,0,chart.width,chart.height);
      ctx.beginPath();
      data.forEach((v,i)=>{
        const x = i*(chart.width/(data.length-1));
        const y = chart.height - ((v-min)/(max-min||1))*chart.height;
        if (i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
      });
      ctx.strokeStyle = '#3ea0ff'; ctx.lineWidth = 2; ctx.stroke();
      ctx.fillStyle = '#0b1220'; ctx.font = '13px Inter, Arial';
      ctx.fillText('Price: ₦ ' + Number(marketData[activeSymbol].last).toLocaleString(), 8, 18);
    }

    function simulateTick(){
      markets.forEach(s=>{
        const m = marketData[s];
        const vol = s.startsWith('BTC')? 0.006 : (s.startsWith('ETH')? 0.015 : 0.02);
        const drift = (Math.random()-0.5) * vol * m.last;
        let next = Math.max(1, Math.round(m.last + drift));
        if (Math.random() < 0.02) next += Math.round(m.last * (Math.random()>0.5?0.02:-0.02));
        m.last = next;
        m.prices.push(next); if (m.prices.length>200) m.prices.shift();
      });
      drawChart();
      renderOrderbook();
      updateCountdowns();
    }

    function renderOrderbook(){
      const asksRows = document.getElementById('asksRows');
      const bidsRows = document.getElementById('bidsRows');
      asksRows.innerHTML = ''; bidsRows.innerHTML = '';
      const snap = snaps[activeSymbol] || {asks:[], bids:[]};
      const nowLabel = new Date().toLocaleTimeString();
      snap.asks.slice(0,10).forEach(a=>{
        const div = document.createElement('div');
        div.style.display='flex'; div.style.justifyContent='space-between'; div.style.padding='4px 0';
        // include a small time label after size (current time for demo)
        div.innerHTML = `<div class="small">₦ ${Number(a.price).toLocaleString()}</div><div class="small">${Number(a.size).toFixed(8)} <span class="time-label">${nowLabel}</span></div>`;
        asksRows.appendChild(div);
      });
      snap.bids.slice(0,10).forEach(b=>{
        const div = document.createElement('div');
        div.style.display='flex'; div.style.justifyContent='space-between'; div.style.padding='4px 0';
        div.innerHTML = `<div class="small">₦ ${Number(b.price).toLocaleString()}</div><div class="small">${Number(b.size).toFixed(8)} <span class="time-label">${nowLabel}</span></div>`;
        bidsRows.appendChild(div);
      });
    }

    // countdowns
    function updateCountdowns(){
      document.querySelectorAll('.countdown').forEach(el=>{
        const expires = el.getAttribute('data-expires');
        if (!expires) return;
        const then = new Date(expires + ' UTC'); // assume server times in local TZ -> add UTC to force parsing as UTC safe
        const now = new Date();
        let diff = Math.floor((then.getTime() - now.getTime())/1000);
        if (isNaN(diff)) { el.textContent = '--:--'; return; }
        if (diff <= 0) {
          el.textContent = 'Expired';
          el.classList.add('expired');
          return;
        }
        const hours = Math.floor(diff/3600);
        diff = diff % 3600;
        const mins = Math.floor(diff/60);
        const secs = diff % 60;
        el.textContent = (hours>0? hours+':':'') + String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
      });
    }

    setInterval(simulateTick, 1200);
    setInterval(updateCountdowns, 1000);
    window.addEventListener('resize', drawChart);
    drawChart();
    renderOrderbook();
    updateCountdowns();
  </script>
</body>
</html>
