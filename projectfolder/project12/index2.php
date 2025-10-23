<?php
/**
 * index.php — Single-file investment demo (improved)
 *
 * Features:
 * - Register / Login (welcome bonus ₦1,500)
 * - Deposit requests (admin approval credits wallet)
 * - Withdraw requests (fast deduction at request time; admin marks processed)
 * - 10 doubling investment plans (30 days each) + invest action (requires at least one admin-approved deposit)
 * - Daily payout processing on page load (demo)
 * - Coupon redemption
 * - Support messages
 * - Product page -> user_products (user can "add plan to product")
 * - Profile with tabs: recharge, withdraw, earning, my product, withdraw record, team, bank account, change password, logout
 * - Admin panel: approve deposits, process withdraws, create coupons
 *
 * WARNING: Demo only. Do not use in production without HTTPS, SMTP, KYC/AML, 2FA, logging, tests, and a security review.
 */

/* ========== CONFIG ========== */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'investtment_db';
date_default_timezone_set('Africa/Lagos');

define('WELCOME_BONUS_NGN', 1500.00);
define('MIN_DEPOSIT_NGN', 2000.00);
define('CSRF_TOKEN_KEY', 'csrf_token');

define('ADMIN_EMAIL', 'admin@example.com'); // change to your admin address
define('MAIL_FROM', 'no-reply@example.com');
define('MAIL_FROM_NAME', 'BestTrading');

define('DEBUG', true);

/* ========== SECURITY HEADERS ========== */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');

/* ========== SESSIONS ========== */
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false, // set true in production (HTTPS)
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) session_start();

/* ========== MAIL (simple) ========== */
function send_mail($to, $subject, $htmlBody) {
    $from = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: " . mb_encode_mimeheader($fromName) . " <{$from}>\r\n";
    if (defined('DEBUG') && DEBUG) error_log("MAIL to={$to} subject={$subject}");
    @mail($to, $subject, $htmlBody, $headers);
    return true;
}

/* ========== CONNECT & DB CREATE ========== */
try {
    $dsnNoDb = "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4";
    $pdo0 = new PDO($dsnNoDb, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    die("DB server connection failed: " . htmlspecialchars($e->getMessage()));
}
try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB connect failed: " . htmlspecialchars($e->getMessage()));
}

/* ========== SCHEMA (create/alter tables) ========== */
$schema = [
"CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  bank_name VARCHAR(150) NULL,
  bank_account VARCHAR(80) NULL,
  account_name VARCHAR(150) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
  balance DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  hold_amount DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_currency (user_id, currency),
  CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
  type VARCHAR(100) NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS deposit_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
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
  currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
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
"CREATE TABLE IF NOT EXISTS invest_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  principal_amount DECIMAL(28,8) NOT NULL,
  duration_days INT NOT NULL DEFAULT 30,
  daily_payout DECIMAL(28,8) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS investments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  principal DECIMAL(28,8) NOT NULL,
  daily_payout DECIMAL(28,8) NOT NULL,
  remaining_days INT NOT NULL,
  start_at DATETIME NOT NULL,
  mature_at DATETIME NOT NULL,
  last_payout_at DATETIME NOT NULL,
  status ENUM('active','matured','cancelled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_invest_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_invest_product FOREIGN KEY (product_id) REFERENCES invest_products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL UNIQUE,
  amount DECIMAL(28,8) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS coupon_redemptions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  coupon_id INT NOT NULL,
  user_id INT NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  CONSTRAINT fk_coupon_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS support_messages (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(150) NULL,
  email VARCHAR(255) NULL,
  message TEXT NOT NULL,
  status ENUM('open','closed') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS user_products (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_product_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_product_product FOREIGN KEY (product_id) REFERENCES invest_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($schema as $sql) {
    $pdo->exec($sql);
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
    if (empty($_SESSION[CSRF_TOKEN_KEY])) $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(24));
    return $_SESSION[CSRF_TOKEN_KEY];
}
function check_csrf($t) {
    return isset($_SESSION[CSRF_TOKEN_KEY]) && hash_equals($_SESSION[CSRF_TOKEN_KEY], (string)$t);
}
function require_login() {
    if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Please login'; header('Location:?view=login'); exit; }
}
function require_admin() {
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { $_SESSION['flash']='Admin required'; header('Location:?view=login'); exit; }
}
function ensure_wallet(PDO $pdo, $uid, $currency = 'NGN') {
    $ins = $pdo->prepare("INSERT IGNORE INTO wallets (user_id, currency, balance, hold_amount) VALUES (?, ?, 0, 0)");
    $ins->execute([$uid, $currency]);
}
function money($n) { return number_format((float)$n, 2, '.', ','); }
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

/* ========== PROCESS INVESTMENTS (daily payouts on page load - demo) ========== */
function process_investments(PDO $pdo) {
    $list = $pdo->query("SELECT * FROM investments WHERE status='active'")->fetchAll();
    foreach ($list as $inv) {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare("SELECT * FROM investments WHERE id = ? FOR UPDATE");
            $lock->execute([$inv['id']]); $row = $lock->fetch();
            if (!$row || $row['status'] !== 'active') { $pdo->rollBack(); continue; }

            $now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));
            $last = new DateTimeImmutable($row['last_payout_at'], new DateTimeZone(date_default_timezone_get()));
            $mature = new DateTimeImmutable($row['mature_at'], new DateTimeZone(date_default_timezone_get()));
            $remaining = (int)$row['remaining_days'];

            if ($remaining <= 0) {
                if ($now >= $mature) {
                    $uid = (int)$row['user_id'];
                    ensure_wallet($pdo, $uid, 'NGN');
                    $w = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE");
                    $w->execute([$uid]); $wr = $w->fetch();
                    if ($wr) {
                        $principal = (float)$row['principal'];
                        $release = min((float)$wr['hold_amount'], $principal);
                        if ($release > 0) {
                            $pdo->prepare("UPDATE wallets SET hold_amount=hold_amount-?, balance=balance+? WHERE id=?")->execute([$release,$release,$wr['id']]);
                            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','investment_principal_return',?,?)")
                                ->execute([$uid,$release,json_encode(['investment_id'=>$row['id']])]);
                        }
                    }
                    $pdo->prepare("UPDATE investments SET status='matured' WHERE id=?")->execute([$row['id']]);
                }
                $pdo->commit();
                continue;
            }

            $diff = max(0, $now->getTimestamp() - $last->getTimestamp());
            $days = floor($diff / 86400);
            if ($days <= 0) {
                if ($now >= $mature) $days = $remaining; else { $pdo->commit(); continue; }
            }
            $daysToPay = min($days, $remaining);
            if ($daysToPay > 0) {
                $uid = (int)$row['user_id'];
                $daily = (float)$row['daily_payout'];
                $total = round($daily * $daysToPay,2);

                ensure_wallet($pdo,$uid,'NGN');
                $w = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE");
                $w->execute([$uid]); $wr = $w->fetch();
                if ($wr) {
                    $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$total,$wr['id']]);
                    $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','investment_payout',?,?)")
                        ->execute([$uid,$total,json_encode(['investment_id'=>$row['id'],'days'=>$daysToPay])]);
                }

                $newRemaining = max(0, $remaining - $daysToPay);
                $newLast = $last->modify("+{$daysToPay} days")->format('Y-m-d H:i:s');
                $pdo->prepare("UPDATE investments SET remaining_days=?, last_payout_at=? WHERE id=?")->execute([$newRemaining,$newLast,$row['id']]);

                if ($newRemaining <= 0) {
                    $principal = (float)$row['principal'];
                    $wr2 = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE");
                    $wr2->execute([$uid]); $wrow = $wr2->fetch();
                    if ($wrow) {
                        $release = min((float)$wrow['hold_amount'], $principal);
                        if ($release > 0) {
                            $pdo->prepare("UPDATE wallets SET hold_amount=hold_amount-?, balance=balance+? WHERE id=?")->execute([$release,$release,$wrow['id']]);
                            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','investment_principal_return',?,?)")
                                ->execute([$uid,$release,json_encode(['investment_id'=>$row['id']])]);
                        }
                    }
                    $pdo->prepare("UPDATE investments SET status='matured' WHERE id=?")->execute([$row['id']]);
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if (DEBUG) error_log("process_investments error: ".$e->getMessage());
            continue;
        }
    }
}
try { process_investments($pdo); } catch (Exception $e) { if (DEBUG) error_log($e->getMessage()); }

/* ========== ROUTES & HANDLERS ========== */
$view = $_GET['view'] ?? 'home';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$token = csrf_token();

/* POST handlers */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // REGISTER
    if (!empty($_POST['action']) && $_POST['action'] === 'register') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=register'); exit; }
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$email || !$password) { $_SESSION['flash']='Complete all fields'; header('Location:?view=register'); exit; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $_SESSION['flash']='Invalid email'; header('Location:?view=register'); exit; }
        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (username,email,password) VALUES (?, ?, ?)");
            $ins->execute([$username,$email,$hash]);
            $uid = $pdo->lastInsertId();
            ensure_wallet($pdo,$uid,'NGN');
            if (WELCOME_BONUS_NGN > 0) {
                $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'NGN'")->execute([WELCOME_BONUS_NGN,$uid]);
                $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN', 'welcome_bonus', ?, ?)")->execute([$uid, WELCOME_BONUS_NGN, json_encode(['note'=>'welcome bonus'])]);
            }
            $pdo->commit();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $uid;
            $_SESSION['role'] = 'user';
            $_SESSION['flash'] = "Welcome! ₦".number_format(WELCOME_BONUS_NGN,2)." credited to your wallet.";
            header('Location:?view=home'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = 'Registration error: '.$e->getMessage();
            header('Location:?view=register'); exit;
        }
    }

    // LOGIN
    if (!empty($_POST['action']) && $_POST['action'] === 'login') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=login'); exit; }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) { $_SESSION['flash']='Missing credentials'; header('Location:?view=login'); exit; }
        $stmt = $pdo->prepare("SELECT id,password,role,username FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]); $u = $stmt->fetch();
        if ($u && password_verify($password, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['role'] = $u['role'];
            $_SESSION['flash'] = "Welcome back, ".esc($u['username']);
            header('Location:?view=home'); exit;
        } else {
            $_SESSION['flash'] = 'Invalid credentials';
            header('Location:?view=login'); exit;
        }
    }

    // LOGOUT
    if (!empty($_POST['action']) && $_POST['action'] === 'logout') {
        session_unset(); session_destroy(); session_start();
        $_SESSION['flash'] = 'Logged out';
        header('Location:?view=login'); exit;
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

        // WITHDRAW REQUEST (deduct immediately)
    if (!empty($_POST['action']) && $_POST['action'] === 'withdraw_request') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=withdraw'); exit; }
        $amount = (float)($_POST['amount'] ?? 0);
        $acc_num = trim($_POST['account_number'] ?? '');
        $bank = trim($_POST['bank_name'] ?? '');
        $acc_name = trim($_POST['account_name'] ?? '');
        if ($amount <= 0 || !$acc_num || !$bank || !$acc_name) { $_SESSION['flash']='Complete all fields'; header('Location:?view=withdraw'); exit; }
        $uid = $_SESSION['user_id'];
        try {
            $pdo->beginTransaction();
            ensure_wallet($pdo,$uid,'NGN');
            $wq = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $wq->execute([$uid]); $w = $wq->fetch();
            if (!$w) { $pdo->rollBack(); $_SESSION['flash']='Wallet missing'; header('Location:?view=withdraw'); exit; }
            $available = (float)$w['balance'] - (float)$w['hold_amount'];
            if ($available + 1e-12 < $amount) {
                $pdo->rollBack();
                $_SESSION['flash'] = "Insufficient available balance (Available: ₦".number_format($available,2).")";
                header('Location:?view=withdraw'); exit;
            }
            // deduct
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, updated_at = NOW() WHERE id = ?")->execute([$amount,$w['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN', 'withdraw_request_deducted', ?, ?)")->execute([$uid, -$amount, json_encode(['account'=>$acc_num,'bank'=>$bank])]);
            // create withdraw request
            $ins = $pdo->prepare("INSERT INTO withdraw_requests (user_id,currency,amount,account_number,bank_name,account_name,status,created_at) VALUES (?, 'NGN', ?, ?, ?, ?, 'pending', NOW())");
            $ins->execute([$uid,$amount,$acc_num,$bank,$acc_name]);
            $reqId = $pdo->lastInsertId();
            $pdo->commit();
            // notify admin
            $userQ = $pdo->prepare("SELECT username,email FROM users WHERE id = ? LIMIT 1");
            $userQ->execute([$uid]); $userRow = $userQ->fetch();
            send_mail(ADMIN_EMAIL, "Withdraw request #{$reqId}", "<p>User {$userRow['username']} (ID {$uid}) requests withdraw ₦".number_format($amount,2)."</p>");
            $_SESSION['flash'] = 'Withdrawal recorded and deducted. Admin will process payout.';
            header('Location:?view=withdraw'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = 'Withdraw error: '.$e->getMessage();
            header('Location:?view=withdraw'); exit;
        }
    }

    // INVEST (reserve principal & create investment) - requires approved deposit
    if (!empty($_POST['action']) && $_POST['action'] === 'invest') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=home'); exit; }
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id <= 0) { $_SESSION['flash']='Invalid product'; header('Location:?view=home'); exit; }
        $uid = $_SESSION['user_id'];
        try {
            $pdo->beginTransaction();
            $pstmt = $pdo->prepare("SELECT * FROM invest_products WHERE id = ? AND is_active = 1 LIMIT 1 FOR UPDATE");
            $pstmt->execute([$product_id]); $prod = $pstmt->fetch();
            if (!$prod) { $pdo->rollBack(); $_SESSION['flash']='Product not found'; header('Location:?view=home'); exit; }
            $principal = (float)$prod['principal_amount'];
            $duration = (int)$prod['duration_days'];
            $daily = (float)$prod['daily_payout'];
            // require admin-approved deposit
            $app = $pdo->prepare("SELECT COUNT(*) FROM deposit_requests WHERE user_id = ? AND status = 'approved'");
            $app->execute([$uid]); $approvedCount = (int)$app->fetchColumn();
            if ($approvedCount <= 0 && ($_SESSION['role'] ?? '') !== 'admin') {
                $pdo->rollBack();
                $_SESSION['flash'] = 'You must have at least one admin-approved deposit before investing.';
                header('Location:?view=deposit'); exit;
            }
            ensure_wallet($pdo,$uid,'NGN');
            $wq = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $wq->execute([$uid]); $w = $wq->fetch();
            if (!$w) { $pdo->rollBack(); $_SESSION['flash']='Wallet missing'; header('Location:?view=home'); exit; }
            $available = (float)$w['balance'] - (float)$w['hold_amount'];
            if ($available + 1e-12 < $principal) {
                $pdo->rollBack();
                $_SESSION['flash'] = "Insufficient NGN balance. Required: ₦".number_format($principal,2);
                header('Location:?view=deposit'); exit;
            }
            // reserve
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, hold_amount = hold_amount + ?, updated_at = NOW() WHERE id = ?")->execute([$principal,$principal,$w['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN', 'invest_reserve', ?, ?)")->execute([$uid, -$principal, json_encode(['product_id'=>$product_id,'product_name'=>$prod['name']])]);
            $start = date('Y-m-d H:i:s'); $mature_at = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
            $ins = $pdo->prepare("INSERT INTO investments (user_id, product_id, principal, daily_payout, remaining_days, start_at, mature_at, last_payout_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$uid, $product_id, $principal, $daily, $duration, $start, $mature_at, $start]);
            $pdo->commit();
            send_mail(ADMIN_EMAIL, "New investment by user {$uid}", "User {$uid} started {$prod['name']} for ₦".number_format($principal,2));
            $_SESSION['flash'] = "Investment started: ₦".number_format($principal,2)." locked for {$duration} days.";
            header('Location:?view=home'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = 'Investment error: '.$e->getMessage();
            header('Location:?view=home'); exit;
        }
    }

    // ADD product to user_products (non-invest)
    if (!empty($_POST['action']) && $_POST['action'] === 'add_product') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=product'); exit; }
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id <= 0) { $_SESSION['flash']='Invalid product'; header('Location:?view=product'); exit; }
        try {
            $ins = $pdo->prepare("INSERT INTO user_products (user_id, product_id) VALUES (?, ?)");
            $ins->execute([$_SESSION['user_id'], $product_id]);
            $_SESSION['flash'] = 'Product saved to your products list.';
            header('Location:?view=product'); exit;
        } catch (Exception $e) {
            $_SESSION['flash'] = 'Add product error: '.$e->getMessage();
            header('Location:?view=product'); exit;
        }
    }

    // SUPPORT message
    if (!empty($_POST['action']) && $_POST['action'] === 'support_send') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!$message) { $_SESSION['flash'] = 'Please write a message'; header('Location:?view=support'); exit; }
        try {
            $uid = $_SESSION['user_id'] ?? null;
            $ins = $pdo->prepare("INSERT INTO support_messages (user_id, name, email, message) VALUES (?, ?, ?, ?)");
            $ins->execute([$uid, $name ?: null, $email ?: null, $message]);
            // notify admin
            send_mail(ADMIN_EMAIL, "Support message", "<p>From: ".esc($name)." (".esc($email).")<br>Message: ".nl2br(esc($message))."</p>");
            $_SESSION['flash'] = 'Support message sent. We will respond soon.';
            header('Location:?view=support'); exit;
        } catch (Exception $e) {
            $_SESSION['flash'] = 'Support error: '.$e->getMessage();
            header('Location:?view=support'); exit;
        }
    }

    // COUPON redeem
    if (!empty($_POST['action']) && $_POST['action'] === 'redeem_coupon') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=coupon'); exit; }
        $code = trim($_POST['code'] ?? '');
        if (!$code) { $_SESSION['flash']='Enter coupon code'; header('Location:?view=coupon'); exit; }
        try {
            $c = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1");
            $c->execute([$code]); $coupon = $c->fetch();
            if (!$coupon) { $_SESSION['flash']='Coupon not found or inactive'; header('Location:?view=coupon'); exit; }
            // check redemption
            $r = $pdo->prepare("SELECT COUNT(*) FROM coupon_redemptions WHERE coupon_id = ? AND user_id = ?");
            $r->execute([$coupon['id'], $_SESSION['user_id']]); $used = (int)$r->fetchColumn();
            if ($used > 0) { $_SESSION['flash']='Coupon already redeemed by you'; header('Location:?view=coupon'); exit; }
            // redeem: credit wallet immediately
            $pdo->beginTransaction();
            ensure_wallet($pdo,$_SESSION['user_id'],'NGN');
            $wq = $pdo->prepare("SELECT id FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $wq->execute([$_SESSION['user_id']]); $w = $wq->fetch();
            $amount = (float)$coupon['amount'];
            $pdo->prepare("UPDATE wallets SET balance = balance + ?, updated_at = NOW() WHERE id = ?")->execute([$amount,$w['id']]);
            $pdo->prepare("INSERT INTO coupon_redemptions (coupon_id,user_id,amount) VALUES (?, ?, ?)")->execute([$coupon['id'], $_SESSION['user_id'], $amount]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN', 'coupon_redeem', ?, ?)")->execute([$_SESSION['user_id'], $amount, json_encode(['coupon_id'=>$coupon['id'],'code'=>$coupon['code']])]);
            $pdo->commit();
            $_SESSION['flash'] = "Coupon redeemed: ₦".number_format($amount,2)." credited.";
            header('Location:?view=coupon'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = 'Coupon error: '.$e->getMessage();
            header('Location:?view=coupon'); exit;
        }
    }

    // PROFILE: update bank info
    if (!empty($_POST['action']) && $_POST['action'] === 'update_bank') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=profile'); exit; }
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_account = trim($_POST['bank_account'] ?? '');
        $account_name = trim($_POST['account_name'] ?? '');
        try {
            $pdo->prepare("UPDATE users SET bank_name = ?, bank_account = ?, account_name = ? WHERE id = ?")->execute([$bank_name ?: null, $bank_account ?: null, $account_name ?: null, $_SESSION['user_id']]);
            $_SESSION['flash'] = 'Bank details updated';
            header('Location:?view=profile&tab=bank'); exit;
        } catch (Exception $e) {
            $_SESSION['flash'] = 'Bank update error: '.$e->getMessage();
            header('Location:?view=profile&tab=bank'); exit;
        }
    }

    // PROFILE: change password
    if (!empty($_POST['action']) && $_POST['action'] === 'change_password') {
        require_login();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=profile&tab=security'); exit; }
        $current = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        if (!$current || !$new) { $_SESSION['flash']='Complete fields'; header('Location:?view=profile&tab=security'); exit; }
        try {
            $uQ = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $uQ->execute([$_SESSION['user_id']]); $u = $uQ->fetch();
            if (!$u || !password_verify($current, $u['password'])) { $_SESSION['flash']='Current password is incorrect'; header('Location:?view=profile&tab=security'); exit; }
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
            $_SESSION['flash'] = 'Password changed';
            header('Location:?view=profile&tab=security'); exit;
        } catch (Exception $e) {
            $_SESSION['flash'] = 'Password change error: '.$e->getMessage();
            header('Location:?view=profile&tab=security'); exit;
        }
    }

   // ADMIN: approve deposit (kept for admin if needed — deposits created by user are already approved)
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
    // ADMIN: process withdraw (mark processed)
    if (!empty($_POST['action']) && $_POST['action'] === 'admin_process_withdraw') {
        require_admin();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin'); exit; }
        $id = (int)($_POST['withdraw_id'] ?? 0);
        if (!$id) { $_SESSION['flash']='Select withdraw'; header('Location:?view=admin'); exit; }
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("SELECT * FROM withdraw_requests WHERE id = ? FOR UPDATE");
            $s->execute([$id]); $req = $s->fetch();
            if (!$req || $req['status'] !== 'pending') { $pdo->rollBack(); $_SESSION['flash']='Not pending'; header('Location:?view=admin'); exit; }
            $uid = $req['user_id']; $amount = (float)$req['amount'];
            // mark processed
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','withdraw_processed', ?, ?)")->execute([$uid, 0, json_encode(['withdraw_request'=>$id,'processed_by'=>$_SESSION['user_id']])]);
            $pdo->prepare("UPDATE withdraw_requests SET status='processed', processed_by=?, processed_at=NOW() WHERE id = ?")->execute([$_SESSION['user_id'],$id]);
            $pdo->commit();
            // notify user
            $uQ = $pdo->prepare("SELECT email,username FROM users WHERE id = ? LIMIT 1");
            $uQ->execute([$uid]); $uRow = $uQ->fetch();
            if ($uRow) send_mail($uRow['email'], "Withdraw #{$id} processed", "<p>Your withdraw #{$id} for ₦".number_format($amount,2)." has been processed.</p>");
            $_SESSION['flash'] = 'Withdraw processed';
            header('Location:?view=admin'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = 'Process withdraw error: '.$e->getMessage();
            header('Location:?view=admin'); exit;
        }
    }

    // ADMIN: create coupon
    if (!empty($_POST['action']) && $_POST['action'] === 'admin_create_coupon') {
        require_admin();
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin'); exit; }
        $code = trim($_POST['code'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        if (!$code || $amount <= 0) { $_SESSION['flash']='Complete coupon fields'; header('Location:?view=admin'); exit; }
        try {
            $ins = $pdo->prepare("INSERT INTO coupons (code,amount,is_active) VALUES (?, ?, 1)");
            $ins->execute([$code, $amount]);
            $_SESSION['flash'] = 'Coupon created';
            header('Location:?view=admin'); exit;
        } catch (Exception $e) {
            $_SESSION['flash'] = 'Coupon error: '.$e->getMessage();
            header('Location:?view=admin'); exit;
        }
    }

} // end POST

/* ========== DATA for rendering ========== */
$logged_in = !empty($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

$wallet = ['balance'=>0.0,'hold_amount'=>0.0,'available'=>0.0];
if ($logged_in) {
    ensure_wallet($pdo, $uid, 'NGN');
    $wq = $pdo->prepare("SELECT id, currency, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = 'NGN' LIMIT 1");
    $wq->execute([$uid]); $wr = $wq->fetch();
    if ($wr) {
        $wallet = ['balance'=>(float)$wr['balance'],'hold_amount'=>(float)$wr['hold_amount'],'available'=>((float)$wr['balance'] - (float)$wr['hold_amount'])];
    }
}

$products = $pdo->query("SELECT * FROM invest_products WHERE is_active = 1 ORDER BY principal_amount ASC")->fetchAll();
$investments = [];
if ($logged_in) {
    $iq = $pdo->prepare("SELECT inv.*, p.name as product_name FROM investments inv JOIN invest_products p ON inv.product_id = p.id WHERE inv.user_id = ? ORDER BY inv.created_at DESC");
    $iq->execute([$uid]); $investments = $iq->fetchAll();
}
$recent_tx = [];
if ($logged_in) {
    $tq = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $tq->execute([$uid]); $recent_tx = $tq->fetchAll();
}
$my_products = [];
if ($logged_in) {
    $upq = $pdo->prepare("SELECT up.*, p.name, p.principal_amount FROM user_products up JOIN invest_products p ON up.product_id = p.id WHERE up.user_id = ? ORDER BY up.created_at DESC");
    $upq->execute([$uid]); $my_products = $upq->fetchAll();
}

/* ========== RENDER (HTML) ========== */
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>BestTrading — NGN Investments</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--accent:#0b7b45}
*{box-sizing:border-box}
body{font-family:Inter,system-ui,Arial,sans-serif;background:var(--bg);margin:0;color:#0b1220}
.topbar{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:#0b1220;color:#fff}
.brand{font-weight:800;font-size:18px}
.nav{display:flex;gap:14px;align-items:center}
.nav a{color:#fff;text-decoration:none;padding:6px 10px;border-radius:6px}
.nav a.active{background:#075b33}
.container{max-width:1100px;margin:20px auto;padding:12px}
.grid{display:grid;grid-template-columns:1fr 320px;gap:18px}
.card{background:var(--card);border-radius:10px;padding:16px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
.small{font-size:13px;color:#64748b}
.btn{display:inline-block;padding:8px 14px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer;text-decoration:none}
.input,select,textarea{padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%;margin-top:6px;margin-bottom:10px}
.footer{margin-top:18px;text-align:center;color:#94a3b8;font-size:13px}
.plan{display:flex;justify-content:space-between;align-items:center;padding:12px;border:1px solid #eef7ef;border-radius:8px;margin-bottom:10px}
.menu-links{display:flex;gap:8px;flex-wrap:wrap}
.tx-list{max-height:260px;overflow:auto}
.badge{padding:6px 8px;border-radius:999px;background:#f1fdf7;border:1px solid #dff3e6}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:8px;border-bottom:1px solid #eee}
.profile-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.profile-tabs a{padding:8px 10px;background:#f3f6f9;border-radius:8px;text-decoration:none;color:#0b1220}
.notice{background:#fffbe6;border-left:4px solid #ffe08a;padding:10px;border-radius:6px;margin-bottom:12px}
@media (max-width:900px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
  <header class="topbar">
    <div style="display:flex;gap:20px;align-items:center">
      <div class="brand">BestTrading</div>
      <nav class="nav">
        <a href="?view=home" class="<?=($view==='home'?'active':'')?>">Home</a>
        <a href="?view=product" class="<?=($view==='product'?'active':'')?>">Product</a>
        <a href="?view=team" class="<?=($view==='team'?'active':'')?>">Team</a>
        <a href="?view=profile" class="<?=($view==='profile'?'active':'')?>">Profile</a>
      </nav>
    </div>

    <div style="display:flex;gap:12px;align-items:center">
      <?php if($logged_in): ?>
        <div style="text-align:right">
          <div style="font-size:12px;color:#d1d5db">Balance</div>
          <div style="font-weight:800">₦ <?=money($wallet['balance'])?></div>
          <div style="font-size:12px;color:#94a3b8">On hold: ₦ <?=money($wallet['hold_amount'])?></div>
        </div>
        <div style="margin-left:12px;color:#fff"><?=esc($_SESSION['role']==='admin'?'ADMIN':'USER')?></div>
        <form method="post" style="display:inline">
          <input type="hidden" name="csrf" value="<?=esc($token)?>">
          <input type="hidden" name="action" value="logout">
          <button class="btn" type="submit">Logout</button>
        </form>
      <?php else: ?>
        <a class="btn" href="?view=login">Login</a>
        <a class="btn" href="?view=register">Sign up</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="container">
    <?php if(!empty($flash)): ?><div class="card"><div class="small"><?=esc($flash)?></div></div><?php endif; ?>

    <?php if($view==='admin'): ?>
        <?php require_admin(); ?> 
      <div class="grid">
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

        <aside class="card">
          <h4>Pending Withdraws</h4>
          <?php $wreqs = $pdo->query("SELECT w.*, u.username FROM withdraw_requests w JOIN users u ON w.user_id = u.id WHERE w.status='pending' ORDER BY w.created_at ASC")->fetchAll(); ?>
          <?php if(!$wreqs) echo "<div class='small'>No pending withdraws</div>"; ?>
          <table class="table"><thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Action</th></tr></thead><tbody>
            <?php foreach($wreqs as $wr): ?>
              <tr>
                <td><?=intval($wr['id'])?></td>
                <td><?=esc($wr['username'])?></td>
                <td>₦ <?=number_format($wr['amount'],2)?></td>
                <td>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?=esc($token)?>">
                    <input type="hidden" name="action" value="admin_process_withdraw">
                    <input type="hidden" name="withdraw_id" value="<?=intval($wr['id'])?>">
                    <button class="btn" type="submit">Mark processed</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody></table>

          <hr>
          <h4>Create Coupon</h4>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=esc($token)?>">
            <input type="hidden" name="action" value="admin_create_coupon">
            <input class="input" name="code" placeholder="CODE123" required>
            <input class="input" name="amount" placeholder="Amount (e.g. 1500)" required>
            <button class="btn">Create coupon</button>
          </form>
        </aside>
      </div>

    <?php else: ?>

      <!-- Normal pages -->
      <?php if ($view === 'product'): ?>
        <div class="grid">
          <section class="card">
            <h3>Products</h3>
            <div class="small">Choose a plan and add to your products list or invest directly (investment requires an admin-approved deposit).</div>
            <div style="margin-top:12px">
              <?php foreach($products as $p): ?>
                <div class="plan">
                  <div>
                    <div style="font-weight:700"><?=esc($p['name'])?></div>
                    <div class="small">Principal: ₦ <?=number_format($p['principal_amount'],2)?> — Daily: ₦ <?=number_format($p['daily_payout'],2)?> — Duration: <?=intval($p['duration_days'])?> days</div>
                  </div>
                  <div>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?=esc($token)?>">
                      <input type="hidden" name="action" value="add_product">
                      <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
                      <button class="btn">Save to products</button>
                    </form>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?=esc($token)?>">
                      <input type="hidden" name="action" value="invest">
                      <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
                      <button class="btn" style="background:#0b5b90">Invest</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <aside class="card">
            <h4>Your Products</h4>
            <?php if(empty($my_products)): ?><div class="small">No saved products</div><?php else: ?>
              <ul>
              <?php foreach($my_products as $mp): ?>
                <li><?=esc($mp['name'])?> — ₦ <?=number_format($mp['principal_amount'],2)?> (added <?=esc($mp['created_at'])?>)</li>
              <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </aside>
        </div>

      <?php elseif ($view === 'team'): ?>
        <div class="card">
          <h3>Our Team</h3>
          <p class="small">We are a demo team. Replace this page with your actual team members, biographies, and contact links. Join our community: <a href="#" onclick="alert('Join group link placeholder')">Join our group</a>.</p>
        </div>

      <?php elseif ($view === 'profile'): ?>
        <?php
          $tab = $_GET['tab'] ?? 'overview';
          $userRow = null;
          if ($logged_in) {
              $uQ = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
              $uQ->execute([$_SESSION['user_id']]); $userRow = $uQ->fetch();
          }
        ?>
        <div class="card">
          <h3>Profile</h3>
          <?php if(!$logged_in): ?>
            <div class="small">Please <a href="?view=login">login</a> or <a href="?view=register">signup</a>.</div>
          <?php else: ?>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-weight:700"><?=esc($userRow['username'])?></div>
                <div class="small"><?=esc($userRow['email'])?></div>
              </div>
              <div style="text-align:right">
                <div class="small">Balance: ₦ <?=money($wallet['balance'])?></div>
                <div class="small">Available: ₦ <?=money($wallet['available'])?></div>
              </div>
            </div>

            <div style="margin-top:12px" class="profile-tabs">
              <a href="?view=profile&tab=overview" class="<?=($tab==='overview'?'active':'')?>">Overview</a>
              <a href="deposit,php">Recharge</a>
              <a href="withdraw.php">Withdraw</a>
              <a href="?view=profile&tab=earnings">Earnings</a>
              <a href="?view=profile&tab=myproducts">My Products</a>
              <a href="?view=profile&tab=withdraws">Withdraw Record</a>
              <a href="?view=profile&tab=team">Team</a>
              <a href="?view=profile&tab=bank">Bank Account</a>
              <a href="?view=profile&tab=security">Change Password</a>
              <a href="?view=profile&tab=logout">Logout</a>
            </div>

            <?php if($tab==='overview'): ?>
              <h4>Quick actions</h4>
              <div class="menu-links">
                <a class="btn" href="deposit.php">Deposit</a>
                <a class="btn" href="withdraw.php">Withdraw</a>
                <a class="btn" href="?view=support">Support</a>
                <a class="btn" href="?view=coupon">Coupon</a>
              </div>

            <?php elseif($tab==='recharge'): ?>
              <h4>Deposit history</h4>
              <?php $d = $pdo->prepare("SELECT * FROM deposit.php WHERE user_id = ? ORDER BY created_at DESC"); $d->execute([$uid]); $ds = $d->fetchAll(); ?>
              <table class="table"><thead><tr><th>ID</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead>
                <tbody><?php foreach($ds as $r): ?><tr><td><?=intval($r['id'])?></td><td>₦ <?=number_format($r['amount'],2)?></td><td><?=esc($r['status'])?></td><td><?=esc($r['created_at'])?></td></tr><?php endforeach; ?></tbody>
              </table>

            <?php elseif($tab==='withdraw'): ?>
              <h4>Make Withdraw</h4>
              <form method="post">
                <input type="hidden" name="csrf" value="<?=esc($token)?>">
                <input type="hidden" name="action" value="withdraw_request">
                <label class="small">Amount <input class="input" name="amount" type="number" step="0.01" required></label>
                <label class="small">Bank name <input class="input" name="bank_name" required></label>
                <label class="small">Account number <input class="input" name="account_number" required></label>
                <label class="small">Account name <input class="input" name="account_name" required></label>
                <button class="btn">Request Withdraw</button>
              </form>

            <?php elseif($tab==='earnings'): ?>
              <h4>Transactions</h4>
              <?php $tx = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC"); $tx->execute([$uid]); $txs = $tx->fetchAll(); ?>
              <table class="table"><thead><tr><th>Time</th><th>Type</th><th>Amount</th></tr></thead><tbody>
                <?php foreach($txs as $t): ?><tr><td><?=esc($t['created_at'])?></td><td><?=esc($t['type'])?></td><td><?=($t['amount']>=0?'+':'')?>₦ <?=number_format($t['amount'],2)?></td></tr><?php endforeach; ?>
              </tbody></table>

            <?php elseif($tab==='myproducts'): ?>
              <h4>My Saved Products</h4>
              <?php if(empty($my_products)): ?><div class="small">You have no saved products.</div><?php else: ?>
                <ul><?php foreach($my_products as $mp): ?><li><?=esc($mp['name'])?> — ₦ <?=number_format($mp['principal_amount'],2)?> (added <?=esc($mp['created_at'])?>)</li><?php endforeach;?></ul><?php endif; ?>

            <?php elseif($tab==='withdraws'): ?>
              <h4>Withdraw history</h4>
              <?php $wr = $pdo->prepare("SELECT w.*, u.username FROM withdraw_requests w LEFT JOIN users u ON u.id = w.user_id WHERE w.user_id = ? ORDER BY w.created_at DESC"); $wr->execute([$uid]); $wrs = $wr->fetchAll(); ?>
              <table class="table"><thead><tr><th>Time</th><th>Amount</th><th>Status</th></tr></thead><tbody><?php foreach($wrs as $w): ?><tr><td><?=esc($w['created_at'])?></td><td>₦ <?=number_format($w['amount'],2)?></td><td><?=esc($w['status'])?></td></tr><?php endforeach; ?></tbody></table>

            <?php elseif($tab==='team'): ?>
              <h4>Your team</h4>
              <p class="small">Team / referral features are not implemented in this demo. Add a referral table and logic to track your team.</p>

            <?php elseif($tab==='bank'): ?>
              <h4>Bank account</h4>
              <form method="post">
                <input type="hidden" name="csrf" value="<?=esc($token)?>">
                <input type="hidden" name="action" value="update_bank">
                <label class="small">Bank name <input class="input" name="bank_name" value="<?=esc($userRow['bank_name'] ?? '')?>"></label>
                <label class="small">Account number <input class="input" name="bank_account" value="<?=esc($userRow['bank_account'] ?? '')?>"></label>
                <label class="small">Account name <input class="input" name="account_name" value="<?=esc($userRow['account_name'] ?? '')?>"></label>
                <button class="btn">Save bank</button>
              </form>

            <?php elseif($tab==='security'): ?>
              <h4>Change password</h4>
              <form method="post">
                <input type="hidden" name="csrf" value="<?=esc($token)?>">
                <input type="hidden" name="action" value="change_password">
                <label class="small">Current password <input class="input" name="current" type="password" required></label>
                <label class="small">New password <input class="input" name="new" type="password" required></label>
                <button class="btn">Change password</button>
              </form>

            <?php elseif($tab==='logout'): ?>
              <form method="post"><input type="hidden" name="csrf" value="<?=esc($token)?>"><input type="hidden" name="action" value="logout"><button class="btn">Logout now</button></form>
            <?php endif; ?>
          <?php endif; ?>
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


      <?php elseif ($view === 'withdraw'): ?>
        <div class="card">
          <h3>Withdraw</h3>
          <?php if(!$logged_in): ?><div class="small">Please <a href="?view=login">login</a>.</div>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?=esc($token)?>">
              <input type="hidden" name="action" value="withdraw_request">
              <label class="small">Amount <input class="input" name="amount" type="number" step="0.01" required></label>
              <label class="small">Bank name <input class="input" name="bank_name" required></label>
              <label class="small">Account number <input class="input" name="account_number" required></label>
              <label class="small">Account name <input class="input" name="account_name" required></label>
              <button class="btn">Request withdraw</button>
            </form>
          <?php endif; ?>
        </div>

      <?php elseif ($view === 'support'): ?>
        <div class="card">
          <h3>Support</h3>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=esc($token)?>">
            <input type="hidden" name="action" value="support_send">
            <label class="small">Name <input class="input" name="name" value="<?=esc($_SESSION['username'] ?? '')?>"></label>
            <label class="small">Email <input class="input" name="email" value="<?=esc($_SESSION['email'] ?? '')?>"></label>
            <label class="small">Message <textarea class="input" name="message" rows="6" required></textarea></label>
            <button class="btn">Send message</button>
          </form>
        </div>

      <?php elseif ($view === 'coupon'): ?>
        <div class="card">
          <h3>Coupon</h3>
          <?php if(!$logged_in): ?><div class="small">Login to redeem coupons.</div>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?=esc($token)?>">
              <input type="hidden" name="action" value="redeem_coupon">
              <label class="small">Code <input class="input" name="code" required></label>
              <button class="btn">Redeem</button>
            </form>
          <?php endif; ?>
        </div>

      <?php elseif ($view === 'login'): ?>
        <div class="card">
          <h3>Login</h3>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=esc($token)?>">
            <input type="hidden" name="action" value="login">
            <label class="small">Email <input class="input" name="email" type="email" required></label>
            <label class="small">Password <input class="input" name="password" type="password" required></label>
            <button class="btn">Login</button>
          </form>
        </div>

      <?php elseif ($view === 'register'): ?>
        <div class="card">
          <h3>Register</h3>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=esc($token)?>">
            <input type="hidden" name="action" value="register">
            <label class="small">Username <input class="input" name="username" required></label>
            <label class="small">Email <input class="input" name="email" type="email" required></label>
            <label class="small">Password <input class="input" name="password" type="password" required></label>
            <button class="btn">Sign up (get ₦<?=number_format(WELCOME_BONUS_NGN,2)?>)</button>
          </form>
        </div>

      <?php else: /* default home */ ?>
        <div class="grid">
          <section class="card">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <h3>Welcome <?= $logged_in ? esc($userRow['username'] ?? '') : 'guest' ?></h3>
                <div class="small">Start by depositing or redeeming a coupon. New users receive ₦<?=number_format(WELCOME_BONUS_NGN,2)?> (demo). Deposits require admin approval before you may invest.</div>
              </div>
              <div style="text-align:right">
                <a class="btn" href="?view=deposit">Deposit</a>
                <a class="btn" href="?view=withdraw" style="background:#6b7280">Withdraw</a>
                <a class="btn" href="?view=support">Support</a>
                <a class="btn" href="?view=coupon">Coupon</a>
              </div>
            </div>

            <div style="margin-top:14px">
              <!-- machine graphic -->
              <div style="background:linear-gradient(180deg,#eafaf1,#fff);padding:16px;border-radius:12px;margin-bottom:12px">
                <svg width="100%" height="120" viewBox="0 0 360 120" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="6" width="348" height="108" rx="10" fill="#ffffff" stroke="#dff3e6" stroke-width="2"/><g fill="#0b7b45"><rect x="28" y="22" width="60" height="76" rx="8" fill="#eafaf1"/><rect x="108" y="22" width="60" height="76" rx="8" fill="#eafaf1"/><rect x="188" y="22" width="60" height="76" rx="8" fill="#eafaf1"/><rect x="268" y="22" width="60" height="76" rx="8" fill="#eafaf1"/></g><text x="20" y="110" font-size="12" fill="#64748b">Machine: choose a plan and invest (principal locked until maturity).</text></svg>
              </div>

              <h4>Investment Plans</h4>
              <?php foreach($products as $p): ?>
                <div class="plan">
                  <div>
                    <div style="font-weight:700"><?=esc($p['name'])?></div>
                    <div class="small">Principal: ₦ <?=number_format($p['principal_amount'],2)?> — Daily payout: ₦ <?=number_format($p['daily_payout'],2)?> — 30 days</div>
                    <div class="small">Total payout (sum of daily payouts): ₦ <?=number_format($p['daily_payout'] * $p['duration_days'],2)?></div>
                  </div>
                  <div>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?=esc($token)?>">
                      <input type="hidden" name="action" value="add_product">
                      <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
                      <button class="btn">Save</button>
                    </form>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?=esc($token)?>">
                      <input type="hidden" name="action" value="invest">
                      <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
                      <button class="btn" style="background:#0b5b90">Invest</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <aside class="card">
            <h4>Quick Info</h4>
            <div class="small">
              <ul>
                <li>Welcome bonus: ₦<?=number_format(WELCOME_BONUS_NGN,2)?> credited at signup (demo).</li>
                <li>You <strong>cannot invest</strong> until you have at least one admin-approved deposit.</li>
                <li>Withdraw requests deduct immediately; admin will process payouts.</li>
                <li>For production: enable HTTPS, SMTP, KYC, 2FA and monitoring.</li>
              </ul>
            </div>
            <hr>
            <h4>Recent transactions</h4>
            <div class="tx-list">
              <?php if(empty($recent_tx)): ?><div class="small">No transactions yet</div><?php endif; ?>
              <?php foreach($recent_tx as $tx): ?>
                <div style="padding:6px;border-bottom:1px dashed #eee">
                  <div class="small"><strong><?=esc($tx['currency'])?> <?=($tx['amount']>=0?'+':'')?><?=number_format($tx['amount'],2)?></strong></div>
                  <div class="small" style="color:#94a3b8"><?=esc($tx['type'])?> — <?=esc($tx['created_at'])?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </aside>
        </div>

      <?php endif; ?>

    <?php endif; ?>

    <div class="footer">Demo platform — NGN only. Replace demo mail with SMTP & harden before production.</div>
  </main>
</body>
</html>