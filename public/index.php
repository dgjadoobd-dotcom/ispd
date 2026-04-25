<?php

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';

// Handle CORS for API
if (str_contains($_SERVER['REQUEST_URI'], '/api/')) {
    $allowedOrigins = explode(',', getenv('CORS_ALLOWED_ORIGINS', 'http://localhost,http://localhost:3000,http://127.0.0.1'));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    $originHost = parse_url($origin, PHP_URL_HOST) ?? '';
    
    foreach ($allowedOrigins as $allowed) {
        if (str_contains($originHost, trim($allowed))) {
            header('Access-Control-Allow-Origin: ' . $origin);
            break;
        }
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
}

// Parse the request
$requestUri  = $_SERVER['REQUEST_URI'];
$scriptName  = dirname($_SERVER['SCRIPT_NAME']);
$path        = str_replace($scriptName, '', $requestUri);
$path        = strtok($path, '?');
$path        = '/' . trim($path, '/');
$method      = $_SERVER['REQUEST_METHOD'];

// Load routes
require_once BASE_PATH . '/routes/web.php';
require_once BASE_PATH . '/routes/api.php';
