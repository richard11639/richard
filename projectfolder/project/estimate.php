<?php
session_start();
include 'auth.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch blog posts including blog_title
$sql = "SELECT 
            tblblog.blog_id,
            tblblog.blog_title,
            tblblog.blog_content, 
            tblblog.date_posted, 
            tbluser.user_id AS posted_by, 
            tbluser.username AS posted_by_username
        FROM tblblog 
        JOIN tbluser ON tblblog.posted_by = tbluser.user_id 
        WHERE tblblog.blog_status = 'active'
        ORDER BY tblblog.date_posted DESC";

$result = $mysql->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!DOCTYPE html>   
  <h4><a href="realestate.php">REALESTATE</a></h4>
        <img src="images/image28.jpg" alt="">
</Card>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart House Estimate</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container mt-5">
    <h1 class="text-center">House Estimate Platform</h1>
    <form id="estimateForm" method="POST" action="submit.php">
      <div class="row mb-3">
        <div class="col">
          <label for="location" class="form-label">Location</label>
          <select class="form-select" id="location" name="location">
            <option value="ikeja">Ikeja, Lagos</option>
            <option value="lekki">Lekki, Lagos</option>
            <option value="abuja">Gwarinpa, Abuja</option>
            <option value="abuja">Gwarinpa, Abuja</option>
            <option value="abuja">Gwarinpa, Abuja</option>
 

          </select>
        </div>
        <div class="col">
          <label for="bedrooms" class="form-label">Bedrooms</label>
          <input type="number" class="form-control" id="bedrooms" name="bedrooms" required>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col">
          <label for="bathrooms" class="form-label">Bathrooms</label>
          <input type="number" class="form-control" id="bathrooms" name="bathrooms" required>
        </div>
        <div class="col">
          <label for="features" class="form-label">Smart Features</label>
          <select class="form-select" id="features" name="features">
            <option value="basic">Basic</option>
            <option value="modern">Modern</option>
            <option value="luxury">Luxury</option>
          </select>
        </div>
      </div>
      <a href="estimateprice.php" class="btn btn-primary">estimateprice</a>
    </form>

  <script src="script.js"></script>
    
</body>
</html>
