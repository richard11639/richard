<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'auth.php'; // Database connection

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM tbluser WHERE email = ?";
    $stmt = $mysql->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['user_id'];
            header("Location: trading2.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "No user found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Trading App - Sign In</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body {
          background: linear-gradient(135deg, #0d1b2a, #1b263b, #415a77);
          min-height: 100vh;
          display: flex;
          justify-content: center;
          align-items: center;
          color: #fff;
      }
      .card {
          border-radius: 20px;
          box-shadow: 0 4px 20px rgba(0,0,0,0.3);
          padding: 30px;
          background-color: #ffffff;
          color: #000;
          width: 400px;
      }
      .btn-primary {
          background-color: #0077b6;
          border: none;
      }
      .btn-primary:hover {
          background-color: #023e8a;
      }
      .social-btn i {
          font-size: 18px;
      }
  </style>
</head>
<body>
  <div class="card">
    <h3 class="text-center mb-4">🔑 Trading Login</h3>
    <form method="POST" action="signin.php">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required placeholder="Enter your email">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Enter your password">
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-3">
            <a href="forgot.php" class="text-decoration-none">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100">Sign In</button>

        <p class="text-center mt-3">Not a member? <a href="signup.php" class="text-decoration-none">Register</a></p>

        <hr>
        <p class="text-center mb-2">Or sign in with</p>
        <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-outline-dark social-btn"><i class="fab fa-google"></i></button>
            <button type="button" class="btn btn-outline-dark social-btn"><i class="fab fa-facebook-f"></i></button>
            <button type="button" class="btn btn-outline-dark social-btn"><i class="fab fa-twitter"></i></button>
            <button type="button" class="btn btn-outline-dark social-btn"><i class="fab fa-github"></i></button>
        </div>
    </form>
  </div>

  <script src="https://kit.fontawesome.com/yourkit.js" crossorigin="anonymous"></script>
</body>
</html>
