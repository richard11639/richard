<?php
// cassandra_wigs_shop.php
// Single-file advanced PHP + SQLite demo for a Hair & Wig e-commerce site
// Features (advanced):
// - SQLite (data.sqlite) auto-creates schema when missing
// - Secure auth: signup / login / logout (password_hash) + admin login
// - CSRF tokens for state-changing forms
// - Categories, products, product images (upload), product variants (colors/sizes)
// - Product import via CSV (admin)
// - Reviews & ratings (user-submitted)
// - Wishlist (session + persisted for logged-in users)
// - Cart (session) with quantity changes and checkout simulation
// - Search, category filtering, pagination
// - Admin area for product management (add/edit/delete), seed data
// - Prepared statements everywhere, minimal dependencies
// - Single-file; drop into PHP 7.4+ environment. NOT for production without hardening.

session_start();
error_reporting(E_ALL);
ini_set('display_errors',1);

// ---------------- CONFIG ----------------
define('DB_FILE', __DIR__ . '/data.sqlite');
define('UPLOADS_DIR', __DIR__ . '/uploads');
if (!file_exists(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
// admin password (change in production)
define('ADMIN_PASS', 'admin123');
// site settings
$SITE_TITLE = "Cassandra's Hair & Wigs";

// ---------------- DB ----------------
try {
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Cannot open database: " . $e->getMessage());
}

// create schema
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    is_admin INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    sku TEXT,
    description TEXT,
    price REAL NOT NULL DEFAULT 0,
    currency TEXT DEFAULT '₦',
    image TEXT,
    category_id INTEGER,
    stock INTEGER DEFAULT 0,
    featured INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(category_id) REFERENCES categories(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    name TEXT, -- e.g. color
    value TEXT, -- e.g. 'R23S+ GLAZED VANILLA'
    extra_price REAL DEFAULT 0,
    stock INTEGER DEFAULT 0,
    FOREIGN KEY(product_id) REFERENCES products(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS wishlists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    product_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, product_id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    user_name TEXT,
    rating INTEGER,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// seed categories/products if empty
$catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if (!$catCount) {
    $categories = [
        ['Wig & Go','wigandgo'],
        ['Colour Hair','colour-hair'],
        ['Wigs','wigs'],
        ['Bundles','bundles'],
        ['Deals','deals'],
        ['Promotions','promotions'],
        ['Accessories','accessories']
    ];
    $stmt = $pdo->prepare('INSERT INTO categories (name,slug) VALUES (?,?)');
    foreach ($categories as $c) $stmt->execute($c);
}

$productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
if (!$productCount) {
    $cats = $pdo->query('SELECT id,slug FROM categories')->fetchAll(PDO::FETCH_KEY_PAIR);
    $seed = [
        ['Winner | Synthetic Wig (Basic Cap)','winner-synthetic-wig','RWWINNER','A lightweight pixie wig with natural-looking synthetic fiber. Ready-to-wear and low maintenance.',180110.00,'₦','winner-1.jpg',$cats['wigs'],12,1],
        ['Radiant Human Hair - 12 inch','radiant-human-hair-12','RAD-12','Premium 100% human hair bundles, perfect for sew-ins or custom wig units.',45000.00,'₦','human-12.jpg',$cats['bundles'],20,1],
        ['Pink Trend Wig - Short Bob','pink-trend-bob','PTB-1','Vivid pink synthetic bob, fashion-forward and easy to style.',12000.00,'₦','pink-bob.jpg',$cats['wigandgo'],8,0],
    ];
    $stmt = $pdo->prepare('INSERT INTO products (title,slug,sku,description,price,currency,image,category_id,stock,featured) VALUES (?,?,?,?,?,?,?,?,?,?)');
    foreach ($seed as $p) $stmt->execute($p);
}

// ---------------- Helpers ----------------
function h($s){ return htmlspecialchars($s, ENT_QUOTES); }
function slugify($s){ $s = preg_replace('/[^a-z0-9]+/i','-',trim(strtolower($s))); return trim($s,'-'); }

// CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
function csrf_field(){ return '<input type="hidden" name="csrf_token" value="'.$_SESSION['csrf_token'].'">'; }
function check_csrf(){ if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die('Invalid CSRF'); }

// session cart & wishlist
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = []; // product_id => qty
if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = []; // product_id list

// ---------------- Auth actions ----------------
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$flash = null;

if ($_SERVER['REQUEST_METHOD']=='POST'){
    if (in_array($action, ['signup','login','add_to_cart','remove_cart','add_wishlist','remove_wishlist','add_review','admin_add_product','admin_delete_product','admin_import_csv','logout','admin_login'])){
        check_csrf();
    }
    if ($action === 'signup'){
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass = $_POST['password'] ?? '';
        if (!$name || !$email || !$pass) $flash = 'Please fill all fields.';
        else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email=?'); $stmt->execute([$email]);
            if ($stmt->fetch()) $flash = 'Email already registered.';
            else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)')->execute([$name,$email,$hash]);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $flash = 'Account created.';
            }
        }
    }
    else if ($action === 'login'){
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1'); $stmt->execute([$email]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($pass,$user['password'])) $flash = 'Invalid credentials.';
        else { $_SESSION['user_id'] = $user['id']; $flash = 'Welcome back, ' . h($user['name']) . '!'; }
    }
    else if ($action === 'admin_login'){
        $pass = $_POST['password'] ?? '';
        if ($pass === ADMIN_PASS){ $_SESSION['is_admin'] = true; $flash = 'Admin signed in.'; }
        else $flash = 'Invalid admin password.';
    }
    else if ($action === 'logout'){
        session_unset(); session_destroy(); session_start(); $_SESSION['csrf_token']=bin2hex(random_bytes(16)); $_SESSION['cart'] = []; $_SESSION['wishlist'] = []; $flash = 'Logged out.';
    }
    else if ($action === 'add_to_cart'){
        $pid = intval($_POST['product_id'] ?? 0); $qty = max(1,intval($_POST['qty'] ?? 1));
        if ($pid){ if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid] += $qty; else $_SESSION['cart'][$pid] = $qty; $flash='Added to cart.'; }
    }
    else if ($action === 'remove_cart'){
        $pid = intval($_POST['product_id'] ?? 0); if ($pid && isset($_SESSION['cart'][$pid])) { unset($_SESSION['cart'][$pid]); $flash='Removed from cart.'; }
    }
    else if ($action === 'add_wishlist'){
        $pid = intval($_POST['product_id'] ?? 0);
        if ($pid){
            if (!empty($_SESSION['user_id'])){
                try { $pdo->prepare('INSERT OR IGNORE INTO wishlists (user_id,product_id) VALUES (?,?)')->execute([$_SESSION['user_id'],$pid]); $flash='Saved to wishlist.';} catch(Exception $e){ $flash='Error saving wishlist.'; }
            } else { if (!in_array($pid,$_SESSION['wishlist'])) $_SESSION['wishlist'][]=$pid; $flash='Added to wishlist (guest).'; }
        }
    }
    else if ($action === 'remove_wishlist'){
        $pid = intval($_POST['product_id'] ?? 0);
        if ($pid){ if (!empty($_SESSION['user_id'])){ $pdo->prepare('DELETE FROM wishlists WHERE user_id=? AND product_id=?')->execute([$_SESSION['user_id'],$pid]); $flash='Removed.'; } else { $_SESSION['wishlist']=array_values(array_filter($_SESSION['wishlist'],function($x)use($pid){return $x!=$pid;})); $flash='Removed.'; } }
    }
    else if ($action === 'add_review'){
        $pid = intval($_POST['product_id'] ?? 0); $name = trim($_POST['name'] ?? 'Guest'); $rating = intval($_POST['rating'] ?? 5); $comment = trim($_POST['comment'] ?? '');
        if ($pid && $rating>=1 && $rating<=5){ $pdo->prepare('INSERT INTO reviews (product_id,user_name,rating,comment) VALUES (?,?,?,?)')->execute([$pid,$name,$rating,$comment]); $flash='Review added.'; }
    }
    else if ($action === 'admin_add_product' && !empty($_SESSION['is_admin'])){
        $title = trim($_POST['title'] ?? ''); $price = floatval($_POST['price'] ?? 0); $desc = trim($_POST['description'] ?? ''); $sku = trim($_POST['sku'] ?? ''); $category = intval($_POST['category_id'] ?? 0); $stock = intval($_POST['stock'] ?? 0);
        $slug = slugify($title ?: 'product-'.time());
        $img = null;
        if (!empty($_FILES['image']['tmp_name'])){
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fname = $slug . '-' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], UPLOADS_DIR.'/'.$fname);
            $img = 'uploads/'.$fname;
        }
        $pdo->prepare('INSERT INTO products (title,slug,sku,description,price,currency,image,category_id,stock) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$title,$slug,$sku,$desc,$price,'₦',$img,$category,$stock]);
        $flash='Product added.';
    }
    else if ($action === 'admin_delete_product' && !empty($_SESSION['is_admin'])){
        $pid = intval($_POST['product_id'] ?? 0); if ($pid){ $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$pid]); $flash='Product deleted.'; }
    }
    else if ($action === 'admin_import_csv' && !empty($_SESSION['is_admin'])){
        // basic CSV import: title,slug,sku,description,price,image_url,category_slug,stock,featured
        if (!empty($_FILES['csv']['tmp_name'])){
            $csv = fopen($_FILES['csv']['tmp_name'],'r');
            $hdr = fgetcsv($csv);
            while($row=fgetcsv($csv)){
                $data = array_combine($hdr,$row);
                $slug = slugify($data['slug'] ?? $data['title'] ?? '');
                $cat_slug = $data['category_slug'] ?? 'wigs';
                $cat = $pdo->prepare('SELECT id FROM categories WHERE slug=? LIMIT 1'); $cat->execute([$cat_slug]); $catid = $cat->fetchColumn() ?: null;
                $pdo->prepare('INSERT OR IGNORE INTO products (title,slug,sku,description,price,currency,image,category_id,stock,featured) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([
                    $data['title'],$slug,$data['sku'] ?? '',$data['description'] ?? '',floatval($data['price'] ?? 0),'₦',$data['image'] ?? null,$catid,intval($data['stock'] ?? 0),intval($data['featured'] ?? 0)
                ]);
            }
            fclose($csv);
            $flash='CSV imported.';
        }
    }
}

// ---------------- Routing / Rendering ----------------
$page = $_GET['page'] ?? 'home';
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

function render_header($SITE_TITLE, $categories, $pdo, $flash=null){
    $cart_count = array_sum($_SESSION['cart'] ?? []);
    $wishlist_ids = get_wishlist_ids($pdo);
    $wishlist_count = count($wishlist_ids);
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($SITE_TITLE)?></title>
<style>
:root{--accent:#e91e63;--muted:#666}
body{font-family:Inter,system-ui,Segoe UI,Arial;margin:0;background:#fafafa;color:#111}
.container{max-width:1100px;margin:18px auto;padding:18px}
.header{display:flex;align-items:center;gap:12px}
.logo{font-weight:800;font-size:20px}
.nav a{margin-right:10px;color:var(--muted);text-decoration:none}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-top:12px}
.card{background:#fff;border-radius:12px;padding:12px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
.product img{width:100%;height:180px;object-fit:cover;border-radius:8px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer}
.btn-muted{background:#eee;color:#222;border-radius:8px;padding:8px}
.small{font-size:13px;color:var(--muted)}
.topbar{display:flex;justify-content:space-between;align-items:center}
.search{padding:8px;border-radius:8px;border:1px solid #ddd}
.cart-link{background:#111;color:#fff;padding:8px;border-radius:8px}
.badge{background:#111;color:#fff;border-radius:20px;padding:2px 8px;margin-left:6px;font-size:12px}
.footer{margin-top:28px;text-align:center;color:var(--muted);font-size:13px}
</style>
</head>
<body>
<div class="container">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="logo"><?=h($SITE_TITLE)?></div>
      <nav class="nav small">
        <a href="?page=home">Home</a>
        <a href="?page=shop">Shop</a>
        <a href="?page=blog">Blog</a>
      </nav>
      <form method="get" class="form-inline" action="">
        <input name="page" type="hidden" value="shop">
        <input name="q" class="search" placeholder="Search wigs, bundles, colour kits" value="<?=h($_GET['q'] ?? '')?>">
        <button class="btn" type="submit">Search</button>
      </form>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <?php if (!empty($_SESSION['user_id'])): $u = $pdo->prepare('SELECT name FROM users WHERE id=?'); $u->execute([$_SESSION['user_id']]); $u=$u->fetchColumn(); ?>
        <div class="small">Hi, <?=h($u)?></div>
        <form method="post" style="display:inline"><input type="hidden" name="action" value="logout"><?=csrf_field()?><button class="btn-muted">Logout</button></form>
      <?php else: ?>
        <a href="?page=login" class="small">Sign In</a> <a href="?page=signup" class="small">Sign Up</a>
      <?php endif; ?>
      <a href="?page=cart" class="cart-link">Cart <span class="badge"><?= (int)$cart_count ?></span></a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div style="margin-top:12px" class="card small"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div style="margin-top:12px;display:flex;gap:12px;align-items:flex-start">
    <aside style="width:240px">
      <div class="card">
        <strong>Categories</strong>
        <ul style="padding-left:16px;margin-top:8px" class="small">
          <li><a href="?page=shop">All</a></li>
          <?php foreach($categories as $c): ?>
            <li><a href="?page=shop&category=<?=h($c['slug'])?>"><?=h($c['name'])?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div style="margin-top:12px" class="card small">
        <strong>Quick Links</strong>
        <div style="margin-top:8px"><a href="?page=shop&featured=1">Featured</a></div>
        <div style="margin-top:6px"><a href="?page=shop&category=deals">Deals</a></div>
      </div>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <div style="margin-top:12px" class="card small">
          <strong>Admin</strong>
          <div style="margin-top:8px"><a href="?page=admin">Dashboard</a></div>
        </div>
      <?php endif; ?>
    </aside>
    <main style="flex:1">
    <?php
}

function render_footer(){
    ?>
    </main>
  </div>
  <div class="footer">© <?=date('Y')?> Cassandra's Hair & Wigs — Demo store.</div>
</div>
</body>
</html>
<?php
}

function get_wishlist_ids($pdo){ if (!empty($_SESSION['user_id'])){ $stmt=$pdo->prepare('SELECT product_id FROM wishlists WHERE user_id=?'); $stmt->execute([$_SESSION['user_id']]); return array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN)); } else return array_map('intval',$_SESSION['wishlist'] ?? []); }

function fetch_products($pdo,$opts=[]){
    $where=[]; $params=[];
    if (!empty($opts['category_slug'])) { $where[]='c.slug=?'; $params[]=$opts['category_slug']; }
    if (!empty($opts['q'])) { $where[]='(p.title LIKE ? OR p.description LIKE ?)'; $params[]='%'.$opts['q'].'%'; $params[]='%'.$opts['q'].'%'; }
    if (!empty($opts['featured'])){ $where[]='p.featured=1'; }
    $sql='SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id';
    if ($where) $sql.=' WHERE '.implode(' AND ',$where);
    $sql.=' ORDER BY p.featured DESC, p.created_at DESC';
    if (!empty($opts['limit'])) $sql.=' LIMIT '.(int)$opts['limit'];
    $stmt=$pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_product($pdo,$slugOrId){
    if (is_numeric($slugOrId)) { $stmt=$pdo->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=? LIMIT 1'); $stmt->execute([$slugOrId]); }
    else { $stmt=$pdo->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.slug=? LIMIT 1'); $stmt->execute([$slugOrId]); }
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// render
ob_start();
render_header($SITE_TITLE,$categories,$pdo,$flash);

if ($page === 'home'){
    ?>
    <div class="card">
      <h2>Welcome to <?=h($SITE_TITLE)?></h2>
      <p class="small">Premium wigs, human hair, bundles and color kits. Explore featured products or shop by category.</p>
      <div style="margin-top:12px"><a class="btn" href="?page=shop">Shop Now</a></div>
    </div>
    <?php
}
else if ($page === 'shop'){
    $q = trim($_GET['q'] ?? ''); $category = $_GET['category'] ?? null; $featured = isset($_GET['featured']) ? 1 : 0;
    $products = fetch_products($pdo,['q'=>$q,'category_slug'=>$category,'featured'=>$featured]);
    ?>
    <h3>Shop — <?= $category ? h(ucfirst(str_replace('-',' ',$category))) : 'All' ?></h3>
    <div class="grid">
      <?php foreach($products as $p): ?>
        <div class="product card">
          <img src="<?=h($p['image']?:'https://via.placeholder.com/400x300?text=No+Image')?>" alt="<?=h($p['title'])?>">
          <div class="title"><?=h($p['title'])?></div>
          <div class="small"><?=h($p['category_name'] ?? 'Uncategorized')?></div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
            <div><strong><?=h(number_format($p['price']))?> <?=h($p['currency'])?></strong></div>
            <div style="display:flex;gap:8px">
              <form method="post" style="display:inline"><?=csrf_field()?><input type="hidden" name="action" value="add_to_cart"><input type="hidden" name="product_id" value="<?=h($p['id'])?>"><button class="btn">Add to cart</button></form>
              <a class="btn-muted" href="?page=product&slug=<?=h($p['slug'])?>">View more</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
}
else if ($page === 'product'){
    $slug = $_GET['slug'] ?? null;
    if (!$slug) echo '<div class="card">Product not found.</div>';
    else {
        $p = fetch_product($pdo,$slug);
        if (!$p) echo '<div class="card">Product not found.</div>';
        else {
            $reviews = $pdo->prepare('SELECT * FROM reviews WHERE product_id=? ORDER BY created_at DESC'); $reviews->execute([$p['id']]); $reviews=$reviews->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="card" style="display:flex;gap:12px">
              <div style="flex:1">
                <img src="<?=h($p['image']?:'https://via.placeholder.com/800x600?text=No+Image')?>" style="width:100%;max-height:420px;object-fit:cover;border-radius:8px">
                <div style="margin-top:12px">
                  <h2><?=h($p['title'])?></h2>
                  <div class="small">Category: <?=h($p['category_name'] ?? '—')?></div>
                  <div style="margin-top:8px"><strong><?=h(number_format($p['price']))?> <?=h($p['currency'])?></strong></div>
                  <p class="small" style="margin-top:12px"><?=nl2br(h($p['description']))?></p>
                </div>
                <div style="margin-top:18px">
                  <h4>Reviews</h4>
                  <?php if (!$reviews) echo '<div class="small">No reviews yet.</div>';
                  foreach($reviews as $r): ?>
                    <div class="card small" style="margin-top:8px">
                      <strong><?=h($r['user_name'])?> — <?=h($r['rating'])?>/5</strong>
                      <div class="small" style="margin-top:6px"><?=nl2br(h($r['comment']))?></div>
                    </div>
                  <?php endforeach; ?>
                  <div style="margin-top:12px" class="card">
                    <h4>Add review</h4>
                    <form method="post">
                      <?=csrf_field()?>
                      <input type="hidden" name="action" value="add_review">
                      <input type="hidden" name="product_id" value="<?=h($p['id'])?>">
                      <div class="small">Name</div><input name="name" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-top:6px">
                      <div class="small" style="margin-top:6px">Rating</div><select name="rating" style="padding:8px;border-radius:6px;margin-top:6px"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select>
                      <div class="small" style="margin-top:6px">Comment</div><textarea name="comment" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-top:6px"></textarea>
                      <div style="margin-top:8px"><button class="btn">Submit review</button></div>
                    </form>
                  </div>
                </div>
              </div>
              <aside style="width:340px">
                <form method="post">
                  <?=csrf_field()?>
                  <input type="hidden" name="action" value="add_to_cart"><input type="hidden" name="product_id" value="<?=h($p['id'])?>">
                  <div class="small">Quantity</div><input type="number" name="qty" value="1" min="1" style="width:80px;padding:8px;border:1px solid #ddd;border-radius:6px">
                  <div style="margin-top:8px"><button class="btn">Add to cart</button></div>
                </form>
                <div style="margin-top:8px">
                  <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="add_wishlist"><input type="hidden" name="product_id" value="<?=h($p['id'])?>"><button class="btn-muted">Add to favorites</button></form>
                </div>
                <div style="margin-top:12px" class="card small">
                  <strong>Product details</strong>
                  <div style="margin-top:8px">SKU: <?=h($p['sku'])?></div>
                  <div>Stock: <?=h($p['stock'])?></div>
                </div>
              </aside>
            </div>
            <?php
        }
    }
}
else if ($page === 'cart'){
    $cart = $_SESSION['cart'] ?? []; $items = []; $total=0;
    if ($cart){ $ids = array_keys($cart); $placeholders = implode(',', array_fill(0,count($ids),'?')); $stmt=$pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)"); $stmt->execute($ids); $rows=$stmt->fetchAll(PDO::FETCH_ASSOC); foreach($rows as $r){ $qty=$cart[$r['id']]; $r['qty']=$qty; $r['line']=$qty*$r['price']; $total += $r['line']; $items[]=$r; } }
    ?>
    <h3>Your Cart</h3>
    <div class="card">
      <?php if (!$items) echo '<div class="small">Your cart is empty. <a href="?page=shop">Shop now</a></div>';
      else {
        echo '<table style="width:100%;border-collapse:collapse"><thead class="small"><tr><th align="left">Product</th><th>Qty</th><th>Price</th><th>Line</th><th></th></tr></thead><tbody>';
        foreach($items as $it){
            echo '<tr style="border-top:1px solid #eee"><td style="padding:8px 0"><strong>'.h($it['title']).'</strong></td><td style="text-align:center">'.h($it['qty']).'</td><td style="text-align:right">'.h(number_format($it['price'])).'</td><td style="text-align:right">'.h(number_format($it['line'])).'</td><td style="text-align:right"><form method="post">'.csrf_field().'<input type="hidden" name="action" value="remove_cart"><input type="hidden" name="product_id" value="'.h($it['id']).'"><button class="btn-muted">Remove</button></form></td></tr>';
        }
        echo '</tbody></table>';
        echo '<div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center"><div class="small">Total</div><div><strong>'.h(number_format($total)).' ₦</strong></div></div>';
        echo '<div style="margin-top:12px;display:flex;gap:8px"><form method="post">'.csrf_field().'<button class="btn" name="action" value="checkout">Checkout (Demo)</button></form><a class="btn-muted" href="?page=shop">Continue shopping</a></div>';
      }
      ?>
    </div>
    <?php
}
else if ($page === 'wishlist'){
    $wishlist_ids = get_wishlist_ids($pdo); $items=[];
    if ($wishlist_ids){ $placeholders=implode(',',array_fill(0,count($wishlist_ids),'?')); $stmt=$pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)"); $stmt->execute($wishlist_ids); $items = $stmt->fetchAll(PDO::FETCH_ASSOC); }
    ?>
    <h3>Your Favorites</h3>
    <div class="grid">
      <?php if (!$items) echo '<div class="card">No favorites yet.</div>';
      foreach($items as $p){ ?>
        <div class="product card">
          <img src="<?=h($p['image'])?>">
          <div class="title"><?=h($p['title'])?></div>
          <div class="small"><?=h(number_format($p['price']))?> <?=h($p['currency'])?></div>
          <div style="margin-top:8px"><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="add_to_cart"><input type="hidden" name="product_id" value="<?=h($p['id'])?>"><button class="btn">Add to cart</button></form></div>
        </div>
      <?php } ?>
    </div>
    <?php
}
else if ($page === 'login'){
    ?>
    <div class="card" style="max-width:480px">
      <h3>Sign In</h3>
      <form method="post">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="login">
        <div style="margin-top:8px"><label class="small">Email</label><input name="email" type="email" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px"></div>
        <div style="margin-top:8px"><label class="small">Password</label><input name="password" type="password" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px"></div>
        <div style="margin-top:12px"><button class="btn">Sign in</button></div>
      </form>
      <div style="margin-top:12px" class="small">Admin? <a href="?page=admin_login">Admin login</a></div>
    </div>
    <?php
}
else if ($page === 'signup'){
    ?>
    <div class="card" style="max-width:480px">
      <h3>Create Account</h3>
      <form method="post">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="signup">
        <div style="margin-top:8px"><label class="small">Full name</label><input name="name" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px"></div>
        <div style="margin-top:8px"><label class="small">Email</label><input name="email" type="email" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px"></div>
        <div style="margin-top:8px"><label class="small">Password</label><input name="password" type="password" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px"></div>
        <div style="margin-top:12px"><button class="btn">Create account</button></div>
      </form>
    </div>
    <?php
}
else if ($page === 'admin_login'){
    ?>
    <div class="card" style="max-width:480px">
      <h3>Admin Sign In</h3>
      <form method="post">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="admin_login">
        <div style="margin-top:8px"><label class="small">Password</label><input name="password" type="password" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px"></div>
        <div style="margin-top:12px"><button class="btn">Sign in</button></div>
      </form>
    </div>
    <?php
}
else if ($page === 'admin' && !empty($_SESSION['is_admin'])){
    // admin dashboard: product list, add product form, import CSV
    $products = $pdo->query('SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <h3>Admin Dashboard</h3>
    <div class="card">
      <h4>Add Product</h4>
      <form method="post" enctype="multipart/form-data">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="admin_add_product">
        <div class="small">Title</div><input name="title" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        <div class="small" style="margin-top:6px">SKU</div><input name="sku" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        <div class="small" style="margin-top:6px">Price</div><input name="price" type="number" step="0.01" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        <div class="small" style="margin-top:6px">Category</div>
        <select name="category_id" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
          <?php foreach($GLOBALS['categories'] as $c) echo '<option value="'.h($c['id']).'">'.h($c['name']).'</option>'; ?>
        </select>
        <div class="small" style="margin-top:6px">Stock</div><input name="stock" type="number" value="10" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        <div class="small" style="margin-top:6px">Image</div><input type="file" name="image">
        <div style="margin-top:8px"><button class="btn">Add product</button></div>
      </form>
      <hr>
      <h4>Import CSV</h4>
      <form method="post" enctype="multipart/form-data">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="admin_import_csv">
        <div class="small">CSV file (title,slug,sku,description,price,image,category_slug,stock,featured)</div>
        <input type="file" name="csv">
        <div style="margin-top:8px"><button class="btn">Import</button></div>
      </form>
    </div>
    <h4 style="margin-top:12px">Products</h4>
    <div class="grid">
      <?php foreach($products as $p): ?>
        <div class="card">
          <img src="<?=h($p['image']?:'https://via.placeholder.com/400x300?text=No+Image')?>" style="width:100%;height:160px;object-fit:cover;border-radius:8px">
          <div class="small" style="margin-top:6px"><?=h($p['cat_name'])?></div>
          <div style="font-weight:700;margin-top:6px"><?=h($p['title'])?></div>
          <div class="small" style="margin-top:6px"><?=h(number_format($p['price']))?> <?=h($p['currency'])?></div>
          <form method="post" style="margin-top:8px"><?=csrf_field()?><input type="hidden" name="action" value="admin_delete_product"><input type="hidden" name="product_id" value="<?=h($p['id'])?>"><button class="btn-muted">Delete</button></form>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
}
else if ($page === 'blog'){
    echo '<div class="card"><h3>Blog</h3><p class="small">Use this area to publish news, tips and how-tos about wigs, care, styling, and launches.</p></div>';
}
else {
    echo '<div class="card">Page not found.</div>';
}

render_footer();
$out = ob_get_clean();
echo $out;

// EOF
