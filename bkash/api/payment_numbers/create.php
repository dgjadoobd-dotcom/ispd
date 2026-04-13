<?php
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

$provider = $data['provider'];
$type = $data['account_type'];
$number = $data['number'];
$name = $data['name'];
$api = $data['api_key'];

$qr = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data="
    . urlencode("pay://$provider/$number?apikey=$api");

$conn->query("INSERT INTO payment_numbers(provider,account_type,number,name,api_key,qr_url)
VALUES('$provider','$type','$number','$name','$api','$qr')");

echo "ok";