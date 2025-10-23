<?php
// signup.php
include "db.php";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST["firstname"]);
    $lastname  = trim($_POST["lastname"]);
    $email     = trim($_POST["email"]);
    $phone     = trim($_POST["phone"]);
    $password  = password_hash($_POST["password"], PASSWORD_DEFAULT); // hash password

    $sql = "INSERT INTO users (firstname, lastname, email, phone, password)  VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $phone, $password);

    if ($stmt->execute()) {
        header("Location: signin.php");
        exit();
    } else {
        $msg = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - RIC Restaurant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #ff9a9e, #fad0c4);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin:0;
    }
    .signup-container {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #e53935;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    input {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }
    input:focus {
      border-color: #e53935;
      outline: none;
      box-shadow: 0 0 4px #e53935;
    }
    button {
      background: #e53935;
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #c62828;
    }
    .msg {
      text-align: center;
      color: red;
      margin-bottom: 10px;
    }
    .login-link {
      text-align: center;
      margin-top: 10px;
      font-size: 14px;
    }
    .login-link a {
      color: #e53935;
      text-decoration: none;
      font-weight: bold;
    }
    .login-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="signup-container">
    <h2>Create Account</h2>
    <?php if ($msg): ?>
      <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="text" name="firstname" placeholder="First Name" required>
      <input type="text" name="lastname" placeholder="Last Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="text" name="phone" placeholder="Phone Number" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Sign Up</button>
    </form>
    <div class="login-link">
      Already have an account? <a href="signin.php">Sign In</a>
    </div>
  </div>
</body>
</html>
