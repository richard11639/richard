<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Richard — Forex Demo Trading</title>
<style>
:root{
  --bg:#071025; --card:#0c1624; --muted:#9fb3d1; --accent:#3ea0ff; 
  --up:#16c784; --down:#ff6b6b; --glass:rgba(255,255,255,0.04); --radius:12px;
}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:var(--bg);color:#e9f0ff;-webkit-font-smoothing:antialiased}
.container{max-width:1200px;margin:18px auto;padding:12px}
.top-hero{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.brand{display:flex;align-items:center;gap:10px}
.logo{width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg,#3ea0ff,#8de0ff);display:flex;align-items:center;justify-content:center;font-weight:900;color:#031022}
.title{font-size:1.2rem;font-weight:800}
.nav{display:flex;gap:8px;margin-left:auto;flex-wrap:wrap}
.nav button{background:transparent;border:1px solid var(--glass);color:var(--muted);padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:700}
.main-grid{display:grid;grid-template-columns:320px 1fr;gap:12px;margin-top:12px}
@media(max-width:980px){.main-grid{grid-template-columns:1fr}}
.card{background:linear-gradient(180deg,var(--card),#071428);border-radius:var(--radius);padding:12px;border:1px solid var(--glass)}
.market-list{display:flex;flex-direction:column;gap:8px;max-height:480px;overflow-y:auto}
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
.subnav{display:flex;gap:8px;margin-top:12px}
.subnav button{background:transparent;border:none;color:var(--muted);padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:700}
.subnav button.active{color:#fff;background:rgba(255,255,255,0.03)}
.bottom-grid{display:grid;grid-template-columns:1fr 360px;gap:12px;margin-top:12px}
@media(max-width:1100px){.bottom-grid{grid-template-columns:1fr}}
.order-card .split{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.btn{padding:10px;border-radius:8px;border:none;cursor:pointer;font-weight:800}
.buy{background:linear-gradient(90deg,#00c77f,#009a59);color:#052016}
.sell{background:linear-gradient(90deg,#ff6b6b,#b32b2b);color:#200808}
.ghost{background:transparent;border:1px solid var(--glass);color:var(--muted)}
.orderbook{display:flex;gap:12px;flex-wrap:wrap}
.book-side{flex:1;max-height:200px;overflow:auto}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:6px;font-size:.9rem;text-align:left;border-bottom:1px dashed rgba(255,255,255,0.03)}
.center{text-align:center}
.right{text-align:right}
.footer{margin-top:12px;color:var(--muted);font-size:.9rem}
.badge{padding:6px 8px;border-radius:999px;background:rgba(255,255,255,0.02);font-weight:800}
.account-sum{font-size:1.1rem;font-weight:900}
.signal-box{padding:10px;border-radius:8px;background:linear-gradient(180deg,rgba(62,160,255,0.04),transparent)}
.top-images{display:flex;gap:8px;align-items:center;margin-left:12px;flex-wrap:wrap}
.top-images img{width:120px;height:60px;border-radius:8px;object-fit:cover;border:1px solid var(--glass)}
</style>
</head>
<body>
<div class="container">
  <div class="top-hero">
    <div class="brand">
      <div class="logo">FX</div>
      <div>
        <div class="title">RICHARD Forex</div>
        <div class="small">Demo Trading · ₦ Base</div>
      </div>
    </div>
    <div class="nav">
      <button><a href="deposit.php">Deposit</a></button>
      <button><a href="withdraw.php">Withdraw</a></button>
      <button><a href="swap.php">Swap</a></button>
      <button><a href="exchange.php">Exchange</a></button>
      <button><a href="signal.php">Signal</a></button>
      <button><a href="service.php">Services</a></button>
      <button><a href="logout.php">Log Out</a></button>
    </div>
    <div class="top-images" aria-hidden="true">
      <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='240' height='120'><rect width='100%' height='100%' fill='%23031a2b'/><text x='50%' y='50%' fill='%238de0ff' font-size='20' font-family='Segoe UI' text-anchor='middle' dominant-baseline='central'>Forex Heat</text></svg>" alt="">
      <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='240' height='120'><rect width='100%' height='100%' fill='%23031a2b'/><text x='50%' y='50%' fill='%23ffd166' font-size='20' font-family='Segoe UI' text-anchor='middle' dominant-baseline='central'>Top Movers</text></svg>" alt="">
    </div>
  </div>
  <div class="subnav">
    <button class="active" data-sub="signal">Signal</button>
    <button data-sub="team">Team Assets</button>
    <button data-sub="account">Account</button>
    <div style="margin-left:auto" class="badge">Account total: <span class="account-sum" id="acctSum">₦0</span></div>
  </div>
  <div class="main-grid">
    <aside class="card">
      <div class="row"><strong>Markets</strong></div>
      <div class="market-list" id="marketList"></div>
      <div style="margin-top:14px" class="small muted">Quick filters</div>
      <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
        <button class="ghost badge" id="favAll">All</button>
        <button class="ghost badge" data-filter="top">Top</button>
        <button class="ghost badge" data-filter="gainer">Gainers</button>
        <button class="ghost badge" data-filter="loser">Losers</button>
      </div>
    </aside>
    <main>
      <div class="card">
        <div class="top-stats">
          <div class="stat"><div class="small">Pair</div><div id="pairName">EUR/USD</div></div>
          <div class="stat"><div class="small">Last Price (₦)</div><div id="lastPrice">—</div></div>
          <div class="stat"><div class="small">24h Change</div><div id="dayChange" class="change">—</div></div>
          <div class="stat"><div class="small">24h Volume</div><div id="vol24">—</div></div>
          <div class="stat"><div class="small">Market Cap</div><div id="mktcap">—</div></div>
        </div>
        <div class="chart-wrap"><canvas id="chart"></canvas></div>
        <div class="controls">
          <div class="small muted">Order Type</div>
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
          <div style="display:flex;justify-content:space-between;align-items:center">
            <strong>Order Book</strong><div class="small muted">Live (simulated)</div>
          </div>
          <div class="orderbook">
            <div class="book-side card" style="padding:8px">
              <div class="small muted">Asks</div>
              <table class="table" id="asksTable"><tbody></tbody></table>
            </div>
            <div class="book-side card" style="padding:8px">
              <div class="small muted">Bids</div>
              <table class="table" id="bidsTable"><tbody></tbody></table>
            </div>
          </div>
          <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
            <div style="flex:1">
              <strong>Open Orders</strong>
              <table class="table" id="openOrders"><tbody></tbody></table>
            </div>
            <div style="flex:1">
              <strong>Trade History</strong>
              <table class="table" id="tradeHistory"><tbody></tbody></table>
            </div>
          </div>
        </div>
        <aside class="card">
          <div style="display:flex;flex-direction:column;gap:10px">
            <div><strong>Signal</strong><div class="signal-box" id="signalBox">No active signals — demo only.</div></div>
            <div><strong>Team Assets</strong><div class="small muted">Team holdings (demo)</div><div style="display:flex;justify-content:space-between;margin-top:6px"><div>EUR</div><div>0.00</div></div></div>
            <div><strong>Account</strong><div class="small muted">Account balance (₦)</div><div id="accountSum" style="font-weight:900;font-size:1.25rem;margin-top:6px">₦0</div></div>
          </div>
        </aside>
      </div>
    </main>
  </div>
  <div class="footer small muted">Demo trading UI — no real funds. Production requires backend integration, real price feeds, wallet signing, and strong security.</div>
</div>

<script>
// Forex pairs demo
const pairs = ['EUR/USD','USD/JPY','GBP/USD','AUD/USD','USD/CAD','NZD/USD','USD/CHF','EUR/GBP','EUR/JPY','GBP/JPY'];
const markets = {};
pairs.forEach(p=>{
  const price = Math.round(Math.random()*10000)/100;
  markets[p] = {symbol:p,last:price,changePct:0,vol24:Math.floor(Math.random()*50000),mktcap:Math.floor(Math.random()*100000000),prices:Array.from({length:120},()=>price)};
});
let activePair = pairs[0];
let orders = [];
let trades = [];
const acctVal = 0;

// DOM
const marketListEl = document.getElementById('marketList');
const pairNameEl = document.getElementById('pairName');
const lastPriceEl = document.getElementById('lastPrice');
const dayChangeEl = document.getElementById('dayChange');
const vol24El = document.getElementById('vol24');
const mktcapEl = document.getElementById('mktcap');
const chartCanvas = document.getElementById('chart');
const ctx = chartCanvas.getContext('2d');
const asksTable = document.querySelector('#asksTable tbody');
const bidsTable = document.querySelector('#bidsTable tbody');
const openOrdersTable = document.querySelector('#openOrders tbody');
const tradeHistoryTable = document.querySelector('#tradeHistory tbody');
const orderTypeEl = document.getElementById('orderType');
const orderSideEl = document.getElementById('orderSide');
const orderPriceEl = document.getElementById('orderPrice');
const orderAmountEl = document.getElementById('orderAmount');
const placeOrderBtn = document.getElementById('placeOrder');
const acctSumEl = document.getElementById('acctSum');
acctSumEl.textContent='₦'+acctVal;

// helper
const fmtN=n=>typeof n==='number'?n.toLocaleString():n;

// render market list
function renderMarketList(){
  marketListEl.innerHTML='';
  pairs.forEach(p=>{
    const m=markets[p];
    const div=document.createElement('div');
    div.className='market-item'+(p===activePair?' active':'');
    div.innerHTML=`<div style="display:flex;flex-direction:column"><div class="pair">${p}</div><div class="small muted">${m.vol24.toLocaleString()} vol</div></div><div style="text-align:right"><div class="price">₦ ${fmtN(m.last)}</div><div style="margin-top:6px"><span class="change ${m.changePct>=0?'up':'down'}">${m.changePct>=0?'+':''}${m.changePct}%</span></div></div>`;
    div.onclick=()=>{activePair=p;updateUI();renderMarketList();}
    marketListEl.appendChild(div);
  });
}

// chart
function drawChart(){
  const w=chartCanvas.width=chartCanvas.clientWidth,h=chartCanvas.height=chartCanvas.clientHeight;
  ctx.clearRect(0,0,w,h);
  const data=markets[activePair].prices.slice(-200);
  if(data.length<2) return;
  const min=Math.min(...data),max=Math.max(...data),step=w/(data.length-1);
  ctx.beginPath();
  data.forEach((v,i)=>{const x=i*step,y=h-((v-min)/(max-min||1))*h;if(i===0)ctx.moveTo(x,y);else ctx.lineTo(x,y);});
  ctx.strokeStyle='#3ea0ff';ctx.lineWidth=2;ctx.stroke();
  ctx.lineTo(w,h);ctx.lineTo(0,h);ctx.closePath();
  const grad=ctx.createLinearGradient(0,0,0,h);grad.addColorStop(0,'rgba(62,160,255,0.12)');grad.addColorStop(1,'rgba(62,160,255,0)');ctx.fillStyle=grad;ctx.fill();
  ctx.fillStyle='#e9f0ff';ctx.font='13px Inter, Arial';ctx.fillText('₦ '+fmtN(markets[activePair].last),8,18);
}

// orderbook
function renderOrderBook(){
  asksTable.innerHTML='';bidsTable.innerHTML='';const last=markets[activePair].last;
  for(let i=10;i>0;i--){const p=Math.round(last+i*(Math.random()*5+1)),s=(Math.random()*5).toFixed(4);const tr=document.createElement('tr');tr.innerHTML=`<td>₦ ${fmtN(p)}</td><td class="right">${s}</td>`;asksTable.appendChild(tr);}
  for(let i=10;i>0;i--){const p=Math.round(last-i*(Math.random()*5+1)),s=(Math.random()*5).toFixed(4);const tr=document.createElement('tr');tr.innerHTML=`<td>₦ ${fmtN(p)}</td><td class="right">${s}</td>`;bidsTable.appendChild(tr);}
}

// update
function updateUI(){
  const m=markets[activePair];
  pairNameEl.textContent=m.symbol;
  lastPriceEl.textContent='₦ '+fmtN(m.last);
  dayChangeEl.textContent=(m.changePct>=0?'+':'')+m.changePct+'%';
  dayChangeEl.className='change '+(m.changePct>=0?'up':'down');
  vol24El.textContent=m.vol24.toLocaleString();
  mktcapEl.textContent='₦ '+fmtN(m.mktcap);
  drawChart();renderOrderBook();renderOrders();renderTrades();
}

// orders/trades
function renderOrders(){openOrdersTable.innerHTML='';if(orders.length===0){openOrdersTable.innerHTML='<tr><td colspan="4" class="small muted">No open orders</td></tr>';return;}orders.forEach(o=>{const tr=document.createElement('tr');tr.innerHTML=`<td class="small">${o.time}</td><td>${o.side.toUpperCase()}</td><td>₦ ${fmtN(o.price)}</td><td>${o.amount}</td>`;openOrdersTable.appendChild(tr);});}
function renderTrades(){tradeHistoryTable.innerHTML='';if(trades.length===0){tradeHistoryTable.innerHTML='<tr><td colspan="4" class="small muted">No trades yet</td></tr>';return;}trades.slice().reverse().forEach(t=>{const tr=document.createElement('tr');tr.innerHTML=`<td class="small">${t.time}</td><td>${t.side.toUpperCase()}</td><td>₦ ${fmtN(t.price)}</td><td>${t.amount}</td>`;tradeHistoryTable.appendChild(tr);});}

// place order
placeOrderBtn.onclick=()=>{
  const type=orderTypeEl.value,side=orderSideEl.value,amt=parseFloat(orderAmountEl.value||0),price=parseFloat(orderPriceEl.value||markets[activePair].last);
  if(!amt||amt<=0){alert('Enter a valid amount');return;}
  const nowStr=new Date().toLocaleTimeString();
  if(type==='market'){trades.push({time:nowStr,side,price:markets[activePair].last,amount:amt});alert('Market order executed at ₦ '+fmtN(markets[activePair].last));}
  else{orders.push({symbol:activePair,time:nowStr,side,price,amount:amt});alert('Limit order placed');}
  renderOrders();renderTrades();
}

// simulation tick
function simulateTick(){pairs.forEach(sym=>{const m=markets[sym];const vol=Math.random()*0.5-0.25;m.last=Math.max(0,(m.last*(1+vol/100)));m.prices.push(m.last);if(m.prices.length>300)m.prices.shift();m.changePct=Math.round(vol*10000)/100;m.vol24=Math.floor(m.vol24*(1+vol));});
for(let i=orders.length-1;i>=0;i--){const o=orders[i],m=markets[o.symbol];if((o.side==='buy'&&m.last<=o.price)||(o.side==='sell'&&m.last>=o.price)){trades.push({time:new Date().toLocaleTimeString(),side:o.side,price:o.price,amount:o.amount});orders.splice(i,1);}}
updateUI();renderMarketList();renderOrders();renderTrades();}
renderMarketList();updateUI();simulateTick();
setInterval(simulateTick,1400);
window.addEventListener('resize',()=>drawChart());
document.querySelectorAll('.subnav button').forEach(btn=>{btn.onclick=()=>{document.querySelectorAll('.subnav button').forEach(b=>b.classList.remove('active'));btn.classList.add('active');const sub=btn.getAttribute('data-sub');const signalBox=document.getElementById('signalBox');if(sub==='signal')signalBox.textContent='No active trading signals in demo.';if(sub==='team')signalBox.textContent='Team assets overview is shown to the right.';if(sub==='account')signalBox.textContent='Account total: ₦0 — demo balance.';}});
</script>
</body>
</html>
