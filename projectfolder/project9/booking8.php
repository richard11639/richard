<?php
// index.php — booking form
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <a href="index6.php">home</a>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ric Hotel Booking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f7f7f7; font-family: Inter, system-ui, Arial; }
  .booking-box { background:#fff; padding:28px; border-radius:12px; margin:28px auto; max-width:980px;
    box-shadow:0 8px 30px rgba(1,6,20,0.06);}
  .btn-custom { background:#ff7b00; color:#fff; border:none; }
  .price-box { background:#022b3a; color:#fff; padding:14px; border-radius:8px; font-weight:700; text-align:center;}
  label.small { font-size:0.85rem; color:#555; }
</style>
</head>
<body>
<div class="container">
  <div class="booking-box">
    <form id="bookingForm" method="POST" action="process_booking.php">
      <h2 class="mb-3">Book Your Stay — Ric Hotel & Suites</h2>

      <div class="row g-3">
        <!-- Dates -->
        <div class="col-md-6">
          <label class="small">Check-in</label>
          <input type="date" id="checkin" name="checkin" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="small">Check-out</label>
          <input type="date" id="checkout" name="checkout" class="form-control" required>
        </div>

        <!-- Room type -->
        <div class="col-md-6">
          <label class="small">Room Type</label>
          <select id="roomType" name="room_type" class="form-control" required>
            <option value="Eko Garden|40000">studio house — ₦40,000/night</option>
            <option value="Eko Suite|65000">studio house(2bedroom) — ₦65,000/night</option>
          </select>
        </div>
<!-- Extras -->
        <div class="col-md-12">
          <label class="small">Extras (recreation services)</label>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Food","amount":20000}'>
            <label class="form-check-label">gymnasium — ₦20,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Accommodation Upgrade","amount":10000}'>
            <label class="form-check-label">swimming pool — ₦10,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Water Package","amount":10000}'>
            <label class="form-check-label">tennis court— ₦10,000</label>
  </div>
            <input class="form-check-input extra" type="checkbox" value='{"label":"Food","amount":15000}'>
            <label class="form-check-label">spa — ₦15,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Accommodation Upgrade","amount":10000}'>
            <label class="form-check-label">saloon — ₦10,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Water Package","amount":5000}'>
            <label class="form-check-label">nail studio — ₦5,000</label>            
          </div>

   <!-- Extras -->
        <div class="col-md-12">
          <label class="small">Extras (dinning&bar)</label>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Food","amount":30000}'>
            <label class="form-check-label">Sky resturant after — ₦30,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Accommodation Upgrade","amount":30000}'>
            <label class="form-check-label">Atarodo restaurant after — ₦30,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Water Package","amount":25000}'>
            <label class="form-check-label">lagoon breeze after— ₦25,000</label>
            <input class="form-check-input extra" type="checkbox" value='{"label":"Food","amount":30000}'>
            <label class="form-check-label">Lagoon irish pub after— ₦30,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Accommodation Upgrade","amount":20000}'>
            <label class="form-check-label">Calabash bar after — ₦20,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Water Package","amount":25000}'>
            <label class="form-check-label">Red chinese restaurant after— ₦25,000</label>            
          </div>       
        </div> 


        <!-- Guests -->
        <div class="col-md-6">
          <label class="small">Guests</label>
          <input type="number" id="guests" name="guests" class="form-control" min="1" value="1" required>
        </div>

        <!-- Extras -->
        <div class="col-md-12">
          <label class="small">Extras (tick to include)</label>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Food","amount":10000}'>
            <label class="form-check-label">Food — ₦10,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Accommodation Upgrade","amount":20000}'>
            <label class="form-check-label">Accommodation Upgrade — ₦20,000</label>
          </div>
          <div class="form-check">
            <input class="form-check-input extra" type="checkbox" value='{"label":"Water Package","amount":5000}'>
            <label class="form-check-label">Water Package — ₦5,000</label>
          </div>
        </div>

        <!-- Guest info -->
        <div class="col-md-6">
          <label class="small">First Name</label>
          <input type="text" name="fname" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="small">Last Name</label>
          <input type="text" name="lname" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="small">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="small">Member No (optional)</label>
          <input type="text" name="member_no" class="form-control">
        </div>

        <div class="col-md-4"><label class="small">State</label><input type="text" name="state" class="form-control"></div>
        <div class="col-md-4"><label class="small">City</label><input type="text" name="city" class="form-control"></div>
        <div class="col-md-4"><label class="small">Country</label><input type="text" name="country" class="form-control" value="Nigeria"></div>

        <!-- Payment -->
        <div class="col-md-6">
          <label class="small">Payment Method</label>
          <select name="payment_method" class="form-control" required>
            <option value="bank">Bank Transfer</option>
            <option value="card">Card (Flutterwave)</option>
          </select>
        </div>

        <!-- Hidden -->
        <input type="hidden" name="nights" id="nights">
        <input type="hidden" name="room_price" id="room_price">
        <input type="hidden" name="extras_json" id="extras_json">
        <input type="hidden" name="total_amount" id="total_amount">

        <!-- Price box -->
        <div class="col-12"><div class="price-box" id="priceBox">Total: ₦0</div></div>

            <div class="col-12 text-begining">                                                  <div class="col-12 text-end">
          <button type="process2a.php" class="btn btn-custom mt-3">bank transfer</button>      <button type="process2b" class="btn btn-custom mt-3">pay online</button>
        </div>                                                                              </div>
      </div>                                                                                </div>
    </form>                                                                                  <form>   

    </form>
  </div>
</div>

<script>
function getNights() {
  const ci = document.getElementById('checkin').value;
  const co = document.getElementById('checkout').value;
  if(!ci || !co) return 0;
  const d1 = new Date(ci);
  const d2 = new Date(co);
  const diff = (d2 - d1) / (1000*60*60*24);
  return diff > 0 ? diff : 0;
}

function calcPrice() {
  const nights = getNights();
  document.getElementById('nights').value = nights;

  const roomVal = document.getElementById('roomType').value.split('|');
  const roomPrice = parseInt(roomVal[1],10);
  document.getElementById('room_price').value = roomPrice;

  const guests = parseInt(document.getElementById('guests').value) || 1;

  let extras = [], extrasAmount = 0;
  document.querySelectorAll('.extra:checked').forEach(el=>{
    const obj = JSON.parse(el.value);
    extras.push(obj);
    extrasAmount += obj.amount;
  });

  const total = (roomPrice * nights * guests) + extrasAmount;
  document.getElementById('extras_json').value = JSON.stringify(extras);
  document.getElementById('total_amount').value = total;

  document.getElementById('priceBox').innerText = 'Total: ₦' + total.toLocaleString();
}

['change','keyup'].forEach(evt=>{
  document.querySelectorAll('#checkin,#checkout,#roomType,#guests,.extra').forEach(el=>{
    el.addEventListener(evt, calcPrice);
  });
});

calcPrice();
</script>
</body>
</html>

