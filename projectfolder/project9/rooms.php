<!doctype html>
            <html lang="en">
            <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width,initial-scale=1" />
            <title>Richard Hotel & Suites </title>
            <meta name="description" content="Eko Hotel & Suites — The most preferred hotel in West Africa. Demo site for development." />
            <style>
      /* ---------- Rooms ---------- */
              .rooms{padding:34px 18px;background:transparent;margin:18px}
              .rooms h2{margin-bottom:8px}
              .rooms-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:16px}
              .room-card{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 8px 22px rgba(2,22,36,0.06)}
              .room-card .img-wrap{height:180px;overflow:hidden}
              .room-card img{width:100%;height:100%;object-fit:cover;display:block;cursor:pointer}
              .room-body{padding:12px}
              .room-body h3{font-size:1.05rem;margin-bottom:6px}
              .room-body .price{color:#ff7b54;font-weight:800;margin-bottom:6px}
              .room-actions{display:flex;gap:8px;margin-top:8px}
          /* ---------- Hero (slider + booking) ---------- */
              .hero-title{display:flex;align-items:center;gap:12px;color:#fff}
              .hero-title h2{font-size:2rem;letter-spacing:0.3px}
              .hero-tag{color:#ffe9b7;font-weight:700;margin-top:6px}
              .slider{position:relative;overflow:hidden;border-radius:12px;margin-top:14px;box-shadow:0 16px 40px rgba(2,22,36,0.35)}
              .slides{display:flex;transition:transform .6s ease-in-out}
              .slide{min-width:100%;height:46vh;object-fit:cover;flex-shrink:0}
              .slide-controls{position:absolute;top:50%;left:12px;right:12px;display:flex;justify-content:space-between;transform:translateY(-50%);pointer-events:none}
              .slide-btn{pointer-events:auto;background:rgba(0,0,0,0.45);border:none;color:#fff;padding:10px 12px;border-radius:8px;cursor:pointer}
              .hero-right{background:rgba(255,255,255,0.98);padding:14px;border-radius:10px}
              .avail h3{margin-bottom:8px}
              .avail label{display:block;margin-bottom:8px;font-size:.9rem}
              .avail input,.avail select{width:100%;padding:10px;border-radius:8px;border:1px solid #e4eaf0;margin-top:6px}
              .btn{display:inline-block;padding:10px 14px;border-radius:8px;font-weight:800;background:#ff7b54;color:#fff;border:none;cursor:pointer}
              .btn.ghost{background:transparent;border:2px solid rgba(2,22,36,0.06);color:#071428}
    
          /* ---------- Recreation (black bg + moving images) ---------- */
              .recreation{background:#000;color:#fff;padding:36px 18px;border-radius:10px;margin:18px 0}
              .recreation h2{color:#fff}
              .rec-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:16px}
              .rec-card{height:200px;border-radius:10px;background-size:cover;background-position:center;position:relative;overflow:hidden;transition:transform .45s}
              .rec-card::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,0.1),rgba(0,0,0,0.55))}
              .rec-overlay{position:absolute;left:12px;bottom:12px;z-index:3}
              .rec-card:hover{transform:translateY(-6px)}   
             /* ---------- Header ---------- */
    header.site-header{display:flex;align-items:center;justify-content:space-between;padding:18px;border-bottom:1px solid #eee}
    .brand{display:flex;align-items:center;gap:12px}
    .logo{width:56px;height:56px;border-radius:8px;background:linear-gradient(180deg,#000,#333);color:#fff;display:grid;place-items:center;font-weight:900}
    .brand h1{font-size:1.1rem;letter-spacing:0.2px}
    .nav{display:flex;gap:12px;align-items:center}
    .nav a{padding:8px 12px;border-radius:8px;border:1px solid transparent;font-weight:700}
    .nav a:hover{background:#f3f3f3}

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
       <header class="site-header container" role="banner">
    <div class="brand">
      <div class="logo">RIC</div>
      <div>
        <h1>ROOM</h1>
        <small style="color:var(--muted)">Command road • Lagos</small>
      </div>
    </div>

    <nav class="nav" role="navigation" aria-label="Main nav">
      <a href="index6.php">home</a>
    </nav>
  </header>

  <main class="container">

    <!-- 2 moving pictures (slider) -->
    <section aria-label="Image slider" style="margin-top:18px">
      <div class="slider" id="slider">
        <div class="slides" id="slides">
          <div class="slide">
            <img src="images/image157.jpg" alt="Eko Estate exterior">
            <div class="overlay">executive room — Exterior</div>
          </div>
          <div class="slide">
            <img src="images/image158.jpg"
            te — Interior</div>
          </div>
        </div>
        <button class="ctrl prev" id="prev" aria-label="Previous">&#10094;</button>
        <button class="ctrl next" id="next" aria-label="Next">&#10095;</button>
        <div class="dots" id="dots" aria-hidden="false"></div>
      </div>
    </section>          
           <!-- ROOMS / Our hotel types (9 cards) -->
            <section id="rooms" class="rooms container" aria-labelledby="rooms-heading">
              <h2 id="rooms-heading">Our Rooms & Suites</h2>
              <p style="color:#55606a">Click images to preview. Prices shown are sample per-night rates (NGN).</p>

              <div class="rooms-grid" id="roomsGrid">
                <!-- 9 card examples; replace src with your assets for production -->
                <article class="room-card">
                  <div class="img-wrap"><img src="images/image110.jpg" alt="Eko Garden" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Eko Garden</h3><div class="price">₦45,000 / night</div><div>Garden view deluxe room with modern amenities.</div><div class="room-actions"><a class="btn" href="viewmore1.php">View More</a><a class="btn ghost" href="booking1.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image111.jpg" alt="Ocean Suite" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Ocean Suite</h3><div class="price">₦120,000 / night</div><div>Spacious suite with balcony and ocean views.</div><div class="room-actions"><a class="btn" href="viewmore2.php">View More</a><a class="btn ghost" href="booking2.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image112.jpg" alt="Executive" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Executive</h3><div class="price">₦70,000 / night</div><div>Business-focused rooms with workspace and amenities.</div><div class="room-actions"><a class="btn" href="viewmore3.php">View More</a><a class="btn ghost" href="booking3.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image113.jpg" alt="Presidential" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Presidential</h3><div class="price">₦250,000 / night</div><div>Top-floor suite with private lounge and service.</div><div class="room-actions"><a class="btn" href="viewmore4.php" onclick="openModal('https://source.unsplash.com/1600x900/?presidential,suite')">View More</a><a class="btn ghost" href="booking4.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image114.jpg" alt="Family Room" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Family Room</h3><div class="price">₦55,000 / night</div><div>Spacious room ideal for family stays.</div><div class="room-actions"><a class="btn" href="viewmore5.php">View More</a><a class="btn ghost" href="booking5.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image116.jpg" alt="Business Twin" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Business Twin</h3><div class="price">₦60,000 / night</div><div>Comfortable twin beds for work & rest.</div><div class="room-actions"><a class="btn" href="viewmore6.php">View More</a><a class="btn ghost" href="booking6.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image115.jpg" alt="Penthouse" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Penthouse</h3><div class="price">₦300,000 / night</div><div>Luxury penthouse with panoramic views and lounge.</div><div class="room-actions"><a class="btn" href="viewmore7.php">View More</a><a class="btn ghost" href="booking7.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image117.jpg" alt="Studio" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Studio</h3><div class="price">₦40,000 / night</div><div>Compact studio perfect for short stays.</div><div class="room-actions"><a class="btn" href="viewmore8.php">View More</a><a class="btn ghost" href="booking8.php">Book Now</a></div></div>
                </article>

                <article class="room-card">
                  <div class="img-wrap"><img src="images/image118.jpg" alt="Deluxe" onclick="openModal(this.src)"></div>
                  <div class="room-body"><h3>Deluxe</h3><div class="price">₦80,000 / night</div><div>Deluxe room with premium comfort and amenities.</div><div class="room-actions"><a class="btn" href="viewmore9.php">View More</a><a class="btn ghost" href="booking9.php">Book Now</a></div></div>
                </article>
              </div>
            </section>    