<?php
session_start();
include 'auth.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch blog posts including blog_title
$sql = "SELECT 
            tblblog.blog_id,
            tblblog.blog_title,
            tblblog.blog_content, 
            tblblog.date_posted, 
            tbluser.user_id AS posted_by, 
            tbluser.username AS posted_by_username
        FROM tblblog 
        JOIN tbluser ON tblblog.posted_by = tbluser.user_id 
        WHERE tblblog.blog_status = 'active'
        ORDER BY tblblog.date_posted DESC";

$result = $mysql->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <h4><a href="realestate.php">REALESTATE</a></h4>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Real Estate Pricing</title>
  <style>
    :root{
      --bg:#0f1221; --card:#171a2b; --muted:#9aa3b2; --text:#e9eef6; --brand:#6c9cff; --accent:#ffd166;
      --ok:#2ecc71; --warn:#f39c12; --danger:#e74c3c;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;background:var(--bg);color:var(--text);}
    .container{max-width:1100px;margin:auto;padding:24px}

    /* Header */
    header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
    header h1{font-size:clamp(1.4rem,2.5vw,2rem);margin:0;letter-spacing:.4px}
    .badge{background:linear-gradient(90deg,var(--brand),#8ee3ff);color:#05121f;padding:6px 10px;border-radius:999px;font-weight:600;font-size:.9rem}

    /* Summary cards */
    .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px}
    .card{grid-column:span 12;background:var(--card);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:18px;box-shadow:0 8px 24px rgba(0,0,0,.25)}
    @media(min-width:720px){.span-4{grid-column:span 4}.span-6{grid-column:span 6}.span-12{grid-column:span 12}}
    .card h3{margin:0 0 8px;font-size:1.05rem;color:#dbe6ff}
    .price{display:flex;align-items:baseline;gap:6px}
    .price .amt{font-size:1.6rem;font-weight:800}
    .sub{color:var(--muted);font-size:.9rem}

    /* Pricing lists */
    .tier{display:grid;gap:10px}
    .item{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px dashed rgba(255,255,255,.08);border-radius:12px;background:rgba(255,255,255,.02)}
    .item strong{font-weight:600}
    .tag{padding:4px 8px;border-radius:999px;font-size:.8rem;font-weight:700}
    .tag.basic{background:#2a3a22;color:#b6ff9b}
    .tag.modern{background:#2a3346;color:#9fc5ff}
    .tag.luxury{background:#3a2732;color:#ffb0d0}

    /* Land table */
    .toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:6px;margin-bottom:10px}
    .search{flex:1;min-width:220px}
    input[type="text"],select{width:100%;background:#0c0f1f;color:var(--text);border:1px solid rgba(255,255,255,.12);padding:10px 12px;border-radius:10px;outline:none}
    table{width:100%;border-collapse:separate;border-spacing:0 10px}
    thead th{font-size:.9rem;color:#cad6f6;text-align:left;padding:0 12px}
    tbody tr{background:var(--card);border:1px solid rgba(255,255,255,.06)}
    tbody td{padding:14px 12px}
    tbody tr{border-radius:12px;overflow:hidden}

    .pill{padding:6px 10px;border-radius:999px;display:inline-block;font-weight:700;font-size:.85rem}
    .pill.dev{background:#1d2b1f;color:#8affc1}
    .pill.core{background:#2b1f1f;color:#ffb3b3}
    .pill.prime{background:#1f263a;color:#b7caff}

    footer{margin-top:28px;color:var(--muted);font-size:.85rem}
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>🏡 Real Estate Pricing Overview</h1>
      <span class="badge">Lagos • ₦ (NGN)</span>
    </header>

    <!-- Summary Cards -->
    <section class="grid">
      <div class="card span-4">
        <h3>Basic Homes</h3>
        <div class="price"><span class="amt">₦5m–₦35m</span><span class="sub">Studio to 3‑bed flats</span></div>
      </div>
      <div class="card span-4">
        <h3>Modern Homes</h3>
        <div class="price"><span class="amt">₦35m–₦90m</span><span class="sub">Terrace & Semi‑D</span></div>
      </div>
      <div class="card span-4">
        <h3>Luxury Homes</h3>
        <div class="price"><span class="amt">₦120m–₦500m+</span><span class="sub">Detached, Penthouses</span></div>
      </div>
    </section>

    <!-- Detailed Tiers -->
    <section class="grid" style="margin-top:16px">
      <div class="card span-6">
        <h3>Basic (Affordable)</h3>
        <div class="tier">
          <div class="item"><span><strong>Studio / Self‑Contain</strong> <span class="sub">(25–35 m²)</span></span><span class="tag basic">₦5m–₦8m</span></div>
          <div class="item"><span><strong>1‑Bedroom Flat</strong></span><span class="tag basic">₦10m–₦15m</span></div>
          <div class="item"><span><strong>2‑Bedroom Flat</strong></span><span class="tag basic">₦18m–₦25m</span></div>
          <div class="item"><span><strong>3‑Bedroom Flat</strong></span><span class="tag basic">₦28m–₦35m</span></div>
        </div>
      </div>

      <div class="card span-6">
        <h3>Modern (Mid‑Range)</h3>
        <div class="tier">
          <div class="item"><span><strong>2‑Bed Terrace Duplex</strong></span><span class="tag modern">₦35m–₦50m</span></div>
          <div class="item"><span><strong>3‑Bed Terrace Duplex</strong></span><span class="tag modern">₦55m–₦70m</span></div>
          <div class="item"><span><strong>4‑Bed Semi‑Detached</strong></span><span class="tag modern">₦75m–₦90m</span></div>
        </div>
      </div>

      <div class="card span-12">
        <h3>Luxury (High‑End)</h3>
        <div class="tier">
          <div class="item"><span><strong>4‑Bed Fully Detached</strong></span><span class="tag luxury">₦120m–₦180m</span></div>
          <div class="item"><span><strong>5‑Bed Fully Detached + BQ</strong></span><span class="tag luxury">₦200m–₦300m</span></div>
          <div class="item"><span><strong>Luxury Penthouse / Smart Home</strong></span><span class="tag luxury">₦350m–₦500m+</span></div>
        </div>
      </div>
    </section>

    <!-- Land Pricing -->
    <section class="card" style="margin-top:16px">
      <h3>Land Pricing by Location (Per 600 m² plot)</h3>
      <div class="toolbar">
        <div class="search"><input id="filter" type="text" placeholder="Search locations (e.g., Lekki, Ikoyi, Ibeju)…" /></div>
        <select id="band">
          <option value="all">All price bands</option>
          <option value="dev">Developing</option>
          <option value="prime">Prime</option>
          <option value="core">Core city</option>
        </select>
      </div>
      <table>
        <thead>
          <tr>
            <th>Location</th>
            <th>Band</th>
            <th>Price Range</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody id="land-body">
          <tr data-band="dev"><td>Ibeju‑Lekki</td><td><span class="pill dev">Developing</span></td><td>₦3m – ₦15m</td><td>Title, road access affect price</td></tr>
          <tr data-band="prime"><td>Ajah / Sangotedo</td><td><span class="pill prime">Prime</span></td><td>₦20m – ₦45m</td><td>Gated estates cost more</td></tr>
          <tr data-band="prime"><td>Lekki Phase 1</td><td><span class="pill prime">Prime</span></td><td>₦200m – ₦450m</td><td>Commercial plots higher</td></tr>
          <tr data-band="core"><td>Ikeja GRA</td><td><span class="pill core">Core City</span></td><td>₦250m – ₦500m</td><td>Low supply, high demand</td></tr>
          <tr data-band="core"><td>Victoria Island</td><td><span class="pill core">Core City</span></td><td>₦350m – ₦800m</td><td>Prime commercial hub</td></tr>
          <tr data-band="core"><td>Ikoyi</td><td><span class="pill core">Core City</span></td><td>₦500m – ₦1.2bn</td><td>Ultra‑prime luxury</td></tr>
        </tbody>
      </table>
      <footer>Prices are indicative ranges for 2025 and vary by title (C of O, Governor’s Consent), proximity, infrastructure, and developer.</footer>
    </section>
  </div>

  <script>
    const q = document.getElementById('filter');
    const band = document.getElementById('band');
    const rows = [...document.querySelectorAll('#land-body tr')];

    function applyFilter(){
      const term = q.value.trim().toLowerCase();
      const b = band.value;
      rows.forEach(r=>{
        const txt = r.textContent.toLowerCase();
        const okBand = (b==='all') || (r.dataset.band===b);
        r.style.display = (txt.includes(term) && okBand) ? '' : 'none';
      });
    }
    q.addEventListener('input', applyFilter);
    band.addEventListener('change', applyFilter);
  </script>
</body>
</html>
