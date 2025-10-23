<?php
/**
 * index.php — Single-file demo investment platform (updated)
 *
 * Adds: coupon create/activate/deactivate/delete (admin), coupon redeem (user)
 *
 * WARNING: Demo only. Do NOT use as-is in production handling real funds.
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

/* ========== SESSIONS (stronger flags recommended on HTTPS) ========== */
session_set_cookie_params([
    'lifetime'=>0,
    'path'=>'/',
    'domain'=>'',
    'secure'=>false, // set to true when using HTTPS
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

/* ========== CREATE TABLES (including coupons, rate_limits, secret code, password_resets) ========== */
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

/* new coupons table */
"CREATE TABLE IF NOT EXISTS coupons (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(100) NOT NULL UNIQUE,
  value DECIMAL(28,8) NOT NULL,
  created_by INT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  used_by INT NULL,
  used_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_coupons_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_coupons_usedby FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($schemas as $sql) {
    $pdo->exec($sql);
}

/* ========== SEED ADMIN + 10 PLANS (balances zero) ========== */
$adminCheck = $pdo->prepare("SELECT id FROM users WHERE role='admin' LIMIT 1"); $adminCheck->execute();
if (!$adminCheck->fetch()) {
    $hash = password_hash('Admin123!', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username,email,password,role,welcome_released) VALUES ('owner', ?, ?, 'admin', 1)")
        ->execute([ADMIN_EMAIL, $hash]);
    $aid = $pdo->lastInsertId();
    // ensure admin wallet exists with zero balance
    $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, 'NGN', 0, 0)")->execute([$aid]);
}

/* seed investment products */
$count = (int)$pdo->query("SELECT COUNT(*) FROM invest_products")->fetchColumn();
if ($count === 0) {
    $ins = $pdo->prepare("INSERT INTO invest_products (name,principal_amount,duration_days,daily_payout) VALUES (?, ?, ?, ?)");
    $basePrincipal = 2000;
    $baseDaily = 700; // as requested
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
function audit($pdo,$uid,$event,$meta=null){ $ip = $_SERVER['REMOTE_ADDR'] ?? null; $pdo->prepare("INSERT INTO audit_logs (user_id,event,meta,ip) VALUES (?,?,?,?)")->execute([$uid,$event,json_encode($meta),$ip]); }

/* generate a per-user deposit/payment reference */
function generate_payment_ref($uid) {
    try {
        $rand = bin2hex(random_bytes(3));
    } catch (Exception $e) {
        $rand = substr(hash('sha256', microtime(true)),0,6);
    }
    return 'mav112v' . intval($uid) . 'v' . $rand;
}

/* ========== RATE LIMITER (DB-backed) ========== */
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

/* ========== EMAIL (PHPMailer if available) ========== */
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
        // fallback to mail()
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

/* ========== INVESTMENT PROCESSOR (demo: runs on page load) ========== */
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

    // Register (with 4-digit secret code) - unchanged
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

    // Login - unchanged
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

    // Logout - unchanged
    if (($_POST['action'] ?? '') === 'logout') {
        audit($pdo,$_SESSION['user_id'] ?? null,'logout',[]);
        session_unset(); session_destroy(); session_start();
        $_SESSION['flash']='Logged out'; header('Location:?view=login'); exit;
    }

    // KYC upload - unchanged
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
        // send admin notification
        send_email(ADMIN_EMAIL, "KYC uploaded by user #{$_SESSION['user_id']}", "User {$_SESSION['user_id']} uploaded KYC: {$safeName}");
        $_SESSION['flash']='KYC uploaded — admin will review shortly.';
        header('Location:?view=profile'); exit;
    }

    // Deposit start (user creates deposit request via "I have paid")
    if (($_POST['action'] ?? '') === 'deposit_start') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=deposit'); exit; }
        if (!rate_limit_check($pdo, $clientIp, 'deposit_start', 6, 300)) { $_SESSION['flash']='Too many deposit attempts; try later'; header('Location:?view=deposit'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }

        // amount may come from 'preset_amount' or 'custom_amount'
        $preset = (float)($_POST['preset_amount'] ?? 0);
        $custom = isset($_POST['custom_amount']) ? (float)$_POST['custom_amount'] : 0.0;
        $amount = $custom > 0 ? $custom : $preset;
        $sender_name = trim($_POST['sender_name'] ?? '');
        $reference = trim($_POST['payment_ref'] ?? '');

        if ($amount < MIN_DEPOSIT) { $_SESSION['flash']='Minimum deposit is ₦'.number_format(MIN_DEPOSIT,2); header('Location:?view=deposit'); exit; }
        if (!$sender_name) { $_SESSION['flash']='Please provide the name used to send the funds'; header('Location:?view=deposit'); exit; }

        // ensure reference present, generate if missing
        if (empty($reference)) {
            $reference = generate_payment_ref($_SESSION['user_id']);
        }

        try {
            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO deposit_requests (user_id,amount,account_number,bank_name,account_name,status,created_at) VALUES (?,?,?,?,?,'pending',NOW())");
            $ins->execute([$_SESSION['user_id'],$amount,DEPOSIT_ACCOUNT_NUMBER,DEPOSIT_BANK_NAME,DEPOSIT_ACCOUNT_NAME]);
            $id = $pdo->lastInsertId();

            // insert transaction to record the user's confirmation (admin still approves)
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

            // notify admin (demo)
            send_email(ADMIN_EMAIL, "Deposit confirmation #{$id}", "User {$_SESSION['user_id']} says they paid ₦".number_format($amount,2)." with reference {$reference}. Sender name: {$sender_name}");

            audit($pdo,$_SESSION['user_id'],'deposit_request_created',['id'=>$id,'amount'=>$amount,'ref'=>$reference]);
            $_SESSION['flash']="Deposit confirmation recorded. Reference: {$reference}. Admin will review and approve.";
            header('Location:?view=deposit'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Deposit error: '.$e->getMessage(); header('Location:?view=deposit'); exit;
        }
    }

    // Admin approves deposit -> credit wallet and release welcome hold if not released (unchanged)
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

    // Coupon creation (admin)
    if (($_POST['action'] ?? '') === 'admin_create_coupon') {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { $_SESSION['flash']='Admin required'; header('Location:?view=login'); exit; }
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin_coupons'); exit; }
        $code = trim($_POST['coupon_code'] ?? '');
        $value = (float)($_POST['coupon_value'] ?? 0);
        if ($code === '' || $value <= 0) { $_SESSION['flash']='Provide valid coupon code and a positive value'; header('Location:?view=admin_coupons'); exit; }
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, value, created_by, active, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$code, $value, $_SESSION['user_id']]);
            audit($pdo, $_SESSION['user_id'], 'admin_create_coupon', ['code'=>$code, 'value'=>$value]);
            send_email(ADMIN_EMAIL, "Coupon created: {$code}", "Admin created coupon {$code} worth ₦".number_format($value,2));
            $_SESSION['flash']="Coupon {$code} created (₦".number_format($value,2).").";
            header('Location:?view=admin_coupons'); exit;
        } catch (Exception $e) {
            $_SESSION['flash']='Coupon creation error: '.$e->getMessage(); header('Location:?view=admin_coupons'); exit;
        }
    }

    // Admin coupon actions: activate / deactivate / delete
    if (($_POST['action'] ?? '') === 'admin_coupon_action') {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { $_SESSION['flash']='Admin required'; header('Location:?view=login'); exit; }
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=admin_coupons'); exit; }
        $op = $_POST['op'] ?? '';
        $cid = (int)($_POST['coupon_id'] ?? 0);
        if (!$cid || !in_array($op, ['activate','deactivate','delete'])) { $_SESSION['flash']='Invalid action'; header('Location:?view=admin_coupons'); exit; }
        try {
            if ($op === 'delete') {
                $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$cid]);
                audit($pdo, $_SESSION['user_id'], 'admin_delete_coupon', ['coupon_id'=>$cid]);
                $_SESSION['flash']='Coupon deleted.';
            } else {
                $active = $op === 'activate' ? 1 : 0;
                $pdo->prepare("UPDATE coupons SET active = ? WHERE id = ?")->execute([$active, $cid]);
                audit($pdo, $_SESSION['user_id'], 'admin_toggle_coupon', ['coupon_id'=>$cid,'active'=>$active]);
                $_SESSION['flash']=$op === 'activate' ? 'Coupon activated.' : 'Coupon deactivated.';
            }
            header('Location:?view=admin_coupons'); exit;
        } catch (Exception $e) {
            $_SESSION['flash']='Coupon op error: '.$e->getMessage(); header('Location:?view=admin_coupons'); exit;
        }
    }

    // Coupon redemption (user) — now redeems and credits wallet
    if (($_POST['action'] ?? '') === 'coupon_redeem') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=coupon'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        $code = trim($_POST['coupon_code'] ?? '');
        if ($code === '') { $_SESSION['flash']='Enter a coupon code'; header('Location:?view=coupon'); exit; }

        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT * FROM coupons WHERE code = ? FOR UPDATE");
            $q->execute([$code]);
            $coupon = $q->fetch();
            if (!$coupon) { $pdo->rollBack(); $_SESSION['flash']='Coupon not found'; header('Location:?view=coupon'); exit; }
            if (intval($coupon['active']) !== 1) { $pdo->rollBack(); $_SESSION['flash']='This coupon is not active'; header('Location:?view=coupon'); exit; }
            if (!empty($coupon['used_by'])) { $pdo->rollBack(); $_SESSION['flash']='This coupon has already been used'; header('Location:?view=coupon'); exit; }

            $uid = $_SESSION['user_id'];
            $value = (float)$coupon['value'];

            // ensure wallet and credit
            ensure_wallet($pdo,$uid,'NGN');
            $wq = $pdo->prepare("SELECT id,balance,hold_amount FROM wallets WHERE user_id=? AND currency='NGN' FOR UPDATE"); $wq->execute([$uid]); $w = $wq->fetch();
            if (!$w) { $pdo->rollBack(); $_SESSION['flash']='User wallet missing'; header('Location:?view=coupon'); exit; }

            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?")->execute([$value, $w['id']]);
            // mark coupon used and deactivate
            $pdo->prepare("UPDATE coupons SET used_by = ?, used_at = NOW(), active = 0 WHERE id = ?")->execute([$uid, $coupon['id']]);
            // log transaction
            $meta = ['coupon_id'=>intval($coupon['id']),'code'=>$coupon['code']];
            $pdo->prepare("INSERT INTO transactions (user_id,currency,type,amount,meta) VALUES (?, 'NGN','coupon_redeemed', ?, ?)")->execute([$uid,$value,json_encode($meta)]);
            $pdo->commit();

            audit($pdo,$uid,'coupon_redeemed',['coupon_id'=>$coupon['id'],'value'=>$value]);
            // notify admin (optional)
            send_email(ADMIN_EMAIL, "Coupon redeemed: {$coupon['code']}", "User {$uid} redeemed coupon {$coupon['code']} for ₦".number_format($value,2).".");
            $_SESSION['flash']="Coupon redeemed: ₦".number_format($value,2)." added to your wallet.";
            header('Location:?view=transactions'); exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash']='Coupon error: '.$e->getMessage(); header('Location:?view=coupon'); exit;
        }
    }

    // Invite by email (team invite)
    if (($_POST['action'] ?? '') === 'invite_send') {
        if (!check_csrf($_POST['csrf'] ?? '')) { $_SESSION['flash']='Invalid CSRF'; header('Location:?view=team'); exit; }
        if (empty($_SESSION['user_id'])) { $_SESSION['flash']='Login required'; header('Location:?view=login'); exit; }
        $to = trim($_POST['invite_email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $_SESSION['flash']='Enter a valid email to invite'; header('Location:?view=team'); exit; }
        $uid = $_SESSION['user_id'];
        $invite_code = 'INV' . str_pad($uid,5,'0',STR_PAD_LEFT);
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . "/?view=register&ref=" . urlencode($invite_code);
        // send email (demo)
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

/* prepare deposit presets */
$deposit_presets = [];
for ($i=0; $i<=9; $i++) {
    $deposit_presets[] = 2000 * (2**$i); // 2000,4000,... upto 2000*512=1,024,000
}

/* ========== RENDER HTML ========== */
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
.card{background:var(--card);border-radius:10px;padding:16px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
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
.badge {display:inline-block;padding:4px 8px;border-radius:6px;background:#f1f5f9;color:#0b1220;font-size:12px}
</style>
</head>
<body>
  <header class="header">
    <div class="brand">InvestDemo</div>
    <nav>
      <a href="?view=home" class="nav">Home</a>
      <a href="?view=product" class="nav">Product</a>
      <a href="?view=team" class="nav">Team</a>
      <a href="?view=profile" class="nav">Profile</a>
      <a href="?view=transactions" class="nav">Transactions</a>
      <a href="?view=coupon" class="nav">Coupon</a>
      <?php if ($role === 'admin'): ?>
        <a href="?view=admin" class="nav">Admin</a>
        <a href="?view=admin_coupons" class="nav">Coupons</a>
      <?php endif; ?>
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
      <?php if (!$logged_in) { header('Location:?view=login'); exit; } ?>
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
          <div class="small"><a href="?view=deposit">Deposit</a> · <a href="withdraw.php">Withdraw</a> · <a href="?view=product">My Products</a></div>
          <h4 style="margin-top:12px">Recent Activity</h4>
          <div style="max-height:220px;overflow:auto">
            <?php foreach ($recent_tx as $tx): ?>
              <div style="padding:6px;border-bottom:1px dashed #eee"><div class="small"><strong><?=htmlspecialchars($tx['type'])?></strong> <?=($tx['amount']>=0?'+':'')?><?=money($tx['amount'])?></div><div class="small" style="color:#94a3b8"><?=htmlspecialchars($tx['created_at'])?></div></div>
            <?php endforeach; ?>
          </div>
        </aside>
      </div>

    <?php elseif ($view === 'product'): ?>
      <?php if (!$logged_in) { header('Location:?view=login'); exit; } ?>
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
      <?php if (!$logged_in) { header('Location:?view=login'); exit; } ?>
      <div class="card">
        <h2>Create Deposit Request / Confirm Payment</h2>

        <div class="bankbox">
          <div class="small"><strong>Bank transfer details (pay into):</strong></div>
          <div style="margin-top:6px"><strong>Bank:</strong> <?=htmlspecialchars(DEPOSIT_BANK_NAME)?></div>
          <div><strong>Account name:</strong> <?=htmlspecialchars(DEPOSIT_ACCOUNT_NAME)?></div>
          <div><strong>Account number:</strong> <?=htmlspecialchars(DEPOSIT_ACCOUNT_NUMBER)?></div>
        </div>

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

          <?php
            // generate a suggested ref for the user to include in transfer
            $suggested_ref = $logged_in ? generate_payment_ref($uid) : '';
          ?>
          <label class="small">Payment reference (include this in your bank transfer; you may edit)
            <input name="payment_ref" id="payment_ref" class="input" value="<?=htmlspecialchars($suggested_ref)?>">
            <div class="small">Example reference: <?=htmlspecialchars($suggested_ref)?> — include in your transfer description so admin can match it.</div>
          </label>

          <div style="margin-top:10px;display:flex;gap:8px">
            <button class="btn" type="submit">I have paid — Confirm</button>
            <a class="btn" style="background:#6b7280;text-decoration:none" href="?view=deposit">Refresh</a>
          </div>
          <div class="small" style="margin-top:8px">After confirming, your deposit will appear as pending and admin will approve the credit.</div>
        </form>
      </div>

    <?php elseif ($view === 'transactions'): ?>
      <?php if (!$logged_in) { header('Location:?view=login'); exit; } ?>
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
      <?php if (!$logged_in) { header('Location:?view=login'); exit; } ?>
      <div class="card">
        <h2>Submit gift/coupon code</h2>
        <p class="small">Paste the coupon/gift code you received. Valid codes will be redeemed immediately and credited to your wallet.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
          <input type="hidden" name="action" value="coupon_redeem">
          <label class="small">Coupon code <input name="coupon_code" required class="input" placeholder="Enter gift code"></label>
          <button class="btn">Redeem coupon</button>
        </form>
      </div>

    <?php elseif ($view === 'team'): ?>
      <?php if (!$logged_in) { header('Location:?view=login'); exit; } ?>
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
            <h3>Pending Withdraws</h3>
            <?php if (empty($pending_withdraws)): ?><p class="small">No pending withdraws</p><?php else: ?>
              <table class="table"><thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Action</th></tr></thead><tbody>
              <?php foreach ($pending_withdraws as $wr): ?>
                <tr><td><?=intval($wr['id'])?></td><td><?=htmlspecialchars($wr['username'])?></td><td>₦<?=money($wr['amount'])?></td>
                  <td>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                      <input type="hidden" name="action" value="admin_process_withdraw">
                      <input type="hidden" name="withdraw_id" value="<?=intval($wr['id'])?>">
                      <button class="btn">Process</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody></table>
            <?php endif; ?>
          </div>
        </div>

        <aside class="card">
          <h3>Admin Info</h3>
          <p class="small">Admin account: <?=htmlspecialchars(ADMIN_EMAIL)?> (pw: Admin123!)</p>
          <p class="small">Deposit account: <?=DEPOSIT_ACCOUNT_NUMBER?> — <?=DEPOSIT_BANK_NAME?> — <?=DEPOSIT_ACCOUNT_NAME?></p>
        </aside>
      </div>

    <?php elseif ($view === 'admin_coupons' && $role === 'admin'): ?>
      <div class="card">
        <h2>Admin — Coupons</h2>

        <div style="display:flex;gap:16px;align-items:flex-start">
          <div style="flex:1">
            <h3>Create Coupon Code</h3>
            <form method="post">
              <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
              <input type="hidden" name="action" value="admin_create_coupon">
              <label>Coupon code <input name="coupon_code" required class="input" placeholder="e.g. GIFT2025"></label>
              <label>Value (NGN) <input name="coupon_value" type="number" min="1" step="0.01" required class="input"></label>
              <div><button class="btn">Create Coupon</button></div>
            </form>
          </div>

          <div style="flex:1">
            <h3>Active / Recent Coupons</h3>
            <table class="table"><thead><tr><th>Code</th><th>Value</th><th>Status</th><th>Used by</th><th>Used at</th><th>Action</th></tr></thead>
            <tbody>
            <?php
              $coupons = $pdo->query("SELECT c.*, u.username as creator_name, uu.username as used_by_name FROM coupons c LEFT JOIN users u ON c.created_by=u.id LEFT JOIN users uu ON c.used_by=uu.id ORDER BY c.created_at DESC LIMIT 100")->fetchAll();
              foreach ($coupons as $c):
            ?>
              <tr>
                <td><?=htmlspecialchars($c['code'])?></td>
                <td>₦ <?=money($c['value'])?></td>
                <td><?= $c['active'] ? '<span class="badge">active</span>' : '<span class="badge" style="background:#fde68a">inactive</span>' ?></td>
                <td><?= $c['used_by'] ? htmlspecialchars($c['used_by_name']) . " (".intval($c['used_by']).")" : '' ?></td>
                <td><?= htmlspecialchars($c['used_at']) ?></td>
                <td>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                    <input type="hidden" name="action" value="admin_coupon_action">
                    <input type="hidden" name="coupon_id" value="<?=intval($c['id'])?>">
                    <?php if ($c['active']): ?>
                      <input type="hidden" name="op" value="deactivate">
                      <button class="btn" type="submit">Deactivate</button>
                    <?php else: ?>
                      <input type="hidden" name="op" value="activate">
                      <button class="btn" type="submit">Activate</button>
                    <?php endif; ?>
                  </form>
                  <form method="post" style="display:inline;margin-left:8px" onsubmit="return confirm('Delete coupon?');">
                    <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
                    <input type="hidden" name="action" value="admin_coupon_action">
                    <input type="hidden" name="coupon_id" value="<?=intval($c['id'])?>">
                    <input type="hidden" name="op" value="delete">
                    <button class="btn" style="background:#ef4444">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody></table>
          </div>
        </div>
      </div>

    <?php else: /* HOME & others (including register/login/forgot/reset) */ ?>
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
          <div class="small" style="margin-top:8px">Or use your 4-digit secret code to reset at <a href="?view=reset">Reset</a>.</div>
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
        <!-- HOME / Landing -->
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
                <a class="btn" href="?view=withdraw" style="background:#6b7280">Withdraw</a>
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

    <div class="footer">Demo platform — DB: <strong><?=htmlspecialchars($DB_NAME)?></strong> — For learning only.</div>
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
      var paymentRef = document.getElementById('payment_ref');

      if (presets && presetInput) {
        presets.forEach(function(el){
          el.addEventListener('click', function(){
            // toggle selection
            presets.forEach(function(p){ p.classList.remove('selected'); });
            el.classList.add('selected');
            presetInput.value = el.dataset.amt || '0';
            if (customInput) customInput.value = '';
          });
        });
        if (customInput) {
          customInput.addEventListener('input', function(){
            // clear presets when custom amount entered
            if (customInput.value && customInput.value.trim() !== '') {
              presets.forEach(function(p){ p.classList.remove('selected'); });
              presetInput.value = '0';
            }
          });
        }
      }

      // small UX: allow clicking suggested ref to copy
      var refEl = document.getElementById('payment_ref');
      if (refEl) {
        refEl.addEventListener('dblclick', function(){
          try { refEl.select(); document.execCommand('copy'); /* user copied */ } catch(e){}
        });
      }
    });
  </script>
</body>
</html>

<?php
/* ===== END OF FILE =====
Notes:
- Admin coupons page: ?view=admin_coupons (admins only). Navbar link is added for admins.
- Admin creates coupons (code + value). Coupons are created active by default.
- When a user redeems a coupon (coupon_redeem post), if coupon exists, active and unused, the coupon value is credited to user's wallet, a transaction coupon_redeemed is created, and coupon marked used and deactivated.
- Admins can activate/deactivate/delete coupons from the admin coupons page.
- This is demo code: add further validation, logging, auditing, throttling and admin UI improvements before any production use.
*/
?>


