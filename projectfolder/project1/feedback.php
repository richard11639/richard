<?php
// feedback.php
session_start();
require_once 'db.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(20));
}
$csrf = $_SESSION['csrf_token'];

$errors = [];
$success = null;

// handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid form submission.";
    } else {
        // collect and sanitize inputs
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $food_quality = intval($_POST['food_quality'] ?? 0);
        $delivery_quality = intval($_POST['delivery_quality'] ?? 0);
        $taste = intval($_POST['taste'] ?? 0);
        $customer_care = intval($_POST['customer_care'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        // validation
        if ($name === '') $errors[] = "Please enter your name.";
        if ($message === '') $errors[] = "Please enter a message.";
        foreach (['food_quality'=>$food_quality,'delivery_quality'=>$delivery_quality,'taste'=>$taste,'customer_care'=>$customer_care] as $k => $v) {
            if ($v < 1 || $v > 5) $errors[] = ucfirst(str_replace('_',' ',$k))." must be between 1 and 5.";
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please provide a valid email address.";

        // insert
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO feedback (name, email, food_quality, delivery_quality, taste, customer_care, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssiiiis', $name, $email, $food_quality, $delivery_quality, $taste, $customer_care, $message);
                if ($stmt->execute()) {
                    $success = "Thanks — your feedback has been submitted!";
                    // regenerate CSRF token (prevent double submit)
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(20));
                    $csrf = $_SESSION['csrf_token'];
                    // clear submitted values for form
                    $name = $email = $message = '';
                    $food_quality=$delivery_quality=$taste=$customer_care = 0;
                } else {
                    $errors[] = "Database error when saving feedback.";
                }
                $stmt->close();
            } else {
                $errors[] = "Database error (prepare failed).";
            }
        }
    }
}

// fetch latest 50 feedbacks
$feedbacks = [];
$res = $conn->query("SELECT id, name, email, food_quality, delivery_quality, taste, customer_care, message, created_at FROM feedback ORDER BY created_at DESC LIMIT 50");
if ($res) {
    while ($row = $res->fetch_assoc()) $feedbacks[] = $row;
}

// compute averages
$averages = ['food'=>0,'delivery'=>0,'taste'=>0,'care'=>0];
$total_count = 0;
$res2 = $conn->query("SELECT COUNT(*) AS c, AVG(food_quality) AS a_food, AVG(delivery_quality) AS a_delivery, AVG(taste) AS a_taste, AVG(customer_care) AS a_care FROM feedback");
if ($res2) {
    $row = $res2->fetch_assoc();
    $total_count = intval($row['c']);
    $averages['food'] = $row['a_food'] ? round($row['a_food'],2) : 0;
    $averages['delivery'] = $row['a_delivery'] ? round($row['a_delivery'],2) : 0;
    $averages['taste'] = $row['a_taste'] ? round($row['a_taste'],2) : 0;
    $averages['care'] = $row['a_care'] ? round($row['a_care'],2) : 0;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>RIC — Feedback</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --accent:#e53935; --muted:#55606a; --bg:#f7f8fa; --card:#fff; --radius:10px;
      --card-border:#e9edf0;
    }
    *{box-sizing:border-box}
    body{font-family:Inter,system-ui,Arial,sans-serif;margin:0;background:var(--bg);color:#1b1f23;line-height:1.5}
    .container{max-width:980px;margin:28px auto;padding:18px}
    header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
    header h1{margin:0;color:var(--accent);letter-spacing:1px}
    .card{background:var(--card);border:1px solid var(--card-border);border-radius:var(--radius);padding:16px;box-shadow:0 6px 20px rgba(0,0,0,0.03)}
    form .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    label{display:block;font-weight:600;margin-bottom:6px;color:#333}
    input[type="text"],input[type="email"],select,textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #e6e9ec;background:#fff}
    textarea{min-height:100px;resize:vertical}
    .actions{display:flex;gap:10px;align-items:center;justify-content:flex-end;margin-top:10px}
    .btn{background:var(--accent);color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer;font-weight:700}
    .btn.ghost{background:#fff;color:var(--accent);border:1px solid var(--accent)}
    .note{color:var(--muted);font-size:14px;margin-bottom:10px}
    .error{background:#ffecec;border:1px solid #ffbcbc;padding:10px;border-radius:8px;color:#990000;margin-bottom:12px}
    .success{background:#e9f7ef;border:1px solid #c7eed3;padding:10px;border-radius:8px;color:#0a7a3a;margin-bottom:12px}
    .feedback-list{margin-top:18px;display:grid;gap:12px}
    .feedback-item{padding:12px;border-radius:8px;border:1px solid var(--card-border);background:#fff}
    .meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
    .rating{font-weight:800;color:var(--accent)}
    .small{font-size:13px;color:var(--muted)}
    .averages{display:flex;gap:12px;flex-wrap:wrap}
    .avg-box{background:#fff;border:1px solid var(--card-border);padding:10px;border-radius:8px;min-width:130px}
    @media (max-width:680px){
      .form .grid{grid-template-columns:1fr}
      .averages{flex-direction:column}
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
        <a href="index.php">Home</a>
      <h1>RIC — Feedback</h1>
      <div class="small">We appreciate your feedback ❤️</div>
       <a href="viewfeedback.php">view feedback ❤️</a>
    </header>

    <section class="card">
      <?php if (!empty($errors)): ?>
        <div class="error"><strong>Please fix the following:</strong><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="success"><?=htmlspecialchars($success)?></div>
      <?php endif; ?>

      <p class="note">Tell us how we did — choose ratings 1 (poor) to 5 (excellent) and leave a short message.</p>

      <form method="post" novalidate>
        <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">

        <div class="grid">
          <div>
            <label for="name">Name *</label>
            <input id="name" name="name" type="text" maxlength="150" required value="<?=htmlspecialchars($name ?? '')?>">
          </div>
          <div>
            <label for="email">Email (optional)</label>
            <input id="email" name="email" type="email" maxlength="255" value="<?=htmlspecialchars($email ?? '')?>">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:12px;">
          <div>
            <label for="food_quality">Food quality *</label>
            <select id="food_quality" name="food_quality" required>
              <option value="">—</option>
              <?php for($i=5;$i>=1;$i--): ?>
                <option value="<?=$i?>" <?=isset($food_quality) && $food_quality == $i ? 'selected' : ''?>><?=$i?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label for="delivery_quality">Delivery man quality *</label>
            <select id="delivery_quality" name="delivery_quality" required>
              <option value="">—</option>
              <?php for($i=5;$i>=1;$i--): ?>
                <option value="<?=$i?>" <?=isset($delivery_quality) && $delivery_quality == $i ? 'selected' : ''?>><?=$i?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label for="taste">Taste *</label>
            <select id="taste" name="taste" required>
              <option value="">—</option>
              <?php for($i=5;$i>=1;$i--): ?>
                <option value="<?=$i?>" <?=isset($taste) && $taste == $i ? 'selected' : ''?>><?=$i?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label for="customer_care">Customer care *</label>
            <select id="customer_care" name="customer_care" required>
              <option value="">—</option>
              <?php for($i=5;$i>=1;$i--): ?>
                <option value="<?=$i?>" <?=isset($customer_care) && $customer_care == $i ? 'selected' : ''?>><?=$i?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <div style="margin-top:12px;">
          <label for="message">Message *</label>
          <textarea id="message" name="message" maxlength="1000" required><?=htmlspecialchars($message ?? '')?></textarea>
        </div>

        <div class="actions">
          <button class="btn" type="submit">Send Feedback</button>
          <button class="btn ghost" type="reset">Reset</button>
        </div>
      </form>
    </section>

    