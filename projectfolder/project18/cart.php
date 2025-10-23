<?php
require_once 'header.php';
global $pdo;
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo '<main style="max-width:1100px;margin:18px auto;padding:12px"><h2>Your Cart is empty</h2><a href="index.php" class="btn">Back to shop</a></main>';
    require 'footer.php'; exit;
}

// refresh DB prices if present
$subtotal = cart_subtotal($pdo);
$delivery_fee = 1500.00;
$tax = round($subtotal * 0.05,2);
$total = $subtotal + $delivery_fee + $tax;
?>
<main style="max-width:1100px;margin:18px auto;padding:12px">
  <h2>Your Cart</h2>
  <form method="post" action="cart_action.php?action=update">
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>Image</th><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
    <tbody>
    <?php
    $placeholders = implode(',', array_fill(0,count($cart),'?'));
    $ids = array_keys($cart);
    $db = [];
    if (!empty($ids)) {
        $stmt = $pdo->prepare("SELECT id,title,price,image FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        foreach($stmt->fetchAll() as $r) $db[$r['id']] = $r;
    }
    foreach($cart as $pid=>$it):
        $pid = intval($pid);
        $qty = intval($it['qty']);
        if (isset($db[$pid])) { $p = $db[$pid]; $price = $p['price']; $image = $p['image']; $title = $p['title']; }
        else { $price = $it['price']; $image = $it['image']; $title = $it['title']; }
        $line = $price * $qty;
    ?>
      <tr style="border-top:1px solid #eee">
        <td style="width:120px"><img src="<?=htmlspecialchars($image?:'images/placeholder.jpg')?>" style="width:100px;height:60px;object-fit:cover;border-radius:6px"></td>
        <td><?=htmlspecialchars($title)?></td>
        <td><?=money($price)?></td>
        <td><input type="number" name="qty[<?=$pid?>]" value="<?=$qty?>" min="1" style="width:70px"></td>
        <td><?=money($line)?></td>
        <td>
          <form method="post" action="cart_action.php?action=remove">
            <input type="hidden" name="product_id" value="<?=$pid?>">
            <button class="btn" type="submit">Remove</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div style="margin-top:12px">
    <button class="btn" type="submit">Update quantities</button>
    <a href="index.php" class="btn" style="margin-left:8px">Continue shopping</a>
  </div>
  </form>

  <div style="margin-top:18px;background:#fff;padding:12px;border-radius:8px">
    <div>Subtotal: <?=money($subtotal)?></div>
    <div>Delivery fee: <?=money($delivery_fee)?></div>
    <div>Tax (5%): <?=money($tax)?></div>
    <div style="font-weight:900;margin-top:8px">Total: <?=money($total)?></div>
    <form method="post" action="checkout.php" style="margin-top:12px">
      <input type="hidden" name="delivery_fee" value="<?=$delivery_fee?>">
      <button class="btn" type="submit">Proceed to Checkout</button>
    </form>
  </div>
</main>
<?php require_once 'footer.php'; ?>

