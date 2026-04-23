<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PermissionHelper.
 *
 * Tests session-based role helpers, branch filtering, and the static
 * permission map — all without a database connection.
 */
class PermissionHelperTest extends TestCase
{
    /**
     * Reset $_SESSION before every test so state does not leak between cases.
     */
    protected function setUp(): void
    {
        // PHPUnit runs in CLI where sessions are not started automatically.
        // We manipulate $_SESSION directly as an array.
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ── isComAdmin() ──────────────────────────────────────────────

    /**
     * @test
     */
    public function testIsComAdminReturnsTrueForComadminRole(): void
    {
        $_SESSION['user_role'] = 'comadmin';

        $this->assertTrue(\PermissionHelper::isComAdmin());
    }

    /**
     * @test
     */
    public function testIsComAdminReturnsTrueForSuperadminRole(): void
    {
        $_SESSION['user_role'] = 'superadmin';

        $this->assertTrue(\PermissionHelper::isComAdmin());
    }

    /**
     * @test
     */
    public function testIsComAdminReturnsFalseForBranchAdmin(): void
    {
        $_SESSION['user_role'] = 'branch_admin';

        $this->assertFalse(\PermissionHelper::isComAdmin());
    }

    /**
     * @test
     */
    public function testIsComAdminReturnsFalseForEmployee(): void
    {
        $_SESSION['user_role'] = 'employee';

        $this->assertFalse(\PermissionHelper::isComAdmin());
    }

    /**
     * @test
     */
    public function testIsComAdminReturnsFalseWhenNoRoleSet(): void
    {
        // No user_role in session
        $this->assertFalse(\PermissionHelper::isComAdmin());
    }

    // ── isBranchAdmin() ───────────────────────────────────────────

    /**
     * @test
     */
    public function testIsBranchAdminReturnsTrueForBranchAdminRole(): void
    {
        $_SESSION['user_role'] = 'branch_admin';

        $this->assertTrue(\PermissionHelper::isBranchAdmin());
    }

    /**
     * @test
     */
    public function testIsBranchAdminReturnsFalseForComadmin(): void
    {
        $_SESSION['user_role'] = 'comadmin';

        $this->assertFalse(\PermissionHelper::isBranchAdmin());
    }

    /**
     * @test
     */
    public function testIsBranchAdminReturnsFalseForSuperadmin(): void
    {
        $_SESSION['user_role'] = 'superadmin';

        $this->assertFalse(\PermissionHelper::isBranchAdmin());
    }

    /**
     * @test
     */
    public function testIsBranchAdminReturnsFalseWhenNoRoleSet(): void
    {
        $this->assertFalse(\PermissionHelper::isBranchAdmin());
    }

    // ── getBranchFilter() ─────────────────────────────────────────

    /**
     * @test
     */
    public function testGetBranchFilterReturnsNullForComadmin(): void
    {
        $_SESSION['user_role'] = 'comadmin';
        $_SESSION['branch_id'] = 5;

        $this->assertNull(\PermissionHelper::getBranchFilter());
    }

    /**
     * @test
     */
    public function testGetBranchFilterReturnsNullForSuperadmin(): void
    {
        $_SESSION['user_role'] = 'superadmin';
        $_SESSION['branch_id'] = 3;

        $this->assertNull(\PermissionHelper::getBranchFilter());
    }

    /**
     * @test
     */
    public function testGetBranchFilterReturnsBranchIdForBranchAdmin(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        $_SESSION['branch_id'] = 7;

        $result = \PermissionHelper::getBranchFilter();

        $this->assertSame(7, $result);
    }

    /**
     * @test
     */
    public function testGetBranchFilterReturnsBranchIdAsInt(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        $_SESSION['branch_id'] = '12'; // stored as string

        $result = \PermissionHelper::getBranchFilter();

        $this->assertIsInt($result);
        $this->assertSame(12, $result);
    }

    /**
     * @test
     */
    public function testGetBranchFilterReturnsNullWhenBranchIdMissingForBranchAdmin(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        // branch_id not set

        $this->assertNull(\PermissionHelper::getBranchFilter());
    }

    // ── applyBranchFilter() ───────────────────────────────────────

    /**
     * @test
     */
    public function testApplyBranchFilterDoesNotModifySqlForComadmin(): void
    {
        $_SESSION['user_role'] = 'comadmin';

        $sql    = 'SELECT * FROM employees';
        $params = [];

        $result = \PermissionHelper::applyBranchFilter($sql, $params);

        $this->assertSame('SELECT * FROM employees', $result);
        $this->assertEmpty($params);
    }

    /**
     * @test
     */
    public function testApplyBranchFilterAddsWhereClauseForBranchAdmin(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        $_SESSION['branch_id'] = 3;

        $sql    = 'SELECT * FROM employees';
        $params = [];

        $result = \PermissionHelper::applyBranchFilter($sql, $params);

        $this->assertStringContainsString('WHERE', $result);
        $this->assertStringContainsString('branch_id', $result);
        $this->assertStringContainsString('?', $result);
        $this->assertSame([3], $params);
    }

    /**
     * @test
     */
    public function testApplyBranchFilterAddsAndWhenWhereAlreadyExists(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        $_SESSION['branch_id'] = 4;

        $sql    = 'SELECT * FROM employees WHERE status = ?';
        $params = ['active'];

        $result = \PermissionHelper::applyBranchFilter($sql, $params);

        $this->assertStringContainsString(' AND ', $result);
        $this->assertStringNotContainsString('WHERE WHERE', $result);
        // Original param preserved, branch_id appended
        $this->assertSame(['active', 4], $params);
    }

    /**
     * @test
     */
    public function testApplyBranchFilterUsesTableAlias(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        $_SESSION['branch_id'] = 2;

        $sql    = 'SELECT * FROM employees e';
        $params = [];

        $result = \PermissionHelper::applyBranchFilter($sql, $params, 'e');

        $this->assertStringContainsString('`e`.`branch_id`', $result);
    }

    /**
     * @test
     */
    public function testApplyBranchFilterWithoutAliasUsesBareBranchId(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        $_SESSION['branch_id'] = 5;

        $sql    = 'SELECT * FROM employees';
        $params = [];

        $result = \PermissionHelper::applyBranchFilter($sql, $params);

        $this->assertStringContainsString('`branch_id`', $result);
    }

    // ── getAllPermissionNames() ────────────────────────────────────

    /**
     * @test
     */
    public function testGetAllPermissionNamesReturnsNonEmptyArray(): void
    {
        $names = \PermissionHelper::getAllPermissionNames();

        $this->assertIsArray($names);
        $this->assertNotEmpty($names);
    }

    /**
     * @test
     */
    public function testGetAllPermissionNamesContainsExpectedPermissions(): void
    {
        $names = \PermissionHelper::getAllPermissionNames();

        $this->assertContains('hr.view', $names);
        $this->assertContains('hr.edit', $names);
        $this->assertContains('accounts.view', $names);
        $this->assertContains('roles.delete', $names);
    }

    /**
     * @test
     */
    public function testGetAllPermissionNamesAreStrings(): void
    {
        $names = \PermissionHelper::getAllPermissionNames();

        foreach ($names as $name) {
            $this->assertIsString($name);
            // Each permission should follow the module.action format
            $this->assertMatchesRegularExpression('/^\w+\.\w+$/', $name);
        }
    }

    /**
     * @test
     */
    public function testGetAllPermissionNamesCoversAllModules(): void
    {
        $names = \PermissionHelper::getAllPermissionNames();

        // Verify at least one permission exists for each defined module
        foreach (array_keys(\PermissionHelper::MODULE_PERMISSIONS) as $module) {
            $modulePermissions = array_filter($names, fn($n) => str_starts_with($n, $module . '.'));
            $this->assertNotEmpty(
                $modulePermissions,
                "No permissions found for module: {$module}"
            );
        }
    }

    // ── getModulePermissions() ────────────────────────────────────

    /**
     * @test
     */
    public function testGetModulePermissionsReturnsPermissionsForKnownModule(): void
    {
        $permissions = \PermissionHelper::getModulePermissions('hr');

        $this->assertIsArray($permissions);
        $this->assertNotEmpty($permissions);
        $this->assertContains('hr.view', $permissions);
        $this->assertContains('hr.create', $permissions);
        $this->assertContains('hr.edit', $permissions);
        $this->assertContains('hr.delete', $permissions);
    }

    /**
     * @test
     */
    public function testGetModulePermissionsReturnsEmptyArrayForUnknownModule(): void
    {
        $permissions = \PermissionHelper::getModulePermissions('nonexistent_module');

        $this->assertIsArray($permissions);
        $this->assertEmpty($permissions);
    }

    /**
     * @test
     */
    public function testGetModulePermissionsReturnsCorrectPermissionsForRolesModule(): void
    {
        $permissions = \PermissionHelper::getModulePermissions('roles');

        $this->assertContains('roles.view', $permissions);
        $this->assertContains('roles.create', $permissions);
        $this->assertContains('roles.edit', $permissions);
        $this->assertContains('roles.delete', $permissions);
        $this->assertContains('roles.assign', $permissions);
    }

    /**
     * @test
     */
    public function testGetModulePermissionsAllPermissionsBelongToModule(): void
    {
        foreach (array_keys(\PermissionHelper::MODULE_PERMISSIONS) as $module) {
            $permissions = \PermissionHelper::getModulePermissions($module);

            foreach ($permissions as $permission) {
                $this->assertStringStartsWith(
                    $module . '.',
                    $permission,
                    "Permission '{$permission}' does not belong to module '{$module}'"
                );
            }
        }
    }

    // ── hasPermission() — session-based (no DB) ───────────────────

    /**
     * @test
     */
    public function testHasPermissionReturnsTrueForComadmin(): void
    {
        // comadmin bypasses all permission checks
        $_SESSION['user_role'] = 'comadmin';

        $this->assertTrue(\PermissionHelper::hasPermission('hr.delete'));
        $this->assertTrue(\PermissionHelper::hasPermission('roles.delete'));
    }

    /**
     * @test
     */
    public function testHasPermissionReturnsTrueForSuperadmin(): void
    {
        $_SESSION['user_role'] = 'superadmin';

        $this->assertTrue(\PermissionHelper::hasPermission('any.permission'));
    }

    /**
     * @test
     */
    public function testHasPermissionReturnsTrueWhenPermissionInSession(): void
    {
        $_SESSION['user_role']    = 'branch_admin';
        $_SESSION['permissions']  = ['hr.view', 'hr.edit', 'support.view'];

        $this->assertTrue(\PermissionHelper::hasPermission('hr.view'));
        $this->assertTrue(\PermissionHelper::hasPermission('support.view'));
    }

    /**
     * @test
     */
    public function testHasPermissionReturnsFalseWhenPermissionNotInSession(): void
    {
        $_SESSION['user_role']   = 'branch_admin';
        $_SESSION['permissions'] = ['hr.view'];

        $this->assertFalse(\PermissionHelper::hasPermission('hr.delete'));
        $this->assertFalse(\PermissionHelper::hasPermission('roles.delete'));
    }

    /**
     * @test
     */
    public function testHasPermissionReturnsFalseWhenNoPermissionsAndNoUserId(): void
    {
        $_SESSION['user_role'] = 'branch_admin';
        // No permissions array, no user_id — cannot load from DB

        $this->assertFalse(\PermissionHelper::hasPermission('hr.view'));
    }
}
