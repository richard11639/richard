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
  <title>About the Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #000; color: #fff; font-family: 'Arial', sans-serif; }
    h1, h2 { color: #f5c542; }
    p { font-size: 1.1rem; line-height: 1.6; }
    .carousel-item img { height: 400px; object-fit: cover; border-radius: 15px; }
    .gallery img { width: 100%; border-radius: 10px; transition: 0.3s; }
    .gallery img:hover { transform: scale(1.05); }
    .divider { height: 2px; background: linear-gradient(to right, transparent, #444, transparent); margin: 40px 0; }
  </style>
</head>
<body>
    <!-- <li class="nav-item"><a class="nav-link" href="search.php">Search</a></li> -->
        <li class="nav-item"><a class="nav-link" href="restaurant.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="services.php">services</a></li>
        <li class="nav-item"><a class="nav-link" href="booknow.php">Pricing</a></li>
        <li class="nav-item"><a class="nav-link" href="menu.php">Member Book</a></li>
        <li class="nav-item"><a class="nav-link" href="pricing2.php">Order Now</a></li>
        <li class="nav-item"><a href="#" class="nav-link">moreabout</a></li>
         <li class="nav-item"><a class="nav-link" href="logout.php">LOG OUT</a></li>
      </ul>
    </div>
  </div>
</nav>

  <!-- About the Hotel -->
  <section class="container py-5">
    <div class="row mb-5">
      <div class="col-lg-6">
        <h1>About <span style="color:#f5c542;">Our Hotel</span></h1>
        <p class="lead">
          Welcome to our luxury hotel, where comfort meets elegance. Nestled in the heart of the city, 
          we provide guests with a truly unforgettable experience—whether you’re here for business, 
          leisure, or a romantic getaway.
        </p>
        <p>
          Our hotel combines modern design with warm hospitality. Each room is designed to 
          provide maximum comfort with plush bedding, state-of-the-art facilities, and beautiful views.  
        </p>
        <ul>
          <li>Elegant Rooms & Suites with premium amenities</li>
          <li>World-class Restaurant & Fine Dining</li>
          <li>Swimming Pool, Spa & Fitness Center</li>
          <li>Conference Halls & Event Spaces</li>
          <li>24/7 Concierge & Room Service</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <img src="images/image81.jpg" alt="Hotel Front View" class="img-fluid rounded shadow">
      </div>
    </div>

    <div class="divider"></div>

    <!-- 5 Moving Images (Carousel) -->
    <h2 class="mb-3">Discover Our Spaces</h2>
    <div id="hotelCarousel" class="carousel slide mb-5" data-bs-ride="carousel" data-bs-interval="3000">
      <div class="carousel-inner">
        <div class="carousel-item active"><img src="images/image82.jpg" class="d-block w-100" alt="Lobby"></div>
        <div class="carousel-item"><img src="images/image81.jpg" class="d-block w-100" alt="Luxury Room"></div>
        <div class="carousel-item"><img src="images/images83.jpg" class="d-block w-100" alt="Restaurant"></div>
        <div class="carousel-item"><img src="images/image84.jpg" class="d-block w-100" alt="Swimming Pool"></div>
        <div class="carousel-item"><img src="images/image85.jpg" class="d-block w-100" alt="Event Hall"></div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#hotelCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#hotelCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>

    <div class="divider"></div>

    <!-- 5 Single Images Gallery -->
    <h2 class="mb-3">Hotel Gallery</h2>
    <div class="row g-3 gallery">
      <div class="col-6 col-md-4 col-lg-2"><img src="images/image86.jpg" alt="Gallery 1"></div>
      <div class="col-6 col-md-4 col-lg-2"><img src="images/image87.jpg" alt="Gallery 2"></div>
      <div class="col-6 col-md-4 col-lg-2"><img src="images/image85.jpg" alt="Gallery 3"></div>
      <div class="col-6 col-md-4 col-lg-2"><img src="images/image80.jpg" alt="Gallery 4"></div>
      <div class="col-6 col-md-4 col-lg-2"><img src="images/image84.jpg" alt="Gallery 5"></div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
