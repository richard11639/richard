<?php
/* index.php — Single-file trading platform starter
   - Creates trade_db and tables if missing
   - Seeds admin + optional demo user
   - Seeds markets (BTC/USDT,... + BTC/NGN, USDT/NGN) with tick_size/min_size
   - Register/login for real users
   - Wallets with balance & hold_amount (reserves)
   - Deposits & Withdrawals (admin approval)
   - Place orders with holds; matching engine (price-time priority) with transactional settlement
   - Permanent deposit account stored in settings (7050672951, palmpay, ogundele olayinka mary)
   NOTE: This is a functional demo. Harden for production!
*/

ini_set('display_errors',1); error_reporting(E_ALL);
session_start();

// DB config — change as needed
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'tradess_db';

// connect without db to create it if needed
try {
    $pdo0 = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo0->exec("USE `{$DB_NAME}`");
} catch (Exception $e) {
    die("DB host error: " . $e->getMessage());
}

// connect to db
try {
    $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("DB connect error: " . $e->getMessage());
}

/* ================= CREATE TABLES ================= */
$stmts = [];

/* users */
$stmts[] = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) DEFAULT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* wallets */
$stmts[] = "CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    currency VARCHAR(16),
    balance DECIMAL(30,8) DEFAULT 0,
    hold_amount DECIMAL(30,8) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_currency (user_id,currency),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* markets */
$stmts[] = "CREATE TABLE IF NOT EXISTS markets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(64) UNIQUE,
    base_currency VARCHAR(16),
    quote_currency VARCHAR(16),
    tick_size DECIMAL(30,8) DEFAULT 0.01,
    min_size DECIMAL(30,8) DEFAULT 0.00001,
    last_price DECIMAL(30,8) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* orders */
$stmts[] = "CREATE TABLE IF NOT EXISTS orders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    market_id INT,
    side ENUM('buy','sell'),
    type ENUM('limit','market') DEFAULT 'limit',
    price DECIMAL(30,8) DEFAULT NULL,
    size DECIMAL(30,8),
    filled DECIMAL(30,8) DEFAULT 0,
    status ENUM('open','partial','filled','cancelled') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    INDEX (market_id, side, price, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* trades */
$stmts[] = "CREATE TABLE IF NOT EXISTS trades (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    market_id INT,
    buy_order_id BIGINT,
    sell_order_id BIGINT,
    price DECIMAL(30,8),
    size DECIMAL(30,8),
    taker_fee DECIMAL(30,8) DEFAULT 0,
    maker_fee DECIMAL(30,8) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* deposits */
$stmts[] = "CREATE TABLE IF NOT EXISTS deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(30,8),
    currency VARCHAR(16),
    tx_ref VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* withdrawals */
$stmts[] = "CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(30,8),
    currency VARCHAR(16),
    bank VARCHAR(255),
    account_number VARCHAR(64),
    account_name VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* transactions ledger */
$stmts[] = "CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    currency VARCHAR(16),
    type VARCHAR(64),
    amount DECIMAL(30,8),
    meta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

/* settings */
$stmts[] = "CREATE TABLE IF NOT EXISTS settings (
    k VARCHAR(128) PRIMARY KEY,
    v TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

foreach ($stmts as $s) { $pdo->exec($s); }

/* ========== SEED MARKETS ========== */
$marketSymbols = [
  'BTC/USDT','ETH/USDT','DOGE/USDT','BCH/USDT','LTC/USDT','IOTA/USDT','FLOW/USDT','TRX/USDT','BNB/USDT','ETC/USDT','JST/USDT','DOT/USDT',
  'BTC/NGN','USDT/NGN'
];
$msStmt = $pdo->prepare("SELECT COUNT(*) FROM markets WHERE symbol = ?");
$insMarket = $pdo->prepare("INSERT INTO markets (symbol, base_currency, quote_currency, tick_size, min_size) VALUES (?, ?, ?, ?, ?)");
foreach ($marketSymbols as $s) {
    $parts = explode('/', $s);
    $base = $parts[0];
    $quote = $parts[1] ?? 'USDT';
    $msStmt->execute([$s]);
    if ($msStmt->fetchColumn() == 0) {
        $insMarket->execute([$s,$base,$quote,0.01,0.00001]);
    }
}

/* ========== CREATE/LOAD ADMIN + DEMO USER ========= */
$DEMO_USERNAME = 'demo';
$DEMO_EMAIL = 'user@example.com';
$DEMO_PASSWORD = 'user1234';
$ADMIN_EMAIL = 'admin@trade.local';
$ADMIN_PASSWORD = 'Admin@123';

$uCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$uCheck->execute([$ADMIN_EMAIL]);
if (!$uCheck->fetchColumn()) {
    $pwd = password_hash($ADMIN_PASSWORD, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES (?, ?, ?, 'admin')")->execute(['admin',$ADMIN_EMAIL,$pwd]);
}

$uCheck->execute([$DEMO_EMAIL]);
if (!$uCheck->fetchColumn()) {
    $pwd = password_hash($DEMO_PASSWORD, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES (?, ?, ?, 'user')")->execute([$DEMO_USERNAME,$DEMO_EMAIL,$pwd]);
    $demo_id = $pdo->lastInsertId();
    // seed demo wallets (do not overwrite on existing installs)
    $seedW = $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, ?, ?, 0)");
    $seedW->execute([$demo_id, 'NGN', '100000.00']);
    $seedW->execute([$demo_id, 'USDT', '1000.00']);
    $seedW->execute([$demo_id, 'BTC', '0.05']);
} else {
    $demo_id = (int)$pdo->prepare("SELECT id FROM users WHERE email = ?")->execute([$DEMO_EMAIL]) ?: 0;
    // ensure wallets exist (won't change balances if present)
    $userIdRow = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $userIdRow->execute([$DEMO_EMAIL]);
    $demo = $userIdRow->fetch(PDO::FETCH_ASSOC);
    $demo_id = $demo ? (int)$demo['id'] : 0;
    if ($demo_id) {
        $ew = $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, ?, ?, 0)");
        $ew->execute([$demo_id, 'NGN', '100000.00']);
        $ew->execute([$demo_id, 'USDT', '1000.00']);
        $ew->execute([$demo_id, 'BTC', '0.05']);
    }
}

/* ========== SEED SETTINGS (fees + permanent deposit account) ========== */
$st = $pdo->prepare("INSERT INTO settings (k,v) VALUES (:k,:v) ON DUPLICATE KEY UPDATE v=VALUES(v)");
$st->execute([':k'=>'taker_fee_percent', ':v'=>'0.1']);
$st->execute([':k'=>'maker_fee_percent', ':v'=>'0.05']);
$palmpay_info = json_encode(['account_number'=>'7050672951','bank'=>'palmpay','name'=>'ogundele olayinka mary']);
$st->execute([':k'=>'permanent_deposit_account', ':v'=>$palmpay_info]);

/* ========== HELPERS ========== */
function money_fmt($a) { return number_format((float)$a, 2, '.', ','); }
function ensure_wallet(PDO $pdo, $uid, $currency) {
    $ins = $pdo->prepare("INSERT IGNORE INTO wallets (user_id, currency, balance, hold_amount) VALUES (?, ?, 0, 0)");
    $ins->execute([$uid, $currency]);
}
function get_market(PDO $pdo, $market_id) {
    $s = $pdo->prepare("SELECT * FROM markets WHERE id = ?");
    $s->execute([$market_id]); return $s->fetch(PDO::FETCH_ASSOC);
}
function get_setting(PDO $pdo, $k) {
    $s = $pdo->prepare("SELECT v FROM settings WHERE k = ?");
    $s->execute([$k]); return $s->fetchColumn();
}
function ledger(PDO $pdo, $user_id, $currency, $type, $amount, $meta='') {
    $ins = $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$user_id, $currency, $type, $amount, $meta]);
}

/* ========== MATCHING ENGINE (transactional + settlement) ========== */
function match_orders(PDO $pdo, $market_id) {
    $maker_fee_rate = floatval(get_setting($pdo,'maker_fee_percent'))/100.0;
    $taker_fee_rate = floatval(get_setting($pdo,'taker_fee_percent'))/100.0;
    $EPS = 1e-12;

    while (true) {
        try {
            $pdo->beginTransaction();

            // lock best buy and sell
            $buyStmt = $pdo->prepare("SELECT * FROM orders WHERE market_id = ? AND side='buy' AND status IN ('open','partial') AND price IS NOT NULL ORDER BY price DESC, created_at ASC LIMIT 1 FOR UPDATE");
            $sellStmt = $pdo->prepare("SELECT * FROM orders WHERE market_id = ? AND side='sell' AND status IN ('open','partial') AND price IS NOT NULL ORDER BY price ASC, created_at ASC LIMIT 1 FOR UPDATE");
            $buyStmt->execute([$market_id]); $sellStmt->execute([$market_id]);
            $buy = $buyStmt->fetch(PDO::FETCH_ASSOC);
            $sell = $sellStmt->fetch(PDO::FETCH_ASSOC);

            if (!$buy || !$sell) { $pdo->rollBack(); break; }
            if ((float)$buy['price'] + $EPS < (float)$sell['price']) { $pdo->rollBack(); break; }

            // price-time: choose trade_price (seller price)
            $trade_price = (float)$sell['price'];
            $buy_rem = (float)$buy['size'] - (float)$buy['filled'];
            $sell_rem = (float)$sell['size'] - (float)$sell['filled'];
            $trade_size = min($buy_rem, $sell_rem);
            if ($trade_size <= 0) { $pdo->rollBack(); break; }

            $cost = $trade_price * $trade_size;
            $buyer_id = (int)$buy['user_id'];
            $seller_id = (int)$sell['user_id'];

            $m = get_market($pdo,$market_id);
            if (!$m) { $pdo->rollBack(); break; }
            $base = $m['base_currency'];
            $quote = $m['quote_currency'];

            // lock buyer quote wallet and seller base wallet
            $wbq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wsb = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wbq->execute([$buyer_id, $quote]); $buyerQuote = $wbq->fetch(PDO::FETCH_ASSOC);
            $wsb->execute([$seller_id, $base]); $sellerBase = $wsb->fetch(PDO::FETCH_ASSOC);
            if (!$buyerQuote || !$sellerBase) { $pdo->rollBack(); break; }

            // verify holds: buyer's hold_amount must be >= cost, seller's hold_amount >= trade_size
            if ((float)$buyerQuote['hold_amount'] + $EPS < $cost) { $pdo->rollBack(); break; }
            if ((float)$sellerBase['hold_amount'] + $EPS < $trade_size) { $pdo->rollBack(); break; }

            // compute fees
            $taker_fee = $cost * $taker_fee_rate;
            $maker_fee = $cost * $maker_fee_rate;

            // settlement:
            // 1) decrease buyer hold_amount by cost, and deduct cost + taker_fee from buyer.balance
            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance - ? WHERE id = ?")
                ->execute([$cost, $cost + $taker_fee, $buyerQuote['id']]);

            // 2) credit buyer base
            $pdo->prepare("INSERT INTO wallets (user_id,currency,balance,hold_amount) SELECT ?,?,0,0 WHERE NOT EXISTS (SELECT 1 FROM wallets WHERE user_id=? AND currency=?)")
                ->execute([$buyer_id,$base,$buyer_id,$base]);
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")
                ->execute([$trade_size, $buyer_id, $base]);

            // ledger buyer
            ledger($pdo, $buyer_id, $quote, 'trade_cost', -$cost, json_encode(['market'=>$m['symbol'],'size'=>$trade_size,'price'=>$trade_price]));
            if ($taker_fee > 0) ledger($pdo, $buyer_id, $quote, 'fee', -$taker_fee, json_encode(['market'=>$m['symbol'],'role'=>'taker']));

            // 3) decrease seller hold_amount by trade_size, and deduct seller base by trade_size
            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance - ? WHERE id = ?")
                ->execute([$trade_size, $trade_size, $sellerBase['id']]);

            // 4) credit seller quote by (cost - maker_fee)
            $pdo->prepare("INSERT INTO wallets (user_id,currency,balance,hold_amount) SELECT ?,?,0,0 WHERE NOT EXISTS (SELECT 1 FROM wallets WHERE user_id=? AND currency=?)")
                ->execute([$seller_id,$quote,$seller_id,$quote]);
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")
                ->execute([$cost - $maker_fee, $seller_id, $quote]);

            // ledger seller
            ledger($pdo, $seller_id, $base, 'trade_sell', -$trade_size, json_encode(['market'=>$m['symbol'],'price'=>$trade_price]));
            if ($maker_fee > 0) ledger($pdo, $seller_id, $quote, 'fee', -$maker_fee, json_encode(['market'=>$m['symbol'],'role'=>'maker']));

            // insert trade
            $ins = $pdo->prepare("INSERT INTO trades (market_id, buy_order_id, sell_order_id, price, size, taker_fee, maker_fee) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$market_id, $buy['id'], $sell['id'], $trade_price, $trade_size, $taker_fee, $maker_fee]);
            $trade_id = $pdo->lastInsertId();

            // update orders filled & status
            $upd = $pdo->prepare("UPDATE orders SET filled = filled + ?, status = CASE WHEN filled + ? >= size THEN 'filled' ELSE 'partial' END WHERE id = ?");
            $upd->execute([$trade_size, $trade_size, $buy['id']]);
            $upd->execute([$trade_size, $trade_size, $sell['id']]);

            // update market last price
            $pdo->prepare("UPDATE markets SET last_price = ? WHERE id = ?")->execute([$trade_price, $market_id]);

            $pdo->commit();

            // loop to try next match
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // stop matching for this run on any error
            break;
        }
    }
}

/* ========== AUTH + ACTIONS ========== */
$me = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id,username,email,role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $me = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* registration */
$errors = [];
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) $errors[] = 'Email and password required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1"); $chk->execute([$email]);
        if ($chk->fetchColumn()) $errors[] = 'Email already used';
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?, 'user')")->execute([$username,$email,$hash]);
            $uid = $pdo->lastInsertId();
            // create default wallets (NGN,USDT,BTC)
            $insw = $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, ?, 0, 0)");
            foreach (['NGN','USDT','BTC','ETH','DOGE'] as $c) $insw->execute([$uid,$c]);
            $_SESSION['user_id'] = $uid;
            header('Location: index.php'); exit;
        }
    }
}

/* login */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) $errors[] = 'Email and password required';
    else {
        $s = $pdo->prepare("SELECT id,password FROM users WHERE email = ? LIMIT 1");
        $s->execute([$email]); $r = $s->fetch(PDO::FETCH_ASSOC);
        if ($r && password_verify($password, $r['password'])) {
            $_SESSION['user_id'] = $r['id'];
            header('Location: index.php'); exit;
        } else $errors[] = 'Invalid credentials';
    }
}

/* logout */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy(); header('Location: index.php'); exit;
}

/* create deposit request */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_deposit') {
    if (!$me) { header('Location: index.php'); exit; }
    $amount = $_POST['amount'] ?? 0; $currency = $_POST['currency'] ?? 'NGN'; $txref = $_POST['txref'] ?? '';
    if ((float)$amount <= 0) $errors[] = 'Invalid amount';
    else {
        $pdo->prepare("INSERT INTO deposits (user_id,amount,currency,tx_ref,status) VALUES (?, ?, ?, ?, 'pending')")->execute([$me['id'],$amount,$currency,$txref]);
        $messages[] = 'Deposit created (pending admin approval). Transfer to the permanent deposit account and include txref.';
    }
}

/* create withdrawal request */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_withdraw') {
    if (!$me) { header('Location: index.php'); exit; }
    $amount = $_POST['amount'] ?? 0; $currency = $_POST['currency'] ?? 'NGN';
    $bank = trim($_POST['bank'] ?? ''); $account_number = trim($_POST['account_number'] ?? ''); $account_name = trim($_POST['account_name'] ?? '');
    if ((float)$amount <= 0) $errors[] = 'Invalid amount';
    if (!$bank || !$account_number || !$account_name) $errors[] = 'Bank, account number and name required';
    if (empty($errors)) {
        ensure_wallet($pdo, $me['id'], $currency);
        $w = $pdo->prepare("SELECT balance,hold_amount FROM wallets WHERE user_id = ? AND currency = ? LIMIT 1");
        $w->execute([$me['id'],$currency]); $wr = $w->fetch(PDO::FETCH_ASSOC);
        $available = (float)$wr['balance'] - (float)$wr['hold_amount'];
        if ($available < (float)$amount) $errors[] = 'Insufficient available balance';
        else {
            $pdo->prepare("INSERT INTO withdrawals (user_id,amount,currency,bank,account_number,account_name,status) VALUES (?, ?, ?, ?, ?, ?, 'pending')")
                ->execute([$me['id'],$amount,$currency,$bank,$account_number,$account_name]);
            $messages[] = 'Withdrawal created (pending admin approval).';
        }
    }
}

/* place order: reserve funds and insert order */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!$me) { header('Location: index.php'); exit; }
    $market_id = (int)($_POST['market_id'] ?? 0);
    $side = ($_POST['side'] ?? 'buy') === 'sell' ? 'sell' : 'buy';
    $type = ($_POST['type'] ?? 'limit') === 'market' ? 'market' : 'limit';
    $price = isset($_POST['price']) ? (float)$_POST['price'] : null;
    $size = (float)($_POST['size'] ?? 0);

    // basic checks
    if ($market_id <= 0) $errors[] = 'Select market';
    if ($size <= 0) $errors[] = 'Size must be > 0';
    $market = get_market($pdo,$market_id);
    if (!$market) $errors[] = 'Invalid market';
    if ($type === 'limit' && ($price === null || $price <= 0)) $errors[] = 'Price required for limit order';

    if (empty($errors)) {
        // compute cost (quote)
        $trade_price = $price;
        $cost = $trade_price * $size;

        try {
            $pdo->beginTransaction();

            // ensure wallets exist
            ensure_wallet($pdo, $me['id'], $market['base_currency']);
            ensure_wallet($pdo, $me['id'], $market['quote_currency']);

            if ($side === 'buy') {
                // reserve cost in quote: increase hold_amount
                $wq = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wq->execute([$me['id'],$market['quote_currency']]); $wqr = $wq->fetch(PDO::FETCH_ASSOC);
                if (!$wqr) { $pdo->rollBack(); $errors[] = 'Wallet not found'; }
                else {
                    $available = (float)$wqr['balance'] - (float)$wqr['hold_amount'];
                    if ($available < $cost) { $pdo->rollBack(); $errors[] = 'Insufficient available balance to place buy order'; }
                    else {
                        $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount + ? WHERE id = ?")->execute([$cost, $wqr['id']]);
                    }
                }
            } else {
                // sell: reserve base size
                $wb = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wb->execute([$me['id'],$market['base_currency']]); $wbr = $wb->fetch(PDO::FETCH_ASSOC);
                if (!$wbr) { $pdo->rollBack(); $errors[] = 'Wallet not found'; }
                else {
                    $available = (float)$wbr['balance'] - (float)$wbr['hold_amount'];
                    if ($available < $size) { $pdo->rollBack(); $errors[] = 'Insufficient available balance to place sell order'; }
                    else { $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount + ? WHERE id = ?")->execute([$size, $wbr['id']]); }
                }
            }

            if (empty($errors)) {
                $ins = $pdo->prepare("INSERT INTO orders (user_id,market_id,side,type,price,size,filled,status) VALUES (?, ?, ?, ?, ?, ?, 0, 'open')");
                $ins->execute([$me['id'],$market_id,$side,$type,$price,$size]);
                $order_id = $pdo->lastInsertId();
                $pdo->commit();

                // run matching engine for this market (settlement happens inside)
                match_orders($pdo, $market_id);
                $messages[] = 'Order placed (matching attempted).';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Order error: '.$e->getMessage();
        }
    }
}

/* Admin actions: approve deposit/withdrawal */
if ($me && $me['role'] === 'admin' && isset($_GET['admin_action'])) {
    $act = $_GET['admin_action'];
    if ($act === 'approve_deposit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $s = $pdo->prepare("SELECT * FROM deposits WHERE id = ? FOR UPDATE"); $s->execute([$id]); $d = $s->fetch(PDO::FETCH_ASSOC);
        if ($d && $d['status'] === 'pending') {
            $pdo->beginTransaction();
            try {
                // credit balance
                ensure_wallet($pdo, $d['user_id'], $d['currency']);
                $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")->execute([$d['amount'],$d['user_id'],$d['currency']]);
                ledger($pdo,$d['user_id'],$d['currency'],'deposit',$d['amount'],json_encode(['deposit_id'=>$d['id']]));
                $pdo->prepare("UPDATE deposits SET status='approved' WHERE id = ?")->execute([$id]);
                $pdo->commit();
            } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); }
        }
        header('Location: index.php?action=admin'); exit;
    }
    if ($act === 'approve_withdraw' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $s = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? FOR UPDATE"); $s->execute([$id]); $wrow = $s->fetch(PDO::FETCH_ASSOC);
        if ($wrow && $wrow['status'] === 'pending') {
            $pdo->beginTransaction();
            try {
                // check available
                ensure_wallet($pdo, $wrow['user_id'], $wrow['currency']);
                $w = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $w->execute([$wrow['user_id'],$wrow['currency']]); $wr = $w->fetch(PDO::FETCH_ASSOC);
                $available = (float)$wr['balance'] - (float)$wr['hold_amount'];
                if ($available + 0.00000001 < (float)$wrow['amount']) {
                    $pdo->prepare("UPDATE withdrawals SET status='rejected' WHERE id = ?")->execute([$id]);
                    $pdo->commit();
                } else {
                    // debit balance
                    $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?")->execute([$wrow['amount'],$wr['id']]);
                    ledger($pdo, $wrow['user_id'], $wrow['currency'], 'withdrawal', -$wrow['amount'], json_encode(['withdrawal_id'=>$id,'bank'=>$wrow['bank'],'acc'=>$wrow['account_number']]));
                    $pdo->prepare("UPDATE withdrawals SET status='approved' WHERE id = ?")->execute([$id]);
                    $pdo->commit();
                }
            } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); }
        }
        header('Location: index.php?action=admin'); exit;
    }
}

/* quick helpers for views */
function setting($k) { global $pdo; return get_setting($pdo,$k); }

/* ========== UI Data ========= */
$markets = $pdo->query("SELECT * FROM markets WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$permanent_deposit = json_decode(setting('permanent_deposit_account'), true);

/* If user logged in, load wallets/orders/trades */
$user_wallets = [];
$openOrders = []; $recentTrades = []; $txs = [];
if ($me) {
    $st = $pdo->prepare("SELECT currency,balance,hold_amount FROM wallets WHERE user_id = ?");
    $st->execute([$me['id']]); $user_wallets = $st->fetchAll(PDO::FETCH_ASSOC);
    $q = $pdo->prepare("SELECT o.*, m.symbol FROM orders o JOIN markets m ON o.market_id = m.id WHERE o.user_id = ? ORDER BY created_at DESC LIMIT 200");
    $q->execute([$me['id']]); $openOrders = $q->fetchAll(PDO::FETCH_ASSOC);
    $tq = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 100"); $tq->execute([$me['id']]); $txs = $tq->fetchAll(PDO::FETCH_ASSOC);
}
$recentTrades = $pdo->query("SELECT t.*, m.symbol FROM trades t JOIN markets m ON t.market_id = m.id ORDER BY t.created_at DESC LIMIT 50")->fetchAll();

/* ======== Render UI ======== */
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Company Exchange — Trade Platform</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--accent:#1d4ed8}
body{font-family:Inter,Arial,sans-serif;margin:0;background:var(--bg);color:#0b1220}
.header{background:#0b1220;color:#fff;padding:12px 18px;display:flex;justify-content:space-between;align-items:center}
.container{max-width:1200px;margin:18px auto;padding:12px}
.topnav a{color:#fff;text-decoration:none;padding:8px 10px;border-radius:6px;background:#1f2937;margin-right:8px}
.grid{display:grid;grid-template-columns:320px 1fr;gap:18px}
.card{background:var(--card);padding:14px;border-radius:10px;box-shadow:0 6px 20px rgba(2,6,23,0.06)}
.table{width:100%;border-collapse:collapse}
.table td,.table th{padding:8px;border-bottom:1px solid #eef2f7}
.btn{padding:8px 12px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}
.input, select {padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%;margin-top:6px;margin-bottom:8px}
.small{font-size:13px;color:#64748b}
.notice{background:#fffbe6;padding:8px;border-left:4px solid #ffecb5;border-radius:6px;margin-bottom:10px}
.market-item{padding:8px;border-radius:8px;border:1px solid #eef2f7;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;cursor:pointer}
.market-item.active{background:#f0f9ff;border-color:#cfeeff}
.footer{padding:12px;text-align:center;color:#76829a}
</style>
</head>
<body>
<header class="header">
  <div style="font-weight:700">Company Exchange</div>
  <div>
    <a class="topnav" href="index.php">Dashboard</a>
    <a class="topnav" href="index.php?action=markets">Markets</a>
    <a class="topnav" href="index.php?action=deposit">Deposit</a>
    <a class="topnav" href="index.php?action=withdraw">Withdraw</a>
    <a class="topnav" href="index.php?action=place">Exchange</a>
    <?php if ($me && $me['role']=='admin') echo '<a class="topnav" href="index.php?action=admin">Admin</a>'; ?>
    <?php if (!$me): ?>
      <a class="topnav" href="index.php?action=login">Login</a>
      <a class="topnav" href="index.php?action=register">Register</a>
    <?php else: ?>
      <span style="color:#fff;margin-left:10px">Hi, <?php echo htmlspecialchars($me['username']?:$me['email']); ?></span>
      <a class="topnav" href="index.php?action=logout">Logout</a>
    <?php endif; ?>
  </div>
</header>

<div class="container">
  <?php foreach ($errors as $e) echo "<div class='notice' style='border-left-color:#f87171'>{htmlspecialchars($e)}</div>"; ?>
  <?php foreach ($messages as $m) echo "<div class='notice'>{htmlspecialchars($m)}</div>"; ?>

  <?php
  $action = $_GET['action'] ?? 'home';

  /* ========== Login form ========== */
  if ($action === 'login' && !$me): ?>
    <div class="card" style="max-width:420px;margin:0 auto">
      <h3>Login</h3>
      <form method="post">
        <input type="hidden" name="action" value="login">
        <input class="input" name="email" placeholder="Email" required>
        <input class="input" name="password" type="password" placeholder="Password" required>
        <button class="btn">Login</button>
      </form>
      <p class="small">Seed admin: admin@trade.local / Admin@123. Change immediately in production.</p>
    </div>

  <?php elseif ($action === 'register' && !$me): ?>
    <div class="card" style="max-width:520px;margin:0 auto">
      <h3>Create an account</h3>
      <form method="post">
        <input type="hidden" name="action" value="register">
        <input class="input" name="username" placeholder="Full name">
        <input class="input" name="email" placeholder="Email" required>
        <input class="input" name="password" type="password" placeholder="Password" required>
        <button class="btn">Register & Start</button>
      </form>
    </div>

  <?php elseif ($action === 'deposit' && $me): ?>
    <div class="card" style="max-width:600px;margin:0 auto">
      <h3>Create Deposit Request</h3>
      <form method="post">
        <input type="hidden" name="action" value="create_deposit">
        <input class="input" name="amount" placeholder="Amount" required>
        <select class="input" name="currency"><option>NGN</option><option>USDT</option><option>BTC</option></select>
        <input class="input" name="txref" placeholder="Transaction reference (optional)">
        <button class="btn">Create Deposit Request</button>
      </form>
      <div style="margin-top:12px">
        <strong>Permanent deposit account</strong>
        <div class="small">Account: <?php echo htmlspecialchars($permanent_deposit['account_number']); ?> — Bank: <?php echo htmlspecialchars($permanent_deposit['bank']); ?> — Name: <?php echo htmlspecialchars($permanent_deposit['name']); ?></div>
        <div class="small">Make transfer to the account above and submit a deposit request with TX ref. Admin must approve to credit your wallet.</div>
      </div>
    </div>

  <?php elseif ($action === 'withdraw' && $me): ?>
    <div class="card" style="max-width:600px;margin:0 auto">
      <h3>Create Withdrawal Request</h3>
      <form method="post">
        <input type="hidden" name="action" value="create_withdraw">
        <input class="input" name="amount" placeholder="Amount" required>
        <select class="input" name="currency"><option>NGN</option><option>USDT</option><option>BTC</option></select>
        <input class="input" name="bank" placeholder="Bank">
        <input class="input" name="account_number" placeholder="Account number">
        <input class="input" name="account_name" placeholder="Account name">
        <button class="btn">Create Withdrawal Request</button>
      </form>
      <div class="small">Withdrawals are pending admin approval. Only available balance (balance - hold_amount) is withdrawable.</div>
    </div>

  <?php elseif ($action === 'place' && $me): ?>
    <div class="grid">
      <aside class="card">
        <h3>Markets</h3>
        <div>
          <?php foreach ($markets as $m): ?>
            <div class="market-item" data-id="<?php echo $m['id']; ?>" data-symbol="<?php echo htmlspecialchars($m['symbol']); ?>"><?php echo htmlspecialchars($m['symbol']); ?> <span class="small"><?php echo htmlspecialchars($m['base_currency'].'/'.$m['quote_currency']); ?></span></div>
          <?php endforeach; ?>
        </div>

        <h4 style="margin-top:12px">Your wallets</h4>
        <table class="table">
          <tr><th>Currency</th><th>Balance</th><th>On hold</th><th>Available</th></tr>
          <?php foreach ($user_wallets as $w): $avail = (float)$w['balance'] - (float)$w['hold_amount']; ?>
            <tr><td><?php echo htmlspecialchars($w['currency']); ?></td><td><?php echo money_fmt($w['balance']); ?></td><td><?php echo money_fmt($w['hold_amount']); ?></td><td><?php echo money_fmt($avail); ?></td></tr>
          <?php endforeach; ?>
        </table>
      </aside>

      <section class="card">
        <h3 id="marketTitle">Select market</h3>
        <div style="display:flex;gap:12px">
          <div style="flex:1">
            <form method="post">
              <input type="hidden" name="action" value="place_order">
              <input type="hidden" name="market_id" id="market_id" value="">
              <label class="small">Side
                <select class="input" name="side"><option value="buy">Buy</option><option value="sell">Sell</option></select>
              </label>
              <label class="small">Price (quote)
                <input class="input" type="number" step="0.0001" name="price" id="price" placeholder="Price (required for limit)" required>
              </label>
              <label class="small">Size (base)
                <input class="input" type="number" step="0.00000001" name="size" id="size" required>
              </label>
              <button class="btn">Place order</button>
            </form>
          </div>

          <div style="width:360px">
            <h4>Orderbook snapshot</h4>
            <div style="display:flex;gap:8px">
              <div style="flex:1;padding:8px;border:1px solid #eef2f7;border-radius:8px;background:#fff"><strong>Asks</strong><div id="asks"></div></div>
              <div style="flex:1;padding:8px;border:1px solid #eef2f7;border-radius:8px;background:#fff"><strong>Bids</strong><div id="bids"></div></div>
            </div>
          </div>
        </div>

        <div style="margin-top:12px">
          <h4>Your orders</h4>
          <table class="table">
            <tr><th>Time</th><th>Market</th><th>Side</th><th>Price</th><th>Remaining</th><th>Status</th></tr>
            <?php foreach ($openOrders as $o): ?>
              <tr><td class="small"><?php echo $o['created_at']; ?></td><td><?php echo htmlspecialchars($o['symbol']); ?></td><td><?php echo htmlspecialchars($o['side']); ?></td><td><?php echo $o['price']; ?></td><td><?php echo number_format($o['size'] - $o['filled'],8); ?></td><td><?php echo htmlspecialchars($o['status']); ?></td></tr>
            <?php endforeach; ?>
          </table>
        </div>

        <div style="margin-top:12px">
          <h4>Recent trades</h4>
          <table class="table"><tr><th>Time</th><th>Market</th><th>Price</th><th>Size</th></tr>
            <?php foreach ($recentTrades as $t): ?>
              <tr><td class="small"><?php echo $t['created_at']; ?></td><td><?php echo htmlspecialchars($t['symbol']); ?></td><td><?php echo number_format($t['price'],8); ?></td><td><?php echo number_format($t['size'],8); ?></td></tr>
            <?php endforeach; ?>
          </table>
        </div>
      </section>
    </div>

    <script>
      // tiny UI logic: pick first market, fill price field with last_price if present
      const markets = <?php echo json_encode($markets); ?>;
      if (markets.length) {
        const first = markets[0];
        document.getElementById('market_id').value = first.id;
        document.getElementById('marketTitle').innerText = first.symbol;
        document.getElementById('price').value = first.last_price || '';
      }
      document.querySelectorAll('.market-item').forEach(el=>{
        el.addEventListener('click', ()=> {
          document.querySelectorAll('.market-item').forEach(x=>x.classList.remove('active'));
          el.classList.add('active');
          const id = el.dataset.id; const symbol = el.dataset.symbol;
          document.getElementById('market_id').value = id;
          document.getElementById('marketTitle').innerText = symbol;
        });
      });

      // crude orderbook snapshot simulation for visual only
      const asks = document.getElementById('asks'), bids = document.getElementById('bids');
      function renderOB() {
        asks.innerHTML=''; bids.innerHTML='';
        const last = markets[0] ? (markets[0].last_price || 100) : 100;
        for (let i=1;i<=6;i++){
          const price = Math.round((last + i* (last*0.002))*100)/100;
          const size = (Math.random()*5).toFixed(4);
          asks.innerHTML += `<div style="display:flex;justify-content:space-between;padding:4px 0"><small>${price}</small><small>${size}</small></div>`;
          const price2 = Math.round((last - i*(last*0.002))*100)/100;
          bids.innerHTML += `<div style="display:flex;justify-content:space-between;padding:4px 0"><small>${price2}</small><small>${(Math.random()*5).toFixed(4)}</small></div>`;
        }
      }
      renderOB();
      setInterval(renderOB,1200);
    </script>

  <?php elseif ($action === 'admin' && $me && $me['role']==='admin'): ?>
    <div class="card">
      <h3>Admin Panel</h3>
      <h4>Pending deposits</h4>
      <?php $deps = $pdo->query("SELECT d.*, u.email FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.status='pending' ORDER BY d.created_at ASC")->fetchAll(PDO::FETCH_ASSOC); ?>
      <table class="table"><tr><th>ID</th><th>User</th><th>Amount</th><th>Currency</th><th>TX</th><th>Action</th></tr>
        <?php foreach($deps as $d): ?>
          <tr><td><?php echo $d['id']; ?></td><td><?php echo htmlspecialchars($d['email']); ?></td><td><?php echo $d['amount']; ?></td><td><?php echo $d['currency']; ?></td><td><?php echo htmlspecialchars($d['tx_ref']); ?></td>
            <td><a href="index.php?action=admin&admin_action=approve_deposit&id=<?php echo $d['id']; ?>">Approve</a></td></tr>
        <?php endforeach; ?>
      </table>

      <h4 style="margin-top:12px">Pending withdrawals</h4>
      <?php $wds = $pdo->query("SELECT w.*, u.email FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status='pending' ORDER BY w.created_at ASC")->fetchAll(PDO::FETCH_ASSOC); ?>
      <table class="table"><tr><th>ID</th><th>User</th><th>Amount</th><th>Currency</th><th>Bank</th><th>Account</th><th>Action</th></tr>
        <?php foreach($wds as $w): ?>
          <tr><td><?php echo $w['id']; ?></td><td><?php echo htmlspecialchars($w['email']); ?></td><td><?php echo $w['amount']; ?></td><td><?php echo htmlspecialchars($w['currency']); ?></td><td><?php echo htmlspecialchars($w['bank']); ?></td><td><?php echo htmlspecialchars($w['account_number']); ?></td>
            <td><a href="index.php?action=admin&admin_action=approve_withdraw&id=<?php echo $w['id']; ?>">Approve</a></td></tr>
        <?php endforeach; ?>
      </table>
    </div>

  <?php else: /* homepage */ ?>
    <div class="card">
      <h3>Welcome to Company Exchange</h3>
      <?php if (!$me): ?>
        <p class="small">Create an account or login to start trading. Deposits must be approved by admin to credit wallets. Permanent deposit account: <?php echo htmlspecialchars($permanent_deposit['account_number']); ?> — <?php echo htmlspecialchars($permanent_deposit['bank']); ?> — <?php echo htmlspecialchars($permanent_deposit['name']); ?></p>
        <div style="display:flex;gap:12px;margin-top:12px">
          <a class="btn" href="index.php?action=register">Create account</a>
          <a class="btn" href="index.php?action=login">Login</a>
        </div>
      <?php else: ?>
        <div style="display:flex;gap:12px">
          <div style="flex:1">
            <h4>Your wallets</h4>
            <table class="table">
              <tr><th>Currency</th><th>Balance</th><th>On hold</th><th>Available</th></tr>
              <?php foreach($user_wallets as $w): $avail = (float)$w['balance'] - (float)$w['hold_amount']; ?>
                <tr><td><?php echo htmlspecialchars($w['currency']); ?></td><td><?php echo money_fmt($w['balance']); ?></td><td><?php echo money_fmt($w['hold_amount']); ?></td><td><?php echo money_fmt($avail); ?></td></tr>
              <?php endforeach; ?>
            </table>
          </div>

          <div style="width:420px">
            <h4>Markets</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <?php foreach($markets as $m): ?>
                <div style="background:#fff;padding:8px;border-radius:8px;border:1px solid #eef2f7">
                  <div style="font-weight:600"><?php echo htmlspecialchars($m['symbol']); ?></div>
                  <div class="small">Last: <?php echo number_format($m['last_price'],8); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div style="margin-top:12px">
          <h4>Recent activity</h4>
          <div style="display:flex;gap:12px">
            <div style="flex:1" class="card">
              <h5>Transactions</h5>
              <div style="max-height:200px;overflow:auto">
                <?php foreach($txs as $t): ?>
                  <div style="padding:6px;border-bottom:1px dashed #eee"><strong><?php echo htmlspecialchars($t['currency']); ?> <?php echo $t['amount']; ?></strong><div class="small"><?php echo htmlspecialchars($t['type'].' '.$t['created_at']); ?></div></div>
                <?php endforeach; ?>
              </div>
            </div>

            <div style="width:420px" class="card">
              <h5>Recent trades</h5>
              <table class="table"><tr><th>Time</th><th>Market</th><th>Price</th><th>Size</th></tr>
                <?php foreach($recentTrades as $t): ?>
                  <tr><td class="small"><?php echo $t['created_at']; ?></td><td><?php echo htmlspecialchars($t['symbol']); ?></td><td><?php echo number_format($t['price'],8); ?></td><td><?php echo number_format($t['size'],8); ?></td></tr>
                <?php endforeach; ?>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<footer class="footer">Company Exchange — Demo starter. Remember to secure this app before production: HTTPS, CSRF, input validation, session hardening, rate limits, logging.</footer>
</body>
</html>
m