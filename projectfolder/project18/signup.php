<?php
require_once 'header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) $err = "Enter valid email & password (min 6 chars)";
    else {
        // check exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $err = "Email already registered";
        else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (email,password,first_name,last_name) VALUES (:e,:p,:f,:l)");
            $ins->execute([':e'=>$email,':p'=>$hash,':f'=>$first,':l'=>$last]);
            $_SESSION['flash'] = "Account created. Please sign in.";
            header('Location: signin.php'); exit;
        }
    }
}
?>
<main style="max-width:520px;margin:18px auto;padding:12px">
  <h2>Sign up</h2>
  <?php if(!empty($err)) echo "<div style='color:#b91c1c'>$err</div>"; ?>
  <form method="post">
    <label>First name <input name="first_name"></label><br><br>
    <label>Last name <input name="last_name"></label><br><br>
    <label>Email <input name="email" required></label><br><br>
    <label>Password <input name="password" type="password" required></label><br><br>
    <button class="btn" type="submit">Create account</button>
  </form>
</main>
<?php require_once 'footer.php'; ?>

