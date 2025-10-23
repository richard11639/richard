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
    <h4><a href="realestate.php">REALESTATE</a></h4>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DreamHome Real Estate</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, sans-serif;
    }
    header {
      background: url('https://images.unsplash.com/photo-1505691723518-36a5ac3be353') no-repeat center center/cover;
      height: 90vh;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }
    header h1 {
      font-size: 4rem;
      font-weight: bold;
      text-shadow: 2px 2px 5px rgba(0,0,0,0.7);
    }
    .property-card {
      transition: transform .3s;
    }
    .property-card:hover {
      transform: scale(1.05);
    }
    footer {
      background: #111;
      color: #fff;
      padding: 20px;
      text-align: center;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#">DreamHome</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
        <li class="nav-item"><a href="#properties" class="nav-link">Properties</a></li>
        <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
  <li class="nav-item"><a href="realestate.php" class="nav-link">home</a>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<header>
  <div>
    <h1>Find Your Perfect Home</h1>
    <p>Luxury Apartments, Modern Homes, and Affordable Housing</p>
    <a href="#properties" class="btn btn-primary btn-lg mt-3">Browse Properties</a>
  </div>
</header>

<!-- About Section -->
<section id="about" class="py-5 container">
  <div class="row">
    <div class="col-md-6">
      <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c" class="img-fluid rounded shadow" alt="About Us">
    </div>
    <div class="col-md-6 d-flex align-items-center">
      <div>
        <h2>About DreamHome</h2>
        <p>We are one of Nigeria’s leading real estate companies, offering modern and affordable homes for families, investors, and businesses. Our mission is to make luxury living accessible to everyone, with a variety of estates ranging from budget-friendly apartments to high-end mansions.</p>
        <p>With 10+ years of experience, we provide trusted home buying, selling, and rental services with transparent processes and flexible payment plans.</p>
      </div>
    </div>
  </div>
</section>

<!-- Properties Section -->
<section id="properties" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">Our Featured Properties</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card property-card shadow">
          <img src="images/image23.jpg" class="card-img-top" alt="Luxury Villa">
          <div class="card-body">
            <h5 class="card-title">Luxury Villa</h5>
            <p class="card-text">5 Bedrooms • Swimming Pool • Smart Home</p>
            <p><strong>₦150,000,000</strong></p>
            <a href="#" class="btn btn-primary">View Details</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card property-card shadow">
          <img src="https://images.unsplash.com/photo-1570129477492-45c003edd2be" class="card-img-top" alt="Modern Apartment">
          <div class="card-body">
            <h5 class="card-title">Modern Apartment</h5>
            <p class="card-text">3 Bedrooms • City View • 24/7 Power</p>
            <p><strong>₦45,000,000</strong></p>
            <a href="#" class="btn btn-primary">View Details</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card property-card shadow">
          <img src="https://images.unsplash.com/photo-1568605114967-8130f3a36994" class="card-img-top" alt="Affordable Flat">
          <div class="card-body">
            <h5 class="card-title">Affordable Flat</h5>
            <p class="card-text">2 Bedrooms • Secure Estate • Easy Access</p>
            <p><strong>₦15,000,000</strong></p>
            <a href="#" class="btn btn-primary">View Details</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-5 container">
  <h2 class="text-center mb-4">Pricing Packages</h2>
  <div class="row text-center">
    <div class="col-md-4">
      <div class="card shadow p-4">
        <h4>Basic Homes</h4>
        <p>Starting from ₦10,000,000</p>
        <p>Perfect for small families and first-time buyers.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow p-4">
        <h4>Modern Homes</h4>
        <p>Starting from ₦40,000,000</p>
        <p>Stylish homes with advanced facilities and smart living.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow p-4">
        <h4>Luxury Homes</h4>
        <p>Starting from ₦120,000,000</p>
        <p>High-end villas and estates for ultimate comfort.</p>
      </div>
    </div>
  </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">Get In Touch</h2>
    <div class="row">
      <div class="col-md-6">
        <form>
          <div class="mb-3">
            <input type="text" class="form-control" placeholder="Your Name" required>
          </div>
          <div class="mb-3">
            <input type="email" class="form-control" placeholder="Your Email" required>
          </div>
          <div class="mb-3">
            <textarea class="form-control" rows="4" placeholder="Your Message"></textarea>
          </div>
          <button class="btn btn-primary">Send Message</button>
        </form>
      </div>
      <div class="col-md-6">
        <h5>Our Office</h5>
        <p>123 Ikoyi Crescent, Lagos, Nigeria</p>
        <p>Email: Ogundelerichard202@gmail.com.com</p>
        <p>Phone: +2349021427575</p>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <p>&copy; 2025 DreamHome Real Estate | Designed by You</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
