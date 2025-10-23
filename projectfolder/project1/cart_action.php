<?php
// cart_action.php
session_start();
require_once 'db.php';

$action = $_POST['action'] ?? '';
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($action === 'add') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($pid > 0) {
        if (!isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid] = 0;
        $_SESSION['cart'][$pid] += 1;
    }
    header('Location: index.php');
    exit;
}

if ($action === 'remove') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($pid > 0) {
        unset($_SESSION['cart'][$pid]);
    }
    header('Location: index.php');
    exit;
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header('Location: index.php');
    exit;
}

header('Location: index.php');
exit;

