
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
  <a href="restaurant.php">home</a>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restaurant Services</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
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
      color: #ccc;
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
      color: #bbb;
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
    <h2>Our Services</h2>
    <p>We offer a variety of dining and food services to make your experience unforgettable.</p>

    <div class="service-container">
      <div class="service-box">
        <i class="fas fa-utensils"></i>
        <h3>Dine-In</h3>
        <p>Enjoy a fine dining experience with our freshly prepared meals served at your table.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-shopping-bag"></i>
        <h3>Takeaway</h3>
        <p>Order your favorite meals and pick them up quickly on the go.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-motorcycle"></i>
        <h3>Delivery</h3>
        <p>Get your meals delivered hot and fresh right to your doorstep.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-concierge-bell"></i>
        <h3>Catering</h3>
        <p>We cater to weddings, birthdays, and corporate events with customized menus.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-calendar-check"></i>
        <h3>Reservations</h3>
        <p>Book your table in advance and enjoy a hassle-free dining experience.</p>
      </div>

      <div class="service-box">
        <i class="fas fa-glass-cheers"></i>
        <h3>Bar & Drinks</h3>
        <p>Choose from a wide selection of cocktails, wines, and non-alcoholic beverages.</p>
      </div>
    </div>
  </section>

</body>
</html>
