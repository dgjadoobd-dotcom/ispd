<?php
/**
 * RADIUS API v1 - Version Discovery / Info Endpoint
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

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
    'version'    => '1.0',
    'status'     => 'stable',
    'endpoints'  => [
        ['method' => 'GET|POST|PUT|DELETE', 'path' => '/api/v1/radius/users',    'description' => 'RADIUS user management'],
        ['method' => 'GET|DELETE',          'path' => '/api/v1/radius/sessions', 'description' => 'RADIUS session management'],
    ],
    'deprecated' => [],
]);
