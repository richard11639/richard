<?php
/**
 * placetrade.php — demo trading page (single-file, no login)
 * DEMO: uses a single demo user and wallet DB.
 */

/* ========== CONFIG ========== */
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'trade2_db';
date_default_timezone_set('Africa/Lagos');

/* ========== Demo user (no login) ========= */
$DEMO_USERNAME = 'demo';
$DEMO_EMAIL = 'demo@example.com';

/* ========== CONNECT + CREATE DB ========== */
try {
    $dsnNoDb = "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4";
    $pdo0 = new PDO($dsnNoDb, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) { die("DB server connection failed: ".htmlspecialchars($e->getMessage())); }

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) { die("DB connect failed: ".htmlspecialchars($e->getMessage())); }

/* ========== CREATE TABLES (idempotent) ========== */
$create = [
"CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(12) NOT NULL,
  balance DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  hold_amount DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_currency (user_id, currency),
  CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  currency VARCHAR(12) NOT NULL,
  type VARCHAR(50) NOT NULL,
  amount DECIMAL(28,8) NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS markets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  symbol VARCHAR(64) NOT NULL UNIQUE,
  base_currency VARCHAR(12) NOT NULL,
  quote_currency VARCHAR(12) NOT NULL,
  tick_size DECIMAL(28,8) DEFAULT 0.01,
  min_size DECIMAL(28,8) DEFAULT 0.00000001,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  market_id INT NOT NULL,
  side ENUM('buy','sell') NOT NULL,
  type ENUM('limit','market') NOT NULL DEFAULT 'limit',
  price DECIMAL(28,8) NULL,
  size DECIMAL(28,8) NOT NULL,
  filled DECIMAL(28,8) NOT NULL DEFAULT 0.00000000,
  status ENUM('open','partial','filled','cancelled') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_market FOREIGN KEY (market_id) REFERENCES markets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS trades (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  market_id INT NOT NULL,
  buy_order_id BIGINT,
  sell_order_id BIGINT,
  price DECIMAL(28,8) NOT NULL,
  size DECIMAL(28,8) NOT NULL,
  buy_user_id INT,
  sell_user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_trades_market FOREIGN KEY (market_id) REFERENCES markets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($create as $sql) { $pdo->exec($sql); }

/* ========== SEED MARKETS ========= */
$marketSymbols = [
  'BTC/USDT','ETH/USDT','DOGE/USDT','BCH/USDT','LTC/USDT','IOTA/USDT','FLOW/USDT','TRX/USDT','BNB/USDT','ETC/USDT','JST/USDT','DOT/USDT',
  'BTC/NGN','USDT/NGN'
];
$msStmt = $pdo->prepare("SELECT COUNT(*) FROM markets WHERE symbol = ?");
$insMarket = $pdo->prepare("INSERT INTO markets (symbol, base_currency, quote_currency, tick_size, min_size) VALUES (?, ?, ?, ?, ?)");
foreach ($marketSymbols as $s) {
    $parts = explode('/', $s);
    $base = $parts[0];
    $quote = $parts[1] ?? 'USDT';
    $msStmt->execute([$s]);
    if ($msStmt->fetchColumn() == 0) {
        $insMarket->execute([$s,$base,$quote,0.01,0.00001]);
    }
}

/* ========== CREATE/LOAD DEMO USER (no auth) ========= */
$uCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$uCheck->execute([$DEMO_USERNAME, $DEMO_EMAIL]);
$demo = $uCheck->fetch();
if (!$demo) {
    $pdo->prepare("INSERT INTO users (username,email,role) VALUES (?, ?, 'user')")->execute([$DEMO_USERNAME, $DEMO_EMAIL]);
    $demo_id = $pdo->lastInsertId();
    // seed demo wallets with starting balances (do not overwrite on existing installs)
    $seedW = $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, ?, ?, 0)");
    $seedW->execute([$demo_id, 'NGN', 100000.00]);   // ₦100k starting
    $seedW->execute([$demo_id, 'USDT', 1000.00]);    // 1000 USDT
    $seedW->execute([$demo_id, 'BTC', 0.05]);        // 0.05 BTC
} else {
    $demo_id = (int)$demo['id'];
    $ew = $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance,hold_amount) VALUES (?, ?, ?, 0)");
    $ew->execute([$demo_id, 'NGN', 100000.00]);
    $ew->execute([$demo_id, 'USDT', 1000.00]);
    $ew->execute([$demo_id, 'BTC', 0.05]);
}

/* ========== HELPERS ========== */
function money_fmt($a) { return number_format((float)$a, 2, '.', ','); }
function ensure_wallet(PDO $pdo, $uid, $currency) {
    $ins = $pdo->prepare("INSERT IGNORE INTO wallets (user_id, currency, balance, hold_amount) VALUES (?, ?, 0, 0)");
    $ins->execute([$uid, $currency]);
}
function get_market($pdo, $market_id) {
    $s = $pdo->prepare("SELECT * FROM markets WHERE id = ?");
    $s->execute([$market_id]); return $s->fetch();
}

/* ========== MATCHING ENGINE (transactional + correct settlement) ========== */
function match_orders(PDO $pdo, $market_id) {
    $maker_fee_rate = 0.0;
    $taker_fee_rate = 0.0;
    $EPS = 1e-12;

    while (true) {
        try {
            $pdo->beginTransaction();

            // lock best buy & sell
            $buyStmt = $pdo->prepare("SELECT * FROM orders WHERE market_id = ? AND side='buy' AND status IN ('open','partial') AND price IS NOT NULL ORDER BY price DESC, created_at ASC LIMIT 1 FOR UPDATE");
            $sellStmt = $pdo->prepare("SELECT * FROM orders WHERE market_id = ? AND side='sell' AND status IN ('open','partial') AND price IS NOT NULL ORDER BY price ASC, created_at ASC LIMIT 1 FOR UPDATE");
            $buyStmt->execute([$market_id]); $sellStmt->execute([$market_id]);
            $buy = $buyStmt->fetch(); $sell = $sellStmt->fetch();

            if (!$buy || !$sell) { $pdo->rollBack(); break; }
            if ((float)$buy['price'] < (float)$sell['price']) { $pdo->rollBack(); break; }

            $trade_price = (float)$sell['price']; // take seller price
            $buy_rem = (float)$buy['size'] - (float)$buy['filled'];
            $sell_rem = (float)$sell['size'] - (float)$sell['filled'];
            $trade_size = min($buy_rem, $sell_rem);
            if ($trade_size <= 0) { $pdo->rollBack(); break; }

            $cost = $trade_price * $trade_size;
            $buyer_id = (int)$buy['user_id'];
            $seller_id = (int)$sell['user_id'];

            $m = get_market($pdo,$market_id);
            $base = $m['base_currency'];
            $quote = $m['quote_currency'];

            // lock wallet rows (FOR UPDATE)
            $wbq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wsb = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wbq->execute([$buyer_id, $quote]); $buyerQuote = $wbq->fetch();
            $wsb->execute([$seller_id, $base]); $sellerBase = $wsb->fetch();

            if (!$buyerQuote || !$sellerBase) { $pdo->rollBack(); break; }

            // verify holds: buyer's hold_amount must be >= cost, seller's hold_amount >= trade_size
            if ((float)$buyerQuote['hold_amount'] + $EPS < $cost) { $pdo->rollBack(); break; }
            if ((float)$sellerBase['hold_amount'] + $EPS < $trade_size) { $pdo->rollBack(); break; }

            // insert trade
            $ins = $pdo->prepare("INSERT INTO trades (market_id, buy_order_id, sell_order_id, price, size, buy_user_id, sell_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$market_id, $buy['id'], $sell['id'], $trade_price, $trade_size, $buyer_id, $seller_id]);
            $trade_id = $pdo->lastInsertId();

            // update orders filled & status
            $upd = $pdo->prepare("UPDATE orders SET filled = filled + ?, status = CASE WHEN filled + ? >= size THEN 'filled' ELSE 'partial' END WHERE id = ?");
            $upd->execute([$trade_size, $trade_size, $buy['id']]);
            $upd->execute([$trade_size, $trade_size, $sell['id']]);

            // SETTLEMENT (atomic inside transaction)
            // Buyer: reduce hold_amount AND deduct cost from balance (quote wallet), credit base
            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance - ?, update_at = NOW() WHERE id = ?")
                ->execute([$cost, $cost, $buyerQuote['id']]);
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")
                ->execute([$trade_size, $buyer_id, $base]);

            // buyer transactions
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_buy', ?, ?)")
                ->execute([$buyer_id, $base, $trade_size, json_encode(['trade_id'=>$trade_id,'price'=>$trade_price])]);
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_cost', ?, ?)")
                ->execute([$buyer_id, $quote, -$cost, json_encode(['trade_id'=>$trade_id])]);

            // Seller: reduce hold_amount AND deduct base from balance, credit quote proceeds
            $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount - ?, balance = balance - ?, update_at = NOW() WHERE id = ?")
                ->execute([$trade_size, $trade_size, $sellerBase['id']]);
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?")
                ->execute([$cost, $seller_id, $quote]);

            // seller transactions
            $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'trade_sell', ?, ?)")
                ->execute([$seller_id, $quote, $cost, json_encode(['trade_id'=>$trade_id,'price'=>$trade_price])]);

            $pdo->commit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // stop matching on errors in demo
            break;
        }
    }
}

/* ========== HANDLE PLACE ORDER (demo user) ========== */
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $market_id = (int)($_POST['market_id'] ?? 0);
    $side = $_POST['side'] ?? 'buy';
    $type = $_POST['type'] ?? 'limit';
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $size = (float)($_POST['size'] ?? 0);

    // basic validations
    if ($market_id <= 0) { $flash = 'Select a market'; }
    elseif ($size <= 0) { $flash = 'Enter a size > 0'; }
    else {
        $market = get_market($pdo,$market_id);
        if (!$market) $flash = 'Market not found';
        else {
            $base = $market['base_currency'];
            $quote = $market['quote_currency'];

            // minimum trade cost >= 1 (quote currency)
            if (!$price) $flash = 'Price required for orders';
            else {
                $cost = $price * $size;
                if ($cost < 1.0) $flash = 'Minimum trade cost is 1 (quote currency). Increase size or price.';
            }

            if (!$flash) {
                try {
                    $pdo->beginTransaction();

                    // ensure wallets exist
                    ensure_wallet($pdo, $demo_id, $base);
                    ensure_wallet($pdo, $demo_id, $quote);

                    if ($side === 'buy') {
                        $reserve = $cost; // demo: reserve exact cost
                        $wq = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                        $wq->execute([$demo_id,$quote]); $wqr = $wq->fetch();
                        if (!$wqr) { $pdo->rollBack(); $flash = 'Wallet error'; }
                        else {
                            $available = (float)$wqr['balance'] - (float)$wqr['hold_amount'];
                            if ($available + 1e-12 < $reserve) {
                                $pdo->rollBack();
                                $flash = 'Insufficient available balance. <a href="deposit.php">Make a deposit</a>';
                            } else {
                                // increase hold_amount (reserve funds). Do NOT deduct balance yet.
                                $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")
                                    ->execute([$reserve, $wqr['id']]);
                                // reserve transaction -- we keep amount = 0 to avoid double-counting balances (settlement will write the real debit)
                                $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'reserve', ?, ?)")
                                    ->execute([$demo_id, $quote, 0.00000000, json_encode(['market'=>$market['symbol'],'side'=>'buy','price'=>$price,'size'=>$size])]);
                            }
                        }
                    } else { // sell
                        $wb = $pdo->prepare("SELECT id, balance, hold_amount FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
                        $wb->execute([$demo_id,$base]); $wbr = $wb->fetch();
                        if (!$wbr) { $pdo->rollBack(); $flash = 'Wallet error'; }
                        else {
                            $available = (float)$wbr['balance'] - (float)$wbr['hold_amount'];
                            if ($available + 1e-12 < $size) {
                                $pdo->rollBack();
                                $flash = 'Insufficient available balance to place sell order. <a href="deposit.php">Make a deposit</a>';
                            } else {
                                $pdo->prepare("UPDATE wallets SET hold_amount = hold_amount + ?, update_at = NOW() WHERE id = ?")
                                    ->execute([$size, $wbr['id']]);
                                $pdo->prepare("INSERT INTO transactions (user_id, currency, type, amount, meta) VALUES (?, ?, 'reserve', ?, ?)")
                                    ->execute([$demo_id, $base, 0.00000000, json_encode(['market'=>$market['symbol'],'side'=>'sell','price'=>$price,'size'=>$size])]);
                            }
                        }
                    }

                    if (!$flash) {
                        // insert order and commit
                        $ins = $pdo->prepare("INSERT INTO orders (user_id, market_id, side, type, price, size) VALUES (?, ?, ?, ?, ?, ?)");
                        $ins->execute([$demo_id, $market_id, $side, $type, $price, $size]);
                        $order_id = $pdo->lastInsertId();
                        $pdo->commit();

                        // attempt matching (settlement happens inside)
                        match_orders($pdo, $market_id);

                        $flash = 'Order placed';
                    }
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $flash = 'Order error: '.htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

/* ========== Data for UI ========= */
$markets = $pdo->query("SELECT * FROM markets WHERE is_active = 1 ORDER BY id ASC")->fetchAll();
$user_wallets = $pdo->prepare("SELECT currency, balance, hold_amount FROM wallets WHERE user_id = ?");
$user_wallets->execute([$demo_id]); $user_wallets = $user_wallets->fetchAll();
$openOrders = $pdo->prepare("SELECT o.*, m.symbol FROM orders o JOIN markets m ON o.market_id = m.id WHERE o.user_id = ? AND o.status IN ('open','partial') ORDER BY o.created_at DESC");
$openOrders->execute([$demo_id]); $openOrders = $openOrders->fetchAll();
$recentTrades = $pdo->query("SELECT t.*, m.symbol FROM trades t JOIN markets m ON t.market_id = m.id ORDER BY t.created_at DESC LIMIT 50")->fetchAll();
$txs = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"); $txs->execute([$demo_id]); $txs = $txs->fetchAll();

/* ========== RENDER PAGE ========= */
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PlaceTrade — demo (no auth)</title>
<style>
:root{--bg:#f6f8fb;--card:#fff;--accent:#2563eb}
body{font-family:Inter,system-ui,Arial; margin:0; background:var(--bg); color:#0f172a}
.header{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;background:#0b1220;color:#fff}
.container{max-width:1200px;margin:18px auto;padding:12px}
.grid{display:grid;grid-template-columns:360px 1fr;gap:18px}
.card{background:var(--card);padding:14px;border-radius:10px;box-shadow:0 6px 20px rgba(2,6,23,0.06)}
.table{width:100%;border-collapse:collapse}
.table td,.table th{padding:8px;border-bottom:1px solid #eef2f7;font-size:14px}
.btn{padding:8px 12px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}
.input,select{padding:8px;border-radius:8px;border:1px solid #e6edf3;width:100%;margin-top:6px;margin-bottom:8px}
.small{font-size:13px;color:#64748b}
.notice{background:#fffbe6;padding:8px;border-left:4px solid #ffecb5;border-radius:6px;margin-bottom:10px}
.market-item{padding:8px;border-radius:8px;border:1px solid #eef2f7;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;cursor:pointer}
.market-item.active{background:#f0f9ff;border-color:#cfeeff}
.canvas-box{height:160px;border:1px solid #eef2f7;border-radius:8px;background:#fff;padding:8px}
</style>
</head>
<body>
  <header class="header">
    <div style="font-weight:700">PlaceTrade Demo (no login)</div>
    <div class="small">Demo account used automatically — start trading from ₦1</div>
    <a href="index.php" style="color:#fff">Dashboard</a>
  </header>

  <main class="container">
    <?php if($flash): ?><div class="notice"><?= $flash ?></div><?php endif; ?>

    <div class="grid">
      <aside class="card">
        <h3>Markets</h3>
        <div id="marketList">
          <?php foreach($markets as $m): ?>
            <div class="market-item" data-id="<?=intval($m['id'])?>" data-symbol="<?=htmlspecialchars($m['symbol'])?>">
              <div><?=htmlspecialchars($m['symbol'])?></div>
              <div class="small"><?=htmlspecialchars($m['base_currency'])?>/<?=htmlspecialchars($m['quote_currency'])?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:12px">
          <h4>Your wallets</h4>
          <table class="table">
            <tr><th>Currency</th><th>Balance</th><th>On hold</th><th>Available</th></tr>
            <?php foreach($user_wallets as $w): $avail = (float)$w['balance'] - (float)$w['hold_amount']; ?>
              <tr>
                <td><?=htmlspecialchars($w['currency'])?></td>
                <td><?=money_fmt($w['balance'])?></td>
                <td><?=money_fmt($w['hold_amount'])?></td>
                <td><?=money_fmt($avail)?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>

        <div style="margin-top:12px">
          <h4>Recent transactions</h4>
          <div style="max-height:220px;overflow:auto">
            <?php foreach($txs as $t): ?>
              <div style="padding:6px;border-bottom:1px dashed #eee">
                <div class="small"><strong><?=htmlspecialchars($t['currency'])?> <?=($t['amount']>=0?'+':'')?><?=number_format($t['amount'],8)?></strong></div>
                <div class="small"><?=htmlspecialchars($t['type'])?> — <?=htmlspecialchars($t['created_at'])?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>

      <section class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <h2 id="activeSymbol">Select market</h2>
          <div class="small">Open orders: <?=count($openOrders)?></div>
        </div>

        <div style="margin-top:10px" class="canvas-box">
          <canvas id="chartCanvas" style="width:100%;height:140px;"></canvas>
        </div>

        <div style="display:flex;gap:12px;margin-top:12px">
          <div style="flex:1">
            <form method="post">
              <input type="hidden" name="action" value="place_order">
              <input type="hidden" name="market_id" id="market_id" value="">
              <label class="small">Side
                <select class="input" name="side">
                  <option value="buy">Buy</option>
                  <option value="sell">Sell</option>
                </select>
              </label>
              <label class="small">Price (quote) <input class="input" name="price" id="priceField" type="number" step="0.0001" required></label>
              <label class="small">Size (base) <input class="input" name="size" id="sizeField" type="number" step="0.00000001" required></label>
              <div style="display:flex;gap:8px">
                <button class="btn" type="submit">Place order</button>
                <a class="btn" style="background:#10b981;color:#fff;text-decoration:none" href="deposit.php">Deposit</a>
              </div>
              <div class="small" style="margin-top:8px">If available balance is insufficient you'll be prompted to deposit. Minimum trade cost is 1 unit of quote currency (e.g. ₦1).</div>
            </form>
          </div>

          <div style="width:360px">
            <h4>Orderbook (snapshot)</h4>
            <div style="display:flex;gap:8px">
              <div style="flex:1;border:1px solid #eef2f7;padding:8px;border-radius:8px;background:#fff">
                <strong>Asks</strong>
                <div id="asksList" style="margin-top:8px"></div>
              </div>
              <div style="flex:1;border:1px solid #eef2f7;padding:8px;border-radius:8px;background:#fff">
                <strong>Bids</strong>
                <div id="bidsList" style="margin-top:8px"></div>
              </div>
            </div>
          </div>
        </div>

        <div style="margin-top:12px">
          <h4>Your open orders</h4>
          <table class="table">
            <tr><th>Time</th><th>Market</th><th>Side</th><th>Price</th><th>Remaining</th><th>Status</th></tr>
            <?php foreach($openOrders as $o): ?>
              <tr>
                <td class="small"><?=htmlspecialchars($o['created_at'])?></td>
                <td><?=htmlspecialchars($o['symbol'])?></td>
                <td><?=htmlspecialchars($o['side'])?></td>
                <td><?=money_fmt($o['price'])?></td>
                <td><?=number_format($o['size'] - $o['filled'],8)?></td>
                <td><?=htmlspecialchars($o['status'])?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>

        <div style="margin-top:12px">
          <h4>Recent trades</h4>
          <table class="table">
            <tr><th>Time</th><th>Market</th><th>Price</th><th>Size</th></tr>
            <?php foreach($recentTrades as $t): ?>
              <tr><td class="small"><?=htmlspecialchars($t['created_at'])?></td><td><?=htmlspecialchars($t['symbol'])?></td><td><?=money_fmt($t['price'])?></td><td><?=number_format($t['size'],8)?></td></tr>
            <?php endforeach; ?>
          </table>
        </div>

      </section>
    </div>
  </main>

  <script>
    // client-side market simulator & UI interactions
    const markets = <?php echo json_encode(array_map(function($m){return ['id'=>$m['id'],'symbol'=>$m['symbol'],'base'=>$m['base_currency'],'quote'=>$m['quote_currency']];}, $markets)); ?>;
    const data = {};
    markets.forEach(m=>{
      const base = m.symbol.startsWith('BTC')? 3500000 : (m.symbol.startsWith('ETH')? 220000 : 50000);
      const start = Math.round(base * (1 + (Math.random()-0.5)*0.2));
      data[m.symbol] = { prices: Array.from({length:80}, ()=>start), last: start };
    });

    const marketItems = document.querySelectorAll('.market-item');
    let activeSymbol = markets.length ? markets[0].symbol : null;
    let activeMarketId = markets.length ? markets[0].id : null;
    marketItems.forEach((el,idx)=>{
      el.addEventListener('click', ()=>{
        document.querySelectorAll('.market-item').forEach(x=>x.classList.remove('active'));
        el.classList.add('active');
        activeSymbol = el.dataset.symbol;
        activeMarketId = el.dataset.id;
        document.getElementById('activeSymbol').textContent = activeSymbol;
        document.getElementById('market_id').value = activeMarketId;
        document.getElementById('priceField').value = data[activeSymbol].last;
        renderOrderbook();
        drawChart();
      });
      if (idx===0) el.classList.add('active');
    });

    if (activeSymbol) {
      document.getElementById('activeSymbol').textContent = activeSymbol;
      document.getElementById('market_id').value = activeMarketId;
      document.getElementById('priceField').value = data[activeSymbol].last;
    }

    const canvas = document.getElementById('chartCanvas');
    const ctx = canvas.getContext('2d');
    function drawChart(){
      if (!ctx) return;
      canvas.width = canvas.clientWidth;
      canvas.height = canvas.clientHeight;
      const prices = data[activeSymbol].prices;
      const min = Math.min(...prices), max = Math.max(...prices);
      ctx.clearRect(0,0,canvas.width,canvas.height);
      ctx.beginPath();
      prices.forEach((p,i)=>{
        const x = i * (canvas.width/(prices.length-1));
        const y = canvas.height - ((p - min)/(max - min || 1))*canvas.height;
        if (i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
      });
      ctx.strokeStyle='#3ea0ff'; ctx.lineWidth=2; ctx.stroke();
      ctx.fillStyle='#0b1220'; ctx.font='12px Inter, Arial';
      ctx.fillText('Price: ' + Number(data[activeSymbol].last).toLocaleString(), 8, 14);
    }
    function simulate(){
      markets.forEach(m=>{
        const sym = m.symbol;
        const row = data[sym];
        const vol = sym.startsWith('BTC')?0.006:(sym.startsWith('ETH')?0.015:0.02);
        const drift = (Math.random()-0.5)*vol*row.last;
        let next = Math.max(1, Math.round(row.last + drift));
        if (Math.random()<0.02) next += Math.round(row.last * (Math.random()>0.5?0.02:-0.02));
        row.last = next;
        row.prices.push(next); if (row.prices.length>200) row.prices.shift();
      });
      drawChart();
      renderOrderbook();
    }
    function renderOrderbook(){
      const asksEl = document.getElementById('asksList');
      const bidsEl = document.getElementById('bidsList');
      asksEl.innerHTML=''; bidsEl.innerHTML='';
      const last = data[activeSymbol].last;
      for (let i=1;i<=6;i++){
        const price = last + i * Math.round(last*0.002);
        const size = (Math.random()*5).toFixed(4);
        const a = document.createElement('div'); a.style.display='flex'; a.style.justifyContent='space-between'; a.style.padding='4px 0';
        a.innerHTML = `<div class="small">${price.toLocaleString()}</div><div class="small">${size}</div>`;
        asksEl.appendChild(a);
        const price2 = last - i * Math.round(last*0.002);
        const size2 = (Math.random()*5).toFixed(4);
        const b = document.createElement('div'); b.style.display='flex'; b.style.justifyContent='space-between'; b.style.padding='4px 0';
        b.innerHTML = `<div class="small">${price2.toLocaleString()}</div><div class="small">${size2}</div>`;
        bidsEl.appendChild(b);
      }
    }

    setInterval(simulate, 1200);
    window.addEventListener('resize', drawChart);
    drawChart();
    renderOrderbook();
  </script>
</body>
</html>

