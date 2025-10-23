<?php
// feedback.php
session_start();

// auth2.php should create a mysqli $conn and start/validate session.
// Adjust the include path if your file is named differently (e.g. db.php).
require_once 'db.php';

// Only allow users with ID 1 or 3
$allowed_user_ids = [1, 3];
if (!isset($_SESSION['user_id']) || !in_array((int)$_SESSION['user_id'], $allowed_user_ids, true)) {
    // Not allowed — redirect to homepage (change this if you prefer a 403 page)
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $food_quality = (int)($_POST['food_quality'] ?? 5);
    $delivery_quality = (int)($_POST['delivery_quality'] ?? 5);
    $taste = (int)($_POST['taste'] ?? 5);
    $customer_care = (int)($_POST['customer_care'] ?? 5);
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors[] = "Name is required.";
    }

    // Basic rating bounds check
    foreach (['food_quality'=>$food_quality,'delivery_quality'=>$delivery_quality,'taste'=>$taste,'customer_care'=>$customer_care] as $k=>$v) {
        if ($v < 1 || $v > 5) $errors[] = ucfirst(str_replace('_',' ',$k)) . " must be between 1 and 5.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO feedback (name,email,food_quality,delivery_quality,taste,customer_care,message, created_at) VALUES (?,?,?,?,?,?,?, NOW())");
        if ($stmt === false) {
            $errors[] = "DB prepare error: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('ssiiiis', $name, $email, $food_quality, $delivery_quality, $taste, $customer_care, $message);
            $ok = $stmt->execute();
            if ($ok) {
                $success = true;
                // Optionally clear POST variables so form shows blank after success
                $_POST = [];
            } else {
                $errors[] = "DB error: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Leave Feedback — RIC Restaurant</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Inter,system-ui,Arial,sans-serif;background:#f6f7f9;padding:24px}
    .container{max-width:800px;margin:0 auto}
    form{background:#fff;padding:18px;border:1px solid #e6e9ee;border-radius:10px}
    label{display:block;margin-top:12px;font-weight:700}
    input[type=text],input[type=email],textarea,select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6e9ee}
    .row{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
    .row > div{min-width:0}
    .btn{background:#e53935;color:#fff;padding:8px 12px;border-radius:8px;border:0;margin-top:12px}
    .btn.ghost{background:#fff;color:#e53935;border:1px solid #e53935}
    .small{color:#6b7580;font-size:13px}
    .notice{padding:10px;border-radius:8px;margin-bottom:12px}
    .notice.success{background:#e6ffef;border:1px solid #b6f0d0;color:#064e2a}
    .notice.error{background:#fff4f4;border:1px solid #f1c2c2;color:#6c0b0b}
    @media(max-width:600px){ .row{grid-template-columns:repeat(2,1fr);} }
    header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .nav-btns a{margin-left:8px;text-decoration:none;padding:8px 12px;border-radius:8px;border:1px solid #e6e9ee;color:#333;background:#fff}
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Leave Feedback</h1>
      <div class="nav-btns">
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
      </div>
    </header>

    <?php if ($success): ?>
      <div class="notice success">
        Thanks — your feedback has been submitted.
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <?php foreach ($errors as $err): ?>
        <div class="notice error"><?php echo htmlspecialchars($err); ?></div>
      <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="feedback.php" novalidate>
      <label for="name">Your name *</label>
      <input id="name" name="name" type="text" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

      <label for="email">Email (optional)</label>
      <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

      <label>Ratings (1 = poorest, 5 = best)</label>
      <div class="row">
        <div>
          <label class="small">Food quality</label>
          <select name="food_quality">
            <?php for ($i=5;$i>=1;$i--): $sel = (isset($_POST['food_quality']) && (int)$_POST['food_quality'] === $i) ? 'selected' : ''; ?>
              <option value="<?= $i ?>" <?= $sel ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="small">Delivery quality</label>
          <select name="delivery_quality">
            <?php for ($i=5;$i>=1;$i--): $sel = (isset($_POST['delivery_quality']) && (int)$_POST['delivery_quality'] === $i) ? 'selected' : ''; ?>
              <option value="<?= $i ?>" <?= $sel ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="small">Taste</label>
          <select name="taste">
            <?php for ($i=5;$i>=1;$i--): $sel = (isset($_POST['taste']) && (int)$_POST['taste'] === $i) ? 'selected' : ''; ?>
              <option value="<?= $i ?>" <?= $sel ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="small">Customer care</label>
          <select name="customer_care">
            <?php for ($i=5;$i>=1;$i--): $sel = (isset($_POST['customer_care']) && (int)$_POST['customer_care'] === $i) ? 'selected' : ''; ?>
              <option value="<?= $i ?>" <?= $sel ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <label for="message">Message (optional)</label>
      <textarea id="message" name="message" rows="5"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>

      <button class="btn" type="submit">Submit feedback</button>
      <a class="btn ghost" href="index.php" style="text-decoration:none;display:inline-block;margin-left:8px">Cancel</a>
    </form>
  </div>
</body>
</html>

