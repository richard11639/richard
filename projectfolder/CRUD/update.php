<?php
include('db.php');
session_start();

// Check if 'id' parameter is set in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    // Retrieve the product data from the database
    $sql = "SELECT * FROM tblproduct WHERE product_id = $id";
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
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $description = $_POST['description'];

    $stmt = $mysql->prepare("UPDATE tblproduct SET product_name = ?, product_price = ?, description = ? WHERE product_id = ?");
    $stmt->bind_param("sdsi", $name, $price, $description, $id);

    if ($stmt->execute()) {
        header("Location: read.php");
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
        Name: <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required><br><br>
        Price: <input type="text" name="product_price" value="<?php echo htmlspecialchars($product['product_price']); ?>" required><br><br>
        Description: <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea><br><br>
        <input type="hidden" name="product_status" value="active">
        <input type="submit" value="Update Product">
    </form>
</body>
</html>
