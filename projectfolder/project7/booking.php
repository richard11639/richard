<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$property = trim($_POST['property_type'] ?? '');
$room_type = trim($_POST['room_type'] ?? '');
$nights = (int)($_POST['nights'] ?? 1);
$checkin = $_POST['checkin'] ?? null;
$checkout = $_POST['checkout'] ?? null;
$guests = (int)($_POST['guests'] ?? 1);
$amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
$price_per_night = (float)($_POST['price_per_night'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'paystack';

// compute amount
$amount = $price_per_night * max(1,$nights);
// optional simple amenity add-ons
if (strpos($amenities, 'pool') !== false) $amount += 2000;
if (strpos($amenities, 'spa') !== false) $amount += 5000;
if (strpos($amenities, 'parking') !== false) $amount += 1000;

// insert booking
$pdo = db();
$stmt = $pdo->prepare("INSERT INTO bookings (name,email,phone,property_type,room_type,nights,checkin,checkout,guests,amenities,amount,payment_method,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'pending')");
$stmt->execute([$name,$email,$phone,$property,$room_type,$nights,$checkin,$checkout,$guests,$amenities,$amount,$payment_method]);
$bookingId = $pdo->lastInsertId();

// store in session and redirect to payment page for card flow, or show bank instructions
session_start();
$_SESSION['booking_id'] = $bookingId;
$_SESSION['booking_amount'] = $amount;
$_SESSION['booking_email'] = $email;

if ($payment_method === 'paystack') {
    header('Location: pay.php'); exit;
} else {
    // show bank transfer instructions and booking summary (simple)
    ?>
    <!doctype html>
    <html lang="en"><head><meta charset="utf-8"/><title>Bank transfer — Booking <?=$bookingId?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
    <body class="bg-dark text-light">
    <div class="container py-5">
      <div class="card p-4">
        <h3>Booking Created — Reference #<?=$bookingId?></h3>
        <p class="smallmuted">Please pay by bank transfer using the details below. Once you send proof, email reservations@marrettikeja.example or WhatsApp +2348001234567 and we'll confirm your booking.</p>
        <ul>
          <li>Bank: First Bank Nigeria Plc</li>
          <li>Account name: Marrett Ikeja</li>
          <li>Account number: 0123456789</li>
          <li>Amount: ₦<?=number_format($amount)?></li>
          <li>Booking reference: <strong><?=$bookingId?></strong></li>
        </ul>
        <p><a class="btn btn-primary" href="index.php">Return to site</a></p>
      </div>
    </div>
    </body></html>
    <?php
}
