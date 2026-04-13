<?php
/**
 * API v1 - Top-level index / resource discovery
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/config/app.php';

header('Content-Type: application/json');
header('X-API-Version: 1.0');

// ── Auth ──────────────────────────────────────────────────────────────────────
function isAuthorized(): bool {
    if (!empty($_SESSION['user_id'])) {
        return true;
    }
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $envKey = getenv('API_KEY') ?: '';
    return $envKey !== '' && hash_equals($envKey, $apiKey);
}

if (!isAuthorized()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized', 'code' => 401]);
    exit;
}

// ── Response ──────────────────────────────────────────────────────────────────
echo json_encode([
    'version'   => '1.0',
    'resources' => [
        'users'    => '/api/v1/radius/users',
        'sessions' => '/api/v1/radius/sessions',
    ],
]);
