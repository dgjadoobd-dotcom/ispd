<?php
/**
 * Health check endpoint for RADIUS Service
 * 
 * This endpoint provides health status of the application
 * and its dependencies (database, redis, etc.)
 */

// Set content type
header('Content-Type: application/json');

// Function to check database connection
function checkDatabase() {
    try {
        // This is a simplified check - in production, you'd use your actual DB connection
        $pdo = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['status' => 'healthy', 'message' => 'Database connection successful'];
    } catch (Exception $e) {
        return ['status' => 'unhealthy', 'message' => $e->getMessage()];
    }
}

// Function to check Redis connection
function checkRedis() {
    try {
        $redis = new Redis();
        $redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
        if ($redis->ping()) {
            return ['status' => 'healthy', 'message' => 'Redis connection successful'];
        }
        return ['status' => 'unhealthy', 'message' => 'Redis ping failed'];
    } catch (Exception $e) {
        return ['status' => 'unhealthy', 'message' => $e->getMessage()];
    }
}

// Function to check disk space
function checkDiskSpace() {
    $free = disk_free_space(__DIR__);
    $total = disk_total_space(__DIR__);
    $used = $total - $free;
    $percentUsed = ($used / $total) * 100;
    
    if ($percentUsed > 90) {
        return ['status' => 'warning', 'message' => 'Disk space running low', 'usage_percent' => round($percentUsed, 2)];
    }
    return ['status' => 'healthy', 'message' => 'Disk space OK', 'usage_percent' => round($percentUsed, 2)];
}

// Function to check application health
function checkApplication() {
    // Check if application is responsive
    if (function_exists('opcache_get_status')) {
        $opcache = opcache_get_status();
        if (!$opcache['opcache_enabled']) {
            return ['status' => 'warning', 'message' => 'OPcache is disabled'];
        }
    }
    
    return ['status' => 'healthy', 'message' => 'Application is running'];
}

// Main health check
$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'service' => 'RADIUS Service',
    'version' => '1.0.0',
    'checks' => []
];

// Run all health checks
$checks = [
    'database' => checkDatabase(),
    'redis' => checkRedis(),
    'disk' => checkDiskSpace(),
    'application' => checkApplication()
];

$health['checks'] = $checks;

// Determine overall status
$allHealthy = true;
foreach ($checks as $check) {
    if ($check['status'] !== 'healthy') {
        $allHealthy = false;
        $health['status'] = 'degraded';
    }
}

if ($allHealthy) {
    $health['status'] = 'healthy';
    http_response_code(200);
} else {
    $health['status'] = 'unhealthy';
    http_response_code(503);
}

// Output as JSON
echo json_encode($health, JSON_PRETTY_PRINT);
?>