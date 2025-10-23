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
  <title>Luxury Restaurant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #000;
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
    }
    .navbar {
      background-color: #fff !important;
    }
    .navbar .nav-link {
      color: #000 !important;
      font-weight: 500;
      margin-right: 15px;
    }
    .hero-section {
      text-align: center;
      padding: 60px 20px;
    }
    .hero-section img {
      border-radius: 15px;
      margin: 10px;
      height: 250px;
      object-fit: cover;
    }
    .carousel-item img {
      object-fit: cover;
      height: 80vh;
    }
    .about-section {
      background: url('restaurant-bg.jpg') no-repeat center center/cover;
      padding: 80px 20px;
      text-align: center;
      color: #fff;
    }
    .about-section h2 {
      font-size: 2.5rem;
      margin-bottom: 20px;
    }
    footer {
      background: #111;
      color: #aaa;
      padding: 15px;
      text-align: center;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <a class="navbar-brand fw-bold text-dark" href="#">🍽️ Luxury Dinner</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <i class="bi bi-search"></i>
        <li>
        <div class="search-container">
          <input type="text" class="search-input" placeholder="Search...">
          <button class="search-button">Search</button>
        </div>
        </li>
        <!-- <li class="nav-item"><a class="nav-link" href="search.php">Search</a></li> -->
        <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="hotel.php">Hotel</a></li>
        <li class="nav-item"><a class="nav-link" href="service.php">services</a></li>
        <li class="nav-item"><a class="nav-link" href="pricing.php">Order Now</a></li>
        <li class="nav-item"><a class="nav-link" href="book now.php">Pricing</a></li>
        <li class="nav-item"><a class="nav-link" href="memberbook.php">member book </a></li>
        <li class="nav-item"><a href="#" class="nav-link">moreabout</a></li>
         <li class="nav-item"><a class="nav-link" href="logout.php">LOG OUT</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Moving Picture (Carousel) + Story -->
<section class="my-5">
  <div id="restaurantCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="images/image57.jpg" class="d-block w-100" alt="Restaurant Interior 1">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-3">
          <h3>WELCOME TO OUR RESTURANT</h3>
          <p>Your goal to spot for great food and good times.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="images/image58.jpg" class="d-block w-100" alt="Restaurant Interior 2">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-3">
          <h3>DELIGHTFUL EXPERIENCE</h3>
          <p>Our restaurant blends tradition with modern design for a unique experience.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="images/image59.jpg" class="d-block w-100" alt="Restaurant Interior 3">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-3">
          <h3>Exquisite Taste</h3>
          <p>From local favorites to international cuisines, we serve perfection.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3 Pictures + Story -->
<section class="hero-section">
  <h1 class="mb-4">Welcome to Luxury Dine</h1>
  <p class="mb-5">A place where delicious food meets an unforgettable dining experience.</p>
  <div class="d-flex justify-content-center flex-wrap">
    <img src="images/image50.jpg" alt="Delicious Food 1" class="img-fluid">
    <img src="images/image51.jpg" alt="Delicious Food 2" class="img-fluid">
    <img src="images/image52.jpg" alt="Delicious Food 3" class="img-fluid">
  </div>
</section>
<!-- Moving Picture (Carousel) + Story -->
<section class="my-5">
  <div id="restaurantCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="images/image53.jpg" class="d-block w-100" alt="Restaurant Interior 1">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-3">
          <h3>Fine Dining Experience</h3>
          <p>Enjoy meals crafted by world-class chefs in an elegant atmosphere.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="images/image54.jpg" class="d-block w-100" alt="Restaurant Interior 2">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-3">
          <h3>Elegant Ambience</h3>
          <p>Our restaurant blends tradition with modern design for a unique experience.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="images/image56.jpg" class="d-block w-100" alt="Restaurant Interior 3">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-3">
          <h3>Exquisite Taste</h3>
          <p>From local favorites to international cuisines, we serve perfection.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- About Section with Background -->
<section class="about-section">
  <h2>About Our Restaurant</h2>
  <p>
    Established with passion, Luxury Dine is more than just a restaurant — it's an experience.  
    We focus on fresh ingredients, authentic recipes, and a cozy environment for our guests.  
    Whether you're here for a casual dinner or a luxury event, we ensure your time with us is unforgettable.
  </p>
</section>



<!-- Footer -->
<footer>
  &copy; 2025 Luxury Dine. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
