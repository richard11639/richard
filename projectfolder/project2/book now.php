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
  <a href="restaurant.php">home</a>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book Now - Gourmet Restaurant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #000; color: #fff; font-family: 'Segoe UI', sans-serif; }
    h1, h2 { color: #f5c542; font-weight: bold; }
    .meal-card { background: #111; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
    .meal-card img { width: 100%; border-radius: 12px; height: 220px; object-fit: cover; }
    .btn-custom { background-color: #f5c542; color: #000; font-weight: bold; }
    .btn-custom:hover { background-color: #d4a617; color: #fff; }
    .form-section { background: #111; padding: 30px; border-radius: 15px; margin-top: 40px; }
    label { font-weight: bold; }
    .divider { height: 2px; background: linear-gradient(to right, transparent, #555, transparent); margin: 40px 0; }
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="text-center mb-5">Reserve Your Table at Our Restaurant</h1>

  <!-- Restaurant Special Meals -->
  <div class="row">
    <div class="col-md-4">
      <div class="meal-card">
        <img src="images/steak.jpg" alt="Steak Dinner">
        <h3 class="mt-3">Classic Steak</h3>
        <p>Our signature grilled steak served with vegetables and wine pairing.</p>
        <p><strong>Price:</strong> 20k / person</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="meal-card">
        <img src="images/pasta.jpg" alt="Italian Pasta">
        <h3 class="mt-3">Italian Pasta</h3>
        <p>Fresh handmade pasta with rich sauce options and parmesan topping.</p>
        <p><strong>Price:</strong> 25k / person</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="meal-card">
        <img src="images/dessert.jpg" alt="Desserts">
        <h3 class="mt-3">Dessert Platter</h3>
        <p>A variety of our chef’s desserts including cakes, pastries, and ice cream.</p>
        <p><strong>Price:</strong> 15k / person</p>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- Booking Form -->
  <h2 class="mb-4">Book a Table</h2>
  <div class="form-section">
    <form action="book now.php" method="POST">
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
          <label for="date">Reservation Date</label>
          <input type="date" class="form-control" name="date" required>
        </div>
        <div class="col-md-6">
          <label for="time">Reservation Time</label>
          <input type="time" class="form-control" name="time" required>
        </div>
        <div class="col-md-6">
          <label for="guests">Number of Guests</label>
          <input type="number" class="form-control" name="guests" min="1" required>
        </div>
        <div class="col-md-6">
          <label for="meal">Meal Preference</label>
          <select class="form-select" name="meal" required>
            <option value="">Select</option>
            <option value="Classic Steak">Classic Steak - 20k</option>
            <option value="Italian Pasta">Italian Pasta - 25k</option>
            <option value="Dessert Platter">Dessert Platter - 15k</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-custom mt-4">Confirm Reservation</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
