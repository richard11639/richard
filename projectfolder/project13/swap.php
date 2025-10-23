<?php
// swap.php — simple NGN <-> USDT swap with 2% fee (demo only)

/* ========== CONFIG (match your index.php) ========== */
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
    'secure'=>false,
    'httponly'=>true,
    'samesite'=>'Lax'
]);
if (session_status()===PHP_SESSION_NONE) session_start();

/* ========== CONNECT ========== */
try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB connect failed: ".htmlspecialchars($e->getMessage()));
}

/* ========== HELPERS (copied from index.php) ========== */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}
function check_csrf($t) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$t);
}
function money_fmt($a) { return number_format((float)$a, 2, '.', ','); }
function require_login() { if (empty($_SESSION['user_id'])) { header('Location: index.php?view=login'); exit; } }
function ensure_wallet(PDO $pdo, $uid, $currency, $initial = 0.0) {
    // Insert ignore, then optionally set initial balance only if created fresh.
    $ins = $pdo->prepare("INSERT IGNORE INTO wallets (user_id, currency, balance, hold_amount) VALUES (?, ?, ?, 0)");
    $ins->execute([$uid, $currency, 0]);
    // If initial > 0 and balance is zero, set it (demo-only)
    if ($initial > 0) {
        $sel = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ?");
        $sel->execute([$uid, $currency]); $r = $sel->fetch();
        if ($r && ((float)$r['balance'] == 0.0)) {
            $upd = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
            $upd->execute([$initial, $r['id']]);
        }
    }
}

/* ========== UTILS ========== */
$token = csrf_token();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$logged_in = !empty($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;
$swap_fee_rate = 0.02; // 2%

/* ========== get_live_rate() ==========
   Determine NGN per USDT rate. Use latest trade for USDT/NGN market if available,
   otherwise fall back to a sensible demo rate (e.g. 800 NGN per USDT).
*/
function get_live_rate(PDO $pdo) {
    // try to find market id for symbol 'USDT/NGN' (we seeded this in index.php)
    $s = $pdo->prepare("SELECT id FROM markets WHERE symbol = ? LIMIT 1");
    $s->execute(['USDT/NGN']);
    $m = $s->fetch();
    if ($m) {
        $t = $pdo->prepare("SELECT price FROM trades WHERE market_id = ? ORDER BY created_at DESC LIMIT 1");
        $t->execute([$m['id']]); $tr = $t->fetch();
        if ($tr && (float)$tr['price'] > 0) return (float)$tr['price'];
    }
    // fallback demo rate
    return 800.0;
}

/* ========== HANDLE POST (swap action) ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'do_swap') {
    require_login();
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = 'Invalid CSRF';
        header('Location: swap.php'); exit;
    }

    $direction = $_POST['direction'] ?? 'ngn_to_usdt'; // 'ngn_to_usdt' or 'usdt_to_ngn'
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        $_SESSION['flash'] = 'Enter a valid amount to swap';
        header('Location: swap.php'); exit;
    }

    try {
        $rate = get_live_rate($pdo); // NGN per USDT
        $pdo->beginTransaction();

        // Ensure both wallets exist
        ensure_wallet($pdo, $uid, 'NGN');
        ensure_wallet($pdo, $uid, 'USDT');

        if ($direction === 'ngn_to_usdt') {
            // User provides NGN, convert to USDT after 2% fee
            $ngn_amount = $amount;
            $fee_ngn = round($ngn_amount * $swap_fee_rate, 8);
            $net_ngn = $ngn_amount - $fee_ngn;
            if ($net_ngn <= 0) throw new Exception('Amount too small after fee');

            // lock NGN wallet
            $sw = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $sw->execute([$uid, 'NGN']); $ngw = $sw->fetch();
            if (!$ngw) throw new Exception('NGN wallet missing');

            if ((float)$ngw['balance'] < $ngn_amount) {
                throw new Exception('Insufficient NGN balance for swap');
            }

            // compute USDT to credit
            $usdt_to_credit = $net_ngn / $rate;

            // apply changes
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, update_at = NOW() WHERE id = ?")
                ->execute([$ngn_amount, $ngw['id']]);

            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'USDT'")
                ->execute([$usdt_to_credit, $uid]);

            // transactions ledger
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'NGN', 'swap_out', ?, ?)")
                ->execute([$uid, -$ngn_amount, json_encode(['to'=>'USDT','rate'=>$rate,'fee'=>$fee_ngn])]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'USDT', 'swap_in', ?, ?)")
                ->execute([$uid, $usdt_to_credit, json_encode(['from'=>'NGN','rate'=>$rate,'fee_ngn'=>$fee_ngn])]);
            // record fee as separate transaction (optional)
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'NGN', 'fee', ?, ?)")
                ->execute([$uid, -$fee_ngn, json_encode(['desc'=>'swap fee 2%','pair'=>'NGN->USDT'])]);

            $pdo->commit();
            $_SESSION['flash'] = 'Swapped ₦'.money_fmt($ngn_amount).' → '.number_format($usdt_to_credit,8).' USDT (rate: ₦'.money_fmt($rate).', fee: ₦'.money_fmt($fee_ngn).')';
            header('Location: swap.php'); exit;

        } else {
            // USDT -> NGN
            $usdt_amount = $amount;
            $fee_usdt = round($usdt_amount * $swap_fee_rate, 8);
            $net_usdt = $usdt_amount - $fee_usdt;
            if ($net_usdt <= 0) throw new Exception('Amount too small after fee');

            // lock USDT wallet
            $sw = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $sw->execute([$uid, 'USDT']); $usdw = $sw->fetch();
            if (!$usdw) throw new Exception('USDT wallet missing');

            if ((float)$usdw['balance'] < $usdt_amount) {
                throw new Exception('Insufficient USDT balance for swap');
            }

            $ngn_to_credit = $net_usdt * $rate;

            // deduct USDT, credit NGN
            $pdo->prepare("UPDATE wallets SET balance = balance - ?, update_at = NOW() WHERE id = ?")
                ->execute([$usdt_amount, $usdw['id']]);

            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'NGN'")
                ->execute([$ngn_to_credit, $uid]);

            // ledger entries
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'USDT', 'swap_out', ?, ?)")
                ->execute([$uid, -$usdt_amount, json_encode(['to'=>'NGN','rate'=>$rate,'fee'=>$fee_usdt])]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'NGN', 'swap_in', ?, ?)")
                ->execute([$uid, $ngn_to_credit, json_encode(['from'=>'USDT','rate'=>$rate,'fee_usdt'=>$fee_usdt])]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, 'USDT', 'fee', ?, ?)")
                ->execute([$uid, -$fee_usdt, json_encode(['desc'=>'swap fee 2%','pair'=>'USDT->NGN'])]);

            $pdo->commit();
            $_SESSION['flash'] = 'Swapped '.number_format($usdt_amount,8).' USDT → ₦'.money_fmt($ngn_to_credit).' (rate: ₦'.money_fmt($rate).', fee: '.number_format($fee_usdt,8).' USDT)';
            header('Location: swap.php'); exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = 'Swap error: '.$e->getMessage();
        header('Location: swap.php'); exit;
    }
}

/* ========== Page: show swap UI & balances ========== */
if (!$logged_in) {
    // quick link to login/register page in your app
    echo "<p>Please <a href='index.php?view=login'>login</a> to use swap.</p>";
    exit;
}

// fetch balances
$wq = $pdo->prepare("SELECT currency, balance, hold_amount FROM wallets WHERE user_id = ?");
$wq->execute([$uid]); $wallets = $wq->fetchAll();
$map = [];
foreach ($wallets as $w) $map[$w['currency']] = $w;

$ngn_bal = isset($map['NGN']) ? (float)$map['NGN']['balance'] : 0.0;
$usdt_bal = isset($map['USDT']) ? (float)$map['USDT']['balance'] : 0.0;
$rate_now = get_live_rate($pdo);

?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Swap — NGN ⇄ USDT</title>
<style>
body{font-family:Inter,Arial,sans-serif;padding:18px;background:#f6f8fb;color:#0f172a}
.card{background:#fff;padding:16px;border-radius:10px;box-shadow:0 8px 20px rgba(2,6,23,0.06);max-width:720px;margin:10px auto}
h2{margin:0 0 12px}
.form-row{display:flex;gap:8px;align-items:center}
.input{padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%}
.btn{padding:8px 12px;border-radius:8px;background:#2563eb;color:#fff;border:0;cursor:pointer}
.small{font-size:13px;color:#64748b}
.flash{padding:8px;background:#eefbe6;border-left:4px solid #b7f0c5;margin-bottom:12px}
</style>
</head>
<body>
  <div class="card">
    <h2>Swap NGN ↔ USDT (demo)</h2>

    <?php if(!empty($flash)): ?><div class="flash"><?=htmlspecialchars($flash)?></div><?php endif; ?>

    <p class="small">Current demo rate (approx): <strong>₦ <?=money_fmt($rate_now)?> per USDT</strong>. Fee: <strong>2%</strong> of source amount.</p>

    <div style="display:flex;gap:12px;margin-top:12px">
      <div style="flex:1">
        <h3>Balances</h3>
        <table style="width:100%">
          <tr><td><strong>NGN</strong></td><td>₦ <?=money_fmt($ngn_bal)?></td></tr>
          <tr><td><strong>USDT</strong></td><td><?=number_format($usdt_bal,8)?></td></tr>
        </table>
      </div>

      <div style="width:320px">
        <h3>Swap</h3>

        <!-- NGN -> USDT -->
        <form method="post" style="margin-bottom:10px">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="do_swap">
          <input type="hidden" name="direction" value="ngn_to_usdt">
          <div class="form-row"><input class="input" name="amount" placeholder="NGN amount (e.g. 10000)" type="number" step="0.01" required></div>
          <div style="margin-top:8px">
            <button class="btn" type="submit">Swap NGN → USDT</button>
          </div>
        </form>

        <!-- USDT -> NGN -->
        <form method="post">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="do_swap">
          <input type="hidden" name="direction" value="usdt_to_ngn">
          <div class="form-row"><input class="input" name="amount" placeholder="USDT amount (e.g. 1.5)" type="number" step="0.00000001" required></div>
          <div style="margin-top:8px">
            <button class="btn" type="submit">Swap USDT → NGN</button>
          </div>
        </form>
      </div>
    </div>

    <p class="small" style="margin-top:10px">Notes: This is a demo implementation — not production-ready. All swaps are processed immediately on your DB and charged 2% fee. Rate is derived from the latest `USDT/NGN` trade if present; otherwise a fallback rate is used.</p>

    <p style="margin-top:12px"><a href="index.php">Back to Dashboard</a></p>
  </div>
</body>
</html>
