<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Withdraw Funds</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 500px;
      margin: 50px auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    label {
      font-weight: bold;
      display: block;
      margin: 10px 0 5px;
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #0066cc;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background: #004c99;
    }
    .error { color: red; font-weight: bold; text-align: center; }
    .success { color: green; font-weight: bold; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
  <a href="trading4.php">home</a>   
    <h2>Withdraw Funds</h2>
    <?php
    // STEP 1: Select method + amount
    if (!isset($_POST['step']) || $_POST['step'] == "choose_method") {
        ?>
        <form method="POST">
          <label for="method">Choose Withdrawal Method:</label>
          <select name="method" id="method" required>
            <option value="">-- Select --</option>
            <option value="bank">Bank</option>
            <option value="usdt">USDT - TRC20</option>
          </select>

          <label for="amount">Enter Amount:</label>
          <input type="number" name="amount" id="amount" placeholder="Enter withdrawal amount" required>

          <input type="hidden" name="step" value="details">
          <button type="submit">Submit</button>
        </form>
        <?php
    }

    // STEP 2: Enter details
    elseif ($_POST['step'] == "details") {
        $method = $_POST['method'];
        $amount = (float) $_POST['amount'];

        // Validation
        if ($method == "bank" && $amount < 5000) {
            echo "<p class='error'>❌ Minimum withdrawal for Bank is ₦5,000.</p>";
            echo "<form method='POST'><input type='hidden' name='step' value='choose_method'><button type='submit'>Try Again</button></form>";
        }
        elseif ($method == "usdt" && $amount < 30000) {
            echo "<p class='error'>❌ Minimum withdrawal for USDT-TRC20 is ₦30,000.</p>";
            echo "<form method='POST'><input type='hidden' name='step' value='choose_method'><button type='submit'>Try Again</button></form>";
        }
        else {
            // Save session
            $_SESSION['method'] = $method;
            $_SESSION['amount'] = $amount;
            $fee = $amount * 0.20;
            $final = $amount - $fee;

            echo "<p><b>Amount:</b> ₦$amount</p>";
            echo "<p><b>Withdrawal Fee (20%):</b> ₦$fee</p>";
            echo "<p><b>You will receive:</b> ₦$final</p>";

            if ($method == "bank") {
                ?>
       <a href="withdraw.php">back</a>            
                <form method="POST">
                  <label for="bank">Select Bank:</label>
                  <select name="bank" id="bank" required>
                    <option value="">-- Select Bank --</option>
                      <option value="Access">Access bank</option>
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
                    <option value="Zenith Bank"></option>
                    <option value="First Bank">First Bank</option>
                  </select>

                  <label for="account_number">Account Number:</label>
                  <input type="text" name="account_number" required>

                  <label for="account_name">Account Name:</label>
                  <input type="text" name="account_name" required>

                  <input type="hidden" name="step" value="confirm">
                  <button type="submit">Proceed</button>
                </form>
                <?php
            } else {
                ?>
                <form method="POST">
                  <label for="usdt_address">USDT - TRC20 Address:</label>
                  <input type="text" name="usdt_address" required>

                  <input type="hidden" name="step" value="confirm">
                  <button type="submit">Proceed</button>
                </form>
                <?php
            }
        }
    }

    // STEP 3: Confirmation
    elseif ($_POST['step'] == "confirm") {
        $method = $_SESSION['method'];
        $amount = $_SESSION['amount'];
        $fee = $amount * 0.20;
        $final = $amount - $fee;

        echo "<h3>Confirm Withdrawal</h3>";
        echo "<p><b>Method:</b> " . strtoupper($method) . "</p>";
        echo "<p><b>Amount:</b> ₦$amount</p>";
        echo "<p><b>Fee:</b> ₦$fee</p>";
        echo "<p><b>Final Amount:</b> ₦$final</p>";

        if ($method == "bank") {
            $bank = $_POST['bank'];
            $account_number = $_POST['account_number'];
            $account_name = $_POST['account_name'];

            $_SESSION['bank'] = $bank;
            $_SESSION['account_number'] = $account_number;
            $_SESSION['account_name'] = $account_name;

            echo "<p><b>Bank:</b> $bank</p>";
            echo "<p><b>Account Number:</b> $account_number</p>";
            echo "<p><b>Account Name:</b> $account_name</p>";
        } else {
            $usdt_address = $_POST['usdt_address'];
            $_SESSION['usdt_address'] = $usdt_address;
            echo "<p><b>USDT Address:</b> $usdt_address</p>";
        }
        ?>
        <form method="POST">
          <input type="hidden" name="step" value="final">
          <button type="submit">✅ Yes, Submit</button>
        </form>
        <form method="POST">
          <input type="hidden" name="step" value="choose_method">
          <button type="submit">❌ Cancel</button>
        </form>
        <?php
    }

    // STEP 4: Success
    elseif ($_POST['step'] == "final") {
        echo "<p class='success'>✅ Withdrawal Request Submitted Successfully!</p>";
        echo "<p>We will process your request shortly.</p>";
        session_destroy();
        ?>
        <form method="POST">
          <input type="hidden" name="step" value="choose_method">
          <button type="submit">Make Another Withdrawal</button>
        </form>
        <?php
    }
    ?>
  </div>
</body>
</html>




<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>List of Banks in Nigeria (CBN list)</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;background:#f6f8fb;color:#0b2136;padding:28px}
    .wrap{max-width:980px;margin:0 auto;background:#fff;border-radius:10px;padding:24px;box-shadow:0 6px 30px rgba(10,20,30,0.08)}
    h1{margin:0 0 8px;font-size:1.6rem}
    p.meta{color:#546e7a;margin:0 0 20px}
    section{margin-bottom:18px}
    h2{font-size:1.05rem;margin:10px 0 8px;color:#033e6b}
    ul{margin:6px 0 0 18px;padding:0}
    li{margin:6px 0;line-height:1.45}
    .footnote{font-size:0.85rem;color:#6b7d8a;margin-top:18px}
    .source{font-size:0.85rem;margin-top:8px}
    a.link{color:#0a84ff;text-decoration:none}
    .badge{display:inline-block;background:#eef6ff;color:#034ea2;padding:4px 8px;border-radius:999px;font-weight:700;font-size:0.8rem;margin-left:8px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Banks in Nigeria — CBN (deposit money / commercial banks & related institutions)</h1>
    <p class="meta">This page lists banks and related licensed banking institutions as published by the Central Bank of Nigeria (CBN). The snapshot below is taken from the CBN list published on April 26, 2024. For the official, most recent list visit the CBN website.</p>

    <section>
      <h2>Commercial banks — International authorization</h2>
      <ul>
        <li>Access Bank Plc</li>
        <li>Fidelity Bank Plc</li>
        <li>First City Monument Bank Plc (FCMB)</li>
        <li>First Bank of Nigeria Limited</li>
        <li>Guaranty Trust Bank Limited (GTBank)</li>
        <li>United Bank for Africa Plc (UBA)</li>
        <li>Zenith Bank Plc</li>
        <li>Citibank Nigeria Limited</li>
        <li>Ecobank Nigeria Plc</li>
        <li>Heritage Bank Plc</li>
        <li>Globus Bank Limited</li>
        <li>Keystone Bank Limited</li>
        <li>Polaris Bank Limited</li>
        <li>Stanbic IBTC Bank Limited</li>
        <li>Standard Chartered Bank Limited</li>
        <li>Sterling Bank Limited</li>
        <li>Titan Trust Bank Limited</li>
        <li>Union Bank of Nigeria Plc</li>
        <li>Unity Bank Plc</li>
        <li>Wema Bank Plc</li>
        <li>PremiumTrust Bank Limited</li>
      </ul>
    </section>

