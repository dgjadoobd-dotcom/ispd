<?php
/**
 * RADIUS Daily Usage Rollup Cron Script
 *
 * Aggregates radius_sessions data for a given date into radius_usage_daily.
 *
 * Usage:
 *   php cron_radius_rollup.php              — rolls up yesterday's data
 *   php cron_radius_rollup.php --date=2024-01-15
 *
 * # Run daily at 00:05 AM: 5 0 * * * php /path/to/cron_radius_rollup.php
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Services/RadiusUsageTrackingService.php';

// Parse optional --date=YYYY-MM-DD argument
$date = date('Y-m-d', strtotime('-1 day'));
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (preg_match('/^--date=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
        $date = $m[1];
        break;
    }
}

$ts = '[' . date('Y-m-d H:i:s') . ']';

try {
    $pdo = Database::getInstance('radius')->getConnection();
    $service = new RadiusUsageTrackingService($pdo);
    $result = $service->recordDailyRollup($date);
    echo "{$ts} RADIUS rollup for {$date}: {$result['processed']} rows processed" . PHP_EOL;
    exit(0);
} catch (Exception $e) {
    echo "{$ts} ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
