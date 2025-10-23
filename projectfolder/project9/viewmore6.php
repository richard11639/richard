<?php
// view-more.php
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Business room— View More</title>
<meta name="description" content="Eko Hotel & Suites — detailed room overview, guest facilities, dining, and recreation.">
<style>
  :root{
    --ink:#0e2433;
    --bg:#f6f8fb;
    --card:#ffffff;
    --accent:#ffb703;
    --deep:#02233a;
    --muted:#5a6b79;
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{margin:0;font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial; background:var(--bg); color:var(--ink); line-height:1.55}
  img{max-width:100%; display:block}
  .container{max-width:1180px;margin:0 auto;padding:18px}

  /* Header */
  .site-header{background:linear-gradient(90deg,#021428,#03314a); color:#fff; position:sticky; top:0; z-index:50; box-shadow:0 10px 28px rgba(2,22,36,.25)}
  .topbar{display:flex; align-items:center; gap:14px; padding:12px 18px}
  .logo{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#ffd166,#ff7b54);display:grid;place-items:center;font-weight:900;color:#042;letter-spacing:.3px}
  .brand{display:flex; flex-direction:column}
  .brand h1{font-size:1.05rem;margin:0}
  .brand small{opacity:.85}

  /* Hero slider (2 images) */
  .hero{padding:18px}
  .slider{position:relative; overflow:hidden; border-radius:14px; background:#000; box-shadow:0 20px 48px rgba(0,0,0,.35)}
  .slides{display:flex; transition:transform .6s cubic-bezier(.2,.8,.2,1)}
  .slide{min-width:100%; height:58vh; position:relative}
  .slide img{width:100%;height:100%;object-fit:cover}
  .caption{position:absolute;left:16px;bottom:16px;background:rgba(0,0,0,.38);color:#fff;padding:10px 14px;border-radius:10px;font-weight:700;backdrop-filter:blur(4px)}
  .ctrl{position:absolute; top:50%; transform:translateY(-50%); background:rgba(0,0,0,.45); color:#fff; border:0; padding:10px 12px; border-radius:10px; cursor:pointer}
  .prev{left:12px} .next{right:12px}
  .dots{position:absolute;left:50%;transform:translateX(-50%);bottom:10px;display:flex;gap:8px}
  .dot{width:9px;height:9px;border-radius:999px;background:rgba(255,255,255,.35); cursor:pointer}
  .dot.active{background:var(--accent); box-shadow:0 6px 18px rgba(0,0,0,.45)}

  /* Hand-moving grid (4 tiles) */
  .hand-wrap{margin-top:18px; background:linear-gradient(180deg,#021b2d,#032b41); border-radius:14px; padding:18px; color:#fff}
  .hand-title{display:flex;justify-content:space-between;align-items:center;gap:12px}
  .hand-title h2{margin:0;color:#ffd166}
  .hand-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; margin-top:12px; perspective:900px}
  .card-parallax{position:relative;height:190px;border-radius:12px;overflow:hidden;background:#0a2d3e;transform-style:preserve-3d;box-shadow:0 14px 32px rgba(0,0,0,.35)}
  .card-parallax img{position:absolute;inset:0;width:115%;height:115%;object-fit:cover; transition:transform .14s linear}
  .badge{position:absolute;left:12px;bottom:12px;background:rgba(0,0,0,.45);color:#fff;padding:8px 10px;border-radius:10px;font-weight:700}

  /* View More text section */
  .content{background:var(--card); border-radius:14px; padding:20px; margin-top:18px; box-shadow:0 14px 36px rgba(2,22,36,.08)}
  .content h3{margin:0 0 10px}
  .lead{font-weight:700;color:var(--deep)}
  .features{display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; margin-top:10px}
  .chip{background:#0b2d40; color:#fff; padding:10px 12px; border-radius:10px}

  /* Room overview */
  .rooms{margin-top:18px}
  .room-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px}
  .room-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 12px 30px rgba(2,22,36,.08)}
  .room-card img{height:180px;object-fit:cover}
  .room-body{padding:14px}
  .room-body h4{margin:0 0 6px}
  .meta{color:var(--muted);font-size:.92rem}

              .hero-right{background:rgba(255,255,255,0.98);padding:14px;border-radius:10px}
              .avail h3{margin-bottom:8px}
              .avail label{display:block;margin-bottom:8px;font-size:.9rem}
              .avail input,.avail select{width:100%;padding:10px;border-radius:8px;border:1px solid #e4eaf0;margin-top:6px}
              .btn{display:inline-block;padding:10px 14px;border-radius:8px;font-weight:800;background:#ff7b54;color:#fff;border:none;cursor:pointer}
              .btn.ghost{background:transparent;border:2px solid rgba(2,22,36,0.06);color:#071428}


  /* Footer contact */
  .footer{margin:22px 0;background:#022033;color:#fff;border-radius:12px; padding:18px}
  .contact{display:grid; grid-template-columns:1.3fr .7fr; gap:14px}
  .contact .box{background:rgba(255,255,255,.06); padding:14px; border-radius:10px}
  .contact a{color:#ffe08a}

  /* Responsive */
  @media (max-width:860px){
    .slide{height:44vh}
    .contact{grid-template-columns:1fr}
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
      <a href="booking6.php">booking</a>
      <a href="index6.php">home</a>
    </nav>
  </header>

  <main class="container">

  <!-- 2 moving pictures -->
  <section class="hero container" aria-label="Two-image slider">
    <div class="slider" id="slider">
      <div class="slides" id="slides">
        <!-- Replace image sources with your assets -->
        <div class="slide">
          <img src="images/image165.jpg" alt="Eko Estate exterior at dusk">
          <div class="caption">Eko Estate — Evening Facade</div>
        </div>
        <div class="slide">
          <img src="images/image151.jpg" alt="Signature suite interior">
          <div class="caption">Signature Suite — Serenity & Space</div>
        </div>
      </div>
      <button class="ctrl prev" id="prev" aria-label="Previous slide">&#10094;</button>
      <button class="ctrl next" id="next" aria-label="Next slide">&#10095;</button>
      <div class="dots" id="dots" role="tablist" aria-label="Slide indicators"></div>
    </div>
  </section>

  <!-- View More narrative + features -->
  <section class="container content" id="view-more">
    <h3>About the Rooms — Eko Estate Story</h3>
    <p class="lead">
      All rooms are well appointed and designed to provide the comfort of human beings.
    </p>
    <p>
      Beside the room are well appointed and designed to provide comfort and convenience.beside the usual amenities such as central air conditioning, satellite TV, fast internet connection,
      a fridge and bathroom amenities of the highest quality, the guest can also have fast internet access
      in all the public areas through our Wi-Fi (wireless) network. Our spaces are crafted to balance
      Lagos energy with restorative calm, creating a seamless experience for business and leisure.
    </p>

    <div class="features">
      <div class="chip">Central Air Conditioning</div>
      <div class="chip">Satellite TV</div>
      <div class="chip">High-Speed Internet</div>
      <div class="chip">Premium Bath Amenities</div>
      <div class="chip">In-room Fridge</div>
      <div class="chip">Property-wide Wi-Fi</div>
    </div>
  </section>

  <!-- Room Overview -->
  <section class="container rooms" aria-label="Room overview">
    <h3 style="margin:10px 0">Overview of the Rooms</h3>
    <div class="room-grid">
      <!-- Standard Room -->
      <article class="room-card">
        <img src="images/image163.jpg" alt="Standard (Garden) Room">
        <div class="room-body">
          <h4>Eko  (Garden)</h4>
          <p class="meta">Room Size: <strong>29.68M</strong> &nbsp;|&nbsp; Bathroom: <strong>6.82M</strong></p>
          <p>
            The garden standard room occupies the left wing overlooking the greenery —
            cozy, efficient, and quietly elegant for everyday stays,has spacious room with large windows,fully air conditioned,phone,cable tv,fridge,and a view of the city lagos.
          </p>
           <a class="btn ghost" href="booking6.php">Book Now</a></div></div> 
        </div>
      </article>
 <!-- Standard Room -->
        <article class="room-card">
          <img src="images/image163.jpg" alt="Standard family Room" onclick="openModal(this.src)" style="cursor:pointer">
          <div class="room-body">
            <h3>Standard family Room </h3>
            <div class="meta">Room side: <strong>30.68</strong>  —  Bathroom: <strong>7.82</strong></div>
            <p>Standard room with marble floor and a great view of the city with smoking or non smoking room available </p>
             <a class="btn ghost" href="booking6.php">Book Now</a></div></div> 
         </div>
        </article>
        <!-- Classic Room -->
        <article class="room-card">
          <img src="images/image165.jpg" alt="executive Superior room" onclick="openModal(this.src)" style="cursor:pointer">
          <div class="room-body">
            <h3>Executive presidential Room</h3>
            <div class="meta">Room side: <strong>26.50</strong>  —  Bathroom: <strong>7.90</strong></div>
            <p>standard room with marible floor and a great view of the Atlantic with smoking or non-smoking rooms available.</p>
             <a class="btn ghost" href="booking6.php">Book Now</a></div></div> 
          </div>
        </article>
      <!-- Classic Room -->
      <article class="room-card">
        <img src="images/image157.jpg" alt="Classic Room">
        <div class="room-body">
          <h4>business garden(2 room)</h4>
          <p class="meta">Room Size: <strong>26.50</strong> &nbsp;|&nbsp; Bathroom: <strong>7.90</strong></p>
          <p>
            A taking up the entire right wing of the garden,the kuramo garden classic room are totally refurbished spacious room with a mini loune for the visitors, large windows, fully air-conditioned,direct dial telephone,drefined classic with smart layout and generous bath; ideal for
            business travelers who want comfort with a polished touch.
          </p>
           <a class="btn ghost" href="booking6.php">Book Now</a></div></div> 
        </div>
      </article>
    </div>
  </section>

  <!-- Contact / Footer -->
  <footer class="container footer" aria-label="Contact information">
    <div class="contact">
      <div class="box">
        <h4 style="margin:0 0 6px">Our Information</h4>
        <p style="margin:.25rem 0"><strong>Address:</strong> Command Road, Ipaja Expressway, Victoria Island, Lagos</p>
        <p style="margin:.25rem 0"><strong>Call us:</strong> <a href="tel:+2349021427575">0902 142 7575</a></p>
        <p style="margin:.25rem 0"><strong>Email:</strong> <a href="mailto:reservations@ekohotels.com">reservations@richotels.com</a></p>
      </div>
      <div class="box">
        <h4 style="margin:0 0 6px">Questions?</h4>
        <p style="margin:.25rem 0">We’re happy to help with availability, rates and events.</p>
        <a href="mailto:reservations@ekohotels.com" style="display:inline-block;margin-top:8px;background:#ff7b54;color:#fff;padding:10px 12px;border-radius:8px;text-decoration:none;font-weight:700">Email Reservations</a>
      </div>
    </div>
    <p style="text-align:center;margin-top:12px;opacity:.85">&copy; <?php echo date('Y'); ?> Ric Hotel & Suites — All rights reserved.</p>
  </footer>
</main>

<script>
/* ========== Mini Slider (2 images) ========== */
(function(){
  const slidesEl = document.getElementById('slides');
  const slides = Array.from(slidesEl.children);
  const prev = document.getElementById('prev');
  const next = document.getElementById('next');
  const dotsWrap = document.getElementById('dots');
  let i = 0, timer;

  // dots
  slides.forEach((_, idx)=>{
    const b = document.createElement('button');
    b.className = 'dot' + (idx===0?' active':'');
    b.setAttribute('aria-label', 'Go to slide ' + (idx+1));
    b.addEventListener('click', ()=> go(idx));
    dotsWrap.appendChild(b);
  });
  const dots = Array.from(dotsWrap.children);

  function render(){
    slidesEl.style.transform = `translateX(${-i*100}%)`;
    dots.forEach((d,idx)=> d.classList.toggle('active', idx===i));
  }
  function go(n){ i = (n + slides.length) % slides.length; render(); restart(); }
  function nextFn(){ go(i+1); }
  function prevFn(){ go(i-1); }

  prev.addEventListener('click', prevFn);
  next.addEventListener('click', nextFn);

  function start(){ timer = setInterval(nextFn, 4500); }
  function stop(){ clearInterval(timer); }
  function restart(){ stop(); start(); }

  document.getElementById('slider').addEventListener('mouseenter', stop);
  document.getElementById('slider').addEventListener('mouseleave', start);

  render(); start();
})();

/* ========== Hand-moving (parallax) tiles ========== */
(function(){
  const grid = document.getElementById('handGrid');
  if(!grid) return;
  const cards = Array.from(grid.querySelectorAll('.card-parallax'));

  function move(e){
    const rect = grid.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;

    cards.forEach(card=>{
      const r = card.getBoundingClientRect();
      const cx = r.left - rect.left + r.width/2;
      const cy = r.top - rect.top + r.height/2;
      const dx = x - cx, dy = y - cy;
      const speed = parseFloat(card.dataset.speed || 18);
      const tx = Math.max(-28, Math.min(28, (dx/rect.width)*speed));
      const ty = Math.max(-28, Math.min(28, (dy/rect.height)*speed));
      const img = card.querySelector('img');
      img.style.transform = `translate3d(${tx}px, ${ty}px, 0) scale(1.06)`;
    });
  }
  function reset(){ cards.forEach(c=> c.querySelector('img').style.transform=''); }

  grid.addEventListener('pointermove', move);
  grid.addEventListener('pointerleave', reset);
  grid.addEventListener('touchmove', move, {passive:true});
  grid.addEventListener('touchend', reset);
})();
</script>
</body>
</html>
