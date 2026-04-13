<?php
/**
 * RADIUS Real-Time Usage API
 * GET ?action=stats|active|top_users
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Services/RadiusSessionService.php';
require_once BASE_PATH . '/app/Services/RadiusUsageTrackingService.php';

header('Content-Type: application/json');

// Auth check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'stats';

try {
    $pdo = Database::getInstance('radius')->getConnection();
    $sessionService = new RadiusSessionService($pdo);
    $usageService   = new RadiusUsageTrackingService($pdo);

    switch ($action) {
        case 'stats':
            $data = $sessionService->getSessionStats();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'active':
            $sessions = $sessionService->getActiveSessions();
            echo json_encode(['success' => true, 'data' => $sessions]);
            break;

        case 'top_users':
            $stmt = $pdo->prepare(
                "SELECT username,
                        SUM(bytes_in)  AS total_bytes_in,
                        SUM(bytes_out) AS total_bytes_out,
                        SUM(bytes_in + bytes_out) AS total_bytes,
                        COUNT(*) AS session_count
                 FROM radius_sessions
                 WHERE DATE(start_time) = CURDATE()
                 GROUP BY username
                 ORDER BY total_bytes DESC
                 LIMIT 10"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'alerts':
            $stmt = $pdo->prepare(
                "SELECT severity, message, alert_type, created_at
                 FROM radius_alerts
                 WHERE resolved_at IS NULL
                 ORDER BY created_at DESC
                 LIMIT 5"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
