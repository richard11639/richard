<?php
include("db2.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['bookName'];
    $author = $_POST['author']; 
    $serial = $_POST['serial'];
    $status ='active';

    $stmt = $mysql->prepare("INSERT INTO tblliberary (book_name, book_author, book_series, book_status) values (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name,$author,$serial,$status);
    if($stmt->execute()){
        echo "product added successfully";
        // "Location: read2.php";
    }else{
        echo"Error: ". $sql . $mysql->error;
    }

    $stmt->close();
};
?>
<form action="create2.php" method="POST">
 <label>Book Name</label>  
 <input type="text" name="bookName" required> 
 <label>Book Author</label> 
 <input type="text" name="author" required>
<label>Serial Number</label>
<input type="text" name="serial"> 
<input type="submit"value="create product" required>

</form>


