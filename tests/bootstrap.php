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
];

foreach ($services as $class) {
    $file = __DIR__ . '/../app/Services/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}
