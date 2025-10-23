<?php
include 'auth2.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$checkin = $_POST['checkin'];
$checkout = $_POST['checkout'];
$room_type = $_POST['room_type'];
$guests = $_POST['guests'];
$extras = isset($_POST['extras']) ;
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$member_no = $_POST['member_no'];
$state = $_POST['state'];
$city = $_POST['city'];
$country = $_POST['country'];
$payment_method = $_POST['payment_method'];
$total_price = $_POST['total_price'];

$sql = "INSERT INTO booking
(checkin, checkout, room_type, guests, extras, first_name, last_name, email, member_no, state, city, country, payment_method, total_price) 
VALUES 
('$checkin', '$checkout', '$room_type', '$guests', '$extras', '$first_name', '$last_name', '$email', '$member_no', '$state', '$city', '$country', '$payment_method', '$total_price')";

if ($conn->query($sql) === TRUE) {
    echo "Booking successful! Thank you, $first_name.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <a href="index6.php">🏠 Home</a>
  <title>Book Your Stay — Ric Hotel & Suites</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>Book Your Stay — Ric Hotel & Suites</h2>
  <form method="POST" action=""> 
    <label>Check-in</label>
    <input type="date" name="checkin" required><br>

    <label>Check-out</label>
    <input type="date" name="checkout" required><br>

    <label>Room Type</label>
    <select name="room_type" required>
      <option value="Ric room(single)" data-price="45000">Ric room(single) — ₦45,000/night</option>
       <option value="Ric room(2room)" data-price="60000">Ric room(2 room) — ₦60,000/night</option>
    </select><br>

    <label>Guests</label>
    <input type="number" name="guests" min="1" value="1" required><br>

    <h3>Extras (Recreation Services)</h3>
    <input type="checkbox" name="extras" value="Gymnasium — 20000"> Gymnasium — ₦20,000 <br>
    <input type="checkbox" name="extras" value="Swimming Pool — 10000"> Swimming pool — ₦10,000 <br>
    <input type="checkbox" name="extras" value="Tennis Court — 10000"> Tennis court — ₦10,000 <br>
    <input type="checkbox" name="extras" value="Spa — 15000"> Spa — ₦15,000 <br>
    <input type="checkbox" name="extras" value="Saloon — 10000"> Saloon — ₦10,000 <br>
    <input type="checkbox" name="extras" value="Nail Studio — 5000"> Nail studio — ₦5,000 <br>

    <h3>Extras (Dining & Bar)</h3>
    <input type="checkbox" name="extras" value="Sky Restaurant — 30000"> Sky Restaurant — ₦30,000 <br>
    <input type="checkbox" name="extras" value="Atarodo Restaurant — 30000"> Atarodo Restaurant — ₦30,000 <br>
    <input type="checkbox" name="extras" value="Lagoon Breeze — 25000"> Lagoon Breeze — ₦25,000 <br>
    <input type="checkbox" name="extras" value="Lagoon Irish Pub — 30000"> Lagoon Irish Pub — ₦30,000 <br>
    <input type="checkbox" name="extras" value="Calabash Bar — 20000"> Calabash Bar — ₦20,000 <br>
    <input type="checkbox" name="extras" value="Red Chinese Restaurant — 25000"> Red Chinese Restaurant — ₦25,000 <br>

    <h3>Other Extras</h3>
    <input type="checkbox" name="extras" value="Food — 10000"> Food — ₦10,000 <br>
    <input type="checkbox" name="extras" value="Accommodation Upgrade — 20000"> Accommodation Upgrade — ₦20,000 <br>
    <input type="checkbox" name="extras" value="Water Package — 5000"> Water Package — ₦5,000 <br>

    <h3>Guest Details</h3>
    <input type="text" name="first_name" placeholder="First Name" required><br>
    <input type="text" name="last_name" placeholder="Last Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="text" name="member_no" placeholder="Member No (optional)"><br>
    <input type="text" name="state" placeholder="State"><br>
    <input type="text" name="city" placeholder="City"><br>
    <input type="text" name="country" value="Nigeria"><br>

    <h3>Payment Method</h3>
    <select name="payment_method" required>
      <option value="Bank Transfer">Bank Transfer</option>
      <option value="online">online</option>
    </select><br>

    <h3>Total: ₦<span id="total">0</span></h3>
    <input type="hidden" name="total_price" id="total_input">

    <button type="submit">Book Now</button>

 <div class="col-12 text-begining">                                                  <div class="col-12 text-end">
          <button type="process2a.php" class="btn btn-custom mt-3">bank transfer</button>      <button type="process2b" class="btn btn-custom mt-3">pay online</button>
        </div>                                                                              </div>
      </div>                                                                                </div>
    </form>                                                                                  <form>   

  <script src="script.js"></script>
</body>
</html>
