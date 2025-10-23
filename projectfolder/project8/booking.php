<?php
// booking.php - simple demo booking handler (saves to bookings.csv)
// WARNING: This is for demo only. For production, validate inputs, sanitize, add CSRF, rate limiting, and use DB.

$csvFile = __DIR__ . '/data/bookings.csv';
// Ensure data folder exists
if (!is_dir(__DIR__.'/data')) {
    mkdir(__DIR__.'/data', 0755, true);
}

function saveBooking($data) {
    global $csvFile;
    $exists = file_exists($csvFile);
    $fp = fopen($csvFile, 'a');
    if (!$exists) {
        // write header row
        fputcsv($fp, array('timestamp','name','email','arrival','departure','guests','room_type','message'));
    }
    fputcsv($fp, $data);
    fclose($fp);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // availability form or inquiry
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? $_POST['email'] ?? '');
    $arrival = $_POST['arrival'] ?? '';
    $departure = $_POST['departure'] ?? '';
    $guests = $_POST['guests'] ?? '';
    $room_type = $_POST['room_type'] ?? '';
    $message = $_POST['message'] ?? '';

    // Minimal validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Save to CSV (demo)
        $timestamp = date('Y-m-d H:i:s');
        saveBooking([$timestamp, $name, $email, $arrival, $departure, $guests, $room_type, $message]);
        $success = true;
    }
} else {
    header('Location: index6.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Feedback Submitted — Eko Hotel</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="center-box">
    <?php if(!empty($error)): ?>
      <h2>Error</h2>
      <p><?php echo htmlspecialchars($error); ?></p>
      <p><a href="index.php" class="btn">Back</a></p>
    <?php else: ?>
      <h2>Thank you!</h2>
      <p>Your request has been received. A member of our reservations team will review and contact you at <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
      <p>For urgent inquiries please call +234 800 123 4567.</p>
      <p><a href="index6.php" class="btn">Return Home</a></p>
    <?php endif; ?>
  </div>
</body>
</html>
