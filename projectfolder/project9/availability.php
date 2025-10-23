<?php
// view-more.php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>RIC Estate — View More</title>
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

 /* ---------- Contact / Map ---------- */
              .contact{padding:28px 18px;background:#fff;border-radius:10px;margin:18px 0}
              .contact-grid{display:grid;grid-template-columns:1fr 380px;gap:16px}
              .contact .map iframe{width:100%;height:320px;border:0;border-radius:8px}
              .contact-info h3{margin-bottom:8px}
              
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
        <h1>Avalability images</h1>
        <small style="color:var(--muted)">Victoria Island • Lagos</small>
      </div>
    </div>

    <nav class="nav" role="navigation" aria-label="Main nav">
      <a href="#booking">more</a>
      <a href="index6.php">home</a>
    </nav>
  </header>

  <main class="container">

    <!-- 2 moving pictures (slider) -->
    <section aria-label="Image slider" style="margin-top:18px">
      <div class="slider" id="slider">
        <div class="slides" id="slides">
          <div class="slide">
            <img src="images/image86.jpg" alt="Eko Estate exterior">
            <div class="overlay">More images availability — Exterior</div>
          </div>
          <div class="slide">
            <img src="images/image106.jpg"
            te — Interior</div>
          </div>
        </div>
        <button class="ctrl prev" id="prev" aria-label="Previous">&#10094;</button>
        <button class="ctrl next" id="next" aria-label="Next">&#10095;</button>
        <div class="dots" id="dots" aria-hidden="false"></div>
      </div>
    </section>
<!-- 2 moving pictures -->
  <section class="hero container" aria-label="Two-image slider">
    <div class="slider" id="slider">
      <div class="slides" id="slides">
        <!-- Replace image sources with your assets -->
        <div class="slide">
          <img src="images/image87.jpg" alt="Eko Estate exterior at dusk">
          <div class="caption">availability images — Evening Facade</div>
        </div>
        <div class="slide">
          <img src="images/image84.jpg" alt="Signature suite interior">
          <div class="caption">availability images — Serenity & Space</div>
        </div>
      </div>
      <button class="ctrl prev" id="prev" aria-label="Previous slide">&#10094;</button>
      <button class="ctrl next" id="next" aria-label="Next slide">&#10095;</button>
      <div class="dots" id="dots" role="tablist" aria-label="Slide indicators"></div>
    </div>
  </section>
