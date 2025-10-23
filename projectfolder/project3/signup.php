<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); 
include 'auth.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $mysql->prepare("INSERT INTO tbluser (username, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $phone, $password);

    if ($stmt->execute()) {
        $userId = $mysql->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $userId;
        header("Location: trading2.php");
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
    <title>Trading Sign Up</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0f172a;
            font-family: 'Segoe UI', sans-serif;
            color: #f8fafc;
        }
        .card {
            border-radius: 15px;
            background: #1e293b;
            padding: 25px;
            box-shadow: 0px 8px 20px rgba(0,0,0,0.5);
        }
        .btn-custom {
            background: linear-gradient(90deg, #06b6d4, #3b82f6);
            border: none;
            color: white;
            font-weight: bold;
        }
        .btn-custom:hover {
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
        }
        .form-label {
            color: #cbd5e1;
        }
        a {
            color: #06b6d4;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center vh-100">
        <div class="card col-md-5">
            <h2 class="text-center mb-3">🚀 Create Trading Account</h2>
            <p class="text-center text-secondary">Sign up and start your trading journey</p>
            
            <form method="POST" action="signup.php">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="Enter phone number" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-custom w-100">Sign Up</button>
            </form>

            <p class="text-center mt-3">
                Already have an account? <a href="signin.php">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>
