<?php
require_once 'config.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Marrett Ikeja — Luxury Hotel, Ikeja</title>

<!-- Bootstrap & icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
  --bg:#071025; --card:#0b1723; --muted:#b6cfe8; --accent:#ff6a00; --accent2:#3ea0ff;
  --radius:14px;
}
*{box-sizing:border-box}
body{background:linear-gradient(180deg,#061225,#071025); color:#e9f0ff; font-family:Inter, system-ui, Arial, sans-serif}
.navbar{background:rgba(10,16,22,0.5) !important;backdrop-filter:blur(4px)}
.brand {font-weight:800; letter-spacing:0.4px}
.hero{
  height:64vh; background-image: url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1600&auto=format&fit=crop&crop=faces');
  background-size:cover;background-position:center; border-radius:12px; position:relative; overflow:hidden;
}
.hero .overlay{position:absolute; inset:0; background:linear-gradient(90deg, rgba(2,6,23,0.6), rgba(2,6,23,0.1)); display:flex; align-items:center}
.hero .content{padding:28px}
.card-room{background:linear-gradient(180deg, rgba(255,255,255,0.02), transparent); border:1px solid rgba(255,255,255,0.03); color:#e9f0ff; border-radius:12px}
.smallmuted{color:var(--muted); font-size:.95rem}
.carousel-indicators [data-bs-target]{background:#ddd !important; opacity:0.9}
.gallery img{width:100%; height:140px; object-fit:cover; border-radius:8px; border:1px solid rgba(255,255,255,0.03)}
.amenity {display:flex; gap:10px; align-items:center}
.map-wrap iframe{width:100%; height:320px; border-radius:12px; border:0}
.room-thumb{height:160px; object-fit:cover; border-radius:8px}
.footer{color:var(--muted); padding:24px 0}
.badge-price{background:linear-gradient(90deg,#ff8a00,#ff6a00); padding:8px 12px; border-radius:999px; font-weight:700}
.undernav{background:rgba(255,255,255,0.02); padding:12px; border-radius:12px; margin-top:-28px; margin-bottom:20px}
.property-card img{height:160px; object-fit:cover}
.room-grid .col-md-3{margin-bottom:18px}
@media(max-width:700px){ .hero{height:48vh} .hero .overlay{align-items:flex-end} .overlay .content{padding:18px}}
</style>
</head>
<body>

<!-- NAV -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand brand" href="#">Marrett Ikeja</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navc">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navc">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="#hero">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#properties">Properties</a></li>
        <li class="nav-item"><a class="nav-link" href="#rooms">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" href="#amenities">Amenities</a></li>
        <li class="nav-item"><a class="nav-link" href="#map">Location</a></li>
        <li class="nav-item ms-3"><a class="btn btn-outline-light" href="#booking"><i class="fa fa-calendar-check me-2"></i> Book Now</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO with 6 moving images (carousel) -->
<section id="hero" class="container my-4">
  <div class="hero card-room p-0">
    <div id="heroCarousel" class="carousel slide h-100" data-bs-ride="carousel" data-bs-interval="3200">
      <div class="carousel-inner h-100">
        <?php
        // six localized-style images (Unsplash queries hint Lagos / Nigeria)
        $heroImgs = [
          "",
          "https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?q=80&w=1600&auto=format&fit=crop",
          "https://images.unsplash.com/photo-1501117716987-c8e3e0dbb2d6?q=80&w=1600&auto=format&fit=crop",
          "https://images.unsplash.com/photo-1551882547-ff9e6d1a7f12?q=80&w=1600&auto=format&fit=crop",
          "https://images.unsplash.com/photo-1526772662000-3f88f10405ff?q=80&w=1600&auto=format&fit=crop",
          "https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?q=80&w=1600&auto=format&fit=crop"
        ];
        foreach($heroImgs as $i=>$img){
          $active = $i===0 ? 'active' : '';
          echo "<div class='carousel-item h-100 $active'><img src='".htmlspecialchars($img)."' class='d-block w-100 h-100' style='object-fit:cover'/></div>";
        }
        ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>

      <div class="overlay">
        <div class="container content">
          <h1 class="display-5">Marrett Hotel — Ikeja</h1>
          <p class="smallmuted">Luxury stays in the heart of Ikeja GRA • Rooftop dining • Conference suites • Pool & Spa</p>
          <div class="d-flex gap-3 mt-3">
            <a class="btn btn-primary" href="#booking"><i class="fa fa-bed me-2"></i> Reserve a Room</a>
            <a class="btn btn-outline-light" href="#map"><i class="fa fa-map-marker-alt me-2"></i> Get Directions</a>
            <div class="ms-3 badge-price">Avg from ₦45,000</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- under-navbar (destination / dates / amenities) -->
  <div class="undernav mt-3">
    <div class="row align-items-center">
      <div class="col-md-4">
        <label class="form-label smallmuted mb-1">Destination</label>
        <input id="dest" class="form-control" value="Ikeja, Lagos" />
      </div>
      <div class="col-md-3">
        <label class="form-label smallmuted mb-1">Check-in / Check-out</label>
        <div class="d-flex gap-2">
          <input id="ci" type="date" class="form-control" />
          <input id="co" type="date" class="form-control" />
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label smallmuted mb-1">Guests</label>
        <select id="guests" class="form-select"><option>1</option><option>2</option><option>3</option><option>4+</option></select>
      </div>
      <div class="col-md-3">
        <label class="form-label smallmuted mb-1">Amenities</label>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-sm btn-outline-light amen-btn active" data-amen="free-breakfast">Free Breakfast</button>
          <button class="btn btn-sm btn-outline-light amen-btn" data-amen="pool">Pool</button>
          <button class="btn btn-sm btn-outline-light amen-btn" data-amen="wifi">Free WiFi</button>
          <button class="btn btn-sm btn-outline-light amen-btn" data-amen="parking">Parking</button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PROPERTIES (multiple property types with images, price and short details) -->
<section id="properties" class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Our Properties & Types</h3>
    <p class="smallmuted">Choose a hotel type — each card shows 4 images, short details and rates</p>
  </div>

  <div class="row g-4">
    <!-- Hotel Type 1: Business Tower -->
    <div class="col-md-4">
      <div class="card property-card card-room p-0">
        <div class="row g-0">
          <div class="col-6">
            <img src="https://images.unsplash.com/photo-1540518614846-7eded433c457?q=80&w=800&auto=format&fit=crop" class="img-fluid" alt="">
            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?q=80&w=800&auto=format&fit=crop" class="img-fluid mt-2" alt="">
          </div>
          <div class="col-6">
            <img src="https://images.unsplash.com/photo-1501117716987-c8e3e0dbb2d6?q=80&w=800&auto=format&fit=crop" class="img-fluid" alt="">
            <img src="https://images.unsplash.com/photo-1551882547-ff9e6d1a7f12?q=80&w=800&auto=format&fit=crop" class="img-fluid mt-2" alt="">
          </div>
        </div>
        <div class="p-3">
          <h5>Business Tower</h5>
          <div class="smallmuted">Great for business travellers. Meeting rooms, high-speed WiFi and executive lounge.</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="smallmuted">4 rooms types</div>
            <div class="badge-price">From ₦45,000</div>
          </div>
          <a href="#rooms" class="btn btn-outline-light btn-sm mt-2">View rooms</a>
        </div>
      </div>
    </div>

    <!-- Hotel Type 2: Leisure Wing -->
    <div class="col-md-4">
      <div class="card property-card card-room p-0">
        <div class="row g-0">
          <div class="col-6">
            <img src="https://images.unsplash.com/photo-1505692794406-02f32457b070?q=80&w=800&auto=format&fit=crop" class="img-fluid" alt="">
            <img src="https://images.unsplash.com/photo-1529287685583-8f112e0a9aee?q=80&w=800&auto=format&fit=crop" class="img-fluid mt-2" alt="">
          </div>
          <div class="col-6">
            <img src="https://images.unsplash.com/photo-1496417263034-38ec4f0b665a?q=80&w=800&auto=format&fit=crop" class="img-fluid" alt="">
            <img src="https://images.unsplash.com/photo-1526772662000-3f88f10405ff?q=80&w=800&auto=format&fit=crop" class="img-fluid mt-2" alt="">
          </div>
        </div>
        <div class="p-3">
          <h5>Leisure Wing</h5>
          <div class="smallmuted">Family-friendly, poolside rooms, kids club and weekend brunch.</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="smallmuted">4 rooms types</div>
            <div class="badge-price">From ₦55,000</div>
          </div>
          <a href="#rooms" class="btn btn-outline-light btn-sm mt-2">View rooms</a>
        </div>
      </div>
    </div>

    <!-- Hotel Type 3: Presidential Residences -->
    <div class="col-md-4">
      <div class="card property-card card-room p-0">
        <div class="row g-0">
          <div class="col-6">
            <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop" class="img-fluid" alt="">
            <img src="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=800&auto=format&fit=crop" class="img-fluid mt-2" alt="">
          </div>
          <div class="col-6">
            <img src="https://images.unsplash.com/photo-1467269204594-9661b134dd2b?q=80&w=800&auto=format&fit=crop" class="img-fluid" alt="">
            <img src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?q=80&w=800&auto=format&fit=crop" class="img-fluid mt-2" alt="">
          </div>
        </div>
        <div class="p-3">
          <h5>Presidential Residences</h5>
          <div class="smallmuted">Top floor suites, private terraces, personal concierge and limousine service.</div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="smallmuted">4 suites</div>
            <div class="badge-price">From ₦250,000</div>
          </div>
          <a href="#rooms" class="btn btn-outline-light btn-sm mt-2">View rooms</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ROOMS list (each room type with its own 4-image gallery + details + rate) -->
<section id="rooms" class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Room Types & Rates</h3>
    <p class="smallmuted">Select a room and proceed to booking. Each card shows 4 images and a concise description.</p>
  </div>

  <div class="row room-grid">
    <?php
    // sample rooms array
    $rooms = [
      [
        'property' => 'Business Tower',
        'type' => 'Deluxe Room',
        'price' => 45000,
        'desc' => 'King bed, work desk, high-speed WiFi, city view.',
        'imgs' => [
          'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1551882547-ff9e6d1a7f12?q=80&w=800&auto=format&fit=crop'
        ]
      ],
      [
        'property' => 'Leisure Wing',
        'type' => 'Executive Suite',
        'price' => 85000,
        'desc' => 'Suite with living area, complimentary breakfast and access to spa.',
        'imgs' => [
          'https://images.unsplash.com/photo-1501117716987-c8e3e0dbb2d6?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1529287685583-8f112e0a9aee?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1505691938895-1758d7feb511?q=80&w=800&auto=format&fit=crop'
        ]
      ],
      [
        'property' => 'Presidential Residences',
        'type' => 'Presidential Suite',
        'price' => 250000,
        'desc' => 'Private terrace, dining area, premium amenities & concierge.',
        'imgs' => [
          'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1496417263034-38ec4f0b665a?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1560444454-2c84a0d5fbbd?q=80&w=800&auto=format&fit=crop',
          'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?q=80&w=800&auto=format&fit=crop'
        ]
      ]
    ];

    foreach($rooms as $r):
    ?>
    <div class="col-md-4">
      <div class="card card-room p-3 h-100">
        <div class="row g-2">
          <?php foreach($r['imgs'] as $img): ?>
            <div class="col-6"><img src="<?=$img?>" class="room-thumb" alt=""></div>
          <?php endforeach; ?>
        </div>

        <div class="mt-3">
          <h5><?=$r['type']?></h5>
          <div class="smallmuted"><?=$r['property']?> · <?=$r['desc']?></div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="smallmuted">Max guests 2</div>
            <div class="badge-price">₦<?=number_format($r['price'])?> / night</div>
          </div>
          <div class="mt-2 d-flex gap-2">
            <button class="btn btn-outline-light btn-sm view-room" data-price="<?=$r['price']?>" data-type="<?=htmlspecialchars($r['type'])?>" data-property="<?=htmlspecialchars($r['property'])?>">Book</button>
            <a class="btn btn-link text-white smallmuted" data-bs-toggle="collapse" href="#details<?=md5($r['type'])?>">More details</a>
          </div>
          <div id="details<?=md5($r['type'])?>" class="collapse mt-2 smallmuted">
            <ul>
              <li>Complimentary breakfast</li>
              <li>WiFi, Air conditioning, Minibar</li>
              <li>Accessible rooms on request</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Amenities -->
<section id="amenities" class="container my-4">
  <div class="row g-4">
    <div class="col-md-6">
      <h4>Amenities & Services</h4>
      <div class="mt-3">
        <div class="amenity mb-2"><i class="fa fa-swimmer fa-lg" style="color:#3ea0ff"></i><div><strong>Pool & Spa</strong><div class="smallmuted">Outdoor pool, spa treatments, sauna</div></div></div>
        <div class="amenity mb-2"><i class="fa fa-utensils fa-lg" style="color:#ff8a00"></i><div><strong>Rooftop Restaurant</strong><div class="smallmuted">Local & continental cuisine, private dining</div></div></div>
        <div class="amenity mb-2"><i class="fa fa-wifi fa-lg"></i><div><strong>Free High-Speed WiFi</strong><div class="smallmuted">Fiber connectivity in rooms & meeting rooms</div></div></div>
        <div class="amenity mb-2"><i class="fa fa-briefcase fa-lg"></i><div><strong>Business & Conference</strong><div class="smallmuted">Board rooms, AV equipment, event catering</div></div></div>
      </div>
    </div>

    <div class="col-md-6">
      <h4>Gallery</h4>
      <div class="row g-2 gallery mt-2">
        <div class="col-6"><img src="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=800&auto=format&fit=crop" alt=""></div>
        <div class="col-6"><img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?q=80&w=800&auto=format&fit=crop" alt=""></div>
        <div class="col-6"><img src="https://images.unsplash.com/photo-1467269204594-9661b134dd2b?q=80&w=800&auto=format&fit=crop" alt=""></div>
        <div class="col-6"><img src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?q=80&w=800&auto=format&fit=crop" alt=""></div>
      </div>
    </div>
  </div>
</section>

<!-- MAP -->
<section id="map" class="container my-4">
  <div class="row">
    <div class="col-md-8">
      <h4>Location — Ikeja, Lagos</h4>
      <p class="smallmuted">Azure Grand, 12 Commercial Ave, Ikeja GRA, Lagos — get directions below.</p>
      <div class="map-wrap card-room p-2">
        <!-- Google Maps embed centered on Ikeja (LatLng ~ 6.5919, 3.3515) -->
        <iframe
          src="https://www.google.com/maps?q=Ikeja+GRA+Lagos&output=embed"
          loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>
    <div class="col-md-4">
      <h5>Destination & Contact</h5>
      <p class="smallmuted mb-1"><strong>Azure Grand Hotel — Ikeja</strong></p>
      <p class="smallmuted">12 Commercial Ave, Ikeja GRA, Lagos.<br/>Phone: +234 800 123 4567<br/>Email: reservations@marrettikeja.example</p>
      <div class="mt-3">
        <strong class="smallmuted">Transport & Directions</strong>
        <p class="smallmuted">We offer airport transfers on request. Secure parking on-site. Request transfer when booking.</p>
      </div>
    </div>
  </div>
</section>

<!-- BOOKING FORM & PAYMENT -->
<section id="booking" class="container my-4">
  <div class="row g-4">
    <div class="col-md-8">
      <h3>Book a Room</h3>
      <div class="card card-room p-4">
        <form id="bookForm" method="POST" action="booking.php">
          <input type="hidden" name="property_type" id="property_type" value="">
          <input type="hidden" name="room_type" id="room_type_field" value="">
          <input type="hidden" name="price_per_night" id="price_per_night" value="">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label smallmuted">Full name</label>
              <input name="name" required class="form-control" placeholder="Jane Doe">
            </div>
            <div class="col-md-6">
              <label class="form-label smallmuted">Email</label>
              <input name="email" type="email" required class="form-control" placeholder="you@example.com">
            </div>
            <div class="col-md-4">
              <label class="form-label smallmuted">Phone</label>
              <input name="phone" class="form-control" placeholder="+234 ...">
            </div>
            <div class="col-md-4">
              <label class="form-label smallmuted">Check-in</label>
              <input name="checkin" id="b_ci" type="date" required class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label smallmuted">Check-out</label>
              <input name="checkout" id="b_co" type="date" required class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label smallmuted">Nights</label>
              <input name="nights" id="b_nights" type="number" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-3">
              <label class="form-label smallmuted">Guests</label>
              <input name="guests" type="number" class="form-control" value="1" min="1">
            </div>

            <div class="col-12">
              <label class="form-label smallmuted">Select Room</label>
              <div class="d-flex gap-2 flex-wrap" id="roomSelectList">
                <!-- JS will populate selectable buttons from room cards -->
              </div>
            </div>

            <div class="col-12">
              <label class="form-label smallmuted">Amenities (optional)</label>
              <div class="d-flex gap-2 flex-wrap">
                <label class="form-check form-check-inline smallmuted"><input class="form-check-input amen" name="amenities[]" type="checkbox" value="free-breakfast"> Free Breakfast</label>
                <label class="form-check form-check-inline smallmuted"><input class="form-check-input amen" name="amenities[]" type="checkbox" value="pool"> Pool Access</label>
                <label class="form-check form-check-inline smallmuted"><input class="form-check-input amen" name="amenities[]" type="checkbox" value="spa"> Spa</label>
                <label class="form-check form-check-inline smallmuted"><input class="form-check-input amen" name="amenities[]" type="checkbox" value="parking"> Parking</label>
              </div>
            </div>

            <div class="col-12 d-flex justify-content-between align-items-center">
              <div>
                <small class="muted">Estimated total</small>
                <div id="estTotal" class="h4">₦0</div>
                <div class="smallmuted">Taxes included</div>
              </div>
              <div>
                <label class="form-label smallmuted mb-1">Payment method</label>
                <select name="payment_method" id="payment_method" class="form-select">
                  <option value="paystack">Card (Online)</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
                <div class="mt-2">
                  <button type="submit" class="btn btn-success btn-lg" id="proceedBtn">Proceed</button>
                </div>
              </div>
            </div>
          </div>
        </form>
        <div id="bankInstructions" class="mt-3 smallmuted d-none card p-3">
          <strong>Bank transfer instructions</strong>
          <p>Bank: First Bank Nigeria Plc<br/>Account name: Marrett Ikeja<br/>Account number: 0123456789<br/>Amount: Pay the estimated total above and send proof to reservations@marrettikeja.example or WhatsApp +2348001234567. We will confirm and mark your booking as paid once verified.</p>
        </div>
      </div>
    </div>

    <!-- sidebar summary -->
    <div class="col-md-4">
      <div class="card card-room p-3">
        <h5>Booking Summary</h5>
        <div class="smallmuted">Selected room:</div>
        <div id="summaryRoom" class="fw-bold">—</div>
        <div class="smallmuted mt-2">Dates:</div>
        <div id="summaryDates">—</div>
        <div class="smallmuted mt-2">Guests:</div>
        <div id="summaryGuests">—</div>
        <div class="smallmuted mt-2">Total:</div>
        <div id="summaryTotal" class="h4">₦0</div>

        <hr class="bg-white/10">
        <h6 class="smallmuted">Quick pay</h6>
        <div class="d-grid gap-2">
          <a class="btn btn-outline-light" id="quickPayCard"><i class="fa fa-credit-card me-2"></i> Pay with Card</a>
          <a class="btn btn-outline-light" id="quickPayBank"><i class="fa fa-university me-2"></i> Bank Transfer</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="container footer">
  <div class="d-flex justify-content-between align-items-center">
    <div>&copy; <?=date('Y')?> Marrett Ikeja — Luxury Hotel • All rights reserved.</div>
    <div class="smallmuted">Demo site • For payments use Paystack test keys</div>
  </div>
</footer>

<!-- Bootstrap & scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ----- Client side: populate room selection, compute totals, show bank instructions ----- */
const rooms = <?php echo json_encode($rooms, JSON_HEX_APOS|JSON_HEX_QUOT); ?>;

const roomSelectList = document.getElementById('roomSelectList');
const estTotalEl = document.getElementById('estTotal');
const summaryRoom = document.getElementById('summaryRoom');
const summaryDates = document.getElementById('summaryDates');
const summaryGuests = document.getElementById('summaryGuests');
const summaryTotal = document.getElementById('summaryTotal');
const pricePerNightField = document.getElementById('price_per_night');
const propertyTypeField = document.getElementById('property_type');
const roomTypeField = document.getElementById('room_type_field');

function formatNGN(v){ return '₦' + Number(v).toLocaleString(); }

rooms.forEach((r, idx) => {
  const btn = document.createElement('button');
  btn.className = 'btn btn-outline-light btn-sm';
  btn.innerHTML = `<strong>${r.type}</strong><div class="smallmuted">${r.property}</div><div class="mt-1 badge-price">₦${Number(r.price).toLocaleString()}</div>`;
  btn.dataset.price = r.price;
  btn.dataset.type = r.type;
  btn.dataset.property = r.property;
  btn.onclick = (e) => {
    e.preventDefault();
    // mark active
    document.querySelectorAll('#roomSelectList .btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    propertyTypeField.value = r.property;
    roomTypeField.value = r.type;
    pricePerNightField.value = r.price;
    updateEstimates();
  }
  roomSelectList.appendChild(btn);
});

// amenity toggle UI under hero
document.querySelectorAll('.amen-btn').forEach(b=>{
  b.addEventListener('click', (e)=>{
    b.classList.toggle('active');
  });
});

// compute nights
function daysBetween(a,b){
  if(!a || !b) return 1;
  const d1 = new Date(a), d2 = new Date(b);
  const diff = Math.ceil((d2 - d1) / (1000*60*60*24));
  return diff > 0 ? diff : 1;
}

function updateEstimates(){
  const price = Number(document.getElementById('price_per_night').value || 0);
  const nights = Math.max(1, Number(document.getElementById('b_nights').value || 1));
  const guests = document.querySelector('input[name="guests"]')?.value || document.getElementById('guests').value || 1;
  // amenities cost small add-on example
  let amenCost = 0;
  document.querySelectorAll('.amen:checked').forEach(ch=>{
    if(ch.value === 'pool') amenCost += 2000;
    if(ch.value === 'spa') amenCost += 5000;
    if(ch.value === 'parking') amenCost += 1000;
  });
  const subtotal = price * nights + amenCost;
  estTotalEl.textContent = formatNGN(subtotal);
  summaryRoom.textContent = document.getElementById('room_type_field').value || '—';
  summaryDates.textContent = (document.getElementById('b_ci').value || '—') + ' → ' + (document.getElementById('b_co').value || '—');
  summaryGuests.textContent = guests;
  summaryTotal.textContent = formatNGN(subtotal);
}

// trigger updates
document.getElementById('b_nights').addEventListener('input', updateEstimates);
document.getElementById('b_ci').addEventListener('change', ()=>{
  const ci = document.getElementById('b_ci').value;
  const co = document.getElementById('b_co').value;
  if(ci && co){
    const nights = daysBetween(ci, co);
    document.getElementById('b_nights').value = nights;
  }
  updateEstimates();
});
document.getElementById('b_co').addEventListener('change', ()=>{
  const ci = document.getElementById('b_ci').value;
  const co = document.getElementById('b_co').value;
  if(ci && co){
    const nights = daysBetween(ci, co);
    document.getElementById('b_nights').value = nights;
  }
  updateEstimates();
});
document.querySelectorAll('.amen').forEach(ch => ch.addEventListener('change', updateEstimates));
document.getElementById('guests').addEventListener('change', updateEstimates);

// show bank instructions if chosen
const paymentMethod = document.getElementById('payment_method');
const bankInstructions = document.getElementById('bankInstructions');
paymentMethod.addEventListener('change', ()=>{
  if(paymentMethod.value === 'bank_transfer') bankInstructions.classList.remove('d-none');
  else bankInstructions.classList.add('d-none');
});

// "Book" quick buttons from room cards
document.querySelectorAll('.view-room').forEach(btn=>{
  btn.addEventListener('click', (e)=>{
    e.preventDefault();
    const price = btn.dataset.price;
    const type = btn.dataset.type;
    const property = btn.dataset.property;
    // set form
    document.getElementById('price_per_night').value = price;
    document.getElementById('room_type_field').value = type;
    document.getElementById('property_type').value = property;
    // select matching in the room list UI
    document.querySelectorAll('#roomSelectList .btn').forEach(b=>{
      if(b.dataset.type === type){
        b.click();
      }
    });
    window.location.hash = '#booking';
    updateEstimates();
  });
});

// Quick pay handlers (basic)
document.getElementById('quickPayCard').addEventListener('click', (e)=>{
  alert('To pay with card use the booking form and select "Card (Online)" then Proceed. (Demo)');
});
document.getElementById('quickPayBank').addEventListener('click', (e)=>{
  alert('For bank transfer, select "Bank Transfer" on the booking form and follow instructions. (Demo)');
});

// initial estimate
updateEstimates();
</script>
</body>
</html>
