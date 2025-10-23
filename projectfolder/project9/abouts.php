<?php
// about.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - Eko Hotels & Suites</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #ffffff;
      color: #111;
      line-height: 1.7;
    }
    header {
      background: url('images/hotel-banner.jpg') no-repeat center center/cover; /* Long background image */
      padding: 40px 20px;
      text-align: center;
      position: relative;
      color: #fff;
    }
    header h1 {
      font-size: 2.5rem;
      letter-spacing: 2px;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
    }
    header img {
      width: 100px;
      height: auto;
      margin-bottom: 15px;
    }
    nav {
      margin-top: 20px;
      background: rgba(0,0,0,0.6);
      padding: 10px 0;
      border-radius: 8px;
      display: inline-block;
    }
    nav a {
      color: #fff;
      margin: 0 15px;
      text-decoration: none;
      font-weight: 500;
      font-size: 1rem;
    }
    nav a:hover {
      border-bottom: 2px solid #ffcc00;
    }
    .container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 0 20px;
    }
    h2 {
      color: #222;
      margin-bottom: 15px;
      border-left: 5px solid #000;
      padding-left: 10px;
    }
    p {
      margin-bottom: 20px;
      text-align: justify;
    }
    .highlight {
      font-weight: bold;
      color: #000;
    }
    footer {
      background: #000;
      color: #fff;
      padding: 20px;
      text-align: center;
      margin-top: 40px;
    }
    footer a {
      color: #ffcc00;
      text-decoration: none;
      margin: 0 10px;
    }
    .newsletter {
      background: #f8f8f8;
      padding: 30px;
      margin-top: 40px;
      border: 1px solid #ddd;
      text-align: center;
    }
    .newsletter input[type="email"] {
      padding: 10px;
      width: 250px;
      border: 1px solid #333;
      margin-right: 10px;
    }
    .newsletter button {
      padding: 10px 20px;
      border: none;
      background: #000;
      color: #fff;
      cursor: pointer;
    }
    .newsletter button:hover {
      background: #444;
    }
               /* ---------- Slider (2 moving pictures) ---------- */
    .slider{position:relative;overflow:hidden;border-radius:12px;box-shadow:0 12px 36px rgba(0,0,0,0.08)}
    .slides{display:flex;transition:transform .6s ease}
    .slide{min-width:100%;height:46vh;position:relative;overflow:hidden}
    .slide img{width:100%;height:100%;object-fit:cover;filter:grayscale(0%);transform:scale(1);transition:transform .5s}
    .slide .overlay{position:absolute;left:18px;bottom:18px;background:rgba(0,0,0,0.6);color:#fff;padding:10px 14px;border-radius:8px;font-weight:800}
    .slider .ctrl{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.5);color:#fff;border:0;padding:10px 12px;border-radius:8px;cursor:pointer}
    .slider .prev{left:12px} .slider .next{right:12px}
    .dots{position:absolute;left:50%;transform:translateX(-50%);bottom:10px;display:flex;gap:8px}
    .dot{width:10px;height:10px;border-radius:999px;background:rgba(255,255,255,0.4);cursor:pointer}
    .dot.active{background:#000}

  </style>
</head>
<body>

<header>
    </section>  
  <h1>Ric Hotels & Suites</h1>
  <nav>
    <a href="index6.php">Home</a>
    <a href="contact.php">Contact</a>
  </nav>
<!-- 2 moving pictures -->
  <section class="hero container" aria-label="Two-image slider">
    <div class="slider" id="slider">
      <div class="slides" id="slides">
        <!-- Replace image sources with your assets -->
        <div class="slide">
          <img src="images/image91.jpg" alt="Eko Estate exterior at dusk">
          <div class="caption">Eko hotel </div>
        </div>


</header>

<div class="container">
  <!-- Your About Page Content stays same -->
  <h2>About Ric Hotels & Suites</h2>
  <p>Ric Hotels & Suites is the most preferred hotel in West Africa and it's all about the right mix! Located in the heart of Victoria Island and shielded from the hustle and bustle of the Lagos metropolis, we offer our corporate clients and walk in guests a perfect blend of relaxation, activities, and African tradition delicately infused to meet the highest international standards.</p>

  <p>Overlooking the Eko Atlantic City and Ocean, it is just a 10-minute drive to the City Centre and only 45 minutes away from the Airport.</p>

  <p>Our hotels are designed for your comfort and convenience. Your security is our primary concern and you'll find our customer care second to none.</p>

  <p>Our hotel has taken important steps to live up to West Africa's future model in the hospitality sector. With the best conference and banqueting facilities, tastefully furnished to a 7000-seater capacity, a world-class swimming pool, <span class="highlight">824 excellently furnished rooms</span> spread across 4 hotels: EKO SIGNATURE, EKO SUITES, EKO HOTEL AND KURAMO GARDENS, mostly with a choice of city and sea views. We have 9 different restaurants and 7 different bars serving a range of international cuisines, amongst other facilities.</p>

  <p>The hotel is equipped with a state of the art health and fitness centre comprising of a gym, a tennis/basket ball court, a volley ball court, a sauna, spa, salon, and nail studio. We have an in-house medical clinic as well.</p>

  <p>We host the best themed buffet every Friday evening where we serve specialties across the globe: Africa, Asia, America, Italy, Mexico, Middle East and Mongolia amongst others.</p>

  <h2>Our VISION & MISSION</h2>
  <ul>
    <li>We aspire to be the leading and preferred hotel in West Africa</li>
    <li>We are committed to exceeding guests' expectations</li>
    <li>We are dedicated to providing impeccable facilities and personalized services</li>
    <li>We are proud to deliver genuine care, comfort and warmth to all our guests</li>
    <li>We pursue growth and development through continuous learning</li>
    <li>We are constantly adapting to an ever-changing world</li>
  </ul>

  <h2>Our VALUES</h2>
  <ul>
    <li>Commitment to Excellence</li>
    <li>Pursuit of Growth</li>
    <li>Genuine Care</li>
    <li>Dedication</li>
    <li>Creativity</li>
    <li>Passion</li>
    <li>Pride</li>
    <li>Integrity and Discipline</li>
    <li>Socially and Environmentally Responsible</li>
  </ul>

  <div class="newsletter">
    <h3>Subscribe to Our Newsletter</h3>
    <form action="subscribe.php" method="post">
      <input type="email" name="email" placeholder="Enter your email address" required>
      <button type="submit">Subscribe</button>
    </form>
  </div>
</div>

<footer>
  <p><strong>Eko Hotel</strong> | Plot 1415 Adetokunbo Ademola Street, PMB 12724, Victoria Island, Lagos Nigeria</p>
  <p>Call Us: +234 9021427575, +234 7050672951 | Fax: +234 1 2704071</p>
  <p>Email: Ogundelerichard202@gmail.com| reservation@richotels.com | banquet@richotels.com</p>
  <p>
    <a href="terms.php">Terms & Conditions</a> |
    <a href="privacy.php">Privacy</a>
  </p>
  <p>&copy; 2024 Ric Hotels & Suites</p>
</footer>

</body>
</html>
