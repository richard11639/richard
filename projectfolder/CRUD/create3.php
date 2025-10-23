<?php
include("db3.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    9
    $stmt = $mysql->prepare("INSERT INTO tblstudent (student_name, course_title, student_status) values (?, ?, ?)");
    $stmt->bind_param("sss", $name,$title,$status);
    if($stmt->execute()){
        echo "product added successfully";
        // "Location: read2.php";
    }else{
        echo"Error: ". $sql . $mysql->error;
    }

    $stmt->close();
};
?>
<form action="create3.php" method="POST">
 <label>Student Name</label>  
 <input type="text" name="studentName" required> 
 <label>Course title</label> 
 <input type="text" name="title" required>
<input type="submit"value="create student" required>

</form>