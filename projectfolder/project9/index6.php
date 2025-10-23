
          <!doctype html>
            <html lang="en">
            <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width,initial-scale=1" />
            <title>RIC </title>
            <meta name="description" content="Ric Hotel & Suites — The most preferred hotel in West Africa. Demo site for development." />
            <style>
              /* ---------- Reset & Base ---------- */
              *{box-sizing:border-box;margin:0;padding:0}
              html,body{height:100%}
              body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:#f3f6f9;color:#102028;line-height:1.45}
              a{color:inherit;text-decoration:none}
              img{max-width:100%;display:block}
              .container{max-width:1200px;margin:0 auto;padding:18px}

              /* ---------- Header / Navbar ---------- */
              header.site-header{background:linear-gradient(90deg,#022433,#01364a);color:#fff;position:sticky;top:0;z-index:60;box-shadow:0 6px 18px rgba(2,22,36,0.22)}
              .topbar{display:flex;align-items:center;gap:18px;padding:12px 18px}
              .brand{display:flex;gap:12px;align-items:center}
              .logo{width:56px;height:56px;border-radius:10px;background:linear-gradient(135deg,#ffd166,#ff7b54);display:flex;align-items:center;justify-content:center;font-weight:800;color:#071428;font-size:1.05rem}
              .brand h1{font-size:1.05rem;margin-bottom:2px}
              .brand small{display:block;opacity:.9;font-size:.78rem}
              nav.main-nav{margin-left:auto;display:flex;gap:10px;align-items:center}
              nav.main-nav a{color:#eaf6ff;padding:8px 12px;border-radius:8px;font-weight:600;opacity:.95}
              nav.main-nav a:hover{background:rgba(255,255,255,0.04)}
              .cta-book{background:#ffd166;color:#071428;padding:8px 12px;border-radius:8px;font-weight:800}
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

              /* ---------- About (half yellow / half white) ---------- */
              .about{display:flex;gap:20px;padding:44px 18px;align-items:center;flex-wrap:wrap}
              .about-left{flex:1;min-width:320px;background:linear-gradient(90deg,#ffd966 50%, #ffffff 50%);padding:28px;border-radius:12px;color:#102028}
              .about-right{flex:1;min-width:320px;background:#fff;padding:24px;border-radius:12px;box-shadow:0 12px 30px rgba(2,22,36,0.06)}
              .about .lead{font-weight:700;margin-bottom:12px}
              .about p{margin-bottom:12px;color:#07202b;opacity:.95}

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

              /* ---------- Recreation (black bg + moving images) ---------- */
              .recreation{background:#000;color:#fff;padding:36px 18px;border-radius:10px;margin:18px 0}
              .recreation h2{color:#fff}
              .rec-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:16px}
              .rec-card{height:200px;border-radius:10px;background-size:cover;background-position:center;position:relative;overflow:hidden;transition:transform .45s}
              .rec-card::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,0.1),rgba(0,0,0,0.55))}
              .rec-overlay{position:absolute;left:12px;bottom:12px;z-index:3}
              .rec-card:hover{transform:translateY(-6px)}

              /* ---------- Dining ---------- */
              .dining{padding:32px 18px;background:#fff;border-radius:10px;margin:18px 0}
              .d-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
              .d-card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 10px 20px rgba(2,22,36,0.05)}
              .d-card img{height:160px;object-fit:cover}

              /* ---------- Gallery ---------- */
              .gallery{padding:28px 18px}
              .gallery-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px}
              .gallery-grid img{height:120px;object-fit:cover;border-radius:6px;cursor:pointer;transition:transform .22s}
              .gallery-grid img:hover{transform:scale(1.05)}

              /* ---------- Contact / Map ---------- */
              .contact{padding:28px 18px;background:#fff;border-radius:10px;margin:18px 0}
              .contact-grid{display:grid;grid-template-columns:1fr 380px;gap:16px}
              .contact .map iframe{width:100%;height:320px;border:0;border-radius:8px}
              .contact-info h3{margin-bottom:8px}

              /* ---------- Footer ---------- */
              footer.site-footer{padding:20px;text-align:center;color:#fff;background:#022033;border-top:4px solid #ff7b54;margin-top:22px}

              /* ---------- Modal (image preview) ---------- */
              .modal{position:fixed;inset:0;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;visibility:hidden;opacity:0;transition:opacity .18s;z-index:999}
              .modal.show{visibility:visible;opacity:1}
              .modal img{max-width:92%;max-height:86%;border-radius:8px}
              .modal .close{position:absolute;top:18px;right:22px;color:#fff;font-size:28px;cursor:pointer}

              /* ---------- Responsive ---------- */
              @media (max-width:1000px){
                .hero-inner{grid-template-columns:1fr}
                .hero-right{order:2}
                .hero-left{order:1}
                .contact-grid{grid-template-columns:1fr}
              }
              @media (max-width:600px){
                .topbar{padding:8px}
                .brand h1{font-size:.98rem}
                .hero-title h2{font-size:1.4rem}
              }      
            </style>
            </head>
            <body>

            <!-- HEADER -->
            <header class="site-header">
              <div class="container topbar">
                <div class="brand" aria-hidden="false">
                  <div class="logo">RIC</div>
                  <div>
                    <h1>RIC Hotel & Suites</h1>
                    <small>Command lagos — Lagos</small>
                  </div>
                </div>

                <nav class="main-nav" aria-label="Main navigation">
                  <a href="rooms.php">Rooms</a>
                  <a href="gallery.php">Gallery</a>
                  <a href="feedback.php">Feedback</a>
                  <a href="abouts.php">about us</a>
                  <a href="logout.php" class="cta-book">LOG OUT</a>
                </nav>
                
              </div>
            </header>


            <!-- HERO / SLIDER + AVAILABILITY -->
            <section class="hero" aria-label="Hero">
              <div class="container hero-inner">
                <div class="hero-left" aria-hidden="false">
                  <div class="hero-title">
                    <div style="display:flex;flex-direction:column">
                      <h2 style="color:#fff">RIC Hotel & Suites</h2>
                      <div class="hero-tag">The Most  Biggest And Preferred Hotel in Nigeria</div>
                    </div>
                    <div style="margin-left:12px;align-self:flex-start">
                      <!-- arrow beside name -->
                      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" aria-hidden>
                        <path d="M5 12h14M13 5l7 7-7 7" stroke="#ffe9b7" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </div>
                  </div>
              <!-- 2 moving pictures -->
  <section class="hero container" aria-label="Two-image slider">
    <div class="slider" id="slider">
      <div class="slides" id="slides">
        <!-- Replace image sources with your assets -->
        <div class="slide">
          <img src="images/image91.jpg" alt="Eko Estate exterior at dusk">
          <div class="caption">Ric hotel </div>
        </div>
        <div class="slide">
          <img src="images/images83.jpg" alt="Signature suite interior">
          <div class="caption">Ric hotel</div>
        </div>
         <div class="slide">
          <img src="images/image87.jpg" alt="Signature suite interior">
          <div class="caption">Ric hotel</div>
        </div>
      </div>
      <button class="ctrl prev" id="prev" aria-label="Previous slide">&#10094;</button>
      <button class="ctrl next" id="next" aria-label="Next slide">&#10095;</button>
      <div class="dots" id="dots" role="tablist" aria-label="Slide indicators"></div>
    </div>
  </section>
               <br><br>

            <a href="availability.php" class="btn" style="margin-top:6px;display:inline-block">check Availability</a>
              </div>
                </div>

                    </form>
                  </div>
                </aside>
              </div>
            </section>

            <!-- ABOUT (half yellow / half white) -->
            <section id="about" class="about container" aria-labelledby="about-heading">
              <div class="about-left" aria-hidden="false">
                <h3 id="about-heading">Welcome to Ric Hotel & Suites</h3>
                <div class="lead">The Most Preferred Hotel in West Africa</div>
                <p>
                  Ric Hotels & Suites is the most preferred hotel in West Africa, and it is all about the right mix! Located in the heart of Victoria Island, we offer our clients a perfect blend of business & leisure amenities with dining and recreational options delicately infused in one amazing space. We crown all these with services that meet the highest international standards.
                </p>
                <p>
                  Overlooking the new Eko Atlantic City and the Atlantic Ocean, it is just a 10-minute drive to the City Centre and only 45 minutes away from the airport. Our property consists of luxurious suites and rooms, conference facilities, world-class dining, and recreational services that ensure every stay is exceptional. Our experienced staff provides personalized service and attention to detail — making Eko Hotel & Suites an ideal choice for both business travelers and tourists and is also a developed area with a much of people and a well advanced hotel,with a good rendered services to our customer, we give them our best to be santify for what they came here for our service is well develop and it came with some few discount for our customer one in a month,any other services is alway rendered to good and well develop people.                </p>
                <a href="learnmore.php" class="btn" style="margin-top:6px;display:inline-block">Learn More</a>
              </div>

              <div class="about-right">
                <img src="images/image91.jpg" alt="About Eko Hotel">
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
              <div class="room-body"><h3>Ric Garden</h3><div class="price">₦45,000 / night</div><div>Garden view deluxe room with modern amenities.</div><div class="room-actions"><a class="btn" href="viewmore1.php">View More</a><a class="btn ghost" href="booking1.php">Book Now</a></div></div>
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

      <!-- RECREATIONAL SERVICES (black bg + moving images) -->
      <section id="recreation" class="recreation container" aria-labelledby="rec-heading">
        <h2 id="rec-heading">Recreational Services</h2>
        <p style="opacity:.85">Premium facilities for relaxation and fitness.</p>

        <div class="rec-grid" id="recGrid" style="margin-top:12px">
          <div class="rec-card" style="background-image:url('images/image120.jpg')" data-speed="0.2" tabindex="0">
            <div class="rec-overlay"><h3>Gymnasium</h3><p style="font-size:.9rem"> Our well eqquipped gym with sauna room are accessible to our in house fit fam junkies, and comes with available instructor State-of-the-art fitness center open 24/7.</p></div>
          </div>
          <div class="rec-card" style="background-image:url('images/image121.jpg')" data-speed="0.25" tabindex="0">
            <div class="rec-overlay"><h3>Swimming Pool</h3><p style="font-size:.9rem"> Additional perks of being an in house guest is to enjoy a day or night time swim. access to the pool for outside guests are at an additional cost outdoor pool with lounge and poolside service.</p></div>
          </div>
          <div class="rec-card" style="background-image:url('images/image124.jpg')" data-speed="0.18" tabindex="0">
            <div class="rec-overlay"><h3>Tennis Court</h3><p style="font-size:.9rem"> our floodlit courts is accessible for day & evening play.Access to the courth for outside guests,and tennis lesson are available at an additional cost hours.</p></div>
          </div>
          <div class="rec-card" style="background-image:url('images/image122.jpg')" data-speed="0.22" tabindex="0">
            <div class="rec-overlay"><h3>Spa</h3><p style="font-size:.9rem">You are invited to experience a variety of relaxing wellness treatment,from massage to getting everything done these pamparing session guarantee a memorable stay with us and relaxing treatments and massage therapies.</p></div>
          </div>
          <div class="rec-card" style="background-image:url('https://source.unsplash.com/1200x900/?salon,beauty')" data-speed="0.2" tabindex="0">
            <div class="rec-overlay"><h3>Saloon</h3><p style="font-size:.9rem">Professional grooming & styling.</p></div>
          </div>
          <div class="rec-card" style="background-image:url('images/image123.jpg')" data-speed="0.2" tabindex="0">
            <div class="rec-overlay"><h3>Nail Studio</h3><p style="font-size:.9rem">You are invited to experience a variety of relaxing wellness treatment,from massage to getting your hair and nails done .the pamparing session guarantee a memorable stay with usbeauty & nail services by appointment.</p></div>
          </div>
        </div>
      </section>

      <!-- DINING & RESTAURANTS -->
      <section id="dining" class="dining container" aria-labelledby="dining-heading">
        <h2 id="dining-heading">Dining & Bars</h2>
        <p style="color:#55606a">Exceptional restaurants & bars for every taste.</p>

        <div class="d-grid" style="margin-top:12px">
          <div class="d-card"><img src="images/image125.jpg" alt="Sky Restaurant"><div style="padding:12px"><h3>Sky Restaurant</h3><p>Fine dining with panoramic city views.</p></div></div>
          <div class="d-card"><img src="images/image126.jpg" alt="Atarado"><div style="padding:12px"><h3>Atarado</h3><p>Heritage flavors with contemporary twists.</p></div></div>
          <div class="d-card"><img src="images/image127.jpg" alt="Lagoon Breeze"><div style="padding:12px"><h3>Lagoon Breeze</h3><p>Seafood specialty facing the lagoon.</p></div></div>
          <div class="d-card"><img src="images/image128.jpg" alt="Calabash Bar"><div style="padding:12px"><h3>Calabash Bar</h3><p>Casual cocktails & local spirits.</p></div></div>
          <div class="d-card"><img src="images/image129.jpg" alt="Lagos Irish Pub"><div style="padding:12px"><h3>Lagos Irish Pub</h3><p>Live music and bar bites.</p></div></div>
          <div class="d-card"><img src="images/image130.jpg" alt="Red Chinese"><div style="padding:12px"><h3>Red Chinese</h3><p>Authentic Asian flavors and curated menu.</p></div></div>
        </div>
      </section>

      <!-- GALLERY (20 images) -->

        <!-- Slider -->
        <div class="slides" id="slides">
          <!-- Replace these src attributes with your local images -->
          <div class="slide" data-index="0"><img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1600&q=80&auto=format&fit=crop" alt="Slide 1"><div class="slide-caption">Luxury exterior view</div></div>
        </div>

        <button class="control prev" id="prevBtn" aria-label="Previous slide">&#10094;</button>
        <button class="control next" id="nextBtn" aria-label="Next slide">&#10095;</button>

        <div class="dots" id="dots" role="tablist" aria-label="Slide indicators"></div>
        </section>

        </div>
        </script>
        </body>
        </html>
      <!-- ====== MAP + CONTACT ====== -->
      <section id="contact" class="contact container" aria-labelledby="contact-heading">
        <h2 id="contact-heading">Find & Contact Us</h2>

        <div class="contact-grid" style="margin-top:12px">
          <div class="map">
            <!-- Replace query with exact coordinates or embed code for Eko Hotels & Suites -->
            <iframe
              src="https://www.google.com/maps?q=Eko+Hotels+and+Suites+Victoria+Island&output=embed"
              allowfullscreen="" loading="lazy"></iframe>
          </div>

          <div class="contact-info">
            <h3>Contact & Address</h3>
            <p><strong>Address:</strong> Command Road, Ipaja Expressway, Victoria Island, Lagos</p>
            <p><strong>Phone:</strong> +234 9021427575,+2347046964469</p>
            <p><strong>Email:</strong> Ogundelerichard202@gmail.com</p>

            <h4 style="margin-top:14px">Have a question?</h4>
            <p style="color:#55606a">Send us an email or call reservations. We’ll reply within 24 hours.</p>

            <form id="inquiryForm" onsubmit="return onDemoInquiry(event)" style="margin-top:8px">
              <label style="display:block;margin-bottom:8px"><input type="text" name="name" placeholder="Your name" required style="width:100%;padding:10px;border-radius:8px;border:1px solid #e4eaf0"></label>
              <label style="display:block;margin-bottom:8px"><input type="email" name="email" placeholder="Email" required style="width:100%;padding:10px;border-radius:8px;border:1px solid #e4eaf0"></label>
              <label style="display:block;margin-bottom:8px"><textarea name="message" placeholder="Your question" required style="width:100%;padding:10px;border-radius:8px;border:1px solid #e4eaf0;min-height:120px"></textarea></label>
              <button class="btn" type="submit">Send Inquiry</button>
            </form>
          </div>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="site-footer">
        <div>&copy; <span id="year"></span> Ric Hotel & Suites — All rights reserved.</div>
        <div style="opacity:.85;margin-top:6px">Demo site for client preview and development guidance. Replace demo forms with secure backend endpoints before production.</div>
      </footer>

      <!-- IMAGE MODAL -->
      <div id="modal" class="modal" aria-hidden="true">
        <div class="close" onclick="closeModal()" style="position:absolute;top:18px;right:24px;color:#fff;font-size:28px;cursor:pointer;">&times;</div>
        <img id="modalImg" src="" alt="Preview">
      </div>

      <!-- SCRIPTS -->
      <script>
        document.getElementById('year').textContent = new Date().getFullYear();

        /* Slider */
        (function(){
          const slides = document.getElementById('slides');
          const total = slides.children.length;
          let idx = 0;
          const prev = document.getElementById('prev');
          const next = document.getElementById('next');

          function show(i){
            idx = (i + total) % total;
            slides.style.transform = 'translateX(' + (-idx * 100) + '%)';
          }
          prev.addEventListener('click', ()=> show(idx-1));
          next.addEventListener('click', ()=> show(idx+1));
          // auto slide every 5s
          setInterval(()=> show(idx+1), 5000);
        })();

        /* Modal functions */
        function openModal(src){
          const modal = document.getElementById('modal');
          const img = document.getElementById('modalImg');
          img.src = src;
          modal.classList.add('show');
          modal.setAttribute('aria-hidden','false');
        }
        function closeModal(){
          const modal = document.getElementById('modal');
          modal.classList.remove('show');
          modal.setAttribute('aria-hidden','true');
        }
        document.getElementById('modal').addEventListener('click', function(e){
          if(e.target === this) closeModal();
        });
        document.addEventListener('keydown', function(e){
          if(e.key === 'Escape') closeModal();
        });

        /* Demo booking handler */
        function onDemoBooking(e){
          e.preventDefault();
          const form = e.target;
          const arrival = form.arrival.value;
          const departure = form.departure.value;
          const room = form.room_type.value;
          const email = form.email.value;
          alert('Demo booking request received:\\nArrival: ' + arrival + '\\nDeparture: ' + departure + '\\nRoom: ' + room + '\\nA confirmation will be sent to ' + email + '\\n\\nNote: this demo does not store data. Connect the form to your backend for production.');
          form.reset();
          return false;
        }

        /* Demo inquiry */
        function onDemoInquiry(e){
          e.preventDefault();
          const f = e.target;
          alert('Thank you, ' + (f.name.value || 'Guest') + '!\\nWe received your message and will reply to ' + f.email.value + '. (Demo only)');
          f.reset();
          return false;
        }

        /* Scroll helper */
        function scrollToSection(id){
          const el = document.getElementById(id);
          if(el) el.scrollIntoView({behavior:'smooth',block:'start'});
        }

        /* Parallax-ish effect for recreation cards */
        (function(){
          const recCards = document.querySelectorAll('.rec-card');
          window.addEventListener('mousemove', function(e){
            recCards.forEach(card=>{
              const rect = card.getBoundingClientRect();
              const speed = parseFloat(card.dataset.speed || 0.12);
              const x = (e.clientX - (rect.left + rect.width/2)) * speed / 100;
              const y = (e.clientY - (rect.top + rect.height/2)) * speed / 100;
              card.style.transform = `translate(${x}px, ${y}px)`;
            });
          });
          window.addEventListener('mouseout', function(){ recCards.forEach(c=>c.style.transform=''); });
        })();
      </script>
      </body>
      </html>
          