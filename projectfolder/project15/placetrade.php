<?php
/**
 * placetrade.php — Demo trading page (single-file)
 * - Wallet-backed trades, orders store amount
 * - Minimum trade amounts (NGN 1000, USDT 1, others 10000)
 * - Allow using full available balance (relaxes tiny fee buffer when necessary)
 * - New-user bonus: NGN 5000 + USDT 3 and welcome message
 * - Timed settlement: gain 10% / loss 10% (applied at expiry)
 *
 * DEMO ONLY — not production-ready. Do NOT run as-is in production.
 */

/* ========== CONFIG ========== */
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

/* ========== Ensure tables ========== */
$create = [
// users
"CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

// wallets
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

// transactions
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

// markets
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

// orders (we'll add amount column later if needed)
"CREATE TABLE IF NOT EXISTS orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  market_id INT NOT NULL,
  side ENUM('buy','sell') NOT NULL,
  type ENUM('limit','market') NOT NULL DEFAULT 'limit',
  price DECIMAL(28,8) NULL,
  size DECIMAL(28,8) NOT NULL,
  filled DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  amount DECIMAL(28,8) NULL,
  status ENUM('open','partial','filled','cancelled') NOT NULL DEFAULT 'open',
  expire_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_market FOREIGN KEY (market_id) REFERENCES markets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

// trades
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

// market_events
"CREATE TABLE IF NOT EXISTS market_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  market_id INT NOT NULL,
  direction ENUM('up','down') NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (market_id, start_at, end_at),
  CONSTRAINT fk_events_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($create as $sql) {
    $pdo->exec($sql);
}

// defensive: ensure orders.amount exists (some earlier versions may not have it)
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN amount DECIMAL(28,8) NULL");
} catch (Exception $e) {
    // ignore; column may already exist
}

/* ========== Seed markets requested by user ========== */
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

// Return last trade price or fallback
function get_effective_last_price(PDO $pdo, $market_id, $fallback_price) {
    $tq = $pdo->prepare("SELECT price FROM trades WHERE market_id = ? ORDER BY created_at DESC LIMIT 1");
    $tq->execute([$market_id]); $lastTrade = $tq->fetch();
    $base_price = $lastTrade ? (float)$lastTrade['price'] : (float)$fallback_price;
    return $base_price;
}

/* ========== Timed settlement constants & function (GAIN 10%, LOSS 10%) ========== */
$BUY_SETTLE_MINUTES = 10;
$SELL_SETTLE_MINUTES = 20;
$TIMED_PROFIT_RATE_GAIN = 0.10; // 10%
$TIMED_PROFIT_RATE_LOSS = 0.10; // 10%
$MAX_FEE_RATE = 0.002;

/* ========== New-user registration helper with bonus ========== */
function create_user_with_bonus(PDO $pdo, $username, $email, $password_plain) {
    // This helper creates a user and credits NGN 5000 + USDT 3 as bonus
    $pwHash = password_hash($password_plain, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (username,email,password) VALUES (?, ?, ?)");
    $ins->execute([$username, $email, $pwHash]);
    $uid = $pdo->lastInsertId();

    // ensure wallets exist and credit bonuses
    ensure_wallet($pdo, $uid, 'NGN');
    ensure_wallet($pdo, $uid, 'USDT');

    // credit NGN 5000
    $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")->execute([5000.00, $uid, 'NGN']);
    $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'bonus', ?, ?)")->execute([$uid, 'NGN', 5000.00, json_encode(['note'=>'welcome bonus'])]);

    // credit USDT 3
    $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")->execute([3.00, $uid, 'USDT']);
    $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'bonus', ?, ?)")->execute([$uid, 'USDT', 3.00, json_encode(['note'=>'welcome bonus'])]);

    return $uid;
}

/* ========== Process time-based settlements: apply 10% gain or loss on expiry/timeout ========== */
function process_time_based_settlements(PDO $pdo) {
    global $BUY_SETTLE_MINUTES, $SELL_SETTLE_MINUTES, $TIMED_PROFIT_RATE_GAIN, $TIMED_PROFIT_RATE_LOSS, $MAX_FEE_RATE;
    $sql = "SELECT * FROM orders WHERE status IN ('open','partial') AND ( (expire_at IS NOT NULL AND expire_at <= NOW()) OR (side='buy' AND created_at <= NOW() - INTERVAL ? MINUTE) OR (side='sell' AND created_at <= NOW() - INTERVAL ? MINUTE) )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$BUY_SETTLE_MINUTES, $SELL_SETTLE_MINUTES]);
    $candidates = $stmt->fetchAll();
    foreach ($candidates as $ord) {
        try {
            $pdo->beginTransaction();
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
            $last_price = get_effective_last_price($pdo, $market_id, $order_price);

            $m = get_market($pdo, $market_id);
            $base = $m['base_currency'];
            $quote = $m['quote_currency'];

            if ($side === 'buy') {
                // For buy orders: if last_price > order_price => gain, else => loss
                ensure_wallet($pdo, $user_id, $quote);
                $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wq->execute([$user_id, $quote]); $wqr = $wq->fetch();
                if (!$wqr) { $pdo->rollBack(); continue; }

                // release reserved portion (approx)
                $reserved_est = $order_price * $remaining_size * (1 + $MAX_FEE_RATE);
                $release = min((float)$wqr['hold_amount'], $reserved_est);
                if ($release > 0) {
                    $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance + ? WHERE id = ?")
                        ->execute([$release, $release, $wqr['id']]);
                    $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_settlement_release', ?, ?)")->execute([$user_id, $quote, $release, json_encode(['order_id'=>$order['id'],'remaining_size'=>$remaining_size])]);
                }

                // apply gain or loss
                if ($last_price > $order_price) {
                    // gain 10% on notional credited
                    $profit = $notional * $TIMED_PROFIT_RATE_GAIN;
                    if ($profit>0) {
                        $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$profit, $wqr['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_profit', ?, ?)")->execute([$user_id, $quote, $profit, json_encode(['order_id'=>$order['id'],'last_price'=>$last_price,'order_price'=>$order_price])]);
                    }
                } else {
                    // loss 10%: deduct from balance (after release)
                    $loss = $notional * $TIMED_PROFIT_RATE_LOSS;
                    if ($loss>0) {
                        $wqCheck = $pdo->prepare("SELECT balance FROM wallets WHERE id = ? FOR UPDATE"); $wqCheck->execute([$wqr['id']]); $cur = $wqCheck->fetch();
                        $curBal = $cur ? (float)$cur['balance'] : 0.0;
                        $deduct = min($curBal, $loss);
                        if ($deduct>0) {
                            $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?")->execute([$deduct, $wqr['id']]);
                            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_loss', ?, ?)")->execute([$user_id, $quote, -$deduct, json_encode(['order_id'=>$order['id'],'last_price'=>$last_price,'order_price'=>$order_price])]);
                        }
                    }
                }

                // mark filled
                $pdo->prepare("UPDATE orders SET filled = size, status = 'filled' WHERE id = ?")->execute([$order['id']]);
                $pdo->commit();

            } else {
                // SELL: if last_price < order_price => gain, else => loss
                ensure_wallet($pdo, $user_id, $base);
                ensure_wallet($pdo, $user_id, $quote);

                $wb = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wb->execute([$user_id, $base]); $wbr = $wb->fetch();
                $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wq->execute([$user_id, $quote]); $wqr = $wq->fetch();
                if (!$wbr || !$wqr) { $pdo->rollBack(); continue; }

                // release base held
                $releaseBase = min((float)$wbr['hold_amount'], $remaining_size);
                if ($releaseBase > 0) {
                    $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance + ? WHERE id = ?")->execute([$releaseBase, $releaseBase, $wbr['id']]);
                    $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_settlement_release_base', ?, ?)")->execute([$user_id, $base, $releaseBase, json_encode(['order_id'=>$order['id'],'remaining_size'=>$remaining_size])]);
                }

                $proceeds = $order_price * $remaining_size;

                if ($last_price < $order_price) {
                    // SELL gain: credit proceeds + 10% profit to quote wallet
                    $profit = $proceeds * $TIMED_PROFIT_RATE_GAIN;
                    $credit = $proceeds + $profit;
                    $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$credit, $wqr['id']]);
                    $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_profit', ?, ?)")->execute([$user_id, $quote, $credit, json_encode(['order_id'=>$order['id'],'last_price'=>$last_price,'order_price'=>$order_price])]);
                } else {
                    // SELL loss: credit proceeds, then deduct 10% loss from credit
                    $credit = $proceeds;
                    if ($credit > 0) {
                        $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$credit, $wqr['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'timed_proceeds', ?, ?)")->execute([$user_id, $quote, $credit, json_encode(['order_id'=>$order['id'],'order_price'=>$order_price])]);
                    }
                    $wqAfter = $pdo->prepare("SELECT balance FROM wallets WHERE id = ? FOR UPDATE");
                    $wqAfter->execute([$wqr['id']]); $balAfter = $wqAfter->fetch();
                    $balAfter = $balAfter ? (float)$balAfter['balance'] : 0.0;
                    $loss = $proceeds * $TIMED_PROFIT_RATE_LOSS;
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

/* ========== Simple matching engine ========== */
function match_orders(PDO $pdo, $market_id) {
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

            // buyer: deduct hold_amount (cost), credit base amount to buyer balance
            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, update_at = NOW() WHERE id = ?")->execute([$cost, $bq['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_buy', ?, ?)")->execute([$buyer_id, $base, $trade_size, json_encode(['trade_id'=>$trade_id,'price'=>$trade_price])]);
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")->execute([$trade_size, $buyer_id, $base]);

            // seller: deduct base hold_amount, credit quote proceeds to seller balance
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

/* ========== POST handling ========== */
$token = csrf_token();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$logged_in = !empty($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;

/* ========== Run settlements on each page load (demo) ========== */
try { process_time_based_settlements($pdo); } catch (Exception $e) { /* ignore in demo */ }

/* ========== Helper: server-side min amount per market ========== */
function min_amount_for_market(array $market) {
    // Returns minimum amount in quote currency for the given market row
    $quote = strtoupper($market['quote_currency'] ?? '');
    if ($quote === 'NGN') return 1000.0;
    if ($quote === 'USDT' || $quote === 'USD') return 1.0;
    return 10000.0; // fallback
}

/* ========== Place order ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    require_login();
    if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash'] = 'Invalid CSRF'; header('Location: placetrade.php'); exit; }

    $market_id = (int)($_POST['market_id'] ?? 0);
    $side = $_POST['side'] ?? 'buy';
    $type = $_POST['type'] ?? 'limit';
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $size = isset($_POST['size']) && $_POST['size'] !== '' ? (float)$_POST['size'] : 0;
    $amount = isset($_POST['amount']) && $_POST['amount'] !== '' ? (float)$_POST['amount'] : null; // amount in quote currency
    $duration = (int)($_POST['duration'] ?? 0);

    if ($price === null) { $_SESSION['flash'] = 'Price is required'; header('Location: placetrade.php'); exit; }

    // if user supplied amount (quote), compute size
    if ($amount !== null && $amount > 0 && ($size <= 0)) {
        $size = ($price > 0) ? ($amount / $price) : 0;
    } elseif ($size > 0 && ($amount === null || $amount == 0)) {
        $amount = $price * $size;
    }

    if ($size <= 0) { $_SESSION['flash'] = 'Invalid size/amount'; header('Location: placetrade.php'); exit; }

    $market = get_market($pdo,$market_id);
    if (!$market) { $_SESSION['flash'] = 'Market not found'; header('Location: placetrade.php'); exit; }
    $base = $market['base_currency']; $quote = $market['quote_currency'];

    // validate minimum amount for this market (server-side)
    $minAmount = min_amount_for_market($market);
    if ($amount < $minAmount) {
        $_SESSION['flash'] = "Minimum trade amount for {$quote} markets is {$minAmount} {$quote}.";
        header('Location: placetrade.php'); exit;
    }

    // durations: if not provided, choose defaults
    if ($duration <= 0) $duration = ($side === 'buy') ? $BUY_SETTLE_MINUTES : $SELL_SETTLE_MINUTES;
    $expire_at = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

    $max_fee_rate = $MAX_FEE_RATE;
    try {
        $pdo->beginTransaction();

        if ($side === 'buy') {
            // reserve quote (amount + fee) but allow full-balance use: if available >= cost but < cost+fee
            $cost = $price * $size;
            $reserve_with_fee = $cost + ($cost * $max_fee_rate);
            ensure_wallet($pdo, $uid, $quote);
            $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wq->execute([$uid,$quote]); $wqr = $wq->fetch();
            if (!$wqr) throw new Exception('Wallet error');
            $available = (float)$wqr['balance'] - (float)$wqr['hold_amount'];

            if ($available < $cost) {
                // insufficient to cover even the cost
                $pdo->rollBack();
                $_SESSION['flash'] = 'Insufficient available balance. <a href="deposit.php">Make a deposit</a>';
                header('Location: placetrade.php'); exit;
            }

            // If available >= reserve_with_fee then reserve full (cost+fee)
            // If available < reserve_with_fee but available >= cost, allow reserving only the cost so user can use full balance
            if ($available >= $reserve_with_fee) {
                $reserve = $reserve_with_fee;
            } else {
                // allow using full available to cover cost (no extra fee buffer)
                $reserve = $cost;
            }

            // Deduct balance, increase hold_amount by reserve
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")
                ->execute([$reserve, $reserve, $wqr['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'reserve', ?, ?)")->execute([$uid, $quote, -$reserve, json_encode(['market'=>$market['symbol'],'side'=>'buy','price'=>$price,'size'=>$size,'amount'=>$cost,'reserve'=>$reserve])]);

        } else {
            // sell: reserve base size
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
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")->execute([$size, $size, $wbr['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'reserve', ?, ?)")->execute([$uid, $base, -$size, json_encode(['market'=>$market['symbol'],'side'=>'sell','price'=>$price,'size'=>$size,'amount'=>($price*$size)])]);
        }

        // Insert order with amount saved
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, market_id, side, type, price, size, amount, expire_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $market_id, $side, $type, $price, $size, ($price*$size), $expire_at]);
        $order_id = $pdo->lastInsertId();

        // Create market event demo (direction)
        $isDown = (mt_rand(1,100) <= 50); // 50/50
        if ($isDown) { $minutes = mt_rand(2,6); $direction = 'down'; } else { $minutes = mt_rand(1,4); $direction = 'up'; }
        $start_at = date('Y-m-d H:i:s');
        $end_at = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        $insEv = $pdo->prepare("INSERT INTO market_events (market_id, direction, start_at, end_at) VALUES (?, ?, ?, ?)");
        $insEv->execute([$market_id, $direction, $start_at, $end_at]);

        $pdo->commit();
        // try match immediate
        match_orders($pdo, $market_id);

        $_SESSION['flash'] = 'Order placed — event: '.$direction.' for '.$minutes.'m';
        header('Location: placetrade.php'); exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = 'Order error: '.$e->getMessage();
        header('Location: placetrade.php'); exit;
    }
}

/* ========== Data for rendering ========== */
/* If not logged in, create demo user (and give bonuses) and auto-login */
if (empty($logged_in) && empty($uid)) {
    $uStmt = $pdo->query("SELECT id FROM users LIMIT 1")->fetch();
    if (!$uStmt) {
        // create demo user with bonus
        $uid = create_user_with_bonus($pdo, 'demo', 'demo@example.com', 'demo123');
        $_SESSION['user_id'] = $uid;
        $_SESSION['flash'] = 'Welcome to BestTrading website';
    } else {
        $u = $pdo->query("SELECT * FROM users LIMIT 1")->fetch();
        $_SESSION['user_id'] = $u['id'];
        $uid = $u['id'];
    }
    $logged_in = true;
}

/* fetch markets and other UI data */
$markets = $pdo->query("SELECT * FROM markets WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

/* fetch wallets (ensure some wallets exist for demo) */
foreach (['NGN','USDT','BTC','ETH'] as $c) ensure_wallet($pdo, $uid, $c);
$wq = $pdo->prepare("SELECT currency, balance, hold_amount FROM wallets WHERE user_id = ?");
$wq->execute([$uid]);
$user_wallets = $wq->fetchAll(PDO::FETCH_ASSOC);

/* fetch open orders for user and general */
$openOrdersUser = $pdo->prepare("SELECT o.*, m.symbol, m.base_currency, m.quote_currency FROM orders o JOIN markets m ON o.market_id = m.id WHERE o.user_id = ? AND o.status IN ('open','partial') ORDER BY o.created_at DESC");
$openOrdersUser->execute([$uid]); $openOrdersUser = $openOrdersUser->fetchAll();

/* fetch trade history recent */
$tradeHistory = $pdo->query("SELECT t.*, m.symbol FROM trades t JOIN markets m ON t.market_id = m.id ORDER BY t.created_at DESC LIMIT 50")->fetchAll();

/* orderbook snapshots & active events */
$market_snaps = []; $market_events_active = [];
foreach ($markets as $m) {
    $asks = $pdo->prepare("SELECT price, SUM(size - filled) as size FROM orders WHERE market_id = ? AND side='sell' AND status IN ('open','partial') GROUP BY price ORDER BY price ASC LIMIT 10");
    $bids = $pdo->prepare("SELECT price, SUM(size - filled) as size FROM orders WHERE market_id = ? AND side='buy' AND status IN ('open','partial') GROUP BY price ORDER BY price DESC LIMIT 10");
    $asks->execute([$m['id']]); $bids->execute([$m['id']]);
    $market_snaps[$m['symbol']] = ['asks'=>$asks->fetchAll(),'bids'=>$bids->fetchAll()];

    $evq = $pdo->prepare("SELECT * FROM market_events WHERE market_id = ? AND end_at >= NOW() ORDER BY start_at DESC LIMIT 1");
    $evq->execute([$m['id']]); $ev = $evq->fetch();
    if ($ev) $market_events_active[$m['symbol']] = $ev;
}

/* get last trade prices to show "recent price" */
$last_prices = [];
$tradeQ = $pdo->prepare("SELECT price FROM trades WHERE market_id = ? ORDER BY created_at DESC LIMIT 1");
foreach ($markets as $m) {
    $tradeQ->execute([$m['id']]); $t = $tradeQ->fetch();
    if ($t) $last_prices[$m['symbol']] = (float)$t['price'];
    else {
        // fallback simulated initial price for display
        $p = 1;
        if (strpos($m['symbol'],'BTC') === 0) $p = 3500000;
        elseif (strpos($m['symbol'],'ETH') === 0) $p = 220000;
        elseif ($m['quote_currency'] === 'NGN') $p = 150; // arbitrary
        else $p = 1;
        $last_prices[$m['symbol']] = $p;
    }
}

/* ========== Render HTML & client JS ========== */
?>
<!doctype html>
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
.event-badge {display:inline-block;padding:4px 8px;border-radius:6px;font-size:12px;margin-left:6px}
.event-up {background:#ecfeff;color:#065f46}
.event-down {background:#fff1f2;color:#7f1d1d}
.currency-symbol {font-weight:700;margin-left:6px}
.row {display:flex;gap:8px;align-items:center}
.row > * {flex:1}
.small-muted {font-size:12px;color:#94a3b8}
.min-note {font-size:13px;margin-top:8px;color:#334155}
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
            <div class="market-item" data-symbol="<?=htmlspecialchars($m['symbol'])?>" data-id="<?=intval($m['id'])?>" data-base="<?=htmlspecialchars($m['base_currency'])?>" data-quote="<?=htmlspecialchars($m['quote_currency'])?>" data-last="<?=htmlspecialchars($last_prices[$m['symbol']])?>">
              <div>
                <div style="font-weight:600"><?=htmlspecialchars($m['symbol'])?></div>
                <div class="small"><?=htmlspecialchars($m['base_currency'])?> / <?=htmlspecialchars($m['quote_currency'])?></div>
              </div>
              <div class="small-muted">Last: <?=htmlspecialchars(number_format($last_prices[$m['symbol']], 2, '.', ','))?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:12px">
          <h4>Your wallets</h4>
          <table class="table">
            <tr><th>Currency</th><th>Balance</th><th>On Hold</th></tr>
            <?php foreach($user_wallets as $w): ?>
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
            <form method="post" id="orderForm">
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

              <!-- Price (quote currency) -->
              <label class="small">Price
                <div class="row">
                  <input class="input" id="priceField" name="price" type="number" step="0.0001" required style="flex:1">
                  <div id="priceCurrency" class="currency-symbol" style="align-self:center">₦</div>
                </div>
                <div class="small-muted" id="recentPriceLabel"></div>
              </label>

              <!-- Amount (quote) -->
              <label class="small">Amount (quote currency)
                <div class="row">
                  <input class="input" id="amountField" name="amount" type="number" step="0.0001" placeholder="Enter amount in quote (e.g., USDT or NGN)">
                  <div id="amountCurrency" class="currency-symbol" style="align-self:center"></div>
                </div>
                <div class="small-muted">Enter amount in quote; size will be calculated (size = amount / price).</div>
              </label>

              <!-- Size (base currency) -->
              <label class="small">Size (base currency)
                <div class="row">
                  <input class="input" id="sizeField" name="size" type="number" step="0.00000001" placeholder="Calculated from amount / price">
                  <div id="sizeCurrency" class="currency-symbol" style="align-self:center">BTC</div>
                </div>
                <div class="small-muted">You can also type size directly; amount will be recalculated.</div>
              </label>

              <!-- Duration selector -->
              <label class="small">Duration
                <select class="input" name="duration" id="duration">
                  <option value="10">10 minutes</option>
                  <option value="20">20 minutes</option>
                  <option value="30">30 minutes</option>
                  <option value="40">40 minutes</option>
                  <option value="60">1 hour</option>
                  <option value="120">2 hours</option>
                </select>
              </label>

              <div style="display:flex;gap:8px">
                <button class="btn" type="submit">Place Order</button>
                <a class="btn" style="background:#10b981;text-decoration:none"  href="?index.php=deposit">Deposit</a>
              </div>
              <div class="min-note" id="minNote">Minimum: —</div>
              <div class="small" style="margin-top:8px">When the countdown reaches 00:00 the amount will be credited/debited by 10% depending on market direction (demo behavior).</div>
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
            <tr><th>Time</th><th>Market</th><th>Side</th><th>Price</th><th>Size</th><th>Amount</th><th>Time left</th><th>Status</th></tr>
            <?php foreach($openOrdersUser as $o):
                $expire = $o['expire_at'];
                if (empty($expire)) {
                    $mins = ($o['side'] === 'buy') ? $BUY_SETTLE_MINUTES : $SELL_SETTLE_MINUTES;
                    $expire = date('Y-m-d H:i:s', strtotime("+{$mins} minutes", strtotime($o['created_at'])));
                }
                $expire_ts = (int) strtotime($expire);
            ?>
              <tr>
                <td class="small"><?=htmlspecialchars($o['created_at'])?></td>
                <td><?=htmlspecialchars($o['symbol'])?></td>
                <td><?=htmlspecialchars($o['side'])?></td>
                <td><?=money_fmt($o['price'])?> <span class="small"><?=htmlspecialchars($o['quote_currency'] ?? '')?></span></td>
                <td><?=number_format($o['size'] - $o['filled'],8)?> <span class="small"><?=htmlspecialchars($o['base_currency'] ?? '')?></span></td>
                <td><?=isset($o['amount'])?money_fmt($o['amount']):money_fmt($o['price']*($o['size']-$o['filled']))?> <span class="small"><?=htmlspecialchars($o['quote_currency'] ?? '')?></span></td>
                <td><span class="countdown" data-expires-ts="<?=htmlspecialchars($expire_ts)?>">--:--</span></td>
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
    // Client-side behaviour: connect market list, show recent price, compute size/amount
    const markets = <?php echo json_encode(array_column($markets,'symbol')); ?>;
    const marketMeta = {};
    document.querySelectorAll('.market-item').forEach(el=>{
      marketMeta[el.dataset.symbol] = { id: el.dataset.id, base: el.dataset.base, quote: el.dataset.quote, last: parseFloat(el.dataset.last) };
    });

    const marketData = {};
    markets.forEach((s,i)=>{
      const last = marketMeta[s] ? marketMeta[s].last : 1;
      marketData[s] = { prices: Array.from({length:80}, ()=>last), last: last };
    });

    const snaps = <?php echo json_encode($market_snaps); ?>;
    const activeEvents = <?php echo json_encode($market_events_active); ?>;

    let activeSymbol = markets[0] || null;
    const chart = document.getElementById('chart');
    const ctx = chart.getContext && chart.getContext('2d');
    const activeSymbolEl = document.getElementById('activeSymbol');

    function setActiveMarket(symbol, el) {
      document.querySelectorAll('.market-item').forEach(x=>x.classList.remove('active'));
      if (el) el.classList.add('active');
      activeSymbol = symbol;
      document.getElementById('market_id').value = marketMeta[symbol].id;
      activeSymbolEl.textContent = symbol;
      // set price field to last
      document.getElementById('priceField').value = Number(marketData[symbol].last).toFixed(4);
      updateCurrencyLabels(symbol);
      renderOrderbook();
      drawChart();
      // show recent price
      const rp = marketMeta[symbol] && marketMeta[symbol].last ? marketMeta[symbol].last : marketData[symbol].last;
      document.getElementById('recentPriceLabel').textContent = 'Recent price: ' + rp.toLocaleString();

      // update min note
      updateMinNote(symbol);
    }

    document.querySelectorAll('.market-item').forEach((el,idx)=>{
      if (idx===0) el.classList.add('active');
      el.addEventListener('click', ()=> setActiveMarket(el.dataset.symbol, el));
    });

    const firstEl = document.querySelector('.market-item');
    if (firstEl) setActiveMarket(firstEl.dataset.symbol, firstEl);

    function drawChart(){
      if (!ctx || !activeSymbol) return;
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
      const symbol = activeSymbol || '';
      const moneyLabel = marketMeta[symbol] && (marketMeta[symbol].quote === 'NGN' ? '₦' : (marketMeta[symbol].quote === 'USDT' || marketMeta[symbol].quote === 'USD' ? '$' : marketMeta[symbol].quote));
      ctx.fillText('Price: ' + (moneyLabel ? (moneyLabel + ' ') : '') + Number(marketData[activeSymbol].last).toLocaleString(), 8, 18);
    }

    function updateCurrencyLabels(symbol) {
      const meta = marketMeta[symbol];
      if (!meta) return;
      const priceCurrencyEl = document.getElementById('priceCurrency');
      const sizeCurrencyEl = document.getElementById('sizeCurrency');
      const amountCurrencyEl = document.getElementById('amountCurrency');
      const quote = meta.quote || '';
      const base = meta.base || '';
      let quoteSym = quote;
      if (quote === 'NGN') quoteSym = '₦';
      else if (quote === 'USDT' || quote === 'USD') quoteSym = '$';
      priceCurrencyEl.textContent = quoteSym;
      amountCurrencyEl.textContent = quoteSym;
      sizeCurrencyEl.textContent = base;
    }

    // compute size <-> amount
    const priceField = document.getElementById('priceField');
    const sizeField = document.getElementById('sizeField');
    const amountField = document.getElementById('amountField');

    function recalcFromPriceOrAmount() {
      const price = parseFloat(priceField.value) || 0;
      const amount = parseFloat(amountField.value) || 0;
      if (price > 0 && amount > 0) {
        const size = amount / price;
        sizeField.value = size.toFixed(8);
      }
      validateMinBeforeSubmit();
    }
    function recalcFromPriceOrSize() {
      const price = parseFloat(priceField.value) || 0;
      const size = parseFloat(sizeField.value) || 0;
      if (price > 0 && size > 0) {
        const amount = price * size;
        amountField.value = amount.toFixed(4);
      }
      validateMinBeforeSubmit();
    }
    priceField.addEventListener('input', ()=>{ recalcFromPriceOrAmount(); recalcFromPriceOrSize(); });
    amountField.addEventListener('input', recalcFromPriceOrAmount);
    sizeField.addEventListener('input', recalcFromPriceOrSize);

    // client-side min amounts (mirror server rules)
    function minAmountForSymbol(symbol) {
      const meta = marketMeta[symbol];
      if (!meta) return 10000;
      const q = (meta.quote || '').toUpperCase();
      if (q === 'NGN') return 1000.0;
      if (q === 'USDT' || q === 'USD') return 1.0;
      return 10000.0;
    }

    function updateMinNote(symbol) {
      const min = minAmountForSymbol(symbol);
      const q = marketMeta[symbol] ? marketMeta[symbol].quote : '';
      document.getElementById('minNote').textContent = `Minimum trade amount for ${q} markets: ${min} ${q}`;
    }

    function validateMinBeforeSubmit() {
      const symbol = activeSymbol;
      if (!symbol) return true;
      const min = minAmountForSymbol(symbol);
      const amount = parseFloat(amountField.value) || 0;
      const btn = document.querySelector('#orderForm button[type="submit"]');
      if (amount < min) {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        return false;
      } else {
        btn.disabled = false;
        btn.style.opacity = '1';
        return true;
      }
    }

    // simple tick simulation for chart price
    function simulateTick(){
      markets.forEach(s=>{
        const m = marketData[s];
        const baseVol = s.startsWith('BTC')? 0.006 : (s.startsWith('ETH')? 0.015 : 0.02);
        const drift = (Math.random()-0.5) * baseVol * m.last;
        let next = Math.max(0.000001, Math.round(m.last + drift));
        if (Math.random() < 0.02) next += Math.round(m.last * (Math.random()>0.5?0.02:-0.02));
        m.last = next;
        m.prices.push(next); if (m.prices.length>200) m.prices.shift();
      });
      drawChart();
      renderOrderbook();
      updateCountdowns();
    }

    function renderOrderbook(){
      if (!activeSymbol) return;
      const asksRows = document.getElementById('asksRows');
      const bidsRows = document.getElementById('bidsRows');
      asksRows.innerHTML = ''; bidsRows.innerHTML = '';
      const snap = snaps[activeSymbol] || {asks:[], bids:[]};
      const nowLabel = new Date().toLocaleTimeString();
      snap.asks.slice(0,10).forEach(a=>{
        const div = document.createElement('div');
        const price = Number(a.price).toLocaleString();
        const size = Number(a.size).toFixed(8);
        div.style.display='flex'; div.style.justifyContent='space-between'; div.style.padding='4px 0';
        div.innerHTML = `<div class="small">${price}</div><div class="small">${size} <span class="time-label">${nowLabel}</span></div>`;
        asksRows.appendChild(div);
      });
      snap.bids.slice(0,10).forEach(b=>{
        const div = document.createElement('div');
        const price = Number(b.price).toLocaleString();
        const size = Number(b.size).toFixed(8);
        div.style.display='flex'; div.style.justifyContent='space-between'; div.style.padding='4px 0';
        div.innerHTML = `<div class="small">${price}</div><div class="small">${size} <span class="time-label">${nowLabel}</span></div>`;
        bidsRows.appendChild(div);
      });
    }

    // countdowns (MM:SS) using data-expires-ts (unix seconds)
    function updateCountdowns(){
      const nowSec = Math.floor(Date.now() / 1000);
      document.querySelectorAll('.countdown').forEach(el=>{
        const tsRaw = el.getAttribute('data-expires-ts');
        if (!tsRaw) { el.textContent = '--:--'; return; }
        const ts = parseInt(tsRaw, 10);
        if (isNaN(ts)) { el.textContent = '--:--'; return; }
        let diff = ts - nowSec;
        if (diff <= 0) {
          el.textContent = '00:00';
          el.classList.add('expired');
          return;
        }
        const mins = Math.floor(diff / 60);
        const secs = diff % 60;
        el.textContent = String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
      });
    }

    // initial min note and validation
    updateMinNote(activeSymbol);
    validateMinBeforeSubmit();

    setInterval(simulateTick, 1200);
    setInterval(updateCountdowns, 1000);
    window.addEventListener('resize', drawChart);
    drawChart();
    renderOrderbook();
    updateCountdowns();

    // block form submit on invalid min (extra safety)
    document.getElementById('orderForm').addEventListener('submit', function(e){
      if (!validateMinBeforeSubmit()) {
        e.preventDefault();
        alert('Trade amount is below the minimum for this market.');
      }
    });
  </script>
</body>
</html>

 