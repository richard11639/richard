<?php
include 'db3.php';

$sql = "SELECT * FROM tblstudent WHERE student_status = 'active'";
$result = $mysql->query($sql);
?>
<h2>Product List</h2>
<table border='10'>
    <tr>

        <th>name</th>
        <th>title</th>
        <th>Time</th>
        <th>Action</th>
    </tr>
    <?php
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['student_name'] . "</td>
                    <td>" . $row['course_title'] . "</td>
                    <td>" . $row['time'] . "</td>
                    <td>
                        <a href='update3.php?id=" . $row['student_name'] . "'>Edit</a>
                        <a href='delete.3php?id=" . $row['student_name'] . "'>Delete</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No products found</td></tr>";
    }
    ?>
</table>
<br>
<a href="create3.php">Add New student</a>