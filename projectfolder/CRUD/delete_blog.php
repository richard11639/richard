<?php
include 'auth.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin2.php");
    exit();
}

$blog_id = $_GET['blog_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;

if (!$blog_id || !$user_id) {
    echo "Missing blog ID or user ID.";
    exit();
}

// Set blog_status to 'inactive'
$stmt = $mysql->prepare("UPDATE tblblog SET blog_status = 'inactive' WHERE blog_id = ?");
$stmt->bind_param("i", $blog_id);

if ($stmt->execute()) {
    header("Location: blog.php?user_id=$user_id&deleted=true");
    exit();
} else {
    echo "Failed to delete blog.";
}
?>
