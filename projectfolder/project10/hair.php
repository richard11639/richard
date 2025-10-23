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
              nav.main-nav a:hover{background:rgba(184, 17, 17, 0.75)}
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
                  <div class="logo">TRB</div>
                  <div>
                    <h1>Tripple r all business</h1>
                    <small>Command lagos — Lagos</small>
                  </div>
                </div>
 <nav class="main-nav" aria-label="Main navigation">
                  <a href="rooms.php">Hairs</a>
                  <a href="gallery.php">Charger</a>
                  <a href="gallery.php">Ponmo</a>
                  <a href="feedback.php">Feedback</a>
                  <a href="abouts.php">About us</a>
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
                      <h2 style="color:#fff">TRIPPLR R ALL BUSINESS</h2>
                      <div class="hero-tag">The Most  Biggest And Preferred Business in Nigeria,And Most Affordable Price In Nigeria</div>
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
          <img src="images/image91.jpg" alt="the main business is hair">
          <div class="caption">Hair</div>
        </div>
        <div class="slide">
          <img src="images/images83.jpg" alt="the main business is hair">
          <div class="caption">Hair</div>
        </div>
         <div class="slide">
          <img src="images/image87.jpg" alt="the main business is hair">
          <div class="caption">Hair</div>
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
