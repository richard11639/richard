<?php
require_once 'header.php';
global $pdo;
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$action = strtolower($action);

if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
    $pid = intval($_POST['product_id'] ?? 0);
    $qty = max(1, intval($_POST['qty'] ?? 1));
    if ($pid <= 0) { header('Location: index.php'); exit; }

    // use DB canonical data when possible
    $stmt = $pdo->prepare("SELECT id,title,price,image FROM products WHERE id=? LIMIT 1");
    $stmt->execute([$pid]);
    $row = $stmt->fetch();

    if ($row) {
        $title = $row['title']; $price = floatval($row['price']); $image = $row['image'];
    } else {
        $title = $_POST['title'] ?? 'Product '.$pid; $price = floatval($_POST['price'] ?? 0); $image = $_POST['image'] ?? null;
    }

    if (!isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid] = ['qty'=>$qty,'title'=>$title,'price'=>$price,'image'=>$image];
    } else {
        $_SESSION['cart'][$pid]['qty'] += $qty;
    }

    $_SESSION['flash'] = 'Added to cart';
    $ref = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $ref"); exit;
}

if ($action === 'remove' && $_SERVER['REQUEST_METHOD']==='POST') {
    $pid = intval($_POST['product_id'] ?? 0);
    if ($pid>0) unset($_SESSION['cart'][$pid]);
    header('Location: cart.php'); exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD']==='POST') {
    $qtys = $_POST['qty'] ?? [];
    if (is_array($qtys)) {
        foreach ($qtys as $pid => $q) {
            $pid = intval($pid); $q = max(1,intval($q));
            if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid]['qty'] = $q;
        }
    }
    header('Location: cart.php'); exit;
}

if ($action === 'clear') {
    unset($_SESSION['cart']);
    header('Location: cart.php'); exit;
}

header('Location: index.php'); exit;

