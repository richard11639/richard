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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <a href="restaurant.php">home</a>
  <title>About the Restaurant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #000; color: #fff; font-family: Arial, sans-serif; }
    h1, h2 { color: #f5c542; }
    .carousel-item img { height: 400px; object-fit: cover; border-radius: 15px; }
    .gallery img { width: 100%; border-radius: 10px; transition: 0.3s; }
    .gallery img:hover { transform: scale(1.05); }
    .divider { height: 2px; background: linear-gradient(to right, transparent, #444, transparent); margin: 40px 0; }
  </style>
</head>
<body>

  <!-- About the Restaurant -->
  <section class="container py-5">
    <div class="row mb-5">
      <div class="col-lg-6">
        <h1>Welcome to <span style="color:#f5c542;">Our Restaurant</span></h1>
        <p class="lead">
          Experience the taste of tradition and modern culinary art in a warm, stylish setting.
          We serve delicious meals crafted from the freshest ingredients, offering a perfect blend 
          of flavor, comfort, and hospitality.
        </p>
        <ul>
          <li>Authentic recipes & signature dishes</li>
          <li>Fresh, locally sourced ingredients</li>
          <li>Fine dining and casual experiences</li>
          <li>Cozy, elegant atmosphere</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <img src="images/image59.jpg" alt="Restaurant Interior" class="img-fluid rounded shadow">
      </div>
    </div>

    <div class="divider"></div>

    <!-- 5 Moving Images (Carousel) -->
    <h2 class="mb-3">Inside Our Restaurant</h2>
    <div id="restaurantCarousel" class="carousel slide mb-5" data-bs-ride="carousel" data-bs-interval="3000">
      <div class="carousel-inner">
        <div class="carousel-item active"><img src="images/image61.jpg" class="d-block w-100" alt="Dining area"></div>
        <div class="carousel-item"><img src="images/image62.jpg" class="d-block w-100" alt="Delicious meal"></div>
        <div class="carousel-item"><img src="images/image63.jpg" class="d-block w-100" alt="Restaurant staff"></div>
        <div class="carousel-item"><img src="images/image64.jpg" class="d-block w-100" alt="Family dining"></div>
        <div class="carousel-item"><img src="images/image65.jpg" class="d-block w-100" alt="Outdoor seating"></div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#restaurantCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#restaurantCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>

    <div class="divider"></div>
<!-- 20 Single Images Gallery -->
    <h2 class="mb-3">Gallery</h2>
    <div class="row g-3 gallery">
      <!-- Repeat these blocks with your images (20 total) -->
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 1"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 2"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 3"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 4"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 5"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 6"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 7"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 8"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 9"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 10"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 11"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 12"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 13"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 14"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 15"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 16"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 17"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 18"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 19"></div>
      <div class="col-6 col-md-4 col-lg-3"><img src="images/image68.jpg" alt="Gallery Image 20"></div>
    </div>
  </section>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    </div>
  </div>
</section>
