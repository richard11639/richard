<?php
/**
 * index.php — Demo trading platform (single-file)
 * - Creates DB + tables if missing
 * - Seeds markets + admin
 * - Register / Login / Logout
 * - Deposit (multi-step) -> creates deposit_request (pending)
 * - Withdraw (creates withdraw_request pending)
 * - Admin approves deposits (credits wallets) / processes withdraws
 * - Place limit orders (reserves funds via hold_amount)
 * - Matching engine to match/settle orders (transactional)
 *
 * FOR LEARNING ONLY.
 */

/* ========== CONFIG ========== */
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'trade_db';
date_default_timezone_set('Africa/Lagos');

/* ========== SESSION ========== */
session_set_cookie_params([
    'lifetime'=>0,
    'path'=>'/',
    'domain'=>'',
    'secure'=>false, // set true when on HTTPS
    'httponly'=>true,
    'samesite'=>'Lax'
]);
if (session_status()===PHP_SESSION_NONE) session_start();

/* ========== CONNECT + CREATE DB ========== */
try {
    $dsnNoDb = "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4";
    $pdo0 = new PDO($dsnNoDb, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) { die("DB server connection failed: ".htmlspecialchars($e->getMessage())); }

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) { die("DB connect failed: ".htmlspecialchars($e->getMessage())); }

/* ========== CREATE TABLES ========== */
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

"CREATE TABLE IF NOT EXISTS deposit_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(12) NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  account_number VARCHAR(80) NULL,
  bank_name VARCHAR(150) NULL,
  account_name VARCHAR(150) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_by INT NULL,
  approved_at TIMESTAMP NULL,
  CONSTRAINT fk_deposit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS withdraw_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(12) NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  account_number VARCHAR(80) NULL,
  bank_name VARCHAR(150) NULL,
  account_name VARCHAR(150) NULL,
  status ENUM('pending','processed','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_by INT NULL,
  processed_at TIMESTAMP NULL,
  CONSTRAINT fk_withdraw_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
    try { $pdo->exec($sql); } catch (Exception $e) { die("DB create error: ".htmlspecialchars($e->getMessage())); }
}

/* ========== SEED MARKETS + ADMIN ========== */
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

// seed admin
try {
    $adminCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $adminCheck->execute(['admin','admin@example.com']);
    if (!$adminCheck->fetch()) {
        $adminPass = 'Admin123!';
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $insAdmin = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        $insAdmin->execute(['admin','admin@example.com',$hash]);
    }
} catch (Exception $e) {
    // ignore
}

/* ========== HELPERS ========== */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}
function check_csrf($t) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$t);
}
function money_fmt($a) { return number_format((float)$a, 2, '.', ','); }
function require_login() { if (empty($_SESSION['user_id'])) { header('Location: ?view=login'); exit; } }
function require_admin() { if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ?view=login'); exit; } }
function ensure_wallet(PDO $pdo, $uid, $currency) {
    $ins = $pdo->prepare("INSERT IGNORE INTO wallets (user_id, currency, balance, hold_amount) VALUES (?, ?, 0, 0)");
    $ins->execute([$uid, $currency]);
}
function get_market($pdo, $market_id) {
    $s = $pdo->prepare("SELECT * FROM markets WHERE id = ?");
    $s->execute([$market_id]); return $s->fetch();
}
function get_base_currency($pdo, $market_id) { $m = get_market($pdo,$market_id); return $m ? $m['base_currency'] : null; }
function get_quote_currency($pdo, $market_id) { $m = get_market($pdo,$market_id); return $m ? $m['quote_currency'] : null; }

/* ========== MATCHING ENGINE (transactional & corrected) ========== */
function match_orders(PDO $pdo, $market_id) {
    $maker_fee_rate = 0.001; // 0.1%
    $taker_fee_rate = 0.002; // 0.2%

    while (true) {
        try {
            $pdo->beginTransaction();

            // lock best buy & sell
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

            $taker_fee = $cost * $taker_fee_rate;
            $maker_fee = $cost * $maker_fee_rate;

            $base = get_base_currency($pdo,$market_id);
            $quote = get_quote_currency($pdo,$market_id);

            // ensure wallet exists
ensure_wallet($pdo, $uid, $currency);

// lock wallet row
$w = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
$w->execute([$uid, $currency]);
$wr = $w->fetch();

$new_balance = (float)$wr['balance'] + $amount;

// update wallet balance
$pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?")->execute([$new_balance, $wr['id']]);

// add transaction record
$pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'deposit', ?, ?)")
    ->execute([$uid, $currency, $amount, json_encode(['deposit_request_id'=>$pdo->lastInsertId()])]);

            if (!$buyerQuote || !$sellerBase) { $pdo->rollBack(); break; }
            // check holds
            if ((float)$buyerQuote['hold_amount'] + 1e-12 < ($cost + $taker_fee)) { $pdo->rollBack(); break; }
            if ((float)$sellerBase['hold_amount'] + 1e-12 < $trade_size) { $pdo->rollBack(); break; }

            // insert trade
            $insTrade = $pdo->prepare("INSERT INTO trades (market_id, buy_order_id, sell_order_id, price, size, buy_user_id, sell_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insTrade->execute([$market_id, $buy['id'], $sell['id'], $trade_price, $trade_size, $buyer_id, $seller_id]);
            $trade_id = $pdo->lastInsertId();

            // update orders' filled & status
            $updOrder = $pdo->prepare("UPDATE orders SET filled = filled + ?, status = CASE WHEN filled + ? >= size THEN 'filled' ELSE 'partial' END WHERE id = ?");
            $updOrder->execute([$trade_size, $trade_size, $buy['id']]);
            $updOrder->execute([$trade_size, $trade_size, $sell['id']]);

            // SETTLEMENT
            // Buyer (quote): reduce hold_amount and permanently deduct cost + taker_fee from balance
            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance - ? WHERE user_id = ? AND currency = ?")
                ->execute([$cost + $taker_fee, $cost + $taker_fee, $buyer_id, $quote]);

            // Buyer (base): credit base
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")
                ->execute([$trade_size, $buyer_id, $base]);

            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_buy', ?, ?)")
                ->execute([$buyer_id, $base, $trade_size, json_encode(['trade_id'=>$trade_id,'price'=>$trade_price])]);

            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_cost', ?, ?)")
                ->execute([$buyer_id, $quote, -$cost, json_encode(['trade_id'=>$trade_id])]);

            if ($taker_fee > 0) {
                $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'fee', ?, ?)")
                    ->execute([$buyer_id, $quote, -$taker_fee, json_encode(['trade_id'=>$trade_id,'role'=>'taker'])]);
            }

            // Seller (base): reduce hold_amount and permanently deduct sold base from balance
            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance - ? WHERE user_id = ? AND currency = ?")
                ->execute([$trade_size, $trade_size, $seller_id, $base]);

            // Seller (quote): credit proceeds minus maker_fee
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")
                ->execute([$cost - $maker_fee, $seller_id, $quote]);

            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_sell', ?, ?)")
                ->execute([$seller_id, $quote, $cost - $maker_fee, json_encode(['trade_id'=>$trade_id,'price'=>$trade_price])]);

            if ($maker_fee > 0) {
                $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'fee', ?, ?)")
                    ->execute([$seller_id, $quote, -$maker_fee, json_encode(['trade_id'=>$trade_id,'role'=>'maker'])]);
            }

            $pdo->commit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // break loop; in production you'd log the error and investigate.
            break;
        }
    }
}

/* ========== ROUTING & POST HANDLERS ========== */
$view = $_GET['view'] ?? 'home';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$token = csrf_token();

/* ========== POST HANDLERS ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // REGISTER
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=register'); exit; }
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$email || !$password) { $_SESSION['flash']='Complete all fields'; header('Location:?view=register'); exit; }
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username,email,password) VALUES (?, ?, ?)");
            $stmt->execute([$username,$email,$hash]);
            $uid = $pdo->lastInsertId();
            // create default NGN & USDT wallets for convenience
            ensure_wallet($pdo, $uid, 'NGN'); ensure_wallet($pdo, $uid, 'USDT'); ensure_wallet($pdo, $uid, 'BTC');
            $_SESSION['user_id'] = $uid;
            $_SESSION['role'] = 'user';
            $_SESSION['flash']='Registered and logged in';
            header('Location:?view=home'); exit;
        } catch (Exception $e) {
            $_SESSION['flash']='Registration error: '.$e->getMessage();
            header('Location:?view=register'); exit;
        }
    }

    // LOGIN
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=login'); exit; }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT id,password,role FROM users WHERE email = ?");
        $stmt->execute([$email]); $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['flash']='Logged in';
            header('Location:?view=home'); exit;
        } else {
            $_SESSION['flash']='Invalid credentials';
            header('Location:?view=login'); exit;
        }
    }

    // LOGOUT
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        session_unset(); session_destroy(); session_start();
        $_SESSION['flash']='Logged out';
        header('Location:?view=login'); exit;
    }

    // PLACE ORDER (reserving hold_amount only)
    if (isset($_POST['action']) && $_POST['action'] === 'place_order') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=exchange'); exit; }
        $market_id = (int)($_POST['market_id'] ?? 0);
        $side = $_POST['side'] ?? 'buy';
        $type = $_POST['type'] ?? 'limit';
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
        $size = (float)($_POST['size'] ?? 0);
        if ($size <= 0) { $_SESSION['flash']='Invalid size'; header('Location:?view=exchange'); exit; }
        $market = get_market($pdo,$market_id);
        if (!$market) { $_SESSION['flash']='Market not found'; header('Location:?view=exchange'); exit; }
        $base = $market['base_currency']; $quote = $market['quote_currency'];
        $max_fee_rate = 0.002;
        try {
            $pdo->beginTransaction();
            if ($side === 'buy') {
                if (!$price) throw new Exception('Price required for buy limit');
                $cost = $price * $size;
                $reserve = $cost + ($cost * $max_fee_rate);
                $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wq->execute([$_SESSION['user_id'],$quote]); $wqr = $wq->fetch();
                if (!$wqr || ((float)$wqr['balance'] - (float)$wqr['hold_amount']) < $reserve) throw new Exception('Insufficient available quote balance to reserve cost+fee');
                // only increase hold_amount
                $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")
                    ->execute([$reserve, $wqr['id']]);
            } else {
                $wb = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                $wb->execute([$_SESSION['user_id'],$base]); $wbr = $wb->fetch();
                if (!$wbr || ((float)$wbr['balance'] - (float)$wbr['hold_amount']) < $size) throw new Exception('Insufficient available base balance to reserve');
                $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")
                    ->execute([$size, $wbr['id']]);
            }
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, market_id, side, type, price, size) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $market_id, $side, $type, $price, $size]);
            $order_id = $pdo->lastInsertId();
            // attempt matching (settlement happens inside)
            match_orders($pdo, $market_id);
            $pdo->commit();
            $_SESSION['flash']='Order placed';
            header('Location:?view=exchange'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Order error: '.$e->getMessage();
            header('Location:?view=exchange'); exit;
        }
    }

    // DEPOSIT: multi-step internal flow
    if (isset($_POST['action']) && $_POST['action'] === 'deposit_start') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=deposit'); exit; }
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount < 100) { $_SESSION['flash']='Minimum deposit is ₦100'; header('Location:?view=deposit'); exit; }
        $_SESSION['deposit_amount'] = $amount;
        header('Location:?view=deposit&step=confirm'); exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'deposit_confirm') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=deposit'); exit; }
        if (empty($_SESSION['deposit_amount'])) { $_SESSION['flash']='Session expired'; header('Location:?view=deposit'); exit; }
        $amount = (float)$_SESSION['deposit_amount'];
        if ($amount <= 0) { $_SESSION['flash']='Invalid amount'; header('Location:?view=deposit'); exit; }
        // permanent account details (per your earlier request)
        $account_number = '7050672951';
        $bank_name = 'Palmpay';
        $account_name = 'Ogundele Olayinka Mary';
        try {
            $stmt = $pdo->prepare("INSERT INTO deposit_requests (user_id, currency, amount, account_number, bank_name, account_name, status, created_at) VALUES (?, 'NGN', ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$_SESSION['user_id'], $amount, $account_number, $bank_name, $account_name]);
            $reqId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'NGN', 'deposit_request', ?, ?)")->execute([$_SESSION['user_id'],$amount,json_encode(['deposit_request_id'=>$reqId])]);
            unset($_SESSION['deposit_amount']);
            $_SESSION['flash']="Deposit request created (#{$reqId}) — pending admin approval.";
            header('Location:?view=home'); exit;
        } catch (Exception $e) {
            $_SESSION['flash']='Deposit error: '.$e->getMessage();
            header('Location:?view=deposit'); exit;
        }
    }

    // WITHDRAW: create withdraw_request (pending)
    if (isset($_POST['action']) && $_POST['action'] === 'withdraw_request') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=withdraw'); exit; }
        $amount = (float)($_POST['amount'] ?? 0);
        $currency = $_POST['currency'] ?? 'NGN';
        $acc_num = trim($_POST['account_number'] ?? '');
        $bank_name = trim($_POST['bank_name'] ?? '');
        $acc_name = trim($_POST['account_name'] ?? '');
        if ($amount <= 0 || !$acc_num || !$bank_name || !$acc_name) { $_SESSION['flash']='Complete all fields'; header('Location:?view=withdraw'); exit; }
        try {
            $pdo->beginTransaction();
            ensure_wallet($pdo, $_SESSION['user_id'], $currency);
            $w = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $w->execute([$_SESSION['user_id'],$currency]); $wr = $w->fetch();
            $available = (float)$wr['balance'] - (float)$wr['hold_amount'];
            if ($available < $amount) { $pdo->rollBack(); $_SESSION['flash']='Insufficient available balance'; header('Location:?view=withdraw'); exit; }
            // create withdraw request (admin will process)
            $ins = $pdo->prepare("INSERT INTO withdraw_requests (user_id, currency, amount, account_number, bank_name, account_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $ins->execute([$_SESSION['user_id'], $currency, $amount, $acc_num, $bank_name, $acc_name]);
            $reqId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'withdraw_request', ?, ?)")->execute([$_SESSION['user_id'],$currency,-$amount,json_encode(['withdraw_request_id'=>$reqId])]);
            $pdo->commit();
            $_SESSION['flash']="Withdraw request submitted (#{$reqId}) — pending admin processing.";
            header('Location:?view=home'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Withdraw error: '.$e->getMessage();
            header('Location:?view=withdraw'); exit;
        }
    }

    // ADMIN: approve deposit (credits wallet)
    if (isset($_POST['action']) && $_POST['action'] === 'admin_approve_deposit') {
        require_admin();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin'); exit; }
        $id = (int)($_POST['deposit_id'] ?? 0);
        if (!$id) { $_SESSION['flash']='Invalid id'; header('Location:?view=admin'); exit; }
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = ? FOR UPDATE");
            $s->execute([$id]); $req = $s->fetch();
            if (!$req || $req['status'] !== 'pending') throw new Exception('Not pending');
            $uid = $req['user_id']; $currency = $req['currency']; $amount = (float)$req['amount'];
            ensure_wallet($pdo, $uid, $currency);
            $w = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $w->execute([$uid,$currency]); $wr = $w->fetch();
            if (!$wr) throw new Exception('Wallet missing');
            $new = (float)$wr['balance'] + $amount;
            $pdo->prepare("UPDATE wallets SET balance = ?, update_at = NOW() WHERE id = ?")->execute([$new, $wr['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'deposit', ?, ?)")->execute([$uid,$currency,$amount,json_encode(['deposit_request_id'=>$id,'approved_by'=>$_SESSION['user_id']])]);
            $pdo->prepare("UPDATE deposit_requests SET status='approved', approved_by=?, approved_at=NOW() WHERE id = ?")->execute([$_SESSION['user_id'],$id]);
            $pdo->commit();
            $_SESSION['flash']='Deposit approved and wallet credited';
            header('Location:?view=admin'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Error: '.$e->getMessage();
            header('Location:?view=admin'); exit;
        }
    }

    // ADMIN: process withdraw (deducts wallet and marks processed)
    if (isset($_POST['action']) && $_POST['action'] === 'admin_process_withdraw') {
        require_admin();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin'); exit; }
        $id = (int)($_POST['withdraw_id'] ?? 0);
        if (!$id) { $_SESSION['flash']='Invalid id'; header('Location:?view=admin'); exit; }
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("SELECT * FROM withdraw_requests WHERE id = ? FOR UPDATE");
            $s->execute([$id]); $req = $s->fetch();
            if (!$req || $req['status'] !== 'pending') throw new Exception('Invalid request');
            $uid = $req['user_id']; $currency = $req['currency']; $amount = (float)$req['amount'];
            $w = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $w->execute([$uid,$currency]); $wr = $w->fetch();
            if (!$wr || ((float)$wr['balance'] - 0) < $amount) {
                $pdo->prepare("UPDATE withdraw_requests SET status='rejected', processed_by=?, processed_at=NOW() WHERE id = ?")->execute([$_SESSION['user_id'],$id]);
                $pdo->commit();
                $_SESSION['flash']='Withdraw rejected due to insufficient funds';
                header('Location:?view=admin'); exit;
            }
            // Deduct balance permanently
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, update_at = NOW() WHERE id = ?")->execute([$amount,$wr['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'withdraw', ?, ?)")->execute([$uid,$currency,-$amount,json_encode(['withdraw_request'=>$id])]);
            $pdo->prepare("UPDATE withdraw_requests SET status='processed', processed_by=?, processed_at=NOW() WHERE id = ?")->execute([$_SESSION['user_id'],$id]);
            $pdo->commit();
            $_SESSION['flash']='Withdraw processed';
            header('Location:?view=admin'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Error: '.$e->getMessage();
            header('Location:?view=admin'); exit;
        }
    }

} // end POST handlers

/* ========== Data for rendering ========== */
$logged_in = !empty($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

$markets = $pdo->query("SELECT * FROM markets WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

// fetch wallets and compute available
$user_wallets = [];
$wallet_map = [];
if ($logged_in) {
    $wq = $pdo->prepare("SELECT id, currency, balance, hold_amount FROM wallets WHERE user_id = ?");
    $wq->execute([$uid]); $user_wallets = $wq->fetchAll();
    foreach ($user_wallets as $w) {
        $w['available'] = number_format((float)$w['balance'] - (float)$w['hold_amount'], 8, '.', '');
        $wallet_map[$w['currency']] = $w;
    }
    if (!isset($wallet_map['NGN'])) {
        $wallet_map['NGN'] = ['id'=>null,'currency'=>'NGN','balance'=>0.0,'hold_amount'=>0.0,'available'=>'0.00000000'];
    }
}

$recent_tx = [];
if ($logged_in) {
    $tq = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 12");
    $tq->execute([$uid]); $recent_tx = $tq->fetchAll();
}

/* ========== RENDER HTML ========== */
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Trade Demo — trade_db</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--accent:#2563eb;--muted:#6b7280}
*{box-sizing:border-box}
body{font-family:Inter, system-ui, Arial, sans-serif;background:var(--bg);margin:0;color:#0f172a}
.topnav{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;background:#0b1220;color:#fff}
.topnav .brand{font-weight:700}
.topnav nav a{color:#fff;margin-left:12px;text-decoration:none;font-size:14px}
.container{max-width:1100px;margin:20px auto;padding:12px}
.grid{display:grid;grid-template-columns:1fr 360px;gap:18px}
.card{background:var(--card);border-radius:10px;padding:16px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
h2,h3{margin:0 0 12px 0}
.table{width:100%;border-collapse:collapse}
.table td,.table th{padding:8px;border-bottom:1px solid #eef2f7;text-align:left;font-size:14px}
.btn{display:inline-block;padding:8px 14px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}
.input,select,textarea{padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%;margin-top:6px;margin-bottom:10px}
.muted{color:var(--muted);font-size:13px}
.small{font-size:12px;color:#64748b}
.tx-list{max-height:220px;overflow:auto;padding-right:6px}
.footer{max-width:1100px;margin:20px auto;text-align:center;color:var(--muted);font-size:13px;padding:10px}
@media (max-width:900px){.grid{grid-template-columns:1fr}}
.notice{background:#eef2ff;border-left:4px solid #b6d4ff;padding:10px;border-radius:6px;margin-bottom:12px}
.error{background:#fff0f0;border-left:4px solid #ffb4b4;padding:10px;border-radius:6px;margin-bottom:12px}
.success{background:#eefbe6;border-left:4px solid #b7f0c5;padding:10px;border-radius:6px;margin-bottom:12px}
.form-inline{display:flex;gap:8px}
.form-inline .input{flex:1}

/* exchange panel styles */
.main-grid{display:grid;grid-template-columns:320px 1fr;gap:18px}
.market-list .market-item{padding:8px;border-radius:8px;border:1px solid #eef2f7;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;cursor:pointer}
.market-item.active{background:#f0f9ff;border-color:#cfeeff}
.top-stats{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.top-stats .stat{padding:8px;background:#fbfdff;border-radius:8px;min-width:120px;text-align:center}
.chart-wrap{margin-top:12px}
.controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:12px}
.controls input, .controls select{width:130px;padding:8px}
.bottom-grid{display:grid;grid-template-columns:1fr 300px;gap:12px;margin-top:12px}
.orderbook .book-side{height:220px;overflow:auto}
.signal-box{padding:10px;border-radius:8px;background:#f9fbff;border:1px solid #eef6ff}
.badge{padding:6px 8px;border-radius:999px;border:1px solid #e6edf3;background:#fff}
.ghost{background:#fff}
.table .right{text-align:right}
.price-snippet{min-width:120px;margin-left:8px}
</style>
</head>
<body>
  <header class="topnav">
    <div class="brand">TradeDemo</div>
    <nav>
      <a href="?view=home">Dashboard</a>
      <a href="?view=deposit">Deposit</a>
      <a href="?view=withdraw">Withdraw</a>
      <a href="?view=transfer">Transfer</a>
      <a href="?view=swap">Swap</a>
      <a href="?view=exchange">Exchange</a>
      <?php if(($role ?? '') === 'admin'): ?><a href="?view=admin">Admin</a><?php endif; ?>
      <?php if($logged_in): ?>
        <form method="post" style="display:inline;margin:0">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="logout">
          <button class="btn" style="background:#0b1220;padding:8px 12px">Logout</button>
        </form>
      <?php else: ?>
        <a href="?view=login" style="margin-left:8px;color:#fff">Login</a>
        <a href="?view=register" style="margin-left:8px;color:#fff">Register</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">
    <?php if(!empty($flash)): ?>
      <div class="card success"><?=htmlspecialchars($flash)?></div>
    <?php endif; ?>

    <?php if($view === 'register'): ?>
      <div class="card">
        <h2>Register</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="register">
          <label class="small">Username <input class="input" name="username" required></label>
          <label class="small">Email <input class="input" name="email" type="email" required></label>
          <label class="small">Password <input class="input" name="password" type="password" required></label>
          <button class="btn">Create account</button>
        </form>
      </div>

    <?php elseif($view === 'login'): ?>
      <div class="card">
        <h2>Login</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="login">
          <label class="small">Email <input class="input" name="email" type="email" required></label>
          <label class="small">Password <input class="input" name="password" type="password" required></label>
          <button class="btn">Login</button>
        </form>
      </div>

    <?php elseif(in_array($view, ['exchange','home'])): ?>
      <div class="main-grid">
        <!-- left: markets -->
        <aside class="card">
          <div class="row"><div><strong>Markets</strong></div><div class="small muted">Click a pair to open</div></div>
          <div class="market-list" id="marketList" style="margin-top:10px">
            <?php foreach($markets as $m): ?>
              <div class="market-item" data-symbol="<?=htmlspecialchars($m['symbol'])?>" data-market-id="<?=intval($m['id'])?>">
                <div><?=htmlspecialchars($m['symbol'])?></div>
                <div class="small muted"><?=htmlspecialchars($m['base_currency'])?>/<?=htmlspecialchars($m['quote_currency'])?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:14px" class="small muted">Quick filters</div>
          <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
            <button class="ghost badge" id="favAll">All</button>
            <button class="ghost badge" data-filter="top">Top</button>
            <button class="ghost badge" data-filter="gainer">Gainers</button>
            <button class="ghost badge" data-filter="loser">Losers</button>
          </div>
        </aside>

        <!-- right: trading panel -->
        <main>
          <div class="card">
            <div class="top-stats">
              <div class="stat"><div class="small">Active account</div><div id="acctSum" style="font-weight:900">—</div></div>
              <div class="stat"><div class="small">Available balance (NGN)</div>
                <div style="font-weight:900"><?= $logged_in ? money_fmt($wallet_map['NGN']['available'] ?? 0) : '0.00' ?></div>
              </div>
            </div>

            <div class="chart-wrap">
              <canvas id="chart" width="900" height="240"></canvas>
            </div>

            <div class="controls">
              <form method="post" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
                <input type="hidden" name="action" value="place_order">
                <label class="small muted">Market
                  <select class="input" name="market_id"><?php foreach($markets as $m): ?><option value="<?=intval($m['id'])?>"><?=htmlspecialchars($m['symbol'])?></option><?php endforeach; ?></select>
                </label>
                <label class="small muted">Side
                  <select class="input" name="side"><option value="buy">Buy</option><option value="sell">Sell</option></select>
                </label>
                <label class="small muted">Type
                  <select class="input" name="type"><option value="limit">Limit</option></select>
                </label>
                <label class="small muted">Price <input class="input" name="price" type="number" step="0.0001" required></label>
                <label class="small muted">Size <input class="input" name="size" type="number" step="0.00000001" required></label>
                <button class="btn">Place Order</button>
              </form>
            </div>
          </div>

          <div class="bottom-grid">
            <div class="card">
              <div style="display:flex;justify-content:space-between;align-items:center">
                <strong>Order Book</strong><div class="small muted">Live (simulated)</div>
              </div>
              <div class="orderbook" style="margin-top:8px;display:flex;gap:8px">
                <div class="book-side card" style="padding:8px;width:48%;min-height:160px">
                  <div class="small muted">Asks</div>
                  <table class="table" id="asksTable"><thead><tr><th>Price</th><th class="right">Size</th></tr></thead><tbody></tbody></table>
                </div>
                <div class="book-side card" style="padding:8px;width:48%;min-height:160px">
                  <div class="small muted">Bids</div>
                  <table class="table" id="bidsTable"><thead><tr><th>Price</th><th class="right">Size</th></tr></thead><tbody></tbody></table>
                </div>
              </div>

              <div style="margin-top:12px;display:flex;gap:8px">
                <div style="flex:1">
                  <strong>Open Orders</strong>
                  <table class="table" id="openOrders"><thead><tr><th>Time</th><th>Side</th><th>Price</th><th>Amount</th></tr></thead><tbody></tbody></table>
                </div>
                <div style="flex:1">
                  <strong>Trade History</strong>
                  <table class="table" id="tradeHistory"><thead><tr><th>Time</th><th>Market</th><th>Price</th><th>Amount</th></tr></thead><tbody>
                  <?php
                    $tr = $pdo->query("SELECT t.*, m.symbol FROM trades t JOIN markets m ON t.market_id = m.id ORDER BY t.created_at DESC LIMIT 12")->fetchAll();
                    foreach($tr as $trade):
                  ?>
                    <tr><td class="small"><?=htmlspecialchars($trade['created_at'])?></td><td><?=htmlspecialchars($trade['symbol'])?></td><td><?=money_fmt($trade['price'])?></td><td><?=money_fmt($trade['size'])?></td></tr>
                  <?php endforeach; ?>
                  </tbody></table>
                </div>
              </div>
            </div>

            <aside class="card">
              <div style="display:flex;flex-direction:column;gap:10px">
                <div><strong>Signal</strong><div class="signal-box" id="signalBox">No active signals — demo only.</div></div>

                <div>
                  <strong>Your Wallets</strong>
                  <?php if(!$logged_in): ?>
                    <p class="muted">Login to view your wallets</p>
                  <?php else: ?>
                    <table class="table">
                      <tr><th>Currency</th><th>Balance</th><th>On Hold</th><th>Available</th></tr>
                      <?php foreach($user_wallets as $w): ?>
                        <tr>
                          <td><?=htmlspecialchars($w['currency'])?></td>
                          <td><?=money_fmt($w['balance'])?></td>
                          <td><?=money_fmt($w['hold_amount'])?></td>
                          <td><?=number_format(((float)$w['balance'] - (float)$w['hold_amount']), 2, '.', ',')?></td>
                        </tr>
                      <?php endforeach; ?>
                    </table>
                  <?php endif; ?>
                </div>

                <div>
                  <strong>Team Assets</strong>
                  <div class="small muted">Team holdings (demo)</div>
                  <div style="display:flex;justify-content:space-between;margin-top:6px"><div>BTC</div><div>12.32</div></div>
                  <div style="display:flex;justify-content:space-between"><div>ETH</div><div>45.12</div></div>
                  <div style="display:flex;justify-content:space-between"><div>USDT</div><div>2,500,000</div></div>
                </div>
              </div>
            </aside>
          </div>
        </main>
      </div>

    <?php elseif($view === 'deposit'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>Deposit (NGN)</h2>
        <?php $step = $_GET['step'] ?? 'enter'; ?>
        <?php if($step === 'enter'): ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
            <input type="hidden" name="action" value="deposit_start">
            <label class="small muted">Amount (NGN)</label>
            <input class="input" name="amount" type="number" step="0.01" min="100" required>
            <button class="btn">Continue</button>
          </form>
        <?php elseif($step === 'confirm' && !empty($_SESSION['deposit_amount'])): ?>
          <div class="notice">
            <p><strong>Send to:</strong></p>
            <p>Account name: <strong>Ogundele Olayinka Mary</strong><br>
            Bank: <strong>Palmpay</strong><br>
            Account number: <strong>7050672951</strong></p>
            <p class="muted">Amount: ₦<?=number_format($_SESSION['deposit_amount'],2)?></p>
          </div>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
            <input type="hidden" name="action" value="deposit_confirm">
            <button class="btn">I have paid — Notify admin</button>
          </form>
        <?php else: ?>
          <p class="muted">Start a deposit.</p>
        <?php endif; ?>
      </div>

    <?php elseif($view === 'withdraw'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>Withdraw</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="withdraw_request">
          <label class="small">Currency <input class="input" name="currency" value="NGN" required></label>
          <label class="small">Amount <input class="input" name="amount" type="number" step="0.01" required></label>
          <label class="small">Account number <input class="input" name="account_number" required></label>
          <label class="small">Bank name <input class="input" name="bank_name" required></label>
          <label class="small">Account name <input class="input" name="account_name" required></label>
          <button class="btn">Request Withdraw</button>
        </form>
      </div>

    <?php elseif($view === 'admin'): ?>
      <?php require_admin(); ?>
      <div class="grid">
        <div>
          <div class="card">
            <h2>Admin — Pending Deposit Requests</h2>
            <?php $reqs = $pdo->query("SELECT dr.*, u.username FROM deposit_requests dr JOIN users u ON dr.user_id = u.id WHERE dr.status='pending' ORDER BY dr.created_at ASC")->fetchAll(); ?>
            <?php if(!$reqs): ?><p class="muted">No pending deposits</p><?php endif; ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
              <input type="hidden" name="action" value="admin_approve_deposit">
              <?php foreach($reqs as $r): ?>
                <div style="padding:8px;border-bottom:1px dashed #eee">
                  <label><input type="radio" name="deposit_id" value="<?=intval($r['id'])?>"> Request #<?=intval($r['id'])?> — <?=htmlspecialchars($r['username'])?> <?=htmlspecialchars($r['currency'])?> <?=money_fmt($r['amount'])?> (<?=htmlspecialchars($r['created_at'])?>)</label>
                </div>
              <?php endforeach; ?>
              <?php if($reqs): ?><button class="btn">Approve selected</button><?php endif; ?>
            </form>
          </div>

          <div class="card" style="margin-top:16px">
            <h3>Pending Withdraw Requests</h3>
            <?php $wreqs = $pdo->query("SELECT w.*, u.username FROM withdraw_requests w JOIN users u ON w.user_id = u.id WHERE w.status='pending' ORDER BY w.created_at ASC")->fetchAll(); ?>
            <?php if(!$wreqs) echo "<p class='muted'>No pending withdraws</p>"; ?>
            <table class="table">
              <tr><th>ID</th><th>User</th><th>Currency</th><th>Amount</th><th>Action</th></tr>
              <?php foreach($wreqs as $wr): ?>
                <tr>
                  <td><?=intval($wr['id'])?></td>
                  <td><?=htmlspecialchars($wr['username'])?></td>
                  <td><?=htmlspecialchars($wr['currency'])?></td>
                  <td><?=money_fmt($wr['amount'])?></td>
                  <td>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
                      <input type="hidden" name="action" value="admin_process_withdraw">
                      <input type="hidden" name="withdraw_id" value="<?=intval($wr['id'])?>">
                      <button class="btn" type="submit">Process</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        <aside>
          <div class="card">
            <h3>Admin Info</h3>
            <p class="small">Admin account: <strong>admin@example.com</strong> / <strong>Admin123!</strong></p>
            <p class="small">Deposits must be approved by admin. Withdrawals processed by admin.</p>
          </div>
        </aside>
      </div>

    <?php elseif($view === 'transfer'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>Transfer</h2>
        <form method="post" action="?view=transfer">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="transfer">
          <label class="small">Recipient username <input class="input" name="to_user" required></label>
          <label class="small">Currency <input class="input" name="currency" value="NGN" required></label>
          <label class="small">Amount <input class="input" name="amount" type="number" step="0.01" required></label>
          <button class="btn">Send</button>
        </form>
      </div>

    <?php else: ?>
      <div class="card"><h2>Page not found</h2><p class="muted">Unknown view</p></div>
    <?php endif; ?>

    <div class="footer">Demo trading platform — Database: <strong><?=htmlspecialchars($DB_NAME)?></strong> — For learning/testing only.</div>
  </main>

  <script>
    // client-side market simulator (random walk + spikes)
    const initialPairs = <?php echo json_encode(array_column($markets,'symbol')); ?>;
    const markets = {};
    initialPairs.forEach((p,i)=>{
      const base = (p.startsWith('BTC')? 3500000 : (p.startsWith('ETH')? 220000 : 50000));
      const start = Math.round(base * (1 + (Math.random()-0.5)*0.2));
      markets[p] = { last: start, changePct: ((Math.random()-0.5)*10).toFixed(2), vol24: Math.round(Math.random()*50000+1000), prices: Array.from({length:120}, (_,i)=> start) };
    });

    let activePair = initialPairs[0];
    const marketListEl = document.getElementById('marketList');
    if (marketListEl) {
      marketListEl.querySelectorAll('.market-item').forEach(el=>{
        el.addEventListener('click', ()=>{
          activePair = el.dataset.symbol;
          document.querySelectorAll('.market-item').forEach(d=>d.classList.remove('active'));
          el.classList.add('active');
          draw();
        });
      });
    }

    const canvas = document.getElementById('chart');
    const ctx = canvas ? canvas.getContext('2d') : null;
    function draw(){
      if(!canvas || !ctx) return;
      canvas.width = canvas.clientWidth;
      canvas.height = canvas.clientHeight;
      ctx.clearRect(0,0,canvas.width,canvas.height);
      const data = markets[activePair].prices;
      if(!data || data.length < 2) return;
      const min = Math.min(...data), max = Math.max(...data);
      ctx.beginPath();
      data.forEach((v,i)=>{
        const x = i*(canvas.width/(data.length-1));
        const y = canvas.height - ((v - min)/(max - min || 1))*canvas.height;
        if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
      });
      ctx.strokeStyle = '#3ea0ff'; ctx.lineWidth = 2; ctx.stroke();
      ctx.lineTo(canvas.width, canvas.height); ctx.lineTo(0, canvas.height); ctx.closePath();
      const g = ctx.createLinearGradient(0,0,0,canvas.height); g.addColorStop(0,'rgba(62,160,255,0.12)'); g.addColorStop(1,'rgba(62,160,255,0)');
      ctx.fillStyle = g; ctx.fill();
      ctx.fillStyle = '#0b1220'; ctx.font = '13px Inter, Arial';
      ctx.fillText('₦ ' + Number(markets[activePair].last).toLocaleString(), 8, 18);
    }

    function simulateTickDemo(){
      initialPairs.forEach(sym=>{
        const m = markets[sym];
        const vol = sym.startsWith('BTC')? 0.006 : (sym.startsWith('ETH')? 0.015 : 0.02);
        const drift = (Math.random()-0.5) * vol * m.last;
        const mean = m.prices.reduce((a,b)=>a+b,0)/m.prices.length;
        const revert = (mean - m.last) * 0.001;
        let next = Math.max(1, Math.round(m.last + drift + revert));
        if (Math.random() < 0.02) {
          const spike = Math.round(m.last * (0.02 * (Math.random()>0.5?1:-1)));
          next = Math.max(1, next + spike);
        }
        m.last = next;
        m.prices.push(next); if (m.prices.length > 300) m.prices.shift();
        const prev24 = m.prices[0] || next;
        m.changePct = (((m.last - prev24) / (prev24 || 1)) * 100).toFixed(2);
        m.vol24 = Math.round(m.vol24 * (1 + (Math.random()-0.5)*0.1));
      });
      draw();
      // update market list snippets
      document.querySelectorAll('.market-item').forEach(el=>{
        const symbol = el.dataset.symbol;
        const p = markets[symbol];
        if(!p) return;
        // remove previous snippet and append new
        el.querySelectorAll('.price-snippet').forEach(n=>n.remove());
        const right = document.createElement('div'); right.className = 'price-snippet';
        right.style.textAlign='right'; right.innerHTML = `<div style="font-weight:700">₦ ${Number(p.last).toLocaleString()}</div><div class="small muted">${p.changePct>=0?'+':''}${p.changePct}%</div>`;
        el.appendChild(right);
      });
    }

    draw();
    setInterval(simulateTickDemo, 1500);
    window.addEventListener('resize', draw);
  </script>
</body>
</html>









