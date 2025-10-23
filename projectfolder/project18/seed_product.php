<?php
// seed_products.php
// Run safely: it only inserts if there are fewer than 100 products

require_once __DIR__ . '/db.php';

try {
    $count = $pdo->query('SELECT count(*) FROM products')->fetchColumn();
} catch (Exception $e) {
    // If table doesn't exist yet, let db.php's table-creation run first, then try again.
    $count = 0;
}

if ($count >= 100) return;

$pdo->beginTransaction();
$insert = $pdo->prepare('INSERT INTO products (title,price,image,description) VALUES (?,?,?,?)');

for ($i = 1; $i <= 100; $i++) {
    $title = "Wig Model #" . str_pad($i, 3, '0', STR_PAD_LEFT);
    $price = rand(15000, 180000); // sample prices
    // adjust these paths to images you actually have; placeholder names used
    $image = "images/wigs/wig_" . ($i % 12 + 1) . ".jpg";
    $desc = "Premium 100% human hair wig — model $i. High quality, seamless lace.";
    $insert->execute([$title, $price, $image, $desc]);
}

$pdo->commit();
