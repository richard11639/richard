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
  <a href="hotel.php">home</a>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Services</title>
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background: #111;
      color: #fff;
    }

    section.services {
      padding: 60px 20px;
      text-align: center;
      background: #1c1c1c;
    }

    section.services h2 {
      font-size: 2.5rem;
      margin-bottom: 20px;
      color: #f39c12;
    }

    section.services p {
      max-width: 600px;
      margin: 0 auto 40px;
      font-size: 1rem;
      color: #bbb;
    }

    .service-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      max-width: 1100px;
      margin: auto;
    }

    .service-box {
      background: #222;
      padding: 30px 20px;
      border-radius: 15px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .service-box:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    }

    .service-box i {
      font-size: 40px;
      color: #f39c12;
      margin-bottom: 15px;
    }

    .service-box h3 {
      font-size: 1.4rem;
      margin-bottom: 10px;
      color: #fff;
    }

    .service-box p {
      font-size: 0.95rem;
      color: #ccc;
    }

    @media (max-width: 600px) {
      section.services h2 {
        font-size: 2rem;
      }
    }
  </style>

  <!-- FontAwesome for icons -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

  <section class="services">
    <h2>Our Hotel Services</h2>
    <p>We provide a wide range of facilities to make your stay comfortable and memorable.</p>

    <div class="service-container">
      <div class="service-box">
        <i class="fas fa-bed"></i>
        <h3>Room Accommodation</h3>
        <p>Comfortable rooms ranging from Single to Luxury Suites, cleaned daily.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-concierge-bell"></i>
        <h3>Room Service</h3>
        <p>Enjoy 24/7 in-room dining with a wide variety of cuisines and drinks.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-utensils"></i>
        <h3>Restaurant & Bar</h3>
        <p>Fine dining restaurant and bar offering local and international dishes.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-spa"></i>
        <h3>Spa & Wellness</h3>
        <p>Relax with spa treatments, sauna, massage, and a modern fitness center.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-swimmer"></i>
        <h3>Swimming Pool</h3>
        <p>Take a dip in our outdoor/indoor pools with a beautiful relaxing atmosphere.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-bus"></i>
        <h3>Transport & Parking</h3>
        <p>Free parking and shuttle services including airport pick-up and drop-off.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-briefcase"></i>
        <h3>Conference Halls</h3>
        <p>Spacious event halls for meetings, weddings, and corporate gatherings.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-wifi"></i>
        <h3>Free Wi-Fi</h3>
        <p>High-speed internet access throughout the hotel for all guests.</p>
      </div>
    </div>
  </section>

</body>
</html>
