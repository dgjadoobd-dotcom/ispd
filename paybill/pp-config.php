<?php
/**
 * PipraPay Panel — Database Configuration
 * Reads credentials from the project root .env file.
 * Generated: do not delete this file.
 */

$_env = file_exists(__DIR__ . '/../.env') ? parse_ini_file(__DIR__ . '/../.env') : [];

// These globals are used by connectDatabase() in pp-functions.php
$db_host   = $_env['PAYBILL_DB_HOST']     ?? 'localhost';
$db_user   = $_env['PAYBILL_DB_USERNAME'] ?? 'root';
$db_pass   = $_env['PAYBILL_DB_PASSWORD'] ?? '';
$db_name   = $_env['PAYBILL_DB_NAME']     ?? 'piprapay';
$db_prefix = $_env['PAYBILL_DB_PREFIX']   ?? 'pp_';

unset($_env);
