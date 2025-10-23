<?php
include('db3.php');
session_start();

// Check if 'id' parameter is set in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    // Retrieve the product data from the database
    $sql = "SELECT * FROM tblstudent WHERE student_name = $name";
    $result = $mysql->query($sql);

    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        echo "Product not found!";
        exit();
    }
} else {
    echo "Invalid product ID!";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['studentName'];
    $title = $_POST['title']; 
    $status ='active';

    $stmt = $mysql->prepare("UPDATE tblstudent SET course_title = ?, student_name = ?, description = ? WHERE student_name = ?");
    $stmt->bind_param("sdsi", $name, $title);

    if ($stmt->execute()) {
        header("Location: read3.php");
        exit();
    } else {
        echo "Error updating product: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Product</title>
</head>
<body>
    <h2>Update Product</h2>
    <form method="POST" action="">
        Name: <input type="text" name="student_name" value="<?php echo htmlspecialchars($product['student_name']); ?>" required><br><br>
        Title: <input type="text" name="course_title" value="<?php echo htmlspecialchars($product['course_title']); ?>" required><br><br>
        <input type="hidden" name="student_status" value="active">
        <input type="submit" value="Update Product">
    </form>
</body>
</html>
