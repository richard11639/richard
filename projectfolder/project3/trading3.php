<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forex Demo Trading — Richard</title>
<style>
:root{
  --bg:#071025; --card:#0c1624; --muted:#9fb3d1; --accent:#3ea0ff;
  --up:#16c784; --down:#ff6b6b; --glass: rgba(255,255,255,0.04);
  --radius:12px; --currency: '₦';
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,Arial,sans-serif;background:var(--bg);color:#e9f0ff;-webkit-font-smoothing:antialiased}
.container{max-width:1200px;margin:20px auto;padding:12px}
.top-hero{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.brand{display:flex;align-items:center;gap:10px}
.logo {width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg,#3ea0ff,#8de0ff);display:flex;align-items:center;justify-content:center;font-weight:900;color:#031022}
.title{font-size:1.2rem;font-weight:800}
.nav{display:flex;gap:8px;margin-left:auto;flex-wrap:wrap}
.nav button{background:transparent;border:1px solid var(--glass);color:var(--muted);padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:700}
.main-grid{display:grid;grid-template-columns:320px 1fr;gap:12px;margin-top:12px}
@media(max-width:980px){.main-grid{grid-template-columns:1fr}}
.card{background:linear-gradient(180deg,var(--card),#071428);border-radius:var(--radius);padding:12px;border:1px solid var(--glass)}
.market-list{display:flex;flex-direction:column;gap:8px}
.market-item{display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:8px;cursor:pointer;border:1px solid transparent}
.market-item:hover{background:rgba(255,255,255,0.02)}
.market-item.active{background:linear-gradient(90deg, rgba(62,160,255,0.12), rgba(141,224,255,0.04));border-color:rgba(62,160,255,0.18)}
.pair{font-weight:800}
.price{font-weight:800}
.change{font-weight:800;padding:6px 8px;border-radius:8px;font-size:.85rem}
.change.up{background:rgba(22,199,132,0.12);color:var(--up)}
.change.down{background:rgba(255,107,107,0.08);color:var(--down)}
.top-stats{display:flex;gap:12px;flex-wrap:wrap}
.stat{padding:10px;border-radius:10px;background:linear-gradient(180deg,rgba(255,255,255,0.02),transparent);min-width:160px}
.row{display:flex;align-items:center;justify-content:space-between;gap:8px}
.small{color:var(--muted);font-size:.9rem}
.chart-wrap{margin-top:12px}
#chart{width:100%;height:300px;background:linear-gradient(180deg,#021023,#04162a);border-radius:10px;border:1px solid var(--glass);display:block}
.controls{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;align-items:center}
.subnav{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
.subnav button{background:transparent;border:none;color:var(--muted);padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:700}
.subnav button.active{color:#fff;background:rgba(255,255,255,0.03)}
.bottom-grid{display:grid;grid-template-columns:1fr 360px;gap:12px;margin-top:12px}
@media(max-width:1100px){.bottom-grid{grid-template-columns:1fr}}
.order-card .split{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.btn{padding:10px;border-radius:8px;border:none;cursor:pointer;font-weight:800}
.buy{background:linear-gradient(90deg,#00c77f,#009a59);color:#052016}
.sell{background:linear-gradient(90deg,#ff6b6b,#b32b2b);color:#200808}
.ghost{background:transparent;border:1px solid var(--glass);color:var(--muted)}
.orderbook{display:flex;gap:12px}
.book-side{flex:1;max-height:200px;overflow:auto}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:6px;font-size:.9rem;text-align:left;border-bottom:1px dashed rgba(255,255,255,0.03)}
.center{text-align:center}
.right{text-align:right}
.footer{margin-top:12px;color:var(--muted);font-size:.9rem}
.account-sum{font-size:1.1rem;font-weight:900}
.signal-box{padding:10px;border-radius:8px;background:linear-gradient(180deg,rgba(62,160,255,0.04),transparent)}
.badge{padding:6px 8px;border-radius:999px;background:rgba(255,255,255,0.02);font-weight:800}
</style>
</head>
<body>
<div class="container">
  <div class="top-hero">
    <div class="brand">
      <div class="logo">FX</div>
      <div>
        <div class="title">Richard Forex</div>
        <div class="small">Forex & Spot Trading · ₦ Base</div>
      </div>
    </div>
    <div class="nav">
      <button><a href="deposit.php" style="color:inherit;text-decoration:none">Deposit</a></button>
      <button><a href="withdraw.php" style="color:inherit;text-decoration:none">Withdraw</a></button>
      <button><a href="exchange.php" style="color:inherit;text-decoration:none">Exchange</a></button>
      <button><a href="logout.php" style="color:inherit;text-decoration:none">Logout</a></button>
    </div>
  </div>

  <div class="subnav">
    <button class="active" data-sub="signal">Signal</button>
    <button data-sub="account">Account</button>
    <button data-sub="team">Team Assets</button>
    <div style="margin-left:auto" class="badge">Account total: <span class="account-sum" id="acctSum">₦0</span></div>
  </div>

  <div class="main-grid">
    <!-- Markets -->
    <aside class="card">
      <div class="row"><div><strong>Markets</strong></div></div>
      <div class="market-list" id="marketList"></div>
    </aside>

    <!-- Trading Panel -->
    <main>
      <div class="card">
        <div class="top-stats">
          <div class="stat"><div class="small">Pair</div><div id="pairName" style="font-weight:900">EUR/USD</div></div>
          <div class="stat"><div class="small">Last Price (₦)</div><div id="lastPrice" style="font-weight:900">—</div></div>
          <div class="stat"><div class="small">24h Change</div><div id="dayChange" class="change">—</div></div>
          <div class="stat"><div class="small">24h Volume</div><div id="vol24" style="font-weight:900">—</div></div>
        </div>
        <div class="chart-wrap"><canvas id="chart" width="900" height="300"></canvas></div>
        <div class="controls">
          <div class="small muted">Type</div>
          <select id="orderType"><option value="market">Market</option><option value="limit">Limit</option></select>
          <div class="small muted">Side</div>
          <select id="orderSide"><option value="buy">Buy</option><option value="sell">Sell</option></select>
          <div class="small muted">Price (₦)</div>
          <input id="orderPrice" placeholder="Limit price">
          <div class="small muted">Amount</div>
          <input id="orderAmount" placeholder="Amount">
          <button id="placeOrder" class="btn buy">Place Order</button>
        </div>
      </div>

      <div class="bottom-grid">
        <div class="card">
          <div style="display:flex;justify-content:space-between"><strong>Order Book</strong></div>
          <div class="orderbook" style="margin-top:8px">
            <div class="book-side card" style="padding:8px">
              <div class="small muted">Asks</div>
              <table class="table" id="asksTable"><thead><tr><th>Price</th><th class="right">Size</th></tr></thead><tbody></tbody></table>
            </div>
            <div class="book-side card" style="padding:8px">
              <div class="small muted">Bids</div>
              <table class="table" id="bidsTable"><thead><tr><th>Price</th><th class="right">Size</th></tr></thead><tbody></tbody></table>
            </div>
          </div>

          <div style="margin-top:12px;display:flex;gap:8px">
            <div style="flex:1">
              <strong>Open Orders</strong>
              <table class="table" id="openOrders"><thead><tr><th>Time</th><th>Side</th><th>Price</th><th>Amount</th></tr></thead><tbody></tbody></table>
            </div>
            <div style="flex:1">
              <strong>Trade History</strong>
              <table class="table" id="tradeHistory"><thead><tr><th>Time</th><th>Side</th><th>Price</th><th>Amount</th></tr></thead><tbody></tbody></table>
            </div>
          </div>
        </div>

        <aside class="card">
          <div style="display:flex;flex-direction:column;gap:10px">
            <div>
              <strong>Signal</strong>
              <div class="signal-box" id="signalBox">No active signals — demo only.</div>
            </div>
            <div>
              <strong>Account</strong>
              <div class="small muted">Account balance (₦)</div>
              <div style="font-weight:900;font-size:1.25rem;margin-top:6px" id="accountSum">₦0</div>
              <div style="margin-top:8px;display:flex;gap:8px">
                <a class="nav-link" href="deposit.php">Deposit</a>
                <button class="ghost btn">Withdraw</button>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>
  </div>
  <div class="footer small muted">Demo Forex trading UI — no real funds. Connect to backend for production.</div>
</div>

<script>
// Forex pairs
const pairs = ['EUR/USD','USD/JPY','GBP/USD','USD/CHF','AUD/USD','USD/CAD','NZD/USD','EUR/GBP','EUR/JPY','GBP/JPY'];
const markets = {};
pairs.forEach(p=>{
  const base = Math.round(Math.random()*100 + 100);
  markets[p]={symbol:p,last:base,changePct:(Math.random()-0.5)*2,vol24:Math.floor(Math.random()*100000+1000),prices:Array.from({length:120},()=>base+Math.random()*2-1)};
});
let activePair = pairs[0], orders=[], trades=[], accountBalance=0;

// DOM
const marketListEl=document.getElementById('marketList');
const pairNameEl=document.getElementById('pairName');
const lastPriceEl=document.getElementById('lastPrice');
const dayChangeEl=document.getElementById('dayChange');
const vol24El=document.getElementById('vol24');
const chartCanvas=document.getElementById('chart');
const ctx=chartCanvas.getContext('2d');
const asksTable=document.querySelector('#asksTable tbody');
const bidsTable=document.querySelector('#bidsTable tbody');
const openOrdersTable=document.querySelector('#openOrders tbody');
const tradeHistoryTable=document.querySelector('#tradeHistory tbody');
const orderTypeEl=document.getElementById('orderType');
const orderSideEl=document.getElementById('orderSide');
const orderPriceEl=document.getElementById('orderPrice');
const orderAmountEl=document.getElementById('orderAmount');
const placeOrderBtn=document.getElementById('placeOrder');
const acctSumEl=document.getElementById('acctSum');
const accountSumDisplay=document.getElementById('accountSum');

// Helpers
const fmtN=n=>n.toLocaleString();
const fmt2=n=>Number(n).toFixed(2);

// Render market list
function renderMarketList(){
  marketListEl.innerHTML='';
  pairs.forEach(p=>{
    const m=markets[p];
    const div=document.createElement('div');
    div.className='market-item'+(p===activePair?' active':'');
    div.innerHTML=`<div class="pair">${p}</div><div class="price">₦ ${fmtN(m.last)}</div>`;
    div.onclick=()=>{activePair=p; updateUI(); renderMarketList();}
    marketListEl.appendChild(div);
  });
}

// Draw chart
function drawChart(){
  const w=chartCanvas.width=chartCanvas.clientWidth;
  const h=chartCanvas.height=chartCanvas.clientHeight;
  ctx.clearRect(0,0,w,h);
  const data=markets[activePair].prices.slice(-200);
  if(data.length<2)return;
  const min=Math.min(...data);
  const max=Math.max(...data);
  const step=w/(data.length-1);
  ctx.beginPath();
  data.forEach((v,i)=>{const x=i*step; const y=h-((v-min)/(max-min||1))*h; i===0?ctx.moveTo(x,y):ctx.lineTo(x,y);});
  ctx.strokeStyle='#3ea0ff'; ctx.lineWidth=2; ctx.stroke();
  ctx.lineTo(w,h); ctx.lineTo(0,h); ctx.closePath();
  const grad=ctx.createLinearGradient(0,0,0,h); grad.addColorStop(0,'rgba(62,160,255,0.12)'); grad.addColorStop(1,'rgba(62,160,255,0)');
  ctx.fillStyle=grad; ctx.fill();
  ctx.fillStyle='#e9f0ff'; ctx.font='13px Inter, Arial'; ctx.fillText('₦ '+fmtN(markets[activePair].last),8,18);
}

// Orderbook
function renderOrderBook(){
  asksTable.innerHTML=''; bidsTable.innerHTML='';
  const last=markets[activePair].last;
  for(let i=10;i>0;i--){ const p=Math.round(last+i*(Math.random()*2+1)); const s=(Math.random()*5).toFixed(2); asksTable.innerHTML+=`<tr><td>₦ ${fmtN(p)}</td><td class="right">${s}</td></tr>`;}
  for(let i=10;i>0;i--){ const p=Math.round(last-i*(Math.random()*2+1)); const s=(Math.random()*5).toFixed(2); bidsTable.innerHTML+=`<tr><td>₦ ${fmtN(p)}</td><td class="right">${s}</td></tr>`;}
}

// Update UI
function updateUI(){
  const m=markets[activePair];
  pairNameEl.textContent=m.symbol;
  lastPriceEl.textContent='₦ '+fmtN(m.last);
  dayChangeEl.textContent=(m.changePct>=0?'+':'')+fmt2(m.changePct)+'%';
  dayChangeEl.className='change '+(m.changePct>=0?'up':'down');
  vol24El.textContent=m.vol24.toLocaleString();
  drawChart();
  renderOrderBook();
}

// Orders/Trades
function renderOrders(){
  openOrdersTable.innerHTML='';
  if(orders.length===0){openOrdersTable.innerHTML='<tr><td colspan="4" class="small muted">No open orders</td></tr>';return;}
  orders.forEach(o=>{openOrdersTable.innerHTML+=`<tr><td class="small">${o.time}</td><td>${o.side.toUpperCase()}</td><td>₦ ${fmtN(o.price)}</td><td>${o.amount}</td></tr>`;});
}
function renderTrades(){
  tradeHistoryTable.innerHTML='';
  if(trades.length===0){tradeHistoryTable.innerHTML='<tr><td colspan="4" class="small muted">No trades yet</td></tr>';return;}
  trades.slice().reverse().forEach(t=>{tradeHistoryTable.innerHTML+=`<tr><td class="small">${t.time}</td><td>${t.side.toUpperCase()}</td><td>₦ ${fmtN(t.price)}</td><td>${t.amount}</td></tr>`;});
}

// Place order
placeOrderBtn.onclick=()=>{
  const type=orderTypeEl.value;
  const side=orderSideEl.value;
  const amt=parseFloat(orderAmountEl.value||0);
  const price=parseFloat(orderPriceEl.value||markets[activePair].last);
  if(!amt||amt<=0){alert('Enter valid amount'); return;}
  if(accountBalance<=0){alert('Your account balance is zero. Deposit to trade.'); return;}
  const nowStr=new Date().toLocaleTimeString();
  if(type==='market'){trades.push({time:nowStr,side,price:markets[activePair].last,amount:amt}); alert('Market order executed');}
  else{orders.push({symbol:activePair,time:nowStr,side,price,amount:amt}); alert('Limit order placed');}
  renderOrders(); renderTrades();
}

// Simulate ticks
function simulateTick(){
  pairs.forEach(sym=>{
    const m=markets[sym];
    const change=(Math.random()-0.5)*0.02*m.last; m.last=Math.max(1,Math.round(m.last+change));
    m.prices.push(m.last); if(m.prices.length>300)m.prices.shift();
    m.changePct=(Math.random()-0.5)*2; m.vol24=Math.floor(m.vol24*(1+(Math.random()-0.5)*0.2));
  });
  for(let i=orders.length-1;i>=0;i--){
    const o=orders[i]; const m=markets[o.symbol];
    if((o.side==='buy'&&m.last<=o.price)||(o.side==='sell'&&m.last>=o.price)){ trades.push({time:new Date().toLocaleTimeString(),side:o.side,price:o.price,amount:o.amount}); orders.splice(i,1);}
  }
  updateUI(); renderMarketList(); renderOrders(); renderTrades();
}

// Init
renderMarketList(); updateUI(); renderOrders(); renderTrades();
setInterval(simulateTick,1500);
acctSumEl.textContent='₦'+fmtN(accountBalance); accountSumDisplay.textContent='₦'+fmtN(accountBalance);
window.addEventListener('resize',()=>drawChart());
</script>
</body>
</html>
