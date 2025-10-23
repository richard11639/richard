<?php
include("db4.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['customerName'];
    $complaint = $_POST['complaints']; 
    $status ='active';

    $stmt = $mysql->prepare("INSERT INTO tblcomplaints (customers_name, complaints, complaint_status) values (?, ?, ?)");
    $stmt->bind_param("sss", $name,$complaint,$status);
    if($stmt->execute()){
        echo "product added successfully";
        // "Location: read2.php";
    }else{
        echo"Error: ". $sql . $mysql->error;
    }

    $stmt->close();
};
?>
<form action="create4.php" method="POST">
 <label>Customer Name</label>  
 <input type="text" name="customerName" required> 
 <label>Complaint</label> 
 <input type="text" name="complaints" required>
<input type="submit"value="create complaints" required>

</form>