<?php
// index.php
session_start();
require_once 'db.php';

// simple auth indicator
$logged_in = isset($_SESSION['user_id']);
$user_name = $logged_in ? ($_SESSION['user_name'] ?? 'You') : null;

// handle search
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// fetch products
$products = [];
if ($search !== '') {
    $stmt = $conn->prepare("SELECT id, name, description, price, image 
                            FROM products 
                            WHERE name LIKE ? OR description LIKE ? 
                            ORDER BY id ASC");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, name, description, price, image FROM products ORDER BY id ASC");
}

if ($result) {
    while ($r = $result->fetch_assoc()) $products[] = $r;
}

// ensure session cart exists
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>RIC Restaurant — Book Now</title>
  <link rel="stylesheet" href="css/style.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    .search-bar {
      display:flex;
      align-items:center;
      margin-bottom:20px;
      max-width:400px;
      border:1px solid #ccc;
      border-radius:20px;
      padding:5px 12px;
    }
    .search-bar input {
      flex:1;
      border:none;
      outline:none;
      padding:8px;
      font-size:14px;
    }
    .search-bar button {
      background:none;
      border:none;
      cursor:pointer;
      font-size:18px;
    }
  </style>
</head>
<body>
<header class="header container">
  <div class="logo">RIC</div>
  <div>
    <span class="nav">
      <a href="restuarant.php">Home</a>
      <a href="feedback.php">feedback</a>
      <a href="#foods">Order Online</a>
      <a href="#contact">Contact</a>
    </span>
    <?php if ($logged_in): ?>
      <a href="logout.php" style="margin-left:12px">Logout</a>
    <?php else: ?>
      <a href="signin.php" style="margin-left:12px">Sign In</a>
      <a href="signup.php" style="margin-left:8px">Sign Up</a>
    <?php endif; ?>
  </div>
</header>

<main class="container">
  <section class="hero">
    <h1>RIC RESTAURANT</h1>
    <p class="small">Delicious food straight to your door</p>
  </section>

  <section id="foods">
    <h2>Our Restaurant Packages</h2>

    <!-- search bar -->
    <form class="search-bar" method="get" action="index.php">
      <input type="text" name="q" placeholder="Search food..." value="<?=htmlspecialchars($search)?>">
      <button type="submit">🔍</button>
    </form>

     <div class="products" id="productsGrid">
      <?php if (empty($products)): ?>
        <p>No results found.</p>
      <?php else: ?>
        <?php foreach ($products as $p): ?>
          <article class="card">
            <img src="<?=htmlspecialchars($p['image'])?>" 
                 alt="<?=htmlspecialchars($p['name'])?>" 
                 onerror="this.onerror=null;this.src='<?=$p['id']?>/600/400'">
            <h3><?=htmlspecialchars($p['name'])?></h3>
            <p class="small"><?=htmlspecialchars($p['description'])?></p>
            <div class="price">₦<?=number_format($p['price'],2)?></div>
            <div class="actions">
              <form method="post" action="cart_action.php" style="display:inline">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn">ADD</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<!-- cart panel (floating) -->
<div class="cartbar" id="cartBar">
  <h4>Your Cart</h4>
  <div id="cartContents">
    <?php
    $subtotal = 0;
    if (!empty($_SESSION['cart'])):
      foreach ($_SESSION['cart'] as $pid => $qty):
        $stmt = $conn->prepare("SELECT id,name,price,image FROM products WHERE id=? LIMIT 1");
        $stmt->bind_param('i',$pid);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (!$res) continue;
        $line = $res;
        $line_qty = (int)$qty;
        $line_sub = $line_qty * $line['price'];
        $subtotal += $line_sub;
    ?>
      <div class="cart-item">
        <img src="<?=htmlspecialchars($line['image'])?>" 
             onerror="this.onerror=null;this.src='https://picsum.photos/seed/ric<?=$line['id']?>/200/160'">
        <div style="flex:1">
          <div><strong><?=htmlspecialchars($line['name'])?></strong></div>
          <div class="small">₦<?=number_format($line['price'],2)?> • Qty: <?=$line_qty?> • Sub ₦<?=number_format($line_sub,2)?></div>
        </div>
        <div>
          <form method="post" action="cart_action.php">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product_id" value="<?= $line['id'] ?>">
            <button class="btn ghost" type="submit">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="small">Cart is empty</div>
    <?php endif; ?>
  </div>

  <div class="summary">Subtotal ₦<?=number_format($subtotal,2)?></div>
  <div style="margin-top:8px;display:flex;gap:8px">
    <a href="checkout.php" class="btn">Go to Checkout</a>
    <form method="post" action="cart_action.php" style="display:inline">
      <input type="hidden" name="action" value="clear">
      <button type="submit" class="btn ghost">Clear</button>
    </form>
  </div>
  <div class="small" style="margin-top:8px">Tax: 5% • Delivery fee: ₦3000</div>
</div>

<script>
function viewPreview(id){
  window.open('https://picsum.photos/seed/ric'+id+'/800/600','_blank');
}
</script>

</body>
</html>



