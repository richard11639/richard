<?php
// index.php - Game UI (Canvas) + JS + CSS
// No server-side logic needed here other than serving the page.
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Star Defender — Shooting Game</title>
<style>
  :root{
    --bg1:#0b1221; --panel:#0f1724; --accent:#ff7b54; --muted:#9aa5b1;
  }
  html,body{height:100%;margin:0;font-family:Inter,system-ui,Arial,sans-serif;background:linear-gradient(180deg,#061020 0%, #071222 60%);color:#e6eef6}
  .wrap{max-width:1100px;margin:18px auto;padding:18px;display:grid;grid-template-columns:1fr 320px;gap:18px}
  header{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between}
  h1{margin:0;font-size:20px;color:var(--accent)}
  .controls{display:flex;gap:8px;align-items:center}
  .btn{background:var(--accent);color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:700}
  .btn.ghost{background:transparent;color:var(--accent);border:1px solid rgba(255,123,84,0.18)}
  .game-panel{background:rgba(255,255,255,0.02);border-radius:12px;padding:12px;display:flex;flex-direction:column;align-items:center}
  canvas{background:linear-gradient(180deg,#001f3f,#02283e);border-radius:8px;display:block;max-width:100%}
  .hud{display:flex;gap:12px;margin-top:8px;align-items:center}
  .hud .pill{background:rgba(255,255,255,0.03);padding:8px 10px;border-radius:999px;font-weight:700}
  aside.panel{background:#071224;border-radius:12px;padding:12px;color:var(--muted);height:fit-content}
  aside h3{margin:0 0 8px 0;color:var(--accent)}
  .leaderboard{display:flex;flex-direction:column;gap:8px}
  .score-row{display:flex;justify-content:space-between;padding:8px;border-radius:8px;background:rgba(255,255,255,0.02)}
  .center{display:flex;align-items:center;gap:8px}
  .small{font-size:13px;color:var(--muted)}
  .overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(1,2,5,0.6);z-index:999}
  .modal{background:#071122;padding:18px;border-radius:12px;min-width:320px;color:#e6eef6}
  input[type="text"]{padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:#fff;width:100%}
  @media(max-width:900px){ .wrap{grid-template-columns:1fr} aside.panel{order:2} }
</style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>Star Defender</h1>
    <div class="controls">
      <button id="btn-start" class="btn">Start</button>
      <button id="btn-pause" class="btn ghost">Pause</button>
      <button id="btn-reset" class="btn ghost">Reset</button>
    </div>
  </header>

  <section class="game-panel" aria-label="Game area">
    <canvas id="game" width="720" height="520" role="img" aria-label="Game canvas"></canvas>

    <div class="hud" aria-hidden="false">
      <div class="pill">Score: <span id="score">0</span></div>
      <div class="pill">Lives: <span id="lives">3</span></div>
      <div class="pill">Level: <span id="level">1</span></div>
    </div>

    <p class="small" style="margin-top:10px;text-align:center">
      Controls: Left/Right arrow or A/D to move • Space to shoot • Click Start to play
    </p>
  </section>

  <aside class="panel" aria-label="Leaderboard & info">
    <h3>Leaderboard (Top 10)</h3>
    <div id="leaderboard" class="leaderboard">
      <div class="small">Loading...</div>
    </div>

    <hr style="border:none;height:8px">

    <h3>How to play</h3>
    <p class="small">Defend your starship — shoot enemies, avoid collisions. Survive and get the highest score. After game over, save your score to the leaderboard.</p>

    <div style="margin-top:12px">
      <button id="btn-view" class="btn ghost" onclick="showHelp()">Help</button>
    </div>
  </aside>
</div>

<!-- Save score modal -->
<div id="overlay" class="overlay" role="dialog" aria-modal="true">
  <div class="modal">
    <h3>Game Over — Save your score?</h3>
    <p class="small">Your score: <strong id="finalScore">0</strong></p>
    <input id="playerName" type="text" maxlength="24" placeholder="Enter name (max 24 chars)">
    <div style="display:flex;gap:8px;margin-top:10px;justify-content:flex-end">
      <button class="btn" id="saveScoreBtn">Save</button>
      <button class="btn ghost" onclick="closeModal()">Close</button>
    </div>
    <p id="saveMessage" class="small" style="margin-top:8px"></p>
  </div>
</div>

<script>
/*
  Star Defender - simple shooting game
  - Player moves horizontally at bottom
  - Shoot bullets (space) to destroy descending enemies
  - Score increases per enemy
  - On game over, show modal to save score (server-side)
  - Uses fetch to /save_score.php and /highscores.php
*/

// Canvas setup
const canvas = document.getElementById('game');
const ctx = canvas.getContext('2d');
let W = canvas.width, H = canvas.height;

// Game state
let running = false, paused = false;
let score = 0, lives = 3, level = 1;
let bullets = [], enemies = [], particles = [];
let keys = {};
let player = { x: W/2, y: H-60, w: 48, h: 24, speed: 6, cooldown: 0 };

const cfg = {
  enemySpawnRate: 900, // ms initial
  enemySpeedMin: 1.0,
  enemySpeedMax: 2.5
};

let lastSpawn = 0, lastTime = 0, spawnInterval = cfg.enemySpawnRate;
let updateTimer = null;

// helpers
function rand(min,max){ return Math.random()*(max-min)+min; }
function clamp(v,a,b){ return Math.max(a, Math.min(b,v)); }

// input
window.addEventListener('keydown', e=>keys[e.key.toLowerCase()]=true);
window.addEventListener('keyup', e=>keys[e.key.toLowerCase()]=false);
document.getElementById('btn-start').addEventListener('click',()=> startGame());
document.getElementById('btn-pause').addEventListener('click',()=> togglePause());
document.getElementById('btn-reset').addEventListener('click',()=> resetGame());

// resize handling (optional)
window.addEventListener('resize', ()=> { /* keep canvas fixed for simplicity */ });

// game functions
function startGame(){
  if (running) return;
  resetState();
  running = true;
  paused = false;
  lastTime = performance.now();
  spawnInterval = cfg.enemySpawnRate;
  loop(lastTime);
  fetchLeaderboard();
}
function togglePause(){
  if (!running) return;
  paused = !paused;
  document.getElementById('btn-pause').textContent = paused ? 'Resume' : 'Pause';
  if (!paused) { lastTime = performance.now(); loop(lastTime); }
}
function resetGame(){ running=false; paused=false; resetState(); draw(); fetchLeaderboard(); }

function resetState(){
  score = 0; lives = 3; level = 1;
  bullets = []; enemies = []; particles = [];
  player.x = W/2;
  player.cooldown = 0;
  document.getElementById('score').textContent = score;
  document.getElementById('lives').textContent = lives;
  document.getElementById('level').textContent = level;
}

// spawn enemy
function spawnEnemy(){
  const size = rand(22,48);
  const ex = rand(size, W-size);
  const speed = rand(cfg.enemySpeedMin + (level-1)*0.2, cfg.enemySpeedMax + (level-1)*0.4);
  enemies.push({ x: ex, y: -size, w: size, h: size, speed: speed, hp: Math.ceil(size/18) });
}

// loop
function loop(timestamp){
  if (!running || paused) return;
  const dt = timestamp - lastTime;
  lastTime = timestamp;

  // spawn logic
  lastSpawn += dt;
  if (lastSpawn > spawnInterval) {
    spawnEnemy();
    lastSpawn = 0;
    // speed up spawn over time
    spawnInterval = Math.max(250, spawnInterval * 0.995);
  }

  // update player
  if (keys['arrowleft'] || keys['a']) player.x -= player.speed;
  if (keys['arrowright'] || keys['d']) player.x += player.speed;
  player.x = clamp(player.x, player.w/2, W - player.w/2);

  // shooting
  if ((keys[' '] || keys['space']) && player.cooldown <= 0) {
    bullets.push({ x: player.x, y: player.y-12, r: 5, speed: 9 });
    player.cooldown = 14; // frames cooldown
  }
  if (player.cooldown > 0) player.cooldown -= 1;

  // update bullets
  for (let i = bullets.length-1; i >=0; i--){
    bullets[i].y -= bullets[i].speed;
    if (bullets[i].y < -10) bullets.splice(i,1);
  }

  // update enemies
  for (let i = enemies.length-1; i >=0; i--){
    enemies[i].y += enemies[i].speed;
    // collision with player (enemy hits bottom)
    if (enemies[i].y > H - 40) {
      // damage
      enemies.splice(i,1);
      createParticles(player.x, player.y, 18, '#ff7b54');
      lives -= 1;
      document.getElementById('lives').textContent = lives;
      if (lives <= 0) { gameOver(); return; }
      continue;
    }
  }

  // bullets vs enemies collisions
  for (let b = bullets.length-1; b >= 0; b--){
    const bb = bullets[b];
    for (let e = enemies.length-1; e >= 0; e--){
      const en = enemies[e];
      if (dist(bb.x, bb.y, en.x, en.y) < (en.w/2 + bb.r)) {
        // hit
        bullets.splice(b,1);
        en.hp -= 1;
        createParticles(bb.x, bb.y, 8, '#ffd27f');
        if (en.hp <= 0) {
          // enemy destroyed
          score += Math.round(10 + en.w);
          createParticles(en.x, en.y, 16, '#7ee7ff');
          enemies.splice(e,1);
          // level up by score thresholds
          if (score > 0 && score % 250 === 0) {
            level += 1;
            document.getElementById('level').textContent = level;
          }
        }
        document.getElementById('score').textContent = score;
        break;
      }
    }
  }

  // update particles
  for (let p = particles.length-1; p>=0; p--){
    particles[p].x += particles[p].vx;
    particles[p].y += particles[p].vy;
    particles[p].life -= 1;
    particles[p].vy += 0.12; // gravity
    if (particles[p].life <= 0) particles.splice(p,1);
  }

  render();
  requestAnimationFrame(loop);
}

function	dist(x1,y1,x2,y2){ return Math.hypot(x1-x2,y1-y2); }

function createParticles(x,y,count,color){
  for (let i=0;i<count;i++){
    particles.push({
      x: x + rand(-6,6),
      y: y + rand(-6,6),
      vx: rand(-2,2),
      vy: rand(-3,1),
      life: Math.floor(rand(14,34)),
      size: rand(1.5,3.5),
      color: color
    });
  }
}

function render(){
  // clear
  ctx.clearRect(0,0,W,H);

  // stars background
  drawStars();

  // player
  drawShip(player.x, player.y, player.w, player.h);

  // bullets
  bullets.forEach(b=>{
    ctx.beginPath();
    ctx.fillStyle = '#fff9';
    ctx.arc(b.x, b.y, b.r, 0, Math.PI*2); ctx.fill();
  });

  // enemies
  enemies.forEach(en=>{
    ctx.save();
    ctx.translate(en.x, en.y);
    // simple enemy shape - rounded square
    ctx.fillStyle = '#ff9f89';
    roundRect(ctx, -en.w/2, -en.h/2, en.w, en.h, 6);
    ctx.fill();
    ctx.restore();
  });

  // particles
  particles.forEach(p=>{
    ctx.fillStyle = p.color;
    ctx.fillRect(p.x, p.y, p.size, p.size);
  });

  // HUD drawn on canvas (optional)
}

// small helpers for drawing
function drawStars(){
  // subtle static stars (for effect draw on each render)
  ctx.fillStyle = '#041424';
  ctx.fillRect(0,0,W,H);
  for (let i=0;i<60;i++){
    const x = (i*37) % W;
    const y = (i*73) % H;
    ctx.fillStyle = 'rgba(255,255,255,'+ (0.06 + (i%5)/40) +')';
    ctx.fillRect((x + i*3*Math.sin(Date.now()/1000+i))%W, y, 1,1);
  }
}

function drawShip(x,y,w,h){
  ctx.save();
  ctx.translate(x,y);
  // body
  ctx.fillStyle = '#7ee7ff';
  ctx.beginPath();
  ctx.moveTo(-w/2, h/2);
  ctx.lineTo(0,-h/2);
  ctx.lineTo(w/2, h/2);
  ctx.closePath();
  ctx.fill();
  // cockpit
  ctx.fillStyle = '#072a33';
  ctx.beginPath();
  ctx.ellipse(0, -2, w*0.22, h*0.2, 0, 0, Math.PI*2);
  ctx.fill();
  ctx.restore();
}

function roundRect(ctx, x, y, w, h, r){
  ctx.beginPath();
  ctx.moveTo(x+r, y);
  ctx.arcTo(x+w, y, x+w, y+h, r);
  ctx.arcTo(x+w, y+h, x, y+h, r);
  ctx.arcTo(x, y+h, x, y, r);
  ctx.arcTo(x, y, x+w, y, r);
  ctx.closePath();
}

// Game over
function gameOver(){
  running = false;
  // show modal to save
  document.getElementById('finalScore').textContent = score;
  openModal();
  fetchLeaderboard();
}

// Modal functions
function openModal(){ document.getElementById('overlay').style.display = 'flex'; document.getElementById('playerName').focus(); }
function closeModal(){ document.getElementById('overlay').style.display = 'none'; document.getElementById('saveMessage').textContent = ''; }

// Save score button
document.getElementById('saveScoreBtn').addEventListener('click', async ()=>{
  const name = document.getElementById('playerName').value.trim().substring(0,24) || 'Anonymous';
  const s = score;
  document.getElementById('saveMessage').textContent = 'Saving...';
  try {
    const res = await fetch('save_score.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({name:name, score:s})
    });
    const j = await res.json();
    if (j.ok) {
      document.getElementById('saveMessage').textContent = 'Saved! Refreshing leaderboard...';
      fetchLeaderboard();
    } else {
      document.getElementById('saveMessage').textContent = 'Save failed: ' + (j.error || 'unknown');
    }
  } catch(err){
    document.getElementById('saveMessage').textContent = 'Network error';
  }
});

// leaderboard fetch
async function fetchLeaderboard(){
  const el = document.getElementById('leaderboard');
  el.innerHTML = '<div class="small">Loading...</div>';
  try {
    const res = await fetch('highscores.php');
    const data = await res.json();
    if (!Array.isArray(data)) { el.innerHTML = '<div class="small">No data</div>'; return; }
    el.innerHTML = '';
    data.forEach((r, idx)=>{
      const row = document.createElement('div');
      row.className = 'score-row';
      row.innerHTML = '<div class="center"><strong style="margin-right:10px">'+(idx+1)+'.</strong><div><div>'+escapeHtml(r.name)+'</div><div class="small">'+r.when+'</div></div></div><div>★ '+r.score+'</div>';
      el.appendChild(row);
    });
  } catch(e){
    el.innerHTML = '<div class="small">Failed to load</div>';
  }
}

function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]); }

window.onload = ()=> {
  draw();
  fetchLeaderboard();
};

// simple draw frame when not running
function draw(){
  ctx.clearRect(0,0,W,H);
  drawStars();
  drawShip(player.x, player.y, player.w, player.h);
  // draw instructions
  ctx.fillStyle = 'rgba(255,255,255,0.06)';
  ctx.fillRect(18,18,260,86);
  ctx.fillStyle = '#dbeefd';
  ctx.font = '14px Inter, Arial';
  ctx.fillText('Press Start to begin. Survive as long as you can!', 30, 42);
  ctx.font = '12px Inter, Arial';
  ctx.fillStyle = '#a3bed6';
  ctx.fillText('Move: ← → or A/D  •  Shoot: Space', 30, 68);
}
</script>
</body>
</html>
