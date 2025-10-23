<?php
// Ric Restaurant Website - can expand PHP later (orders, forms, DB etc.)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>RIC RESTAURANT</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f8f8f8;
      color: #333;
    }

    /* Navbar */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
      padding: 10px 30px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .navbar .logo img {
      width: 60px;
      height: auto;
      border-radius: 50%;
    }

    .navbar ul {
      list-style: none;
      display: flex;
      margin: 0;
      padding: 0;
    }

    .navbar ul li {
      margin-left: 20px;
    }

    .navbar ul li a {
      text-decoration: none;
      color: #333;
      font-weight: bold;
      text-transform: uppercase;
    }

    .navbar ul li a:hover {
      color: red;
    }

    /* Hero section */
    .hero {
      background: red;
      color: white;
      text-align: center;
      padding: 60px 20px;
    }

    .logo-plate {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: #fff;
      margin: 0 auto 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 10px solid #eee;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .logo-plate h1 {
      font-size: 40px;
      font-weight: 900;
      color: red;
      text-transform: uppercase;
      margin: 0;
    }

    .tagline {
      font-size: 22px;
      margin: 10px 0;
    }

   .order-section {
  text-align: center;
  margin: 40px 0;
}

.order-btn {
  display: inline-block;
  padding: 15px 40px;
  background: #ff0000;
  color: #fff;
  font-size: 20px;
  font-weight: bold;
  text-decoration: none;
  border-radius: 8px;
  transition: background 0.3s;
}

.order-btn:hover {
  background: #cc0000;
}

/* How It Work Section */
.how-it-work {
  margin: 60px 0;
  text-align: center;
}

.how-it-work h2 {
  background: #000;
  color: #fff;
  padding: 20px 0;
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 40px;
}

.work-steps {
  display: flex;
  justify-content: space-around;
  gap: 20px;
  flex-wrap: wrap;
}

.step {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  flex: 1;
  min-width: 250px;
  max-width: 300px;
}

.step h3 {
  color: #e60000;
  margin-bottom: 10px;
}

.step p {
  color: #333;
  font-size: 0.95rem;
}

    /* Video */
    .video-section {
      margin: 40px auto;
      max-width: 800px;
      text-align: center;
    }

    iframe {
      width: 100%;
      height: 400px;
      border-radius: 10px;
      border: none;
    }

     
    /* Socials */
    .socials {
      margin: 30px 0;
      text-align: center;
    }

    .socials a {
      margin: 0 15px;
      text-decoration: none;
      font-size: 28px;
      color: #333;
    }

    .socials a:hover {
      color: red;
    }

    /* Footer */
    footer {
      background: #bd1414ff;
      color: #fff;
      text-align: center;
      padding: 20px;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="logo">
    <img src="images/logo.png" alt="RIC Restaurant Logo">
  </div>
  <ul>
    <li><a href="#">Home</a></li>
    <li><a href="#">Order Online</a></li>
    <li><a href="#">The Hall</a></li>
    <li><a href="#">Contact</a></li>
  </ul>
</nav>

<!-- Hero Section -->
<header class="hero">
  <h1> THE WORLD ON A PLATE ON A PLATE</h1>
  <p>Delicious food straight to your door</p>
</header>

<!-- Order Online Section (moved after hero) -->
<section id="order" class="order-section">
  <a href="index.php" class="order-btn">ORDER ONLINE</a>
</section>

<!-- How It Work Section -->
<section class="how-it-work">
  <h2>HOW IT WORK</h2>
  <div class="work-steps">
    <div class="step">
      <h3>Select Nearest Location</h3>
      <p>Find the closest Ric Restaurant to get your order delivered fresh and fast.</p>
    </div>
    <div class="step">
      <h3>Choose Your Meal</h3>
      <p>Pick from our delicious menu of local and international dishes.</p>
    </div>
    <div class="step">
      <h3>Enjoy Your Meal</h3>
      <p>Sit back, relax, and let us deliver happiness right to your door.</p>
    </div>
  </div>
</section>

<!-- Video -->
<div class="video-section">
  <h2>Watch How To Order</h2>
  <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" allowfullscreen></iframe>
</div>

<!-- Socials -->
<div class="socials">
  <a href="https://facebook.com" target="_blank">📘</a>
  <a href="https://instagram.com" target="_blank">📸</a>
  <a href="https://twitter.com" target="_blank">🐦</a>
</div>

<!-- Footer -->
<footer>
  <p>Website by: Richard (Owner of Ric Restaurant)</p>
  <p>Email: ricrestaurant@example.com | Phone: +234 800 000 0000</p>
  <p>&copy; <?php echo date("Y"); ?> Ric Restaurant. All rights reserved.</p>
</footer>

<script>
// Simple slideshow
let slideIndex = 0;
const slides = document.querySelectorAll('.slides img');
function showSlides() {
  slides.forEach((img, i) => img.classList.toggle('active', i === slideIndex));
  slideIndex = (slideIndex + 1) % slides.length;
  setTimeout(showSlides, 3000);
}
showSlides();
</script>

</body>
</html>
