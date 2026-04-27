<?php

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// Load environment first
$envFile = BASE_PATH . '/.env';
$envData = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;          // skip comments
        if (!str_contains($line, '=')) continue;                  // skip invalid lines
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Strip surrounding quotes
        if (strlen($val) >= 2 && (
            ($val[0] === '"'  && $val[-1] === '"') ||
            ($val[0] === "'"  && $val[-1] === "'")
        )) {
            $val = substr($val, 1, -1);
        }
        // Strip inline comments (# after value, outside quotes)
        if (str_contains($val, ' #')) {
            $val = trim(explode(' #', $val, 2)[0]);
        }
        $envData[$key] = $val;
    }
}

define('APP_NAME', $envData['APP_NAME'] ?? 'FCNCHBD ISP ERP');
define('APP_URL', $envData['APP_URL'] ?? 'http://localhost');
define('APP_TIMEZONE', $envData['APP_TIMEZONE'] ?? 'Asia/Dhaka');
define('APP_DEBUG', ($envData['APP_DEBUG'] ?? 'true') === 'true');

// Save to $_ENV for the env() helper
foreach ($envData as $k => $v) { $_ENV[$k] = $v; }

date_default_timezone_set(APP_TIMEZONE);

// Environment loaded above

// Session config
ini_set('session.gc_maxlifetime', 86400);
session_name('ISP_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/app/Controllers/',
        BASE_PATH . '/app/Controllers/CustomerPortal/',
        BASE_PATH . '/app/Controllers/SuperAdmin/',
        BASE_PATH . '/app/Models/',
        BASE_PATH . '/app/Middleware/',
        BASE_PATH . '/app/Services/',
        BASE_PATH . '/app/Helpers/',
        BASE_PATH . '/app/Core/',
        BASE_PATH . '/config/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Explicitly load multi-class controller files
$multiClassFiles = [
    BASE_PATH . '/app/Controllers/ModuleControllers.php',
    BASE_PATH . '/app/Controllers/PipraPayController.php',
];
foreach ($multiClassFiles as $f) {
    if (file_exists($f)) require_once $f;
}

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("[ERROR] $errstr in $errfile on line $errline");
    }
    return true;
});

function env(string $key, $default = null) {
    return $_ENV[$key] ?? $default;
}

function base_url(string $path = ''): string {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string {
    return base_url('assets/' . ltrim($path, '/'));
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Alias
function json_response(array $data, int $status = 200): void {
    jsonResponse($data, $status);
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatMoney(float $amount): string {
    return '৳ ' . number_format($amount, 2);
}

function formatDate(string $date, string $format = 'd M Y'): string {
    return date($format, strtotime($date));
}

function generateReceiptNumber(): string {
    return 'RCP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generate_random_string(int $length = 8): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $str;
}
