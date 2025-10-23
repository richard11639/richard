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
    <title>product</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="realestate.php">Realestate</a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a href="#products" class="nav-link">Products</a></li>
          <li class="nav-item"><a href="#structure" class="nav-link">Structure</a></li>
          <li class="nav-item"><a href="#advantages" class="nav-link">Advantages</a></li>
          <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Product Section -->
  <section id="products" class="p-5 bg-light">
    <div class="container">
      <h2 class="text-center mb-4">Our Products</h2>
      <div class="row text-center">
        <div class="col-md-4 mb-3">
          <div class="card p-3 shadow">
            <h4>Luxury Apartments</h4>
            <p>Smart and stylish apartments in prime areas.</p>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card p-3 shadow">
            <h4>Modern Duplexes</h4>
            <p>Fully detached smart duplexes with flexible plans.</p>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card p-3 shadow">
            <h4>Custom Homes</h4>
            <p>Tailor-made homes with personal architectural design.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Structure Section -->
  <section id="structure" class="p-5">
    <div class="container">
      <h2 class="text-center mb-4">House Structure & Designs</h2>
      <div class="row align-items-center">
        <div class="col-md-6">
          <img src="images/image26.jpg" class="img-fluid rounded shadow" alt="Structure">
        </div>
        <div class="col-md-6">
          <ul>
            <li>Modern kitchen with smart appliances</li>
            <li>3D smart building structure</li>
            <li>Pre-installed solar energy</li>
            <li>Smart security & automation</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Advantages -->
  <section id="advantages" class="bg-success text-white p-5">
    <div class="container text-center">
      <h2>Why Choose Us?</h2>
      <div class="row">
        <div class="col-md-3">
          <h5>Smart Tech</h5>
          <p>Automation & energy-saving solutions.</p>
        </div>
        <div class="col-md-3">
          <h5>Great Location</h5>
          <p>We build in high-value areas like Lekki and Ikeja.</p>
        </div>
        <div class="col-md-3">
          <h5>Flexible Payments</h5>
          <p>Mortgage, Installments & Loans available.</p>
        </div>
        <div class="col-md-3">
          <h5>Warranty</h5>
          <p>10 years on structure, 2 years on installations.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Form -->
  <section id="contact" class="p-5 bg-light">
    <div class="container">
      <h2 class="text-center mb-4">Contact / Book Tour</h2>
      <form method="POST" action="submit.php" class="row g-3">
        <div class="col-md-6">
          <input type="text" name="name" class="form-control" placeholder="Full Name" required />
        </div>
        <div class="col-md-6">
          <input type="email" name="email" class="form-control" placeholder="Email" required />
        </div>
        <div class="col-12">
          <textarea name="message" class="form-control" placeholder="Message..." required></textarea>
        </div>
        <div class="col-12 text-center">
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </form>
    </div>
  </section>

  <footer class="text-center p-3 bg-dark text-white">
    &copy; 2025 RealEstatePro - All rights reserved
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

    
</body>
</html>