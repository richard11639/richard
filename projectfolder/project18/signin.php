<?php
require_once 'header.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id,password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($pass, $u['password'])){
        $_SESSION['user_id'] = $u['id'];
        // redirect after login if set
        $redir = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: '.$redir); exit;
    } else $err = "Invalid credentials";
}
?>
<main style="max-width:420px;margin:18px auto;padding:12px">
  <h2>Sign in</h2>
  <?php if(!empty($err)) echo "<div style='color:#b91c1c'>$err</div>"; ?>
  <form method="post">
    <label>Email <input name="email" required></label><br><br>
    <label>Password <input name="password" type="password" required></label><br><br>
    <button class="btn" type="submit">Sign in</button>
  </form>
  <p>Don't have an account? <a href="signup.php">Sign up</a></p>
</main>
<?php require_once 'footer.php'; ?>
