<?php
include 'db.php';

$sql = "SELECT * FROM tblproduct WHERE product_status = 'active'";
$result = $mysql->query($sql);
?>

<h2>Product List</h2>
<table border='1'>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Price</th>
        <th>Description</th>
        <th>Time</th>
        <th>Action</th>
    </tr>
    <?php
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['product_id'] . "</td>
                    <td>" . $row['product_name'] . "</td>
                    <td>" . $row['product_price'] . "</td>
                    <td>" . $row['description'] . "</td>
                    <td>" . $row['created_at'] . "</td>
                    <td>
                        <a href='update.php?id=" . $row['product_id'] . "'>Edit</a>
                        <a href='delete.php?id=" . $row['product_id'] . "'>Delete</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No products found</td></tr>";
    }
    ?>
</table>
<br>
<a href="create.php">Add New Product</a>