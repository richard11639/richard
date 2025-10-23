<?php
// swap.php — multi-currency swap (NGN, USDT, BTC, ETH) with 2% fee (demo only)
// Rewritten: non-recursive rate resolution, min-amount checks, immediate DB wallet updates.

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'tradding_db';
date_default_timezone_set('Africa/Lagos');

session_set_cookie_params([
    'lifetime'=>0,
    'path'=>'/',
    'domain'=>'',
    'secure'=>false,
    'httponly'=>true,
    'samesite'=>'Lax'
]);
if (session_status()===PHP_SESSION_NONE) session_start();

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB connect failed: ".htmlspecialchars($e->getMessage()));
}

/* helpers */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}
function check_csrf($t) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$t);
}
function money_fmt($a) { return number_format((float)$a, 2, '.', ','); }
function crypto_fmt($a) { return rtrim(rtrim(number_format((float)$a, 8, '.', ''), '0'), '.'); }
function require_login() { if (empty($_SESSION['user_id'])) { header('Location: index.php?view=login'); exit; } }
function ensure_wallet(PDO $pdo, $uid, $currency, $initial = 0.0) {
    $ins = $pdo->prepare("INSERT IGNORE INTO wallets (user_id, currency, balance, hold_amount) VALUES (?, ?, ?, 0)");
    $ins->execute([$uid, $currency, 0]);
    if ($initial > 0) {
        $sel = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ?");
        $sel->execute([$uid, $currency]); $r = $sel->fetch();
        if ($r && ((float)$r['balance'] == 0.0)) {
            $upd = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
            $upd->execute([$initial, $r['id']]);
        }
    }
}

/**
 * get_market_price - returns latest trade price for given market symbol (A/B),
 *   or 0.0 if no market/trade found.
 * Note: price returned is "quote per base", i.e., 1 A = price B.
 */
function get_market_price(PDO $pdo, string $symbol) : float {
    $q = $pdo->prepare("SELECT id FROM markets WHERE symbol = ? LIMIT 1");
    $q->execute([$symbol]); $m = $q->fetch();
    if (!$m) return 0.0;
    $t = $pdo->prepare("SELECT price FROM trades WHERE market_id = ? ORDER BY created_at DESC LIMIT 1");
    $t->execute([$m['id']]); $tr = $t->fetch();
    return ($tr && (float)$tr['price'] > 0) ? (float)$tr['price'] : 0.0;
}

/**
 * get_pair_rate - non-recursive, bounded pair rate resolution
 * Returns number (float) representing how many units of $to one unit of $from buys.
 * Example: get_pair_rate('NGN','USDT') => (USDT per NGN) e.g. 0.00125
 *          get_pair_rate('BTC','USDT') => (USDT per BTC) e.g. 43000
 */
function get_pair_rate(PDO $pdo, string $from, string $to) : float {
    $from = strtoupper(trim($from));
    $to   = strtoupper(trim($to));
    if ($from === $to) return 1.0;

    // 1) try direct market FROM/TO (1 FROM = price TO)
    $sym = "{$from}/{$to}";
    $p = get_market_price($pdo, $sym);
    if ($p > 0.0) return $p;

    // 2) try inverse market TO/FROM => invert
    $symInv = "{$to}/{$from}";
    $pInv = get_market_price($pdo, $symInv);
    if ($pInv > 0.0) {
        if ($pInv == 0.0) return 0.0;
        return 1.0 / $pInv;
    }

    // 3) try single-intermediate derivation: from -> mid, mid -> to
    // prefer USDT then NGN as intermediate
    $intermediates = ['USDT','NGN'];
    foreach ($intermediates as $mid) {
        if ($mid === $from || $mid === $to) continue;

        // from -> mid (try direct or inverse)
        $p_from_mid = get_market_price($pdo, "{$from}/{$mid}");
        if ($p_from_mid == 0.0) {
            $p_mid_from = get_market_price($pdo, "{$mid}/{$from}");
            if ($p_mid_from > 0.0) $p_from_mid = 1.0 / $p_mid_from;
        }

        // mid -> to
        $p_mid_to = get_market_price($pdo, "{$mid}/{$to}");
        if ($p_mid_to == 0.0) {
            $p_to_mid = get_market_price($pdo, "{$to}/{$mid}");
            if ($p_to_mid > 0.0) $p_mid_to = 1.0 / $p_to_mid;
        }

        if ($p_from_mid > 0.0 && $p_mid_to > 0.0) {
            // rate from -> to = (from->mid) * (mid->to)
            return $p_from_mid * $p_mid_to;
        }
    }

    // 4) fallback demo defaults (safe, finite)
    $defaults = [
        'USDT/NGN' => 800.0,   // 1 USDT = 800 NGN
        'BTC/NGN'  => 3500000.0,
        'ETH/NGN'  => 220000.0,
    ];
    // If from/to match a default pair or its inverse, return accordingly:
    $k = "{$from}/{$to}";
    if (isset($defaults[$k])) return $defaults[$k];
    $kInv = "{$to}/{$from}";
    if (isset($defaults[$kInv]) && $defaults[$kInv] != 0.0) return 1.0 / $defaults[$kInv];

    // As last resort, if we can estimate via USDT default:
    if ($from === 'NGN' && $to === 'USDT') return 1.0 / $defaults['USDT/NGN'];
    if ($from === 'USDT' && $to === 'NGN') return $defaults['USDT/NGN'];

    return 0.0;
}

/* page vars */
$token = csrf_token();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$logged_in = !empty($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;
$swap_fee_rate = 0.02; // 2%

/* POST handling: perform the swap */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'do_swap') {
    require_login();
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = 'Invalid CSRF';
        header('Location: swap.php'); exit;
    }

    $from = strtoupper(trim($_POST['from_currency'] ?? 'NGN'));
    $to   = strtoupper(trim($_POST['to_currency'] ?? 'USDT'));
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        $_SESSION['flash'] = 'Enter a valid amount to swap';
        header('Location: swap.php'); exit;
    }
    if ($from === $to) {
        $_SESSION['flash'] = 'Source and destination currencies must differ';
        header('Location: swap.php'); exit;
    }

    try {
        // find conversion rate (units of $to per 1 unit of $from)
        $rate = get_pair_rate($pdo, $from, $to);
        if ($rate <= 0.0) throw new Exception("Exchange rate unavailable for {$from}/{$to}");

        // Minimum rules:
        // - if swapping FROM NGN: require amount >= 1000 NGN
        // - if swapping FROM other (USDT/BTC/ETH): require NGN equivalent >= 10000 NGN
        if ($from === 'NGN') {
            if ($amount < 1000.0) throw new Exception('Minimum NGN amount is ₦1,000');
        } else {
            // compute NGN equivalent of source: need rate from source -> NGN
            $rate_to_ngn = get_pair_rate($pdo, $from, 'NGN');
            if ($rate_to_ngn <= 0.0) {
                // try derive via USDT fallback
                $rate_to_usdt = get_pair_rate($pdo, $from, 'USDT');
                $usdt_to_ngn = get_pair_rate($pdo, 'USDT', 'NGN');
                if ($rate_to_usdt > 0 && $usdt_to_ngn > 0) {
                    $rate_to_ngn = $rate_to_usdt * $usdt_to_ngn;
                }
            }
            if ($rate_to_ngn > 0.0) {
                $equiv_ngn = $amount * $rate_to_ngn;
                if ($equiv_ngn < 10000.0) {
                    throw new Exception("Minimum trade value for non-NGN source is ₦10,000 (your amount ≈ ₦".money_fmt($equiv_ngn).")");
                }
            } else {
                // if we cannot determine NGN equivalent, be conservative and allow but warn
                // (or you could reject — here we reject to be safe)
                throw new Exception("Unable to determine NGN equivalent for '{$from}'. Swap rejected. Add liquidity or contact admin.");
            }
        }

        // Proceed with DB update
        $pdo->beginTransaction();

        // ensure wallets exist
        ensure_wallet($pdo, $uid, $from);
        ensure_wallet($pdo, $uid, $to);

        // lock source wallet
        $ws = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
        $ws->execute([$uid, $from]); $sw = $ws->fetch();
        if (!$sw) throw new Exception("Source wallet not found ({$from})");

        if ((float)$sw['balance'] < $amount) throw new Exception('Insufficient balance to perform swap');

        // compute fee and net
        $fee = round($amount * $swap_fee_rate, 8);
        $net_source = $amount - $fee;
        if ($net_source <= 0) throw new Exception('Amount too small after fee');

        // compute target amount
        $target_amount = $net_source * $rate;

        // lock target wallet
        $wt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
        $wt->execute([$uid, $to]); $tw = $wt->fetch();
        if (!$tw) throw new Exception("Target wallet not found ({$to})");

        // DO update balances
        $updOut = $pdo->prepare("UPDATE wallets SET balance = balance - ?, update_at = NOW() WHERE id = ?");
        $updOut->execute([$amount, $sw['id']]);

        $updIn = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?");
        $updIn->execute([$target_amount, $tw['id']]);

        // ledger entries
        $meta_out = json_encode(['to'=>$to, 'rate'=>$rate, 'fee'=>$fee, 'net'=>$net_source]);
        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'swap_out', ?, ?)")->execute([$uid, $from, -$amount, $meta_out]);

        $meta_in = json_encode(['from'=>$from, 'rate'=>$rate, 'fee'=>$fee, 'net'=>$net_source]);
        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'swap_in', ?, ?)")->execute([$uid, $to, $target_amount, $meta_in]);

        // record fee as separate transaction (source currency negative)
        $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'fee', ?, ?)")->execute([$uid, $from, -$fee, json_encode(['desc'=>'swap fee','pair'=>"{$from}_to_{$to}"])]);

        $pdo->commit();

        // friendly message
        $display_amt_from = ($from === 'NGN') ? '₦'.money_fmt($amount) : (($from === 'USDT') ? number_format($amount,8).' USDT' : crypto_fmt($amount).' '.$from);
        $display_amt_to   = ($to   === 'NGN') ? '₦'.money_fmt($target_amount) : (($to === 'USDT') ? number_format($target_amount,8).' USDT' : crypto_fmt($target_amount).' '.$to);
        $display_fee = ($from === 'NGN') ? '₦'.money_fmt($fee) : (($from === 'USDT') ? number_format($fee,8).' USDT' : crypto_fmt($fee).' '.$from);

        $_SESSION['flash'] = "Swapped {$display_amt_from} → {$display_amt_to} (rate: 1 {$from} = ".( ($to==='NGN') ? '₦'.money_fmt($rate) : ( ( $to==='USDT') ? number_format($rate,8).' USDT' : number_format($rate,8).' '.$to ) ).", fee: {$display_fee})";
        header('Location: swap.php'); exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = 'Swap error: '.$e->getMessage();
        header('Location: swap.php'); exit;
    }
}

/* Page rendering (balances + simple UI) */
if (!$logged_in) {
    echo "<p>Please <a href='index.php?view=login'>login</a> to use swap.</p>";
    exit;
}

$wq = $pdo->prepare("SELECT currency, balance, hold_amount FROM wallets WHERE user_id = ?");
$wq->execute([$uid]); $wallets = $wq->fetchAll();
$map = [];
foreach ($wallets as $w) $map[$w['currency']] = $w;

$ngn_bal = isset($map['NGN']) ? (float)$map['NGN']['balance'] : 0.0;
$usdt_bal = isset($map['USDT']) ? (float)$map['USDT']['balance'] : 0.0;
$btc_bal  = isset($map['BTC'])  ? (float)$map['BTC']['balance']  : 0.0;
$eth_bal  = isset($map['ETH'])  ? (float)$map['ETH']['balance']  : 0.0;

// preview pair rate (optional)
$preview_from = strtoupper($_GET['from'] ?? 'NGN');
$preview_to   = strtoupper($_GET['to'] ?? 'USDT');
$preview_rate = get_pair_rate($pdo, $preview_from, $preview_to);

$available_pairs = ['NGN','USDT','BTC','ETH'];
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Swap — multi-currency (demo)</title>
<style>
body{font-family:Inter,Arial,sans-serif;padding:18px;background:#f6f8fb;color:#0f172a}
.card{background:#fff;padding:16px;border-radius:10px;box-shadow:0 8px 20px rgba(2,6,23,0.06);max-width:820px;margin:10px auto}
h2{margin:0 0 12px}
.form-row{display:flex;gap:8px;align-items:center}
.input,select{padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%}
.btn{padding:8px 12px;border-radius:8px;background:#2563eb;color:#fff;border:0;cursor:pointer}
.small{font-size:13px;color:#64748b}
.flash{padding:8px;background:#eefbe6;border-left:4px solid #b7f0c5;margin-bottom:12px}
.row{display:flex;gap:12px}
.col{flex:1}
.rate-box{background:#f1f5f9;padding:8px;border-radius:8px;margin-top:8px}
.balance{font-weight:700}
</style>
</head>
<body>
  <div class="card">
    <h2>Swap NGN / USDT / BTC / ETH (demo)</h2>

    <?php if(!empty($flash)): ?><div class="flash"><?=htmlspecialchars($flash)?></div><?php endif; ?>

    <p class="small">Fee: <strong>2%</strong> of the source amount. Minimums: ₦1,000 when swapping from NGN; non-NGN trades must be ≥ ₦10,000 equivalent.</p>

    <div class="row" style="margin-top:12px">
      <div class="col">
        <h3>Balances</h3>
        <table style="width:100%">
          <tr><td><strong>NGN</strong></td><td class="balance">₦ <?=money_fmt($ngn_bal)?></td></tr>
          <tr><td><strong>USDT</strong></td><td class="balance"><?=number_format($usdt_bal,8)?> USDT</td></tr>
          <tr><td><strong>BTC</strong></td><td class="balance"><?=crypto_fmt($btc_bal)?> BTC</td></tr>
          <tr><td><strong>ETH</strong></td><td class="balance"><?=crypto_fmt($eth_bal)?> ETH</td></tr>
        </table>
      </div>

      <div style="width:420px">
        <h3>Swap</h3>

        <form method="post" id="swapForm">
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($token)?>">
          <input type="hidden" name="action" value="do_swap">

          <label class="small">From
            <select name="from_currency" id="from_currency" class="input" required>
              <?php foreach ($available_pairs as $c): ?>
                <option value="<?=htmlspecialchars($c)?>" <?=($c==='NGN'?'selected':'')?>><?=htmlspecialchars($c)?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="small">To
            <select name="to_currency" id="to_currency" class="input" required>
              <?php foreach ($available_pairs as $c): ?>
                <option value="<?=htmlspecialchars($c)?>" <?=($c==='USDT'?'selected':'')?>><?=htmlspecialchars($c)?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="small">Amount (source currency)
            <input class="input" name="amount" id="amount" placeholder="Enter amount to swap" type="number" step="0.00000001" required>
          </label>

          <div class="rate-box" id="rateBox">
            <div class="small">Preview rate: <strong id="rateText"><?php echo $preview_rate>0 ? htmlspecialchars((string)$preview_rate) : 'unavailable'; ?></strong></div>
            <div class="small" id="estimateText">Target amount estimate: —</div>
            <div class="small">Fee (2%): <span id="feeText">—</span></div>
          </div>

          <div style="margin-top:8px;display:flex;gap:8px">
            <button class="btn" type="submit">Perform Swap</button>
            <button type="button" id="previewBtn" class="btn" style="background:#10b981">Preview</button>
          </div>
        </form>

        <div style="margin-top:10px" class="small">
          Notes: Demo only. Swaps are immediate DB operations here. Rates are derived from `trades`/`markets` where available; otherwise demo fallback rates are used.
        </div>
      </div>
    </div>

    <p style="margin-top:12px"><a href="index.php">Back to Dashboard</a></p>
  </div>

  <script>
    const apiRate = <?php echo json_encode(['default_preview_rate' => $preview_rate]); ?>;
    const fromEl = document.getElementById('from_currency');
    const toEl   = document.getElementById('to_currency');
    const amountEl = document.getElementById('amount');
    const rateText = document.getElementById('rateText');
    const estimateText = document.getElementById('estimateText');
    const feeText = document.getElementById('feeText');
    const previewBtn = document.getElementById('previewBtn');

    function fetchPreviewRate(from, to) {
      // lightweight demo preview: if pair matches server-provided preview, use it.
      if (from === '<?php echo $preview_from?>' && to === '<?php echo $preview_to?>') {
        return apiRate.default_preview_rate || 0;
      }
      // otherwise show "click Preview" — in production you'd add an AJAX endpoint to return precise latest rates.
      return 0;
    }

    function updateEstimate() {
      const from = fromEl.value;
      const to = toEl.value;
      const amt = parseFloat(amountEl.value) || 0;
      if (amt <= 0) {
        estimateText.textContent = 'Target amount estimate: —';
        feeText.textContent = '—';
        return;
      }
      const fee = +(amt * 0.02).toFixed(8);
      feeText.textContent = fee + ' ' + from;
      const previewRate = fetchPreviewRate(from,to);
      rateText.textContent = previewRate > 0 ? previewRate : 'unavailable';
      if (previewRate > 0) {
        const target = +(((amt - fee) * previewRate).toFixed(8));
        estimateText.textContent = 'Target amount estimate: ' + target + ' ' + to;
      } else {
        estimateText.textContent = 'Target amount estimate: unavailable (click Preview to compute server-side)';
      }
    }

    fromEl.addEventListener('change', updateEstimate);
    toEl.addEventListener('change', updateEstimate);
    amountEl.addEventListener('input', updateEstimate);

    previewBtn.addEventListener('click', function(){
      const from = encodeURIComponent(fromEl.value);
      const to = encodeURIComponent(toEl.value);
      window.location = 'swap.php?from=' + from + '&to=' + to;
    });

    updateEstimate();
  </script>
</body>
</html>
