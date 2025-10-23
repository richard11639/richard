<?php
session_start();
include('db.php');

$message = '';

if(isset($_POST['signup'])){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // check if username/email exists
    $stmt = $mysql->prepare("INSERT INTO tblusers (username=, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $phone, $password);

    if($stmt->execute()) {
        $message = "Username or Email already exists!";
    } else {
        $stmt = $mysql->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)");
        if($stmt->execute([$username,$email,$password])){
            header("Location: signin.php?msg=signup_success");
            exit;
        } else {
            $message = "Signup failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up</title>
<style>
body{font-family:sans-serif;background:#071025;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
.form-container{background:#0c1624;padding:30px;border-radius:12px;width:300px;}
input{width:100%;padding:10px;margin:8px 0;border-radius:8px;border:none;}
button{width:100%;padding:10px;background:#3ea0ff;color:#fff;border:none;border-radius:8px;font-weight:bold;cursor:pointer;}
.message{color:#ff6b6b;margin-bottom:10px;text-align:center;}
</style>
</head>
<body>
<div class="form-container">
<h2>Sign Up</h2>
<?php if($message) echo "<div class='message'>$message</div>"; ?>
<form method="POST">
<input type="text" name="username" placeholder="Username" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="signup">Sign Up</button>
</form>
<p style="text-align:center;margin-top:10px;"><a href="signin.php" style="color:#3ea0ff;text-decoration:none;">Already have an account? Sign In</a></p>
</div>
</body>
</html>


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