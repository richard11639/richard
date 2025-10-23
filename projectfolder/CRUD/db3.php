<?php
$host='localhost';
$username='root';
$password='';
$dbname="student_db";

$mysql= new mysqli($host, $username, $password, $dbname);

if ($mysql -> connect_error){
    exit("connection failed: ".$mysql -> connect_error);
}
?>