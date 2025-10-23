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
    <title>realestate</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" integrity="sha384-7qAoOXltbVP82dhxHAUje59V5r2YsVfBafyUDxEdApLPmcdhBPg1DKg1ERo0BZlK" crossorigin="anonymous"></script>
    
<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Estate Company</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .hero {
            background: url('https://via.placeholder.com/1400x400') no-repeat center center;
            background-size: cover;
            color: white;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-shadow: 2px 2px 4px #000;
        }
        .section-title {
            margin-top: 40px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">REALESTATE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="product.php">product</a></li>
            <li class="nav-item"><a class="nav-link" href="info.php">info </a></li>
            <li class="nav-item"><a class="nav-link" href="estimate.php">Estimate</a></li>
            <li class="nav-item"><a class="nav-link" href="dime.php">design</a></li>
            <li class="nav-item"><a class="nav-link" href="decoration.php">Decoration</a></li>                 
            <li class="nav-item"><a class="nav-link" href="design.php">prising</a></li>
            <li class="nav-item"><a class="nav-link" href="#smart">Smart Tech</a></li>
            <li class="nav-item"><a class="nav-link" href="form.php">form </a></l1>
            <li class="nav-item"><a class="nav-link btn-secondary" href="logout.php">Log Out</a></li>
                </ul>
            </div>
        </div>
    </nav>

     <h2 class="section-title" id="gallery">Estate image</h2>
<div id="carouselExampleIndicators" class="carousel slide">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="images/image31.jpg" class="d-block w-100" alt="...">
    </div>
    <div class="carousel-item">
      <img src="images/image30.jpg" class="d-block w-100" alt="...">
    </div>
    <div class="carousel-item">
      <img src="images/image29.jpg" class="d-block w-100" alt="...">
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div><br><br>
<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luxury Real Estate</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #000; /* Black background */
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
    }
    .left-panel {
      background: rgba(0,0,0,0.85);
      padding: 40px;
      height: 100vh;
      overflow-y: auto;
    }
    .carousel-item img {
      object-fit: cover;
      height: 100vh;
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

<div class="container-fluid">
  <div class="row">
    
    <!-- Left Side -->
    <div class="col-md-5 left-panel d-flex flex-column justify-content-center">
      <h1 class="mb-4">Luxury Real Estate</h1>
      <p>
        Experience modern living in our luxury estates, designed with smart technology, 
        eco-friendly features, and prime locations across Lagos.
      </p>
      <ul class="mt-3">
        <li>✔ Smart Home Automation</li>
        <li>✔ Solar Energy Integration</li>
        <li>✔ Prime Locations (Lekki, Ikeja, Ajah)</li>
        <li>✔ Flexible Payment Plans</li>
        <li>✔ 10-Year Structural Warranty</li>
      </ul>
      <a href="" class=></a>
    </div>

    <!-- Right Side (Big Images) -->
    <div class="col-md-7 p-0">
      <div id="estateCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="images/image26.jpg" class="d-block w-100" alt="Luxury House 1">
          </div>
          <div class="carousel-item">
            <img src="images/image27.jpg" class="d-block w-100" alt="Luxury House 2">
          </div>
          <div class="carousel-item">
            <img src="images/image28.jpg" class="d-block w-100" alt="Luxury House 3">
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html><br><br>
    <section class="hero">
        <div class="container text-center">
          <div class="row align-items-start mb-4">
            <div class="col">
              <img src="images/image22.jpg" alt="">
            </div>
            <div class="col">
              <img src="images/image26.jpg" alt="">
            </div>
            <div class="col">
              <img src="images/image24.jpg" alt="">
            </div>
        </div>

        <div class="row align-items-start">
            <div class="col">
              <img src="images/image25.jpg" alt="">
            </div>
            <div class="col">
              <img src="images/image26.jpg" alt="">
            </div>
            <div class="col">
              <img src="images/image27.jpg" alt="">
            </div>
        </div>
      </div>
      
    </section>
    <div class="container">
        <h2 class="section-title" id="buy">Buy a Home</h2>
        <p>Browse through listings to find your ideal home...</p>

        <h2 class="section-title" id="sell">Sell a Home</h2>
        <p>List your home with us for maximum exposure...</p>

        <h2 class="section-title" id="estimate">Home Estimate</h2>
        <form action="estimate.php" method="POST">
            <input type="text" name="location" placeholder="Location" class="form-control mb-2">
            <input type="number" name="size" placeholder="Size in sqft" class="form-control mb-2">
            <button type="submit" class="btn btn-primary">Get Estimate</button>
        </form>

        <header>
    <p>Discover modern, comfortable, and affordable housing</p>
  </header>
  <!-- Info Section -->
  <div class="info">
    <h2>About Our Real Estate</h2>
    <p>
      We provide top-quality homes with modern designs and smart features.
      Whether you are looking to buy, rent, or lease, we have a wide range of options
      tailored to your budget and lifestyle.
    </p>

    <!-- Advantages -->
    <div class="advantages">
      <h3>Advantages</h3>
      <ul>
        <li>Modern architectural designs</li>
        <li>Affordable payment plans</li>
        <li>Prime locations close to city centers</li>
        <li>Smart home technology included</li>
        <li>Good resale value</li>
      </ul>
    </div>

    <!-- Disadvantages -->
    <div class="disadvantages">
      <h3>Disadvantages</h3>
      <ul>
        <li>High demand may increase prices</li>
        <li>Limited availability in some areas</li>
        <li>Maintenance costs for luxury houses</li>
      </ul>
    </div>

        <h2 class="section-title" id="loans">Home Loans</h2>
        <p>Use our loan calculator or apply directly...</p>

        <h2 class="section-title" id="design">House Designs</h2>
        <p>Explore beautiful modern and smart home designs...</p>

        <h2 class="section-title" id="decor">Interior Decoration</h2>
        <p>Get tips and hire pros for stunning interiors...</p>

        <h2 class="section-title" id="types">Estate Types</h2>
        <p>From Villas to Apartments, explore your options...</p>

        <h2 class="section-title" id="pricing">Pricing & Charges</h2>
        <p>Understand the full cost of your dream home...</p>

        <h2 class="section-title" id="smart">Smart Technologies</h2>
        <ul>
            <li>Smart locks</li>
            <li>Security cameras</li>
            <li>Voice assistants</li>
           <li>Smart lighting</li>
            <li>Solar panels</li>
        </ul>
        

        <h2 class="section-title" id="gallery">Gallery</h2>
<div id="carouselExampleIndicators" class="carousel slide">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="images/image31.jpg" class="d-block w-100" alt="...">
    </div>
    <div class="carousel-item">
      <img src="images/image30.jpg" class="d-block w-100" alt="...">
    </div>
    <div class="carousel-item">
      <img src="images/image29.jpg" class="d-block w-100" alt="...">
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
  </div>
    <div class="container text-center">
  <div class="row align-items-start">
    <div class="col">
      how it work
    </div>
    <div class="col">
       <u>learn more</u>
    </div>
    <div class="col">
       <u>contact</u> 
      09021427575
      07050672951
    </div>

</body>
</html>