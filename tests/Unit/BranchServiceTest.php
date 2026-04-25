<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for BranchService.
 *
 * Validates Requirements:
 *   - 1.3: branch_admin restricted to their branch_id (getBranchFilter)
 *   - 1.6: deactivated branch has is_active=0
 *   - 1.8: duplicate branch code rejected with RuntimeException
 *   - 1.5: generateSummaryReport returns array with required keys
 */
class BranchServiceTest extends TestCase
{
    /** @var MockObject&\Database */
    private MockObject $db;

    /** @var \BranchService */
    private \BranchService $service;

    protected function setUp(): void
    {
        // Create a mock Database object (not a real connection)
        $this->db = $this->createMock(\Database::class);

        // Use the testable subclass that accepts an injected DB
        $this->service = new \TestableBranchService($this->db);

        // Reset session state before each test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ── Req 1.8: Duplicate branch code ────────────────────────────

    /**
     * Req 1.8: Creating two branches with the same code throws RuntimeException.
     */
    public function testBranchCodeUniqueness(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already exists/i');

        // Simulate: code 'DHK01' already exists in DB
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) {
                if (str_contains($sql, 'FROM branches') && str_contains($sql, 'code = ?')) {
                    return ['id' => 1]; // existing branch with this code
                }
                return null;
            });

        $this->service->createBranch([
            'name'    => 'Dhaka Branch',
            'code'    => 'DHK01',
            'address' => '123 Main Road',
            'phone'   => '01700000000',
            'email'   => 'dhaka@example.com',
            'manager' => 'Karim',
        ]);
    }

    /**
     * Req 1.8: Creating a branch with a unique code succeeds and returns an ID.
     */
    public function testBranchCodeUniqueSucceeds(): void
    {
        // No existing branch with this code
        $this->db->method('fetchOne')
            ->willReturn(null);

        $this->db->method('insert')
            ->willReturn(5);

        $id = $this->service->createBranch([
            'name'    => 'Chittagong Branch',
            'code'    => 'CTG01',
            'address' => '456 Port Road',
            'phone'   => '01800000000',
            'email'   => 'ctg@example.com',
            'manager' => 'Rahim',
        ]);

        $this->assertSame(5, $id, 'createBranch should return the new branch ID');
    }

    // ── Req 1.3: Data isolation for branch_admin ──────────────────

    /**
     * Req 1.3: getBranchFilter() returns the session branch_id for branch_admin role.
     */
    public function testDataIsolationForBranchAdmin(): void
    {
        // Set up session as branch_admin with branch_id = 3
        $_SESSION['user_role'] = 'branch_admin';
        $_SESSION['branch_id'] = 3;

        $branchFilter = \PermissionHelper::getBranchFilter();

        $this->assertSame(3, $branchFilter, 'branch_admin should get their branch_id as filter');
    }

    /**
     * Req 1.2: getBranchFilter() returns null for comadmin (no filter — sees all).
     */
    public function testDataIsolationForComAdmin(): void
    {
        $_SESSION['user_role'] = 'comadmin';
        $_SESSION['branch_id'] = 1;

        $branchFilter = \PermissionHelper::getBranchFilter();

        $this->assertNull($branchFilter, 'comadmin should get null filter (sees all branches)');
    }

    // ── Req 1.6: Deactivation ─────────────────────────────────────

    /**
     * Req 1.6: deactivateBranch sets is_active=0.
     */
    public function testDeactivationPreventsNewCustomers(): void
    {
        $branchId = 2;
        $capturedData = null;

        $this->db->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (string $table, array $data, string $where, array $params) use (&$capturedData, $branchId) {
                $capturedData = $data;
                $this->assertSame('branches', $table);
                $this->assertSame([$branchId], $params);
                return 1;
            });

        $this->service->deactivateBranch($branchId);

        $this->assertNotNull($capturedData, 'update() should have been called');
        $this->assertArrayHasKey('is_active', $capturedData, 'update data should contain is_active');
        $this->assertSame(0, $capturedData['is_active'], 'is_active should be set to 0');
    }

    /**
     * Req 1.6: activateBranch sets is_active=1.
     */
    public function testActivationSetsIsActiveToOne(): void
    {
        $branchId = 2;
        $capturedData = null;

        $this->db->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (string $table, array $data, string $where, array $params) use (&$capturedData) {
                $capturedData = $data;
                return 1;
            });

        $this->service->activateBranch($branchId);

        $this->assertSame(1, $capturedData['is_active'], 'is_active should be set to 1');
    }

    // ── Req 1.5: generateSummaryReport ────────────────────────────

    /**
     * Req 1.5: generateSummaryReport returns array with required keys.
     */
    public function testGenerateSummaryReportReturnsArray(): void
    {
        $_SESSION['user_id'] = 1;

        // Mock all DB calls to return safe defaults
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) {
                if (str_contains($sql, 'FROM customers')) {
                    return ['cnt' => 42];
                }
                if (str_contains($sql, 'FROM payments')) {
                    return ['total' => 15000.00];
                }
                if (str_contains($sql, 'FROM invoices')) {
                    return ['total' => 3500.00];
                }
                if (str_contains($sql, 'FROM support_tickets')) {
                    return ['cnt' => 7];
                }
                return null;
            });

        $this->db->method('insert')->willReturn(1);

        $report = $this->service->generateSummaryReport(1, '2024-01-01', '2024-01-31');

        $this->assertIsArray($report, 'generateSummaryReport should return an array');

        // Verify required keys are present
        $requiredKeys = ['branch_id', 'period_start', 'period_end', 'customer_count', 'monthly_revenue', 'outstanding_dues', 'active_tickets'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $report, "Report should contain key: {$key}");
        }

        $this->assertSame(1, $report['branch_id']);
        $this->assertSame('2024-01-01', $report['period_start']);
        $this->assertSame('2024-01-31', $report['period_end']);
        $this->assertSame(42, $report['customer_count']);
        $this->assertSame(15000.00, $report['monthly_revenue']);
        $this->assertSame(3500.00, $report['outstanding_dues']);
        $this->assertSame(7, $report['active_tickets']);
    }

    /**
     * Req 1.5: generateSummaryReport handles missing data gracefully (returns zeros).
     */
    public function testGenerateSummaryReportHandlesMissingData(): void
    {
        $_SESSION['user_id'] = 1;

        // All DB calls return null (no data)
        $this->db->method('fetchOne')->willReturn(null);
        $this->db->method('insert')->willReturn(1);

        $report = $this->service->generateSummaryReport(99, '2024-06-01', '2024-06-30');

        $this->assertIsArray($report);
        $this->assertSame(0, $report['customer_count'], 'Missing data should default to 0');
        $this->assertSame(0.00, $report['monthly_revenue'], 'Missing data should default to 0.00');
        $this->assertSame(0.00, $report['outstanding_dues'], 'Missing data should default to 0.00');
        $this->assertSame(0, $report['active_tickets'], 'Missing data should default to 0');
    }

    // ── Credential assignment ─────────────────────────────────────

    /**
     * Req 1.4: assignCredential inserts when no credential exists.
     */
    public function testAssignCredentialInsertsWhenNoneExists(): void
    {
        $_SESSION['user_id'] = 1;

        $insertedTable = null;

        $this->db->method('fetchOne')->willReturn(null); // no existing credential

        $this->db->expects($this->once())
            ->method('insert')
            ->willReturnCallback(function (string $table, array $data) use (&$insertedTable) {
                $insertedTable = $table;
                return 1;
            });

        $this->service->assignCredential(1, 5);

        $this->assertSame('branch_credentials', $insertedTable);
    }

    /**
     * Req 1.4: assignCredential updates when credential already exists.
     */
    public function testAssignCredentialUpdatesWhenExists(): void
    {
        $_SESSION['user_id'] = 1;

        $this->db->method('fetchOne')
            ->willReturn(['id' => 10]); // existing credential

        $this->db->expects($this->never())->method('insert');

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(1);

        $this->service->assignCredential(1, 7);
    }
}
