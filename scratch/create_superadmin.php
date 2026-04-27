<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';

$db = Database::getInstance();
$username = 'superadmin';
$password = 'Super@1234';
$hashed   = password_hash($password, PASSWORD_DEFAULT);

$data = [
    'username'      => $username,
    'password_hash' => $hashed, // Correct column name!
    'full_name'     => 'System Super Admin',
    'role_id'       => 1, // superadmin
    'is_active'     => 1,
    'created_at'    => date('Y-m-d H:i:s')
];

try {
    $db->insert('users', $data);
    echo "Superadmin user created successfully with column 'password_hash'!\n";
} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
}
