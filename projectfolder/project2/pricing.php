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
      <a href="restaurant.php">home</a>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Restaurant Menu — Pricing Details</title>
  <style>
    :root{
      --bg:#fafafa; --card:#ffffff; --muted:#6b7280; --accent:#ef4444;
      --accent-2:#f59e0b; --ok:#10b981; --shadow: 0 8px 24px rgba(15,23,42,0.08);
      --glass: rgba(255,255,255,0.6);
      --radius:12px;
      --ngn: '₦';
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;color:#0f172a;background:var(--bg)}
    .wrap{max-width:1100px;margin:28px auto;padding:18px}
    header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
    header h1{margin:0;font-size:1.3rem}
    .controls{display:flex;gap:12px;flex-wrap:wrap}

    /* search & filters */
    .search {display:flex;gap:8px;align-items:center}
    .search input{padding:10px 12px;border-radius:10px;border:1px solid #e6e9ef;background:#fff;outline:none;min-width:220px}
    select{padding:10px 12px;border-radius:10px;border:1px solid #e6e9ef;background:#fff}

    /* layout */
    .layout{display:grid;grid-template-columns:1fr 320px;gap:18px}
    @media(max-width:900px){.layout{grid-template-columns:1fr}}
    .menu{background:var(--card);padding:16px;border-radius:var(--radius);box-shadow:var(--shadow)}
    .order{background:linear-gradient(180deg,#fff,#fbfbfb);padding:16px;border-radius:var(--radius);box-shadow:var(--shadow);position:sticky;top:18px;height:fit-content}

    /* categories */
    .cats{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
    .cat{padding:8px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #eef2ff;color:#0f172a;cursor:pointer;font-weight:600}
    .cat.active{background:var(--accent);color:#fff;border-color:transparent;box-shadow:0 6px 20px rgba(239,68,68,0.14)}

    /* items */
    .items{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
    .item{background:linear-gradient(180deg,#fff,#fcfcfd);padding:12px;border-radius:12px;border:1px solid #f0f2f5;display:flex;flex-direction:column;gap:8px}
    .item .head{display:flex;justify-content:space-between;align-items:start;gap:8px}
    .item h4{margin:0;font-size:1rem}
    .price{font-weight:800;color:var(--accent)}
    .desc{color:var(--muted);font-size:.95rem}
    .meta{display:flex;gap:8px;align-items:center;font-size:.85rem;color:var(--muted)}
    .badge{padding:4px 8px;border-radius:999px;background:#fffbeb;color:var(--accent-2);font-weight:700}

    .add{margin-top:auto;display:flex;gap:8px}
    .btn{padding:8px 12px;border-radius:10px;border:none;cursor:pointer;font-weight:700}
    .btn.add{background:var(--ok);color:#fff}
    .btn.info{background:#eef2ff;color:#1e293b;border:1px solid #dbeafe}

    /* order */
    .order h3{margin:0 0 10px}
    .order-list{display:flex;flex-direction:column;gap:8px;max-height:360px;overflow:auto;padding-right:6px}
    .order-row{display:flex;justify-content:space-between;gap:8px;align-items:center;background:#fff;padding:8px;border-radius:10px;border:1px solid #f1f5f9}
    .qty{display:flex;align-items:center;gap:8px}
    .qty button{padding:6px 8px;border-radius:8px;border:1px solid #e6e9ef;background:#fff;cursor:pointer}
    .total{font-weight:900;font-size:1.05rem;margin-top:12px;text-align:right}
    .checkout{margin-top:12px;width:100%;padding:10px;border-radius:10px;border:none;background:var(--accent);color:#fff;font-weight:800;cursor:pointer}

    /* small */
    .muted{color:var(--muted);font-size:.9rem}
    .empty{padding:24px;border-radius:10px;text-align:center;color:var(--muted);background:#fff;border:1px dashed #eef2ff}
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>Restaurant Menu — Pricing Details</h1>
      <div class="controls">
        <div class="search">
          <input id="q" type="text" placeholder="Search dishes or ingredients (e.g., 'chicken')" />
        </div>
        <select id="currency">
          <option value="NGN">₦ NGN</option>
          <option value="USD">$ USD</option>
        </select>
      </div>
    </header>

    <div class="layout">
      <!-- menu -->
      <main class="menu" id="menu">
        <div class="cats" id="cats">
          <!-- categories injected by JS -->
        </div>

        <div class="items" id="items">
          <!-- items injected by JS -->
        </div>
      </main>

      <!-- order summary -->
      <aside class="order">
        <h3>Order Summary</h3>
        <div class="order-list" id="orderList">
          <div class="empty" id="emptyOrder">No items yet — add something to see pricing here.</div>
        </div>

        <div class="total" id="total">Total: ₦0</div>
        <button class="checkout" id="checkout">Checkout</button>
      </aside>
    </div>
  </div>

  <script>
    // sample menu data (name, desc, price in NGN, category, tag)
    const menu = [
      {id:1, name:"Classic Caesar Salad", desc:"Romaine, parmesan, croutons, Caesar dressing", price:2200, cat:"Starters", tag:"veg"},
      {id:2, name:"Spicy Chicken Wings (6pcs)", desc:"Crisp fried wings with chilli glaze", price:3200, cat:"Starters", tag:"hot"},
      {id:3, name:"Tomato Basil Soup", desc:"Creamy tomato with basil and garlic crostini", price:1800, cat:"Starters", tag:"veg"},
      {id:4, name:"Grilled Salmon Fillet", desc:"Served with lemon butter sauce and seasonal veg", price:7800, cat:"Mains", tag:"chef"},
      {id:5, name:"Beef Steak (Ribeye)", desc:"300g medium rare, served with fries and pepper sauce", price:12000, cat:"Mains", tag:"prime"},
      {id:6, name:"Chicken Alfredo Pasta", desc:"Fettuccine, creamy alfredo, grilled chicken", price:5200, cat:"Mains", tag:"popular"},
      {id:7, name:"Margherita Pizza (12\")", desc:"Tomato, mozzarella, fresh basil", price:4800, cat:"Mains", tag:"veg"},
      {id:8, name:"Chocolate Lava Cake", desc:"Warm chocolate cake with molten center", price:1600, cat:"Desserts", tag:"sweet"},
      {id:9, name:"Vanilla Cheesecake", desc:"Classic cheesecake with berry compote", price:1700, cat:"Desserts", tag:"sweet"},
      {id:10, name:"Fresh Fruit Platter", desc:"Seasonal fruits sliced and served fresh", price:1400, cat:"Desserts", tag:"veg"},
      {id:11, name:"Espresso", desc:"Single/double shot espresso", price:800, cat:"Drinks", tag:"hot"},
      {id:12, name:"Fresh Orange Juice", desc:"Cold-pressed orange juice", price:1000, cat:"Drinks", tag:"cold"},
      {id:13, name:"Bottle Water (750ml)", desc:"Still mineral water", price:400, cat:"Drinks", tag:"cold"}
    ];

    // app state
    let currency = 'NGN';
    let exchange = { NGN:1, USD:0.0013 }; // sample conversion (not live)
    let order = {}; // id -> qty

    const catsEl = document.getElementById('cats');
    const itemsEl = document.getElementById('items');
    const qEl = document.getElementById('q');
    const currencyEl = document.getElementById('currency');
    const orderListEl = document.getElementById('orderList');
    const totalEl = document.getElementById('total');
    const emptyOrderEl = document.getElementById('emptyOrder');

    // categories
    const categories = ["All", ...Array.from(new Set(menu.map(i=>i.cat)))];

    let activeCat = "All";

    function formatPrice(ngn){
      if(currency === 'NGN') return `₦${ngn.toLocaleString()}`;
      const val = +(ngn * exchange.USD).toFixed(2);
      return `$${val.toLocaleString()}`;
    }

    function renderCats(){
      catsEl.innerHTML = '';
      categories.forEach(c=>{
        const b = document.createElement('button');
        b.className = 'cat' + (c===activeCat ? ' active' : '');
        b.textContent = c;
        b.onclick = ()=>{ activeCat = c; renderCats(); renderItems(); };
        catsEl.appendChild(b);
      });
    }

    function renderItems(){
      const term = qEl.value.trim().toLowerCase();
      itemsEl.innerHTML = '';
      const filtered = menu.filter(it=>{
        const okCat = activeCat === 'All' ? true : it.cat === activeCat;
        const okTerm = !term || (it.name + ' ' + it.desc + ' ' + it.tag).toLowerCase().includes(term);
        return okCat && okTerm;
      });
      if(filtered.length === 0){
        itemsEl.innerHTML = '<div class="empty" style="grid-column:1/-1">No items found</div>';
        return;
      }
      filtered.forEach(it=>{
        const div = document.createElement('div');
        div.className = 'item';
        div.innerHTML = `
          <div class="head">
            <h4>${it.name}</h4>
            <div class="price">${formatPrice(it.price)}</div>
          </div>
          <div class="desc">${it.desc}</div>
          <div class="meta">
            <div class="badge">${it.tag.toUpperCase()}</div>
            <div class="muted">${it.cat}</div>
          </div>
          <div class="add">
            <button class="btn info" onclick="showInfo(${it.id})">Details</button>
            <button class="btn add" onclick="addToOrder(${it.id})">Add</button>
          </div>
        `;
        itemsEl.appendChild(div);
      });
    }

    function addToOrder(id){
      order[id] = (order[id] || 0) + 1;
      renderOrder();
    }

    function showInfo(id){
      const it = menu.find(m=>m.id===id);
      alert(`${it.name}\n\n${it.desc}\n\nPrice: ${formatPrice(it.price)}`);
    }

    function renderOrder(){
      orderListEl.innerHTML = '';
      const ids = Object.keys(order).map(x=>+x);
      if(ids.length === 0){
        orderListEl.appendChild(emptyOrderEl);
        totalEl.textContent = `Total: ${formatPrice(0)}`;
        return;
      }
      ids.forEach(id=>{
        const it = menu.find(m=>m.id===id);
        const qty = order[id];
        const row = document.createElement('div');
        row.className = 'order-row';
        row.innerHTML = `
          <div style="flex:1">
            <div style="font-weight:700">${it.name}</div>
            <div class="muted" style="font-size:.85rem">${formatPrice(it.price)} × ${qty} = <strong>${formatPrice(it.price * qty)}</strong></div>
          </div>
          <div style="display:flex;flex-direction:column;gap:6px;align-items:end">
            <div class="qty">
              <button onclick="decrease(${id})">−</button>
              <div style="padding:6px 10px;background:#f8fafc;border-radius:8px">${qty}</div>
              <button onclick="increase(${id})">+</button>
            </div>
            <button style="margin-top:6px;background:#fff;border:1px solid #fee2e2;color:var(--accent);padding:6px 8px;border-radius:8px;cursor:pointer" onclick="remove(${id})">Remove</button>
          </div>
        `;
        orderListEl.appendChild(row);
      });
      // total
      const sum = ids.reduce((s,id)=>s + menu.find(m=>m.id===id).price * order[id], 0);
      totalEl.textContent = `Total: ${formatPrice(sum)}`;
    }

    function increase(id){ order[id]++; renderOrder(); }
    function decrease(id){ if(order[id] > 1) order[id]--; else delete order[id]; renderOrder(); }
    function remove(id){ delete order[id]; renderOrder(); }

    // events
    qEl.addEventListener('input', renderItems);
    currencyEl.addEventListener('change', (e)=>{ currency = e.target.value; renderItems(); renderOrder(); });

    document.getElementById('checkout').addEventListener('click', ()=>{
      const ids = Object.keys(order);
      if(!ids.length){ alert('Your order is empty. Add items to proceed.'); return; }
      const sum = ids.reduce((s,id)=>s + menu.find(m=>m.id===+id).price * order[id], 0);
      alert(`Order placed!\nTotal: ${currency === 'NGN' ? '₦' : '$'}${(currency==='NGN'? sum.toLocaleString() : (sum*exchangeRate()).toFixed(2))}`);
      // clear
      order = {};
      renderOrder();
    });

    // helper conversion when checking out to USD for alert only
    function exchangeRate(){ return 0.0013; }

    // init
    renderCats();
    renderItems();
    renderOrder();

    // expose for inline onclick calls
    window.addToOrder = addToOrder;
    window.showInfo = showInfo;
    window.increase = increase;
    window.decrease = decrease;
    window.remove = remove;
  </script>
</body>
</html>
