<?php

/**
 * AuthMiddleware — session-based authentication gate.
 *
 * Handles:
 *  - Redirecting unauthenticated users to the login page
 *  - Returning 401 JSON for unauthenticated API requests
 *  - Loading user permissions into the session on first access
 *  - Delegating permission checks to PermissionHelper
 *  - Portal-specific authentication (reseller, MAC reseller, employee portals)
 *
 * Usage (in controllers or router):
 *   (new AuthMiddleware())->handle();
 *   (new AuthMiddleware())->checkPermission('hr.view');
 *   (new AuthMiddleware())->requirePermission('hr.view');
 */
class AuthMiddleware
{
    /**
     * Module permission map — maps URL path segments to required permissions.
     * Used for automatic permission enforcement based on the request URI.
     *
     * @var array<string, string>
     */
    private const MODULE_PERMISSION_MAP = [
        'hr'            => 'hr.view',
        'support'       => 'support.view',
        'tasks'         => 'tasks.view',
        'sales'         => 'sales.view',
        'purchases'     => 'purchases.view',
        'inventory'     => 'inventory.view',
        'network'       => 'network.view',
        'accounts'      => 'accounts.view',
        'assets'        => 'assets.view',
        'bandwidth'     => 'bandwidth.view',
        'btrc'          => 'btrc_reports.view',
        'ott'           => 'ott.view',
        'configuration' => 'configuration.view',
        'campaigns'     => 'campaigns.view',
        'roles'         => 'roles.view',
        'branches'      => 'branches.view',
        'api'           => 'api.view',
    ];

    /**
     * Portal session keys — maps portal type to its session identifier.
     *
     * @var array<string, string>
     */
    private const PORTAL_SESSION_KEYS = [
        'reseller'     => 'reseller_id',
        'mac_reseller' => 'mac_reseller_id',
        'employee'     => 'employee_portal_id',
    ];

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
                    'success' => false,
                    'error'   => [
                        'code'      => 'UNAUTHORIZED',
                        'message'   => 'Authentication required.',
                        'timestamp' => date('c'),
                    ],
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
     * Require a specific permission or abort with a 403 response.
     *
     * This is a convenience alias for `checkPermission()` that matches
     * the naming convention used in PermissionHelper.
     *
     * @param  string $permission  Permission name, e.g. "hr.view"
     * @return void
     */
    public function requirePermission(string $permission): void
    {
        PermissionHelper::requirePermission($permission);
    }

    /**
     * Verify that the current user has permission to access the requested module.
     *
     * Inspects the REQUEST_URI to determine which module is being accessed and
     * automatically enforces the corresponding view permission.
     *
     * @return void
     */
    public function handleWithModuleCheck(): void
    {
        $this->handle();

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        foreach (self::MODULE_PERMISSION_MAP as $segment => $permission) {
            // Match /segment or /segment/ or /segment/anything
            if (preg_match('#/' . preg_quote($segment, '#') . '(/|$)#i', $uri)) {
                $this->requirePermission($permission);
                return;
            }
        }
    }

    /**
     * Verify that a portal user is authenticated for the given portal type.
     *
     * Portal types: 'reseller', 'mac_reseller', 'employee'
     *
     * Each portal uses its own session namespace to prevent cross-portal access.
     * If the portal session is not set, redirects to the portal login page.
     *
     * @param  string $portalType  One of: 'reseller', 'mac_reseller', 'employee'
     * @return void
     */
    public function handlePortal(string $portalType): void
    {
        $sessionKey = self::PORTAL_SESSION_KEYS[$portalType] ?? null;

        if ($sessionKey === null) {
            http_response_code(500);
            error_log("AuthMiddleware::handlePortal — unknown portal type: {$portalType}");
            exit;
        }

        if (!isset($_SESSION[$sessionKey])) {
            $loginRoutes = [
                'reseller'     => 'reseller/login',
                'mac_reseller' => 'mac-reseller/login',
                'employee'     => 'employee-portal/login',
            ];

            $loginRoute = $loginRoutes[$portalType] ?? 'login';

            if (function_exists('base_url')) {
                header('Location: ' . base_url($loginRoute));
            } else {
                header('Location: /' . $loginRoute);
            }
            exit;
        }
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
