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
  <title>Restaurant Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f8f9fa;
      color: #333;
    }

    /* Top Navbar */
    nav {
      background: #e74c3c;
      padding: 15px 30px;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    nav h1 {
      margin: 0;
      font-size: 1.5rem;
    }
    nav ul {
      list-style: none;
      display: flex;
      margin: 0;
      padding: 0;
    }
    nav ul li {
      margin-left: 20px;
      cursor: pointer;
    }

    .container {
      max-width: 1000px;
      margin: 30px auto;
      padding: 20px;
    }

    .success {
      background: #d4edda;
      color: #155724;
      padding: 12px;
      border-radius: 5px;
      margin-bottom: 20px;
      display: none;
    }

    .card {
      background: #fff;
      padding: 20px;
      margin-bottom: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .card h3 {
      margin-top: 0;
      margin-bottom: 15px;
      color: #e74c3c;
    }

    /* Form */
    form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }
    form input {
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      width: 100%;
    }
    form button {
      grid-column: span 2;
      padding: 12px;
      border: none;
      border-radius: 6px;
      background: #27ae60;
      color: #fff;
      font-size: 1rem;
      cursor: pointer;
    }
    form button:hover {
      background: #1f8a4a;
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    table th, table td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: center;
    }
    table th {
      background: #e74c3c;
      color: #fff;
    }
    table tr:nth-child(even) {
      background: #f2f2f2;
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <nav>
    <h1>🍴 Restaurant Dashboard</h1>
    <ul>
      <li>Dashboard</li>
      <li>Add Member</li>
      <li>Members List</li>
       <a href="restaurant.php"> <li>home</a><li>
    </ul>
  </nav>

  <div class="container">
    <!-- Success Message -->
    <div class="success" id="successMessage">✅ Member added successfully!</div>

    <!-- Add Member -->
    <div class="card">
      <h3>Add New Member</h3>
      <form id="memberForm">
        <input type="text" id="name" placeholder="Full Name" required>
        <input type="email" id="email" placeholder="Email Address" required>
        <input type="text" id="phone" placeholder="Phone Number" required>
        <button type="submit">Add Member</button>
      </form>
    </div>

    <!-- Members List -->
    <div class="card">
      <h3>Members List</h3>
      <table id="membersTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Time Added</th>
          </tr>
        </thead>
        <tbody>
          <!-- Data goes here -->
        </tbody>
      </table>
    </div>
  </div>

  <script>
    const form = document.getElementById("memberForm");
    const tableBody = document.querySelector("#membersTable tbody");
    const successMessage = document.getElementById("successMessage");

    let count = 0;

    form.addEventListener("submit", function(e) {
      e.preventDefault();

      // Collect inputs
      const name = document.getElementById("name").value.trim();
      const email = document.getElementById("email").value.trim();
      const phone = document.getElementById("phone").value.trim();
      const time = new Date().toLocaleString();

      if (name && email && phone) {
        count++;

        // Create table row
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${count}</td>
          <td>${name}</td>
          <td>${email}</td>
          <td>${phone}</td>
          <td>${time}</td>
        `;
        tableBody.appendChild(row);

        // Reset form
        form.reset();

        // Show success message
        successMessage.style.display = "block";
        setTimeout(() => {
          successMessage.style.display = "none";
        }, 2500);
      }
    });
  </script>

</body>
</html>
