<?php
// functions.php - helpers and table creation
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

// money helper
function money($n){ return '₦' . number_format((float)$n, 0); }

// create tables if not exist
function ensure_tables(PDO $pdo){
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(255) UNIQUE,
      password VARCHAR(255),
      first_name VARCHAR(100),
      last_name VARCHAR(100),
      wallet DECIMAL(12,2) DEFAULT 0,
      is_admin TINYINT(1) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      description TEXT,
      price DECIMAL(12,2) NOT NULL DEFAULT 0,
      image VARCHAR(255),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT,
      subtotal DECIMAL(12,2),
      delivery_fee DECIMAL(12,2),
      tax DECIMAL(12,2),
      total DECIMAL(12,2),
      order_type VARCHAR(32),
      pickup_time VARCHAR(64),
      delivery_info TEXT,
      contact_info TEXT,
      payment_method VARCHAR(50),
      payment_proof VARCHAR(255),
      status VARCHAR(50) DEFAULT 'processing',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS order_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id INT,
      product_id INT,
      qty INT,
      price DECIMAL(12,2),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

ensure_tables($pdo);

// current_user
function current_user(){
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    static $u = null;
    if ($u !== null) return $u;
    $stmt = $pdo->prepare("SELECT id,email,first_name,last_name,wallet,is_admin FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    return $u ?: null;
}

// cart helpers: session-based snapshot: $_SESSION['cart'][id] = ['qty'=>int,'title'=>..., 'price'=>float,'image'=>...]
function cart_count(){
    $cart = $_SESSION['cart'] ?? [];
    $c = 0;
    foreach($cart as $id=>$it){
        $c += intval($it['qty'] ?? $it);
    }
    return $c;
}

function cart_subtotal($pdo=null){
    $cart = $_SESSION['cart'] ?? [];
    $subtotal = 0.0;
    if ($pdo && !empty($cart)){
        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0,count($ids),'?'));
        $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $db = [];
        foreach($stmt->fetchAll() as $r) $db[$r['id']] = $r['price'];
        foreach($cart as $pid => $it){
            $pid = intval($pid);
            $qty = intval($it['qty'] ?? $it);
            $price = isset($db[$pid]) ? floatval($db[$pid]) : floatval($it['price'] ?? 0);
            $subtotal += $price * $qty;
        }
    } else {
        foreach($cart as $it){
            $qty = intval($it['qty'] ?? $it);
            $price = floatval($it['price'] ?? 0);
            $subtotal += $price * $qty;
        }
    }
    return round($subtotal,2);
}
