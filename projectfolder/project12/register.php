<?php
// register.php
session_start();
require_once __DIR__ . '/db.php';

$errors = array();
$old = array('full_name'=>'','email'=>'','phone'=>'');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // safe retrieval (no null-coalescing)
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    $old['full_name'] = $name;
    $old['email'] = isset($_POST['email']) ? $_POST['email'] : '';
    $old['phone'] = $phone;

    if (!$email) {
        $errors[] = "Please enter a valid email address.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($name === '') {
        $errors[] = "Full name is required.";
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (email, password_hash, full_name, phone, is_verified, created_at) VALUES (?, ?, ?, ?, 0, NOW())"
            );
            $stmt->execute(array($email, $hash, $name, $phone));
            $userId = $pdo->lastInsertId();

            // auto-login (simple)
            $_SESSION['user_id'] = (int)$userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;

            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            // Duplicate email handling
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
                $errors[] = "That email is already registered.";
            } else {
                // production: log error and show friendly message
                error_log("Register error: " . $e->getMessage());
                $errors[] = "An internal error occurred. Please try again later.";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; background:#f6f7f9; padding:30px; }
    .card { max-width:420px; margin:30px auto; background:#fff; padding:18px; border-radius:8px; box-shadow:0 4px 18px rgba(0,0,0,0.08); }
    h2 { margin-top:0; color:#e53935; }
    label { display:block; margin-bottom:10px; }
    input { width:100%; padding:10px; border-radius:6px; border:1px solid #e6e9ee; box-sizing:border-box; }
    .btn { margin-top:12px; width:100%; background:#007bff; color:white; border:0; padding:10px; border-radius:6px; cursor:pointer; font-weight:700; }
    .err { background:#fff0f0; color:#900; padding:8px; border-radius:6px; margin-bottom:10px; }
    .note { font-size:13px; color:#666; margin-top:12px; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Create account</h2>

    <?php if (!empty($errors)): ?>
      <?php foreach ($errors as $err): ?>
        <div class="err"><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="register.php" autocomplete="off">
      <label>
        Full name
        <input type="text" name="full_name" value="<?= htmlspecialchars($old['full_name']) ?>" required>
      </label>

      <label>
        Email
        <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required>
      </label>

      <label>
        Phone
        <input type="text" name="phone" value="<?= htmlspecialchars($old['phone']) ?>">
      </label>

      <label>
        Password (min 8 chars)
        <input type="password" name="password" required>
      </label>

      <button type="submit" class="btn">Register</button>
    </form>

    <p class="note">Already have an account? <a href="login.php">Sign in</a></p>
  </div>
</body>
</html>

