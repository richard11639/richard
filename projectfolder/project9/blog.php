<?php
session_start();
include 'auth2.php'; // your DB connection
include 'auth2.php'; // if you use same auth as blogs

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: feedback.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch feedbacks
$sql = "SELECT * FROM feedback ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Feedback</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f9f9f9;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .add-feedback-btn {
            padding: 8px 16px;
            text-decoration: none;
            background-color: #007BFF;
            color: white;
            border-radius: 4px;
        }
        .add-feedback-btn:hover {
            background-color: #0056b3;
        }
        .feedback-post {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #fff;
        }
        .feedback-title {
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 6px;
            color: #333;
        }
        .feedback-meta {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 8px;
        }
        .feedback-ratings {
            margin: 10px 0;
            font-size: 0.9em;
        }
        .feedback-comment {
            background: #fafafa;
            padding: 10px;
            border-left: 3px solid #007BFF;
            border-radius: 3px;
            font-style: italic;
        }
        .feedback-actions a {
            margin-right: 10px;
            color: #007BFF;
            text-decoration: none;
        }
        .feedback-actions a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Welcome, <?php echo htmlspecialchars($username) . ' (' . $user_id . ')'; ?>!</h2>
    <a href="feedback.php" class="add-feedback-btn">Back</a>
</div>

<h3>All Guest Feedback</h3>

<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='feedback-post'>";
        echo "<div class='feedback-title'>" . htmlspecialchars($row['name']) . " — " . htmlspecialchars($row['satisfaction']) . "</div>";
        echo "<div class='feedback-meta'>";
        echo "Check-in: " . htmlspecialchars($row['datein']) . " | Check-out: " . htmlspecialchars($row['dateout']) . " | Submitted: " . htmlspecialchars($row['created_at']);
        echo "</div>";
        echo "<div class='feedback-ratings'>";
        echo "Comfort: " . htmlspecialchars($row['comfort']) . " | Quality: " . htmlspecialchars($row['quality']) . " | Facilities: " . htmlspecialchars($row['facility']);
        echo "</div>";
        if (!empty($row['comment'])) {
            echo "<div class='feedback-comment'>" . htmlspecialchars($row['comment']) . "</div>";
        }

        // Admin controls (same style as your blog)
        if ($user_id == 85 || $user_id == 3) {
            $feedback_id = $row['id'];
            echo "<div class='feedback-actions' style='margin-top: 10px;'>";
            echo "<a href='edit_feedback.php?id={$feedback_id}'>Edit</a>";
            echo "<a href='delete_feedback.php?id={$feedback_id}' onclick=\"return confirm('Are you sure you want to delete this feedback?');\">Delete</a>";
            echo "</div>";
        }

        echo "</div>";
    }
} else {
    echo "<p>No feedback yet.</p>";
}
?>

<p><a href="logout.php">Logout</a></p>

</body>
</html>
