<?php

/**
 * AuthMiddleware — session-based authentication gate.
 *
 * Handles:
 *  - Redirecting unauthenticated users to the login page
 *  - Returning 401 JSON for unauthenticated API requests
 *  - Loading user permissions into the session on first access
 *  - Delegating permission checks to PermissionHelper
 *
 * Usage (in controllers or router):
 *   (new AuthMiddleware())->handle();
 *   (new AuthMiddleware())->checkPermission('hr.view');
 */
class AuthMiddleware
{
    /**
     * Verify that a user is authenticated.
     *
     * If no session exists, API requests receive a 401 JSON response and
     * web requests are redirected to the login page.
     *
     * Also ensures user permissions are loaded into the session so that
     * subsequent `PermissionHelper::hasPermission()` calls are fast.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!isset($_SESSION['user_id'])) {
            // For API requests
            if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => 'Unauthorized',
                    'code'  => 401,
                ]);
                exit;
            }

            if (function_exists('redirect') && function_exists('base_url')) {
                redirect(base_url('login'));
            } else {
                header('Location: /login');
                exit;
            }
        }

        // Load permissions into session if not already present
        $this->ensurePermissionsLoaded();
    }

    /**
     * Check that the authenticated user has a specific permission.
     *
     * Delegates to `PermissionHelper::requirePermission()` which will
     * abort with a 403 response if the permission is not held.
     *
     * @param  string $permission  Permission name, e.g. "hr.view"
     * @return void
     */
    public function checkPermission(string $permission): void
    {
        PermissionHelper::requirePermission($permission);
    }

    /**
     * Load user permissions into the session if they are not already cached.
     *
     * This is called automatically by `handle()` so that every authenticated
     * request has permissions available without an extra DB query.
     *
     * @return void
     */
    private function ensurePermissionsLoaded(): void
    {
        if (isset($_SESSION['permissions'])) {
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if ($userId === null) {
            return;
        }

        // Superadmin and comadmin bypass permission loading — they have all permissions
        $role = $_SESSION['user_role'] ?? '';
        if (in_array($role, ['comadmin', 'superadmin'], strict: true)) {
            // Mark as loaded with a sentinel so we don't re-check every request
            $_SESSION['permissions'] = ['*'];
            return;
        }

        PermissionHelper::loadUserPermissions((int)$userId);
    }
}
