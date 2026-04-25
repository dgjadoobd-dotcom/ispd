<?php
/**
 * SuperAdminMiddleware
 * Restricts access to superadmin-only routes.
 */
class SuperAdminMiddleware
{
    public function handle(): void
    {
        if (!isset($_SESSION['user_id'])) {
            if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized', 'code' => 401]);
                exit;
            }
            redirect(base_url('superadmin/login'));
            return;
        }

        $role = $_SESSION['user_role'] ?? '';
        if (!in_array($role, ['superadmin', 'comadmin'], true)) {
            http_response_code(403);
            require_once BASE_PATH . '/views/errors/403.php';
            exit;
        }
    }
}
