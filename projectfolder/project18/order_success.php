<?php
require_once 'db.php';
include 'header.php';

$order_id = intval($_GET['order'] ?? 0);
if (!$order_id) { echo '<p>Order not found</p>'; include 'footer.php'; exit; }

$order = $pdo->prepare('SELECT * FROM orders WHERE id = ?'); $order->execute([$order_id]); $ord = $order->fetch(PDO::FETCH_ASSOC);
$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?'); $items->execute([$order_id]); $items = $items->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Order received</h2>
<p>Order #<?=$ord['id']?> — Status: <?=htmlspecialchars($ord['status'])?></p>
<ul>
<?php foreach($items as $it): ?>
  <li><?=htmlspecialchars($it['title'])?> × <?=$it['qty']?> — <?=money($it['price'])?></li>
<?php endforeach; ?>
</ul>
<p>Subtotal: <?=money($ord['subtotal'])?> — Total: <?=money($ord['total'])?></p>

<?php if($ord['payment_method']==='Bank Transfer'): ?>
  <div style="padding:12px;border:1px dashed #ddd;margin-top:8px">
    <p>Please send transfer to:</p>
    <p>Bank: Palmpay/Local Bank<br>Account name: Olayinka Mary Ogundele<br>Account no: <strong>7050672951</strong></p>
    <p>After transfer, upload proof to your account or reply to 09021427575 with proof.</p>
  </div>
<?php endif; ?>

<a href="index.php">Back to shop</a>
<?php include 'footer.php'; ?>
