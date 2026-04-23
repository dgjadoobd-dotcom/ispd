<?php

/**
 * PHPUnit bootstrap — loads service classes (no Composer autoloader available).
 */

// Minimal env() helper used by some tests
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// Load service classes under test
$services = [
    'RadiusBulkService',
    'MfaService',
    'IpAccessControlService',
    'RateLimiterService',
    'WebhookService',
    // Integration test services
    'RadiusSessionService',
    'RadiusUsageTrackingService',
    'RadiusAuditService',
    'RadiusSearchService',
    // HR module
    'LoggingService',
    'BaseService',
    'HrService',
];

foreach ($services as $class) {
    $file = __DIR__ . '/../app/Services/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}

// Load helper classes under test
$helpers = [
    'ValidationHelper',
    'PermissionHelper',
];

foreach ($helpers as $class) {
    $file = __DIR__ . '/../app/Helpers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}

// Load Database class (needed for HR unit tests — mocked via PHPUnit MockObject)
$dbConfig = __DIR__ . '/../config/database.php';
if (file_exists($dbConfig) && !class_exists('Database')) {
    // Define BASE_PATH if not already set (needed by Database constructor)
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__));
    }
    require_once $dbConfig;
}

// Load HR test doubles
$testDoubles = [
    __DIR__ . '/Unit/TestableHrService.php',
];
foreach ($testDoubles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}
