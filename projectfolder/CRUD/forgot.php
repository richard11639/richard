<?php
session_start();
include 'db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$message = "";
$showPasswordForm = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_email'])) {
        $email = trim($_POST['email']);
        $stmt = $mysqli->prepare("SELECT user_id FROM tbluser WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $_SESSION['reset_email'] = $email;
            $showPasswordForm = true;
        } else {
            $message = "Email not found.";
        }
        $stmt->close();
    }

    if (isset($_POST['reset_password']) && isset($_SESSION['reset_email'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $message = "Passwords do not match.";
            $showPasswordForm = true;
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE tbluser SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $_SESSION['reset_email']);
            if ($stmt->execute()) {
                $message = "Password reset successful.";
                unset($_SESSION['reset_email']);
                $showPasswordForm = false;
                header("Location: signin2.php");
            } else {
                $message = "Failed to reset password.";
                $showPasswordForm = true;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body { font-family: Arial; background-color: #f2f2f2; padding: 40px; }
        form { background-color: #fff; padding: 25px; max-width: 400px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc;
        }
        button { padding: 10px 15px; background-color: #28a745; border: none; color: white; cursor: pointer; }
        .message { color: red; text-align: center; }
    </style>
</head>
<body>

<form method="POST" action="">
    <h2>Forgot Password</h2>
    
    <?php if (!empty($message)): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if (!$showPasswordForm && !isset($_SESSION['reset_email'])): ?>
        <label for="email">Enter your email:</label>
        <input type="email" name="email" required>
        <button type="submit" name="verify_email">Verify Email</button>
    <?php else: ?>
        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" required>
        
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" required>
        
        <button type="submit" name="reset_password">Reset Password</button>
    <?php endif; ?>
</form>

</body>
</html>