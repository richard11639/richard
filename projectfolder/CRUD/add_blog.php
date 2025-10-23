<?php
session_start();
include 'auth.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: signin2.php");
    exit();
}

$session_user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user_id from URL and validate it
$url_user_id = $_GET['user_id'] ?? null;

if ($url_user_id != $session_user_id) {
    die("Unauthorized access.");
}

$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blog_title = trim($_POST['blog_title']);
    $blog_content = trim($_POST['blog_content']);

    if (!empty($blog_title) && !empty($blog_content)) {
        $sql = "INSERT INTO tblblog (blog_title, blog_content, posted_by, blog_status, date_posted) 
                VALUES (?, ?, ?, 'active', NOW())";
        $stmt = $mysql->prepare($sql);
        $stmt->bind_param("ssi", $blog_title, $blog_content, $session_user_id);

        if ($stmt->execute()) {
            header("Location: blog.php?user_id=" . $session_user_id);
            exit();
        } else {
            $error = "Error saving blog post: " . $stmt->error;
        }
    } else {
        $error = "Both blog title and content are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Blog</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        textarea, input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }
        input[type="submit"] {
            margin-top: 10px;
            padding: 8px 16px;
        }
        .error {
            color: red;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <h2>Add a New Blog Post</h2>
    <p>Logged in as: <?php echo htmlspecialchars($username); ?> (ID: <?php echo $session_user_id; ?>)</p>

    <form method="POST" action="add_blog.php?user_id=<?php echo $session_user_id; ?>">
        <label for="blog_title">Blog Title:</label><br>
        <input type="text" name="blog_title" id="blog_title" required><br><br>

        <label for="blog_content">Blog Content:</label><br>
        <textarea name="blog_content" id="blog_content" rows="8" required></textarea><br><br>

        <input type="submit" value="Post Blog">
    </form>

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <p><a href="blog.php?user_id=<?php echo $session_user_id; ?>">← Back to Blog Page</a></p>
    <p><a href="logout.php?user_id=<?php echo $session_user_id; ?>">Logout</a></p>
</body>
</html>