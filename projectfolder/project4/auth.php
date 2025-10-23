<?php
$host = "localhost"; 
$user = "root";     
$pass = "";         
$db   = "trading_db"; 

$mysql = new mysqli($host, $user, $pass, $db);

if ($mysql->connect_error) {
    die("Connection failed: " . $mysql->connect_error);
}
?>




