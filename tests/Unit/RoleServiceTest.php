<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for RoleService.
 *
 * Validates:
 *   - Permission enforcement via PermissionHelper::hasPermission()
 *   - Role validation (empty name/display_name, duplicate name)
 *   - Superadmin/comadmin protection in deleteRole()
 *   - Role assignment logging to role_change_logs
 *   - Permission saving (delete + re-insert)
 *
 * Uses TestableRoleService to inject a mock Database, avoiding real DB connections.
 */
class RoleServiceTest extends TestCase
{
    /** @var MockObject&\Database */
    private MockObject $db;

    /** @var \RoleService */
    private \RoleService $service;

    protected function setUp(): void
    {
        $_SESSION = [];

        // Create a mock Database object (not a real connection)
        $this->db = $this->createMock(\Database::class);

        // Use the testable subclass that accepts an injected DB
        $this->service = new \TestableRoleService($this->db);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ── Permission enforcement ────────────────────────────────────

    /**
     * hasPermission() returns false for a user without the permission in session.
     */
    public function testHasPermissionReturnsFalseForUserWithoutPermission(): void
    {
        $_SESSION['user_role']   = 'branch_admin';
        $_SESSION['permissions'] = ['hr.view', 'hr.edit'];

        $this->assertFalse(\PermissionHelper::hasPermission('roles.delete'));
        $this->assertFalse(\PermissionHelper::hasPermission('hr.delete'));
    }

    /**
     * hasPermission() returns true for superadmin regardless of permission.
     */
    public function testHasPermissionReturnsTrueForSuperadmin(): void
    {
        $_SESSION['user_role'] = 'superadmin';

        $this->assertTrue(\PermissionHelper::hasPermission('roles.delete'));
        $this->assertTrue(\PermissionHelper::hasPermission('any.permission'));
    }

    /**
     * hasPermission() returns true for comadmin regardless of permission.
     */
    public function testHasPermissionReturnsTrueForComadmin(): void
    {
        $_SESSION['user_role'] = 'comadmin';

        $this->assertTrue(\PermissionHelper::hasPermission('roles.delete'));
        $this->assertTrue(\PermissionHelper::hasPermission('hr.delete'));
    }

    // ── Role validation ───────────────────────────────────────────

    /**
     * createRole() throws InvalidArgumentException when name is empty.
     */
    public function testCreateRoleThrowsOnEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/name cannot be empty/i');

        $this->service->createRole('', 'Some Display Name');
    }

    /**
     * createRole() throws InvalidArgumentException when display_name is empty.
     */
    public function testCreateRoleThrowsOnEmptyDisplayName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/display name cannot be empty/i');

        $this->service->createRole('some_role', '');
    }

    /**
     * createRole() throws RuntimeException when role name already exists.
     */
    public function testCreateRoleThrowsOnDuplicateName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already exists/i');

        // DB returns an existing role with the same name
        $this->db->method('fetchOne')
            ->willReturn(['id' => 5, 'name' => 'manager']);

        $this->service->createRole('manager', 'Manager');
    }

    /**
     * createRole() succeeds and returns a new ID when name is unique.
     */
    public function testCreateRoleSucceedsWithValidData(): void
    {
        // No existing role with this name
        $this->db->method('fetchOne')->willReturn(null);
        $this->db->method('insert')->willReturn(42);

        $id = $this->service->createRole('new_role', 'New Role', 'A description');

        $this->assertSame(42, $id);
    }

    /**
     * updateRole() throws InvalidArgumentException when name is empty.
     */
    public function testUpdateRoleThrowsOnEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/name cannot be empty/i');

        $this->service->updateRole(1, '', 'Display Name');
    }

    /**
     * updateRole() throws InvalidArgumentException when display_name is empty.
     */
    public function testUpdateRoleThrowsOnEmptyDisplayName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/display name cannot be empty/i');

        $this->service->updateRole(1, 'some_role', '');
    }

    /**
     * updateRole() throws RuntimeException when name is taken by another role.
     */
    public function testUpdateRoleThrowsOnDuplicateName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already taken/i');

        // Another role has this name
        $this->db->method('fetchOne')
            ->willReturn(['id' => 99, 'name' => 'manager']);

        $this->service->updateRole(1, 'manager', 'Manager');
    }

    // ── Superadmin/comadmin protection ────────────────────────────

    /**
     * deleteRole() throws RuntimeException when trying to delete 'superadmin'.
     */
    public function testDeleteRoleThrowsForSuperadmin(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be deleted/i');

        $this->db->method('fetchOne')
            ->willReturn(['id' => 1, 'name' => 'superadmin', 'display_name' => 'Super Admin']);

        $this->service->deleteRole(1);
    }

    /**
     * deleteRole() throws RuntimeException when trying to delete 'comadmin'.
     */
    public function testDeleteRoleThrowsForComadmin(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be deleted/i');

        $this->db->method('fetchOne')
            ->willReturn(['id' => 2, 'name' => 'comadmin', 'display_name' => 'Company Admin']);

        $this->service->deleteRole(2);
    }

    /**
     * deleteRole() throws RuntimeException when users are assigned to the role.
     */
    public function testDeleteRoleThrowsWhenUsersAssigned(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/user\(s\) are assigned/i');

        $callCount = 0;
        $this->db->method('fetchOne')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    // Role lookup
                    return ['id' => 5, 'name' => 'branch_manager', 'display_name' => 'Branch Manager'];
                }
                // User count check
                return ['c' => 3];
            });

        $this->service->deleteRole(5);
    }

    /**
     * deleteRole() succeeds for a non-protected role with no assigned users.
     */
    public function testDeleteRoleSucceedsForUnprotectedRoleWithNoUsers(): void
    {
        $callCount = 0;
        $this->db->method('fetchOne')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    return ['id' => 5, 'name' => 'branch_manager', 'display_name' => 'Branch Manager'];
                }
                return ['c' => 0];
            });

        $this->db->expects($this->exactly(2))
            ->method('delete');

        $this->service->deleteRole(5);
        $this->addToAssertionCount(1); // no exception thrown
    }

    // ── Role assignment logging ───────────────────────────────────

    /**
     * assignUserRole() calls logRoleChange() which inserts into role_change_logs.
     */
    public function testAssignUserRoleLogsToRoleChangeLogs(): void
    {
        $insertedTables = [];

        $callCount = 0;
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql) use (&$callCount) {
                $callCount++;
                if (str_contains($sql, 'FROM users') && $callCount === 1) {
                    return ['id' => 10, 'full_name' => 'John Doe', 'role_id' => 2];
                }
                if (str_contains($sql, 'FROM roles') && str_contains($sql, 'id = ?')) {
                    return ['id' => 3, 'name' => 'branch_manager', 'display_name' => 'Branch Manager'];
                }
                // Old role lookup
                return ['name' => 'employee', 'display_name' => 'Employee'];
            });

        $this->db->method('update')->willReturn(1);

        $this->db->method('insert')
            ->willReturnCallback(function (string $table) use (&$insertedTables) {
                $insertedTables[] = $table;
                return 1;
            });

        $this->service->assignUserRole(10, 3, 1);

        $this->assertContains('role_change_logs', $insertedTables,
            'assignUserRole() must insert a record into role_change_logs');
    }

    /**
     * logRoleChange() inserts a record into role_change_logs with correct fields.
     */
    public function testLogRoleChangeInsertsRecord(): void
    {
        $insertedData = null;

        $this->db->expects($this->once())
            ->method('insert')
            ->with(
                'role_change_logs',
                $this->callback(function (array $data) use (&$insertedData) {
                    $insertedData = $data;
                    return true;
                })
            )
            ->willReturn(1);

        $this->service->logRoleChange(10, 2, 3, 'Employee', 'Branch Manager', 1, 'Promotion');

        $this->assertSame(10, $insertedData['user_id']);
        $this->assertSame(2,  $insertedData['old_role_id']);
        $this->assertSame(3,  $insertedData['new_role_id']);
        $this->assertSame('Employee',       $insertedData['old_role_name']);
        $this->assertSame('Branch Manager', $insertedData['new_role_name']);
        $this->assertSame(1,  $insertedData['changed_by']);
        $this->assertSame('Promotion', $insertedData['reason']);
    }

    // ── Permission saving ─────────────────────────────────────────

    /**
     * saveRolePermissions() deletes old permissions and inserts new ones.
     */
    public function testSaveRolePermissionsDeletesOldAndInsertsNew(): void
    {
        $deleteCalled   = false;
        $insertedTables = [];

        $this->db->expects($this->once())
            ->method('delete')
            ->with('role_permissions', 'role_id = ?', [5])
            ->willReturnCallback(function () use (&$deleteCalled) {
                $deleteCalled = true;
                return 1;
            });

        // fetchOne returns a permission ID for each name
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) {
                if (str_contains($sql, 'FROM permissions')) {
                    return ['id' => rand(1, 100)];
                }
                return null;
            });

        $this->db->method('insert')
            ->willReturnCallback(function (string $table) use (&$insertedTables) {
                $insertedTables[] = $table;
                return 1;
            });

        $this->service->saveRolePermissions(5, ['hr.view', 'hr.edit', 'support.view']);

        $this->assertTrue($deleteCalled, 'Old permissions must be deleted before saving new ones');
        $this->assertCount(3, array_filter($insertedTables, fn($t) => $t === 'role_permissions'),
            'Three role_permissions rows should be inserted');
    }

    /**
     * saveRolePermissions() with empty array only deletes, does not insert.
     */
    public function testSaveRolePermissionsWithEmptyArrayOnlyDeletes(): void
    {
        $this->db->expects($this->once())
            ->method('delete')
            ->with('role_permissions', 'role_id = ?', [5]);

        $this->db->expects($this->never())
            ->method('insert');

        $this->service->saveRolePermissions(5, []);
    }
}
