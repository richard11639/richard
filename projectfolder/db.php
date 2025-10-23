<?php
// app.php - Single-file Wig & Hair shop with deposit workflow + admin approvals
// Requirements: PHP 7.4+, writable folder. Create images/ with hero.jpg, logo.png, product-placeholder.jpg
// Auto-creates data.sqlite, uploads/ and uploads/proofs/
// Admin created automatically: admin@example.com / admin123

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------------- CONFIG ----------------
define('DB_FILE', __DIR__ . '/data.sqlite');
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('PROOF_DIR', UPLOAD_DIR . '/proofs');
define('IMAGE_DIR', __DIR__ . '/images');
define('SITE_TITLE', 'OYIN WIG & HAIR');
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024); // 3MB

// ensure dirs
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(PROOF_DIR)) mkdir(PROOF_DIR, 0755, true);
if (!is_dir(IMAGE_DIR)) mkdir(IMAGE_DIR, 0755, true);

// ---------------- DB ----------------
$pdo = new PDO('sqlite:' . DB_FILE);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function init_db($pdo){
    $pdo->exec("PRAGMA foreign_keys = ON;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE,
        password TEXT,
        first_name TEXT,
        last_name TEXT,
        wallet NUMERIC DEFAULT 0,
        is_admin INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        price NUMERIC NOT NULL DEFAULT 0,
        image TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS deposits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        amount NUMERIC,
        payment_ref TEXT,
        sender_name TEXT,
        proof TEXT,
        status TEXT DEFAULT 'pending',
        admin_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        subtotal NUMERIC,
        delivery_fee NUMERIC,
        tax NUMERIC,
        total NUMERIC,
        order_type TEXT,
        pickup_time TEXT,
        delivery_info TEXT,
        contact_info TEXT,
        payment_method TEXT,
        payment_proof TEXT,
        status TEXT DEFAULT 'processing',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        qty INTEGER,
        price NUMERIC,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
    )");
}
init_db($pdo);

// create default admin user if none
$cnt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin=1")->fetchColumn();
if (!$cnt){
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (email,password,first_name,last_name,is_admin) VALUES (?,?,?,?,1)");
    $ins->execute(['admin@example.com',$hash,'Admin','User']);
}

// seed products if none
$cnt = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
if (!$cnt){
    $seed = [
        ['Glueless Dark Brown Blunt Cut SDD Bone straight 4x4 HD Lace Wig','100% human hair blunt cut',98000],
        ['Glueless Pre-Cut Closure HD Lace Bob Wig','Ready to go bob',72000],
        ['Chestnut Brown Loose Wave 5x5 HD Lace Wig','Loose wave 5x5',235000],
    ];
    $ins = $pdo->prepare("INSERT INTO products (title,description,price) VALUES (?,?,?)");
    foreach($seed as $p) $ins->execute([$p[0],$p[1],$p[2]]);
}

// ---------------- HELPERS ----------------
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n){ return '₦' . number_format((float)$n, 0); }
function current_user($pdo){
    if (empty($_SESSION['user_id'])) return null;
    static $u = null;
    if ($u !== null) return $u;
    global $pdo;
    $stmt = $pdo->prepare("SELECT id,email,first_name,last_name,wallet,is_admin FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    return $u ?: null;
}
function cart_count(){ $c=0; foreach($_SESSION['cart'] ?? [] as $q) $c += intval($q); return $c; }
function cart_subtotal($pdo){
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return 0;
    $ids = array_keys($cart); $place = implode(',', array_fill(0,count($ids),'?'));
    $stmt = $pdo->prepare("SELECT id,price FROM products WHERE id IN ($place)");
    $stmt->execute($ids); $db=[];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $db[$r['id']] = $r['price'];
    $sum = 0;
    foreach($cart as $id=>$qty){ $price = $db[intval($id)] ?? 0; $sum += $price * intval($qty); }
    return round($sum,2);
}
function csrf_token(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function validate_csrf($token){ return !empty($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'],$token); }

// secure upload (images and pdf proofs)
function save_upload_secure($file, $dir = UPLOAD_DIR){
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_UPLOAD_BYTES) return null;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/jpg'=>'jpg','application/pdf'=>'pdf'];
    if (!isset($allowed[$mime])) return null;
    $ext = $allowed[$mime];
    $safe = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = rtrim($dir,'/') . '/' . $safe;
    if (move_uploaded_file($file['tmp_name'], $dest)) return str_replace(__DIR__.'/', '', $dest);
    return null;
}

function flash($msg){ $_SESSION['flash'] = $msg; }
function show_flash(){ if(!empty($_SESSION['flash'])){ echo '<div style="background:#e6ffef;border:1px solid #bde5c8;padding:10px;border-radius:8px;margin:10px 0">'.e($_SESSION['flash']).'</div>'; unset($_SESSION['flash']); } }

function generate_payment_ref($user_id = null){
    return 'OYIN-' . strtoupper(substr(bin2hex(random_bytes(3)),0,6)) . '-' . date('His');
}

// ---------------- ROUTING ----------------
$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? ($_POST['action'] ?? null);
$user = current_user($pdo);

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    // Add to cart
    if ($action === 'add_to_cart'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=shop'); exit; }
        $pid = intval($_POST['product_id'] ?? 0);
        $qty = max(1,intval($_POST['qty'] ?? 1));
        if ($pid>0){
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
            flash('Added to cart');
        }
        header('Location:?page=shop'); exit;
    }

    // Update cart
    if ($action === 'update_cart'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=cart'); exit; }
        foreach($_POST['qty'] ?? [] as $k=>$v){ $k=intval($k); $v=max(1,intval($v)); if (isset($_SESSION['cart'][$k])) $_SESSION['cart'][$k]=$v; }
        flash('Cart updated'); header('Location:?page=cart'); exit;
    }

    // Remove item
    if ($action === 'remove_cart'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=cart'); exit; }
        $pid = intval($_POST['product_id'] ?? 0); if ($pid>0) unset($_SESSION['cart'][$pid]); flash('Removed'); header('Location:?page=cart'); exit;
    }

    // Clear cart
    if ($action === 'clear_cart'){ unset($_SESSION['cart']); flash('Cleared'); header('Location:?page=cart'); exit; }

    // Signup
    if ($action === 'signup'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=signup'); exit; }
        $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? ''; $first = trim($_POST['first_name'] ?? ''); $last = trim($_POST['last_name'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 4){ flash('Invalid email/password'); header('Location:?page=signup'); exit; }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?"); $stmt->execute([$email]); if ($stmt->fetch()){ flash('Email exists'); header('Location:?page=signup'); exit; }
        $h = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (email,password,first_name,last_name) VALUES (?,?,?,?)")->execute([$email,$h,$first,$last]);
        flash('Account created — please sign in'); header('Location:?page=signin'); exit;
    }

    // Signin
    if ($action === 'signin'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=signin'); exit; }
        $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT id,password FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($password, $row['password'])){ $_SESSION['user_id'] = $row['id']; flash('Signed in'); $redir = $_SESSION['redirect_after_login'] ?? '?page=shop'; unset($_SESSION['redirect_after_login']); header('Location:'.$redir); exit; }
        flash('Invalid credentials'); header('Location:?page=signin'); exit;
    }

    // Signout
    if ($action === 'signout'){ unset($_SESSION['user_id']); flash('Signed out'); header('Location:?page=home'); exit; }

    // Admin add product
    if ($action === 'admin_add_product'){
        if (!$user || !$user['is_admin']){ flash('Admin only'); header('Location:?page=signin'); exit; }
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=admin'); exit; }
        $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? ''); $price = floatval($_POST['price'] ?? 0);
        $img = null;
        if (!empty($_FILES['image']['name'])){ $saved = save_upload_secure($_FILES['image'], UPLOAD_DIR); if ($saved) $img = $saved; }
        $pdo->prepare("INSERT INTO products (title,description,price,image) VALUES (?,?,?,?)")->execute([$title,$desc,$price,$img]);
        flash('Product added'); header('Location:?page=admin'); exit;
    }

    // Admin edit product
    if ($action === 'admin_edit_product'){
        if (!$user || !$user['is_admin']){ flash('Admin only'); header('Location:?page=signin'); exit; }
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=admin'); exit; }
        $id = intval($_POST['id'] ?? 0); $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? ''); $price = floatval($_POST['price'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id=?"); $stmt->execute([$id]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $img = $row['image'] ?? null;
        if (!empty($_FILES['image']['name'])){ $saved = save_upload_secure($_FILES['image'], UPLOAD_DIR); if ($saved) $img = $saved; }
        $pdo->prepare("UPDATE products SET title=?,description=?,price=?,image=? WHERE id=?")->execute([$title,$desc,$price,$img,$id]);
        flash('Product updated'); header('Location:?page=admin'); exit;
    }

    // Admin delete product
    if ($action === 'admin_delete_product'){
        if (!$user || !$user['is_admin']){ flash('Admin only'); header('Location:?page=signin'); exit; }
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=admin'); exit; }
        $id = intval($_POST['id'] ?? 0); $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]); flash('Deleted'); header('Location:?page=admin'); exit;
    }

    // Deposit step 1 (start) - store in session and redirect to confirm
    if ($action === 'deposit_start'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=deposit'); exit; }
        if (empty($_SESSION['user_id'])){ $_SESSION['redirect_after_login'] = '?page=deposit'; flash('Sign in first'); header('Location:?page=signin'); exit; }
        $amount = max(0,floatval($_POST['amount'] ?? 0));
        if ($amount < 50){ flash('Minimum deposit 50 NGN'); header('Location:?page=deposit'); exit; }
        $sender = trim($_POST['sender_name'] ?? '');
        $ref = trim($_POST['payment_ref'] ?? '');
        if (!$ref) $ref = generate_payment_ref($_SESSION['user_id']);
        $_SESSION['deposit_amount'] = $amount;
        $_SESSION['deposit_sender_name'] = $sender;
        $_SESSION['deposit_payment_ref'] = $ref;
        header('Location:?page=deposit&step=confirm'); exit;
    }

    // Deposit step 2 (confirm) - create deposit request and optionally upload proof
    if ($action === 'deposit_confirm'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=deposit'); exit; }
        if (empty($_SESSION['user_id']) || empty($_SESSION['deposit_amount'])){ flash('No deposit in progress'); header('Location:?page=deposit'); exit; }
        $uid = $_SESSION['user_id'];
        $amount = floatval($_SESSION['deposit_amount']);
        $sender = $_SESSION['deposit_sender_name'] ?? '';
        $ref = $_SESSION['deposit_payment_ref'] ?? generate_payment_ref($uid);
        $proof = null;
        if (!empty($_FILES['proof']['name'])){ $saved = save_upload_secure($_FILES['proof'], PROOF_DIR); if ($saved) $proof = $saved; }
        $pdo->prepare("INSERT INTO deposits (user_id,amount,payment_ref,sender_name,proof,status) VALUES (?,?,?,?,?, 'pending')")->execute([$uid,$amount,$ref,$sender,$proof]);
        unset($_SESSION['deposit_amount'], $_SESSION['deposit_sender_name'], $_SESSION['deposit_payment_ref']);
        flash('Deposit request submitted — awaiting admin approval'); header('Location:?page=deposit'); exit;
    }

    // Admin approve/reject deposit
    if ($action === 'admin_deposit_action'){
        if (!$user || !$user['is_admin']){ flash('Admin only'); header('Location:?page=signin'); exit; }
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=admin'); exit; }
        $did = intval($_POST['deposit_id'] ?? 0); $do = $_POST['do'] ?? 'approve';
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id=?"); $stmt->execute([$did]); $dep = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dep){ flash('Not found'); header('Location:?page=admin'); exit; }
        if ($do === 'approve' && $dep['status'] === 'pending'){
            $pdo->prepare("UPDATE users SET wallet = wallet + ? WHERE id = ?")->execute([$dep['amount'], $dep['user_id']]);
            $pdo->prepare("UPDATE deposits SET status='approved', admin_id=?, approved_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$user['id'],$did]);
            flash('Deposit approved and wallet credited');
        } elseif ($do === 'reject' && $dep['status'] === 'pending'){
            $pdo->prepare("UPDATE deposits SET status='rejected', admin_id=?, approved_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$user['id'],$did]);
            flash('Deposit rejected');
        } else {
            flash('Action not allowed');
        }
        header('Location:?page=admin'); exit;
    }

    // Place order (checkout)
    if ($action === 'place_order'){
        if (!validate_csrf($_POST['csrf'] ?? '')){ flash('Invalid token'); header('Location:?page=checkout'); exit; }
        if (empty($_SESSION['cart'])){ flash('Cart empty'); header('Location:?page=shop'); exit; }
        if (!$user){ $_SESSION['redirect_after_login'] = '?page=checkout'; flash('Sign in first'); header('Location:?page=signin'); exit; }
        $subtotal = cart_subtotal($pdo);
        $delivery_fee = floatval($_POST['delivery_fee'] ?? 1500);
        $tax = round($subtotal * 0.05,2);
        $total = $subtotal + $delivery_fee + $tax;
        $order_type = ($_POST['order_type'] ?? '') === 'Pickup' ? 'Pickup' : 'Delivery';
        $when = $_POST['when'] ?? 'ASAP';
        $payment = in_array($_POST['payment_method'] ?? 'Cash',['Cash','Bank Transfer','Card','Wallet']) ? $_POST['payment_method'] : 'Cash';
        if ($payment === 'Wallet'){
            $bal = $pdo->prepare("SELECT wallet FROM users WHERE id=?")->execute([$user['id']]); // not used; we'll not double-check here (demo)
            $stmt = $pdo->prepare("SELECT wallet FROM users WHERE id=?"); $stmt->execute([$user['id']]); $bal = $stmt->fetchColumn();
            if ($bal < $total){ flash('Insufficient wallet funds'); header('Location:?page=deposit'); exit; }
        }
        $delivery_info = $order_type === 'Delivery' ? json_encode([
            'street'=>$_POST['street'] ?? '',
            'no'=>$_POST['no'] ?? '',
            'city'=>$_POST['city'] ?? '',
            'zip'=>$_POST['zip'] ?? '',
            'apt'=>$_POST['apt'] ?? '',
            'company'=>$_POST['company'] ?? ''
        ]) : null;
        $contact_info = json_encode([
            'first'=>$_POST['first_name'] ?? '',
            'last'=>$_POST['last_name'] ?? '',
            'phone'=>$_POST['phone'] ?? '',
            'email'=>$_POST['email'] ?? ''
        ]);
        $proof = null;
        if ($payment === 'Bank Transfer' && !empty($_FILES['proof']['name'])){ $proof = save_upload_secure($_FILES['proof'], PROOF_DIR); }
        $ins = $pdo->prepare("INSERT INTO orders (user_id,subtotal,delivery_fee,tax,total,order_type,pickup_time,delivery_info,contact_info,payment_method,payment_proof,status) VALUES (?,?,?,?,?,?,?,?,?,?,?, 'processing')");
        $ins->execute([$user['id'],$subtotal,$delivery_fee,$tax,$total,$order_type, ($when==='ASAP'? 'ASAP' : ($_POST['specific_time'] ?? '')), $delivery_info, $contact_info, $payment, $proof]);
        $order_id = $pdo->lastInsertId();
        foreach($_SESSION['cart'] as $pid=>$qty){
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id=?"); $stmt->execute([$pid]); $price = $stmt->fetchColumn() ?: 0;
            $pdo->prepare("INSERT INTO order_items (order_id,product_id,qty,price) VALUES (?,?,?,?)")->execute([$order_id,$pid,$qty,$price]);
        }
        if ($payment === 'Wallet'){ $pdo->prepare("UPDATE users SET wallet = wallet - ? WHERE id = ?")->execute([$total, $user['id']]); }
        unset($_SESSION['cart']);
        flash("Order placed! ID #$order_id"); header('Location:?page=orders'); exit;
    }

}

// ---------------- RENDERING ----------------
function page_header($pdo){
    $user = current_user($pdo);
    $pending = 0;
    if ($user && $user['is_admin']){ $pending = (int)$pdo->query("SELECT COUNT(*) FROM deposits WHERE status='pending'")->fetchColumn(); }
    ?>
    <!doctype html><html lang="en">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=e(SITE_TITLE)?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
      :root{--accent:#ff7b54;--muted:#55606a;--dark:#071428}
      *{box-sizing:border-box}
      body{font-family:Poppins,system-ui,Arial;margin:0;background:#f6f8fb;color:var(--dark)}
      .container{max-width:1100px;margin:0 auto;padding:16px}
      header.site-header{background:#fff;border-bottom:1px solid #eee;position:sticky;top:0;z-index:50}
      .nav{display:flex;align-items:center;gap:12px;padding:12px 16px}
      .brand{display:flex;gap:12px;align-items:center}
      .brand img{width:54px;height:54px;border-radius:8px;object-fit:cover}
      nav a{color:var(--dark);text-decoration:none;padding:8px 10px;border-radius:8px;font-weight:700}
      .menu{position:relative}
      .menu .drop{display:none;position:absolute;right:0;top:100%;background:#fff;border:1px solid #eee;padding:8px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,0.06)}
      .menu:hover .drop{display:block}
      .badge{background:var(--accent);color:#fff;padding:6px 8px;border-radius:999px;font-weight:800}
      .card{background:#fff;padding:12px;border-radius:8px;box-shadow:0 6px 20px rgba(2,22,36,0.04);display:flex;flex-direction:column}
      .btn{background:var(--accent);color:#fff;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;font-weight:800}
      .btn.ghost{background:transparent;border:1px solid #e6ebf0;color:var(--dark)}
      .small{font-size:.9rem;color:var(--muted)}
      .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}
      .table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden}
      .table th,.table td{padding:10px;border-top:1px solid #eee;text-align:left}
      .hero{width:100%;height:420px;border-radius:12px;overflow:hidden;background:#ddd;display:flex;align-items:center;justify-content:center}
      .hero img{width:100%;height:100%;object-fit:cover;display:block}
      footer{padding:18px;background:#fff;border-top:1px solid #eee;margin-top:32px;text-align:center;color:var(--muted)}
      .notice{background:#fff8e6;border-left:4px solid #ffb86b;padding:12px;border-radius:8px}
      .muted{color:var(--muted)}
      .form-row{margin:8px 0}
      input[type="text"], input[type="email"], input[type="number"], input[type="password"], textarea {width:100%;padding:8px;border:1px solid #eaeef2;border-radius:6px}
    </style>
    </head><body>
    <header class="site-header">
      <div class="nav container">
        <div class="brand">
          <img src="images/logo.png" alt="logo" onerror="this.onerror=null;this.src='images/product-placeholder.jpg'">
          <div><div style="font-weight:900"><?=e(SITE_TITLE)?></div><small class="small">Wigs • Extensions • Styling</small></div>
        </div>
        <nav style="margin-left:auto;display:flex;align-items:center;gap:8px">
          <a href="?page=home">Home</a>
          <a href="?page=shop">Shop</a>
          <div class="menu"><a href="#">More ▾</a>
            <div class="drop">
              <a href="?page=gallery">Gallery</a><br>
              <a href="?page=about">About</a><br>
              <?php if ($user): ?>
                <a href="?page=orders">My Orders</a><br>
                <a href="?page=deposit">Deposit (Wallet: <?=money($user['wallet'])?>)</a><br>
                <a href="?action=signout">Logout</a>
              <?php else: ?>
                <a href="?page=signup">Sign up</a><br>
                <a href="?page=signin">Sign in</a>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($user && $user['is_admin']): ?>
            <a href="?page=admin" class="badge">Admin <?= $pending ? '('.$pending.')' : '' ?></a>
          <?php endif; ?>
          <a href="?page=cart" class="badge">Cart (<?=cart_count()?>)</a>
        </nav>
      </div>
    </header>
    <main class="container">
    <?php show_flash();
}

function page_footer(){ ?>
    </main>
    <footer>
      © <?=date('Y')?> <?=e(SITE_TITLE)?> — Demo. Production: enforce HTTPS, harden sessions & uploads.
    </footer>
    </body></html>
<?php }

// ---------------- PAGES ----------------
page_header($pdo);
$csrf = csrf_token();

if ($page === 'home'){
    $hero = file_exists(IMAGE_DIR.'/hero.jpg') ? 'images/hero.jpg' : 'images/product-placeholder.jpg';
    ?>
    <div class="hero"><img src="<?=e($hero)?>" alt="Hero"></div>
    <div style="display:flex;gap:12px;margin-top:16px;align-items:flex-start">
      <div style="flex:1">
        <h1 style="margin:0 0 6px">Cassandra's <span style="color:#e91e63">HAIR SALON</span></h1>
        <p class="small">Luxury styling • Wig services • Extensions</p>
        <div class="card" style="margin-top:12px">
          <strong>OUR SERVICES</strong>
          <ul class="small" style="padding-left:18px;margin:8px 0">
            <li>Hair Extensions</li><li>Wig Installations</li><li>Cornrows</li><li>Weaving</li>
            <li>Styling</li><li>Cutting</li><li>Braids</li><li>Dreadlocks</li><li>Twists</li><li>Relaxer</li>
          </ul>
          <button class="btn" onclick="location.href='?page=shop'">Shop Wigs</button>
        </div>
      </div>
      <aside style="width:320px">
        <div class="card" style="background:linear-gradient(180deg,#e91e63,#d31456);color:#fff">
          <div style="font-weight:800;font-size:18px">BEAUTY • CONFIDENCE • STYLE</div>
          <p class="small">Expert stylists • Premium human hair</p>
          <div style="margin-top:12px;background:rgba(255,255,255,0.08);padding:10px;border-radius:8px">
            <strong>OUR HOURS</strong>
            <div class="small">Mon-Fri: 10AM — 5PM<br>Sat: 9AM — 4PM<br>Sun: Closed</div>
          </div>
        </div>
      </aside>
    </div>
    <?php
}

// SHOP
elseif ($page === 'shop'){
    $per = 12; $pageno = max(1,intval($_GET['p'] ?? 1)); $offset = ($pageno-1)*$per;
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT :l OFFSET :o");
    $stmt->bindValue(':l',$per,PDO::PARAM_INT); $stmt->bindValue(':o',$offset,PDO::PARAM_INT); $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); $total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "<h2>Products (".intval($total).")</h2><p class='small'>Add quantity and Add to cart.</p>";
    echo "<div class='grid'>";
    foreach($products as $p){
        $img = $p['image'] ? e($p['image']) : 'images/product-placeholder.jpg';
        ?>
        <div class="card">
          <img src="<?= $img ?>" alt="<?=e($p['title'])?>">
          <h3 style="margin:8px 0 6px"><?=e($p['title'])?></h3>
          <div style="font-weight:800;color:#ff7b54"><?=money($p['price'])?></div>
          <p class="small" style="flex:1"><?=e($p['description'])?></p>
          <form method="post" action="?action=add_to_cart" style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
            <input type="number" name="qty" value="1" min="1" style="width:70px;padding:6px;border-radius:6px;border:1px solid #eef">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <button class="btn" type="submit">Add to cart</button>
            <a class="btn btn.ghost" href="?page=product&id=<?=intval($p['id'])?>">View</a>
          </form>
        </div>
    <?php
    }
    echo "</div>";
    echo '<div style="margin-top:12px">';
    if ($pageno>1) echo '<a href="?page=shop&p='.($pageno-1).'">&laquo; Prev</a>';
    if ($offset + $per < $total) echo '<a href="?page=shop&p='.($pageno+1).'" style="margin-left:12px">Next &raquo;</a>';
    echo '</div>';
}

// PRODUCT
elseif ($page === 'product' && !empty($_GET['id'])){
    $id = intval($_GET['id']); $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?"); $stmt->execute([$id]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) echo "<p>Product not found</p>";
    else {
        $img = $p['image'] ? e($p['image']) : 'images/product-placeholder.jpg';
        echo "<div class='card' style='display:flex;gap:12px;flex-direction:row;align-items:flex-start'>";
        echo "<img src='".$img."' style='width:420px;height:320px;object-fit:cover'>";
        echo "<div style='flex:1'><h2>".e($p['title'])."</h2><div style='font-weight:800;color:#ff7b54'>".money($p['price'])."</div><p class='small'>".e($p['description'])."</p>";
        ?>
        <form method="post" action="?action=add_to_cart" style="display:flex;gap:8px;align-items:center">
          <input type="hidden" name="product_id" value="<?=intval($p['id'])?>">
          <input type="number" name="qty" value="1" min="1" style="width:80px;padding:6px;border-radius:6px;border:1px solid #eef">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <button class="btn" type="submit">Add to cart</button>
          <a class="btn btn.ghost" href="?page=shop">Back</a>
        </form>
        <?php
        echo "</div></div>";
    }
}

// CART
elseif ($page === 'cart'){
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart){ echo '<h2>Your cart is empty</h2><p><a href="?page=shop" class="btn">Back to shop</a></p>'; }
    else {
        $ids = array_keys($cart); $place = implode(',', array_fill(0,count($ids),'?'));
        $stmt = $pdo->prepare("SELECT id,title,price,image FROM products WHERE id IN ($place)"); $stmt->execute($ids);
        $db = []; foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $db[$r['id']] = $r;
        $subtotal = cart_subtotal($pdo);
        echo "<h2>Your Cart (".array_sum($cart)." items)</h2>";
        echo '<form method="post" action="?action=update_cart"><input type="hidden" name="csrf" value="'.csrf_token().'">';
        echo '<table class="table"><thead><tr><th>Image</th><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead><tbody>';
        foreach($cart as $pid=>$qty){
            $p = $db[$pid] ?? null; $price = $p['price'] ?? 0; $line = $price * $qty; $img = $p && $p['image'] ? e($p['image']) : 'images/product-placeholder.jpg';
            echo '<tr>';
            echo '<td style="width:120px"><img src="'.$img.'" style="width:100px;height:60px;object-fit:cover;border-radius:6px"></td>';
            echo '<td>'.e($p['title'] ?? 'Product '.$pid).'</td>';
            echo '<td>'.money($price).'</td>';
            echo '<td><input type="number" name="qty['.intval($pid).']" value="'.intval($qty).'" min="1" style="width:70px;padding:6px"></td>';
            echo '<td>'.money($line).'</td>';
            echo '<td>
                    <form method="post" action="?action=remove_cart" style="display:inline">
                      <input type="hidden" name="product_id" value="'.intval($pid).'">
                      <input type="hidden" name="csrf" value="'.csrf_token().'">
                      <button class="btn btn.ghost" type="submit">Remove</button>
                    </form>
                  </td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<div style="margin-top:12px"><button class="btn" type="submit">Update quantities</button>';
        echo ' <a class="btn btn.ghost" href="?action=clear_cart">Clear cart</a>';
        echo ' <a class="btn" href="?page=checkout">Proceed to Checkout</a></div></form>';
        echo '<div style="margin-top:18px;background:#fff;padding:12px;border-radius:8px">';
        echo "<div>Subtotal: ".money($subtotal)."</div>";
        echo "<div>Delivery fee: ".money(1500)."</div>";
        echo "<div>Tax (5%): ".money(round($subtotal*0.05,2))."</div>";
        echo "<div style='font-weight:900;margin-top:8px'>Estimated total: ".money($subtotal+1500+round($subtotal*0.05,2))."</div>";
        echo '</div>';
    }
}

// SIGNUP
elseif ($page === 'signup'){
    ?>
    <h2>Sign up</h2>
    <form method="post" action="?action=signup" style="max-width:480px">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <div class="form-row"><label>First name <input name="first_name"></label></div>
      <div class="form-row"><label>Last name <input name="last_name"></label></div>
      <div class="form-row"><label>Email <input name="email" required></label></div>
      <div class="form-row"><label>Password <input name="password" type="password" required></label></div>
      <button class="btn" type="submit">Create account</button>
    </form>
    <?php
}

// SIGNIN
elseif ($page === 'signin'){
    ?>
    <h2>Sign in</h2>
    <form method="post" action="?action=signin" style="max-width:420px">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <div class="form-row"><label>Email <input name="email" required></label></div>
      <div class="form-row"><label>Password <input name="password" type="password" required></label></div>
      <button class="btn" type="submit">Sign in</button>
    </form>
    <?php
}

// DEPOSIT multi-step (enter -> confirm -> list)
elseif ($page === 'deposit'){
    if (!$user){ $_SESSION['redirect_after_login'] = '?page=deposit'; flash('Sign in required'); header('Location:?page=signin'); exit; }
    $step = $_GET['step'] ?? 'enter';
    if ($step === 'enter'){
        $suggested_ref = generate_payment_ref($user['id']);
        ?>
        <div class="card">
          <h2>Deposit (NGN)</h2>
          <form method="post" action="?action=deposit_start">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <div class="form-row"><label class="small muted">Amount (NGN) <input name="amount" type="number" step="0.01" min="50" required></label></div>
            <div class="form-row"><label class="small muted">Sender's name (as on transfer) <input name="sender_name" placeholder="e.g. John Doe"></label></div>
            <div class="form-row"><label class="small-muted">Payment reference (include in transfer; you may edit)
              <input name="payment_ref" value="<?=e($suggested_ref)?>">
              <div class="small-muted">Example: <?=e($suggested_ref)?> — include this in your transfer description so admin can match it.</div>
            </label></div>
            <button class="btn" type="submit">Continue</button>
          </form>
        </div>
        <?php
    } elseif ($step === 'confirm' && !empty($_SESSION['deposit_amount'])){
        $amount = $_SESSION['deposit_amount'];
        $sender = $_SESSION['deposit_sender_name'] ?? '';
        $ref = $_SESSION['deposit_payment_ref'] ?? generate_payment_ref($user['id']);
        ?>
        <div class="card">
          <h2>Confirm deposit</h2>
          <div class="notice">
            <p><strong>Send to:</strong></p>
            <p>Account name: <strong>Ogundele Olayinka Mary</strong><br>Bank: <strong>Palmpay</strong><br>Account number: <strong>7050672951</strong></p>
            <p class="muted">Amount: <?=money($amount)?></p>
            <p class="muted">Payment reference to include: <strong><?=e($ref)?></strong></p>
            <p class="muted">Sender name recorded: <strong><?=e($sender)?></strong></p>
          </div>
          <form method="post" action="?action=deposit_confirm" enctype="multipart/form-data" style="margin-top:12px">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <div class="form-row"><label class="small-muted">Upload proof (optional)</label><input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf"></div>
            <button class="btn" type="submit">I have paid — Notify admin</button>
            <a class="btn btn.ghost" href="?page=deposit&step=enter" style="margin-left:8px">Edit</a>
          </form>
        </div>
        <?php
    } else {
        // list user's deposits
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY id DESC"); $stmt->execute([$user['id']]); $deps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>Your deposit requests</h2>";
        echo "<div class='card'><table class='table'><thead><tr><th>ID</th><th>Amount</th><th>Ref</th><th>Status</th><th>Proof</th><th>Created</th></tr></thead><tbody>";
        foreach($deps as $d){
            echo "<tr><td>".intval($d['id'])."</td><td>".money($d['amount'])."</td><td>".e($d['payment_ref'])."</td><td>".e($d['status'])."</td><td>";
            if ($d['proof']) echo '<a href="'.e($d['proof']).'">View</a>'; else echo '-';
            echo "</td><td>".e($d['created_at'])."</td></tr>";
        }
        echo "</tbody></table></div>";
    }
}

// ADMIN
elseif ($page === 'admin'){
    if (!$user || !$user['is_admin']){ flash('Admin only'); header('Location:?page=signin'); exit; }
    echo "<h2>Admin Dashboard</h2>";
    echo '<p><a class="btn" href="?page=admin&new=1">Add product</a> <a class="btn btn.ghost" href="?page=orders">Orders</a></p>';

    // pending deposits
    $pending = $pdo->query("SELECT d.*, u.email FROM deposits d LEFT JOIN users u ON u.id=d.user_id WHERE d.status='pending' ORDER BY d.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    if ($pending){
        echo "<h3>Pending Deposits</h3>";
        echo '<div class="card">';
        foreach($pending as $d){
            echo '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px;border-bottom:1px solid #f1f1f1">';
            echo '<div><strong>#'.intval($d['id']).'</strong> — '.money($d['amount']).' — ref: '.e($d['payment_ref']).' — user: '.e($d['email']).'<br><small class="small-muted">Created: '.e($d['created_at']).'</small></div>';
            echo '<div style="display:flex;gap:8px">';
            echo '<form method="post" style="display:inline"><input type="hidden" name="csrf" value="'.csrf_token().'"><input type="hidden" name="deposit_id" value="'.intval($d['id']).'"><input type="hidden" name="do" value="approve"><button class="btn" formaction="?action=admin_deposit_action" type="submit">Approve</button></form>';
            echo '<form method="post" style="display:inline"><input type="hidden" name="csrf" value="'.csrf_token().'"><input type="hidden" name="deposit_id" value="'.intval($d['id']).'"><input type="hidden" name="do" value="reject"><button class="btn btn.ghost" formaction="?action=admin_deposit_action" type="submit">Reject</button></form>';
            if ($d['proof']) echo '<a class="btn btn.ghost" href="'.e($d['proof']).'">Proof</a>';
            echo '</div></div>';
        }
        echo '</div>';
    } else echo '<div class="card"><p>No pending deposits</p></div>';

    // product management
    if (isset($_GET['new'])){
        ?>
        <div class="card"><h3>Add product</h3>
          <form method="post" action="?action=admin_add_product" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <div class="form-row"><label>Title <input name="title" required></label></div>
            <div class="form-row"><label>Description <textarea name="description"></textarea></label></div>
            <div class="form-row"><label>Price <input name="price" type="number" step="0.01" required></label></div>
            <div class="form-row"><label>Image <input type="file" name="image" accept="image/*"></label></div>
            <button class="btn" type="submit">Add</button>
          </form></div>
        <?php
    } elseif (isset($_GET['edit'])){
        $id = intval($_GET['edit']); $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?"); $stmt->execute([$id]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) echo "<p>Product not found</p>";
        else {
            ?>
            <div class="card"><h3>Edit product</h3>
              <form method="post" action="?action=admin_edit_product" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="id" value="<?=intval($p['id'])?>">
                <div class="form-row"><label>Title <input name="title" value="<?=e($p['title'])?>"></label></div>
                <div class="form-row"><label>Description <textarea name="description"><?=e($p['description'])?></textarea></label></div>
                <div class="form-row"><label>Price <input name="price" type="number" step="0.01" value="<?=e($p['price'])?>"></label></div>
                <div class="form-row"><label>Image <input type="file" name="image" accept="image/*"></label></div>
                <?php if ($p['image']) echo '<div class="form-row"><img src="'.e($p['image']).'" style="width:140px"></div>'; ?>
                <button class="btn" type="submit">Save</button>
              </form>
              <form method="post" action="?action=admin_delete_product" style="margin-top:8px">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="id" value="<?=intval($p['id'])?>">
                <button class="btn btn.ghost" type="submit" onclick="return confirm('Delete product?')">Delete</button>
              </form>
            </div>
            <?php
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC"); $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo '<div class="card"><h3>Products</h3><table class="table"><thead><tr><th>ID</th><th>Title</th><th>Price</th><th>Image</th><th></th></tr></thead><tbody>';
        foreach($products as $p){
            $img = $p['image'] ? e($p['image']) : 'images/product-placeholder.jpg';
            echo '<tr><td>'.intval($p['id']).'</td><td>'.e($p['title']).'</td><td>'.money($p['price']).'</td><td><img src="'.$img.'" style="width:80px;height:50px;object-fit:cover"></td><td><a class="btn btn.ghost" href="?page=admin&edit='.intval($p['id']).'">Edit</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }
}

// CHECKOUT
elseif ($page === 'checkout'){
    $cart = $_SESSION['cart'] ?? []; if (empty($cart)){ flash('Cart empty'); header('Location:?page=shop'); exit; }
    if (!$user){ $_SESSION['redirect_after_login'] = '?page=checkout'; flash('Sign in required'); header('Location:?page=signin'); exit; }
    $subtotal = cart_subtotal($pdo); $delivery_fee = 1500; $tax = round($subtotal*0.05,2); $total = $subtotal + $delivery_fee + $tax;
    ?>
    <h2>Checkout</h2>
    <form method="post" action="?action=place_order" enctype="multipart/form-data" style="max-width:900px">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <h3>Order details</h3>
      <div class="form-row"><label>Order type:
        <select name="order_type"><option>Delivery</option><option>Pickup</option></select></label></div>
      <div class="form-row"><label>When:
        <select name="when" id="whenSel" onchange="toggleTime()"><option>ASAP</option><option>Specific time</option></select>
      </label></div>
      <div id="timeBox" style="display:none" class="form-row"><label>Specific time <input type="time" name="specific_time"></label></div>
      <h3>Delivery Address</h3>
      <div class="form-row"><label>Street <input name="street"></label></div>
      <div class="form-row"><label>City <input name="city"></label></div>
      <div class="form-row"><label>ZIP <input name="zip"></label></div>
      <h3>Contact</h3>
      <div class="form-row"><label>First <input name="first_name" value="<?=e($user['first_name'] ?? '')?>"></label></div>
      <div class="form-row"><label>Last <input name="last_name" value="<?=e($user['last_name'] ?? '')?>"></label></div>
      <div class="form-row"><label>Phone <input name="phone" required></label></div>
      <div class="form-row"><label>Email <input name="email" value="<?=e($user['email'] ?? '')?>"></label></div>
      <h3>Payment</h3>
      <div class="form-row">
        <label><input type="radio" name="payment_method" value="Cash" checked> Cash</label><br>
        <label><input type="radio" name="payment_method" value="Bank Transfer"> Bank Transfer (upload proof)</label>
        <div class="form-row"><label>Upload proof <input type="file" name="proof" accept=".jpg,.png,.pdf"></label></div>
        <label><input type="radio" name="payment_method" value="Card"> Card (not integrated)</label><br>
        <label><input type="radio" name="payment_method" value="Wallet"> Wallet</label>
      </div>
      <h3>Summary</h3>
      <div>Subtotal: <?=money($subtotal)?></div>
      <div>Delivery fee: <?=money($delivery_fee)?></div>
      <div>Tax (5%): <?=money($tax)?></div>
      <div style="font-weight:900">Total: <?=money($total)?></div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Confirm Order</button>
        <a class="btn btn.ghost" href="?page=cart">Back to cart</a>
      </div>
    </form>
    <script>function toggleTime(){document.getElementById('timeBox').style.display=document.getElementById('whenSel').value==='Specific time'?'block':'none'}</script>
    <?php
}

// ORDERS
elseif ($page === 'orders'){
    if (!$user){ flash('Sign in required'); header('Location:?page=signin'); exit; }
    if ($user['is_admin']){ $stmt = $pdo->query("SELECT o.*, u.email FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.id DESC"); $orders = $stmt->fetchAll(PDO::FETCH_ASSOC); }
    else { $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC"); $stmt->execute([$user['id']]); $orders = $stmt->fetchAll(PDO::FETCH_ASSOC); }
    echo "<h2>Orders</h2>";
    foreach($orders as $o){
        echo '<div class="card" style="margin-bottom:10px">';
        echo '<div style="display:flex;justify-content:space-between"><div>Order #'.intval($o['id']).'</div><div>'.e($o['created_at']).'</div></div>';
        echo '<div>Total: '.money($o['total']).' — Payment: '.e($o['payment_method']).' — Status: '.e($o['status']).'</div>';
        $stmt = $pdo->prepare("SELECT oi.*, p.title FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE order_id=?"); $stmt->execute([$o['id']]); $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>"; foreach($items as $it) echo "<li>".intval($it['qty'])." × ".e($it['title']?:'Product '.$it['product_id'])." @ ".money($it['price'])."</li>"; echo "</ul>";
        if ($user['is_admin']){
            echo '<form method="post" action="?action=admin_update_order" style="display:flex;gap:8px;align-items:center">';
            echo '<input type="hidden" name="csrf" value="'.csrf_token().'">';
            echo '<input type="hidden" name="order_id" value="'.intval($o['id']).'">';
            echo '<select name="status"><option>processing</option><option>shipped</option><option>completed</option><option>cancelled</option></select>';
            echo '<button class="btn" type="submit">Update</button></form>';
        }
        echo '</div>';
    }
}

// ABOUT & GALLERY
elseif ($page === 'about'){ echo '<h2>About</h2><p class="small">We sell premium wigs and provide hair services.</p>'; }
elseif ($page === 'gallery'){ echo '<h2>Gallery</h2><div class="grid">'; $imgs = glob(IMAGE_DIR.'/*'); foreach($imgs as $i) echo '<div class="card"><img src="'.str_replace(__DIR__.'/', '', $i).'"><div class="small">Style</div></div>'; echo '</div>'; }

// fallback
else { header('Location:?page=home'); exit; }

page_footer();
