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
  <h4><a href="realestate.php">Home</a></h4>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Real Estate Inquiry Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #000;
      color: #fff;
      font-family: "Segoe UI", sans-serif;
    }
    h1 {
      text-align: center;
      color: #f5c542;
      margin-bottom: 30px;
    }
    .form-section {
      background: #111;
      padding: 30px;
      border-radius: 15px;
      max-width: 800px;
      margin: auto;
      box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
    }
    label {
      font-weight: bold;
    }
    .btn-custom {
      background-color: #f5c542;
      color: #000;
      font-weight: bold;
    }
    .btn-custom:hover {
      background-color: #d4a617;
      color: #fff;
    }
  </style>
</head>
<body>

<div class="container py-5">
  <h1>Real Estate Property Details Form</h1>
  
  <div class="form-section">
    <form action="real-estate-save.php" method="POST">
      
      <!-- Personal Info -->
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control" name="name" placeholder="Enter your full name" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
      </div>

      <div class="mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <input type="text" class="form-control" name="phone" placeholder="Enter your phone number" required>
      </div>

      <!-- Property Details -->
      <div class="mb-3">
        <label for="propertyType" class="form-label">Property Type</label>
        <select class="form-select" name="propertyType" required>
          <option value="">Select property type</option>
          <option value="Apartment">Apartment</option>
          <option value="Flat">Flat</option>
          <option value="Duplex">Duplex</option>
          <option value="Bungalow">Bungalow</option>
          <option value="Luxury House">Luxury House</option>
          <option value="Commercial">Commercial</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="location" class="form-label">Preferred Location</label>
        <input type="text" class="form-control" name="location" placeholder="E.g. Lagos, Ikeja" required>
      </div>

      <div class="mb-3">
        <label for="budget" class="form-label">Budget (₦)</label>
        <input type="number" class="form-control" name="budget" placeholder="Enter your budget" required>
      </div>

      <div class="mb-3">
        <label for="purpose" class="form-label">Purpose</label>
        <select class="form-select" name="purpose" required>
          <option value="">Select</option>
          <option value="Buy">Buy</option>
          <option value="Rent">Rent</option>
          <option value="Lease">Lease</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="details" class="form-label">Additional Details</label>
        <textarea class="form-control" name="details" rows="4" placeholder="Tell us more about your property needs"></textarea>
      </div>

      <button type="submit" class="btn btn-custom">Submit Request</button>
    </form>
  </div>
</div>

</body>
</html>
