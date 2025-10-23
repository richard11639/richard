<?php
require_once 'config.php';
session_start();
if (empty($_SESSION['booking_id'])) {
    header('Location: index.php'); exit;
}
$bookingId = (int)$_SESSION['booking_id'];
$amount = (float)$_SESSION['booking_amount']; // NGN
$email = $_SESSION['booking_email'];
$paystackPublic = PAYSTACK_PUBLIC;
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Pay for booking #<?=$bookingId?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-dark text-light">
<div class="container py-5 text-center">
  <h3>Booking #<?=$bookingId?> — Pay ₦<?=number_format($amount)?></h3>
  <p class="muted">You will be redirected to Paystack. After payment we verify the transaction and update your booking.</p>
  <button id="payBtn" class="btn btn-success btn-lg">Pay with Card (Paystack)</button>
  <p class="mt-3"><a href="index.php" class="btn btn-link text-white">Cancel</a></p>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('payBtn').addEventListener('click', function(){
  var handler = PaystackPop.setup({
    key: '<?=htmlspecialchars($paystackPublic)?>',
    email: '<?=htmlspecialchars($email)?>',
    amount: <?=($amount * 100)?>, // in kobo
    currency: "NGN",
    ref: 'BKNG_<?=$bookingId?>_' + Math.floor((Math.random() * 1000000)),
    metadata: {
      custom_fields: [
        {display_name: "Booking ID", variable_name: "booking_id", value: "<?=$bookingId?>"}
      ]
    },
    callback: function(response){
      window.location.href = "verify_payment.php?reference=" + response.reference;
    },
    onClose: function(){
      alert('Payment closed.');
    }
  });
  handler.openIframe();
});
</script>
</body>
</html>
