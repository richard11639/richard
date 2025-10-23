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
    <a href="realestate.php">HOME</a>
    <title>Document</title>
    <!DOCTYPE html>
<html lang="en">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
      </div>
    <div class="carousel-item">
      <img src="images/image30.jpg" class="d-block w-100" alt="...">
    </div>
    <div class="carousel-item">
      <img src="images/image29.jpg" class="d-block w-100" alt="...">
    </div>
  </div>
  </button>
</div>
  <title>Elowen Real Estate - Featured Home</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
     <img src="images/image30.jpg" class="d-block w-100" alt="...">
    </div>
  <header class="bg-primary text-white text-center py-5">
    <h1 class="display-4">Elowen Real Estate</h1>
    <p class="lead">Discover Luxury Living with Our Signature Property</p>
  </header>

  <section class="container py-5">
    <h2 class="text-center mb-4">Welcome to The Elowen Residence</h2>
    <p>
      The Elowen Residence is more than a home—it's a statement of elegance, smart design, and superior craftsmanship. Located in the prestigious Pinewood Grove community, this house boasts timeless architecture, eco-conscious materials, and cutting-edge smart home integration. Designed for modern families, it blends comfort and luxury into one seamless experience.
    </p>
  </section>

  <section class="container py-5 bg-light">
    <h2 class="text-center mb-4">Exceptional Features You'll Love</h2>
    <div class="row">
      <div class="col-md-6">
        <ul>
          <li>✅ 4 Spacious Bedrooms & 3 Luxurious Bathrooms</li>
          <li>✅ Chef-Inspired Kitchen with Smart Appliances</li>
          <li>✅ Solar-Powered Efficiency & Battery Backup</li>
          <li>✅ Smart Home Controls (Voice, App, Panel)</li>
          <li>✅ Noise-Insulated Windows & Thermal Glass</li>
        </ul>
      </div>
      <div class="col-md-6">
        <ul>
          <li>✅ Private Office + Entertainment Room</li>
          <li>✅ Fenced Backyard with Pet Station</li>
          <li>✅ EV-Ready Garage & Storage Space</li>
          <li>✅ Designer Fixtures and Materials</li>
          <li>✅ Walking Distance to Schools & Parks</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="container py-5">
    <h2 class="text-center mb-4">Meet Your Personal Agent</h2>
    <div class="card text-center">
      <div class="card-body">
        <h4 class="card-title">NO EVIDENCE</h4>
        <p class="card-text">
          Maya Green is a seasoned expert in luxury real estate, known for her honesty, taste, and exceptional service. She’ll guide you personally through every step to ensure this home becomes your future.
        </p>
      </div>
    </div>
  </section>

  <section class="container py-5 bg-light">
    <h2 class="text-center mb-4">Take a Virtual Tour</h2>
    <div class="embed-responsive embed-responsive-16by9">
      <iframe class="embed-responsive-item" src="https://www.youtube.com/image35" allowfullscreen></iframe>
    </div>
  </section>

  <section class="container py-5">
    <h2 class="text-center mb-4">Apply to Own This Home</h2>
    <form action="apply.php" method="POST">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" class="form-control" id="name" name="name" required>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" class="form-control" id="phone" name="phone" required>
      </div>
      <div class="form-group">
        <label for="message">What interests you about this home?</label>
        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
      </div>
      <button type="submit" class="btn btn-success btn-block">Submit Application</button>
    </form>
  </section>

  <footer class="bg-dark text-white text-center py-4">
    <p>&copy; 2025 Elowen Real Estate. All rights reserved.</p>
  </footer>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="script.js"></script>
</body>
</html>
</body>
</html>