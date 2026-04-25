<?php

/**
 * BranchController — Handles all Branch Management module HTTP requests.
 *
 * Routes are prefixed with /branches.
 * Delegates business logic to BranchService.
 *
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8
 */
class BranchController
{
    private BranchService $branchService;

    public function __construct()
    {
        $this->branchService = new BranchService();
    }

    // ── Index ─────────────────────────────────────────────────────

    public function index(): void
    {
        redirect(base_url('branches'));
    }

    // ── List ──────────────────────────────────────────────────────

    /**
     * List all branches.
     * Req 1.2: comadmin sees all; Req 1.3: branch_admin sees only their own.
     */
    public function list(): void
    {
        PermissionHelper::requirePermission('branches.view');

        // Req 1.3: branch_admin restricted to their own branch
        if (PermissionHelper::isBranchAdmin()) {
            $branchId = (int)($_SESSION['branch_id'] ?? 0);
            $branches = $branchId ? [$this->branchService->getBranch($branchId)] : [];
            $branches = array_filter($branches); // remove null
            $branches = array_values($branches);
        } else {
            $branches = $this->branchService->getBranches();
        }

        $pageTitle      = 'Branches';
        $currentPage    = 'branches';
        $currentSubPage = 'branch-list';
        $viewFile       = BASE_PATH . '/views/branches/index.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Create ────────────────────────────────────────────────────

    /**
     * Show the create branch form.
     */
    public function create(): void
    {
        PermissionHelper::requirePermission('branches.create');

        $users = Database::getInstance()->fetchAll(
            "SELECT id, username, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name ASC",
            []
        );

        $pageTitle      = 'Add Branch';
        $currentPage    = 'branches';
        $currentSubPage = 'branch-create';
        $viewFile       = BASE_PATH . '/views/branches/form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save a new branch (POST).
     * Req 1.8: duplicate code rejected with validation error.
     */
    public function store(): void
    {
        PermissionHelper::requirePermission('branches.create');

        $data = [
            'name'    => sanitize($_POST['name'] ?? ''),
            'code'    => sanitize($_POST['code'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'phone'   => sanitize($_POST['phone'] ?? ''),
            'email'   => sanitize($_POST['email'] ?? ''),
            'manager' => sanitize($_POST['manager'] ?? ''),
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Branch name is required.';
            redirect(base_url('branches/create'));
            return;
        }

        if (empty($data['code'])) {
            $_SESSION['error'] = 'Branch code is required.';
            redirect(base_url('branches/create'));
            return;
        }

        try {
            $id = $this->branchService->createBranch($data);

            // Optionally assign credential on create
            $userId = (int)($_POST['credential_user_id'] ?? 0);
            if ($userId > 0) {
                $this->branchService->assignCredential($id, $userId);
            }

            $_SESSION['success'] = 'Branch created successfully.';
            redirect(base_url('branches'));
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('branches/create'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to create branch: ' . $e->getMessage();
            redirect(base_url('branches/create'));
        }
    }

    // ── View ──────────────────────────────────────────────────────

    /**
     * View branch detail with stats and reports.
     * Req 1.3: branch_admin can only view their own branch.
     */
    public function view(int $id): void
    {
        PermissionHelper::requirePermission('branches.view');

        // Req 1.3: branch_admin restricted to their own branch
        if (PermissionHelper::isBranchAdmin()) {
            $sessionBranchId = (int)($_SESSION['branch_id'] ?? 0);
            if ($sessionBranchId !== $id) {
                $_SESSION['error'] = 'You can only view your own branch.';
                redirect(base_url('branches'));
                return;
            }
        }

        $branch = $this->branchService->getBranch($id);
        if (!$branch) {
            $_SESSION['error'] = 'Branch not found.';
            redirect(base_url('branches'));
            return;
        }

        $reports    = $this->branchService->getReports($id);
        $credential = $this->branchService->getCredential($id);
        $users      = Database::getInstance()->fetchAll(
            "SELECT id, username, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name ASC",
            []
        );

        $pageTitle      = 'Branch: ' . htmlspecialchars($branch['name']);
        $currentPage    = 'branches';
        $currentSubPage = 'branch-view';
        $viewFile       = BASE_PATH . '/views/branches/view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Edit ──────────────────────────────────────────────────────

    /**
     * Show the edit branch form.
     * Req 1.3: branch_admin can only edit their own branch.
     */
    public function edit(int $id): void
    {
        PermissionHelper::requirePermission('branches.edit');

        // Req 1.3: branch_admin restricted to their own branch
        if (PermissionHelper::isBranchAdmin()) {
            $sessionBranchId = (int)($_SESSION['branch_id'] ?? 0);
            if ($sessionBranchId !== $id) {
                $_SESSION['error'] = 'You can only edit your own branch.';
                redirect(base_url('branches'));
                return;
            }
        }

        $branch = $this->branchService->getBranch($id);
        if (!$branch) {
            $_SESSION['error'] = 'Branch not found.';
            redirect(base_url('branches'));
            return;
        }

        $users = Database::getInstance()->fetchAll(
            "SELECT id, username, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name ASC",
            []
        );

        $pageTitle      = 'Edit Branch';
        $currentPage    = 'branches';
        $currentSubPage = 'branch-edit';
        $viewFile       = BASE_PATH . '/views/branches/form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save branch changes (POST).
     * Req 1.8: duplicate code rejected.
     */
    public function update(int $id): void
    {
        PermissionHelper::requirePermission('branches.edit');

        // Req 1.3: branch_admin restricted to their own branch
        if (PermissionHelper::isBranchAdmin()) {
            $sessionBranchId = (int)($_SESSION['branch_id'] ?? 0);
            if ($sessionBranchId !== $id) {
                $_SESSION['error'] = 'You can only edit your own branch.';
                redirect(base_url('branches'));
                return;
            }
        }

        $data = [
            'name'    => sanitize($_POST['name'] ?? ''),
            'code'    => sanitize($_POST['code'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'phone'   => sanitize($_POST['phone'] ?? ''),
            'email'   => sanitize($_POST['email'] ?? ''),
            'manager' => sanitize($_POST['manager'] ?? ''),
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Branch name is required.';
            redirect(base_url('branches/edit/' . $id));
            return;
        }

        if (empty($data['code'])) {
            $_SESSION['error'] = 'Branch code is required.';
            redirect(base_url('branches/edit/' . $id));
            return;
        }

        try {
            $this->branchService->updateBranch($id, $data);
            $_SESSION['success'] = 'Branch updated successfully.';
            redirect(base_url('branches/view/' . $id));
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('branches/edit/' . $id));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to update branch: ' . $e->getMessage();
            redirect(base_url('branches/edit/' . $id));
        }
    }

    // ── Activate / Deactivate ─────────────────────────────────────

    /**
     * Deactivate a branch (POST).
     * Req 1.6: deactivated branch prevents new customers/invoices.
     */
    public function deactivate(int $id): void
    {
        PermissionHelper::requirePermission('branches.edit');

        try {
            $this->branchService->deactivateBranch($id);
            $_SESSION['success'] = 'Branch deactivated successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to deactivate branch: ' . $e->getMessage();
        }

        redirect(base_url('branches'));
    }

    /**
     * Activate a branch (POST).
     */
    public function activate(int $id): void
    {
        PermissionHelper::requirePermission('branches.edit');

        try {
            $this->branchService->activateBranch($id);
            $_SESSION['success'] = 'Branch activated successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to activate branch: ' . $e->getMessage();
        }

        redirect(base_url('branches'));
    }

    // ── Reports ───────────────────────────────────────────────────

    /**
     * Generate a summary report for a branch (POST).
     * Req 1.5: per-branch summary reports filterable by date range.
     */
    public function generateReport(int $id): void
    {
        PermissionHelper::requirePermission('branches.reports');

        $dateFrom = sanitize($_POST['date_from'] ?? date('Y-m-01'));
        $dateTo   = sanitize($_POST['date_to']   ?? date('Y-m-d'));

        try {
            $this->branchService->generateSummaryReport($id, $dateFrom, $dateTo);
            $_SESSION['success'] = 'Report generated successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to generate report: ' . $e->getMessage();
        }

        redirect(base_url('branches/view/' . $id));
    }

    // ── Credentials ───────────────────────────────────────────────

    /**
     * Assign a login credential to a branch (POST).
     * Req 1.4: unique login credential per branch.
     */
    public function assignCredential(int $id): void
    {
        PermissionHelper::requirePermission('branches.edit');

        $userId = (int)($_POST['user_id'] ?? 0);

        if (!$userId) {
            $_SESSION['error'] = 'Please select a user to assign.';
            redirect(base_url('branches/view/' . $id));
            return;
        }

        try {
            $this->branchService->assignCredential($id, $userId);
            $_SESSION['success'] = 'Credential assigned successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to assign credential: ' . $e->getMessage();
        }

        redirect(base_url('branches/view/' . $id));
    }

    // ── Export ────────────────────────────────────────────────────

    /**
     * Export branch report history as CSV.
     * Req 1.7: export to CSV.
     */
    public function exportCsv(int $id): void
    {
        PermissionHelper::requirePermission('branches.reports');

        $branch  = $this->branchService->getBranch($id);
        if (!$branch) {
            $_SESSION['error'] = 'Branch not found.';
            redirect(base_url('branches'));
            return;
        }

        $reports = $this->branchService->getReports($id);

        $filename = 'branch_report_' . ($branch['code'] ?? $id) . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM for Excel UTF-8 compatibility
        fwrite($out, "\xEF\xBB\xBF");

        // Header row
        fputcsv($out, [
            'Branch',
            'Period Start',
            'Period End',
            'Customer Count',
            'Monthly Revenue',
            'Outstanding Dues',
            'Active Tickets',
            'Generated By',
            'Generated At',
        ]);

        foreach ($reports as $report) {
            fputcsv($out, [
                $branch['name'],
                $report['period_start'],
                $report['period_end'],
                $report['customer_count'],
                number_format((float)$report['monthly_revenue'], 2, '.', ''),
                number_format((float)$report['outstanding_dues'], 2, '.', ''),
                $report['active_tickets'],
                $report['generated_by_name'] ?? '',
                $report['generated_at'],
            ]);
        }

        fclose($out);
        exit;
    }
}
