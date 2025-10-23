<?php
require_once 'config.php';
$ref = $_GET['reference'] ?? null;
if (!$ref) { echo "No reference provided."; exit; }

// verify via Paystack
$secret = PAYSTACK_SECRET;
$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . urlencode($ref),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ["Authorization: Bearer $secret"]
]);
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if ($err) { die("cURL Error: ".$err); }
$data = json_decode($response, true);
if (!isset($data['status']) || $data['status'] !== true) {
    die("Payment verification failed. Response: " . htmlspecialchars($response));
}

$tx = $data['data'];
$amount_paid = $tx['amount'] / 100;
$metadata = $tx['metadata'] ?? null;
$bookingId = null;
if ($metadata && isset($metadata['custom_fields'])) {
  foreach($metadata['custom_fields'] as $f) {
    if (($f['variable_name'] ?? '') === 'booking_id') $bookingId = $f['value'];
  }
}
if (!$bookingId) {
  if (preg_match('/BKNG_(\d+)_/', $ref, $m)) $bookingId = intval($m[1]);
}
if (!$bookingId) die("Could not determine booking ID.");

$pdo = db();
$stmt = $pdo->prepare("SELECT amount,status FROM bookings WHERE id = ?");
$stmt->execute([$bookingId]);
$row = $stmt->fetch();
if (!$row) die("Booking not found.");

$expected = (float)$row['amount'];
if ($amount_paid + 0.01 < $expected) {
  die("Amount paid (₦$amount_paid) is less than expected (₦$expected).");
}

// update booking
$update = $pdo->prepare("UPDATE bookings SET status='paid', payment_method='card', transaction_ref=?, paid_at=NOW() WHERE id = ?");
$update->execute([$ref,$bookingId]);

?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Payment Verified</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-dark text-light">
  <div class="container py-5">
    <div class="card p-4">
      <h3>Payment Verified</h3>
      <p>Booking #<?=$bookingId?> is now marked as <strong>PAID</strong>. Reference: <?=$ref?></p>
      <p>We have sent a confirmation email (demo). Our reservations team will contact you for final confirmation.</p>
      <a class="btn btn-primary" href="index.php">Return to home</a>
    </div>
  </div>
</body>
</html>
