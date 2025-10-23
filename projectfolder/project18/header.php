<?php
// header.php
require_once __DIR__ . '/functions.php';
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>OYIN WIG & HAIR</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* navbar: pure white with left logo and dropdown */
header.site-header{background:#fff;color:#071428;position:sticky;top:0;z-index:60;border-bottom:1px solid #e8edf2}
.container{max-width:1100px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;gap:12px}
.brand {display:flex;align-items:center;gap:12px}
.brand img{width:46px;height:46px;border-radius:8px;object-fit:cover}
nav.main-nav{margin-left:auto;display:flex;align-items:center;gap:8px}
nav.main-nav a{color:#071428;padding:8px 12px;border-radius:8px;text-decoration:none;font-weight:700}
nav .menu {position:relative}
nav .menu .drop{display:none;position:absolute;right:0;top:100%;background:#fff;border:1px solid #eee;padding:8px;border-radius:8px;box-shadow:0 6px 18px rgba(2,22,36,0.06)}
nav .menu:hover .drop{display:block}
.badge{background:#ff7b54;color:#fff;padding:6px 8px;border-radius:999px;font-weight:800}
</style>
</head>
<body>
<header class="site-header">
  <div class="container">
    <div class="brand">
      <img src="logo.png" alt="logo" onerror="this.src='images/placeholder.jpg'">
      <div>
        <div style="font-weight:900">OYIN WIG & HAIR</div>
        <small style="opacity:.6;font-size:.85rem">About wig and hair</small>
      </div>
    </div>

    <nav class="main-nav" aria-label="Main navigation">
      <a href="index.php">Home</a>
      <a href="shop.php">Shop</a>

      <div class="menu">
        <a href="#" class="menu-toggle">More ▾</a>
        <div class="drop">
          <a href="gallery.php">Gallery</a><br>
          <a href="feedback.php">Feedback</a><br>
          <a href="about.php">About</a><br>
          <?php if($user): ?>
            <a href="orders.php">My Orders</a><br>
            <a href="deposit.php">Deposit (Wallet: <?=money($user['wallet'] ?? 0)?>)</a><br>
            <a href="logout.php">Logout</a>
          <?php else: ?>
            <a href="signup.php">Sign up</a><br>
            <a href="signin.php">Sign in</a>
          <?php endif; ?>
        </div>
      </div>

      <a href="cart.php" class="badge">Cart (<?=cart_count()?>)</a>
    </nav>

  </div>
</header>

