<?php

/**
 * SalesInvoiceController — Handles Sales & Service Invoicing module HTTP requests.
 *
 * Routes are prefixed with /sales.
 * Delegates business logic to SalesInvoiceService.
 *
 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7
 */
class SalesInvoiceController
{
    private SalesInvoiceService $salesService;

    public function __construct()
    {
        $this->salesService = new SalesInvoiceService();
    }

    public function index(): void
    {
        redirect(base_url('sales/invoices'));
    }

    public function invoices(): void
    {
        PermissionHelper::requirePermission('sales.view');

        $branchId = PermissionHelper::getBranchFilter();

        $filters = [
            'status'    => sanitize($_GET['status']    ?? ''),
            'type'     => sanitize($_GET['type']     ?? ''),
            'branch_id' => $branchId,
            'search'   => sanitize($_GET['search']   ?? ''),
            'page'     => max(1, (int)($_GET['page'] ?? 1)),
            'limit'    => 25,
        ];

        $result    = $this->salesService->getInvoices($filters);
        $customers = $this->salesService->getActiveCustomers($branchId);

        $pageTitle      = 'Sales Invoices';
        $currentPage   = 'sales';
        $currentSubPage = 'invoices';
        $viewFile      = BASE_PATH . '/views/sales/invoices.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function create(): void
    {
        PermissionHelper::requirePermission('sales.create');

        $branchId   = PermissionHelper::getBranchFilter();
        $customers = $this->salesService->getActiveCustomers($branchId);

        $pageTitle      = 'Create Invoice';
        $currentPage   = 'sales';
        $currentSubPage = 'invoice-create';
        $viewFile      = BASE_PATH . '/views/sales/invoice_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function store(): void
    {
        PermissionHelper::requirePermission('sales.create');

        try {
            $branchId = PermissionHelper::getBranchFilter()
                ?? (int)($_POST['branch_id'] ?? 0) ?: null;

            $data = [
                'customer_id'   => (int)($_POST['customer_id']   ?? 0) ?: null,
                'branch_id'   => $branchId,
                'invoice_type' => sanitize($_POST['invoice_type'] ?? 'service'),
                'items'       => $_POST['items'] ?? [],
                'discount'    => (float)($_POST['discount']    ?? 0),
                'notes'       => sanitize($_POST['notes']       ?? ''),
                'connection_date' => sanitize($_POST['connection_date'] ?? ''),
                'otc_amount' => (float)($_POST['otc_amount'] ?? 0),
                'created_by'  => $_SESSION['user_id'] ?? null,
            ];

            if (empty($data['customer_id'])) {
                $_SESSION['error'] = 'Customer is required.';
                redirect(base_url('sales/create'));
                return;
            }

            if (empty($data['items'])) {
                $_SESSION['error'] = 'At least one line item is required.';
                redirect(base_url('sales/create'));
                return;
            }

            $invoiceId = $this->salesService->createInvoice($data);

            $_SESSION['success'] = 'Invoice #' . $invoiceId . ' created successfully.';
            redirect(base_url('sales/view/' . $invoiceId));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('sales/create'));
        }
    }

    public function view(int $id): void
    {
        PermissionHelper::requirePermission('sales.view');

        $invoice = $this->salesService->getInvoice($id);
        if (!$invoice) {
            $_SESSION['error'] = 'Invoice not found.';
            redirect(base_url('sales/invoices'));
            return;
        }

        $payments = $this->salesService->getInvoicePayments($id);

        $pageTitle      = 'Invoice #' . $invoice['invoice_number'];
        $currentPage   = 'sales';
        $currentSubPage = 'invoices';
        $viewFile      = BASE_PATH . '/views/sales/invoice_view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function edit(int $id): void
    {
        PermissionHelper::requirePermission('sales.edit');

        $invoice = $this->salesService->getInvoice($id);
        if (!$invoice) {
            $_SESSION['error'] = 'Invoice not found.';
            redirect(base_url('sales/invoices'));
            return;
        }

        if ($invoice['payment_status'] === 'paid' || $invoice['payment_status'] === 'cancelled') {
            $_SESSION['error'] = 'Cannot edit a paid or cancelled invoice.';
            redirect(base_url('sales/invoices'));
            return;
        }

        $customers = $this->salesService->getActiveCustomers($invoice['branch_id']);

        $pageTitle      = 'Edit Invoice #' . $invoice['invoice_number'];
        $currentPage   = 'sales';
        $currentSubPage = 'invoices';
        $viewFile      = BASE_PATH . '/views/sales/invoice_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function update(int $id): void
    {
        PermissionHelper::requirePermission('sales.edit');

        try {
            $data = [
                'invoice_type' => sanitize($_POST['invoice_type'] ?? 'service'),
                'items'       => $_POST['items'] ?? [],
                'discount'    => (float)($_POST['discount']    ?? 0),
                'notes'       => sanitize($_POST['notes']       ?? ''),
                'connection_date' => sanitize($_POST['connection_date'] ?? ''),
                'otc_amount' => (float)($_POST['otc_amount'] ?? 0),
            ];

            $this->salesService->updateInvoice($id, $data);
            $_SESSION['success'] = 'Invoice updated successfully.';
            redirect(base_url('sales/view/' . $id));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('sales/edit/' . $id));
        }
    }

    public function recordPayment(int $id): void
    {
        PermissionHelper::requirePermission('sales.payment');

        try {
            $amount = (float)($_POST['amount'] ?? 0);
            $method = sanitize($_POST['payment_method'] ?? 'cash');
            $reference = sanitize($_POST['reference'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');

            if ($amount <= 0) {
                $_SESSION['error'] = 'Payment amount must be greater than zero.';
                redirect(base_url('sales/view/' . $id));
                return;
            }

            $this->salesService->recordPayment($id, $amount, $method, $reference, $notes);
            $_SESSION['success'] = 'Payment recorded successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('sales/view/' . $id));
    }

    public function cancel(int $id): void
    {
        PermissionHelper::requirePermission('sales.cancel');

        try {
            $reason = sanitize($_POST['reason'] ?? '');

            $this->salesService->cancelInvoice($id, $reason);
            $_SESSION['success'] = 'Invoice cancelled successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('sales/invoices'));
    }

    public function printInvoice(int $id): void
    {
        PermissionHelper::requirePermission('sales.view');

        $invoice = $this->salesService->getInvoice($id);
        if (!$invoice) {
            $_SESSION['error'] = 'Invoice not found.';
            redirect(base_url('sales/invoices'));
            return;
        }

        $pageTitle      = 'Invoice #' . $invoice['invoice_number'];
        $currentPage   = 'sales';
        $currentSubPage = 'invoices';
        $viewFile      = BASE_PATH . '/views/sales/invoice_print.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function payments(): void
    {
        PermissionHelper::requirePermission('sales.view');

        $branchId = PermissionHelper::getBranchFilter();

        $filters = [
            'branch_id' => $branchId,
            'date_from' => sanitize($_GET['date_from'] ?? ''),
            'date_to'   => sanitize($_GET['date_to']   ?? ''),
            'page'     => max(1, (int)($_GET['page'] ?? 1)),
            'limit'    => 25,
        ];

        $result = $this->salesService->getPayments($filters);

        $pageTitle      = 'Payment Records';
        $currentPage   = 'sales';
        $currentSubPage = 'payments';
        $viewFile      = BASE_PATH . '/views/sales/payments.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function reports(): void
    {
        PermissionHelper::requirePermission('sales.reports');

        $branchId = PermissionHelper::getBranchFilter();
        $report  = $this->salesService->getSalesReport($branchId);

        $pageTitle      = 'Sales Reports';
        $currentPage   = 'sales';
        $currentSubPage = 'sales-reports';
        $viewFile      = BASE_PATH . '/views/sales/reports.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}