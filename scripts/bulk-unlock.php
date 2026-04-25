<?php
/**
 * Bulk Reconnect Script - Unlock all suspended customers
 * Run: php scripts/bulk-unlock.php
 */

require_once __DIR__ . '/../config/app.php';
require_once BASE_PATH . '/config/database.php';

echo "======================================\n";
echo "Bulk Reconnect - Unlock All Customers\n";
echo "======================================\n\n";

$db = Database::getInstance();

// Get suspended customers count
$suspended = $db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='suspended'");
echo "Current suspended customers: " . $suspended['c'] . "\n";

// Confirm
echo "\nThis will reconnect ALL suspended customers.\n";
echo "Are you sure? Type 'yes' to continue: ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) !== "yes\n") {
    echo "Cancelled.\n";
    exit;
}

// Update all suspended to active
$db->query("UPDATE customers SET status='active' WHERE status='suspended'");

$updated = $db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='active'");
echo "\nDone! Active customers: " . $updated['c'] . "\n";

// Log entry
$logId = $db->insert('automation_logs', [
    'job_name' => 'bulk_reconnect',
    'status' => 'success',
    'processed' => $suspended['c'],
    'errors' => 0,
    'details' => 'Bulk reconnect: all suspended customers unlocked',
    'created_at' => date('Y-m-d H:i:s')
]);

echo "Logged: #$logId\n";
echo "======================================\n";