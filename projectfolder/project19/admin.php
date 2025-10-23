<?php
// admin.php — admin area for product CRUD (must be admin)
require_once __DIR__ . '/functions.php';
require_admin();
$csrf = csrf_token();

// handle create / edit / delete
$action = $_POST['action'] ?? $_GET['action'] ?? null;

function validate_image_upload(array $file): array {
    // returns [ok, filename or error]
    if ($file['error'] !== UPLOAD_ERR_OK) return [false, 'Upload error'];
    $allowed = ['image/jpeg','image/png','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return [false, 'Invalid image type'];
    if ($file['size'] > 2 * 1024 * 1024) return [false, 'Image too large (max 2MB)'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe = bin2hex(random_bytes(8)) . '.' . $ext;
    return [true, $safe];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf'] ?? '')) {
        $err = "Invalid CSRF";
    } else {
        if ($action === 'create' || $action === 'edit') {
            $title = trim($_POST['title'] ?? '');
            $slug = preg_replace('/[^a-z0-9\-]+/i','-', strtolower(trim($_POST['slug'] ?? '')));
            if (!$slug) $slug = strtolower(preg_replace('/\s+/', '-', $title));
            $description = trim($_POST['description'] ?? '');
            $price = (float)$_POST['price'];
            $category = trim($_POST['category'] ?? 'Wigs');
            $stock = max(0, (int)$_POST['stock']);
            if ($title === '' || $price <= 0) $err = "Missing title or invalid price";
            else {
                // handle image if provided
                $imageFilename = null;
                if (!empty($_FILES['image']) && $_FILES['image']['tmp_name']) {
                    [$ok, $payload] = validate_image_upload($_FILES['image']);
                    if (!$ok) $err = $payload;
                    else {
                        $dest = __DIR__ . '/images/' . $payload;
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) $err = "Failed to store image";
                        else $imageFilename = $payload;
                    }
                }
                if (empty($err)) {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO products (title, slug, description, price, image, category, stock) VALUES (?,?,?,?,?,?,?)");
                        $stmt->execute([$title, $slug, $description, $price, $imageFilename, $category, $stock]);
                        app_log_action("Admin created product: $title");
                        header('Location: admin.php?ok=created'); exit;
                    } else {
                        $id = (int)$_POST['id'];
                        // if new image uploaded, use that; else keep existing
                        if ($imageFilename) {
                            $stmt = $pdo->prepare("UPDATE products SET title=?, slug=?, description=?, price=?, image=?, category=?, stock=? WHERE id=?");
                            $stmt->execute([$title, $slug, $description, $price, $imageFilename, $category, $stock, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE products SET title=?, slug=?, description=?, price=?, category=?, stock=? WHERE id=?");
                            $stmt->execute([$title, $slug, $description, $price, $category, $stock, $id]);
                        }
                        app_log_action("Admin updated product id={$id}");
                        header('Location: admin.php?ok=updated'); exit;
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            app_log_action("Admin deleted product id={$id}");
            header('Location: admin.php?ok=deleted'); exit;
        }
    }
}

// list products
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
$csrf = csrf_token();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Admin — Products</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="container">
  <h1>Admin — Products</h1>
  <?php if (!empty($err)): ?><div class="error"><?= e($err) ?></div><?php endif; ?>
  <?php if (!empty($_GET['ok'])): ?><div class="success">Action completed</div><?php endif; ?>

  <h2>Create product</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="create">
    <label>Title <input name="title" required></label>
    <label>Slug <input name="slug"></label>
    <label>Description <textarea name="description"></textarea></label>
    <label>Price <input name="price" type="number" step="0.01" required></label>
    <label>Category <input name="category" value="Wigs"></label>
    <label>Stock <input name="stock" type="number" value="1" min="0"></label>
    <label>Image <input name="image" type="file" accept="image/*"></label>
    <button class="btn" type="submit">Create</button>
  </form>

  <h2>Existing products</h2>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Title</th><th>Stock</th><th>Price</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td><?= e($p['title']) ?></td>
          <td><?= (int)$p['stock'] ?></td>
          <td>₦<?= number_format($p['price'],0) ?></td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn-outline" onclick="return confirm('Delete?')">Delete</button>
            </form>
            <button onclick="openEdit(<?= (int)$p['id'] ?>)">Edit</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <script>
    function openEdit(id){
      // quick redirect to edit (we'll reuse admin.php?action=edit&id=)
      location.href = 'admin.php?edit=' + id;
    }
  </script>

  <?php
  // show edit form if requested
  if (!empty($_GET['edit'])):
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();
    if ($prod):
  ?>
    <h2>Edit: <?= e($prod['title']) ?></h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
      <label>Title <input name="title" value="<?= e($prod['title']) ?>" required></label>
      <label>Slug <input name="slug" value="<?= e($prod['slug']) ?>"></label>
      <label>Description <textarea name="description"><?= e($prod['description']) ?></textarea></label>
      <label>Price <input name="price" type="number" step="0.01" value="<?= (float)$prod['price'] ?>" required></label>
      <label>Category <input name="category" value="<?= e($prod['category']) ?>"></label>
      <label>Stock <input name="stock" type="number" value="<?= (int)$prod['stock'] ?>" min="0"></label>
      <label>Replace image <input name="image" type="file" accept="image/*"></label>
      <button class="btn" type="submit">Save</button>
    </form>
  <?php endif; endif; ?>

</div></body></html>
