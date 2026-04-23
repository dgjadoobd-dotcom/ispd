<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for HrService.
 *
 * Validates Requirements:
 *   - 2.5: Attendance status must be present/absent/late/half_day/leave
 *   - 2.6: gross = basic_salary + allowances − deductions
 *   - 2.9: Prevent duplicate salary slip generation
 *   - 2.10: Leave balance tracking per type (annual/sick/casual)
 *
 * Uses a testable subclass to inject a mock Database, avoiding real DB connections.
 */
class HrServiceTest extends TestCase
{
    /** @var MockObject&\Database */
    private MockObject $db;

    /** @var \HrService */
    private \HrService $service;

    protected function setUp(): void
    {
        // Create a mock Database object (not a real connection)
        $this->db = $this->createMock(\Database::class);

        // Use the testable subclass that accepts an injected DB
        $this->service = new \TestableHrService($this->db);
    }

    // ── Salary Calculation (Req 2.6) ──────────────────────────────

    /**
     * Req 2.6: gross = basic_salary + allowances − deductions
     */
    public function testSalaryCalculationGrossFormula(): void
    {
        $employeeId = 1;
        $month      = 6;
        $year       = 2024;

        // Employee with basic_salary=5000, allowances=1000
        $employee = $this->makeEmployee($employeeId, 5000.0, 1000.0);

        // getEmployee() calls fetchOne with a JOIN query
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) use ($employee) {
                if (str_contains($sql, 'FROM employees')) {
                    return $employee;
                }
                return null;
            });

        // No absences this month
        $this->db->method('fetchAll')
            ->willReturn([]);

        $result = $this->service->calculateSalary($employeeId, $month, $year);

        $this->assertSame(5000.0, $result['basic_salary']);
        $this->assertSame(1000.0, $result['allowances']);
        $this->assertSame(0.0,    $result['deductions']);
        // gross = 5000 + 1000 - 0 = 6000
        $this->assertSame(6000.0, $result['gross_pay']);
    }

    /**
     * Req 2.6: Full month present → no deductions, gross = basic + allowances
     */
    public function testSalaryCalculationNoAbsences(): void
    {
        $employeeId = 2;
        $month      = 1;
        $year       = 2024;

        $employee = $this->makeEmployee($employeeId, 3000.0, 500.0);

        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql) use ($employee) {
                if (str_contains($sql, 'FROM employees')) {
                    return $employee;
                }
                return null;
            });

        // All present — fetchAll returns rows with status=present only
        $this->db->method('fetchAll')
            ->willReturn([
                ['status' => 'present', 'cnt' => 31],
            ]);

        $result = $this->service->calculateSalary($employeeId, $month, $year);

        $this->assertSame(0.0, $result['deductions'], 'No absences means zero deductions');
        $this->assertSame(3500.0, $result['gross_pay'], 'gross = 3000 + 500 - 0');
    }

    /**
     * Req 2.6: Absent days reduce salary proportionally.
     * deduction = (basic / total_days) * absent_days
     */
    public function testSalaryCalculationWithAbsences(): void
    {
        $employeeId = 3;
        $month      = 6;   // June: 30 days
        $year       = 2024;

        $employee = $this->makeEmployee($employeeId, 3000.0, 0.0);

        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql) use ($employee) {
                if (str_contains($sql, 'FROM employees')) {
                    return $employee;
                }
                return null;
            });

        // 3 absent days
        $this->db->method('fetchAll')
            ->willReturn([
                ['status' => 'absent', 'cnt' => 3],
            ]);

        $result = $this->service->calculateSalary($employeeId, $month, $year);

        // deduction = (3000 / 30) * 3 = 300
        $expectedDeduction = round((3000.0 / 30) * 3, 2);
        $expectedGross     = 3000.0 + 0.0 - $expectedDeduction;

        $this->assertSame($expectedDeduction, $result['deductions']);
        $this->assertSame($expectedGross,     $result['gross_pay']);
    }

    // ── Attendance Validation (Req 2.5) ───────────────────────────

    /**
     * Req 2.5: Valid statuses (present/absent/late/half_day/leave) must not throw.
     *
     * @dataProvider validAttendanceStatusProvider
     */
    public function testAttendanceValidStatus(string $status): void
    {
        $employeeId = 10;
        $employee   = $this->makeEmployee($employeeId, 1000.0, 0.0);

        // findById('employees', ...) → fetchOne
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) use ($employee) {
                if (str_contains($sql, 'FROM `employees`')) {
                    return $employee;
                }
                // No existing attendance record
                if (str_contains($sql, 'FROM attendance')) {
                    return null;
                }
                return null;
            });

        $this->db->method('insert')->willReturn(1);

        // Should not throw
        $this->service->recordAttendance($employeeId, '2024-06-01', $status);
        $this->addToAssertionCount(1); // explicit assertion that no exception was thrown
    }

    public static function validAttendanceStatusProvider(): array
    {
        return [
            'present'  => ['present'],
            'absent'   => ['absent'],
            'late'     => ['late'],
            'half_day' => ['half_day'],
            'leave'    => ['leave'],
        ];
    }

    /**
     * Req 2.5: Invalid status must throw InvalidArgumentException.
     */
    public function testAttendanceInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/invalid attendance status/i');

        // DB should not be called at all for invalid status
        $this->db->expects($this->never())->method('fetchOne');

        $this->service->recordAttendance(1, '2024-06-01', 'holiday');
    }

    // ── Duplicate Salary Slip Prevention (Req 2.9) ───────────────

    /**
     * Req 2.9: Generating a salary slip for the same employee/month/year twice
     * must throw RuntimeException on the second call.
     */
    public function testDuplicateSalarySlipPrevented(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already been generated/i');

        $employeeId = 5;
        $month      = 3;
        $year       = 2024;
        $employee   = $this->makeEmployee($employeeId, 2000.0, 200.0);

        // getEmployee() returns the employee; checkDuplicateSalarySlip returns existing slip
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) use ($employee) {
                if (str_contains($sql, 'FROM employees')) {
                    return $employee;
                }
                // salary_slips check → slip already exists
                if (str_contains($sql, 'FROM salary_slips')) {
                    return ['id' => 99];
                }
                return null;
            });

        $this->db->method('fetchAll')->willReturn([]);

        $this->service->generateSalarySlip($employeeId, $month, $year);
    }

    // ── Leave Balance Defaults (Req 2.10) ─────────────────────────

    /**
     * Req 2.10: When no leave balance records exist, getLeaveBalance() returns
     * 3 default entries (annual, sick, casual).
     */
    public function testLeaveBalanceDefaults(): void
    {
        $employeeId = 7;

        // No records in DB
        $this->db->method('fetchAll')->willReturn([]);

        $balances = $this->service->getLeaveBalance($employeeId);

        $this->assertCount(3, $balances, 'Should return 3 default leave types');

        $types = array_column($balances, 'leave_type');
        $this->assertContains('annual', $types);
        $this->assertContains('sick',   $types);
        $this->assertContains('casual', $types);

        foreach ($balances as $balance) {
            $this->assertSame(0, $balance['total_days'],     'Default total_days should be 0');
            $this->assertSame(0, $balance['used_days'],      'Default used_days should be 0');
            $this->assertSame(0, $balance['remaining_days'], 'Default remaining_days should be 0');
        }
    }

    /**
     * Req 2.10: Invalid leave type must throw InvalidArgumentException.
     */
    public function testLeaveBalanceInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/invalid leave type/i');

        $this->service->updateLeaveBalance(1, 'vacation', 10);
    }

    /**
     * Req 2.10: All valid leave types (annual/sick/casual) must be accepted.
     *
     * @dataProvider validLeaveTypeProvider
     */
    public function testLeaveBalanceValidTypes(string $leaveType): void
    {
        $employeeId = 8;

        // No existing record → insert path
        $this->db->method('fetchOne')->willReturn(null);
        $this->db->method('insert')->willReturn(1);

        // Should not throw
        $this->service->updateLeaveBalance($employeeId, $leaveType, 15, 3);
        $this->addToAssertionCount(1);
    }

    public static function validLeaveTypeProvider(): array
    {
        return [
            'annual' => ['annual'],
            'sick'   => ['sick'],
            'casual' => ['casual'],
        ];
    }

    // ── Employee-User Relationship (Req 2.4) ─────────────────────

    /**
     * Req 2.4: createEmployee() auto-creates a user account when email is provided
     * and no existing user with that email exists.
     */
    public function testCreateEmployeeAutoCreatesUserWhenEmailProvided(): void
    {
        $insertCallCount = 0;
        $insertedTables  = [];

        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) {
                // No existing user with this email
                if (str_contains($sql, 'FROM users')) {
                    return null;
                }
                // No existing employee code
                if (str_contains($sql, 'employee_code')) {
                    return null;
                }
                return null;
            });

        $this->db->method('insert')
            ->willReturnCallback(function (string $table, array $data) use (&$insertCallCount, &$insertedTables) {
                $insertCallCount++;
                $insertedTables[] = $table;
                return $insertCallCount; // return incrementing IDs
            });

        $this->db->method('fetchAll')->willReturn([]);

        // Roles lookup
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'FROM roles')) {
                    return ['id' => 4];
                }
                if (str_contains($sql, 'FROM users')) {
                    return null;
                }
                if (str_contains($sql, 'employee_code')) {
                    return null;
                }
                return null;
            });

        $this->db->method('update')->willReturn(1);

        $data = [
            'branch_id'    => 1,
            'full_name'    => 'Jane Doe',
            'email'        => 'jane.doe@example.com',
            'basic_salary' => 2500.0,
            'allowances'   => 300.0,
        ];

        $employeeId = $this->service->createEmployee($data);

        $this->assertGreaterThan(0, $employeeId, 'createEmployee should return a positive ID');
        $this->assertContains('employees', $insertedTables, 'Should insert into employees table');
        $this->assertContains('users', $insertedTables, 'Should auto-create a user account');
    }

    /**
     * Req 2.4: createEmployee() links to existing user when email already exists.
     */
    public function testCreateEmployeeLinksExistingUserByEmail(): void
    {
        $insertedTables = [];

        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'FROM users')) {
                    return ['id' => 42]; // existing user
                }
                if (str_contains($sql, 'employee_code')) {
                    return null;
                }
                return null;
            });

        $this->db->method('insert')
            ->willReturnCallback(function (string $table) use (&$insertedTables) {
                $insertedTables[] = $table;
                return 1;
            });

        $this->db->method('update')->willReturn(1);
        $this->db->method('fetchAll')->willReturn([]);

        $data = [
            'branch_id'    => 1,
            'full_name'    => 'John Smith',
            'email'        => 'john.smith@example.com',
            'basic_salary' => 2000.0,
            'allowances'   => 0.0,
        ];

        $employeeId = $this->service->createEmployee($data);

        $this->assertGreaterThan(0, $employeeId);
        $this->assertNotContains('users', $insertedTables, 'Should NOT insert a new user when one already exists');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Build a minimal employee array matching what getEmployee() returns.
     */
    private function makeEmployee(int $id, float $basicSalary, float $allowances): array
    {
        return [
            'id'            => $id,
            'branch_id'     => 1,
            'full_name'     => 'Test Employee',
            'employee_code' => 'EMP-000' . $id,
            'basic_salary'  => $basicSalary,
            'allowances'    => $allowances,
            'status'        => 'active',
        ];
    }
}
