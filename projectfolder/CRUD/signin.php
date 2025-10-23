<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start the session
include 'auth.php'; // Include database connection

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the user exists
    $sql = "SELECT * FROM tbluser WHERE email = ?";
    $stmt = $mysql->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Store username and user_id in session
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['user_id'];

            // Redirect to blog page
            header("Location: blog.php");
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
    <title>Sign In</title>
</head>
<body>
    <h2>Sign In</h2>
    <form method="POST" action="signin.php">
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="Sign In">
    </form>
    <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
    <p><a href="forgot.php">Forgot Password</a></p>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</body>
</html>