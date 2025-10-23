<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin2.php");
    exit();
}

$blog_id = $_GET['blog_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;

if (!$blog_id || !$user_id) {
    echo "Invalid access.";
    exit();
}

// Fetch current blog data
$stmt = $mysqli->prepare("SELECT * FROM tblblog WHERE blog_id = ? AND posted_by = ?");
$stmt->bind_param("ii", $blog_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    // Status remains active on edit
    $blog_status = 'active';

    $stmt = $mysql->prepare("UPDATE tblblog SET blog_title = ?, blog_content = ?, blog_status = ? WHERE blog_id = ? AND posted_by = ?");
    $stmt->bind_param("sssii", $title, $content, $blog_status, $blog_id, $user_id);


    if ($stmt->execute()) {
        header("Location: blog.php?user_id=$user_id");
        exit();
    } else {
        echo "Error updating blog.";
    }
}
?>

<h2>Edit Blog Post</h2>
<form method="POST">
    <label>Title:</label><br>
    <input type="text" name="title" value="<?= htmlspecialchars($blog['blog_title']) ?>" required><br><br>

    <label>Content:</label><br>
    <textarea name="content" rows="6" cols="50" required><?= htmlspecialchars($blog['blog_content']) ?></textarea><br><br>

    <button type="submit">Update Blog</button>
</form>