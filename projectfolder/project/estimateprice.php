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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
  <meta charset="UTF-8">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/styles.css">

  <style>
    body {
      background-color: #f5f5f5;
      font-family: 'Segoe UI', sans-serif;
    }

    .navbar {
      background-color: #2c3e50;
    }

    .navbar-brand, .nav-link {
      color: #fff !important;
    }

    .hero {
      background: url('https://images.unsplash.com/photo-1568605114967-8130f3a36994') no-repeat center center/cover;
      height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
    }

    .card {
      border-radius: 12px;
      transition: transform 0.2s ease;
    }

    .card:hover {
      transform: scale(1.03);
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg mb-4">
    <div class="container">
      <a class="navbar-brand" href="#">Lagos Estates</a>
      <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navLinks">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navLinks">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="estimate.php">HOME</a>
          <li class="nav-item"><a class="nav-link" href="#">Listings</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Estimate</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <div class="hero text-center">
    <h1>Find Your Dream Home in Lagos</h1>
  </div>

  <!-- Property Listings -->
  <div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">🏠 Featured Properties</h2>
    <div id="property-list" class="row gy-4">
      <!-- Properties will be injected here by JavaScript -->
    </div>
  </div>

 

  <!-- JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/main.js"></script>

  <p>| **Category** | **1 Bedroom Flat** | **2 Bedroom Flat** | **3–4 Bedroom House** | **Luxury Duplex** |
| ------------ | ------------------ | ------------------ | --------------------- | ----------------- |
| **Basic**    | ₦15M – ₦25M        | ₦25M – ₦40M        | ₦40M – ₦65M           | N/A               |
| **Modern**   | ₦30M – ₦45M        | ₦45M – ₦65M        | ₦70M – ₦120M          | ₦120M – ₦200M     |
| **Luxury**   | ₦60M+              | ₦80M+              | ₦150M – ₦400M         | ₦300M – ₦1B+      |
</p><br><br>

<p>| **Location**      | **1-Bedroom Flat** | **2-Bedroom Flat** | **Luxury Duplex** |
| ----------------- | ------------------ | ------------------ | ----------------- |
| **Lekki Phase 1** | ₦40M+              | ₦70M+              | ₦250M – ₦700M     |
| **Ikoyi**         | ₦60M+              | ₦100M+             | ₦500M – ₦1B       |
| **Ajah**          | ₦25M+              | ₦45M+              | ₦120M – ₦300M     |
| **Ikeja GRA**     | ₦35M+              | ₦60M+              | ₦200M – ₦400M     |
| **Yaba**          | ₦25M+              | ₦40M+              | ₦100M – ₦200M     |
| **Surulere**      | ₦20M+              | ₦35M+              | ₦80M – ₦150M      |
| **Ikorodu**       | ₦15M+              | ₦25M+              |                   |
</p>
     <!-- Footer -->
  <footer class="bg-dark text-white text-center py-3">
    &copy; 2025 Lagos Real Estate | All rights reserved.
  </footer>

    
</body>
</html>