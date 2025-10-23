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
   <a href="hotel.php">home</a>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", sans-serif;
      background: #f1f3f6;
      color: #333;
    }

    /* Header */
    header {
      background: #34495e;
      padding: 15px 30px;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    header h1 {
      margin: 0;
    }

    /* Dashboard Layout */
    .dashboard {
      display: flex;
      flex-direction: column;
      max-width: 1100px;
      margin: 20px auto;
      gap: 20px;
    }

    .card {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .card h3 {
      margin-top: 0;
      margin-bottom: 15px;
      color: #34495e;
    }

    /* Success Message */
    .success {
      background: #d4edda;
      color: #155724;
      padding: 12px;
      border-radius: 6px;
      display: none;
      margin-bottom: 15px;
    }

    /* Form */
    form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }
    form input, form select {
      padding: 10px;
      border: 1px solid #ccc;
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
    }
    th, td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: center;
    }
    th {
      background: #34495e;
      color: #fff;
    }
    tr:nth-child(even) {
      background: #f9f9f9;
    }
  </style>
</head>
<body>

  <!-- Top Bar -->
  <header>
    <h1>🏨 Hotel Dashboard</h1>
    <div>Admin Panel</div>
  </header>

  <!-- Dashboard -->
  <div class="dashboard">
    <!-- Success Message -->
    <div class="success" id="successMessage">✅ Member added successfully!</div>

    <!-- Add Guest -->
    <div class="card">
      <h3>Add Guest</h3>
      <form id="guestForm">
        <input type="text" id="name" placeholder="Full Name" required>
        <input type="email" id="email" placeholder="Email Address" required>
        <input type="text" id="phone" placeholder="Phone Number" required>
        <select id="room" required>
          <option value="">-- Select Room Type --</option>
          <option>Single</option>
          <option>Double</option>
          <option>Suite</option>
          <option>Deluxe</option>
        </select>
        <button type="submit">Add Guest</button>
      </form>
    </div>

    <!-- Guest List -->
    <div class="card">
      <h3>Guest List</h3>
      <table id="guestTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Room Type</th>
            <th>Time Added</th>
          </tr>
        </thead>
        <tbody>
          <!-- Guests will appear here -->
        </tbody>
      </table>
    </div>
  </div>

  <script>
    const form = document.getElementById("guestForm");
    const tableBody = document.querySelector("#guestTable tbody");
    const successMessage = document.getElementById("successMessage");

    let count = 0;

    form.addEventListener("submit", function(e) {
      e.preventDefault();

      const name = document.getElementById("name").value.trim();
      const email = document.getElementById("email").value.trim();
      const phone = document.getElementById("phone").value.trim();
      const room = document.getElementById("room").value;
      const time = new Date().toLocaleString();

      if (name && email && phone && room) {
        count++;

        // Create table row
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${count}</td>
          <td>${name}</td>
          <td>${email}</td>
          <td>${phone}</td>
          <td>${room}</td>
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
