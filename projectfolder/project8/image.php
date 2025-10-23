<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Eko Hotel & Suites — Lagos</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- Header -->
  <header class="site-header">
    <div class="brand">
      <span class="brand-arrows">◄</span>
      <a href="#home" class="brand-name">Eko Hotel & Suites</a>
      <span class="brand-arrows">►</span>
    </div>
    <nav class="nav">
      <a href="#home">Home</a>
      <a href="#about">About</a>
      <a href="#our-hotels">Our Hotels</a>
      <a href="#recreation">Recreation</a>
      <a href="#dining">Dining</a>
      <a href="#gallery">Gallery</a>
      <a href="#visit">Visit</a>
      <a href="#contact" class="btn-sm">Contact</a>
    </nav>
  </header>

  <!-- Hero -->
  <section id="home" class="hero">
    <div class="hero-track">
      <div class="hero-slide" style="--bg:url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1600&q=60')"></div>
      <div class="hero-slide" style="--bg:url('https://images.unsplash.com/photo-1501117716987-c8e2a9cebbf8?auto=format&fit=crop&w=1600&q=60')"></div>
      <div class="hero-slide" style="--bg:url('https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?auto=format&fit=crop&w=1600&q=60')"></div>
      <div class="hero-slide" style="--bg:url('https://images.unsplash.com/photo-1488747279002-c8523379faaa?auto=format&fit=crop&w=1600&q=60')"></div>
      <div class="hero-slide" style="--bg:url('https://images.unsplash.com/photo-1496412705862-e0088f16f791?auto=format&fit=crop&w=1600&q=60')"></div>
    </div>
    <div class="hero-content">
      <h1>Experience Lagos in Style</h1>
      <p class="muted">Hospitality, culture, cuisine — perfectly curated on Victoria Island.</p>
      <a href="#availability" class="btn primary">Check Availability</a>
    </div>
    <button class="hero-nav prev" aria-label="Previous">‹</button>
    <button class="hero-nav next" aria-label="Next">›</button>
  </section>

  <!-- Quick availability -->
  <section id="availability" class="availability">
    <div class="av-wrap">
      <label>Destination
        <input value="Eko Hotel & Suites, Lagos">
      </label>
      <label>Check-in
        <input type="date" id="ci">
      </label>
      <label>Check-out
        <input type="date" id="co">
      </label>
      <label>Guests
        <select id="guests"><option>1</option><option>2</option><option>3</option><option>4</option></select>
      </label>
      <button id="avSearch" class="btn">Search</button>
    </div>
    <div id="avResult" class="av-result" hidden>
      <div>From <strong id="rateOut">₦62,000</strong> / night</div>
      <a class="btn ghost" href="#our-hotels">See Rooms</a>
    </div>
  </section>

  <!-- About -->
  <section id="about" class="about">
    <div class="about-grid">
      <div class="about-visual">
        <img class="tilt" src="https://images.unsplash.com/photo-1551776235-dde6d4829808?auto=format&fit=crop&w=1200&q=60" alt="Eko Hotel exterior">
        <img class="tilt small" src="https://images.unsplash.com/photo-1554995207-c18c203602cb?auto=format&fit=crop&w=1200&q=60" alt="Lobby">
      </div>
      <div class="about-copy">
        <h2>Welcome to Eko Hotel & Suites</h2>
        <p class="eyebrow">The most preferred hotel in West Africa</p>
        <p>
          Nestled in the vibrant heart of Lagos, Eko Hotel & Suites blends contemporary luxury with the warm rhythm of Nigerian hospitality. 
          Our waterfront address places you moments from the city’s business districts, galleries, and nightlife, while leafy gardens, expansive 
          pools, and curated lounges create a calm sanctuary above the city’s hum. Whether you are closing a deal, hosting a celebration, or slipping 
          into weekend mode, our spaces are crafted to elevate every moment. Wake to skyline views, enjoy chef-driven dining that weaves local flavors 
          with global flair, and discover wellness offerings designed for balance—morning laps in the pool, a restorative spa, or a sunset workout 
          with Atlantic breezes. Our dedicated team anticipates needs with intuitive service—seamless check-ins, thoughtful amenities, and discreet 
          touches that feel personal. From grand ballrooms to intimate terraces, flexible venues turn gatherings into occasions. At Eko, every stay 
          is a story: business travelers find momentum, families find room to breathe, and explorers find a lens on Lagos that is both authentic and 
          effortlessly comfortable. Arrive as a guest; leave connected—to place, to people, and to possibilities that linger long after checkout.
        </p>
        <a href="#our-hotels" class="btn link">Learn more →</a>
      </div>
    </div>
  </section>

  <!-- Our Hotels -->
  <section id="our-hotels" class="hotels">
    <div class="section-head">
      <h2>Our Hotels</h2>
      <p class="muted">Every image is clickable — open details or book directly.</p>
    </div>

    <div class="card-grid">
      <!-- 1/9 Eko Garden -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=60" alt="Eko Garden">
        </a>
        <h3>Eko Garden</h3>
        <p class="muted">Garden-side comfort close to pool & gym.</p>
        <div class="row">
          <span class="price">From ₦62,000</span>
          <div class="actions">
            <a class="btn ghost" href="#details">View more</a>
            <a class="btn primary" href="#book">Book now</a>
          </div>
        </div>
      </article>

      <!-- 2/9 Eko Signature -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1200&q=60" alt="Eko Signature">
        </a>
        <h3>Eko Signature</h3>
        <p class="muted">Designer rooms, elevated finishes, city views.</p>
        <div class="row">
          <span class="price">From ₦95,000</span>
          <div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div>
        </div>
      </article>

      <!-- 3/9 Eko Atlantic View -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=60" alt="Eko Atlantic View">
        </a>
        <h3>Eko Atlantic View</h3>
        <p class="muted">High-floor rooms with sweeping ocean light.</p>
        <div class="row"><span class="price">From ₦120,000</span><div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div></div>
      </article>

      <!-- 4/9 Eko Suites -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1505692794403-34d4982f88aa?auto=format&fit=crop&w=1200&q=60" alt="Eko Suites">
        </a>
        <h3>Eko Suites</h3>
        <p class="muted">Spacious living, perfect for long stays.</p>
        <div class="row"><span class="price">From ₦88,000</span><div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div></div>
      </article>

      <!-- 5/9 Eko Residence -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1505691723518-36a5ac3b2d52?auto=format&fit=crop&w=1200&q=60" alt="Eko Residence">
        </a>
        <h3>Eko Residence</h3>
        <p class="muted">Apartment-style comfort with kitchenette.</p>
        <div class="row"><span class="price">From ₦78,000</span><div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div></div>
      </article>

      <!-- 6/9 Eko Business Floor -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1200&q=60" alt="Business Floor">
        </a>
        <h3>Eko Business Floor</h3>
        <p class="muted">Executive lounge access & meeting perks.</p>
        <div class="row"><span class="price">From ₦135,000</span><div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div></div>
      </article>

      <!-- 7/9 Eko Family Wing -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1496417263034-38ec4f0b665a?auto=format&fit=crop&w=1200&q=60" alt="Family Wing">
        </a>
        <h3>Eko Family Wing</h3>
        <p class="muted">Inter-connecting rooms & kids’ perks.</p>
        <div class="row"><span class="price">From ₦70,000</span><div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div></div>
      </article>

      <!-- 8/9 Eko Club Rooms -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=60" alt="Club Rooms">
        </a>
        <h3>Eko Club Rooms</h3>
        <p class="muted">Evening canapés & premium amenities.</p>
        <div class="row"><span class="price">From ₦110,000</span><div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div></div>
      </article>

      <!-- 9/9 Eko Presidential -->
      <article class="card">
        <a href="#book" class="image-link">
          <img src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1200&q=60" alt="Presidential">
        </a>
        <h3>Eko Presidential</h3>
        <p class="muted">Grand living with dedicated concierge.</p>
        <div class="row"><span class="price">From ₦350,000</span><div class="actions"><a class="btn ghost" href="#details">View more</a><a class="btn primary" href="#book">Book now</a></div></div>
      </article>
    </div>
  </section>

  <!-- Recreation -->
  <section id="recreation" class="dark-section">
    <div class="section-head light">
      <h2>Recreational Services</h2>
      <p class="muted">Wellness and play — move your body, reset your mind.</p>
    </div>

    <div class="reel">
      <article class="re-item">
        <img class="hand-move" src="https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=1200&q=60" alt="Gymnasium">
        <h4>Gymnasium</h4>
        <p>Sunlit fitness studio with strength & cardio zones, trainers on request.</p>
      </article>

      <article class="re-item">
        <img class="hand-move" src="https://images.unsplash.com/photo-1528701800489-20be3c3ea64d?auto=format&fit=crop&w=1200&q=60" alt="Swimming Pool">
        <h4>Swimming Pool</h4>
        <p>Resort-style pool, cabanas, light bites and weekend DJs in season.</p>
      </article>

      <article class="re-item">
        <img class="hand-move" src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1200&q=60" alt="Tennis">
        <h4>Tennis Court</h4>
        <p>Hard courts, lights for evening play, racket hire available.</p>
      </article>

      <article class="re-item">
        <img class="hand-move" src="https://images.unsplash.com/photo-1556228453-efd1df104efb?auto=format&fit=crop&w=1200&q=60" alt="Spa">
        <h4>Spa</h4>
        <p>Holistic treatments, steam & sauna, locally inspired rituals.</p>
      </article>

      <article class="re-item">
        <img class="hand-move" src="https://images.unsplash.com/photo-1519415943484-9fa064c2748e?auto=format&fit=crop&w=1200&q=60" alt="Salon">
        <h4>Salon</h4>
        <p>Cut, color, grooming — wedding & event styling by appointment.</p>
      </article>

      <article class="re-item">
        <img class="hand-move" src="https://images.unsplash.com/photo-1596464716121-8b3889a1ad6b?auto=format&fit=crop&w=1200&q=60" alt="Nail Studio">
        <h4>Nail Studio</h4>
        <p>Manicure, pedicure & nail art — classic to bold Lagos looks.</p>
      </article>
    </div>
  </section>

  <!-- Dining -->
  <section id="dining" class="dining">
    <div class="section-head">
      <h2>Dining & Bars</h2>
      <p class="muted">Signature venues for every mood.</p>
    </div>

    <div class="card-grid">
      <article class="card wide">
        <img class="hand-move" src="https://images.unsplash.com/photo-1528605248644-14dd04022da1?auto=format&fit=crop&w=1400&q=60" alt="Sky Restaurant">
        <div class="card-body">
          <h3>Eko Dinner — Sky Restaurant</h3>
          <p class="muted">Panoramic skyline, wood-fired specials, and a cellar curated for conversation.</p>
        </div>
      </article>

      <article class="card">
        <img class="hand-move" src="https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1200&q=60" alt="Atarado">
        <h3>Atarado</h3>
        <p class="muted">Fire-kissed grills with peppery flair.</p>
      </article>

      <article class="card">
        <img class="hand-move" src="https://images.unsplash.com/photo-1470337458703-46ad1756a187?auto=format&fit=crop&w=1200&q=60" alt="Lagoon Breeze">
        <h3>Lagoon Breeze</h3>
        <p class="muted">Casual al-fresco dining by the water.</p>
      </article>

      <article class="card">
        <img class="hand-move" src="https://images.unsplash.com/photo-1498654200943-1088dd4438ae?auto=format&fit=crop&w=1200&q=60" alt="Calabash Bar">
        <h3>Calabash Bar</h3>
        <p class="muted">Craft cocktails & live sets on weekends.</p>
      </article>

      <article class="card">
        <img class="hand-move" src="https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1200&q=60" alt="Lagos Irish Pub">
        <h3>Lagos Irish Pub</h3>
        <p class="muted">Taproom energy with Lagos charm.</p>
      </article>

      <article class="card">
        <img class="hand-move" src="https://images.unsplash.com/photo-1544025164-94b1182d73d6?auto=format&fit=crop&w=1200&q=60" alt="Red Chinese">
        <h3>Red Chinese Restaurant</h3>
        <p class="muted">Dim sum, stir-fries, and moonlit terrace tables.</p>
      </article>
    </div>
  </section>

  <!-- Gallery (20 images) -->
  <section id="gallery" class="gallery">
    <div class="section-head"><h2>Gallery</h2><p class="muted">Hover or tap to feel the motion.</p></div>
    <div class="grid-5">
      <!-- 20 items -->
      <img class="tilt" src="https://source.unsplash.com/random/800x600?hotel,1" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?lagos,2" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?pool,3" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?restaurant,4" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?room,5" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?bar,6" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?gym,7" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?view,8" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?lobby,9" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?spa,10" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?hotel,11" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?lagos,12" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?pool,13" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?restaurant,14" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?room,15" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?bar,16" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?gym,17" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?view,18" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?lobby,19" alt="">
      <img class="tilt" src="https://source.unsplash.com/random/800x600?spa,20" alt="">
    </div>
  </section>

  <!-- Map + Visit -->
  <section id="visit" class="visit">
    <div class="section-head"><h2>Find Us</h2><p class="muted">Plan your route to Eko Hotel & Suites.</p></div>
    <div class="visit-grid">
      <div class="mapwrap">
        <iframe src="https://www.google.com/maps?q=Eko+Hotel+and+Suites+Lagos&z=14&output=embed" loading="lazy"></iframe>
      </div>
      <div class="info">
        <h3>Visitor Information</h3>
        <ul>
          <li><strong>Address:</strong> Command Road, Ipaga Express Way, Lagos</li>
          <li><strong>Phone:</strong> +234 800 000 0000</li>
          <li><strong>Email:</strong> reservations@ekohotel.example</li>
        </ul>
        <a href="#availability" class="btn primary">Check Availability</a>
      </div>
    </div>
  </section>

  <!-- Contact -->
  <section id="contact" class="contact">
    <div class="section-head"><h2>Questions?</h2><p class="muted">Tell us your details and we’ll get back to you.</p></div>
    <form class="contact-form" onsubmit="return sendMessage(event)">
      <input required placeholder="Full name">
      <input required type="email" placeholder="Email address">
      <input placeholder="Phone">
      <textarea rows="5" placeholder="Your message"></textarea>
      <button class="btn">Send Message</button>
      <div id="formNote" class="form-note" role="status" aria-live="polite"></div>
    </form>
  </section>

  <footer class="footer">
    <div>© Eko Hotel & Suites — Demo build for client presentation. Replace images with your licensed photos.</div>
  </footer>

  <script src="script.js"></script>
</body>
</html>
