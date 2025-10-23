<?php
// config.php — DB connection, secure session settings, auto-create tables, logging
declare(strict_types=1);

// --- Session hardening BEFORE session_start() ---
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => $secure,           // set true when using HTTPS
    'httponly' => true,
    'samesite' => 'Lax',           // or 'Strict' if you prefer
]);
if (session_status() === PHP_SESSION_NONE) session_start();

// regenerate occasionally to prevent fixation
if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = time();
}

// --- DB credentials: change these ---
$dbHost = '127.0.0.1';
$dbName = 'wigs_db';
$dbUser = 'root';
$dbPass = '';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // try to create DB if privileged
    try {
        $tmp = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $tmp->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $tmp = null;
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e2) {
        die("DB connection failed: " . htmlspecialchars($e2->getMessage()));
    }
}

// --- auto-create tables with safe schema ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255) DEFAULT NULL,
  category VARCHAR(100) DEFAULT 'Wigs',
  stock INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_wish (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_cart (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  endpoint VARCHAR(100) NOT NULL,
  hits INT DEFAULT 1,
  last_hit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_ip_endpoint (ip, endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// create logs dir if missing
if (!is_dir(__DIR__ . '/logs')) mkdir(__DIR__ . '/logs', 0755, true);

/**
 * Simple logger
 */
function app_log(string $msg) {
    $file = __DIR__ . '/logs/app.log';
    $time = date('Y-m-d H:i:s');
    @file_put_contents($file, "[$time] $msg\n", FILE_APPEND | LOCK_EX);
}

// seed minimal admin & products if no users/products
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ((int)$stmt->fetchColumn() === 0) {
    $hash = password_hash('adminpass', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)")
        ->execute(['Admin','admin@example.com',$hash,'admin']);
    app_log("Seeded initial admin (admin@example.com / adminpass) — change password ASAP.");
}
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
if ((int)$stmt->fetchColumn() === 0) {
    $ins = $pdo->prepare("INSERT INTO products (title, slug, description, price, image, category, stock) VALUES (?,?,?,?,?,?,?)");
    $samples = [
        ['Winner | Synthetic Wig (Basic Cap)', 'winner-synthetic-wig', 'A pixie wig with barely waved layers that’s perfect for every occasion.', 180110.00, 'sample1.jpg', 'Synthetic', 10],
        ['Naomi | HF Synthetic Lace Front Wig', 'naomi-hf-synthetic', 'Medium Gold Blonde & Pale Natural Blonde Blend.', 40899.00, 'sample2.jpg', 'Synthetic', 8],
        ['Radiant Beauty | Gabor Long Wig', 'radiant-beauty', 'Rich dark chestnut with coffee highlights.', 52099.00, 'sample3.jpg', 'Human Hair', 5],
    ];
    foreach ($samples as $s) $ins->execute($s);
    app_log("Seeded sample products.");
}
