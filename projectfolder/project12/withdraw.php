<?php
// withdraw.php - multi-step withdraw UI which inserts a withdraw_requests row after validation
session_start();

// DB config (same as index.php)
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'investtment_db';
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
function csrf_token() { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); return $_SESSION['csrf_token']; }
function check_csrf($t) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$t); }
function require_login() { if (empty($_SESSION['user_id'])) { header('Location: index.php?view=login'); exit; } }

require_login();
$uid = (int)$_SESSION['user_id'];

$step = $_POST['step'] ?? 'choose_method';
$token = csrf_token();

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Withdraw Funds</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f5f8;margin:0;padding:0}
    .wrap{max-width:700px;margin:40px auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 30px rgba(0,0,0,0.06)}
    h2{text-align:center}
    label{display:block;margin-top:10px;font-weight:600}
    input,select{width:100%;padding:10px;border-radius:6px;border:1px solid #ddd;margin-top:6px}
    button{width:100%;padding:10px;background:#0b63b3;color:#fff;border:0;border-radius:6px;margin-top:12px}
    .muted{color:#6b7280}
    .error{color:#b91c1c;font-weight:700}
  </style>
</head>
<body>
  <div class="wrap">
    <a href="index.php">← Back to dashboard</a>
    <h2>Withdraw Funds</h2>

<?php
if ($step === 'choose_method') {
    ?>
    <form method="post">
      <input type="hidden" name="step" value="details">
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
      <label>Withdrawal Method</label>
      <select name="method" required>
        <option value="">-- Select --</option>
        <option value="bank">Bank (NGN)</option>
        <option value="usdt">USDT - TRC20</option>
      </select>
      <label>Amount (NGN)</label>
      <input type="number" name="amount" step="0.01" min="1" required>
      <button type="submit">Continue</button>
    </form>
    <?php
    exit;
}

if ($step === 'details') {
    if (!check_csrf($_POST['csrf'] ?? '')) { echo "<p class='error'>Invalid request</p>"; exit; }
    $method = $_POST['method'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0.0);
    if ($amount <= 0) { echo "<p class='error'>Invalid amount.</p>"; exit; }

    // Validate min amounts based on method
    $minBank = 5000; $minUsdt = 30000;
    if ($method === 'bank' && $amount < $minBank) {
        echo "<p class='error'>Minimum bank withdrawal is ₦" . number_format($minBank) . "</p>";
        echo "<a href='withdraw.php'>Try again</a>"; exit;
    }
    if ($method === 'usdt' && $amount < $minUsdt) {
        echo "<p class='error'>Minimum USDT withdrawal is ₦" . number_format($minUsdt) . "</p>";
        echo "<a href='withdraw.php'>Try again</a>"; exit;
    }

    // Check user balance in NGN wallet
    $stmt = $pdo->prepare("SELECT balance, hold_amount FROM wallets WHERE user_id = ? AND currency = 'NGN'");
    $stmt->execute([$uid]);
    $w = $stmt->fetch();
    $balance = $w ? (float)$w['balance'] : 0.0;
    if ($balance < $amount) {
        echo "<p class='error'>Insufficient balance (your NGN balance is ₦" . number_format($balance,2) . ").</p>";
    }

    // store into session for confirm step
    $_SESSION['withdraw_method'] = $method;
    $_SESSION['withdraw_amount'] = $amount;

    $fee = round($amount * 0.03, 2); // 20% fee as in your UI (you can change)
    $final = round($amount - $fee, 3);

    echo "<p class='muted'>Amount: ₦" . number_format($amount,5) . "</p>";
    echo "<p class='muted'>Fee (3%): ₦" . number_format($fee,3) . "</p>";
    echo "<p class='muted'><strong>You will receive: ₦" . number_format($final,2) . "</strong></p>";

    if ($method === 'bank') {
        ?>
        <form method="post">
          <input type="hidden" name="step" value="confirm">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <label>Select Bank</label>
          <select name="bank" required>
            <option>Access Bank</option>
            <option>GTBank</option>
            <option>UBA</option>
            <option>Zenith Bank</option>
            <option>First Bank</option>
                        <option value="Fidelity">Fidelity</option>
                      <option value="First City Monument Bank Plc (FCMB)">First City Monument Bank Plc (FCMB)</option>
                      <option value="First Bank of Nigeria Limited">First Bank of Nigeria Limited</option>
                      <option value="Guaranty Trust Bank Limited (GTBank)">Guaranty Trust Bank Limited (GTBank)</option>
                      <option value="United Bank for Africa Plc (UBA)">United Bank for Africa Plc (UBA)</option>
                      <option value="wema bank">stanbic IBTC bank limited</option>
                      <option value="wema bank">sterling bank limited</option>
                      <option value="wema bank"> titan trust bank limited</option>
                      <option value="wema bank">unity bank plc</option>
                     <option value="wema bank"> Wema Bank Plc</option>
                     <option value=" premium trust bank">PremiumTrust Bank Limited</option>              
                    <option value="Access Bank">opay</option>
                    <option value="GTBank">palmpay</option>
                    <option value="UBA">moniepoint</option>
                    <option value="Zenith Bank">zenith bank</option>
                    <option value="First Bank">First Bank</option>
          </select>
          <label>Account Number</label>
          <input type="text" name="account_number" required>
          <label>Account Name</label>
          <input type="text" name="account_name" required>
          <button type="submit">Proceed</button>
        </form>
        <?php
    } else {
        // USDT
        ?>
        <form method="post">
          <input type="hidden" name="step" value="confirm">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <label>USDT - TRC20 Address</label>
          <input type="text" name="usdt_address" required>
          <button type="submit">Proceed</button>
        </form>
        <?php
    }
    exit;
}

// confirm step: gather posted details, create withdraw_requests row (pending)
if ($step === 'confirm') {
    if (!check_csrf($_POST['csrf'] ?? '')) { echo "<p class='error'>Invalid request</p>"; exit; }
    if (empty($_SESSION['withdraw_method']) || empty($_SESSION['withdraw_amount'])) {
        echo "<p class='error'>Session expired. Start again.</p>"; exit;
    }
    $method = $_SESSION['withdraw_method'];
    $amount = (float)$_SESSION['withdraw_amount'];
    $fee = round($amount * 0.20, 2);
    $final = round($amount - $fee, 2);

    // Collect details
    $meta = [];
    if ($method === 'bank') {
        $bank = trim($_POST['bank'] ?? '');
        $acc_no = trim($_POST['account_number'] ?? '');
        $acc_name = trim($_POST['account_name'] ?? '');
        if (!$bank || !$acc_no || !$acc_name) { echo "<p class='error'>All bank fields required</p>"; exit; }
        $meta['bank'] = $bank; $meta['account_number'] = $acc_no; $meta['account_name'] = $acc_name;
    } else {
        $addr = trim($_POST['usdt_address'] ?? '');
        if (!$addr) { echo "<p class='error'>USDT address required</p>"; exit; }
        $meta['usdt_address'] = $addr;
    }

    // Final server-side check: ensure balance still available (race condition check)
    $pdo->beginTransaction();
    try {
        $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
        $wq->execute([$uid]);
        $wr = $wq->fetch();
        $balance = $wr ? (float)$wr['balance'] : 0.0;
        if ($balance < $amount) {
            $pdo->rollBack();
            echo "<p class='error'>Insufficient balance at time of request. Your current balance: ₦" . number_format($balance,2) . "</p>";
            
            exit;
        }

        // Create withdraw_requests row with status pending; admin will process
        $stmt = $pdo->prepare("INSERT INTO withdraw_requests (user_id, currency, amount, destination, status, created_at) VALUES (?, 'NGN', ?, ?, 'pending', NOW())");
        $stmt->execute([$uid, $amount, json_encode($meta)]);
        $reqId = $pdo->lastInsertId();

        // Optionally: reduce available balance immediately or move to hold (we'll move to hold to prevent double spending)
        if ($wr) {
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, hold_amount = hold_amount + ? WHERE id = ?")
                ->execute([$amount, $amount, $wr['id']]);
        } else {
            // user has no wallet row (should not happen if they have balance), ensure wallet
            $ins = $pdo->prepare("INSERT INTO wallets (user_id, balance, hold_amount) VALUES (?, 'NGN', 0, ?)");
            $ins->execute([$uid, $amount]);
        }

        // log transaction
        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'NGN', 'withdraw_request', ?, ?)")
            ->execute([$uid, -$amount, json_encode(['withdraw_request_id'=>$reqId])]);

        $pdo->commit();

        // clear session withdraw info
        unset($_SESSION['withdraw_method'], $_SESSION['withdraw_amount']);

        echo "<p class='muted'>✅ Withdraw request submitted (ID: {$reqId}).it will drop soon,and process it shortly.</p>";
        echo "<a href='index.php?view=home'>Back to dashboard</a>";
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
}

?>
  </div>
</body>
</html>


