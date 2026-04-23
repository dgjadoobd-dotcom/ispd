<?php

/**
 * SupportController — Handles all Support & Ticketing module HTTP requests.
 *
 * Routes are prefixed with /support.
 * Delegates business logic to SupportService.
 *
 * Requirements: 3.2, 3.3, 3.4, 3.5, 3.7, 3.8, 3.9, 3.10
 */
class SupportController
{
    private SupportService $supportService;

    public function __construct()
    {
        $this->supportService = new SupportService();
    }

    // ── Index ─────────────────────────────────────────────────────

    public function index(): void
    {
        redirect(base_url('support/tickets'));
    }

    // ── Ticket List ───────────────────────────────────────────────

    /**
     * Paginated ticket list with filters.
     * Requirement 3.1: paginated admin list.
     */
    public function tickets(): void
    {
        PermissionHelper::requirePermission('support.view');

        $branchId = PermissionHelper::getBranchFilter();

        $filters = [
            'status'      => sanitize($_GET['status']      ?? ''),
            'priority'    => sanitize($_GET['priority']    ?? ''),
            'category_id' => (int)($_GET['category_id']   ?? 0) ?: null,
            'branch_id'   => $branchId,
            'search'      => sanitize($_GET['search']      ?? ''),
            'page'        => max(1, (int)($_GET['page']    ?? 1)),
            'limit'       => 25,
        ];

        $result     = $this->supportService->getTickets($filters);
        $categories = $this->supportService->getCategories();

        $pageTitle      = 'Support Tickets';
        $currentPage    = 'support';
        $currentSubPage = 'tickets';
        $viewFile       = BASE_PATH . '/views/support/tickets.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Create ────────────────────────────────────────────────────

    /**
     * Show the create ticket form.
     */
    public function create(): void
    {
        PermissionHelper::requirePermission('support.create');

        $branchId   = PermissionHelper::getBranchFilter();
        $categories = $this->supportService->getCategories();
        $employees  = $this->supportService->getAssignableEmployees($branchId);
        $customers  = Database::getInstance()->fetchAll(
            "SELECT id, full_name, customer_code, phone FROM customers WHERE status = 'active' ORDER BY full_name ASC",
            []
        );

        $pageTitle      = 'New Support Ticket';
        $currentPage    = 'support';
        $currentSubPage = 'tickets';
        $viewFile       = BASE_PATH . '/views/support/ticket_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save a new ticket (POST).
     * Requirement 3.2: SLA deadline set on creation.
     * Requirement 3.10: duplicate warning.
     */
    public function store(): void
    {
        PermissionHelper::requirePermission('support.create');

        try {
            $branchId = PermissionHelper::getBranchFilter()
                ?? (int)($_POST['branch_id'] ?? 0) ?: null;

            $data = [
                'customer_id'  => (int)($_POST['customer_id']  ?? 0) ?: null,
                'branch_id'    => $branchId,
                'subject'      => sanitize($_POST['subject']      ?? ''),
                'description'  => sanitize($_POST['description']  ?? ''),
                'priority'     => sanitize($_POST['priority']     ?? 'medium'),
                'category_id'  => (int)($_POST['category_id']  ?? 0) ?: null,
                'assigned_to'  => (int)($_POST['assigned_to']  ?? 0) ?: null,
            ];

            if (empty($data['subject'])) {
                $_SESSION['error'] = 'Ticket subject is required.';
                redirect(base_url('support/tickets/create'));
                return;
            }

            $result = $this->supportService->createTicket($data);

            // Req 3.10: show duplicate warning but allow creation
            if ($result['duplicate_warning']) {
                $_SESSION['warning'] = 'Warning: This customer already has an open ticket submitted within the last 24 hours. The new ticket has been created anyway.';
            }

            // If assigned_to was set, trigger assignment (sends SMS)
            if (!empty($data['assigned_to'])) {
                try {
                    $this->supportService->assignTicket(
                        $result['id'],
                        (int)$data['assigned_to'],
                        ''
                    );
                } catch (\Throwable $e) {
                    // Non-fatal
                }
            }

            $_SESSION['success'] = 'Ticket #' . $result['id'] . ' created successfully.';
            redirect(base_url('support/tickets/view/' . $result['id']));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('support/tickets/create'));
        }
    }

    // ── View ──────────────────────────────────────────────────────

    /**
     * Ticket detail page with comments and assignment history.
     */
    public function view(int $id): void
    {
        PermissionHelper::requirePermission('support.view');

        $ticket = $this->supportService->getTicket($id);
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket not found.';
            redirect(base_url('support/tickets'));
            return;
        }

        $branchId  = PermissionHelper::getBranchFilter();
        $employees = $this->supportService->getAssignableEmployees($branchId);

        $pageTitle      = 'Ticket #' . $id . ': ' . htmlspecialchars($ticket['subject']);
        $currentPage    = 'support';
        $currentSubPage = 'tickets';
        $viewFile       = BASE_PATH . '/views/support/ticket_view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Edit ──────────────────────────────────────────────────────

    /**
     * Show the edit ticket form.
     */
    public function edit(int $id): void
    {
        PermissionHelper::requirePermission('support.edit');

        $ticket = $this->supportService->getTicket($id);
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket not found.';
            redirect(base_url('support/tickets'));
            return;
        }

        $branchId   = PermissionHelper::getBranchFilter();
        $categories = $this->supportService->getCategories();
        $employees  = $this->supportService->getAssignableEmployees($branchId);
        $customers  = Database::getInstance()->fetchAll(
            "SELECT id, full_name, customer_code FROM customers WHERE status = 'active' ORDER BY full_name ASC",
            []
        );

        $pageTitle      = 'Edit Ticket #' . $id;
        $currentPage    = 'support';
        $currentSubPage = 'tickets';
        $viewFile       = BASE_PATH . '/views/support/ticket_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save ticket changes (POST).
     */
    public function update(int $id): void
    {
        PermissionHelper::requirePermission('support.edit');

        try {
            $data = [
                'subject'     => sanitize($_POST['subject']     ?? ''),
                'description' => sanitize($_POST['description'] ?? ''),
                'priority'    => sanitize($_POST['priority']    ?? 'medium'),
                'status'      => sanitize($_POST['status']      ?? 'open'),
                'category_id' => (int)($_POST['category_id']   ?? 0) ?: null,
            ];

            if (empty($data['subject'])) {
                $_SESSION['error'] = 'Ticket subject is required.';
                redirect(base_url('support/tickets/edit/' . $id));
                return;
            }

            $this->supportService->updateTicket($id, $data);
            $_SESSION['success'] = 'Ticket updated successfully.';
            redirect(base_url('support/tickets/view/' . $id));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('support/tickets/edit/' . $id));
        }
    }

    // ── Assign ────────────────────────────────────────────────────

    /**
     * Assign ticket to an employee (POST).
     * Requirements 3.4, 3.5: assign + SMS notification.
     */
    public function assign(int $id): void
    {
        PermissionHelper::requirePermission('support.assign');

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $notes      = sanitize($_POST['notes'] ?? '');

        if (!$employeeId) {
            $_SESSION['error'] = 'Please select an employee to assign.';
            redirect(base_url('support/tickets/view/' . $id));
            return;
        }

        try {
            $this->supportService->assignTicket($id, $employeeId, $notes);
            $_SESSION['success'] = 'Ticket assigned successfully. SMS notification sent.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('support/tickets/view/' . $id));
    }

    // ── Comment ───────────────────────────────────────────────────

    /**
     * Add a comment to a ticket (POST).
     * Requirement 3.7: comment/activity thread.
     */
    public function comment(int $id): void
    {
        PermissionHelper::requirePermission('support.view');

        $message    = sanitize($_POST['message']     ?? '');
        $isInternal = (bool)($_POST['is_internal']   ?? false);

        if (empty($message)) {
            $_SESSION['error'] = 'Comment message cannot be empty.';
            redirect(base_url('support/tickets/view/' . $id));
            return;
        }

        try {
            $this->supportService->addComment($id, $message, $isInternal);
            $_SESSION['success'] = 'Comment added.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('support/tickets/view/' . $id));
    }

    // ── Resolve ───────────────────────────────────────────────────

    /**
     * Resolve a ticket (POST).
     * Requirement 3.8: record resolution notes, resolver, resolved_at.
     */
    public function resolve(int $id): void
    {
        PermissionHelper::requirePermission('support.resolve');

        $notes = sanitize($_POST['resolution_notes'] ?? '');

        if (empty($notes)) {
            $_SESSION['error'] = 'Resolution notes are required.';
            redirect(base_url('support/tickets/view/' . $id));
            return;
        }

        try {
            $this->supportService->resolveTicket($id, $notes);
            $_SESSION['success'] = 'Ticket resolved successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('support/tickets/view/' . $id));
    }

    // ── Close ─────────────────────────────────────────────────────

    /**
     * Close a ticket (POST).
     */
    public function close(int $id): void
    {
        PermissionHelper::requirePermission('support.edit');

        try {
            $this->supportService->closeTicket($id);
            $_SESSION['success'] = 'Ticket closed.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('support/tickets/view/' . $id));
    }

    // ── Dashboard ─────────────────────────────────────────────────

    /**
     * SLA compliance dashboard.
     * Requirement 3.9: % resolved within SLA per category and per employee.
     */
    public function dashboard(): void
    {
        PermissionHelper::requirePermission('support.reports');

        $branchId   = PermissionHelper::getBranchFilter();
        $dashboard  = $this->supportService->getSlaComplianceDashboard($branchId);

        // Open ticket counts for summary cards
        $db = Database::getInstance();
        $branchFilter = $branchId !== null ? ' AND branch_id = ?' : '';
        $branchParams = $branchId !== null ? [$branchId] : [];

        $openCount     = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 'open'{$branchFilter}", $branchParams)['c'] ?? 0);
        $breachedCount = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 'sla_breached'{$branchFilter}", $branchParams)['c'] ?? 0);
        $todayCount    = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM support_tickets WHERE DATE(created_at) = DATE('now'){$branchFilter}", $branchParams)['c'] ?? 0);

        $pageTitle      = 'SLA Compliance Dashboard';
        $currentPage    = 'support';
        $currentSubPage = 'dashboard';
        $viewFile       = BASE_PATH . '/views/support/dashboard.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── SLA Check (cron-callable) ─────────────────────────────────

    /**
     * Run SLA breach check (POST — cron-callable).
     * Requirement 3.3: marks tickets as sla_breached when past deadline.
     */
    public function checkSla(): void
    {
        PermissionHelper::requirePermission('support.edit');

        $count = $this->supportService->checkAndMarkSlaBreaches();

        $_SESSION['success'] = "SLA check complete. {$count} ticket(s) marked as breached.";
        redirect(base_url('support/tickets'));
    }
}
