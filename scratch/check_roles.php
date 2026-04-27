<?php
define('BASE_PATH', __DIR__);
require_once 'config/app.php';
require_once 'config/database.php';

$db = Database::getInstance();
$roles = $db->fetchAll("SELECT id, name, display_name FROM roles");
echo "Roles:\n";
print_r($roles);
