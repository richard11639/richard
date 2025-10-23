<?php
// view-more.php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Family room— View More</title>
  <meta name="description" content="Eko Estate details, rooms, amenities and contact information.">
  <style>
    /* ---------- Palette & Reset ---------- */
    :root{
      --black:#0b0b0b;
      --white:#ffffff;
      --muted:#7a7a7a;
      --accent:#111111;
      --glass: rgba(255,255,255,0.04);
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%}
    body{font-family:Inter,system-ui,Arial,sans-serif;background:var(--white);color:var(--black);line-height:1.55}
    a{color:inherit}
    img{max-width:100%;display:block}

    .container{max-width:1100px;margin:24px auto;padding:18px}

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

    /* ---------- Single image with HOME overlay ---------- */
    .hero-home{margin-top:18px;position:relative;border-radius:12px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.06)}
    .hero-home img{width:100%;height:40vh;object-fit:cover}
    .hero-home .home-badge{position:absolute;left:20px;top:20px;background:rgba(0,0,0,0.75);color:#fff;padding:10px 14px;border-radius:8px;font-weight:800}

    /* ---------- Content / View More ---------- */
    .content{margin-top:20px;background:#fff;padding:20px;border-radius:10px;border:1px solid #f0f0f0;box-shadow:0 12px 30px rgba(2,2,2,0.03)}
    .content h2{font-size:1.25rem;margin-bottom:8px}
    .content .lead{font-weight:700;margin-bottom:12px}
    .content p{color:var(--muted);margin-bottom:8px}

    .features{display:flex;flex-wrap:wrap;gap:12px;margin-top:12px}
    .feature{background:var(--black);color:var(--white);padding:10px 12px;border-radius:8px;font-weight:700}

    /* ---------- Room overview ---------- */
    .rooms{margin-top:20px}
    .room-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
    .room-card{border-radius:10px;overflow:hidden;border:1px solid #eee;background:#fff;box-shadow:0 10px 28px rgba(2,2,2,.03)}
    .room-card img{height:220px;object-fit:cover}
    .room-body{padding:14px}
    .room-body h3{margin-bottom:6px}
    .meta{color:var(--muted);font-size:.95rem;margin-bottom:8px}
    .room-body p{color:#333}
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

    /* ---------- Footer / Contact ---------- */
    footer.site-footer{margin-top:22px;padding:18px;border-top:1px solid #eee;background:#fafafa;border-radius:8px}
    .contact{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
    .contact .info{color:var(--muted)}
    .contact a{color:var(--black);font-weight:700}

    /* ---------- Modal ---------- */
    .modal{position:fixed;inset:0;background:rgba(0,0,0,0.86);display:flex;align-items:center;justify-content:center;visibility:hidden;opacity:0;transition:opacity .18s}
    .modal.show{visibility:visible;opacity:1}
    .modal img{max-width:92%;max-height:86%;border-radius:8px}
    .modal .close{position:absolute;right:22px;top:18px;color:#fff;font-size:28px;cursor:pointer}

    /* ---------- Responsive ---------- */
    @media (max-width:800px){
      .slide{height:36vh}
      .hero-home img{height:30vh}
    }
  </style>
</head>
<body>

  <header class="site-header container" role="banner">
    <div class="brand">
      <div class="logo">RIC</div>
      <div>
        <h1>Family room — View More</h1>
        <small style="color:var(--muted)">Victoria Island • Lagos</small>
      </div>
    </div>

    <nav class="nav" role="navigation" aria-label="Main nav">
      <a href="booking5.php">booking</a>
      <a href="index6.php">home</a>
    </nav>
  </header>

  <main class="container">

    <!-- 2 moving pictures (slider) -->
    <section aria-label="Image slider" style="margin-top:18px">
      <div class="slider" id="slider">
        <div class="slides" id="slides">
          <div class="slide">
            <img src="images/image162.jpg" alt="Eko Estate exterior">
            <div class="overlay">family room — Exterior</div>
          </div>
          <div class="slide">
            <img src="images/image163.jpg"
            te — Interior</div>
          </div>
        </div>
        <button class="ctrl prev" id="prev" aria-label="Previous">&#10094;</button>
        <button class="ctrl next" id="next" aria-label="Next">&#10095;</button>
        <div class="dots" id="dots" aria-hidden="false"></div>
      </div>
    </section>

    <!-- separate image with HOME inside -->
    <section class="hero-home" aria-label="Home hero">
      <img src="images/image160.jpg" alt="Home image">
    </section>

    <!-- View More story -->
    <section id="view-story" class="content" aria-labelledby="view-title">
      <h2 id="view-title">View More — presidential Estate Story</h2>
      <p class="lead">All the rooms are well appointed and designed to provide the comfort of human beings.</p>

      <p> Beside the room  and they are soo beautiful and well kept with a high cleaning are well appointed and designed to provide comfort and convenience.beside the usual amenities such as central air conditioning, satellite TV, fast internet connection,
      a fridge and bathroom amenities of the highest quality, the guest can also have fast internet access
      in all the public areas through our Wi-Fi (wireless) network. Our spaces are crafted to balance
      Lagos energy with restorative calm, creating a seamless experience for business and leisure.</p>

      <div class="features" aria-hidden="false">
        <div class="feature">Central Air Conditioning</div>
        <div class="feature">Satellite TV</div>
        <div class="feature">Fast Internet</div>
        <div class="feature">Premium Bath Amenities</div>
      </div>
    </section>

    <!-- Rooms overview -->
    <section id="rooms" class="rooms" aria-labelledby="rooms-title">
      <h2 id="rooms-title" style="margin-top:18px">Room Overview</h2>

      <div class="room-grid" style="margin-top:12px">

        <!-- Standard Room -->
        <article class="room-card">
          <img src="images/image163.jpg" alt="family deluxe Room" onclick="openModal(this.src)" style="cursor:pointer">
          <div class="room-body">
            <h3>Family deluxe room (Garden)</h3>
            <div class="meta">Room side: <strong>19.68</strong>  —  Bathroom: <strong>6.82</strong>  -Balcony: <strong>4.31</strong> </div>
            <p>standard Room with marble floors and a great view of the city with smoking or non-smoking room available</p>
            <a class="btn ghost" href="booking5.php">Book Now</a></div></div> 
          </div>
        </article>
 <!-- Standard Room -->
        <article class="room-card">
          <img src="images/image163.jpg" alt="Standard family Room" onclick="openModal(this.src)" style="cursor:pointer">
          <div class="room-body">
            <h3>Standard family Room </h3>
            <div class="meta">Room side: <strong>30.68</strong>  —  Bathroom: <strong>7.82</strong></div>
            <p>Standard room with marble floor and a great view of the city with smoking or non smoking room available </p>
            <a class="btn ghost" href="booking5.php">Book Now</a></div></div> 
          </div>
        </article>
        <!-- Classic Room -->
        <article class="room-card">
          <img src="images/image165.jpg" alt="executive Superior room" onclick="openModal(this.src)" style="cursor:pointer">
          <div class="room-body">
            <h3>Executive presidential Room</h3>
            <div class="meta">Room side: <strong>26.50</strong>  —  Bathroom: <strong>7.90</strong></div>
            <p>standard room with marible floor and a great view of the Atlantic with smoking or non-smoking rooms available.</p>
            <a class="btn ghost" href="booking5.php">Book Now</a></div></div> 
          </div>
        </article>
 <!-- Standard Room -->
        <article class="room-card">
          <img src="images/image164.jpg" alt="Diplomatic family suites" onclick="openModal(this.src)" style="cursor:pointer">
          <div class="room-body">
            <h3>Diplomatic family suites</h3>
            <div class="meta">Room side: <strong>29.68</strong>  —  Bathroom: <strong>6.82</strong></div>
            <p>The Diplomatic suites are stately with ostentatious design and breath-taking view of the atlantic.The suite has a DVD player,and a seprate lounge,bar and dining area for guests.it has an adjoining standard room that make it perfect for a large family.</p>
            <a class="btn ghost" href="booking5.php">Book Now</a></div></div> 
          </div>
        </article>
      </div>
    </section>

    <!-- Contact & footer -->
    <footer id="contact" class="site-footer" role="contentinfo">
      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between">
        <div class="contact-block">
          <h4 style="margin:0 0 6px">Our Information</h4>
          <div class="info" style="color:var(--muted)">
            <div><strong>Address:</strong> Command Road, Ipaja Expressway, Victoria Island, Lagos</div>
            <div><strong>Call us:</strong> <a href="tel:+2349021427575">0902 142 7575</a></div>
            <div><strong>Email:</strong> <a href="mailto:reservations@ekohotels.com">reservations@richotels.com</a></div>
          </div>
        </div>

        <div style="text-align:right">
          <small style="color:var(--muted)">© <?php echo date('Y'); ?> Ric Estate — All rights reserved.</small>
        </div>
      </div>
    </footer>
  </main>

  <!-- image modal -->
  <div id="modal" class="modal" aria-hidden="true" onclick="if(event.target===this) closeModal()">
    <div class="close" onclick="closeModal()" style="position:absolute;right:22px;top:18px;color:#fff;font-size:26px;cursor:pointer">&times;</div>
    <img id="modalImg" src="" alt="Preview">
  </div>

  <script>
    // ---------- Slider logic ----------
    (function(){
      const slides = document.getElementById('slides');
      const total = slides.children.length;
      const prev = document.getElementById('prev');
      const next = document.getElementById('next');
      const dotsWrap = document.getElementById('dots');
      let idx = 0, timer;

      for(let i=0;i<total;i++){
        const d = document.createElement('div'); d.className = 'dot' + (i===0?' active':''); d.dataset.i = i;
        d.addEventListener('click', ()=>goto(parseInt(d.dataset.i,10)));
        dotsWrap.appendChild(d);
      }
      const dots = Array.from(dotsWrap.children);

      function render(){ slides.style.transform = `translateX(${-idx*100}%)`; dots.forEach((d,i)=> d.classList.toggle('active', i===idx)); }
      function goto(i){ idx = (i+total)%total; render(); restart(); }
      function nextFn(){ goto(idx+1); }
      function prevFn(){ goto(idx-1); }
      prev.addEventListener('click', prevFn);
      next.addEventListener('click', nextFn);

      function start(){ timer = setInterval(nextFn, 4500); }
      function stop(){ clearInterval(timer); }
      function restart(){ stop(); start(); }
      document.getElementById('slider').addEventListener('mouseenter', stop);
      document.getElementById('slider').addEventListener('mouseleave', start);
      render(); start();
    })();

    // ---------- Modal for clickable images ----------
    function openModal(src){
      const m = document.getElementById('modal');
      const img = document.getElementById('modalImg');
      img.src = src;
      m.classList.add('show');
      m.setAttribute('aria-hidden','false');
    }
    function closeModal(){
      const m = document.getElementById('modal');
      m.classList.remove('show');
      m.setAttribute('aria-hidden','true');
      document.getElementById('modalImg').src = '';
    }
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeModal(); });
  </script>
</body>
</html>
