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
  <a href="trading2.php">home</a>   
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
       <a href="withdraw.php">home</a>            
                <form method="POST">
                  <label for="bank">Select Bank:</label>
                  <select name="bank" id="bank" required>
                    <option value="">-- Select Bank --</option>
                    <option value="Access Bank">Access Bank</option>
                    <option value="GTBank">GTBank</option>
                    <option value="UBA">UBA</option>
                    <option value="Zenith Bank">Zenith Bank</option>
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
