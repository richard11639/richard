<?php
include 'auth2.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$checkin = $_POST['checkin'];
$checkout = $_POST['checkout'];
$room_type = $_POST['room_type'];
$guests = $_POST['guests'];
$extras = isset($_POST['extras']) ? implode(", ", $_POST['extras']) : "";
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$member_no = $_POST['member_no'];
$state = $_POST['state'];
$city = $_POST['city'];
$country = $_POST['country'];
$payment_method = $_POST['payment_method'];
$total_price = $_POST['total_price'];

$sql = "INSERT INTO booking
(checkin, checkout, room_type, guests, extras, first_name, last_name, email, member_no, state, city, country, payment_method, total_price) 
VALUES 
('$checkin', '$checkout', '$room_type', '$guests', '$extras', '$first_name', '$last_name', '$email', '$member_no', '$state', '$city', '$country', '$payment_method', '$total_price')";

if ($conn->query($sql) === TRUE) {
    echo "Booking successful! Thank you, $first_name.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <a href="index6.php">home</a>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Hotel booking— Details</title>
  <style>
    :root{
      --bg:#f5f7fb; --card:#ffffff; --muted:#6b7280; --brand:#0b77f6;
      --accent:#ffb020; --ok:#10b981; --danger:#ef4444; --radius:12px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial;color:#0f172a;background:var(--bg)}
    .wrap{max-width:1100px;margin:28px auto;padding:18px}
    header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
    header h1{margin:0;font-size:1.25rem}
    .controls{display:flex;gap:10px;align-items:center}

    /* Layout */
    .layout{display:grid;grid-template-columns:1fr 360px;gap:18px}
    @media(max-width:980px){.layout{grid-template-columns:1fr}}
    .panel{background:var(--card);padding:16px;border-radius:var(--radius);box-shadow:0 10px 30px rgba(15,23,42,0.06)}

    /* filters & small controls */
    .controls select, .controls input[type="text"]{padding:8px 10px;border-radius:10px;border:1px solid #e6e9ef;background:#fff}
    .controls label{font-size:.9rem;color:var(--muted);display:flex;gap:8px;align-items:center}

    /* rooms */
    .rooms{display:grid;gap:12px}
    .room{display:flex;gap:12px;align-items:flex-start;padding:12px;border-radius:10px;border:1px solid #eef2ff;background:linear-gradient(180deg,#fff,#fbfdff)}
    .room .left{flex:1}
    .room h3{margin:0 0 6px;font-size:1.05rem}
    .room .meta{color:var(--muted);font-size:.92rem;margin-bottom:8px}
    .rates{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .rate-pill{padding:8px 10px;border-radius:10px;background:#f1f5f9;font-weight:700}
    .price-big{font-weight:900;font-size:1.25rem;color:var(--brand)}
    .pkg{margin-top:8px;display:flex;gap:8px;flex-wrap:wrap}

    /* table for extras */
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #f0f2f6;text-align:left}
    th{color:var(--muted);font-size:.95rem}

    /* booking preview */
    .booking-row{display:flex;justify-content:space-between;gap:10px;padding:10px;border-radius:8px;background:#fbfdff;border:1px solid #eef6ff}
    .muted{color:var(--muted);font-size:.95rem}
    .cta{margin-top:12px;padding:12px;border-radius:10px;border:none;background:var(--brand);color:#fff;font-weight:800;cursor:pointer;width:100%}
    .small{font-size:.88rem;color:var(--muted)}

    /* footer note */
    footer{margin-top:16px;color:var(--muted);font-size:.9rem}
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>🏨 Hotel booking — Room & Service Details</h1>
      <div class="controls">
        <label>
          Season:
          <select id="season">
            <option value="offpeak">Off-peak</option>
            <option value="peak">Peak</option>
          </select>
        </label>

        <label>
          Currency:
          <select id="currency">
            <option value="NGN">₦ NGN</option>
            <option value="USD">$ USD</option>
          </select>
        </label>

        <input type="text" id="search" placeholder="Search rooms or amenities" />
      </div>
    </header>

    <div class="layout">
      <!-- Left: rooms & extras -->
      <main class="panel">
        <section>
          <h2 style="margin:0 0 10px">Room Types</h2>
          <div class="rooms" id="rooms">
            <!-- JS will inject room cards -->
          </div>
        </section>

        <section style="margin-top:18px">
          <h3 style="margin:0 0 8px">Rate Packages</h3>
          <div class="pkg">
            <div class="rate-pill">Room Only</div>
            <div class="rate-pill">Bed & Breakfast</div>
            <div class="rate-pill">accomodation</div>
            <div class="rate-pill">other services included</div>
            <div class="rate-pill">All Inclusive (Select rooms)</div>
          </div>
        </section>

        <section style="margin-top:18px">
          <h3 style="margin:0 0 8px">Extras & Services</h3>
          <table>
            <thead><tr><th>Service</th><th>Price</th></tr></thead>
            <tbody id="extras-body">
              <!-- JS inject -->
            </tbody>
          </table>
        </section>
      </main>

      <!-- Right: booking preview -->
      <aside class="panel">
        <h3 style="margin:0 0 8px">Booking Preview</h3>
        <div class="small muted" style="margin-bottom:10px">Select a room and options to see calculations</div>

        <div class="booking-row"><div class="muted">Selected Room</div><div id="selRoom">—</div></div>
        <div class="booking-row" style="margin-top:8px"><div class="muted">Package</div><div id="selPkg">—</div></div>

        <div style="margin-top:10px;display:flex;gap:8px">
          <div style="flex:1">
            <label class="small muted">Nights</label>
            <input id="nights" type="number" min="1" value="1" style="width:100%;padding:8px;border-radius:8px;border:1px solid #eef2ff" />
          </div>
          <div style="width:120px">
            <label class="small muted">Guests</label>
            <input id="guests" type="number" min="1" value="1" style="width:100%;padding:8px;border-radius:8px;border:1px solid #eef2ff" />
          </div>
        </div>

        <div style="margin-top:12px">
          <h4 style="margin:0 0 6px">Add Extras</h4>
          <div id="extras-list" style="display:flex;flex-direction:column;gap:8px">
            <!-- extras inputs injected -->
          </div>
        </div>

        <div style="margin-top:12px">
          <div class="booking-row"><div class="muted">Subtotal</div><div id="subtotal">₦0</div></div>
          <div class="booking-row" style="margin-top:8px"><div class="muted">Tax (10%)</div><div id="tax">₦0</div></div>
          <div class="booking-row" style="margin-top:8px"><div style="font-weight:800">Total</div><div style="font-weight:900" id="total">₦0</div></div>
        </div>

        <button class="booking.php" id="bookBtn">Proceed to Book</button>
        <div class="small muted" style="margin-top:8px">Prices are indicative. Confirm availability and exact rates at time of booking.</div>
      </aside>
    </div>

    <footer>
      Note: Rates shown are examples for demo purposes. Peak season applies a surcharge; off-peak may have discounts. Taxes and service fees may vary.
    </footer>
  </div>

  <script>
    // Sample data
    const roomsData = [
      {
        id: 'single',
        name: 'Single Room',
        desc: 'Cozy single bed room. Ideal for solo travelers. 20–25 m².',
        base: 12000, // NGN per night (off-peak)
        capacity: 1,
        tags: ['room-only','b&b']
      },
      {
        id: 'double',
        name: 'Double Room',
        desc: 'Comfortable double bed, perfect for couples. 25–32 m².',
        base: 20000,
        capacity: 2,
        tags: ['room-only','b&b','half-board']
      },
      {
        id: '2 bed room',
        name: 'Deluxe Room',
        desc: 'Larger room with better views and amenities. 35–45 m².',
        base: 35000,
        capacity: 3,
        tags: ['b&b','half-board','full-board']
      },
      {
        id: '3bedroom',
        name: 'Suite',
        desc: 'Spacious suite with living area. Ideal for families or business stays.',
        base: 70000,
        capacity: 4,
        tags: ['half-board','full-board','all-inclusive']
      },
      {
        id: 'penthouse',
        name: 'Presidential Suite',
        desc: 'Top-tier suite with private lounge, top services and views.',
        base: 300000,
        capacity: 6,
        tags: ['full-board','all-inclusive','luxury']
      }
    ];

    const extras = [
      {id:'breakfast', name:'Breakfast (per guest)', price: 2000},
      {id:'airport', name:'Airport Transfer (one-way)', price: 8000},
      {id:'spa', name:'Spa Treatment (per person)', price: 15000},
      {id:'parking', name:'Parking (per night)', price: 1000},
      {id:'extra-bed', name:'Extra Bed (per night)', price: 5000}
    ];

    // App state
    let season = 'offpeak'; // offpeak or peak
    let currency = 'NGN'; // NGN or USD
    const exchange = { NGN:1, USD:0.0013 }; // demo rate
    let selectedRoom = null;
    let selectedPkg = 'Room Only';
    const selectedExtras = {}; // id -> qty (0/1/number)
    const pkgMultipliers = { 'Room Only':1.0, 'Bed & Breakfast':1.12, 'Half Board':1.25, 'Full Board':1.45, 'All Inclusive':1.9 };

    // DOM refs
    const roomsEl = document.getElementById('rooms');
    const extrasBody = document.getElementById('extras-body');
    const extrasList = document.getElementById('extras-list');
    const selRoomEl = document.getElementById('selRoom');
    const selPkgEl = document.getElementById('selPkg');
    const nightsEl = document.getElementById('nights');
    const guestsEl = document.getElementById('guests');
    const subtotalEl = document.getElementById('subtotal');
    const taxEl = document.getElementById('tax');
    const totalEl = document.getElementById('total');

    // helpers
    function money(ngn){
      if(currency === 'NGN') return `₦${Number(ngn).toLocaleString()}`;
      return `$${(ngn * exchange.USD).toFixed(2)}`;
    }

    function seasonMultiplier(){
      return season === 'peak' ? 1.35 : 1.0; // example: 35% increase in peak
    }

    function renderRooms(filter=''){
      roomsEl.innerHTML = '';
      const q = filter.trim().toLowerCase();
      roomsData.forEach(r=>{
        if(q && !(r.name + ' ' + r.desc + ' ' + r.tags.join(' ')).toLowerCase().includes(q)) return;

        const node = document.createElement('div');
        node.className = 'room';
        const priceOff = r.base * seasonMultiplier();
        node.innerHTML = `
          <div class="left">
            <h3>${r.name}</h3>
            <div class="meta">${r.desc}</div>
            <div class="rates">
              <div class="price-big">${money(priceOff)}</div>
              <div class="muted">per night (approx)</div>
            </div>
            <div style="margin-top:8px" class="small">
              <strong>Capacity:</strong> ${r.capacity} guest(s)
            </div>
            <div style="margin-top:8px" class="pkg">
              <button class="rate-pill" onclick="selectRoom('${r.id}','Room Only')">Room Only</button>
              <button class="rate-pill" onclick="selectRoom('${r.id}','recreational sevices')">Recreational service</button>
              <button class="rate-pill" onclick="selectRoom('${r.id}','dininig&bar')">dining&bar</button>
            </div>
          </div>
          <div style="width:120px;text-align:right">
            <div class="small muted">From</div>
            <div style="font-weight:900;font-size:1.05rem;margin-top:6px">${money(priceOff)}</div>
          </div>
        `;
        roomsEl.appendChild(node);
      });
    }

    function renderExtras(){
      extrasBody.innerHTML = '';
      extrasList.innerHTML = '';
      extras.forEach(ex=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${ex.name}</td><td>${money(ex.price)}</td>`;
        extrasBody.appendChild(tr);

        const div = document.createElement('div');
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.alignItems = 'center';
        div.innerHTML = `
          <div style="flex:1">${ex.name}</div>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="number" min="0" value="0" id="extra-${ex.id}" style="width:70px;padding:6px;border-radius:8px;border:1px solid #eef2ff" />
          </div>
        `;
        extrasList.appendChild(div);

        // wire change
        const input = div.querySelector(`#extra-${ex.id}`);
        input.addEventListener('input', ()=>{
          const v = Math.max(0, parseInt(input.value || 0));
          selectedExtras[ex.id] = v;
          calculate();
        });
      });
    }

    function selectRoom(roomId, pkg){
      selectedRoom = roomsData.find(r=>r.id===roomId);
      selectedPkg = pkg;
      // Reset extras counts to zero
      extras.forEach(ex=> selectedExtras[ex.id] = 0);
      // Display selection
      selRoomEl.textContent = selectedRoom ? `${selectedRoom.name}` : '—';
      selPkgEl.textContent = selectedPkg;
      // reset inputs
      nightsEl.value = 1;
      guestsEl.value = 1;
      // reset extras inputs in UI
      extras.forEach(ex=>{
        const inp = document.getElementById(`extra-${ex.id}`);
        if(inp) inp.value = 0;
      });
      calculate();
      // scroll to booking preview (mobile)
      if(window.innerWidth < 980) document.querySelector('aside.panel').scrollIntoView({behavior:'smooth'});
    }

    function calculate(){
      if(!selectedRoom){
        subtotalEl.textContent = money(0);
        taxEl.textContent = money(0);
        totalEl.textContent = money(0);
        return;
      }
      const nights = Math.max(1, parseInt(nightsEl.value || 1));
      const guests = Math.max(1, parseInt(guestsEl.value || 1));
      const baseNight = selectedRoom.base * seasonMultiplier();
      const pkgMultiplier = pkgMultipliers[selectedPkg] || 1.0;
      let roomCost = baseNight * pkgMultiplier * nights;

      // extras: some extras priced per guest, some per night, some one-off.
      // For demo, we'll treat: breakfast(per guest) * nights * qty, parking(per night)*nights, spa(per person)*qty, airport(one-off)*qty, extra-bed(per night)*nights*qty
      let extrasCost = 0;
      extras.forEach(ex=>{
        const qty = Number(selectedExtras[ex.id] || 0);
        if(!qty) return;
        if(ex.id === 'breakfast'){
          extrasCost += ex.price * qty * nights; // per guest per night
        } else if(ex.id === 'parking' || ex.id === 'extra-bed'){
          extrasCost += ex.price * qty * nights; // per night
        } else if(ex.id === 'spa'){
          extrasCost += ex.price * qty; // per person one-off
        } else if(ex.id === 'airport'){
          extrasCost += ex.price * qty; // one-off
        } else {
          extrasCost += ex.price * qty;
        }
      });

      // enforce capacity: if guests exceed capacity, auto-add extra-bed cost
      if(guests > selectedRoom.capacity){
        const extraGuests = guests - selectedRoom.capacity;
        const exBedPrice = extras.find(e=>e.id==='extra-bed')?.price || 0;
        extrasCost += extraGuests * exBedPrice * nights;
      }

      const subtotal = roomCost + extrasCost;
      const tax = subtotal * 0.10; // example 10% tax
      const total = subtotal + tax;

      subtotalEl.textContent = money(Math.round(subtotal));
      taxEl.textContent = money(Math.round(tax));
      totalEl.textContent = money(Math.round(total));
    }

    // wire controls
    document.getElementById('season').addEventListener('change', (e)=>{
      season = e.target.value;
      renderRooms(document.getElementById('search').value);
      calculate();
    });

    document.getElementById('currency').addEventListener('change', (e)=>{
      currency = e.target.value;
      renderRooms(document.getElementById('search').value);
      renderExtras();
      calculate();
    });

    document.getElementById('search').addEventListener('input', (e)=>{
      renderRooms(e.target.value);
    });

    nightsEl.addEventListener('input', calculate);
    guestsEl.addEventListener('input', calculate);

    document.getElementById('bookBtn').addEventListener('click', ()=>{
      if(!selectedRoom){
        alert('Please select a room and package to proceed with booking.');
        return;
      }
      const totalText = totalEl.textContent;
      alert(`Booking request received!\nRoom: ${selectedRoom.name}\nPackage: ${selectedPkg}\nNights: ${nightsEl.value}\nGuests: ${guestsEl.value}\nTotal: ${totalText}\n\n(For demo only — integrate with backend to finalize bookings.)`);
    });

    // init
    renderRooms();
    renderExtras();
    // default extras map
    extras.forEach(e=> selectedExtras[e.id] = 0);
    calculate();

    // expose small functions for debugging (optional)
    window._state = () => ({selectedRoom, selectedPkg, selectedExtras, season, currency});
  </script>
</body>
</html>