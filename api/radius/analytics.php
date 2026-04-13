<?php
/**
 * RADIUS Analytics API
 * GET ?action=top_users&period=today|week|month
 * GET ?action=hourly&date=YYYY-MM-DD
 * GET ?action=daily_summary&from=YYYY-MM-DD&to=YYYY-MM-DD
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Services/RadiusAnalyticsService.php';

header('Content-Type: application/json');

// Auth check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = Database::getInstance('radius')->getConnection();
    $analytics = new RadiusAnalyticsService($pdo);

    switch ($action) {
        case 'top_users':
            $period = in_array($_GET['period'] ?? '', ['today', 'week', 'month'])
                ? $_GET['period']
                : 'today';
            $data = $analytics->getTopUsersByUsage(10, $period);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'hourly':
            $date = $_GET['date'] ?? date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid date format']);
                exit;
            }
            $data = $analytics->getHourlySessionCounts($date);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'daily_summary':
            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-6 days'));
            $to   = $_GET['to']   ?? date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid date format']);
                exit;
            }
            $data = $analytics->getDailyUsageSummary($from, $to);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
