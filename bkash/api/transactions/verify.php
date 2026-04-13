<?php
include '../db.php';

$id = $_GET['id'];
$status = $_GET['status']; // approved / rejected

$conn->query("UPDATE transactions SET status='$status' WHERE id=$id");

echo "updated";