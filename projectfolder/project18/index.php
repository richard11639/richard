<?php
require_once 'header.php';
global $pdo;
$isAdmin = !empty($user && $user['is_admin']);

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 100");
$products = $stmt->fetchAll();
?>
<main class="container" style="padding:18px">
  <h2>Our Wig & Hair (<?=count($products)?>)</h2>
  <?php if($isAdmin): ?><p><a href="admin_dashboard.php">Admin Dashboard</a></p><?php endif; ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px">
  <?php foreach($products as $p): ?>
    <div style="background:#fff;padding:12px;border-radius:8px;box-shadow:0 6px 18px rgba(2,22,36,0.06);display:flex;flex-direction:column">
      <img src="<?=htmlspecialchars($p['image'] ?: 'images/placeholder.jpg')?>" style="width:100%;height:180px;object-fit:cover;border-radius:6px" alt="<?=htmlspecialchars($p['title'])?>">
      <h3 style="margin:8px 0 4px"><?=htmlspecialchars($p['title'])?></h3>
      <div style="font-weight:800;color:#ff7b54"><?=money($p['price'])?></div>
      <p style="flex:1"><?=nl2br(htmlspecialchars($p['description']))?></p>

      <form method="post" action="cart_action.php" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
        <input type="number" name="qty" value="1" min="1" style="width:70px;padding:6px;border-radius:6px;border:1px solid #eef">
        <button class="btn" name="action" value="add" type="submit">Add to cart</button>
        <?php if($isAdmin): ?>
          <a href="edit_product.php?id=<?=intval($p['id'])?>" class="btn" style="background:#fff;border:1px solid #e6ebf0;color:#071428">Edit</a>
        <?php endif; ?>
      </form>
    </div>
  <?php endforeach; ?>
  </div>
</main>
<?php require_once 'footer.php'; ?>

