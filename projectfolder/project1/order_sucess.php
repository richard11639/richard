<?php
require_once 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}
if (!$order) {
    echo "Order not found.";
    exit;
}
$items = [];
$itstmt = $conn->prepare("SELECT * FROM order_items WHERE order_id=?");
$itstmt->bind_param('i',$id);
$itstmt->execute();
$res = $itstmt->get_result();
while ($r = $res->fetch_assoc()) $items[] = $r;
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Order Confirmed</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<div class="container">
  <h2>Order #<?=htmlspecialchars($order['id'])?> — <?=htmlspecialchars(ucfirst($order['status']))?></h2>
  <p>Thank you <?=htmlspecialchars($order['first_name'])?> — we've received your order.</p>

  <div class="box">
    <h3>Order Summary</h3>
    <?php foreach($items as $it): ?>
      <div style="display:flex;justify-content:space-between">
        <div><?=htmlspecialchars($it['product_name'])?> × <?=$it['qty']?></div>
        <div>₦<?=number_format($it['subtotal'],2)?></div>
      </div>
    <?php endforeach; ?>
    <hr>
    <div style="display:flex;justify-content:space-between"><div>Subtotal</div><div>₦<?=number_format($order['subtotal'],2)?></div></div>
    <div style="display:flex;justify-content:space-between"><div>Tax</div><div>₦<?=number_format($order['tax'],2)?></div></div>
    <div style="display:flex;justify-content:space-between"><div>Delivery fee</div><div>₦<?=number_format($order['delivery_fee'],2)?></div></div>
    <div style="display:flex;justify-content:space-between"><strong>Total</strong><strong>₦<?=number_format($order['total'],2)?></strong></div>
  </div>

  <?php if ($order['payment_type'] === 'card'): ?>
    <p>Pay online via this link:</p>
    <!-- Example external link - replace with real payment gateway -->
    <a class="btn" href="https://example-payment-gateway.test/checkout?order_id=<?=urlencode($order['id'])?>&amount=<?=urlencode(number_format($order['total'],2,'.',''))?>" target="_blank">Pay Now</a>
  <?php else: ?>
    <p>Payment method: <?=htmlspecialchars($order['payment_type'])?></p>
    <?php if ($order['payment_type'] === 'transfer'): ?>
      <p class="small">If you transferred, please send proof to <strong>09021427575</strong>.</p>
      <?php if ($order['payment_proof']): ?>
        <p>Uploaded proof: <a href="<?=htmlspecialchars($order['payment_proof'])?>" target="_blank">View</a></p>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>

  <p><a href="index.php" class="btn ghost">Back to menu</a></p>
</div>
</body>
</html>
