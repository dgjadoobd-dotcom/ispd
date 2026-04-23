<?php

/**
 * HrService — Business logic for the HR & Payroll module.
 *
 * Covers Requirements 2.5, 2.6, 2.10:
 *   - Employee CRUD with branch isolation
 *   - Attendance tracking (present/absent/late/half_day/leave)
 *   - Salary calculation: gross = basic_salary + allowances − deductions
 *   - Leave balance tracking per employee per type
 *
 * @see database/migrations/2024_01_03_010_hr_module.sql
 */
class HrService extends BaseService
{
    // ── Employees ─────────────────────────────────────────────────

    /**
     * Return all employees, optionally filtered by branch.
     *
     * @param  int|null $branchId  Branch ID to filter by, or null for all branches
     * @return array
     */
    public function getEmployees(?int $branchId = null): array
    {
        $params = [];
        $sql = "SELECT e.*,
                       d.name  AS department_name,
                       dg.title AS designation_title,
                       b.name  AS branch_name
                FROM employees e
                LEFT JOIN departments  d  ON d.id  = e.department_id
                LEFT JOIN designations dg ON dg.id = e.designation_id
                LEFT JOIN branches     b  ON b.id  = e.branch_id";

        if ($branchId !== null) {
            $sql .= " WHERE e.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY e.full_name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getEmployees failed', $e);
            return [];
        }
    }

    /**
     * Return a single employee by ID with joined department/designation/branch.
     *
     * @param  int $id
     * @return array|null
     */
    public function getEmployee(int $id): ?array
    {
        try {
            return $this->db->fetchOne(
                "SELECT e.*,
                        d.name   AS department_name,
                        dg.title AS designation_title,
                        b.name   AS branch_name
                 FROM employees e
                 LEFT JOIN departments  d  ON d.id  = e.department_id
                 LEFT JOIN designations dg ON dg.id = e.designation_id
                 LEFT JOIN branches     b  ON b.id  = e.branch_id
                 WHERE e.id = ? LIMIT 1",
                [$id]
            );
        } catch (\Throwable $e) {
            $this->logError('getEmployee failed', $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Create a new employee record.
     * Requirement 2.4: auto-creates a linked users account if one does not exist.
     *
     * @param  array $data  Employee fields
     * @return int          New employee ID
     * @throws \RuntimeException on failure
     */
    public function createEmployee(array $data): int
    {
        // Generate employee code if not provided
        if (empty($data['employee_code'])) {
            $data['employee_code'] = $this->generateEmployeeCode();
        }

        $employeeId = $this->create('employees', [
            'branch_id'             => (int)($data['branch_id'] ?? 0) ?: null,
            'department_id'         => (int)($data['department_id'] ?? 0) ?: null,
            'designation_id'        => (int)($data['designation_id'] ?? 0) ?: null,
            'employee_code'         => $data['employee_code'],
            'full_name'             => $data['full_name'],
            'phone'                 => $data['phone'] ?? null,
            'email'                 => $data['email'] ?? null,
            'nid_number'            => $data['nid_number'] ?? null,
            'joining_date'          => $data['joining_date'] ?? null,
            'basic_salary'          => (float)($data['basic_salary'] ?? 0),
            'allowances'            => (float)($data['allowances'] ?? 0),
            'bank_account'          => $data['bank_account'] ?? null,
            'bank_name'             => $data['bank_name'] ?? null,
            'emergency_contact'     => $data['emergency_contact'] ?? null,
            'emergency_contact_name'=> $data['emergency_contact_name'] ?? null,
            'status'                => 'active',
        ]);

        // Req 2.4: auto-create linked user account if email provided and no user exists
        if (!empty($data['email']) && empty($data['user_id'])) {
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ? LIMIT 1",
                [$data['email']]
            );

            if (!$existingUser) {
                try {
                    $defaultRole = $this->db->fetchOne(
                        "SELECT id FROM roles WHERE name = 'employee' LIMIT 1",
                        []
                    );
                    $roleId = $defaultRole ? (int)$defaultRole['id'] : 4;

                    $username = strtolower(str_replace(' ', '.', $data['full_name']))
                        . '.' . $employeeId;

                    $userId = $this->db->insert('users', [
                        'branch_id'     => (int)($data['branch_id'] ?? 0) ?: null,
                        'role_id'       => $roleId,
                        'username'      => $username,
                        'email'         => $data['email'],
                        'phone'         => $data['phone'] ?? null,
                        'full_name'     => $data['full_name'],
                        'password_hash' => password_hash('changeme123', PASSWORD_BCRYPT),
                        'is_active'     => 1,
                    ]);

                    $this->db->update('employees', ['user_id' => $userId], 'id = ?', [$employeeId]);
                } catch (\Throwable $e) {
                    // Non-fatal: employee created, user creation failed
                    $this->logError('Auto-create user for employee failed', $e, ['employee_id' => $employeeId]);
                }
            } else {
                $this->db->update('employees', ['user_id' => $existingUser['id']], 'id = ?', [$employeeId]);
            }
        }

        return $employeeId;
    }

    /**
     * Update an existing employee record.
     *
     * @param  int   $id
     * @param  array $data
     * @return void
     */
    public function updateEmployee(int $id, array $data): void
    {
        $this->update('employees', $id, [
            'branch_id'             => (int)($data['branch_id'] ?? 0) ?: null,
            'department_id'         => (int)($data['department_id'] ?? 0) ?: null,
            'designation_id'        => (int)($data['designation_id'] ?? 0) ?: null,
            'full_name'             => $data['full_name'],
            'phone'                 => $data['phone'] ?? null,
            'email'                 => $data['email'] ?? null,
            'nid_number'            => $data['nid_number'] ?? null,
            'joining_date'          => $data['joining_date'] ?? null,
            'basic_salary'          => (float)($data['basic_salary'] ?? 0),
            'allowances'            => (float)($data['allowances'] ?? 0),
            'bank_account'          => $data['bank_account'] ?? null,
            'bank_name'             => $data['bank_name'] ?? null,
            'emergency_contact'     => $data['emergency_contact'] ?? null,
            'emergency_contact_name'=> $data['emergency_contact_name'] ?? null,
            'status'                => $data['status'] ?? 'active',
        ]);
    }

    // ── Departments & Designations ────────────────────────────────

    /**
     * Return all departments, optionally filtered by branch.
     *
     * @param  int|null $branchId
     * @return array
     */
    public function getDepartments(?int $branchId = null): array
    {
        $params = [];
        $sql = "SELECT d.*, b.name AS branch_name,
                       e.full_name AS head_name
                FROM departments d
                LEFT JOIN branches  b ON b.id = d.branch_id
                LEFT JOIN employees e ON e.id = d.head_of_department
                WHERE d.is_active = 1";

        if ($branchId !== null) {
            $sql .= " AND d.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY d.name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getDepartments failed', $e);
            return [];
        }
    }

    /**
     * Return all designations, optionally filtered by department.
     *
     * @param  int|null $deptId
     * @return array
     */
    public function getDesignations(?int $deptId = null): array
    {
        $params = [];
        $sql = "SELECT dg.*, d.name AS department_name
                FROM designations dg
                LEFT JOIN departments d ON d.id = dg.department_id
                WHERE dg.is_active = 1";

        if ($deptId !== null) {
            $sql .= " AND dg.department_id = ?";
            $params[] = $deptId;
        }

        $sql .= " ORDER BY dg.title ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getDesignations failed', $e);
            return [];
        }
    }

    // ── Attendance ────────────────────────────────────────────────

    /**
     * Record or update daily attendance for an employee.
     * Requirement 2.5: status must be present/absent/late/half_day/leave.
     *
     * @param  int    $employeeId
     * @param  string $date        Y-m-d format
     * @param  string $status      present|absent|late|half_day|leave
     * @param  string $notes
     * @return void
     * @throws \InvalidArgumentException for invalid status
     */
    public function recordAttendance(int $employeeId, string $date, string $status, string $notes = ''): void
    {
        $validStatuses = ['present', 'absent', 'late', 'half_day', 'leave'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid attendance status: {$status}");
        }

        $employee = $this->findById('employees', $employeeId);
        if (!$employee) {
            throw new \RuntimeException("Employee #{$employeeId} not found.");
        }

        // Check for existing record (UNIQUE employee_id + attendance_date)
        $existing = $this->db->fetchOne(
            "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? LIMIT 1",
            [$employeeId, $date]
        );

        $recordedBy = $_SESSION['user_id'] ?? null;

        if ($existing) {
            $this->db->update('attendance', [
                'status'      => $status,
                'notes'       => $notes,
                'recorded_by' => $recordedBy,
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('attendance', [
                'employee_id'     => $employeeId,
                'branch_id'       => (int)$employee['branch_id'],
                'attendance_date' => $date,
                'status'          => $status,
                'notes'           => $notes,
                'recorded_by'     => $recordedBy,
            ]);
        }
    }

    /**
     * Return attendance records for an employee for a given month/year.
     *
     * @param  int $employeeId
     * @param  int $month  1-12
     * @param  int $year
     * @return array
     */
    public function getAttendance(int $employeeId, int $month, int $year): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM attendance
                 WHERE employee_id = ?
                   AND MONTH(attendance_date) = ?
                   AND YEAR(attendance_date)  = ?
                 ORDER BY attendance_date ASC",
                [$employeeId, $month, $year]
            );
        } catch (\Throwable $e) {
            $this->logError('getAttendance failed', $e);
            return [];
        }
    }

    /**
     * Return a summary count of each attendance status for a given month/year.
     *
     * @param  int $employeeId
     * @param  int $month
     * @param  int $year
     * @return array  Keys: present, absent, late, half_day, leave
     */
    public function getMonthlyAttendanceSummary(int $employeeId, int $month, int $year): array
    {
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'leave' => 0];

        try {
            $rows = $this->db->fetchAll(
                "SELECT status, COUNT(*) AS cnt
                 FROM attendance
                 WHERE employee_id = ?
                   AND MONTH(attendance_date) = ?
                   AND YEAR(attendance_date)  = ?
                 GROUP BY status",
                [$employeeId, $month, $year]
            );

            foreach ($rows as $row) {
                if (isset($summary[$row['status']])) {
                    $summary[$row['status']] = (int)$row['cnt'];
                }
            }
        } catch (\Throwable $e) {
            $this->logError('getMonthlyAttendanceSummary failed', $e);
        }

        return $summary;
    }

    // ── Salary / Payroll ──────────────────────────────────────────

    /**
     * Calculate salary breakdown for an employee for a given month/year
     * without persisting a salary slip.
     * Requirement 2.6: gross = basic_salary + allowances − deductions.
     *
     * @param  int $employeeId
     * @param  int $month
     * @param  int $year
     * @return array  Keys: basic_salary, allowances, deductions, gross_pay, net_pay, summary
     * @throws \RuntimeException if employee not found
     */
    public function calculateSalary(int $employeeId, int $month, int $year): array
    {
        $employee = $this->getEmployee($employeeId);
        if (!$employee) {
            throw new \RuntimeException("Employee #{$employeeId} not found.");
        }

        $summary     = $this->getMonthlyAttendanceSummary($employeeId, $month, $year);
        $totalDays   = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $basicSalary = (float)$employee['basic_salary'];
        $allowances  = (float)$employee['allowances'];
        $absentDays  = $summary['absent'];

        $deductions = $totalDays > 0
            ? round(($basicSalary / $totalDays) * $absentDays, 2)
            : 0.0;

        $grossPay = $basicSalary + $allowances - $deductions;

        return [
            'basic_salary' => $basicSalary,
            'allowances'   => $allowances,
            'deductions'   => $deductions,
            'gross_pay'    => $grossPay,
            'net_pay'      => $grossPay,
            'summary'      => $summary,
            'total_days'   => $totalDays,
        ];
    }

    /**
     * Generate a salary slip for an employee for a given month/year.
     * Requirement 2.6: gross = basic_salary + allowances − deductions
     * Requirement 2.9: prevents duplicate generation.
     *
     * @param  int $employeeId
     * @param  int $month
     * @param  int $year
     * @return array  The generated salary slip record
     * @throws \RuntimeException if duplicate or employee not found
     */
    public function generateSalarySlip(int $employeeId, int $month, int $year): array
    {
        $employee = $this->getEmployee($employeeId);
        if (!$employee) {
            throw new \RuntimeException("Employee #{$employeeId} not found.");
        }

        // Req 2.9: prevent duplicate
        if ($this->checkDuplicateSalarySlip($employeeId, $month, $year)) {
            throw new \RuntimeException(
                "Salary slip for {$employee['full_name']} for " .
                date('F Y', mktime(0, 0, 0, $month, 1, $year)) .
                " has already been generated."
            );
        }

        // Attendance summary for the month
        $summary   = $this->getMonthlyAttendanceSummary($employeeId, $month, $year);
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $basicSalary = (float)$employee['basic_salary'];
        $allowances  = (float)$employee['allowances'];

        // Deductions: absent days proportionally reduce basic salary
        $workingDays = $totalDays; // treat all calendar days as working for simplicity
        $absentDays  = $summary['absent'];
        $deductions  = $workingDays > 0
            ? round(($basicSalary / $workingDays) * $absentDays, 2)
            : 0.0;

        // Req 2.6: gross = basic + allowances − deductions
        $grossPay = $basicSalary + $allowances - $deductions;
        $netPay   = $grossPay; // net = gross (tax/other deductions can be extended later)

        $salaryMonth = sprintf('%04d-%02d-01', $year, $month);

        $slipId = $this->db->insert('salary_slips', [
            'employee_id'   => $employeeId,
            'branch_id'     => (int)$employee['branch_id'],
            'salary_month'  => $salaryMonth,
            'basic_salary'  => $basicSalary,
            'allowances'    => $allowances,
            'deductions'    => $deductions,
            'gross_pay'     => $grossPay,
            'net_pay'       => $netPay,
            'present_days'  => $summary['present'] + $summary['late'] + $summary['half_day'],
            'absent_days'   => $summary['absent'],
            'leave_days'    => $summary['leave'],
            'payment_status'=> 'pending',
            'generated_by'  => $_SESSION['user_id'] ?? null,
        ]);

        return $this->db->fetchOne("SELECT * FROM salary_slips WHERE id = ? LIMIT 1", [$slipId]) ?? [];
    }

    /**
     * Check whether a salary slip already exists for an employee/month/year.
     * Requirement 2.9.
     *
     * @param  int $employeeId
     * @param  int $month
     * @param  int $year
     * @return bool
     */
    public function checkDuplicateSalarySlip(int $employeeId, int $month, int $year): bool
    {
        $salaryMonth = sprintf('%04d-%02d-01', $year, $month);
        try {
            $existing = $this->db->fetchOne(
                "SELECT id FROM salary_slips WHERE employee_id = ? AND salary_month = ? LIMIT 1",
                [$employeeId, $salaryMonth]
            );
            return $existing !== null;
        } catch (\Throwable $e) {
            $this->logError('checkDuplicateSalarySlip failed', $e);
            return false;
        }
    }

    /**
     * Return all salary slips for an employee, or all slips for a branch.
     *
     * @param  int|null $employeeId
     * @param  int|null $branchId
     * @return array
     */
    public function getSalarySlips(?int $employeeId = null, ?int $branchId = null): array
    {
        $params = [];
        $sql = "SELECT ss.*, e.full_name AS employee_name, e.employee_code,
                       d.name AS department_name, dg.title AS designation_title
                FROM salary_slips ss
                JOIN employees   e  ON e.id  = ss.employee_id
                LEFT JOIN departments  d  ON d.id  = e.department_id
                LEFT JOIN designations dg ON dg.id = e.designation_id
                WHERE 1=1";

        if ($employeeId !== null) {
            $sql .= " AND ss.employee_id = ?";
            $params[] = $employeeId;
        }
        if ($branchId !== null) {
            $sql .= " AND ss.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY ss.salary_month DESC, e.full_name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getSalarySlips failed', $e);
            return [];
        }
    }

    // ── Leave Balances ────────────────────────────────────────────

    /**
     * Return leave balances for an employee (all types, current year).
     * Requirement 2.10.
     *
     * @param  int $employeeId
     * @return array  Keyed by leave_type
     */
    public function getLeaveBalance(int $employeeId): array
    {
        $year = (int)date('Y');
        try {
            $rows = $this->db->fetchAll(
                "SELECT * FROM leave_balances
                 WHERE employee_id = ? AND year = ?
                 ORDER BY leave_type ASC",
                [$employeeId, $year]
            );

            // If no records exist yet, return defaults
            if (empty($rows)) {
                return [
                    ['leave_type' => 'annual',  'total_days' => 0, 'used_days' => 0, 'remaining_days' => 0, 'year' => $year],
                    ['leave_type' => 'sick',    'total_days' => 0, 'used_days' => 0, 'remaining_days' => 0, 'year' => $year],
                    ['leave_type' => 'casual',  'total_days' => 0, 'used_days' => 0, 'remaining_days' => 0, 'year' => $year],
                ];
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logError('getLeaveBalance failed', $e);
            return [];
        }
    }

    /**
     * Update (upsert) a leave balance for an employee.
     * Requirement 2.10: track leave balances per employee per leave type.
     *
     * @param  int    $employeeId
     * @param  string $leaveType  annual|sick|casual
     * @param  int    $days       Number of days to set as total_days
     * @param  int    $usedDays   Number of days used (optional)
     * @return void
     * @throws \InvalidArgumentException for invalid leave type
     */
    public function updateLeaveBalance(int $employeeId, string $leaveType, int $days, int $usedDays = 0): void
    {
        $validTypes = ['annual', 'sick', 'casual'];
        if (!in_array($leaveType, $validTypes, true)) {
            throw new \InvalidArgumentException("Invalid leave type: {$leaveType}");
        }

        $year = (int)date('Y');

        $existing = $this->db->fetchOne(
            "SELECT id FROM leave_balances WHERE employee_id = ? AND leave_type = ? AND year = ? LIMIT 1",
            [$employeeId, $leaveType, $year]
        );

        if ($existing) {
            $this->db->update('leave_balances', [
                'total_days' => $days,
                'used_days'  => $usedDays,
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('leave_balances', [
                'employee_id' => $employeeId,
                'leave_type'  => $leaveType,
                'year'        => $year,
                'total_days'  => $days,
                'used_days'   => $usedDays,
            ]);
        }
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * Generate a unique employee code like EMP-0001.
     */
    private function generateEmployeeCode(): string
    {
        try {
            $last = $this->db->fetchOne(
                "SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1",
                []
            );
            if ($last && preg_match('/(\d+)$/', $last['employee_code'], $m)) {
                $next = (int)$m[1] + 1;
            } else {
                $next = 1;
            }
            return 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            return 'EMP-' . strtoupper(substr(uniqid(), -6));
        }
    }
}
