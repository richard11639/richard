<?php
// deposit.php
// 3-step deposit flow: enter amount -> show bank details -> confirm -> create deposit_request (pending)
// Requires login. Uses permanent account details requested.

session_start();

// DB config (must match index.php)
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'tradding_db';
date_default_timezone_set('Africa/Lagos');

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB connect error: " . htmlspecialchars($e->getMessage()));
}

// helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}
function check_csrf($t) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$t);
}
function require_login() {
    if (empty($_SESSION['user_id'])) {
        // Redirect to login page within your app
        header('Location: index.php?view=login');
        exit;
    }
}

require_login();
$uid = (int)$_SESSION['user_id'];

$token = csrf_token();

// permanent account details (per your request)
$permanent_account = [
    'account_number' => '7050672951',
    'bank_name' => 'Palmpay',
    'account_name' => 'Ogundele Olayinka Mary'
];

// step handling
// By default show amount entry
$step = $_POST['step'] ?? 'enter_amount';

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Deposit Funds</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Inter, Arial, sans-serif;background:#f4f6f9;margin:0;padding:0}
    .wrap{max-width:520px;margin:60px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
    h2{text-align:center;margin:0 0 14px}
    label{display:block;margin:8px 0;font-size:14px;color:#374151}
    input[type=number], input[type=text], select{width:100%;padding:10px;margin-top:6px;border:1px solid #e6edf3;border-radius:8px}
    button{width:100%;padding:12px;border-radius:8px;border:0;background:#059669;color:#fff;font-weight:600;cursor:pointer;margin-top:10px}
    .bank-details{background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #eef2f6;margin-bottom:12px}
    .muted{color:#6b7280;font-size:13px}
    a.link{display:inline-block;margin-bottom:12px;color:#0b1220;text-decoration:none}
    .flash{padding:10px;border-radius:8px;background:#fff7ed;border-left:4px solid #ffd89b;margin-bottom:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <a class="link" href="index.php">← Back to dashboard</a>
    <h2>Deposit Funds (NGN)</h2>

<?php
// STEP 1: show amount entry
if ($step === 'enter_amount') {
    // clear any previous pending session amount
    unset($_SESSION['deposit_amount']);
    ?>
    <form method="post" novalidate>
      <input type="hidden" name="step" value="show_bank">
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
      <label class="muted">Enter amount (NGN) — minimum ₦100</label>
      <input type="number" name="amount" step="0.01" min="100" required>
      <button type="submit">Continue</button>
    </form>
    <?php
    exit;
}

// STEP 2: show bank details (and store amount in session)
if ($step === 'show_bank') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        echo "<div class='flash'>Invalid request (CSRF).</div>";
        exit;
    }
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount < 100) {
        echo "<div class='flash'>Invalid amount. Minimum is ₦100.</div>";
        exit;
    }
    // save amount to session for confirmation step
    $_SESSION['deposit_amount'] = $amount;
    ?>
    <div class="bank-details">
      <p><strong>Bank Transfer Details (permanent)</strong></p>
      <ul style="margin:0;padding-left:18px">
        <li><b>Account name:</b> <?=htmlspecialchars($permanent_account['account_name'])?></li>
        <li><b>Bank:</b> <?=htmlspecialchars($permanent_account['bank_name'])?></li>
        <li><b>Account number:</b> <?=htmlspecialchars($permanent_account['account_number'])?></li>
        <li><b>Amount:</b> ₦<?=number_format($amount,2)?></li>
      </ul>
      <p class="muted">Send the exact amount. After you pay, click <em>I have paid</em>. An admin will verify and credit your wallet after approval.</p>
    </div>

    <form method="post">
      <input type="hidden" name="step" value="confirm_payment">
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
      <button type="submit">I have paid — Notify admin</button>
    </form>
    <?php
    exit;
}

// STEP 3: confirm and create deposit_requests row (pending)
if ($step === 'confirm_payment') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        echo "<div class='flash'>Invalid request (CSRF).</div>";
        exit;
    }
    if (empty($_SESSION['deposit_amount'])) {
        echo "<div class='flash'>Session expired — start again.</div>";
        exit;
    }

    $amount = (float)$_SESSION['deposit_amount'];
    if ($amount <= 0) {
        echo "<div class='flash'>Invalid amount.</div>";
        exit;
    }

    // Insert deposit request (pending) with account metadata
    try {
        $stmt = $pdo->prepare("INSERT INTO deposit_requests (user_id, currency, amount, account_number, bank_name, account_name, status, created_at) VALUES (?, 'NGN', ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$uid, $amount, $permanent_account['account_number'], $permanent_account['bank_name'], $permanent_account['account_name']]);
        $reqId = $pdo->lastInsertId();

        // optional: create a transaction row marking a deposit_request (not credited yet) — helpful for audit
        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'NGN', 'deposit_request', ?, ?)")->execute([$uid, $amount, json_encode(['deposit_request_id'=>$reqId])]);

        // clear session amount
        unset($_SESSION['deposit_amount']);

        // friendly message and redirect to dashboard
        $_SESSION['flash'] = "Deposit requested (#{$reqId}) — pending admin approval. Admin will credit your wallet after verification.";
        header('Location: index.php?view=home');
        exit;
    } catch (Exception $e) {
        // helpful debug message
        echo "<div class='flash'>Error creating deposit request: " . htmlspecialchars($e->getMessage()) . "</div>";
        exit;
    }
}

// fallback
echo "<div class='muted'>Unknown step.</div>";
?>
  </div>
</body>
</html>



