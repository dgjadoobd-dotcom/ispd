<?php
header('Content-Type: text/plain');
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
$db = Database::getInstance();
$nasList = $db->fetchAll("SELECT id, name, ip_address, api_port, username, password FROM nas_devices ORDER BY id");
echo count($nasList) . " NAS device(s):\n";
foreach ($nasList as $n) {
    echo "[{$n['id']}] {$n['name']} {$n['ip_address']}:{$n['api_port']} user={$n['username']} pass={$n['password']}\n";
}
