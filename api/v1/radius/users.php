<?php
/**
 * RADIUS Users REST API
 *
 * GET              List users (params: username, group, online, limit, offset)
 * GET ?id=username Get single user profile with RADIUS attributes
 * POST             Create user (body: {username, password, group, mac_address, concurrent_session_limit})
 * PUT ?id=username Update user profile (body: profile fields)
 * DELETE ?id=username Delete user
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Services/RadiusService.php';
require_once BASE_PATH . '/app/Services/RadiusSearchService.php';
require_once BASE_PATH . '/app/Services/RadiusUserProfileService.php';

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

function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── Route ─────────────────────────────────────────────────────────────────────
try {
    $pdo           = Database::getInstance('radius')->getConnection();
    $radiusService = new RadiusService();
    $searchService = new RadiusSearchService($pdo);
    $profileService = new RadiusUserProfileService($pdo);

    $method = $_SERVER['REQUEST_METHOD'];
    $id     = $_GET['id'] ?? null;

    switch ($method) {
        case 'GET':
            if ($id !== null) {
                // Single user profile
                $profile = $profileService->getProfileWithRadiusAttributes($id);
                jsonResponse($profile);
            } else {
                // List users
                $criteria = [];
                if (!empty($_GET['username'])) $criteria['username'] = $_GET['username'];
                if (!empty($_GET['group']))    $criteria['group']    = $_GET['group'];
                if (isset($_GET['online']))    $criteria['online']   = filter_var($_GET['online'], FILTER_VALIDATE_BOOLEAN);

                $limit  = max(1, (int)($_GET['limit']  ?? 50));
                $offset = max(0, (int)($_GET['offset'] ?? 0));

                $users = $searchService->searchUsers($criteria, $limit, $offset);
                jsonResponse($users);
            }
            break;

        case 'POST':
            $body = getBody();
            if (empty($body['username']) || empty($body['password'])) {
                jsonError('username and password are required');
                break;
            }

            $username = $body['username'];
            $password = $body['password'];

            $radiusService->addUser($username, $password);

            if (!empty($body['group'])) {
                $radiusService->assignGroup($username, $body['group']);
            }

            $profileData = array_intersect_key($body, array_flip(['mac_address', 'concurrent_session_limit']));
            if (!empty($profileData)) {
                $profileService->saveProfile($username, $profileData);
            }

            jsonResponse(['username' => $username], 201);
            break;

        case 'PUT':
            if ($id === null) {
                jsonError('id (username) is required for PUT');
                break;
            }
            $body = getBody();
            $profileService->saveProfile($id, $body);
            jsonResponse(['username' => $id]);
            break;

        case 'DELETE':
            if ($id === null) {
                jsonError('id (username) is required for DELETE');
                break;
            }
            $radiusService->deleteUser($id);
            jsonResponse(['username' => $id]);
            break;

        default:
            jsonError('Method not allowed', 405);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error', 'code' => 500]);
}
