<?php

/**
 * Self-Hosted PipraPay Migration
 * Adds database tables and settings for local payment processing.
 * Supports both SQLite (default) and MySQL.
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

try {
    $db   = Database::getInstance();
    $pdo  = $db->getConnection();
    $lite = $db->isSqlite();

    echo "Starting Self-Hosted PipraPay migration (" . ($lite ? 'SQLite' : 'MySQL') . ")...\n";

    // ── 1. Create tables ─────────────────────────────────────────────────────
    $schemaFile = $lite
        ? __DIR__ . '/database/selfhosted_piprapay_sqlite.sql'
        : __DIR__ . '/database/selfhosted_piprapay_schema.sql';

    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);
    // Strip leading comment lines so they don't swallow the first CREATE TABLE
    $sql = preg_replace('/^--[^\n]*\n/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
            echo "  OK: " . substr($stmt, 0, 60) . "...\n";
        }
    }

    // ── 2. Seed settings rows ─────────────────────────────────────────────────
    $defaults = [
        'selfhosted_piprapay_enabled'              => '0',
        'selfhosted_piprapay_webhook_secret'       => 'change_this_webhook_secret_in_production',
        'selfhosted_piprapay_auto_billing_enabled' => '1',
        'selfhosted_piprapay_retry_attempts'       => '3',
        'selfhosted_piprapay_retry_interval_hours' => '24',
        'selfhosted_piprapay_webhook_timeout'      => '30',
    ];

    $insertSql = $lite
        ? "INSERT OR IGNORE INTO settings (`key`, `value`) VALUES (?, ?)"
        : "INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)";

    $stmt = $pdo->prepare($insertSql);
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
        echo "  Setting: $key\n";
    }

    // ── 3. Add auto_payment_enabled column to customers (SQLite safe) ─────────
    if ($lite) {
        $cols = $pdo->query("PRAGMA table_info(customers)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('auto_payment_enabled', $cols, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN auto_payment_enabled INTEGER DEFAULT 0");
            echo "  Added column: customers.auto_payment_enabled\n";
        } else {
            echo "  Column already exists: customers.auto_payment_enabled\n";
        }
    } else {
        // MySQL supports IF NOT EXISTS in ALTER TABLE
        $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS auto_payment_enabled TINYINT(1) DEFAULT 0");
        echo "  Column ensured: customers.auto_payment_enabled\n";
    }

    echo "\nMigration completed successfully!\n";
    echo "\nNext steps:\n";
    echo "  1. Go to Settings → Payment Gateways\n";
    echo "  2. Enable 'Self-Hosted PipraPay'\n";
    echo "  3. Set a strong webhook secret (32+ chars)\n";
    echo "  4. Register the Windows cron task (run as Administrator):\n";
    echo "     scripts\\windows\\register_selfhosted_piprapay_task.bat\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
