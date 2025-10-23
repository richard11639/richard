<?php
// join_signals.php
// Single-file HTML + CSS + PHP to let users join Telegram & WhatsApp signal groups
// and confirm membership. Records submissions to a CSV file signals_members.csv

// === Configuration ===
// Replace these with your real invite/partner links:
$TELEGRAM_LINK = "https://t.me/TELEGRAM_INVITE_LINK";
$WHATSAPP_LINK = "https://chat.whatsapp.com/WHATSAPP_INVITE_LINK";

// CSV file to store confirmations (writable by the webserver)
$CSV_FILE = __DIR__ . "/signals_members.csv";

// Helper: append row to CSV (creates file with header if missing)
function save_to_csv($file, $row) {
    $exists = file_exists($file);
    $fp = fopen($file, 'a');
    if (!$exists) {
        // header
        fputcsv($fp, ['timestamp','name','contact','joined_telegram','joined_whatsapp','ip','user_agent']);
    }
    fputcsv($fp, $row);
    fclose($fp);
}

// POST handling
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_join'])) {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $joined_telegram = isset($_POST['joined_telegram']) ? 'yes' : 'no';
    $joined_whatsapp = isset($_POST['joined_whatsapp']) ? 'yes' : 'no';

    if ($name === '') $errors[] = "Please enter your name.";
    if ($contact === '') $errors[] = "Please enter your email or phone number.";
    if ($joined_telegram !== 'yes' && $joined_whatsapp !== 'yes') {
        $errors[] = "Please confirm you joined at least one channel (Telegram or WhatsApp).";
    }

    if (empty($errors)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');

        // Save to CSV (silently ignore failure)
        $row = [$timestamp, $name, $contact, $joined_telegram, $joined_whatsapp, $ip, $ua];
        save_to_csv($CSV_FILE, $row);

        $success = true;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Join Signals — Telegram & WhatsApp</title>
<style>
  :root{
    --bg:#0b1220; --card:#0f1b2b; --accent:#3ea0ff; --muted:#9fb3d1; --ok:#16c784;
    --radius:12px;
  }
  *{box-sizing:border-box}
  body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:linear-gradient(180deg,#061026,#07142a);color:#eaf4ff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
  .wrap{max-width:920px;width:100%;display:grid;grid-template-columns:1fr 420px;gap:18px}
  @media(max-width:980px){.wrap{grid-template-columns:1fr}}
  .card{background:linear-gradient(180deg,var(--card),#071526);border-radius:var(--radius);padding:18px;border:1px solid rgba(255,255,255,0.04);box-shadow:0 12px 40px rgba(0,0,0,0.45)}
  .hero{display:flex;flex-direction:column;gap:12px}
  h1{margin:0;font-size:1.25rem}
  p.lead{margin:0;color:var(--muted)}
  .buttons{display:flex;gap:12px;flex-wrap:wrap}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 16px;border-radius:10px;border:none;cursor:pointer;font-weight:800}
  .btn.telegram{background:linear-gradient(90deg,#27a1ff,#66d2ff);color:#022233}
  .btn.whatsapp{background:linear-gradient(90deg,#25d366,#12b34a);color:#04260f}
  .btn.secondary{background:transparent;border:1px solid rgba(255,255,255,0.06);color:var(--muted)}
  .note{background:rgba(255,255,255,0.02);padding:10px;border-radius:8px;color:var(--muted);font-size:0.95rem}
  form{display:flex;flex-direction:column;gap:10px;margin-top:12px}
  label{font-weight:700;font-size:.95rem}
  input[type="text"],input[type="email"]{padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:inherit;outline:none}
  .checkboxes{display:flex;gap:12px;align-items:center}
  .small{font-size:.9rem;color:var(--muted)}
  .success{background:linear-gradient(90deg,#dafbe6,#bff4d4);color:#053b1f;padding:12px;border-radius:8px;font-weight:800}
  .errors{background:linear-gradient(90deg,#ffdede,#ffecec);color:#5d0b0b;padding:12px;border-radius:8px}
  .footer{font-size:.85rem;color:var(--muted);margin-top:8px}
  .csv-link{display:inline-block;margin-top:8px;padding:8px 12px;background:rgba(255,255,255,0.03);border-radius:8px;color:var(--muted);text-decoration:none}
</style>
</head>
<body>
  <div class="wrap">
    <!-- Left: Info & Join Buttons -->
    <div class="card hero" aria-labelledby="join-header">
      <div>
  <a href="trading4.php">home</a>
        <h1 id="join-header">Join our Signal Channels</h1>
        <p class="lead">Join our <strong>Telegram</strong> and <strong>WhatsApp</strong> groups to receive trading signals, alerts and updates. After joining, confirm below so we can track your subscription.</p>
      </div>

      <div class="buttons" role="group" aria-label="Join links">
        <!-- Replace hrefs with real invite links -->
        <button class="btn telegram" id="openTelegram" onclick="openLink('<?php echo htmlspecialchars($TELEGRAM_LINK, ENT_QUOTES); ?>', 'tel')">
          <!-- small SVG -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M22 4L2 12.5L7 14.5L9 20L11.5 14.5L21 4Z" fill="#022233" /></svg>
          Join Telegram
        </button>

        <button class="btn whatsapp" id="openWhatsapp" onclick="openLink('<?php echo htmlspecialchars($WHATSAPP_LINK, ENT_QUOTES); ?>', 'was')">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M20 4.5C18.4 3.1 16 2 12 2 6 2 2 6 2 12c0 2 .6 3.8 1.7 5.3L2 22l4.9-1.5C8.3 21 10 21.3 12 21.3 18 21.3 22 17.3 22 12c0-1.6-0.4-3-1.1-4.2z" fill="#04260f"/></svg>
          Join WhatsApp
        </button>

        <a class="btn secondary" href="#how" onclick="document.getElementById('how').scrollIntoView({behavior:'smooth'})">How it works</a>
      </div>

      <div class="note" id="how" style="margin-top:12px">
        <strong>How to join</strong>
        <ol style="margin:8px 0 0 16px;padding:0">
          <li>Click a group button (opens in a new tab).</li>
          <li>Join the group on the opened page (Telegram / WhatsApp).</li>
          <li>Return here, fill your name & contact, check the channel(s) you joined and click <em>Confirm</em>.</li>
        </ol>
      </div>

      <div class="footer">Privacy: we only store your name and contact to manage signal subscriptions. You can request deletion.</div>
    </div>

    <!-- Right: Confirmation Form -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <strong>Confirm your subscription</strong>
          <div class="small">Tell us which channel(s) you joined</div>
        </div>
        <div class="small">Channels: Telegram & WhatsApp</div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="errors" role="alert">
          <?php echo implode("<br>", array_map('htmlspecialchars',$errors)); ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success" role="status">
          ✅ Thank you — your confirmation was recorded. You will now receive signals via the channel(s) you joined.
        </div>
        <div class="footer">If you gave an email, we may send a welcome message. To view records (admin), check <code>signals_members.csv</code> on the server.</div>
      <?php else: ?>

      <form method="POST" onsubmit="return requireAtLeastOne();">
        <label for="name">Full Name</label>
        <input id="name" name="name" type="text" placeholder="e.g. John Doe" required>

        <label for="contact">Email or Phone</label>
        <input id="contact" name="contact" type="text" placeholder="Email (you@domain.com) or phone (+234...)" required>

        <div class="small">Check the channel(s) you joined (tick at least one).</div>
        <div class="checkboxes" style="margin-top:6px">
          <label style="display:flex;align-items:center;gap:8px"><input id="ctg" type="checkbox" name="joined_telegram" value="yes"> Joined Telegram</label>
          <label style="display:flex;align-items:center;gap:8px"><input id="cwg" type="checkbox" name="joined_whatsapp" value="yes"> Joined WhatsApp</label>
        </div>

        <input type="hidden" name="confirm_join" value="1">
        <button type="submit" style="margin-top:10px">Confirm</button>
      </form>

      <div style="margin-top:10px" class="small">
        Admin: The confirmations are saved to <code>signals_members.csv</code> in the same folder. Make sure the webserver can write to that file (chmod).
      </div>

      <?php endif; ?>

      <?php
        // For convenience: if CSV exists show small link (no directory listing exposure)
        if (file_exists($CSV_FILE)) {
          echo '<a class="csv-link" href="'.basename($CSV_FILE).'" download>Download confirmations CSV</a>';
        }
      ?>
    </div>
  </div>

<script>
  // Open link and mark the checkbox automatically (best-effort)
  function openLink(url, which) {
    // open new tab
    window.open(url, '_blank');

    // try to pre-check the right checkbox
    if (which === 'tel') {
      const t = document.querySelector('input[name="joined_telegram"]');
      if(t) t.checked = true;
    } else if (which === 'was') {
      const w = document.querySelector('input[name="joined_whatsapp"]');
      if(w) w.checked = true;
    }
    // focus the form
    document.querySelector('input[name="name"]').focus();
  }

  function requireAtLeastOne() {
    const tg = document.querySelector('input[name="joined_telegram"]').checked;
    const wg = document.querySelector('input[name="joined_whatsapp"]').checked;
    if (!tg && !wg) {
      alert('Please confirm you joined at least one channel (Telegram or WhatsApp).');
      return false;
    }
    return true;
  }
</script>
</body>
</html>
