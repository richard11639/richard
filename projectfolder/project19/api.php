<?php
// api.php — JSON API for add_to_cart, toggle_wishlist, cart_count with rate-limiting & validation
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid request']); exit;
}
$action = $data['action'] ?? '';
$csrf = $data['csrf'] ?? '';

// basic rate limit per endpoint
if (!rate_limit($action, 60, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    echo json_encode(['error' => 'Invalid CSRF', 'code' => 'csrf']); exit;
}

switch ($action) {
    case 'add_to_cart':
        $pid = (int)($data['product_id'] ?? 0);
        $qty = max(1, (int)($data['qty'] ?? 1));
        if ($pid <= 0) { echo json_encode(['error'=>'Invalid product']); exit; }

        // check product exists & stock
        $stmt = $pdo->prepare("SELECT id, stock, price FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $p = $stmt->fetch();
        if (!$p) { echo json_encode(['error'=>'Product not found']); exit; }
        if ($p['stock'] <= 0) { echo json_encode(['error'=>'Out of stock']); exit; }

        $user = current_user();
        if ($user) {
            // insert or update cart_items while enforcing stock limit
            $stmt2 = $pdo->prepare("SELECT qty FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt2->execute([$user['id'], $pid]);
            $row = $stmt2->fetch();
            if ($row) {
                $newQty = min($p['stock'], $row['qty'] + $qty);
                $pdo->prepare("UPDATE cart_items SET qty = ? WHERE user_id = ? AND product_id = ?")
                    ->execute([$newQty, $user['id'], $pid]);
            } else {
                $ins = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, qty) VALUES (?,?,?)");
                $ins->execute([$user['id'], $pid, min($qty, $p['stock'])]);
            }
        } else {
            // session cart
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            $_SESSION['cart'][$pid] = min($p['stock'], ($_SESSION['cart'][$pid] ?? 0) + $qty);
        }
        $count = cart_get_count();
        app_log_action("add_to_cart pid={$pid} qty={$qty} user=" . (current_user()['id'] ?? 'guest'));
        echo json_encode(['success'=>true, 'cart_count'=>$count]);
        exit;

    case 'toggle_wishlist':
        if (!current_user()) {
            echo json_encode(['error'=>'Login required', 'need_login'=>true]); exit;
        }
        $pid = (int)($data['product_id'] ?? 0);
        if ($pid <= 0) { echo json_encode(['error'=>'Invalid product']); exit; }
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        if (!$stmt->fetch()) { echo json_encode(['error'=>'Product not found']); exit; }
        $u = current_user();
        $stmt2 = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt2->execute([$u['id'], $pid]);
        $row = $stmt2->fetch();
        if ($row) {
            $pdo->prepare("DELETE FROM wishlist WHERE id = ?")->execute([$row['id']]);
            app_log_action("wishlist removed pid={$pid} user={$u['id']}");
            echo json_encode(['success'=>true, 'message'=>'Removed from wishlist']);
        } else {
            $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?,?)")->execute([$u['id'], $pid]);
            app_log_action("wishlist added pid={$pid} user={$u['id']}");
            echo json_encode(['success'=>true, 'message'=>'Added to wishlist']);
        }
        exit;

    case 'cart_count':
        echo json_encode(['success'=>true, 'cart_count'=>cart_get_count()]);
        exit;

    default:
        echo json_encode(['error'=>'Unknown action']); exit;
}
