<?php
define('BASE_PATH', __DIR__);
require_once 'config/app.php';
require_once 'config/database.php';

$db = Database::getInstance();
$user = $db->fetchOne("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.username = 'superadmin'");

if ($user) {
    echo "User Found:\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Role: " . ($user['role_name'] ?? 'N/A') . "\n";
    echo "Is Active: " . $user['is_active'] . "\n";
} else {
    echo "Superadmin user NOT found.\n";
    
    // Check all users
    $all = $db->fetchAll("SELECT username, role_id FROM users");
    echo "\nAll Users:\n";
    print_r($all);
}
