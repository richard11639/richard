<?php
include 'auth2.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $datein = $_POST['datein'];
    $dateout = $_POST['dateout'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $passcode = $_POST['passcode'];
    $comfort = $_POST['comfort'];
    $quality = $_POST['quality'];
    $facility = $_POST['facility'];
    $satisfaction = $_POST['satisfaction'];
    $message = $_POST['message'];

    $sql = "INSERT INTO feedback 
        (datein, dateout, name, phone, email, passcode, comfort, quality, facility, satisfaction, message)
        VALUES ('$datein', '$dateout', '$name', '$phone', '$email', '$passcode', '$comfort', '$quality', '$facility', '$satisfaction', '$message')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Thank you $name, your feedback has been submitted successfully,A member of our reservations team will review and contact you at For urgent inquiries please call +234 902 142 7575.  !');</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RIC Hotel Feedback</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
      color: #fff;
      line-height: 1.6;
      margin: 0;
    }
    header {
      background: rgba(0,0,0,0.85);
      padding: 15px;
      text-align: center;
    }
    header h1 {
      font-size: 2rem;
      color: #f5c542;
    }
    nav a {
      margin: 0 15px;
      color: #fff;
      text-decoration: none;
      font-weight: bold;
    }
    nav a:hover {
      color: #f5c542;
    }
    .container {
      max-width: 800px;
      background: rgba(255,255,255,0.1);
      padding: 30px;
      margin: 40px auto;
      border-radius: 10px;
      box-shadow: 0px 0px 15px rgba(0,0,0,0.5);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #f5c542;
    }
    label {
      display: block;
      margin: 10px 0 5px;
    }
    input, select, textarea {
      width: 100%;
      padding: 10px;
      border: none;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    textarea {
      height: 100px;
    }
    button {
      background: #f5c542;
      color: #000;
      border: none;
      padding: 12px 20px;
      border-radius: 5px;
      cursor: pointer;
      width: 100%;
      font-size: 1rem;
      font-weight: bold;
    }
    button:hover {
      background: #e6b800;
    }
  </style>
</head>
<body>

<header>
  <h1>Ric Hotel Feedback</h1>
  <nav>
    <a href="index6.php">Home</a>
    <a href="#feedback.php">Feedback</a>
    <a href="blog.php">View Feedback</a>
  </nav>
</header>

<div class="container">
  <h2>We Value Your Feedback</h2>
  <form method="POST" action="">  
    <label for="datein">Check-In Date:</label>
    <input type="date" name="datein" required>

    <label for="dateout">Check-Out Date:</label>
    <input type="date" name="dateout" required>

    <label for="name">Full Name:</label>
    <input type="text" name="name" required>

    <label for="phone">Phone Number:</label>
    <input type="tel" name="phone" required>

    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="passcode">Client Passcode:</label>
    <input type="password" name="passcode" required>

    <label for="comfort">Room Comfort:</label>
    <select name="comfort" required>
      <option value="">Select</option>
      <option value="Excellent">Excellent</option>
      <option value="Good">Good</option>
      <option value="Average">Average</option>
      <option value="Poor">Poor</option>
    </select>

    <label for="quality">Room Quality:</label>
    <select name="quality" required>
      <option value="">Select</option>
      <option value="Excellent">Excellent</option>
      <option value="Good">Good</option>
      <option value="Average">Average</option>
      <option value="Poor">Poor</option>
    </select>

    <label for="facility">Hotel Facilities:</label>
    <select name="facility" required>
      <option value="">Select</option>
      <option value="Excellent">Excellent</option>
      <option value="Good">Good</option>
      <option value="Average">Average</option>
      <option value="Poor">Poor</option>
    </select>

    <label for="satisfaction">Overall Satisfaction:</label>
    <select name="satisfaction" required>
      <option value="">Select</option>
      <option value="Very Satisfied">Very Satisfied</option>
      <option value="Satisfied">Satisfied</option>
      <option value="Neutral">Neutral</option>
      <option value="Unsatisfied">Unsatisfied</option>
    </select>

    <label for="additional comment">Additional Comment:</label>
    <textarea name="additional comment" placeholder="Tell us more..."></textarea>

    <button type="submit">Submit Feedback</button>
  </form>
</div>

</body>
</html>
