<?php
define('BASE_PATH', __DIR__);
require_once 'config/app.php';
require_once 'config/database.php';

$db = Database::getInstance();
$cols = $db->getConnection()->query("PRAGMA table_info(users)")->fetchAll();
print_r($cols);
