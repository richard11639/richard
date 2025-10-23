<?php
require_once __DIR__ . '/functions.php';
require_login();
$user = current_user();

// load items from DB
$stmt = $pdo->prepare("SELECT p.*, ci.qty FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.user_id = ?");
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();
$items = [];
$total = 0.0;
foreach ($rows as $r) {
    $qty = (int)$r['qty'];
    $price = (float)$r['price'];
    $subtotal = $qty * $price;
    $items[] = ['product'=>$r, 'qty'=>$qty, 'subtotal'=>$subtotal];
    $total += $subtotal;
}

// Checkout POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Basic server-side re-check of stock
    $pdo->beginTransaction();
    $ok = true;
    foreach ($rows as $r) {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$r['id']]);
        $p = $stmt->fetch();
        if (!$p || $p['stock'] < $r['qty']) { $ok = false; break;}
    }
    if (!$ok) {
        $pdo->rollBack();
        $error = "One or more items are no longer available in the requested quantity.";
    } else {
        $ins = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?,?, 'paid')");
        $ins->execute([$user['id'], $total]);
        $orderId = (int)$pdo->lastInsertId();
        $insItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?,?,?,?)");
        $updStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($rows as $r) {
            $insItem->execute([$orderId, $r['id'], $r['qty'], $r['price']]);
            $updStock->execute([$r['qty'], $r['id']]);
        }
        // clear cart_items for user
        $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$user['id']]);
        $pdo->commit();
        app_log_action("Order placed orderId={$orderId} user={$user['id']} total={$total}");
        header('Location: cart.php?success=1'); exit;
    }
}

?>
<!doctype html><html><head><meta charset="utf-8"><title>Your Cart</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="site-header"><div class="container"><h1 class="logo"><a href="index.php">Cassandra's <span>Hair Salon</span></a></h1></div></header>
<main class="container">
  <h2>Your Cart</h2>
  <?php if (!empty($error)): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
  <?php if (isset($_GET['success'])): ?><div class="success">Order placed successfully — thank you!</div><?php endif; ?>

  <?php if (empty($items)): ?>
    <p>Your cart is empty. <a href="index.php">Shop now</a></p>
  <?php else: ?>
    <table class="cart-table"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e($it['product']['title']) ?></td>
          <td>₦<?= number_format($it['product']['price'],0) ?></td>
          <td><?= (int)$it['qty'] ?></td>
          <td>₦<?= number_format($it['subtotal'],0) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table>
    <p class="cart-total"><strong>Total:</strong> ₦<?= number_format($total,0) ?></p>
    <form method="post">
      <button class="btn" name="checkout" type="submit">Checkout (simulate)</button>
    </form>
  <?php endif; ?>
</main>
</body></html>
