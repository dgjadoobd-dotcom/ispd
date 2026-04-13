<?php
include '../db.php';

$res = $conn->query("SELECT * FROM transactions ORDER BY id DESC");

$data=[];
while($row = $res->fetch_assoc()){
    $data[]=$row;
}

echo json_encode($data);