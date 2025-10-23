<?php
/**
 * index.php — Single-file demo investment platform (completed)
 *
 * - Deposit flow: enter -> confirm -> create deposit_request + transaction
 * - Invest handler implemented: locks principal, creates investment, creates txs
 * - Coupons: admin creates coupons, users redeem and get wallet credited
 * - Check-in: simple daily rewards (24h/48h/72h+ ladder)
 * - Fixes: money_fmt() alias, execute_with_check helper to detect placeholder mismatches
 *
 * DEMO ONLY. Do NOT use as-is in production handling real funds.
 */

/* ========== CONFIG ========== */
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: 3306;
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'investtment_db';
date_default_timezone_set('Africa/Lagos');

define('WELCOME_BONUS', 1500.00);
define('MIN_DEPOSIT', 2000.00);
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@example.com');
define('DEPOSIT_ACCOUNT_NUMBER', getenv('DEPOSIT_ACCOUNT_NUMBER') ?: '7050672951');
define('DEPOSIT_BANK_NAME', getenv('DEPOSIT_BANK_NAME') ?: 'Palmpay');
define('DEPOSIT_ACCOUNT_NAME', getenv('DEPOSIT_ACCOUNT_NAME') ?: 'Olayinka Mary Ogundele');

define('UPLOAD_DIR', __DIR__ . '/uploads');
define('KYC_DIR', UPLOAD_DIR . '/kyc');

/* checkin rewards (NGN) */
$CHECKIN_REWARDS = [
    '24h' => 200.00,
    '48h' => 400.00,
    '72h' => 600.00, // 3 days or more
];

/* ========== SESSIONS ========== */
session_set_cookie_params([
    'lifetime'=>0,
    'path'=>'/',
    'domain'=>'',
    'secure'=>false, // set to true on HTTPS
    'httponly'=>true,
    'samesite'=>'Lax'
]);
if (session_status() === PHP_SESSION_NONE) session_start();

/* ========== DB CONNECT & AUTO-CREATE DB ========== */
try {
    $pdo0 = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) { die("DB server connection failed: ".htmlspecialchars($e->getMessage())); }

try {
    $pdo = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) { die("DB connect failed: ".htmlspecialchars($e->getMessage())); }

/* ========== CREATE TABLES ========== */
$schemas = [
"CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  totp_secret VARCHAR(64) DEFAULT NULL,
  secret_code_hash VARCHAR(255) DEFAULT NULL,
  secret_code_created_at TIMESTAMP NULL,
  welcome_released TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(8) NOT NULL DEFAULT 'NGN',
  balance DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  hold_amount DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_currency (user_id, currency),
  CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(8) NOT NULL DEFAULT 'NGN',
  type VARCHAR(80) NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS deposit_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
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
  CONSTRAINT fk_inv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_product FOREIGN KEY (product_id) REFERENCES invest_products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  event VARCHAR(150) NOT NULL,
  meta JSON NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  key_str VARCHAR(255) NOT NULL,
  action VARCHAR(80) NOT NULL,
  hits INT NOT NULL DEFAULT 1,
  last_hit TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_key_action (key_str, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

// coupons table: admin-created coupons
"CREATE TABLE IF NOT EXISTS coupons (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL UNIQUE,
  amount DECIMAL(28,8) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

// coupon redemptions log
"CREATE TABLE IF NOT EXISTS coupon_redemptions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  coupon_id BIGINT NOT NULL,
  user_id INT NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cr_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

// checkins table
"CREATE TABLE IF NOT EXISTS checkins (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  checkin_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reward DECIMAL(28,8) NOT NULL,
  consecutive_days INT DEFAULT 1,
  CONSTRAINT fk_checkin_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

foreach ($schemas as $sql) {
    $pdo->exec($sql);
}

/* ========== SEED ADMIN + 10 PLANS ========== */
$adminCheck = $pdo->prepare("SELECT id FROM users WHERE role='admin' LIMIT 1"); $adminCheck->execute();
if (!$adminCheck->fetch()) {
    $hash = password_hash('Admin123!', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username,email,password,role,welcome_released) VALUES ('owner', ?, ?, 'admin', 1)")
        ->execute([ADMIN_EMAIL, $hash]);
    $aid = $pdo->lastInsertId();
    $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, 'NGN', 0, 0)")->execute([$aid]);
}

/* seed investment products if empty */
$count = (int)$pdo->query("SELECT COUNT(*) FROM invest_products")->fetchColumn();
if ($count === 0) {
    $ins = $pdo->prepare("INSERT INTO invest_products (name,principal_amount,duration_days,daily_payout) VALUES (?, ?, ?, ?)");
    $basePrincipal = 2000;
    $baseDaily = 700;
    for ($i=0;$i<10;$i++) {
        $p = $basePrincipal * (2**$i);
        $d = $baseDaily * (2**$i);
        $ins->execute(["Plan ".($i+1)." - ₦".number_format($p,0), $p, 30, $d]);
    }
}

/* ========== Ensure upload dirs exist ========== */
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0750, true);
if (!is_dir(KYC_DIR)) @mkdir(KYC_DIR, 0750, true);

/* ========== HELPERS ========== */
function csrf_token() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function check_csrf($t) { return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }
function ensure_wallet($pdo, $uid, $currency='NGN') { $s = $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, ?, 0, 0)"); $s->execute([$uid,$currency]); }
function money($x) { return number_format((float)$x,2,'.',','); }
function money_fmt($x) { return money($x); } // compatibility alias
function audit($pdo,$uid,$event,$meta=null){ $ip = $_SERVER['REMOTE_ADDR'] ?? null; $pdo->prepare("INSERT INTO audit_logs (user_id,event,meta,ip) VALUES (?,?,?,?)")->execute([$uid,$event,json_encode($meta),$ip]); }
function require_login() { if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; } }

/* generate a per-user deposit/payment reference */
function generate_payment_ref($uid) {
    try {
        $rand = bin2hex(random_bytes(3));
    } catch (Exception $e) {
        $rand = substr(hash('sha256', microtime(true)),0,6);
    }
    return 'mav112v' . intval($uid) . 'v' . $rand;
}

/* debug/validation executor (throws clear exceptions if placeholders mismatch) */
function execute_with_check(PDO $pdo, $sql, $params = []) {
    // sanitize and count placeholders (ignore quoted strings)
    $copy = $sql;
    $copy = preg_replace("/'([^'\\\\]|\\\\.)*'/", "''", $copy);
    $copy = preg_replace('/"([^"\\\\]|\\\\.)*"/', '""', $copy);
    $posCount = substr_count($copy, '?');
    preg_match_all('/(?<!:):([a-zA-Z_][a-zA-Z0-9_]*)/', $copy, $m);
    $named = $m[1] ?? [];
    $namedCount = count($named);

    if ($posCount > 0 && $namedCount > 0) {
        throw new Exception("Mixed positional and named placeholders detected. SQL: {$sql}");
    }

    if ($posCount > 0) {
        if (!is_array($params)) throw new Exception("Positional placeholders require array params.");
        $paramCount = count($params);
        if ($paramCount !== $posCount) {
            throw new Exception("Placeholder mismatch: SQL has {$posCount} ? placeholders but you passed {$paramCount} params. SQL: {$sql}. Params: ".json_encode($params));
        }
    } elseif ($namedCount > 0) {
        if (!is_array($params)) throw new Exception("Named placeholders require assoc array params.");
        foreach ($named as $name) {
            if (!array_key_exists($name,$params) && !array_key_exists(":$name",$params)) {
                throw new Exception("Named param :{$name} not provided. SQL: {$sql}. Params: ".json_encode($params));
            }
        }
    } else {
        if (!empty($params)) {
            throw new Exception("No placeholders in SQL but params provided. SQL: {$sql}. Params: ".json_encode($params));
        }
    }

    $stmt = $pdo->prepare($sql);
    if ($namedCount > 0) $stmt->execute($params);
    else $stmt->execute(array_values($params));
    return $stmt;
}

/* ========== RATE LIMITER ========== */
function rate_limit_check(PDO $pdo, $key, $action, $limit=10, $seconds=60) {
    $now = new DateTimeImmutable();
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare("SELECT * FROM rate_limits WHERE key_str=? AND action=? FOR UPDATE");
        $q->execute([$key,$action]);
        $r = $q->fetch();
        if (!$r) {
            $ins = $pdo->prepare("INSERT INTO rate_limits (key_str,action,hits,last_hit) VALUES (?,?,1,NOW())");
            $ins->execute([$key,$action]);
            $pdo->commit();
            return true;
        } else {
            $last = new DateTimeImmutable($r['last_hit']);
            $diff = $now->getTimestamp() - $last->getTimestamp();
            if ($diff > $seconds) {
                $u = $pdo->prepare("UPDATE rate_limits SET hits=1,last_hit=NOW() WHERE id=?");
                $u->execute([$r['id']]);
                $pdo->commit();
                return true;
            } else {
                if ($r['hits'] + 1 > $limit) {
                    $pdo->rollBack();
                    return false;
                } else {
                    $u = $pdo->prepare("UPDATE rate_limits SET hits = hits + 1, last_hit = NOW() WHERE id=?");
                    $u->execute([$r['id']]);
                    $pdo->commit();
                    return true;
                }
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return true; // demo: fail open
    }
}

/* ========== EMAIL (PHPMailer fallback) ========== */
function send_email($to, $subject, $body) {
    $smtpHost = getenv('SMTP_HOST') ?: '';
    $smtpUser = getenv('SMTP_USER') ?: '';
    $smtpPass = getenv('SMTP_PASS') ?: '';
    $smtpPort = getenv('SMTP_PORT') ?: 587;
    $from = getenv('MAIL_FROM') ?: 'noreply@example.com';

    if (file_exists(__DIR__.'/vendor/autoload.php')) {
        require_once __DIR__.'/vendor/autoload.php';
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUser);
            if (!empty($smtpUser)) {
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
            }
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            $mail->setFrom($from, 'InvestDemo');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer failed: " . $e->getMessage());
            return false;
        }
    } else {
        $headers = "From: InvestDemo <{$from}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        return @mail($to, $subject, $body, $headers);
    }
}

/* ========== small helper for upload validation ========== */
function validate_kyc_file($f) {
    $allowed = ['image/jpeg','image/png','application/pdf'];
    if ($f['error'] !== UPLOAD_ERR_OK) return "Upload error code ".$f['error'];
    if ($f['size'] > 5*1024*1024) return "File too large (max 5MB)";
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return "Unsupported file type ({$mime})";
    return true;
}

/* ========== INVESTMENT PROCESSOR (runs on page load) ========== */
function process_investments($pdo) {
    $rows = $pdo->query("SELECT * FROM investments WHERE status='active'")->fetchAll();
    foreach ($rows as $r) {
        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT * FROM investments WHERE id=? FOR UPDATE"); $q->execute([$r['id']]); $inv = $q->fetch();
            if (!$inv) { $pdo->rollBack(); continue; }
            $now = new DateTimeImmutable();
            $last = new DateTimeImmutable($inv['last_payout_at']);
            $remaining = (int)$inv['remaining_days'];
            $days = floor(($now->getTimestamp() - $last->getTimestamp()) / 86400);
            if ($days <= 0) { $pdo->commit(); continue; }
            $payDays = min($days, $remaining);
            if ($payDays > 0) {
                $total = round($payDays * (float)$inv['daily_payout'],2);
                ensure_wallet($pdo, $inv['user_id'], 'NGN');
                $wq = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE"); $wq->execute([$inv['user_id']]); $w = $wq->fetch();
                if ($w) {
                    $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$total, $w['id']]);
                    $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','investment_payout', ?, ?)")->execute([$inv['user_id'],$total,json_encode(['inv'=>$inv['id'],'days'=>$payDays])]);
                }
                $newRemaining = max(0, $remaining - $payDays);
                $newLast = $last->modify("+{$payDays} days")->format('Y-m-d H:i:s');
                $pdo->prepare("UPDATE investments SET remaining_days=?, last_payout_at=? WHERE id=?")->execute([$newRemaining,$newLast,$inv['id']]);
            }
            // if matured, release principal from hold to balance (if there is hold)
            $ref = $pdo->query("SELECT remaining_days,start_at,mature_at,principal,status,user_id FROM investments WHERE id=".intval($r['id']))->fetch();
            if ($ref && $ref['remaining_days'] == 0 && $ref['status'] == 'active') {
                $wq = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE"); $wq->execute([$r['user_id']]); $w = $wq->fetch();
                if ($w) {
                    $release = min((float)$w['hold_amount'], (float)$ref['principal']);
                    if ($release > 0) {
                        $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance + ? WHERE id = ?")->execute([$release,$release,$w['id']]);
                        $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','investment_principal_return', ?, ?)")->execute([$r['user_id'],$release,json_encode(['inv'=>$r['id']])]);
                    }
                }
                $pdo->prepare("UPDATE investments SET status='matured' WHERE id=?")->execute([$r['id']]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("process_investments error: ".$e->getMessage());
        }
    }
}
process_investments($pdo); // demo: run on page load; use cronjob in production

/* ========== ROUTING & POST HANDLERS ========== */
$view = $_GET['view'] ?? 'home';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$csrf = csrf_token();

/* ========== POST HANDLERS ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Register
    if (($_POST['action'] ?? '') === 'register') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=register'); exit; }
        if (!rate_limit_check($pdo, $clientIp, 'register', 5, 300)) { $_SESSION['flash']='Too many registration attempts, slow down'; header('Location:?view=register'); exit; }

        $username = trim($_POST['username'] ?? ''); $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
        $secret_code = trim($_POST['secret_code'] ?? '');
        if (!$username || !$email || !$password || !$secret_code) { $_SESSION['flash']='Complete all fields'; header('Location:?view=register'); exit; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $_SESSION['flash']='Invalid email'; header('Location:?view=register'); exit; }
        if (!preg_match('/^\d{4}$/', $secret_code)) { $_SESSION['flash']='Secret code must be 4 digits'; header('Location:?view=register'); exit; }

        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $secret_hash = password_hash($secret_code, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username,email,password,welcome_released, secret_code_hash, secret_code_created_at) VALUES (?,?,?,?,?,NOW())");
            $stmt->execute([$username,$email,$hash,0,$secret_hash]);
            $uid = $pdo->lastInsertId();
            ensure_wallet($pdo,$uid,'NGN');
            // credit welcome bonus and hold it
            $pdo->prepare("UPDATE wallets SET balance = balance + ?, hold_amount = hold_amount + ? WHERE user_id = ? AND currency = 'NGN'")->execute([WELCOME_BONUS, WELCOME_BONUS, $uid]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN', 'welcome_credit', ?, ?)")->execute([$uid, WELCOME_BONUS, json_encode(['held'=>true])]);
            $pdo->commit();
            $_SESSION['user_id'] = $uid; $_SESSION['username']=$username; $_SESSION['role']='user';
            audit($pdo,$uid,'registered', ['email'=>$email]);
            $_SESSION['flash']='Registered. ₦'.number_format(WELCOME_BONUS,2).' credited but held until you have made a deposit.';
            header('Location:?view=home'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Registration error: '.$e->getMessage(); header('Location:?view=register'); exit;
        }
    }

    // Login
    if (($_POST['action'] ?? '') === 'login') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=login'); exit; }
        if (!rate_limit_check($pdo, $clientIp, 'login', 8, 120)) { $_SESSION['flash']='Too many login attempts; try later'; header('Location:?view=login'); exit; }
        $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
        if (!$email || !$password) { $_SESSION['flash']='Missing credentials'; header('Location:?view=login'); exit; }
        $stmt = $pdo->prepare("SELECT id,password,role,username FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); $u = $stmt->fetch();
        if (!$u || !password_verify($password, $u['password'])) { $_SESSION['flash']='Invalid credentials'; header('Location:?view=login'); exit; }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id']; $_SESSION['role'] = $u['role']; $_SESSION['username'] = $u['username'];
        audit($pdo,$u['id'],'login',[]);
        $_SESSION['flash']='Logged in';
        header('Location:?view=home'); exit;
    }

    // Logout
    if (($_POST['action'] ?? '') === 'logout') {
        audit($pdo,$_SESSION['user_id'] ?? null,'logout',[]);
        session_unset(); session_destroy(); session_start();
        $_SESSION['flash']='Logged out'; header('Location:?view=login'); exit;
    }

    // KYC upload
    if (($_POST['action'] ?? '') === 'kyc_upload') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=profile'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        if (!isset($_FILES['kyc_doc'])) { $_SESSION['flash']='No file uploaded'; header('Location:?view=profile'); exit; }
        $res = validate_kyc_file($_FILES['kyc_doc']);
        if ($res !== true) { $_SESSION['flash']=$res; header('Location:?view=profile'); exit; }
        $ext = pathinfo($_FILES['kyc_doc']['name'], PATHINFO_EXTENSION);
        $safeName = 'kyc_'.$_SESSION['user_id'].'_'.time().'.'.$ext;
        $dest = KYC_DIR . '/' . $safeName;
        if (!move_uploaded_file($_FILES['kyc_doc']['tmp_name'], $dest)) {
            $_SESSION['flash']='Failed to store file'; header('Location:?view=profile'); exit;
        }
        audit($pdo,$_SESSION['user_id'],'kyc_upload',['file'=>$safeName]);
        send_email(ADMIN_EMAIL, "KYC uploaded by user #{$_SESSION['user_id']}", "User {$_SESSION['user_id']} uploaded KYC: {$safeName}");
        $_SESSION['flash']='KYC uploaded — admin will review shortly.';
        header('Location:?view=profile'); exit;
    }

    /* ========== Deposit multi-step ========== */
    // deposit_start: user submits amount & sender name => store in session and redirect to confirm
    if (($_POST['action'] ?? '') === 'deposit_start') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=deposit'); exit; }
        if (!rate_limit_check($pdo, $clientIp, 'deposit_start', 6, 300)) { $_SESSION['flash']='Too many deposit attempts; try later'; header('Location:?view=deposit'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }

        $preset = (float)($_POST['preset_amount'] ?? 0);
        $custom = isset($_POST['custom_amount']) ? (float)$_POST['custom_amount'] : 0.0;
        $amount = $custom > 0 ? $custom : $preset;
        $sender_name = trim($_POST['sender_name'] ?? '');
        $reference = trim($_POST['payment_ref'] ?? '');

        if ($amount < MIN_DEPOSIT) { $_SESSION['flash']='Minimum deposit is ₦'.number_format(MIN_DEPOSIT,2); header('Location:?view=deposit'); exit; }
        if (!$sender_name) { $_SESSION['flash']='Please provide the name used to send the funds'; header('Location:?view=deposit'); exit; }

        if (empty($reference)) $reference = generate_payment_ref($_SESSION['user_id']);

        // store in session and redirect to confirm step
        $_SESSION['deposit_amount'] = $amount;
        $_SESSION['deposit_sender_name'] = $sender_name;
        $_SESSION['deposit_payment_ref'] = $reference;
        header('Location:?view=deposit&step=confirm'); exit;
    }

    // deposit_confirm: create deposit_requests and record transaction
    if (($_POST['action'] ?? '') === 'deposit_confirm') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=deposit'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        $amount = floatval($_SESSION['deposit_amount'] ?? 0);
        $sender_name = trim($_SESSION['deposit_sender_name'] ?? '');
        $reference = trim($_SESSION['deposit_payment_ref'] ?? '');

        if ($amount < MIN_DEPOSIT || !$sender_name) { $_SESSION['flash']='Invalid deposit info'; header('Location:?view=deposit'); exit; }

        try {
            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO deposit_requests (user_id,amount,account_number,bank_name,account_name,status,created_at) VALUES (?,?,?,?,?,'pending',NOW())");
            $ins->execute([$_SESSION['user_id'],$amount,DEPOSIT_ACCOUNT_NUMBER,DEPOSIT_BANK_NAME,DEPOSIT_ACCOUNT_NAME]);
            $id = $pdo->lastInsertId();

            $meta = [
                'deposit_id' => (int)$id,
                'payment_ref' => $reference,
                'sender_name' => $sender_name,
                'dest_account' => DEPOSIT_ACCOUNT_NUMBER,
                'dest_bank' => DEPOSIT_BANK_NAME,
                'dest_name' => DEPOSIT_ACCOUNT_NAME
            ];
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','deposit_user_confirmed', ?, ?)")->execute([$_SESSION['user_id'],$amount,json_encode($meta)]);
            $pdo->commit();

            send_email(ADMIN_EMAIL, "Deposit confirmation #{$id}", "User {$_SESSION['user_id']} says they paid ₦".number_format($amount,2)." with reference {$reference}. Sender name: {$sender_name}");
            audit($pdo,$_SESSION['user_id'],'deposit_request_created',['id'=>$id,'amount'=>$amount,'ref'=>$reference]);
            // clear session deposit
            unset($_SESSION['deposit_amount'], $_SESSION['deposit_sender_name'], $_SESSION['deposit_payment_ref']);
            $_SESSION['flash']="Deposit confirmation recorded. Reference: {$reference}. Admin will review and approve.";
            header('Location:?view=deposit'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Deposit error: '.$e->getMessage(); header('Location:?view=deposit'); exit;
        }
    }

    // Admin approves deposit
    if (($_POST['action'] ?? '') === 'admin_approve_deposit') {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { $_SESSION['flash']='Admin required'; header('Location:?view=login'); exit; }
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin'); exit; }
        $id = (int)($_POST['deposit_id'] ?? 0); if (!$id) { $_SESSION['flash']='Invalid id'; header('Location:?view=admin'); exit; }
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("SELECT * FROM deposit_requests WHERE id=? FOR UPDATE"); $s->execute([$id]); $req = $s->fetch();
            if (!$req || $req['status'] !== 'pending') { $pdo->rollBack(); $_SESSION['flash']='Not pending'; header('Location:?view=admin'); exit; }
            $uid = $req['user_id']; $amount = (float)$req['amount'];
            ensure_wallet($pdo,$uid,'NGN');
            $w = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE"); $w->execute([$uid]); $wr = $w->fetch();
            if (!$wr) { $pdo->rollBack(); $_SESSION['flash']='Wallet missing'; header('Location:?view=admin'); exit; }
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$amount, $wr['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','deposit_approved', ?, ?)")->execute([$uid,$amount,json_encode(['deposit_id'=>$id,'approved_by'=>$_SESSION['user_id']])]);
            $pdo->prepare("UPDATE deposit_requests SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'],$id]);
            // release welcome bonus if still held
            $u = $pdo->prepare("SELECT welcome_released FROM users WHERE id=? FOR UPDATE"); $u->execute([$uid]); $uro = $u->fetch();
            if ($uro && intval($uro['welcome_released']) === 0) {
                $release = WELCOME_BONUS;
                $pdo->prepare("UPDATE wallets SET hold_amount = GREATEST(hold_amount - ?,0) WHERE user_id=? AND currency='NGN'")->execute([$release,$uid]);
                $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','welcome_released', ?, ?)")->execute([$uid,$release,json_encode(['note'=>'released after approved deposit'])]);
                $pdo->prepare("UPDATE users SET welcome_released=1 WHERE id=?")->execute([$uid]);
            }
            $pdo->commit();
            audit($pdo,$_SESSION['user_id'],'deposit_approved',['deposit_id'=>$id,'user'=>$uid,'amount'=>$amount]);
            $_SESSION['flash']='Deposit approved and credited.'; header('Location:?view=admin'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Error: '.$e->getMessage(); header('Location:?view=admin'); exit;
        }
    }

    /* ========== Invest handler ========== */
    if (($_POST['action'] ?? '') === 'invest') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=home'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        $product_id = intval($_POST['product_id'] ?? 0);
        if (!$product_id) { $_SESSION['flash']='Invalid product'; header('Location:?view=home'); exit; }
        try {
            $pdo->beginTransaction();
            $pstmt = $pdo->prepare("SELECT * FROM invest_products WHERE id=? AND is_active=1 FOR UPDATE"); $pstmt->execute([$product_id]); $prod = $pstmt->fetch();
            if (!$prod) { $pdo->rollBack(); $_SESSION['flash']='Product not available'; header('Location:?view=home'); exit; }
            $uid = $_SESSION['user_id'];
            ensure_wallet($pdo,$uid,'NGN');
            $wq = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE"); $wq->execute([$uid]); $w = $wq->fetch();
            if (!$w) { $pdo->rollBack(); $_SESSION['flash']='Wallet missing'; header('Location:?view=home'); exit; }

            $principal = (float)$prod['principal_amount'];
            $available = (float)$w['balance'] - (float)$w['hold_amount'];
            if ($available < $principal) { $pdo->rollBack(); $_SESSION['flash']='Insufficient available balance to invest'; header('Location:?view=home'); exit; }

            // deduct from balance and add to hold_amount (lock)
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, hold_amount = hold_amount + ? WHERE id = ?")->execute([$principal, $principal, $w['id']]);

            $start = new DateTimeImmutable();
            $mature = $start->modify("+{$prod['duration_days']} days");
            $last_payout = $start->format('Y-m-d H:i:s');
            $pdo->prepare("INSERT INTO investments (user_id,product_id,principal,daily_payout,remaining_days,start_at,mature_at,last_payout_at,status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')")
                ->execute([$uid, $product_id, $principal, $prod['daily_payout'], $prod['duration_days'], $start->format('Y-m-d H:i:s'), $mature->format('Y-m-d H:i:s'), $last_payout]);

            $inv_id = $pdo->lastInsertId();
            // record transactions
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','investment_locked', ?, ?)")->execute([$uid, $principal, json_encode(['inv'=>$inv_id,'product'=>$product_id])]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','investment_created', 0, ?)")->execute([$uid, json_encode(['inv'=>$inv_id,'product'=>$product_id])]);

            $pdo->commit();
            audit($pdo,$uid,'invest_created',['inv'=>$inv_id,'product'=>$product_id,'principal'=>$principal]);
            $_SESSION['flash']="Investment created — ₦".money($principal)." locked as principal for plan {$prod['name']}.";
            header('Location:?view=product'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Invest error: '.$e->getMessage(); header('Location:?view=home'); exit;
        }
    }

    /* ========== Coupon creation by admin ========= */
    if (($_POST['action'] ?? '') === 'admin_create_coupon') {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { $_SESSION['flash']='Admin required'; header('Location:?view=login'); exit; }
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin'); exit; }
        $code = trim($_POST['code'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;
        if ($code === '' || $amount <= 0) { $_SESSION['flash']='Provide coupon code and amount'; header('Location:?view=admin'); exit; }
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code,amount,active,created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$code, $amount, $active, $_SESSION['user_id']]);
            $_SESSION['flash']='Coupon created';
            header('Location:?view=admin'); exit;
        } catch (Exception $e) {
            $_SESSION['flash']='Coupon create error: '.$e->getMessage(); header('Location:?view=admin'); exit;
        }
    }

    /* ========== Coupon redemption by user ========= */
    if (($_POST['action'] ?? '') === 'coupon_redeem') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=coupon'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        $code = trim($_POST['coupon_code'] ?? '');
        if ($code === '') { $_SESSION['flash']='Enter a coupon code'; header('Location:?view=coupon'); exit; }
        try {
            $pdo->beginTransaction();
            $c = $pdo->prepare("SELECT * FROM coupons WHERE code=? FOR UPDATE"); $c->execute([$code]); $coupon = $c->fetch();
            if (!$coupon) { $pdo->rollBack(); $_SESSION['flash']='Invalid coupon code'; header('Location:?view=coupon'); exit; }
            if (!$coupon['active']) { $pdo->rollBack(); $_SESSION['flash']='Coupon is inactive'; header('Location:?view=coupon'); exit; }

            // check if user already redeemed same coupon
            $rr = $pdo->prepare("SELECT COUNT(*) FROM coupon_redemptions WHERE coupon_id=? AND user_id=?")->execute([$coupon['id'], $_SESSION['user_id']]);
            $already = (int)$pdo->prepare("SELECT COUNT(*) as c FROM coupon_redemptions WHERE coupon_id=? AND user_id=?")->execute([$coupon['id'], $_SESSION['user_id']]); // We'll do safe check below
            // simpler approach: check select
            $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM coupon_redemptions WHERE coupon_id=? AND user_id=?");
            $chk->execute([$coupon['id'], $_SESSION['user_id']]); $rrr = $chk->fetch();
            if ($rrr && intval($rrr['c']) > 0) { $pdo->rollBack(); $_SESSION['flash']='You already redeemed this coupon'; header('Location:?view=coupon'); exit; }

            // credit wallet
            ensure_wallet($pdo, $_SESSION['user_id'], 'NGN');
            $wq = $pdo->prepare("SELECT id FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE"); $wq->execute([$_SESSION['user_id']]); $w = $wq->fetch();
            if (!$w) { $pdo->rollBack(); $_SESSION['flash']='Wallet missing'; header('Location:?view=coupon'); exit; }

            $amount = (float)$coupon['amount'];
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$amount, $w['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','coupon_redeemed', ?, ?)")->execute([$_SESSION['user_id'], $amount, json_encode(['coupon_id'=>$coupon['id'],'code'=>$coupon['code']])]);
            $pdo->prepare("INSERT INTO coupon_redemptions (coupon_id,user_id,amount) VALUES (?, ?, ?)")->execute([$coupon['id'], $_SESSION['user_id'], $amount]);
            $pdo->commit();
            send_email(ADMIN_EMAIL, "Coupon redeemed by user #{$_SESSION['user_id']}", "User {$_SESSION['user_id']} redeemed coupon {$coupon['code']} for ₦".number_format($amount,2));
            $_SESSION['flash']="Coupon redeemed. ₦".money($amount)." credited.";
            header('Location:?view=transactions'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Coupon error: '.$e->getMessage(); header('Location:?view=coupon'); exit;
        }
    }

    /* ========== Check-in (daily reward) ========= */
    if (($_POST['action'] ?? '') === 'checkin') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=home'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        $uid = $_SESSION['user_id'];
        try {
            $pdo->beginTransaction();
            // get last checkin
            $last = $pdo->prepare("SELECT * FROM checkins WHERE user_id=? ORDER BY checkin_at DESC LIMIT 1 FOR UPDATE"); $last->execute([$uid]); $l = $last->fetch();
            $now = new DateTimeImmutable();
            $reward = 0.0; $consec = 1;
            if ($l) {
                $prev = new DateTimeImmutable($l['checkin_at']);
                $diff = $now->getTimestamp() - $prev->getTimestamp();
                if ($diff < 24*3600) {
                    $pdo->rollBack(); $_SESSION['flash']='You have already checked in within the last 24 hours'; header('Location:?view=home'); exit;
                } elseif ($diff < 48*3600) {
                    $reward = $GLOBALS['CHECKIN_REWARDS']['48h'];
                    $consec = intval($l['consecutive_days']) + 1;
                } elseif ($diff < 72*3600) {
                    $reward = $GLOBALS['CHECKIN_REWARDS']['72h'];
                    $consec = intval($l['consecutive_days']) + 1;
                } else {
                    // break in streak -> treat as new (24h reward)
                    $reward = $GLOBALS['CHECKIN_REWARDS']['24h'];
                    $consec = 1;
                }
            } else {
                $reward = $GLOBALS['CHECKIN_REWARDS']['24h'];
                $consec = 1;
            }

            ensure_wallet($pdo,$uid,'NGN');
            $wq = $pdo->prepare("SELECT id FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE"); $wq->execute([$uid]); $w = $wq->fetch();
            if (!$w) { $pdo->rollBack(); $_SESSION['flash']='Wallet missing'; header('Location:?view=home'); exit; }

            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$reward, $w['id']]);
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','checkin_reward', ?, ?)")->execute([$uid,$reward,json_encode(['consecutive_days'=>$consec])]);
            $pdo->prepare("INSERT INTO checkins (user_id,checkin_at,reward,consecutive_days) VALUES (?, NOW(), ?, ?)")->execute([$uid,$reward,$consec]);
            $pdo->commit();
            $_SESSION['flash']="Checked in — ₦".money($reward)." credited. (Consecutive days: {$consec})";
            header('Location:?view=home'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Check-in error: '.$e->getMessage(); header('Location:?view=home'); exit;
        }
    }

    // Invite send
    if (($_POST['action'] ?? '') === 'invite_send') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=team'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        $to = trim($_POST['invite_email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $_SESSION['flash']='Enter a valid email to invite'; header('Location:?view=team'); exit; }
        $uid = $_SESSION['user_id'];
        $invite_code = 'INV' . str_pad($uid,5,'0',STR_PAD_LEFT);
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . "/?view=register&ref=" . urlencode($invite_code);
        send_email($to, "You're invited to InvestDemo", "User {$_SESSION['username']} invited you. Use code <strong>{$invite_code}</strong> or register via <a href=\"{$link}\">this link</a>.");
        audit($pdo,$_SESSION['user_id'],'invite_sent',['to'=>$to,'code'=>$invite_code]);
        $_SESSION['flash']="Invite sent to {$to}.";
        header('Location:?view=team'); exit;
    }
}

/* ========== Data for rendering ========== */
$logged_in = !empty($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

/* Ensure user wallet exists when logged in */
if ($logged_in) { ensure_wallet($pdo, $uid, 'NGN'); $stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id=? AND currency='NGN'"); $stmt->execute([$uid]); $w = $stmt->fetch(); }
$wallet = $logged_in && !empty($w) ? $w : ['balance'=>0,'hold_amount'=>0];
$wallet['available'] = (float)$wallet['balance'] - (float)$wallet['hold_amount'];

$plans = $pdo->query("SELECT * FROM invest_products WHERE is_active=1 ORDER BY principal_amount ASC")->fetchAll();
$my_investments = $logged_in ? $pdo->prepare("SELECT inv.*, p.name as product_name FROM investments inv JOIN invest_products p ON inv.product_id = p.id WHERE inv.user_id=? ORDER BY inv.created_at DESC") : null;
if ($my_investments) { $my_investments->execute([$uid]); $my_investments = $my_investments->fetchAll(); } else $my_investments = [];

$recent_tx = $logged_in ? $pdo->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20") : null;
if ($recent_tx) { $recent_tx->execute([$uid]); $recent_tx = $recent_tx->fetchAll(); } else $recent_tx = [];

$pending_deposits = ($role === 'admin') ? $pdo->query("SELECT dr.*, u.username FROM deposit_requests dr JOIN users u ON dr.user_id = u.id WHERE dr.status='pending' ORDER BY dr.created_at ASC")->fetchAll() : [] ;
$pending_withdraws = ($role === 'admin') ? $pdo->query("SELECT wr.*, u.username FROM withdraw_requests wr JOIN users u ON wr.user_id = u.id WHERE wr.status='pending' ORDER BY wr.created_at ASC")->fetchAll() : [] ;
$coupons_admin = ($role === 'admin') ? $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll() : [];
$deposit_presets = [];
for ($i=0; $i<=9; $i++) { $deposit_presets[] = 2000 * (2**$i); } // 2000..1,024,000

/* ========== RENDER HTML (same layout but with added buttons/UI) ========== */
?><!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>pay-go investment</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--accent:#2563eb;--muted:#6b7280}
*{box-sizing:border-box}
body{font-family:Inter, system-ui, Arial, sans-serif;background:var(--bg);margin:0;color:#0f172a}
.header{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;background:#0b1220;color:#fff}
.brand{font-weight:700}
.nav a{color:#fff;margin-left:12px;text-decoration:none;font-size:14px}
.container{max-width:1100px;margin:20px auto;padding:12px}
.card{background:var(--card);border-radius:10px;padding:16px;box-shadow:0 8px 30px rgba(111, 190, 59, 0.06)}
.btn{padding:8px 14px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}
.small{font-size:13px;color:#64748b}
.grid{display:grid;grid-template-columns:1fr 360px;gap:18px}
.plan{display:flex;align-items:center;gap:12px;padding:10px;border-radius:8px;border:1px solid #eef2f7;margin-bottom:8px}
.machine{height:120px;border-radius:8px;background:linear-gradient(180deg,#f0f9ff,#fff);display:flex;align-items:center;justify-content:center}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:8px;border-bottom:1px solid #eee;text-align:left}
.footer{max-width:1100px;margin:20px auto;text-align:center;color:var(--muted);font-size:13px}
.countdown{font-weight:700;color:#0b1220}
.input{padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%;margin-top:6px;margin-bottom:10px}
@media (max-width:900px){.grid{grid-template-columns:1fr}}
.kvp{display:flex;justify-content:space-between;gap:8px}
.kv{flex:1}
.preset {display:inline-block;padding:8px 10px;margin:6px;border-radius:8px;border:1px solid #e6edf3;background:#fff;cursor:pointer}
.preset.selected {border-color:#2563eb;background:#f0f9ff}
.bankbox {background:#fff;border:1px solid #eef2f7;padding:12px;border-radius:8px}
</style>
</head>
<body>
  <header class="header">
    <div class="brand">Invest</div>
    <nav>
      <a href="?view=home" class="nav">Home</a>
      <a href="?view=product" class="nav">Product</a>
      <a href="?view=team" class="nav">Team</a>
      <a href="?view=profile" class="nav">Profile</a>
      <a href="?view=transactions" class="nav">Transactions</a>
      <a href="?view=coupon" class="nav">Coupon</a>
      <a href="?view=checkin" class="nav">Check-in</a>
      <?php if ($role === 'admin'): ?><a href="?view=admin" class="nav">Admin</a><?php endif; ?>
      <?php if ($logged_in): ?>
        <form method="post" style="display:inline;margin-left:10px"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="logout"><button class="btn">Logout</button></form>
      <?php else: ?>
        <a href="?view=login" style="color:#fff;margin-left:6px">Login</a>
        <a href="?view=register" style="color:#fff;margin-left:6px">Sign up</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">
    <?php if(!empty($flash)): ?><div class="card small"><?=htmlspecialchars($flash)?></div><?php endif; ?>

    <?php if ($view === 'profile'): ?>
      <?php require_login(); ?>
      <div class="grid">
        <section class="card">
          <h2>Profile</h2>
          <div class="small">Username: <?=htmlspecialchars($_SESSION['username'] ?? '')?></div>
          <div style="margin-top:6px"><strong>Balance</strong> <div class="small">Balance: ₦ <?=money($wallet['balance'])?></div>

          <h3 style="margin-top:12px">Upload KYC</h3>
          <p class="small">Upload ID (PNG, JPG, PDF). Files are scanned and stored (demo).</p>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
            <input type="hidden" name="action" value="kyc_upload">
            <input type="file" name="kyc_doc" required>
            <div style="margin-top:8px"><button class="btn">Upload KYC</button></div>
          </form>

          <h3 style="margin-top:12px">Security</h3>
          <div class="small">(Change password & TOTP setup UI are TODO — see security checklist)</div>
        </section>

        <aside class="card">
          <h4>Quick Links</h4>
          <div class="small"><a href="?view=deposit">Deposit</a> · <a href="?view=withdraw">Withdraw</a> · <a href="?view=product">My Products</a></div>
          <h4 style="margin-top:12px">Recent Activity</h4>
          <div style="max-height:220px;overflow:auto">
            <?php foreach ($recent_tx as $tx): ?>
              <div style="padding:6px;border-bottom:1px dashed #eee"><div class="small"><strong><?=htmlspecialchars($tx['type'])?></strong> <?=($tx['amount']>=0?'+':'')?><?=money($tx['amount'])?></div><div class="small" style="color:#94a3b8"><?=htmlspecialchars($tx['created_at'])?></div></div>
            <?php endforeach; ?>
          </div>
        </aside>
      </div>

    <?php elseif ($view === 'product'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>My Products / Investments</h2>
        <?php if (empty($my_investments)): ?><div class="small">No investments yet.</div><?php else: ?>
          <table class="table"><thead><tr><th>Start</th><th>Plan</th><th>Principal</th><th>Daily</th><th>Remaining</th><th>Matures at</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($my_investments as $inv):
              $mature = new DateTimeImmutable($inv['mature_at']);
              $now = new DateTimeImmutable();
              $remaining_seconds = max(0, $mature->getTimestamp() - $now->getTimestamp());
              $countdownId = 'cd_'.$inv['id'];
          ?>
            <tr>
              <td class="small"><?=htmlspecialchars($inv['created_at'])?></td>
              <td><?=htmlspecialchars($inv['product_name'])?></td>
              <td>₦ <?=money($inv['principal'])?></td>
              <td>₦ <?=money($inv['daily_payout'])?></td>
              <td><?=intval($inv['remaining_days'])?> days</td>
              <td><?=htmlspecialchars($inv['mature_at'])?></td>
              <td><?=htmlspecialchars($inv['status'])?> <div class="countdown" id="<?=htmlspecialchars($countdownId)?>" data-seconds="<?=intval($remaining_seconds)?>"></div></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table>
        <?php endif; ?>
      </div>

    <?php elseif ($view === 'deposit'): ?>
      <?php require_login(); ?>
      <?php $step = $_GET['step'] ?? 'enter'; ?>
      <div class="card">
        <h2>Deposit (NGN)</h2>

        <div class="bankbox">
          <div class="small"><strong>Bank transfer details (pay into):</strong></div>
          <div style="margin-top:6px"><strong>Bank:</strong> <?=htmlspecialchars(DEPOSIT_BANK_NAME)?></div>
          <div><strong>Account name:</strong> <?=htmlspecialchars(DEPOSIT_ACCOUNT_NAME)?></div>
          <div><strong>Account number:</strong> <?=htmlspecialchars(DEPOSIT_ACCOUNT_NUMBER)?></div>
        </div>

        <?php if ($step === 'enter'): ?>
          <p class="small" style="margin-top:10px">Choose a preset amount (or enter custom). Minimum ₦<?=number_format(MIN_DEPOSIT,2)?>. After you transfer, click <em>I have paid</em> to notify admin (admin must approve to credit your wallet).</p>

          <form method="post" id="depositForm" style="margin-top:12px">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
            <input type="hidden" name="action" value="deposit_start">
            <input type="hidden" name="preset_amount" id="preset_amount" value="0">
            <label class="small">Sender's name (name on the bank transfer)
              <input name="sender_name" id="sender_name" required class="input" placeholder="Name used when making the transfer (e.g. John Doe)">
            </label>

            <div class="small">Presets</div>
            <div id="presets">
              <?php foreach ($deposit_presets as $amt): ?>
                <div class="preset" data-amt="<?=intval($amt)?>" role="button">₦ <?=number_format($amt,0)?></div>
              <?php endforeach; ?>
            </div>

            <label class="small" style="display:block;margin-top:8px">Or enter custom amount
              <input type="number" name="custom_amount" id="custom_amount" step="0.01" class="input" placeholder="₦">
            </label>

            <?php $suggested_ref = generate_payment_ref($uid); ?>
            <label class="small">Payment reference (include this in your bank transfer; you may edit)
              <input name="payment_ref" id="payment_ref" class="input" value="<?=htmlspecialchars($suggested_ref)?>">
              <div class="small">Example reference: <?=htmlspecialchars($suggested_ref)?> — include in your transfer description so admin can match it.</div>
            </label>

            <div style="margin-top:10px;display:flex;gap:8px">
              <button class="btn" type="submit">Continue</button>
              <a class="btn" style="background:#6b7280;text-decoration:none" href="?view=deposit">Refresh</a>
            </div>
            <div class="small" style="margin-top:8px">After confirming, your deposit will appear as pending and admin will approve the credit.</div>
          </form>

        <?php elseif ($step === 'confirm' && !empty($_SESSION['deposit_amount'])): ?>
          <div style="margin-top:12px">
            <p><strong>Send to:</strong></p>
            <p>Account name: <strong><?=htmlspecialchars(DEPOSIT_ACCOUNT_NAME)?></strong><br>
            Bank: <strong><?=htmlspecialchars(DEPOSIT_BANK_NAME)?></strong><br>
            Account number: <strong><?=htmlspecialchars(DEPOSIT_ACCOUNT_NUMBER)?></strong></p>
            <p class="small">Amount: ₦<?=number_format($_SESSION['deposit_amount'],2)?></p>
            <p class="small">Payment reference to include: <strong><?=htmlspecialchars($_SESSION['deposit_payment_ref'] ?? $suggested_ref)?></strong></p>
            <p class="small">Sender name recorded: <strong><?=htmlspecialchars($_SESSION['deposit_sender_name'] ?? '')?></strong></p>
          </div>
          <form method="post" style="margin-top:10px">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
            <input type="hidden" name="action" value="deposit_confirm">
            <button class="btn">I have paid — Notify admin</button>
          </form>
        <?php else: ?>
          <p class="small">Start a deposit.</p>
        <?php endif; ?>
      </div>

    <?php elseif ($view === 'transactions'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>Your transactions</h2>
        <table class="table"><thead><tr><th>Time</th><th>Type</th><th>Amount</th><th>Currency</th><th>Meta</th></tr></thead><tbody>
        <?php
          $tq = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 200");
          $tq->execute([$uid]); $txs = $tq->fetchAll();
          foreach ($txs as $tx):
        ?>
          <tr>
            <td class="small"><?=htmlspecialchars($tx['created_at'])?></td>
            <td><?=htmlspecialchars($tx['type'])?></td>
            <td><?=money($tx['amount'])?></td>
            <td><?=htmlspecialchars($tx['currency'])?></td>
            <td class="small"><?=htmlspecialchars(is_string($tx['meta']) ? $tx['meta'] : json_encode($tx['meta']))?></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table>
      </div>

    <?php elseif ($view === 'coupon'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>Submit giftc/coupon code</h2>
        <p class="small">Paste the coupon/gift code you received. Admin will validate and broadcast to support channels (Telegram/WhatsApp) as required. Or redeem admin-created coupons directly.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
          <input type="hidden" name="action" value="coupon_redeem">
          <label class="small">Coupon code <input name="coupon_code" required class="input" placeholder="Enter gift code"></label>
          <button class="btn">Redeem coupon</button>
        </form>
      </div>

    <?php elseif ($view === 'team'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>Your invite code & link</h2>
        <?php
          $invite_code = 'INV' . str_pad($uid ?? 0,5,'0',STR_PAD_LEFT);
          $invite_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . "/?view=register&ref=" . urlencode($invite_code);
        ?>
        <p class="small">Share your invite code or link. New users can register with your code. Use the form to send an invite by email (demo).</p>
        <div style="margin-bottom:8px"><strong>Code:</strong> <?=$invite_code?></div>
        <div style="margin-bottom:8px"><strong>Link:</strong> <a href="<?=htmlspecialchars($invite_link)?>" target="_blank"><?=htmlspecialchars($invite_link)?></a></div>

        <h4 style="margin-top:12px">Invite by email</h4>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
          <input type="hidden" name="action" value="invite_send">
          <label class="small">Email <input name="invite_email" required class="input" placeholder="friend@example.com"></label>
          <button class="btn">Send invite</button>
        </form>
      </div>

    <?php elseif ($view === 'checkin'): ?>
      <?php require_login(); ?>
      <div class="card">
        <h2>Check-in</h2>
        <p class="small">Check in to receive small rewards. Streaks produce larger rewards.</p>
        <form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="checkin"><button class="btn">Check-in Now</button></form>
        <div style="margin-top:12px" class="small">Rewards: 24h <?=money($CHECKIN_REWARDS['24h'])?> · 48h <?=money($CHECKIN_REWARDS['48h'])?> · 72h+ <?=money($CHECKIN_REWARDS['72h'])?></div>
      </div>

    <?php elseif ($view === 'admin' && $role === 'admin'): ?>
      <div class="grid">
        <div>
          <div class="card">
            <h2>Admin — Pending Deposits</h2>
            <?php if (empty($pending_deposits)): ?><p class="small">No pending deposits</p><?php else: ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                <input type="hidden" name="action" value="admin_approve_deposit">
                <?php foreach ($pending_deposits as $r): ?>
                  <div style="padding:8px;border-bottom:1px dashed #eee"><label><input type="radio" name="deposit_id" value="<?=intval($r['id'])?>"> #<?=intval($r['id'])?> — <?=htmlspecialchars($r['username'])?> ₦<?=money($r['amount'])?> (<?=htmlspecialchars($r['created_at'])?>)</label></div>
                <?php endforeach; ?>
                <button class="btn">Approve selected</button>
              </form>
            <?php endif; ?>
          </div>

          <div class="card" style="margin-top:12px">
            <h3>Create Coupon</h3>
            <form method="post">
              <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
              <input type="hidden" name="action" value="admin_create_coupon">
              <label class="small">Code <input name="code" class="input" required></label>
              <label class="small">Amount <input name="amount" type="number" step="0.01" class="input" required></label>
              <label class="small"><input type="checkbox" name="active" checked> Active</label>
              <button class="btn">Create coupon</button>
            </form>
            <h4 style="margin-top:12px">Existing coupons</h4>
            <table class="table"><thead><tr><th>Code</th><th>Amt</th><th>Active</th></tr></thead><tbody>
            <?php foreach ($coupons_admin as $c): ?>
              <tr><td><?=htmlspecialchars($c['code'])?></td><td>₦<?=money($c['amount'])?></td><td><?= $c['active'] ? 'Yes' : 'No' ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
          </div>
        </div>

        <aside class="card">
          <h3>Admin Info</h3>
          <p class="small">Admin account: <?=htmlspecialchars(ADMIN_EMAIL)?> (pw: Admin123!)</p>
          <p class="small">Deposit account: <?=DEPOSIT_ACCOUNT_NUMBER?> — <?=DEPOSIT_BANK_NAME?> — <?=DEPOSIT_ACCOUNT_NAME?></p>
        </aside>
      </div>

    <?php else: /* HOME & auth pages (register/login/forgot/reset) */ ?>
      <?php if ($view === 'register'): ?>
        <div class="card"><h2>Sign up</h2>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
            <input type="hidden" name="action" value="register">
            <label class="small">Username <input name="username" required class="input"></label>
            <label class="small">Email <input name="email" type="email" required class="input"></label>
            <label class="small">Password <input name="password" type="password" required class="input"></label>
            <label class="small">Secret code (4 digits) <input name="secret_code" pattern="\d{4}" maxlength="4" required class="input"></label>
            <button class="btn" style="margin-top:8px">Create account (Get ₦<?=number_format(WELCOME_BONUS,2)?> held)</button>
          </form>
        </div>

      <?php elseif ($view === 'login'): ?>
        <div class="card"><h2>Login</h2>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
            <input type="hidden" name="action" value="login">
            <label class="small">Email <input name="email" type="email" required class="input"></label>
            <label class="small">Password <input name="password" type="password" required class="input"></label>
            <button class="btn">Login</button>
          </form>
          <div style="margin-top:8px" class="small">Forgot password? <a href="?view=forgot">Reset</a></div>
        </div>

      <?php elseif ($view === 'forgot'): ?>
        <div class="card"><h2>Forgot password</h2>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
            <input type="hidden" name="action" value="forgot_request">
            <label class="small">Email <input name="email" type="email" required class="input"></label>
            <button class="btn">Send reset code</button>
          </form>
        </div>

      <?php elseif ($view === 'reset'): ?>
        <div class="card"><h2>Reset password</h2>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
            <input type="hidden" name="action" value="password_reset">
            <label class="small">Email <input name="email" type="email" required class="input"></label>
            <label class="small">Reset code (4 digits sent to email) OR your secret code <input name="code" pattern="\d{4}" maxlength="4" required class="input"></label>
            <label class="small">New password <input name="new_password" type="password" required class="input"></label>
            <label class="small">Confirm new password <input name="new_password2" type="password" required class="input"></label>
            <button class="btn">Reset password</button>
          </form>
        </div>

      <?php else: ?>
        <!-- HOME -->
        <div class="grid">
          <section class="card">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div class="small">Available balance (NGN)</div>
                <div style="font-weight:800;font-size:22px">₦ <?=money($wallet['balance'])?></div>
                <div class="small">Held: ₦ <?=money($wallet['hold_amount'])?></div>
              </div>
              <div style="text-align:right">
                <a class="btn" href="?view=deposit">Deposit</a>
                <a class="btn" href="withdraw.php" style="background:#6b7280">Withdraw</a>
              </div>
            </div>

            <div class="machine" style="margin-top:12px">
              <div style="text-align:center">
                <div style="font-weight:700">Machine</div>
                <div class="small">Pick a plan and invest — principal will be locked for the product duration and daily payout credited.</div>
              </div>
            </div>

            <h3 style="margin-top:12px">Investment Plans</h3>
            <?php foreach ($plans as $p): ?>
              <div class="plan">
                <div style="min-width:140px"><strong>₦ <?=number_format($p['principal_amount'],0)?></strong></div>
                <div style="flex:1">
                  <div style="font-weight:700"><?=htmlspecialchars($p['name'])?></div>
                  <div class="small">Daily payout: ₦ <?=money($p['daily_payout'])?> — Duration: <?=intval($p['duration_days'])?> days</div>
                </div>
                <div>
                  <form method="post">
                    <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                    <input type="hidden" name="action" value="invest">
                    <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
                    <button class="btn">Invest</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </section>

          <aside class="card">
            <h4>Home Links</h4>
            <div class="small"><a href="?view=deposit">Deposit</a> · <a href="withdraw.php">Withdraw</a> · <a href="?view=team">Support</a> · <a href="?view=coupon">Coupon</a></div>

            <h4 style="margin-top:12px">Recent Activity</h4>
            <div style="max-height:220px;overflow:auto">
              <?php foreach ($recent_tx as $tx): ?>
                <div style="padding:6px;border-bottom:1px dashed #eee"><div class="small"><strong><?=htmlspecialchars($tx['type'])?></strong> <?=($tx['amount']>=0?'+':'')?><?=money($tx['amount'])?></div><div class="small" style="color:#94a3b8"><?=htmlspecialchars($tx['created_at'])?></div></div>
              <?php endforeach; ?>
            </div>
          </aside>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="footer">Trading platform<strong><?=htmlspecialchars($DB_NAME)?></strong> — For Investment only.</div>
  </main>

  <script>
    // Countdown timers for investments
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('[id^="cd_"]').forEach(function(el){
        let seconds = parseInt(el.dataset.seconds || '0', 10);
        function tick(){
          if (seconds <= 0) { el.textContent = 'Matured'; return; }
          let d = Math.floor(seconds / 86400); let h = Math.floor((seconds % 86400) / 3600);
          let m = Math.floor((seconds % 3600) / 60); let s = seconds % 60;
          el.textContent = d + 'd ' + String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
          seconds--; setTimeout(tick, 1000);
        }
        tick();
      });

      // Deposit preset selection logic
      var presets = document.querySelectorAll('.preset');
      var presetInput = document.getElementById('preset_amount');
      var customInput = document.getElementById('custom_amount');

      if (presets && presetInput) {
        presets.forEach(function(el){
          el.addEventListener('click', function(){
            presets.forEach(function(p){ p.classList.remove('selected'); });
            el.classList.add('selected');
            presetInput.value = el.dataset.amt || '0';
            if (customInput) customInput.value = '';
          });
        });
        if (customInput) {
          customInput.addEventListener('input', function(){
            if (customInput.value && customInput.value.trim() !== '') {
              presets.forEach(function(p){ p.classList.remove('selected'); });
              presetInput.value = '0';
            }
          });
        }
      }
    });
  </script>
</body>
</html>
