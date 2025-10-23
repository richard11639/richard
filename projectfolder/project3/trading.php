<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Richard Advanced Trading UI</title>
<style>
:root{
  --bg:#0a1026; --card:#101a34; --accent:#3ea0ff; --up:#16c784; --down:#ff6b6b;
  --muted:#9fb3d1; --glass:rgba(255,255,255,0.05); --radius:12px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:Inter,sans-serif;}
body{background:var(--bg);color:#e9f0ff;}
.container{max-width:1400px;margin:12px auto;padding:12px;}
.navbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;}
.navbar button{background:transparent;border:1px solid var(--glass);border-radius:8px;padding:8px 12px;color:var(--muted);font-weight:700;cursor:pointer;}
.navbar button:hover{background:rgba(62,160,255,0.1);}
.main-grid{display:grid;grid-template-columns:320px 1fr;gap:12px;}
@media(max-width:980px){.main-grid{grid-template-columns:1fr;}}
.card{background:var(--card);padding:12px;border-radius:var(--radius);border:1px solid var(--glass);}
.market-list{display:flex;flex-direction:column;gap:6px;}
.market-item{display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:8px;cursor:pointer;border:1px solid transparent;}
.market-item.active{background:linear-gradient(90deg, rgba(62,160,255,0.12), rgba(141,224,255,0.04));border-color:rgba(62,160,255,0.2);}
.market-item:hover{background:rgba(255,255,255,0.02);}
.pair{font-weight:700;}
.price{font-weight:800;}
.change{padding:4px 8px;border-radius:6px;font-weight:700;font-size:.85rem;}
.change.up{background:rgba(22,199,132,0.15);color:var(--up);}
.change.down{background:rgba(255,107,107,0.15);color:var(--down);}
.chart-wrap{margin-top:12px;}
#chart{width:100%;height:350px;border-radius:10px;}
.controls{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;align-items:center;}
.controls input, .controls select{padding:6px;border-radius:6px;border:1px solid var(--glass);background:transparent;color:#fff;width:80px;}
.btn{padding:8px 14px;border:none;border-radius:8px;font-weight:700;cursor:pointer;}
.buy{background:linear-gradient(90deg,#00c77f,#009a59);color:#052016;}
.sell{background:linear-gradient(90deg,#ff6b6b,#b32b2b);color:#200808;}
.orderbook{display:flex;gap:10px;margin-top:12px;}
.book-side{flex:1;max-height:200px;overflow:auto;}
.table{width:100%;border-collapse:collapse;}
.table th,.table td{padding:6px;font-size:.85rem;border-bottom:1px dashed rgba(255,255,255,0.05);}
.subnav{display:flex;gap:8px;margin-top:12px;}
.subnav button{background:transparent;border:none;color:var(--muted);padding:6px 12px;border-radius:8px;cursor:pointer;font-weight:700;}
.subnav button.active{color:#fff;background:rgba(255,255,255,0.03);}
</style>
</head>
<body>
    <title>Richard Advanced Trading UI</title>
<div class="container">
  <div class="navbar">
    <button>Deposit</button><button>Withdraw</button><button>Swap</button>
    <button>Exchange</button><button>Services</button><button>Logout</button>
  </div>

  <div class="subnav">
    <button class="active" data-tab="signal">Signal</button>
    <button data-tab="team">Team Assets</button>
    <button data-tab="account">Account</button>
    <div style="margin-left:auto;">Balance: ₦3,000,000</div>
  </div>

  <div class="main-grid">
    <!-- Left market list -->
    <aside class="card">
      <strong>Markets</strong>
      <div class="market-list" id="marketList"></div>
    </aside>

    <!-- Right trading panel -->
    <main>
      <div class="card">
        <div id="pairDetails">
          <strong id="pairName">BTC/USDT</strong> | Price: <span id="lastPrice">—</span> | 24h Change: <span id="changePct">—</span>
        </div>
        <div class="chart-wrap">
          <canvas id="chart"></canvas>
        </div>
        <div class="controls">
          <select id="orderType"><option value="market">Market</option><option value="limit">Limit</option></select>
          <select id="orderSide"><option value="buy">Buy</option><option value="sell">Sell</option></select>
          <input id="orderPrice" placeholder="Price"/>
          <input id="orderAmount" placeholder="Amount"/>
          <button class="btn buy" id="placeOrder">Place Order</button>
        </div>

        <div class="orderbook">
          <div class="book-side card"><strong>Asks</strong><table class="table" id="asksTable"><tbody></tbody></table></div>
          <div class="book-side card"><strong>Bids</strong><table class="table" id="bidsTable"><tbody></tbody></table></div>
        </div>

        <div style="display:flex;gap:12px;margin-top:12px">
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
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Demo Market & State
const pairs=['BTC/USDT','ETH/USDT','DOGE/USDT','BCH/USDT','LTC/USDT','IOTA/USDT','FLOW/USDT','TRX/USDT','BNB/USDT','ETC/USDT','JST/USDT','DOT/USDT'];
const markets={};
pairs.forEach(p=>{
  const base=p.startsWith('BTC')?3500000:p.startsWith('ETH')?220000:50000;
  const price=Math.round(base*(1+(Math.random()-0.5)*0.2));
  markets[p]={last:price,changePct:(Math.random()-0.5)*5,vol24:Math.round(Math.random()*50000+1000),prices:Array.from({length:120},(_,k)=>Math.round(price*(1+(Math.random()-0.5)/20)))};
});
let activePair=pairs[0],orders=[],trades=[];

// DOM refs
const marketListEl=document.getElementById('marketList');
const pairNameEl=document.getElementById('pairName');
const lastPriceEl=document.getElementById('lastPrice');
const changePctEl=document.getElementById('changePct');
const chartCanvas=document.getElementById('chart');
const asksTable=document.getElementById('asksTable').querySelector('tbody');
const bidsTable=document.getElementById('bidsTable').querySelector('tbody');
const openOrdersTable=document.getElementById('openOrders').querySelector('tbody');
const tradeHistoryTable=document.getElementById('tradeHistory').querySelector('tbody');

// Chart.js
const ctx=chartCanvas.getContext('2d');
let chartData={labels:[],datasets:[{label:'Price',data:[],borderColor:'#3ea0ff',backgroundColor:'rgba(62,160,255,0.2)',fill:true}]};
let chartObj=new Chart(ctx,{type:'line',data:chartData,options:{responsive:true,plugins:{legend:{display:false}}}});

function renderMarketList(){
  marketListEl.innerHTML='';
  pairs.forEach(p=>{
    const m=markets[p];
    const div=document.createElement('div');
    div.className='market-item'+(p===activePair?' active':'');
    div.innerHTML=`<div class="pair">${p}</div><div class="price">₦ ${m.last.toLocaleString()}</div>`;
    div.onclick=()=>{activePair=p; updateUI(); renderMarketList();}
    marketListEl.appendChild(div);
  });
}

function updateUI(){
  const m=markets[activePair];
  pairNameEl.textContent=activePair;
  lastPriceEl.textContent='₦ '+m.last.toLocaleString();
  changePctEl.textContent=(m.changePct>=0?'+':'')+m.changePct.toFixed(2)+'%';
  changePctEl.className='change '+(m.changePct>=0?'up':'down');
  // update chart
  chartData.labels=m.prices.map((_,i)=>i);
  chartData.datasets[0].data=m.prices;
  chartObj.update();
  renderOrderBook();
  renderOrders(); renderTrades();
}

function renderOrderBook(){
  asksTable.innerHTML=''; bidsTable.innerHTML='';
  const last=markets[activePair].last;
  for(let i=10;i>0;i--){
    let p=Math.round(last+i*(Math.random()*1000+200)); asksTable.innerHTML+=`<tr><td>₦ ${p.toLocaleString()}</td><td class="right">${(Math.random()*5).toFixed(4)}</td></tr>`;
    p=Math.round(last-i*(Math.random()*1000+200)); bidsTable.innerHTML+=`<tr><td>₦ ${p.toLocaleString()}</td><td class="right">${(Math.random()*5).toFixed(4)}</td></tr>`;
  }
}

function renderOrders(){
  openOrdersTable.innerHTML='';
  if(!orders.length){openOrdersTable.innerHTML='<tr><td colspan="4" class="small muted">No open orders</td></tr>'; return;}
  orders.forEach(o=>{
    openOrdersTable.innerHTML+=`<tr><td>${o.time}</td><td>${o.side}</td><td>₦ ${o.price}</td><td>${o.amount}</td></tr>`;
  });
}

function renderTrades(){
  tradeHistoryTable.innerHTML='';
  if(!trades.length){tradeHistoryTable.innerHTML='<tr><td colspan="4" class="small muted">No trades</td></tr>'; return;}
  trades.slice().reverse().forEach(t=>{
    tradeHistoryTable.innerHTML+=`<tr><td>${t.time}</td><td>${t.side}</td><td>₦ ${t.price}</td><td>${t.amount}</td></tr>`;
  });
}

// place order demo
document.getElementById('placeOrder').onclick=()=>{
  const type=document.getElementById('orderType').value;
  const side=document.getElementById('orderSide').value;
  const price=parseFloat(document.getElementById('orderPrice').value||markets[activePair].last);
  const amt=parseFloat(document.getElementById('orderAmount').value||0);
  if(amt<=0){alert('Enter valid amount'); return;}
  const now=new Date().toLocaleTimeString();
  if(type==='market'){trades.push({time:now,side,price:markets[activePair].last,amount:amt});}
  else{orders.push({time:now,side,price,amount:amt});}
  renderOrders(); renderTrades();
}

// simulate ticks
setInterval(()=>{
  pairs.forEach(p=>{
    const m=markets[p];
    const change=(Math.random()-0.5)*0.01*m.last;
    m.last=Math.round(m.last+change);
    m.prices.push(m.last);
    if(m.prices.length>200) m.prices.shift();
    m.changePct=(Math.random()-0.5)*5;
  });
  updateUI();
},1200);

renderMarketList();
updateUI();
</script>
</body>
</html>

