<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start the session
include 'auth.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

    // Prepare and execute SQL statement safely
    $stmt = $mysql->prepare("INSERT INTO tbluser (username, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $phone, $password);

    if ($stmt->execute()) {
        // Get the user ID from the last insert
        $userId = $mysql->insert_id;
        
        // Store the username and ID in the session
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $userId;

        // Redirect to the welcome page
        header("Location: blog.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signup</title>
</head>
<body>
    <h2>Sign Up</h2>
    <form method="POST" action="signup.php">
        Username: <input type="text" name="username" required><br>
        Email: <input type="email" name="email" required><br>
        Phone number: <input type="text" name="phone" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="Sign Up">
    </form>
    <p>Already have an account? <a href="signin.php">Sign In</a></p>
</body>
</html>