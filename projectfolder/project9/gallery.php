<?php
// view-more.php
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Eko Estate — View More</title>
  <style>
    body{font-family:Arial, sans-serif;margin:0;padding:0;background:#fff;color:#000}
    .container{max-width:1100px;margin:auto;padding:18px}
    header{display:flex;justify-content:space-between;align-items:center;padding:18px;border-bottom:1px solid #eee}
    .brand{display:flex;align-items:center;gap:12px}
    .logo{width:50px;height:50px;background:#000;color:#fff;display:grid;place-items:center;font-weight:900}
    .nav a{margin-left:12px;text-decoration:none;color:#000;font-weight:600}
    .slider{position:relative;overflow:hidden;border-radius:12px;margin:20px 0}
    .slides{display:flex;transition:transform .6s ease}
    .slide{min-width:100%;height:46vh;position:relative;cursor:pointer}
    .slide img{width:100%;height:100%;object-fit:cover;border-radius:8px}
    .overlay{position:absolute;left:18px;bottom:18px;background:rgba(0,0,0,0.6);color:#fff;padding:6px 10px;border-radius:6px}
    .ctrl{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.5);color:#fff;border:0;padding:10px;cursor:pointer}
    .prev{left:12px} .next{right:12px}
    .dots{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:8px}
    .dot{width:10px;height:10px;background:#ccc;border-radius:50%;cursor:pointer}
    .dot.active{background:#000}
    .contact{background:#f9f9f9;padding:20px;border-radius:10px;margin:20px 0}
    .contact-grid{display:grid;grid-template-columns:1fr 350px;gap:16px}
    .map iframe{width:100%;height:300px;border:0;border-radius:8px}

    /* Lightbox Modal */
    .lightbox {
      display:none;
      position:fixed;
      z-index:1000;
      top:0;left:0;width:100%;height:100%;
      background:rgba(0,0,0,0.9);
      justify-content:center;
      align-items:center;
    }
    .lightbox img {
      max-width:90%;
      max-height:90%;
      border-radius:10px;
      box-shadow:0 0 20px rgba(255,255,255,0.3);
    }
    .lightbox span {
      position:absolute;
      top:20px;right:30px;
      font-size:40px;
      color:#fff;
      cursor:pointer;
      font-weight:bold;
    }
  </style>
</head>
<body>

<header class="container">
  <div class="brand">
    <div class="logo">RIC</div>
    <div>
      <h1>GALLERY</h1>
      <small style="color:#7a7a7a">Victoria Island • Lagos</small>
    </div>
  </div>
  <nav class="nav">
    <a href="#booking">More</a>
    <a href="index6.php">Home</a>
  </nav>
</header>

<main class="container">

  <!-- Slider 1 -->
  <div class="slider" id="slider1">
    <div class="slides">
      <div class="slide"><img src="images/image150.jpg"><div class="overlay">Ric Estate — Evening Facade</div></div>
      <div class="slide"><img src="images/image151.jpg"><div class="overlay">Signature Suite — Serenity</div></div>
    </div>
    <button class="ctrl prev">&#10094;</button>
    <button class="ctrl next">&#10095;</button>
    <div class="dots"></div>
  </div>

  <!-- Slider 2 -->
  <div class="slider" id="slider2">
    <div class="slides">
      <div class="slide"><img src="images/image157.jpg"><div class="overlay">Executive Room — Exterior</div></div>
      <div class="slide"><img src="images/image158.jpg"><div class="overlay">Executive Room — Interior</div></div>
    </div>
    <button class="ctrl prev">&#10094;</button>
    <button class="ctrl next">&#10095;</button>
    <div class="dots"></div>
  </div>

  <!-- Slider 3 -->
  <div class="slider" id="slider3">
    <div class="slides">
      <div class="slide"><img src="images/image150.jpg"><div class="overlay">Presidential Room — Exterior</div></div>
      <div class="slide"><img src="images/image152.jpg"><div class="overlay">Presidential Room — Interior</div></div>
    </div>
    <button class="ctrl prev">&#10094;</button>
    <button class="ctrl next">&#10095;</button>
    <div class="dots"></div>
  </div>

  <!-- Slider 4 -->
  <div class="slider" id="slider4">
    <div class="slides">
      <div class="slide"><img src="images/image162.jpg"><div class="overlay">Pent House — Exterior</div></div>
      <div class="slide"><img src="images/image163.jpg"><div class="overlay">Pent House — Interior</div></div>
    </div>
    <button class="ctrl prev">&#10094;</button>
    <button class="ctrl next">&#10095;</button>
    <div class="dots"></div>
  </div>

  <!-- Slider 5 -->
  <div class="slider" id="slider5">
    <div class="slides">
      <div class="slide"><img src="images/image115.jpg"><div class="overlay">Studio — Exterior</div></div>
      <div class="slide"><img src="images/image114.jpg"><div class="overlay">Studio Room — Interior</div></div>
    </div>
    <button class="ctrl prev">&#10094;</button>
    <button class="ctrl next">&#10095;</button>
    <div class="dots"></div>
  </div>

  <!-- Slider 6 -->
  <div class="slider" id="slider6">
    <div class="slides">
      <div class="slide"><img src="images/image113.jpg"><div class="overlay">Family Room — Exterior</div></div>
      <div class="slide"><img src="images/image112.jpg"><div class="overlay">Family — Interior</div></div>
    </div>
    <button class="ctrl prev">&#10094;</button>
    <button class="ctrl next">&#10095;</button>
    <div class="dots"></div>
  </div>

  <!-- Slider 7 -->
  <div class="slider" id="slider7">
    <div class="slides">
      <div class="slide"><img src="images/image111.jpg"><div class="overlay">Deluxe Room — Exterior</div></div>
      <div class="slide"><img src="images/image155.jpg"><div class="overlay">Deluxe Room — Interior</div></div>
    </div>
    <button class="ctrl prev">&#10094;</button>
    <button class="ctrl next">&#10095;</button>
    <div class="dots"></div>
  </div>

  <!-- Contact -->
  <section class="contact">
    <h2>Find & Contact Us</h2>
    <div class="contact-grid">
      <div class="map">
        <iframe src="https://www.google.com/maps?q=Eko+Hotels+and+Suites+Victoria+Island&output=embed"></iframe>
      </div>
      <div>
        <p><strong>Address:</strong> Command Road, Victoria Island, Lagos</p>
        <p><strong>Phone:</strong> +234 9021427575, +234 7046964469</p>
        <p><strong>Email:</strong> Ogundelerichard202@gmail.com</p>
      </div>
    </div>
  </section>
</main>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
  <span id="closeBtn">&times;</span>
  <img id="lightboxImg" src="" alt="">
</div>

<script>
function initSlider(sliderId){
  const slider=document.getElementById(sliderId);
  const slides=slider.querySelector(".slides");
  const slideItems=slides.children;
  const prev=slider.querySelector(".prev");
  const next=slider.querySelector(".next");
  const dots=slider.querySelector(".dots");
  let index=0;

  // dots
  for(let i=0;i<slideItems.length;i++){
    const dot=document.createElement("div");
    dot.classList.add("dot");
    if(i===0) dot.classList.add("active");
    dot.addEventListener("click",()=>{index=i;update();});
    dots.appendChild(dot);
  }
  const dotItems=dots.children;

  function update(){
    slides.style.transform=`translateX(-${index*100}%)`;
    for(let d of dotItems) d.classList.remove("active");
    dotItems[index].classList.add("active");
  }
  prev.addEventListener("click",()=>{index=(index-1+slideItems.length)%slideItems.length;update();});
  next.addEventListener("click",()=>{index=(index+1)%slideItems.length;update();});

  // auto rotate
  setInterval(()=>{index=(index+1)%slideItems.length;update();},4000);

  // Lightbox
  Array.from(slideItems).forEach(slide=>{
    slide.addEventListener("click",()=>{
      const imgSrc=slide.querySelector("img").src;
      const lightbox=document.getElementById("lightbox");
      const lightboxImg=document.getElementById("lightboxImg");
      lightboxImg.src=imgSrc;
      lightbox.style.display="flex";
    });
  });
}

// Init all sliders
["slider1","slider2","slider3","slider4","slider5","slider6","slider7"].forEach(initSlider);

// Close lightbox
document.getElementById("closeBtn").addEventListener("click",()=>{document.getElementById("lightbox").style.display="none";});
document.getElementById("lightbox").addEventListener("click",e=>{
  if(e.target.id==="lightbox"){document.getElementById("lightbox").style.display="none";}
});
</script>

</body>
</html>
