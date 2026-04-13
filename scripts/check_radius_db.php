<?php
$env = parse_ini_file(dirname(__DIR__) . '/.env');

echo "=== RADIUS Configuration ===" . PHP_EOL;
echo "  RADIUS Host:     " . ($env['RADIUS_HOST'] ?? 'NOT SET') . PHP_EOL;
echo "  RADIUS Port:     " . ($env['RADIUS_PORT'] ?? 'NOT SET') . PHP_EOL;
echo PHP_EOL;
echo "=== RADIUS Database Config ===" . PHP_EOL;
echo "  DB Host:     " . ($env['RADIUS_DB_HOST'] ?? 'NOT SET') . PHP_EOL;
echo "  DB Port:     " . ($env['RADIUS_DB_PORT'] ?? 'NOT SET') . PHP_EOL;
echo "  DB Database: " . ($env['RADIUS_DB_DATABASE'] ?? 'NOT SET') . PHP_EOL;
echo "  DB Username: " . ($env['RADIUS_DB_USERNAME'] ?? 'NOT SET') . PHP_EOL;
echo "  DB Password: " . (isset($env['RADIUS_DB_PASSWORD']) && $env['RADIUS_DB_PASSWORD'] !== '' ? '(set)' : '(empty - using blank)') . PHP_EOL;
echo PHP_EOL;

// Test connection
echo "=== Connection Test ===" . PHP_EOL;
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['RADIUS_DB_HOST'],
        $env['RADIUS_DB_PORT'],
        $env['RADIUS_DB_DATABASE']
    );
    $pdo = new PDO($dsn, $env['RADIUS_DB_USERNAME'], $env['RADIUS_DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "  Status: CONNECTED OK" . PHP_EOL;

    // List all tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "  Tables found: " . count($tables) . PHP_EOL;
    foreach ($tables as $t) {
        echo "    - " . $t . PHP_EOL;
    }
    echo PHP_EOL;

    // Check standard FreeRADIUS tables
    echo "=== Standard RADIUS Tables ===" . PHP_EOL;
    $required = ['radcheck', 'radreply', 'radusergroup', 'radgroupcheck', 'radgroupreply', 'radacct', 'radpostauth', 'nas'];
    foreach ($required as $tbl) {
        $exists = in_array($tbl, $tables);
        echo "  " . ($exists ? "[OK]" : "[MISSING]") . " " . $tbl . PHP_EOL;
    }
    echo PHP_EOL;

    // Check enhanced tables
    echo "=== Enhanced RADIUS Tables (from spec) ===" . PHP_EOL;
    $enhanced = ['radius_sessions', 'radius_user_profiles', 'radius_audit_logs', 'radius_usage_daily', 'radius_alerts'];
    foreach ($enhanced as $tbl) {
        $exists = in_array($tbl, $tables);
        echo "  " . ($exists ? "[OK]" : "[MISSING - run migrations]") . " " . $tbl . PHP_EOL;
    }

} catch (PDOException $e) {
    echo "  Status: FAILED" . PHP_EOL;
    echo "  Error:  " . $e->getMessage() . PHP_EOL;
    echo PHP_EOL;
    echo "=== Fix ===" . PHP_EOL;
    echo "  1. Make sure MySQL is running in XAMPP Control Panel" . PHP_EOL;
    echo "  2. Create the 'radius' database:" . PHP_EOL;
    echo "     mysql -u root -e \"CREATE DATABASE IF NOT EXISTS radius;\"" . PHP_EOL;
    echo "  3. Import the base schema:" . PHP_EOL;
    echo "     mysql -u root radius < database/radius_schema.sql" . PHP_EOL;
    echo "  4. Run enhanced migrations:" . PHP_EOL;
    echo "     mysql -u root radius < database/radius_enhanced_schema.sql" . PHP_EOL;
}
