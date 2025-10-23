<?php
include 'db2.php';

$sql = "SELECT * FROM tblliberary WHERE book_status = 'active'";
$result = $mysql->query($sql);
?>

<h2>Product List</h2>
<table border='10'>
    <tr>
        <th>ID</th>
        <th>book</th>
        <th>author</th>
        <th>serial</th>
        <th>Time</th>
        <th>Action</th>
    </tr>
    <?php
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['libary_id'] . "</td>
                    <td>" . $row['book_name'] . "</td>
                    <td>" . $row['book_author'] . "</td>
                    <td>" . $row['book_series'] . "</td>
                    <td>" . $row['created_at'] . "</td>
                    <td>
                        <a href='update2.php?id=" . $row['libary_id'] . "'>Edit</a>
                        <a href='delete2.php?id=" . $row['libary_id'] . "'>Delete</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No products found</td></tr>";
    }
    ?>
</table>
<br>
<a href="create2.php">Add New Product</a>