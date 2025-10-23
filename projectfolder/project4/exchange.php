<?php
session_start();

// Fixed exchange rates
$rates = [
    "NGN" => 1500,
    "USD" => 1,
    "BTC" => 109553,
    "ETH" => 4415,
    "LTC" => 110,
    "TRX" => 0.033680,
    "BNB" => 839,
    "ETC" => 21.0470,
    "DOGE" => 0.2098
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Crypto & Currency Exchange</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f2f2f2; margin:0; padding:0; }
    .container { max-width:600px; margin:40px auto; background:#fff; padding:25px;
      border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    h2 { text-align:center; margin-bottom:20px; color:#222; }
    label { font-weight:bold; margin-top:10px; display:block; }
    select,input { width:100%; padding:10px; margin:8px 0; border-radius:6px; border:1px solid #bbb; }
    button { width:100%; padding:12px; background:#28a745; border:none; border-radius:6px;
      color:#fff; font-size:16px; cursor:pointer; }
    button:hover { background:#218838; }
    .result, .error, .success { padding:12px; margin:12px 0; border-radius:6px; }
    .error { background:#fdd; color:#900; font-weight:bold; }
    .success { background:#dfd; color:#060; font-weight:bold; }
    .result { background:#eef; color:#003; }
  </style>
</head>
<body>
  <div class="container">
  <a href="trading4.php">home</a>   
    <h2>Exchange Crypto & Currencies</h2>

    <?php
    // Step 1: Exchange Form
    if (!isset($_POST['step']) || $_POST['step']=="choose") {
    ?>
      <form method="POST">
        <label>Exchange From:</label>
        <select name="from" required>
          <option value="">-- Select Currency --</option>
          <?php foreach($rates as $c=>$v){ echo "<option value='$c'>$c</option>"; } ?>
        </select>

        <label>Exchange To:</label>
        <select name="to" required>
          <option value="">-- Select Currency --</option>
          <?php foreach($rates as $c=>$v){ echo "<option value='$c'>$c</option>"; } ?>
        </select>

        <label>Enter Amount:</label>
        <input type="number" step="0.00000001" name="amount" placeholder="Enter amount" required>

        <input type="hidden" name="step" value="review">
        <button type="submit">Proceed</button>
      </form>
    <?php
    }

    // Step 2: Review Exchange
    elseif ($_POST['step']=="review") {
      $from = $_POST['from'];
      $to = $_POST['to'];
      $amount = (float)$_POST['amount'];

      if ($from == $to) {
        echo "<div class='error'>❌ You cannot exchange the same currency.</div>";
        echo "<form method='POST'><input type='hidden' name='step' value='choose'><button>Back</button></form>";
      } else {
        // Convert to USD first
        $usd_value = $amount * $rates[$from];
        // Convert to target currency
        $target_amount = $usd_value / $rates[$to];
        // Apply 2% fee
        $fee = $target_amount * 0.02;
        $final_amount = $target_amount - $fee;

        $_SESSION['from']=$from; $_SESSION['to']=$to; $_SESSION['amount']=$amount;
        $_SESSION['final']=$final_amount; $_SESSION['fee']=$fee; $_SESSION['target_amount']=$target_amount;

        echo "<div class='result'>
          <p><b>Exchange From:</b> $amount $from</p>
          <p><b>Exchange To:</b> $to</p>
          <p><b>Calculated Amount:</b> ".number_format($target_amount,8)." $to</p>
          <p><b>Exchange Fee (2%):</b> ".number_format($fee,8)." $to</p>
          <p><b>You Will Receive:</b> ".number_format($final_amount,8)." $to</p>
        </div>";

        echo "<form method='POST'>
          <input type='hidden' name='step' value='confirm'>
          <button type='submit'>✅ Confirm Exchange</button>
        </form>
        <form method='POST'><input type='hidden' name='step' value='choose'><button>❌ Cancel</button></form>";
      }
    }

    // Step 3: Confirm
    elseif ($_POST['step']=="confirm") {
      echo "<div class='success'>✅ Exchange Completed!</div>";
      echo "<p><b>{$_SESSION['amount']} {$_SESSION['from']}</b> exchanged to <b>".number_format($_SESSION['final'],8)." {$_SESSION['to']}</b></p>";
      session_destroy();
      echo "<form method='POST'><input type='hidden' name='step' value='choose'><button>Exchange Again</button></form>";
    }
    ?>
  </div>
</body>
</html>
