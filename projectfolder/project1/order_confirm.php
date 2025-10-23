<?php
// order_confirm.php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    die("Cart empty. <a href='index.php'>Back</a>");
}

// collect fields
$order_type    = $_POST['order_type'] ?? 'delivery';
$when          = $_POST['when'] ?? 'asap';
$specific_time = $_POST['specific_time'] ?? null;
$firstname     = trim($_POST['firstname'] ?? '');
$lastname      = trim($_POST['lastname'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$company       = trim($_POST['company'] ?? '');
$address       = trim($_POST['address'] ?? '');
$apt           = trim($_POST['apt'] ?? '');
$city          = trim($_POST['city'] ?? '');
$comments      = trim($_POST['comments'] ?? '');
$payment_method= $_POST['payment_method'] ?? 'cash';

$delivery_fee  = (float)($_POST['delivery_fee'] ?? 3000.00);
$tax_rate      = (float)($_POST['tax_rate'] ?? 0.05);

// compute totals safely
$items = [];
$ids = array_keys($cart);
$subtotal_calc = 0;

if ($ids) {
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare(
        "SELECT id,name,price FROM products WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")"
    );
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $qty = intval($cart[$r['id']] ?? 0);
        $line = $r;
        $line['qty'] = $qty;
        $line['subtotal'] = $qty * $r['price'];
        $subtotal_calc += $line['subtotal'];
        $items[] = $line;
    }
}

$tax_amount = $subtotal_calc * $tax_rate;
$total = $subtotal_calc + ($order_type === 'delivery' ? $delivery_fee : 0) + $tax_amount;

// proof file upload
$proof_filename = null;
if (!empty($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
    $u = $_FILES['proof_file'];
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (in_array($u['type'], $allowed) && $u['size'] < 2_500_000) {
        $ext = pathinfo($u['name'], PATHINFO_EXTENSION);
        $proof_filename = 'uploads/proof_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!is_dir('uploads')) mkdir('uploads',0755,true);
        move_uploaded_file($u['tmp_name'], $proof_filename);
    }
}

// Save order
$user_id = $_SESSION['user_id'] ?? null;
$delivery_time_text = $when === 'scheduled' ? ($specific_time ?: '') : ($when === 'asap' ? 'ASAP' : '');

$stmt = $conn->prepare("INSERT INTO orders
(user_id, order_type, delivery_time, address, apt, city, company, firstname, lastname, phone, payment_method, proof_filename, comment, delivery_fee, tax, subtotal, total, created_at)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");

$stmt->bind_param(
    'issssssssssssdddd',
    $user_id,
    $order_type,
    $delivery_time_text,
    $address,
    $apt,
    $city,
    $company,
    $firstname,
    $lastname,
    $phone,
    $payment_method,
    $proof_filename,
    $comments,
    $delivery_fee,
    $tax_amount,
    $subtotal_calc,
    $total
);

$ok = $stmt->execute();
if (!$ok) {
    die("DB error: " . $stmt->error);
}
$order_id = $stmt->insert_id;

// save order items
$stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($items as $it) {
    $stmt_item->bind_param('iiid', $order_id, $it['id'], $it['qty'], $it['price']);
    $stmt_item->execute();
}

// clear cart
$_SESSION['cart'] = [];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Order Confirmed</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container checkout-panel">
  <h2>Order Confirmed</h2>
  <p>Thank you, <?=htmlspecialchars($firstname)?>! Your order #<?= $order_id ?> has been placed.</p>

  <h4>Order summary</h4>
  <?php foreach ($items as $it): ?>
    <div style="display:flex;justify-content:space-between">
      <div><?=htmlspecialchars($it['name'])?> × <?=$it['qty']?></div>
      <div>₦<?=number_format($it['subtotal'],2)?></div>
    </div>
  <?php endforeach; ?>
  <hr>
  <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>₦<?=number_format($subtotal_calc,2)?></span></div>
  <?php if ($order_type === 'delivery'): ?>
    <div style="display:flex;justify-content:space-between"><span>Delivery fee</span><span>₦<?=number_format($delivery_fee,2)?></span></div>
  <?php endif; ?>
  <div style="display:flex;justify-content:space-between"><span>Tax</span><span>₦<?=number_format($tax_amount,2)?></span></div>
  <div class="summary" style="display:flex;justify-content:space-between;margin-top:8px">
    <span>Total</span><span>₦<?=number_format($total,2)?></span>
  </div>

  <h4>Order info</h4>
  <div class="small">Order type: <?=htmlspecialchars($order_type)?> • When: <?=htmlspecialchars($delivery_time_text)?></div>
  <div class="small">Payment: <?=htmlspecialchars($payment_method)?></div>
  <?php if ($proof_filename): ?>
    <div class="small">Proof uploaded: <a href="<?=htmlspecialchars($proof_filename)?>" target="_blank">View</a></div>
  <?php endif; ?>

  <p style="margin-top:12px"><a href="index.php" class="btn">Back to shop</a></p>
</div>
</body>
</html>
