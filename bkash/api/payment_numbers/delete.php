<?php
include '../db.php';
$id = $_GET['id'];

$conn->query("DELETE FROM payment_numbers WHERE id=$id");

echo "deleted";