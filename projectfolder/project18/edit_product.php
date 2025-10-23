<?php
// edit_product.php — handle product edits (admin-only)
require_once 'header.php';
global $pdo;

// require admin
if (empty($_SESSION['admin_id'])) {
    $_SESSION['flash'] = 'Admin login required to edit products.';
    header('Location: shop.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash'] = 'Invalid request.';
    header('Location: shop.php');
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0.0);

if ($product_id <= 0 || $title === '' || $price <= 0) {
    $_SESSION['flash'] = 'Provide valid product id, title and price.';
    header('Location: shop.php');
    exit;
}

// handle optional image upload
$imagePath = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowedExt = ['jpg','jpeg','png','webp','gif'];
    $finfo = pathinfo($_FILES['image']['name']);
    $ext = strtolower($finfo['extension'] ?? '');
    if (!in_array($ext, $allowedExt)) {
        $_SESSION['flash'] = 'Invalid image type. Allowed: ' . implode(', ', $allowedExt);
        header('Location: shop.php'); exit;
    }
    // ensure uploads dir exists
    $uDir = __DIR__ . '/uploads';
    if (!is_dir($uDir)) mkdir($uDir, 0755, true);
    $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $uDir . '/' . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        $_SESSION['flash'] = 'Failed to save uploaded image.';
        header('Location: shop.php'); exit;
    }
    // relative path saved to DB
    $imagePath = 'uploads/' . $filename;
}

// build update query (with or without image)
if ($imagePath) {
    $sql = "UPDATE products SET title = :t, description = :d, price = :p, image = :img WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t'=>$title, ':d'=>$description, ':p'=>$price, ':img'=>$imagePath, ':id'=>$product_id]);
} else {
    $sql = "UPDATE products SET title = :t, description = :d, price = :p WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t'=>$title, ':d'=>$description, ':p'=>$price, ':id'=>$product_id]);
}

$_SESSION['flash'] = 'Product updated.';
header('Location: shop.php');
exit;
