<?php
session_start();
include 'auth.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch blog posts including blog_title
$sql = "SELECT 
            tblblog.blog_id,
            tblblog.blog_title,
            tblblog.blog_content, 
            tblblog.date_posted, 
            tbluser.user_id AS posted_by, 
            tbluser.username AS posted_by_username
        FROM tblblog 
        JOIN tbluser ON tblblog.posted_by = tbluser.user_id 
        WHERE tblblog.blog_status = 'active'
        ORDER BY tblblog.date_posted DESC";

$result = $mysql->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<a href="hotel.php">home</a>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book Now - Luxury Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #000; color: #fff; font-family: 'Segoe UI', sans-serif; }
    h1, h2 { color: #f5c542; font-weight: bold; }
    .room-card { background: #111; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
    .room-card img { width: 100%; border-radius: 12px; height: 250px; object-fit: cover; }
    .btn-custom { background-color: #f5c542; color: #000; font-weight: bold; }
    .btn-custom:hover { background-color: #d4a617; color: #fff; }
    .form-section { background: #111; padding: 30px; border-radius: 15px; margin-top: 40px; }
    label { font-weight: bold; }
    .divider { height: 2px; background: linear-gradient(to right, transparent, #555, transparent); margin: 40px 0; }
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="text-center mb-5">Book Your Stay at Our Hotel</h1>

  <!-- Room Details -->
  <div class="row">
    <div class="col-md-6">
      <div class="room-card">
        <img src="images/single-room.jpg" alt="Single Room">
        <h3 class="mt-3">Single Room</h3>
        <p>Perfect for solo travelers, our single rooms offer comfort and privacy with a cozy design.</p>
        <p><strong>Price:</strong> 80k / night</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="room-card">
        <img src="images/double-room.jpg" alt="Double Room">
        <h3 class="mt-3">Double Room</h3>
        <p>Ideal for couples or friends, double rooms come with elegant interiors and modern facilities.</p>
        <p><strong>Price:</strong> 120k / night</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="room-card">
        <img src="images/luxury-suite.jpg" alt="Luxury Suite">
        <h3 class="mt-3">Luxury Suite</h3>
        <p>Experience unmatched luxury with spacious living, dining, and bedroom areas plus premium services.</p>
        <p><strong>Price:</strong> 250k / night</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="room-card">
        <img src="images/presidential-suite.jpg" alt="Presidential Suite">
        <h3 class="mt-3">Presidential Suite</h3>
        <p>The ultimate luxury experience featuring private lounge, jacuzzi, and personal butler service.</p>
        <p><strong>Price:</strong> 500k / night</p>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- Booking Form -->
  <h2 class="mb-4">Complete Your Booking</h2>
  <div class="form-section">
    <form action="book.php" method="POST">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="name">Full Name</label>
          <input type="text" class="form-control" name="name" required>
        </div>
        <div class="col-md-6">
          <label for="email">Email Address</label>
          <input type="email" class="form-control" name="email" required>
        </div>
        <div class="col-md-6">
          <label for="checkin">Check-In Date</label>
          <input type="date" class="form-control" name="checkin" required>
        </div>
        <div class="col-md-6">
          <label for="checkout">Check-Out Date</label>
          <input type="date" class="form-control" name="checkout" required>
        </div>
        <div class="col-md-6">
          <label for="room">Room Type</label>
          <select class="form-select" name="room" required>
            <option value="">Select</option>
            <option value="Single Room">Single Room - 80k/night</option>
            <option value="Double Room">Double Room - 120k/night</option>
            <option value="Luxury Suite">Luxury Suite - 250k/night</option>
            <option value="Presidential Suite">Presidential Suite - 500k/night</option>
          </select>
        </div>
        <div class="col-md-6">
          <label for="guests">Number of Guests</label>
          <input type="number" class="form-control" name="guests" min="2" required>
        </div>
      </div>
      <button type="submit" class="btn btn-custom mt-4">Confirm Booking</button>
    </form>
  </div>
</div>
</body>
</html>
