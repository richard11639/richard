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
    <title>Blog Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .add-blog-btn {
            padding: 8px 16px;
            text-decoration: none;
            background-color: #007BFF;
            color: white;
            border-radius: 4px;
        }
        .add-blog-btn:hover {
            background-color: #0056b3;
        }
        .blog-post {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .blog-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .blog-meta {
            font-size: 0.85em;
            color: #555;
            margin-top: 8px;
        }
        .blog-actions a {
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Welcome, <?php echo htmlspecialchars($username) . ' (' . $user_id . ')'; ?>!</h2>
        <a href="add_blog.php?user_id=<?php echo $user_id; ?>" class="add-blog-btn">Add Blog</a>
    </div>

    <h3>All Blog Posts</h3>

    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='blog-post'>";
            echo "<div class='blog-title'>" . htmlspecialchars($row['blog_title']) . "</div>";
            echo "<p>" . nl2br(htmlspecialchars($row['blog_content'])) . "</p>";
            echo "<div class='blog-meta'>";
            echo "Posted by: " . htmlspecialchars($row['posted_by_username']) . " (" . htmlspecialchars($row['posted_by']) . ") | ";
            echo "Date: " . htmlspecialchars($row['date_posted']);
            echo "</div>";

            // Show Edit/Delete if user_id is 1 or 3
            if ($user_id == 85 || $user_id == 3) {
                $blog_id = $row['blog_id'];
                echo "<div class='blog-actions' style='margin-top: 10px;'>";
                echo "<a href='edit_blog.php?user_id={$user_id}&blog_id={$blog_id}'>Edit</a>";
                echo "<a href='delete_blog.php?user_id={$user_id}&blog_id={$blog_id}' onclick=\"return confirm('Are you sure you want to delete this blog post?');\">Delete</a>";
                echo "</div>";
            }

            echo "</div>";
        }
    } else {
        echo "<p>No blog posts found.</p>";
    }
    ?>

    <p><a href="logout.php?user_id=<?php echo $user_id; ?>">Logout</a></p>

</body>
</html>