<?php
// Start session to track deposit flow
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>bank payment</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 450px;
      margin: 80px auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    button {
      width: 100%;
      padding: 10px;
      background: green;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background: darkgreen;
    }
    .bank-details {
      background: #f9f9f9;
      padding: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .success {
      color: green;
      font-weight: bold;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="booking1.php">home</a>
    <h2>Amount ask to pay</h2>

    <?php
    // STEP 1: Ask for deposit amount
    if (!isset($_POST['step']) || $_POST['step'] == "enter_amount") {
        ?>
        <form method="POST">
          <label for="amount">Enter amount ask to pay (₦):</label>
          <input type="number" name="amount" id="amount" placeholder="Enter amount" required>
          <input type="hidden" name="step" value="show_bank">
          <button type="submit">Submit</button>
        </form>
        <?php
    }

    // STEP 2: Show bank details after submitting amount
    elseif ($_POST['step'] == "show_bank" && isset($_POST['amount'])) {
        $amount = htmlspecialchars($_POST['amount']);
        $_SESSION['deposit_amount'] = $amount;
        ?>
        <div class="bank-details">
          <p><b>Bank Details:</b></p>
          <ul>
            <li><b>Name:</b> Richard Ogundele</li>
            <li><b>Bank:</b> Palmpay</li>
            <li><b>Account Number:</b> 7050672951</li>
            <li><b>Deposit Amount:</b> ₦<?php echo $amount; ?></li>
          </ul>
        </div>
        <form method="POST">
          <input type="hidden" name="step" value="confirm_payment">
          <button type="submit">I Have Paid</button>
        </form>
        <?php
    }

    // STEP 3: Payment confirmation
    elseif ($_POST['step'] == "confirm_payment" && isset($_SESSION['deposit_amount'])) {
        $amount = $_SESSION['deposit_amount'];
        ?>
   <a href="deposit.php">home</a>  
        <p class="success">✅ Deposit Successful!</p>
        <p>You have deposited <b>₦<?php echo $amount; ?></b></p>
        <p>We will verify your payment and update your wallet balance.</p>
        <form method="POST">
          <input type="hidden" name="step" value="enter_amount">
          <button type="submit">Make Another Deposit</button>
        </form>
        <?php
        // Clear session after success
        session_destroy();
    }
    ?>
  </div>
</body>
</html>
