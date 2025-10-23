<?php
// index.php - main public page
// No sensitive server-side logic here; booking handled by booking.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Eko Hotel & Suites — Victoria Island, Lagos</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- NAVBAR -->
  <nav class="nav">
    <div class="nav-left">
      <div class="logo">EKO</div>
      <div class="brand-text">
        <h1>Eko Hotel & Suites</h1>
        <small>Victoria Island · Lagos · 5★</small>
      </div>
    </div>
    <div class="nav-right">
      <a href="#about">About</a>
      <a href="#rooms">Rooms</a>
      <a href="#recreation">Recreation</a>
      <a href="#dining">Dining</a>
      <a href="#gallery">Gallery</a>
      <a href="#contact">Contact</a>
    </div>
  </nav>

  <!-- HERO / SLIDER -->
  <header class="hero">
    <div class="slider" id="slider">
      <button class="slide-arrow left" id="prev">&#10094;</button>
      <div class="slides">
        <img src="assets/images/slide1.jpg" alt="Eko exterior" class="active">
        <img src="assets/images/slide2.jpg" alt="Suite view">
        <img src="assets/images/slide3.jpg" alt="Pool">
        <img src="assets/images/slide4.jpg" alt="Sky Restaurant">
        <img src="assets/images/slide5.jpg" alt="Conference/ballroom">
      </div>
      <button class="slide-arrow right" id="next">&#10095;</button>
    </div>

    <div class="hero-overlay">
      <div class="hero-left">
        <h2>Eko Hotel & Suites</h2>
        <p class="hotel-tag">The Most Preferred Hotel in West Africa</p>
        <a class="btn primary" href="#booking-form">Check Availability</a>
      </div>
      <div class="hero-right">
        <!-- quick availability widget -->
        <form id="booking-form" class="avail" action="booking.php" method="post">
          <h3>Check Availability</h3>
          <label>Arrival
            <input type="date" name="arrival" required>
          </label>
          <label>Departure
            <input type="date" name="departure" required>
          </label>
          <label>Guests
            <select name="guests">
              <option>1</option><option>2</option><option>3</option><option>4+</option>
            </select>
          </label>
          <label>Room type
            <select name="room_type">
              <option>Eko Garden</option>
              <option>Ocean Suite</option>
              <option>Executive</option>
              <option>Presidential</option>
            </select>
          </label>
          <label>
            Your email
            <input type="email" name="email" placeholder="you@example.com" required>
          </label>
          <button type="submit" class="btn">Check & Book</button>
        </form>
      </div>
    </div>
  </header>

  <!-- ABOUT - half yellow / half white background -->
  <section id="about" class="about split-bg">
    <div class="about-left">
      <h2>Welcome to Eko Hotel & Suites</h2>
      <p class="lead">The Most Preferred Hotel in West Africa</p>
      <p>
        Eko Hotels & Suites is the most preferred hotel in West Africa, and it is all about the right mix! Located in the heart of Victoria Island, we offer our clients a perfect blend of business & leisure amenities with dining and recreational options delicately infused in one amazing space. We crown all these with services that meet the highest international standards.
      </p>
      <p>
        Overlooking the new Eko Atlantic City and Atlantic Ocean, it is just a 10-minute drive to the City Centre and only 45 minutes away from the airport. Our property consists of luxurious suites and rooms, conference and event facilities, world-class dining options, and recreational services to ensure every stay is exceptional.
      </p>
      <a href="#" class="btn">Learn More</a>
    </div>
    <div class="about-right">
      <img src="assets/images/about.jpg" alt="About Eko Hotel">
    </div>
  </section>

  <!-- ROOMS / Our hotel types -->
  <section id="rooms" class="rooms">
    <h2>Our Rooms & Suites</h2>
    <p class="sub">Click images to view details. Prices shown are per night (NGN).</p>
    <div class="rooms-grid">
      <!-- Example card: replicate up to 9 cards (Eko Garden + 8 more) -->
      <?php
        // Define the rooms array in PHP to keep markup clean
        $rooms = [
          ['title'=>'Eko Garden','img'=>'room_garden.jpg','price'=>'₦45,000','desc'=>'Garden view deluxe room.'],
          ['title'=>'Ocean Suite','img'=>'room_ocean.jpg','price'=>'₦120,000','desc'=>'Ocean view suite with balcony.'],
          ['title'=>'Executive','img'=>'room_exec.jpg','price'=>'₦70,000','desc'=>'Business-friendly executive room.'],
          ['title'=>'Presidential','img'=>'room_pres.jpg','price'=>'₦250,000','desc'=>'Top-floor presidential suite.'],
          ['title'=>'Family Room','img'=>'room_family.jpg','price'=>'₦55,000','desc'=>'Spacious family accommodation.'],
          ['title'=>'Business Twin','img'=>'room_twin.jpg','price'=>'₦60,000','desc'=>'Twin beds for business travelers.'],
          ['title'=>'Penthouse','img'=>'room_pent.jpg','price'=>'₦300,000','desc'=>'Luxury penthouse with lounge.'],
          ['title'=>'Studio','img'=>'room_studio.jpg','price'=>'₦40,000','desc'=>'Compact studio for short stays.'],
          ['title'=>'Deluxe','img'=>'room_deluxe.jpg','price'=>'₦80,000','desc'=>'Deluxe room with premium amenities.']
        ];
        foreach($rooms as $r):
      ?>
      <article class="room-card">
        <a href="#" class="img-link" data-img="assets/images/<?php echo $r['img']; ?>">
          <img src="assets/images/<?php echo $r['img']; ?>" alt="<?php echo $r['title']; ?>">
        </a>
        <div class="room-body">
          <h3><?php echo $r['title']; ?></h3>
          <p class="price"><?php echo $r['price']; ?></p>
          <p><?php echo $r['desc']; ?></p>
          <div class="room-actions">
            <a href="#" class="btn">View More</a>
            <a href="#booking-form" class="btn outline">Book Now</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- RECREATION (black bg with moving images) -->
  <section id="recreation" class="recreation">
    <h2>Recreational Services</h2>
    <p class="sub white">Premium facilities to relax and stay active.</p>
    <div class="rec-row">
      <div class="rec-card parallax" style="background-image:url('assets/images/gym.jpg')">
        <div class="rec-overlay">
          <h3>Gymnasium</h3>
          <p>State-of-the-art fitness center open 24/7.</p>
        </div>
      </div>
      <div class="rec-card parallax" style="background-image:url('assets/images/pool.jpg')">
        <div class="rec-overlay">
          <h3>Swimming Pool</h3>
          <p>Outdoor pool with lounge and poolside service.</p>
        </div>
      </div>
      <div class="rec-card parallax" style="background-image:url('assets/images/tennis.jpg')">
        <div class="rec-overlay">
          <h3>Tennis Court</h3>
          <p>Floodlit courts for day & evening play.</p>
        </div>
      </div>
      <div class="rec-card parallax" style="background-image:url('assets/images/spa.jpg')">
        <div class="rec-overlay">
          <h3>Spa</h3>
          <p>Relaxing treatments and massage therapies.</p>
        </div>
      </div>
      <div class="rec-card parallax" style="background-image:url('assets/images/saloon.jpg')">
        <div class="rec-overlay">
          <h3>Saloon</h3>
          <p>Professional grooming services.</p>
        </div>
      </div>
      <div class="rec-card parallax" style="background-image:url('assets/images/nail.jpg')">
        <div class="rec-overlay">
          <h3>Nail Studio</h3>
          <p>Beauty & nail services by appointment.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- DINING -->
  <section id="dining" class="dining">
    <h2>Dining & Bars</h2>
    <div class="dining-grid">
      <div class="d-card">
        <img src="assets/images/sky.jpg" alt="Sky Restaurant">
        <h3>Sky Restaurant</h3>
        <p>Fine dining with panoramic city views.</p>
      </div>
      <div class="d-card">
        <img src="assets/images/atarado.jpg" alt="Atarado">
        <h3>Atarado</h3>
        <p>Heritage flavours & contemporary twists.</p>
      </div>
      <div class="d-card">
        <img src="assets/images/lagoon.jpg" alt="Lagoon Breeze">
        <h3>Lagoon Breeze</h3>
        <p>Seafood specialties facing the lagoon.</p>
      </div>
      <div class="d-card">
        <img src="assets/images/calabash.jpg" alt="Calabash Bar">
        <h3>Calabash Bar</h3>
        <p>Casual cocktails & local spirits.</p>
      </div>
      <div class="d-card">
        <img src="assets/images/irish.jpg" alt="Lagos Irish Pub">
        <h3>Lagos Irish Pub</h3>
        <p>Live music and bar bites.</p>
      </div>
      <div class="d-card">
        <img src="assets/images/chinese.jpg" alt="Red Chinese">
        <h3>Red Chinese</h3>
        <p>Authentic Asian flavors and curated menu.</p>
      </div>
    </div>
  </section>

  <!-- GALLERY -->
  <section id="gallery" class="gallery">
    <h2>Gallery</h2>
    <div class="gallery-grid">
      <!-- 20 images; clickable -->
      <?php for($i=1;$i<=20;$i++): ?>
        <img class="gallery-img" src="assets/images/gallery<?php echo $i; ?>.jpg" alt="Gallery <?php echo $i; ?>">
      <?php endfor; ?>
    </div>
  </section>

  <!-- MAP & CONTACT -->
  <section id="contact" class="contact">
    <h2>Find Us</h2>
    <div class="contact-wrap">
      <div class="map">
        <!-- Replace the src with a proper Google Maps embed for Eko Hotels & Suites Victoria Island -->
        <iframe
          src="https://www.google.com/maps?q=Eko+Hotels+and+Suites+Victoria+Island&output=embed"
          style="border:0" allowfullscreen="" loading="lazy"></iframe>
      </div>

      <div class="contact-info">
        <h3>Contact & Address</h3>
        <p><strong>Address:</strong> Command Road, Ipaja Expressway, Victoria Island, Lagos</p>
        <p><strong>Phone:</strong> +234 800 123 4567</p>
        <p><strong>Email:</strong> reservations@ekohotels.com</p>
        <p><strong>Have questions?</strong> Call or send an email and we'll respond quickly.</p>

        <h4>Quick Inquiry</h4>
        <form action="booking.php" method="post" class="inquiry">
          <input type="text" name="name" placeholder="Your name" required>
          <input type="email" name="email" placeholder="Email" required>
          <textarea name="message" placeholder="Your question" required></textarea>
          <button type="submit" class="btn">Send Inquiry</button>
        </form>
      </div>
    </div>
  </section>

  <footer class="site-footer">
    <div>&copy; <?php echo date('Y'); ?> Eko Hotel & Suites — All rights reserved.</div>
    <div>Designed for demo & client preview</div>
  </footer>

  <!-- MODAL for image preview -->
  <div id="modal" class="modal" aria-hidden="true">
    <span class="modal-close" id="modal-close">&times;</span>
    <img class="modal-img" id="modal-img" src="" alt="Preview">
  </div>

  <script src="assets/js/site.js"></script>
</body>
</html>
