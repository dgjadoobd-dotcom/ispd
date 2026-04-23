<?php

/**
 * HrController — Handles all HR & Payroll module HTTP requests.
 *
 * Routes are prefixed with /hr.
 * Delegates business logic to HrService.
 *
 * Requirements: 2.5, 2.6, 2.10
 */
class HrController
{
    private HrService $hrService;

    public function __construct()
    {
        $this->hrService = new HrService();
    }

    // ── Index ─────────────────────────────────────────────────────

    public function index(): void
    {
        redirect(base_url('hr/employees'));
    }

    // ── Employees ─────────────────────────────────────────────────

    /**
     * List all employees with optional branch filter.
     */
    public function employees(): void
    {
        PermissionHelper::requirePermission('hr.view');

        $branchId = PermissionHelper::getBranchFilter();
        $employees = $this->hrService->getEmployees($branchId);
        $departments = $this->hrService->getDepartments($branchId);

        $pageTitle      = 'Employees';
        $currentPage    = 'hr';
        $currentSubPage = 'employees';
        $viewFile       = BASE_PATH . '/views/hr/employees.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Show the create employee form.
     */
    public function createEmployee(): void
    {
        PermissionHelper::requirePermission('hr.create');

        $branchId    = PermissionHelper::getBranchFilter();
        $departments = $this->hrService->getDepartments($branchId);
        $designations = $this->hrService->getDesignations();
        $branches    = Database::getInstance()->fetchAll(
            "SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC",
            []
        );

        $pageTitle      = 'Add Employee';
        $currentPage    = 'hr';
        $currentSubPage = 'employees';
        $viewFile       = BASE_PATH . '/views/hr/employee_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save a new employee (POST).
     */
    public function storeEmployee(): void
    {
        PermissionHelper::requirePermission('hr.create');

        try {
            $data = [
                'branch_id'              => (int)($_POST['branch_id'] ?? 0),
                'department_id'          => (int)($_POST['department_id'] ?? 0) ?: null,
                'designation_id'         => (int)($_POST['designation_id'] ?? 0) ?: null,
                'full_name'              => sanitize($_POST['full_name'] ?? ''),
                'phone'                  => sanitize($_POST['phone'] ?? ''),
                'email'                  => sanitize($_POST['email'] ?? ''),
                'nid_number'             => sanitize($_POST['nid_number'] ?? ''),
                'joining_date'           => sanitize($_POST['joining_date'] ?? ''),
                'basic_salary'           => (float)($_POST['basic_salary'] ?? 0),
                'allowances'             => (float)($_POST['allowances'] ?? 0),
                'bank_account'           => sanitize($_POST['bank_account'] ?? ''),
                'bank_name'              => sanitize($_POST['bank_name'] ?? ''),
                'emergency_contact'      => sanitize($_POST['emergency_contact'] ?? ''),
                'emergency_contact_name' => sanitize($_POST['emergency_contact_name'] ?? ''),
            ];

            if (empty($data['full_name'])) {
                $_SESSION['error'] = 'Employee name is required.';
                redirect(base_url('hr/employees/create'));
                return;
            }

            $id = $this->hrService->createEmployee($data);
            $_SESSION['success'] = 'Employee created successfully.';
            redirect(base_url('hr/employees/view/' . $id));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('hr/employees/create'));
        }
    }

    /**
     * View a single employee's detail page.
     */
    public function viewEmployee(int $id): void
    {
        PermissionHelper::requirePermission('hr.view');

        $employee = $this->hrService->getEmployee($id);
        if (!$employee) {
            $_SESSION['error'] = 'Employee not found.';
            redirect(base_url('hr/employees'));
            return;
        }

        $leaveBalances = $this->hrService->getLeaveBalance($id);
        $salarySlips   = $this->hrService->getSalarySlips($id);

        $pageTitle      = 'Employee: ' . htmlspecialchars($employee['full_name']);
        $currentPage    = 'hr';
        $currentSubPage = 'employees';
        $viewFile       = BASE_PATH . '/views/hr/employee_view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Show the edit employee form.
     */
    public function editEmployee(int $id): void
    {
        PermissionHelper::requirePermission('hr.edit');

        $employee = $this->hrService->getEmployee($id);
        if (!$employee) {
            $_SESSION['error'] = 'Employee not found.';
            redirect(base_url('hr/employees'));
            return;
        }

        $branchId     = PermissionHelper::getBranchFilter();
        $departments  = $this->hrService->getDepartments($branchId);
        $designations = $this->hrService->getDesignations();
        $branches     = Database::getInstance()->fetchAll(
            "SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC",
            []
        );

        $pageTitle      = 'Edit Employee';
        $currentPage    = 'hr';
        $currentSubPage = 'employees';
        $viewFile       = BASE_PATH . '/views/hr/employee_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save employee changes (POST).
     */
    public function updateEmployee(int $id): void
    {
        PermissionHelper::requirePermission('hr.edit');

        try {
            $data = [
                'branch_id'              => (int)($_POST['branch_id'] ?? 0),
                'department_id'          => (int)($_POST['department_id'] ?? 0) ?: null,
                'designation_id'         => (int)($_POST['designation_id'] ?? 0) ?: null,
                'full_name'              => sanitize($_POST['full_name'] ?? ''),
                'phone'                  => sanitize($_POST['phone'] ?? ''),
                'email'                  => sanitize($_POST['email'] ?? ''),
                'nid_number'             => sanitize($_POST['nid_number'] ?? ''),
                'joining_date'           => sanitize($_POST['joining_date'] ?? ''),
                'basic_salary'           => (float)($_POST['basic_salary'] ?? 0),
                'allowances'             => (float)($_POST['allowances'] ?? 0),
                'bank_account'           => sanitize($_POST['bank_account'] ?? ''),
                'bank_name'              => sanitize($_POST['bank_name'] ?? ''),
                'emergency_contact'      => sanitize($_POST['emergency_contact'] ?? ''),
                'emergency_contact_name' => sanitize($_POST['emergency_contact_name'] ?? ''),
                'status'                 => sanitize($_POST['status'] ?? 'active'),
            ];

            if (empty($data['full_name'])) {
                $_SESSION['error'] = 'Employee name is required.';
                redirect(base_url('hr/employees/edit/' . $id));
                return;
            }

            $this->hrService->updateEmployee($id, $data);
            $_SESSION['success'] = 'Employee updated successfully.';
            redirect(base_url('hr/employees/view/' . $id));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('hr/employees/edit/' . $id));
        }
    }

    /**
     * Update leave balance for an employee (POST).
     * Requirement 2.10.
     */
    public function updateLeaveBalance(int $id): void
    {
        PermissionHelper::requirePermission('hr.edit');

        $leaveType = sanitize($_POST['leave_type'] ?? '');
        $days      = (int)($_POST['days'] ?? 0);
        $usedDays  = (int)($_POST['used_days'] ?? 0);

        try {
            $this->hrService->updateLeaveBalance($id, $leaveType, $days, $usedDays);
            $_SESSION['success'] = 'Leave balance updated successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('hr/employees/view/' . $id));
    }

    /**
     * Store a performance appraisal for an employee (POST).
     * Requirement 2.8: rating (1-5), reviewer, review period, comments.
     */
    public function storeAppraisal(int $id): void
    {
        PermissionHelper::requirePermission('hr.edit');

        $reviewPeriod = sanitize($_POST['review_period'] ?? '');
        $rating       = (int)($_POST['rating'] ?? 0);
        $comments     = sanitize($_POST['comments'] ?? '');

        if (empty($reviewPeriod) || $rating < 1 || $rating > 5) {
            $_SESSION['error'] = 'Review period and a valid rating (1–5) are required.';
            redirect(base_url('hr/employees/view/' . $id));
            return;
        }

        try {
            Database::getInstance()->insert('performance_appraisals', [
                'employee_id'   => $id,
                'reviewer_id'   => $_SESSION['user_id'] ?? null,
                'review_period' => $reviewPeriod,
                'rating'        => $rating,
                'comments'      => $comments,
            ]);
            $_SESSION['success'] = 'Performance appraisal saved.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to save appraisal: ' . $e->getMessage();
        }

        redirect(base_url('hr/employees/view/' . $id));
    }

    // ── Attendance ────────────────────────────────────────────────

    /**
     * Attendance management page.
     */
    public function attendance(): void
    {
        PermissionHelper::requirePermission('hr.attendance');

        $branchId  = PermissionHelper::getBranchFilter();
        $employees = $this->hrService->getEmployees($branchId);

        $selectedEmployee = (int)($_GET['employee_id'] ?? 0);
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));

        $attendanceRecords = [];
        $summary           = [];

        if ($selectedEmployee) {
            $attendanceRecords = $this->hrService->getAttendance($selectedEmployee, $month, $year);
            $summary           = $this->hrService->getMonthlyAttendanceSummary($selectedEmployee, $month, $year);
        }

        $pageTitle      = 'Attendance';
        $currentPage    = 'hr';
        $currentSubPage = 'attendance';
        $viewFile       = BASE_PATH . '/views/hr/attendance.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Record attendance (POST).
     * Requirement 2.5: status values present/absent/late/half_day/leave.
     */
    public function storeAttendance(): void
    {
        PermissionHelper::requirePermission('hr.attendance');

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $date       = sanitize($_POST['attendance_date'] ?? date('Y-m-d'));
        $status     = sanitize($_POST['status'] ?? '');
        $notes      = sanitize($_POST['notes'] ?? '');

        if (!$employeeId || empty($status)) {
            $_SESSION['error'] = 'Employee and status are required.';
            redirect(base_url('hr/attendance'));
            return;
        }

        try {
            $this->hrService->recordAttendance($employeeId, $date, $status, $notes);
            $_SESSION['success'] = 'Attendance recorded successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('hr/attendance?employee_id=' . $employeeId
            . '&month=' . (int)date('n', strtotime($date))
            . '&year='  . (int)date('Y', strtotime($date))));
    }

    // ── Payroll ───────────────────────────────────────────────────

    /**
     * Payroll management page.
     */
    public function payroll(): void
    {
        PermissionHelper::requirePermission('hr.payroll');

        $branchId    = PermissionHelper::getBranchFilter();
        $salarySlips = $this->hrService->getSalarySlips(null, $branchId);
        $employees   = $this->hrService->getEmployees($branchId);

        $pageTitle      = 'Payroll';
        $currentPage    = 'hr';
        $currentSubPage = 'payroll';
        $viewFile       = BASE_PATH . '/views/hr/payroll.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Generate salary slips for a month (POST).
     * Requirement 2.6: gross = basic + allowances − deductions.
     * Requirement 2.9: prevents duplicate generation.
     */
    public function generatePayroll(): void
    {
        PermissionHelper::requirePermission('hr.payroll');

        $month      = (int)($_POST['month'] ?? date('n'));
        $year       = (int)($_POST['year']  ?? date('Y'));
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $branchId   = PermissionHelper::getBranchFilter();

        $generated = 0;
        $skipped   = 0;
        $errors    = [];

        // Determine which employees to process
        if ($employeeId) {
            $employees = [$this->hrService->getEmployee($employeeId)];
            $employees = array_filter($employees); // remove null
        } else {
            $employees = $this->hrService->getEmployees($branchId);
        }

        foreach ($employees as $emp) {
            try {
                $this->hrService->generateSalarySlip((int)$emp['id'], $month, $year);
                $generated++;
            } catch (\RuntimeException $e) {
                // Duplicate or not found — count as skipped
                if (str_contains($e->getMessage(), 'already been generated')) {
                    $skipped++;
                } else {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if ($generated > 0) {
            $_SESSION['success'] = "Generated {$generated} salary slip(s)."
                . ($skipped > 0 ? " {$skipped} skipped (already generated)." : '');
        } elseif ($skipped > 0) {
            $_SESSION['error'] = "All salary slips for this period have already been generated.";
        } else {
            $_SESSION['error'] = 'No salary slips generated.'
                . (!empty($errors) ? ' ' . implode('; ', $errors) : '');
        }

        redirect(base_url('hr/payroll'));
    }

    /**
     * View a single salary slip.
     */
    public function viewSalarySlip(int $id): void
    {
        PermissionHelper::requirePermission('hr.payroll');

        $slip = Database::getInstance()->fetchOne(
            "SELECT ss.*, e.full_name AS employee_name, e.employee_code,
                    e.phone AS employee_phone, e.bank_account, e.bank_name,
                    d.name AS department_name, dg.title AS designation_title,
                    b.name AS branch_name
             FROM salary_slips ss
             JOIN employees   e  ON e.id  = ss.employee_id
             LEFT JOIN departments  d  ON d.id  = e.department_id
             LEFT JOIN designations dg ON dg.id = e.designation_id
             LEFT JOIN branches     b  ON b.id  = ss.branch_id
             WHERE ss.id = ? LIMIT 1",
            [$id]
        );

        if (!$slip) {
            $_SESSION['error'] = 'Salary slip not found.';
            redirect(base_url('hr/payroll'));
            return;
        }

        $pageTitle      = 'Salary Slip';
        $currentPage    = 'hr';
        $currentSubPage = 'payroll';
        $viewFile       = BASE_PATH . '/views/hr/salary_slip.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Departments ───────────────────────────────────────────────

    /**
     * Department management page.
     */
    public function departments(): void
    {
        PermissionHelper::requirePermission('hr.view');

        $branchId    = PermissionHelper::getBranchFilter();
        $departments = $this->hrService->getDepartments($branchId);
        $branches    = Database::getInstance()->fetchAll(
            "SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name ASC",
            []
        );
        $employees = $this->hrService->getEmployees($branchId);

        $pageTitle      = 'Departments';
        $currentPage    = 'hr';
        $currentSubPage = 'departments';
        $viewFile       = BASE_PATH . '/views/hr/departments.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save a new department (POST).
     */
    public function storeDepartment(): void
    {
        PermissionHelper::requirePermission('hr.create');

        $name     = sanitize($_POST['name'] ?? '');
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $headId   = (int)($_POST['head_of_department'] ?? 0) ?: null;
        $desc     = sanitize($_POST['description'] ?? '');

        if (empty($name) || !$branchId) {
            $_SESSION['error'] = 'Department name and branch are required.';
            redirect(base_url('hr/departments'));
            return;
        }

        try {
            Database::getInstance()->insert('departments', [
                'branch_id'          => $branchId,
                'name'               => $name,
                'head_of_department' => $headId,
                'description'        => $desc,
                'is_active'          => 1,
            ]);
            $_SESSION['success'] = 'Department created successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to create department: ' . $e->getMessage();
        }

        redirect(base_url('hr/departments'));
    }

    /**
     * Update an existing department (POST).
     */
    public function updateDepartment(int $id): void
    {
        PermissionHelper::requirePermission('hr.edit');

        $name     = sanitize($_POST['name'] ?? '');
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $headId   = (int)($_POST['head_of_department'] ?? 0) ?: null;
        $desc     = sanitize($_POST['description'] ?? '');
        $active   = (int)($_POST['is_active'] ?? 1);

        if (empty($name)) {
            $_SESSION['error'] = 'Department name is required.';
            redirect(base_url('hr/departments'));
            return;
        }

        try {
            Database::getInstance()->update('departments', [
                'branch_id'          => $branchId,
                'name'               => $name,
                'head_of_department' => $headId,
                'description'        => $desc,
                'is_active'          => $active,
            ], 'id = ?', [$id]);
            $_SESSION['success'] = 'Department updated successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to update department: ' . $e->getMessage();
        }

        redirect(base_url('hr/departments'));
    }

    /**
     * Delete a department (POST).
     */
    public function deleteDepartment(int $id): void
    {
        PermissionHelper::requirePermission('hr.delete');

        try {
            Database::getInstance()->delete('departments', 'id = ?', [$id]);
            $_SESSION['success'] = 'Department deleted.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Cannot delete department: ' . $e->getMessage();
        }

        redirect(base_url('hr/departments'));
    }
}
