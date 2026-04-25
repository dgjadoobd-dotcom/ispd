<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for permission enforcement.
 *
 * Tests PermissionHelper::hasPermission() across different role/permission
 * combinations, and PortalSessionMiddleware::isAuthenticated() for session
 * timeout and validity checks.
 *
 * No database connection is required — all checks operate on $_SESSION.
 */
class PermissionEnforcementTest extends TestCase
{
    protected function setUp(): void
    {
        // Start with a clean session for every test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ── PermissionHelper tests ────────────────────────────────────────────────

    /**
     * A superadmin with the wildcard sentinel ['*'] bypasses all permission
     * checks and is granted every permission.
     */
    public function testSuperadminHasAllPermissions(): void
    {
        $_SESSION['user_role']   = 'superadmin';
        $_SESSION['permissions'] = ['*'];

        $this->assertTrue(
            \PermissionHelper::hasPermission('hr.view'),
            'Superadmin should have hr.view'
        );
        $this->assertTrue(
            \PermissionHelper::hasPermission('any.permission'),
            'Superadmin should have any arbitrary permission'
        );
    }

    /**
     * A user whose session contains specific permissions can access exactly
     * those permissions.
     */
    public function testUserWithPermissionCanAccess(): void
    {
        $_SESSION['user_role']   = 'branch_admin';
        $_SESSION['permissions'] = ['hr.view', 'billing.view'];

        $this->assertTrue(
            \PermissionHelper::hasPermission('hr.view'),
            'User should have hr.view'
        );
        $this->assertTrue(
            \PermissionHelper::hasPermission('billing.view'),
            'User should have billing.view'
        );
    }

    /**
     * A user whose session does not contain a permission is denied access.
     */
    public function testUserWithoutPermissionCannotAccess(): void
    {
        $_SESSION['user_role']   = 'branch_admin';
        $_SESSION['permissions'] = ['billing.view'];

        $this->assertFalse(
            \PermissionHelper::hasPermission('hr.view'),
            'User should not have hr.view'
        );
        $this->assertFalse(
            \PermissionHelper::hasPermission('inventory.edit'),
            'User should not have inventory.edit'
        );
    }

    /**
     * A user with an empty permissions array is denied all access.
     */
    public function testEmptyPermissionsBlocksAccess(): void
    {
        $_SESSION['user_role']   = 'branch_admin';
        $_SESSION['permissions'] = [];

        $this->assertFalse(
            \PermissionHelper::hasPermission('hr.view'),
            'User with no permissions should be denied hr.view'
        );
    }

    // ── PortalSessionMiddleware tests ─────────────────────────────────────────

    /**
     * A session whose last-activity timestamp is more than 30 minutes ago
     * is considered expired and isAuthenticated() returns false.
     */
    public function testPortalSessionMiddlewareEnforcesTimeout(): void
    {
        $middleware = new \PortalSessionMiddleware('reseller');

        $_SESSION['reseller_id']            = 1;
        $_SESSION['reseller_last_activity'] = time() - 7200; // 2 hours ago

        $this->assertFalse(
            $middleware->isAuthenticated(),
            'Session inactive for 2 hours should be expired'
        );
    }

    /**
     * A session whose last-activity timestamp is recent is considered valid
     * and isAuthenticated() returns true.
     */
    public function testPortalSessionMiddlewareValidSession(): void
    {
        $middleware = new \PortalSessionMiddleware('reseller');

        $_SESSION['reseller_id']            = 1;
        $_SESSION['reseller_last_activity'] = time();

        $this->assertTrue(
            $middleware->isAuthenticated(),
            'Session with current activity timestamp should be valid'
        );
    }

    /**
     * When the portal session key is absent, isAuthenticated() returns false.
     */
    public function testPortalSessionMiddlewareNoSession(): void
    {
        $middleware = new \PortalSessionMiddleware('reseller');

        // Ensure the session key is not set
        unset($_SESSION['reseller_id']);

        $this->assertFalse(
            $middleware->isAuthenticated(),
            'Missing session key should result in unauthenticated state'
        );
    }
}
