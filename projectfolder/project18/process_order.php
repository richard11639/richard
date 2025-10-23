<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: cart.php'); exit; }

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) { header('Location: cart.php'); exit; }

$subtotal = 0; foreach($cart as $it) $subtotal += $it['price'] * $it['qty'];
$delivery_fee = 1500.00; $tax = round($subtotal * 0.05,2); $total = $subtotal + $delivery_fee + $tax;

$payment_method = $_POST['payment_method'] ?? 'Cash';
$user_id = $_SESSION['user_id'] ?? null;

// Wallet check
if ($payment_method === 'Wallet') {
    if (!$user_id) { $_SESSION['flash_error'] = 'Please sign in to pay with wallet.'; header('Location: signin.php'); exit; }
    $user = current_user();
    if (!$user || $user['balance'] < $total) { $_SESSION['flash_error'] = 'Insufficient wallet balance. Please deposit.'; header('Location: deposit.php'); exit; }
}

// handle proof upload
$proofPath = null;
if (!empty($_FILES['transfer_proof']['tmp_name'])) {
    $f = $_FILES['transfer_proof'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['png','jpg','jpeg','webp'];
        if (in_array($ext, $allowed)) {
            $destDir = __DIR__ . '/uploads'; if(!is_dir($destDir)) mkdir($destDir,0755,true);
            $name = 'proof_'.time().'_'.rand(100,999).'.'.$ext;
            move_uploaded_file($f['tmp_name'],$destDir.'/'.$name);
            $proofPath = 'uploads/'.$name;
        }
    }
}

// Insert order
$stmt = $pdo->prepare('INSERT INTO orders (user_id,subtotal,delivery_fee,tax,total,payment_method,payment_proof,order_type,when_type,when_time,address_street,address_no,address_city,address_zip,address_apt,address_floor,company_name,first_name,last_name,phone,email,note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

$order_type = $_POST['order_type'] ?? 'Delivery';
$when_type = $_POST['when_type'] ?? 'ASAP';
$when_time = $_POST['when_time'] ?? null;

$address_street = $_POST['address_street'] ?? null;
$address_no = $_POST['address_no'] ?? null;
$address_city = $_POST['address_city'] ?? null;
$address_zip = $_POST['address_zip'] ?? null;
$address_apt = $_POST['address_apt'] ?? null;
$address_floor = $_POST['address_floor'] ?? null;
$company_name = $_POST['company_name'] ?? null;
$first_name = $_POST['first_name'] ?? null;
$last_name = $_POST['last_name'] ?? null;
$phone = $_POST['phone'] ?? null;
$email = $_POST['email'] ?? null;
$note = $_POST['comment'] ?? null;

$stmt->execute([
    $user_id,$subtotal,$delivery_fee,$tax,$total,$payment_method,$proofPath,$order_type,$when_type,$when_time,
    $address_street,$address_no,$address_city,$address_zip,$address_apt,$address_floor,$company_name,
    $first_name,$last_name,$phone,$email,$note
]);

$order_id = $pdo->lastInsertId();

// Insert items
$itStmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,title,price,qty) VALUES (?,?,?,?,?)');
foreach($cart as $it) {
    $itStmt->execute([$order_id,$it['id'],$it['title'],$it['price'],$it['qty']]);
}

// Wallet deduction
if ($payment_method === 'Wallet' && $user_id) {
    $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')->execute([$total,$user_id]);
}

// Clear cart
unset($_SESSION['cart']);

// If Bank transfer, suggest sending proof; redirect to order_success
header('Location: order_success.php?order='.$order_id);
exit;
