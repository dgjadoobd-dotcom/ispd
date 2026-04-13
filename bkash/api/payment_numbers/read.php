<?php
include '../db.php';

$res = $conn->query("SELECT * FROM payment_numbers WHERE status=1");

$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);