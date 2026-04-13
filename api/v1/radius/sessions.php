<?php
/**
 * RADIUS Sessions REST API
 *
 * GET              List active sessions (params: username, nas_ip)
 * GET ?stats=1     Return session statistics
 * DELETE ?id=sid   Terminate a session by session_id
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Services/RadiusSessionService.php';
require_once BASE_PATH . '/app/Services/RadiusSessionTimeoutService.php';

header('Content-Type: application/json');
header('X-API-Version: 1.0');

// ── Auth ─────────────────────────────────────────────────────────────────────
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

// ── Helpers ───────────────────────────────────────────────────────────────────
function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data]);
}

function jsonError(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message, 'code' => $status]);
}

// ── Route ─────────────────────────────────────────────────────────────────────
try {
    $pdo            = Database::getInstance('radius')->getConnection();
    $sessionService = new RadiusSessionService($pdo);
    $timeoutService = new RadiusSessionTimeoutService($pdo, $sessionService);

    $method = $_SERVER['REQUEST_METHOD'];
    $id     = $_GET['id'] ?? null;

    switch ($method) {
        case 'GET':
            if (!empty($_GET['stats'])) {
                jsonResponse($sessionService->getSessionStats());
            } else {
                $filters = [];
                if (!empty($_GET['username'])) $filters['username'] = $_GET['username'];
                if (!empty($_GET['nas_ip']))   $filters['nas_ip']   = $_GET['nas_ip'];

                jsonResponse($sessionService->getActiveSessions($filters));
            }
            break;

        case 'DELETE':
            if ($id === null) {
                jsonError('id (session_id) is required for DELETE');
                break;
            }
            $result = $timeoutService->terminateSession($id);
            jsonResponse(['session_id' => $id, 'terminated' => $result]);
            break;

        default:
            jsonError('Method not allowed', 405);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error', 'code' => 500]);
}
