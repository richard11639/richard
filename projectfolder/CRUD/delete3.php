<?php
include('db3.php');

// Check if 'id' parameter is set in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    // Check if the product exists
    $sql = "SELECT * FROM tblliberary WHERE libary_id = $id";
    $result = $mysql->query($sql);

    if ($result && $result->num_rows > 0) {
        //Product exists, proceed to delete
        $delete_sql = "UPDATE tblliberary SET book_status ='inactive' WHERE libary_id = $id";

        if ($mysql->query($delete_sql) === TRUE) {
            echo "Product deleted successfully";
            header("Location: read.php"); // Redirect to the product listing page
            exit();
       } else {
            echo "Error deleting product: " . $mysql->error;
        }
   } else {
        echo "Product not found!";
    }
} else {
    echo "Invalid product ID!";
}
?>