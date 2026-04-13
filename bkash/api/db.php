<?php
$conn = new mysqli("localhost","root","","payment_gateway");

if($conn->connect_error){
    die("DB Error");
}
?>