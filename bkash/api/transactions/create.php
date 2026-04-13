<?php
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'];
$amount = $data['amount'];
$provider = $data['provider'];
$number = $data['number'];
$trxid = $data['trxid'];

$conn->query("INSERT INTO transactions(user_name,amount,provider,number,trxid)
VALUES('$name','$amount','$provider','$number','$trxid')");

echo "submitted";