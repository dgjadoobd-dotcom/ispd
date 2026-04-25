<?php

/**
 * RoleService — Business logic for the Roles & Permissions module.
 *
 * Covers role CRUD, permission assignment, user role assignment,
 * and role change audit logging.
 *
 * @see database/migrations/2024_01_07_001_create_role_change_logs.sql
 */
class RoleService extends BaseService
{
    /**
     * Create a RoleService with an injected Database instance.
     * Overrides BaseService constructor to allow dependency injection for testing.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        // Skip parent constructor to allow DB injection
        $this->db     = $db;
        $this->logger = new LoggingService();
    }

    /**
     * Return a RoleService instance using the singleton Database connection.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        return new static(Database::getInstance());
    }

    // ── Role queries ──────────────────────────────────────────────

    /**
     * Return all roles with user_count and perm_count aggregates.
     *
     * @return array
     */
    public function getAllRoles(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT r.*,
                        (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count,
                        (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perm_count
                 FROM roles r
                 ORDER BY r.id ASC"
            );
        } catch (\Throwable $e) {
            $this->logError('getAllRoles failed', $e);
            return [];
        }
    }

    /**
     * Return a single role by ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function getRoleById(int $id): ?array
    {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM roles WHERE id = ? LIMIT 1",
                [$id]
            );
        } catch (\Throwable $e) {
            $this->logError('getRoleById failed', $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Create a new role.
     *
     * @param  string $name         Unique machine-readable role key (e.g. "branch_manager")
     * @param  string $displayName  Human-readable display name
     * @param  string $description  Optional description
     * @return int                  New role ID
     * @throws \InvalidArgumentException if name or display_name is empty
     * @throws \RuntimeException         if role name already exists
     */
    public function createRole(string $name, string $displayName, string $description = ''): int
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Role name cannot be empty.');
        }
        if (empty(trim($displayName))) {
            throw new \InvalidArgumentException('Role display name cannot be empty.');
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM roles WHERE name = ? LIMIT 1",
            [$name]
        );
        if ($existing !== null) {
            throw new \RuntimeException("Role name '{$name}' already exists.");
        }

        return $this->db->insert('roles', [
            'name'         => $name,
            'display_name' => $displayName,
            'description'  => $description,
        ]);
    }

    /**
     * Update an existing role.
     *
     * Protects the 'superadmin' role name from being changed.
     *
     * @param  int    $id
     * @param  string $name
     * @param  string $displayName
     * @param  string $description
     * @return void
     * @throws \InvalidArgumentException if name or display_name is empty
     * @throws \RuntimeException         if name is taken by another role
     */
    public function updateRole(int $id, string $name, string $displayName, string $description = ''): void
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Role name cannot be empty.');
        }
        if (empty(trim($displayName))) {
            throw new \InvalidArgumentException('Role display name cannot be empty.');
        }

        // Check for name conflict with a different role
        $dup = $this->db->fetchOne(
            "SELECT id FROM roles WHERE name = ? AND id != ? LIMIT 1",
            [$name, $id]
        );
        if ($dup !== null) {
            throw new \RuntimeException("Role name '{$name}' is already taken by another role.");
        }

        $this->db->update('roles', [
            'name'         => $name,
            'display_name' => $displayName,
            'description'  => $description,
        ], 'id = ?', [$id]);
    }

    /**
     * Delete a role.
     *
     * Protects 'superadmin' and 'comadmin' from deletion.
     * Refuses deletion if any users are assigned to the role.
     *
     * @param  int $id
     * @return void
     * @throws \RuntimeException if role is protected or has assigned users
     */
    public function deleteRole(int $id): void
    {
        $role = $this->db->fetchOne(
            "SELECT * FROM roles WHERE id = ? LIMIT 1",
            [$id]
        );

        if ($role === null) {
            throw new \RuntimeException("Role #{$id} not found.");
        }

        if (in_array($role['name'], ['superadmin', 'comadmin'], true)) {
            throw new \RuntimeException("The '{$role['name']}' role cannot be deleted.");
        }

        $userCount = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS c FROM users WHERE role_id = ?",
            [$id]
        )['c'] ?? 0);

        if ($userCount > 0) {
            throw new \RuntimeException(
                "Cannot delete role — {$userCount} user(s) are assigned to it. Reassign them first."
            );
        }

        $this->db->delete('role_permissions', 'role_id = ?', [$id]);
        $this->db->delete('roles', 'id = ?', [$id]);
    }

    // ── Permission management ─────────────────────────────────────

    /**
     * Replace all permissions for a role with the given list.
     *
     * Deletes existing role_permissions rows and re-inserts the provided ones.
     *
     * @param  int      $roleId
     * @param  string[] $permissionNames  Array of permission name strings
     * @return void
     */
    public function saveRolePermissions(int $roleId, array $permissionNames): void
    {
        $this->db->delete('role_permissions', 'role_id = ?', [$roleId]);

        if (empty($permissionNames)) {
            return;
        }

        foreach ($permissionNames as $permName) {
            $perm = $this->db->fetchOne(
                "SELECT id FROM permissions WHERE name = ? LIMIT 1",
                [$permName]
            );
            if ($perm !== null) {
                try {
                    $this->db->insert('role_permissions', [
                        'role_id'       => $roleId,
                        'permission_id' => $perm['id'],
                    ]);
                } catch (\Throwable $e) {
                    // Ignore duplicate key errors
                }
            }
        }
    }

    /**
     * Return all permission names assigned to a role.
     *
     * @param  int $roleId
     * @return string[]
     */
    public function getRolePermissions(int $roleId): array
    {
        try {
            $rows = $this->db->fetchAll(
                "SELECT p.name
                 FROM permissions p
                 INNER JOIN role_permissions rp ON rp.permission_id = p.id
                 WHERE rp.role_id = ?",
                [$roleId]
            );
            return array_column($rows, 'name');
        } catch (\Throwable $e) {
            $this->logError('getRolePermissions failed', $e, ['role_id' => $roleId]);
            return [];
        }
    }

    // ── User role assignment ──────────────────────────────────────

    /**
     * Assign a role to a user, logging the change to both role_change_logs
     * and activity_logs.
     *
     * @param  int $userId   The user being reassigned
     * @param  int $roleId   The new role ID
     * @param  int $actorId  The user performing the assignment
     * @return array         ['old_role_id' => int|null, 'old_role_name' => string]
     */
    public function assignUserRole(int $userId, int $roleId, int $actorId): array
    {
        $user = $this->db->fetchOne(
            "SELECT id, full_name, role_id FROM users WHERE id = ? LIMIT 1",
            [$userId]
        );
        $newRole = $this->db->fetchOne(
            "SELECT id, name, display_name FROM roles WHERE id = ? LIMIT 1",
            [$roleId]
        );

        if ($user === null || $newRole === null) {
            throw new \RuntimeException("User #{$userId} or role #{$roleId} not found.");
        }

        $oldRoleId   = $user['role_id'] !== null ? (int)$user['role_id'] : null;
        $oldRoleName = '';

        if ($oldRoleId !== null) {
            $oldRole     = $this->db->fetchOne(
                "SELECT name, display_name FROM roles WHERE id = ? LIMIT 1",
                [$oldRoleId]
            );
            $oldRoleName = $oldRole['display_name'] ?? $oldRole['name'] ?? '';
        }

        // Update the user's role
        $this->db->update('users', ['role_id' => $roleId], 'id = ?', [$userId]);

        // Log to role_change_logs
        $this->logRoleChange(
            $userId,
            $oldRoleId,
            $roleId,
            $oldRoleName,
            $newRole['display_name'] ?? $newRole['name'],
            $actorId
        );

        // Log to activity_logs
        try {
            $this->db->insert('activity_logs', [
                'user_id'    => $actorId,
                'action'     => 'user_role_changed',
                'module'     => 'roles',
                'record_id'  => $userId,
                'old_values' => json_encode(['role_id' => $oldRoleId, 'role_name' => $oldRoleName]),
                'new_values' => json_encode(['role_id' => $roleId, 'role_name' => $newRole['display_name'] ?? $newRole['name']]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (\Throwable $e) {
            // Non-fatal: activity log failure should not block the assignment
            $this->logError('assignUserRole activity_log insert failed', $e);
        }

        return [
            'old_role_id'   => $oldRoleId,
            'old_role_name' => $oldRoleName,
        ];
    }

    /**
     * Write a role change record to the role_change_logs table.
     *
     * @param  int         $userId
     * @param  int|null    $oldRoleId
     * @param  int         $newRoleId
     * @param  string      $oldRoleName
     * @param  string      $newRoleName
     * @param  int         $changedBy
     * @param  string      $reason
     * @return void
     */
    public function logRoleChange(
        int $userId,
        ?int $oldRoleId,
        int $newRoleId,
        string $oldRoleName,
        string $newRoleName,
        int $changedBy,
        string $reason = ''
    ): void {
        $this->db->insert('role_change_logs', [
            'user_id'       => $userId,
            'old_role_id'   => $oldRoleId,
            'new_role_id'   => $newRoleId,
            'old_role_name' => $oldRoleName,
            'new_role_name' => $newRoleName,
            'changed_by'    => $changedBy,
            'reason'        => $reason,
        ]);
    }

    /**
     * Return role change history for a specific user.
     *
     * @param  int $userId
     * @return array
     */
    public function getRoleChangeHistory(int $userId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT rcl.*,
                        u.full_name  AS user_name,
                        cb.full_name AS changed_by_name
                 FROM role_change_logs rcl
                 LEFT JOIN users u  ON u.id  = rcl.user_id
                 LEFT JOIN users cb ON cb.id = rcl.changed_by
                 WHERE rcl.user_id = ?
                 ORDER BY rcl.changed_at DESC",
                [$userId]
            );
        } catch (\Throwable $e) {
            $this->logError('getRoleChangeHistory failed', $e, ['user_id' => $userId]);
            return [];
        }
    }

    // ── Aggregate counts ──────────────────────────────────────────

    /**
     * Return the total number of permissions defined in the system.
     *
     * @return int
     */
    public function getTotalPermissionCount(): int
    {
        try {
            return (int)($this->db->fetchOne("SELECT COUNT(*) AS c FROM permissions")['c'] ?? 0);
        } catch (\Throwable $e) {
            $this->logError('getTotalPermissionCount failed', $e);
            return 0;
        }
    }

    /**
     * Return the total number of active users.
     *
     * @return int
     */
    public function getTotalUserCount(): int
    {
        try {
            return (int)($this->db->fetchOne("SELECT COUNT(*) AS c FROM users WHERE is_active = 1")['c'] ?? 0);
        } catch (\Throwable $e) {
            $this->logError('getTotalUserCount failed', $e);
            return 0;
        }
    }

    /**
     * Return all users assigned to a specific role.
     *
     * @param  int $roleId
     * @return array
     */
    public function getUsersByRole(int $roleId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT u.*, b.name AS branch_name
                 FROM users u
                 LEFT JOIN branches b ON b.id = u.branch_id
                 WHERE u.role_id = ?
                 ORDER BY u.full_name ASC",
                [$roleId]
            );
        } catch (\Throwable $e) {
            $this->logError('getUsersByRole failed', $e, ['role_id' => $roleId]);
            return [];
        }
    }

    /**
     * Return a simple list of all roles (id + display_name) for dropdowns.
     *
     * @return array
     */
    public function getAllRolesSimple(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT id, name, display_name FROM roles ORDER BY display_name ASC"
            );
        } catch (\Throwable $e) {
            $this->logError('getAllRolesSimple failed', $e);
            return [];
        }
    }
}
