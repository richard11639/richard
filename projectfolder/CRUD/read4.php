<?php
include 'db4.php';

$sql = "SELECT * FROM tblcomplaints WHERE complaint_status = 'active'";
$result = $mysql->query($sql);
?>
<h2>complaints List</h2>
<table border='10'>
    <tr>
        <th>ID</th>
        <th>name</th>
        <th>complaints</th>
        <th>Time</th>
        <th>Action</th>
    </tr>
    <?php
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['complaint_id'] . "</td>
                    <td>" . $row['customers_name'] . "</td>
                    <td>" . $row['complaints'] . "</td>
                    <td>" . $row['time'] . "</td>
                    <td>
                        <a href='update4.php?id=" . $row['complaint_id'] . "'>Edit</a>
                        <a href='delete4.php?id=" . $row['complaint_id'] . "'>Delete</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No products found</td></tr>";
    }
    ?>
</table>
<br>
<a href="create4.php">Add New complaints</a>