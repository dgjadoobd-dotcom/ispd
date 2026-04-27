<?php

/**
 * PermissionHelper — static utility class for role-based access control.
 *
 * Provides permission checks, branch data isolation, and session-based
 * user context helpers for all 20 modules in the FCNCHBD ISP ERP system.
 *
 * Permissions follow the format: `module.action`
 * e.g. `hr.view`, `hr.edit`, `billing.delete`
 *
 * Roles:
 *   - superadmin  : full access, no branch filter
 *   - comadmin    : company-level admin, no branch filter
 *   - branch_admin: restricted to their own branch_id
 *
 * Usage:
 *   PermissionHelper::requirePermission('hr.view');
 *   $branchId = PermissionHelper::getBranchFilter();
 *   $sql = PermissionHelper::applyBranchFilter($sql, $params, 'e');
 */
class PermissionHelper
{
    // ── All available permissions grouped by module ───────────────

    /**
     * Complete permission map for all 20 modules.
     * Format: module => [action, ...]
     *
     * @var array<string, string[]>
     */
    public const MODULE_PERMISSIONS = [
        'branches' => [
            'branches.view',
            'branches.create',
            'branches.edit',
            'branches.delete',
            'branches.reports',
        ],
        'hr' => [
            'hr.view',
            'hr.create',
            'hr.edit',
            'hr.delete',
            'hr.payroll',
            'hr.attendance',
            'hr.appraisal',
            'hr.leave',
        ],
        'support' => [
            'support.view',
            'support.create',
            'support.edit',
            'support.delete',
            'support.assign',
            'support.resolve',
            'support.reports',
        ],
        'tasks' => [
            'tasks.view',
            'tasks.create',
            'tasks.edit',
            'tasks.delete',
            'tasks.assign',
            'tasks.reports',
        ],
        'sales' => [
            'sales.view',
            'sales.create',
            'sales.edit',
            'sales.delete',
            'sales.payments',
            'sales.cancel',
            'sales.reports',
        ],
        'purchases' => [
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchases.delete',
            'purchases.approve',
            'purchases.payments',
            'purchases.reports',
        ],
        'inventory' => [
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',
            'inventory.issue',
            'inventory.transfer',
            'inventory.reports',
        ],
        'network' => [
            'network.view',
            'network.create',
            'network.edit',
            'network.delete',
            'network.export',
        ],
        'accounts' => [
            'accounts.view',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',
            'accounts.approve',
            'accounts.reports',
        ],
        'assets' => [
            'assets.view',
            'assets.create',
            'assets.edit',
            'assets.delete',
            'assets.dispose',
            'assets.reports',
        ],
        'bandwidth' => [
            'bandwidth.view',
            'bandwidth.create',
            'bandwidth.edit',
            'bandwidth.delete',
            'bandwidth.invoices',
            'bandwidth.reports',
        ],
        'reseller_portal' => [
            'reseller_portal.view',
            'reseller_portal.create',
            'reseller_portal.edit',
            'reseller_portal.delete',
            'reseller_portal.suspend',
        ],
        'mac_reseller' => [
            'mac_reseller.view',
            'mac_reseller.create',
            'mac_reseller.edit',
            'mac_reseller.delete',
            'mac_reseller.billing',
            'mac_reseller.suspend',
        ],
        'employee_portal' => [
            'employee_portal.view',
            'employee_portal.create',
            'employee_portal.edit',
            'employee_portal.delete',
            'employee_portal.collections',
        ],
        'btrc_reports' => [
            'btrc_reports.view',
            'btrc_reports.generate',
            'btrc_reports.export',
        ],
        'ott' => [
            'ott.view',
            'ott.create',
            'ott.edit',
            'ott.delete',
            'ott.activate',
            'ott.deactivate',
        ],
        'roles' => [
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign',
        ],
        'campaigns' => [
            'campaigns.view',
            'campaigns.create',
            'campaigns.edit',
            'campaigns.delete',
            'campaigns.send',
            'campaigns.reports',
        ],
        'api' => [
            'api.view',
            'api.manage',
            'api.tokens',
        ],
        'configuration' => [
            'configuration.view',
            'configuration.edit',
            'configuration.zones',
            'configuration.packages',
            'configuration.billing_rules',
            'configuration.templates',
        ],
    ];

    // ── Permission checks ─────────────────────────────────────────

    /**
     * Check whether the currently authenticated user has a specific permission.
     *
     * Checks `$_SESSION['permissions']` (array of permission name strings).
     * Super-admins and comadmins bypass all permission checks.
     * If permissions are not yet loaded into the session, loads them from DB.
     *
     * @param  string $permission  Permission name, e.g. "hr.view"
     * @return bool
     */
    public static function hasPermission(string $permission): bool
    {
        // Superadmin and comadmin bypass all permission checks
        if (self::isComAdmin()) {
            return true;
        }

        // Ensure permissions are loaded
        if (!isset($_SESSION['permissions'])) {
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId !== null) {
                self::loadUserPermissions((int)$userId);
            }
        }

        $permissions = $_SESSION['permissions'] ?? [];

        return in_array($permission, $permissions, strict: true);
    }

    /**
     * Require a specific permission or abort with a 403 response.
     *
     * For web requests, renders a 403 page or redirects.
     * For API requests (URI contains /api/), returns a JSON 403 response.
     *
     * @param  string $permission  Permission name, e.g. "billing.view"
     * @return void
     */
    public static function requirePermission(string $permission): void
    {
        if (self::hasPermission($permission)) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (str_contains($uri, '/api/')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => [
                    'code'      => 'PERMISSION_DENIED',
                    'message'   => "You do not have permission to perform this action: {$permission}",
                    'timestamp' => date('c'),
                ],
            ]);
            exit;
        }

        // Web request — set flash message and redirect or show 403
        http_response_code(403);

        if (isset($_SESSION)) {
            $_SESSION['error'] = "Access denied. You do not have the '{$permission}' permission.";
        }

        // If a base_url helper is available, redirect to dashboard
        if (function_exists('base_url')) {
            header('Location: ' . base_url('dashboard'));
            exit;
        }

        // Fallback: plain 403 page
        echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>';
        echo '<h1>403 Forbidden</h1>';
        echo '<p>You do not have permission to access this resource.</p>';
        echo '</body></html>';
        exit;
    }

    // ── Branch data isolation ─────────────────────────────────────

    /**
     * Return the branch ID filter for the current user.
     *
     * Returns `$_SESSION['branch_id']` for `branch_admin` users so that
     * all queries are scoped to their branch. Returns `null` for `comadmin`
     * and `superadmin` users (no filter — they see all branches).
     *
     * @return int|null  Branch ID to filter by, or null for no filter
     */
    public static function getBranchFilter(): ?int
    {
        if (self::isBranchAdmin()) {
            $branchId = $_SESSION['branch_id'] ?? null;
            return $branchId !== null ? (int)$branchId : null;
        }

        return null;
    }

    /**
     * Append a branch_id filter to a SQL query if the current user is a branch_admin.
     *
     * Detects whether the query already has a WHERE clause and appends
     * either `WHERE` or `AND` accordingly.
     *
     * @param  string  $sql         The SQL query string to modify
     * @param  array   &$params     Bound parameter array (modified in-place)
     * @param  string  $tableAlias  Optional table alias prefix (e.g. 'e' → `e.branch_id`)
     * @return string               Modified SQL string
     *
     * @example
     *   $sql = "SELECT * FROM employees";
     *   $sql = PermissionHelper::applyBranchFilter($sql, $params, 'e');
     *   // branch_admin: "SELECT * FROM employees WHERE e.branch_id = ?"
     *   // comadmin:     "SELECT * FROM employees"  (unchanged)
     */
    public static function applyBranchFilter(
        string $sql,
        array &$params,
        string $tableAlias = ''
    ): string {
        $branchId = self::getBranchFilter();

        if ($branchId === null) {
            return $sql;
        }

        $column = $tableAlias !== ''
            ? "`{$tableAlias}`.`branch_id`"
            : '`branch_id`';

        // Determine whether to use WHERE or AND
        $hasWhere = stripos($sql, ' WHERE ') !== false;
        $connector = $hasWhere ? ' AND ' : ' WHERE ';

        $params[] = $branchId;

        return $sql . $connector . $column . ' = ?';
    }

    // ── User context helpers ──────────────────────────────────────

    /**
     * Return the current authenticated user's ID.
     *
     * @return int  User ID from session
     * @throws \RuntimeException if no user is authenticated
     */
    public static function getCurrentUserId(): int
    {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId === null) {
            throw new \RuntimeException('No authenticated user in session.');
        }

        return (int)$userId;
    }

    /**
     * Return the current authenticated user's role name.
     *
     * @return string  Role name from session (e.g. "comadmin", "branch_admin")
     */
    public static function getCurrentUserRole(): string
    {
        return (string)($_SESSION['user_role'] ?? '');
    }

    /**
     * Check whether the current user is a company-level administrator.
     *
     * Both `comadmin` and `superadmin` roles are treated as company admins
     * with unrestricted access across all branches.
     *
     * @return bool
     */
    public static function isComAdmin(): bool
    {
        $role = self::getCurrentUserRole();
        return in_array($role, ['comadmin', 'superadmin'], strict: true);
    }

    /**
     * Check whether the current user is a branch-level administrator.
     *
     * Branch admins are restricted to data belonging to their assigned branch.
     *
     * @return bool
     */
    public static function isBranchAdmin(): bool
    {
        return self::getCurrentUserRole() === 'branch_admin';
    }

    // ── Permission loading ────────────────────────────────────────

    /**
     * Load a user's permissions from the database and cache them in the session.
     *
     * Queries the `role_permissions` and `permissions` tables via the user's
     * assigned role. The result is stored in `$_SESSION['permissions']` as a
     * flat array of permission name strings.
     *
     * @param  int   $userId  The user ID to load permissions for
     * @return array          Array of permission name strings
     */
    public static function loadUserPermissions(int $userId): array
    {
        try {
            $db = Database::getInstance();

            $permissions = $db->fetchAll(
                "SELECT p.name
                 FROM permissions p
                 INNER JOIN role_permissions rp ON rp.permission_id = p.id
                 INNER JOIN users u ON u.role_id = rp.role_id
                 WHERE u.id = ?",
                [$userId]
            );

            $permissionNames = array_column($permissions, 'name');

            // Cache in session
            $_SESSION['permissions'] = $permissionNames;

            return $permissionNames;
        } catch (\Throwable $e) {
            // Fail safe: return empty permissions on DB error
            $_SESSION['permissions'] = [];
            return [];
        }
    }

    /**
     * Seed default permissions for all 20 modules into the `permissions` table.
     *
     * This method is idempotent — it uses INSERT IGNORE (or equivalent) so
     * running it multiple times will not create duplicates.
     *
     * Also seeds default roles (superadmin, comadmin, branch_admin) and assigns
     * all permissions to the superadmin role.
     *
     * @return void
     */
    public static function seedDefaultPermissions(): void
    {
        try {
            $db = Database::getInstance();

            // Flatten all permissions into a single list
            $allPermissions = [];
            foreach (self::MODULE_PERMISSIONS as $module => $permissions) {
                foreach ($permissions as $permissionName) {
                    $allPermissions[] = [
                        'name'   => $permissionName,
                        'module' => $module,
                    ];
                }
            }

            // Insert permissions (skip duplicates)
            foreach ($allPermissions as $perm) {
                $existing = $db->fetchOne(
                    "SELECT id FROM permissions WHERE name = ? LIMIT 1",
                    [$perm['name']]
                );

                if ($existing === null) {
                    $db->insert('permissions', [
                        'name'        => $perm['name'],
                        'module'      => $perm['module'],
                        'description' => ucfirst(str_replace('.', ' ', $perm['name'])),
                    ]);
                }
            }

            // Ensure default roles exist
            $defaultRoles = [
                ['name' => 'superadmin',   'display_name' => 'Super Admin',    'description' => 'Full system access'],
                ['name' => 'comadmin',     'display_name' => 'Company Admin',  'description' => 'Company-level administrator'],
                ['name' => 'branch_admin', 'display_name' => 'Branch Admin',   'description' => 'Branch-level administrator'],
                ['name' => 'employee',     'display_name' => 'Employee',       'description' => 'Standard employee access'],
            ];

            foreach ($defaultRoles as $role) {
                $existing = $db->fetchOne(
                    "SELECT id FROM roles WHERE name = ? LIMIT 1",
                    [$role['name']]
                );

                if ($existing === null) {
                    $db->insert('roles', $role);
                }
            }

            // Assign ALL permissions to superadmin role
            $superadminRole = $db->fetchOne(
                "SELECT id FROM roles WHERE name = 'superadmin' LIMIT 1",
                []
            );

            if ($superadminRole !== null) {
                $superadminId = (int)$superadminRole['id'];

                $allDbPermissions = $db->fetchAll(
                    "SELECT id FROM permissions",
                    []
                );

                foreach ($allDbPermissions as $perm) {
                    $existing = $db->fetchOne(
                        "SELECT role_id FROM role_permissions WHERE role_id = ? AND permission_id = ? LIMIT 1",
                        [$superadminId, $perm['id']]
                    );

                    if ($existing === null) {
                        $db->insert('role_permissions', [
                            'role_id'       => $superadminId,
                            'permission_id' => $perm['id'],
                        ]);
                    }
                }
            }

            // Assign comadmin all permissions except roles.delete
            $comadminRole = $db->fetchOne(
                "SELECT id FROM roles WHERE name = 'comadmin' LIMIT 1",
                []
            );

            if ($comadminRole !== null) {
                $comadminId = (int)$comadminRole['id'];

                $comadminPermissions = $db->fetchAll(
                    "SELECT id FROM permissions WHERE name != 'roles.delete'",
                    []
                );

                foreach ($comadminPermissions as $perm) {
                    $existing = $db->fetchOne(
                        "SELECT role_id FROM role_permissions WHERE role_id = ? AND permission_id = ? LIMIT 1",
                        [$comadminId, $perm['id']]
                    );

                    if ($existing === null) {
                        $db->insert('role_permissions', [
                            'role_id'       => $comadminId,
                            'permission_id' => $perm['id'],
                        ]);
                    }
                }
            }

            // Assign branch_admin a limited set of view/create/edit permissions
            $branchAdminRole = $db->fetchOne(
                "SELECT id FROM roles WHERE name = 'branch_admin' LIMIT 1",
                []
            );

            if ($branchAdminRole !== null) {
                $branchAdminId = (int)$branchAdminRole['id'];

                $branchAdminPermissions = $db->fetchAll(
                    "SELECT id FROM permissions WHERE name LIKE '%.view'
                      OR name LIKE '%.create'
                      OR name LIKE '%.edit'
                      OR name LIKE '%.reports'
                      OR name LIKE '%.attendance'
                      OR name LIKE '%.payroll'
                      OR name LIKE '%.assign'
                      OR name LIKE '%.resolve'
                      OR name LIKE '%.payments'
                      OR name LIKE '%.collections'",
                    []
                );

                foreach ($branchAdminPermissions as $perm) {
                    $existing = $db->fetchOne(
                        "SELECT role_id FROM role_permissions WHERE role_id = ? AND permission_id = ? LIMIT 1",
                        [$branchAdminId, $perm['id']]
                    );

                    if ($existing === null) {
                        $db->insert('role_permissions', [
                            'role_id'       => $branchAdminId,
                            'permission_id' => $perm['id'],
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log but do not throw — seeding is a best-effort operation
            error_log('PermissionHelper::seedDefaultPermissions failed: ' . $e->getMessage());
        }
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * Return a flat list of all permission names across all modules.
     *
     * @return string[]
     */
    public static function getAllPermissionNames(): array
    {
        $names = [];
        foreach (self::MODULE_PERMISSIONS as $permissions) {
            foreach ($permissions as $name) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * Return all permissions for a specific module.
     *
     * @param  string   $module  Module name (e.g. "hr", "billing")
     * @return string[]          Array of permission names, or empty array if module not found
     */
    public static function getModulePermissions(string $module): array
    {
        return self::MODULE_PERMISSIONS[$module] ?? [];
    }
}
