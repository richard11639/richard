<?php
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['ProductName'];
    $price = $_POST['Price']; 
    $description = $_POST['description'];
    $status ='active';

    $sql="INSERT INTO tblproduct(product_name,product_price,description) 
    values ('$name','$price','$description','$status)";
    if($mysql->query($sql) === TRUE){
        echo "product added successfully";
        "Location: read.php";
    }else{
        echo"Error: ". $sql . $mysql->error;
    }
};
?>
<form action="create.php" method="POST">
 <label>product Name</label>  
 <input type="text" name="productName" required> 
 <label>price</label> 
 <input type="number" name="price" required>
<label>description</label>
<input type="text" name="despt"> 
<input type="submit"value="create product" required>

</form>