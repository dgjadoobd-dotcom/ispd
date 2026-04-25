<?php

/**
 * PurchaseController — Handles Purchase Management module HTTP requests.
 *
 * Routes are prefixed with /purchase.
 * Delegates business logic to PurchaseService.
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8
 */
class PurchaseController
{
    private PurchaseService $purchaseService;

    public function __construct()
    {
        $this->purchaseService = new PurchaseService();
    }

    public function index(): void
    {
        redirect(base_url('purchase/vendors'));
    }

    public function vendors(): void
    {
        PermissionHelper::requirePermission('purchase.view');

        $filters = [
            'search' => sanitize($_GET['search'] ?? ''),
            'page'  => max(1, (int)($_GET['page'] ?? 1)),
            'limit' => 25,
        ];

        $result = $this->purchaseService->getVendors($filters);

        $pageTitle      = 'Vendors';
        $currentPage   = 'purchase';
        $currentSubPage = 'vendors';
        $viewFile     = BASE_PATH . '/views/purchase/vendors.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function createVendor(): void
    {
        PermissionHelper::requirePermission('purchase.create');

        $pageTitle      = 'Add Vendor';
        $currentPage   = 'purchase';
        $currentSubPage = 'vendors';
        $viewFile     = BASE_PATH . '/views/purchase/vendor_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeVendor(): void
    {
        PermissionHelper::requirePermission('purchase.create');

        try {
            $data = [
                'name'       => sanitize($_POST['name'] ?? ''),
                'contact_person' => sanitize($_POST['contact_person'] ?? ''),
                'phone'      => sanitize($_POST['phone'] ?? ''),
                'email'     => sanitize($_POST['email'] ?? ''),
                'address'    => sanitize($_POST['address'] ?? ''),
                'branch_id'  => PermissionHelper::getBranchFilter(),
                'created_by' => $_SESSION['user_id'] ?? null,
            ];

            if (empty($data['name'])) {
                $_SESSION['error'] = 'Vendor name is required.';
                redirect(base_url('purchase/vendors/create'));
                return;
            }

            $vendorId = $this->purchaseService->createVendor($data);
            $_SESSION['success'] = 'Vendor added successfully.';
            redirect(base_url('purchase/vendors'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('purchase/vendors/create'));
        }
    }

    public function editVendor(int $id): void
    {
        PermissionHelper::requirePermission('purchase.edit');

        $vendor = $this->purchaseService->getVendor($id);
        if (!$vendor) {
            $_SESSION['error'] = 'Vendor not found.';
            redirect(base_url('purchase/vendors'));
            return;
        }

        $pageTitle      = 'Edit Vendor';
        $currentPage   = 'purchase';
        $currentSubPage = 'vendors';
        $viewFile     = BASE_PATH . '/views/purchase/vendor_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updateVendor(int $id): void
    {
        PermissionHelper::requirePermission('purchase.edit');

        try {
            $data = [
                'name'        => sanitize($_POST['name'] ?? ''),
                'contact_person' => sanitize($_POST['contact_person'] ?? ''),
                'phone'      => sanitize($_POST['phone'] ?? ''),
                'email'     => sanitize($_POST['email'] ?? ''),
                'address'   => sanitize($_POST['address'] ?? ''),
            ];

            if (empty($data['name'])) {
                $_SESSION['error'] = 'Vendor name is required.';
                redirect(base_url('purchase/vendors/edit/' . $id));
                return;
            }

            $this->purchaseService->updateVendor($id, $data);
            $_SESSION['success'] = 'Vendor updated successfully.';
            redirect(base_url('purchase/vendors'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('purchase/vendors/edit/' . $id));
        }
    }

    public function bills(): void
    {
        PermissionHelper::requirePermission('purchase.view');

        $branchId = PermissionHelper::getBranchFilter();

        $filters = [
            'vendor_id' => (int)($_GET['vendor_id'] ?? 0) ?: null,
            'status'  => sanitize($_GET['status'] ?? ''),
            'branch_id' => $branchId,
            'search'  => sanitize($_GET['search'] ?? ''),
            'page'   => max(1, (int)($_GET['page'] ?? 1)),
            'limit'  => 25,
        ];

        $result = $this->purchaseService->getBills($filters);
        $vendors = $this->purchaseService->getActiveVendors($branchId);

        $pageTitle      = 'Purchase Bills';
        $currentPage   = 'purchase';
        $currentSubPage = 'bills';
        $viewFile     = BASE_PATH . '/views/purchase/bills.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function createBill(): void
    {
        PermissionHelper::requirePermission('purchase.create');

        $branchId   = PermissionHelper::getBranchFilter();
        $vendors   = $this->purchaseService->getActiveVendors($branchId);

        $pageTitle      = 'Create Purchase Bill';
        $currentPage   = 'purchase';
        $currentSubPage = 'bill-create';
        $viewFile     = BASE_PATH . '/views/purchase/bill_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeBill(): void
    {
        PermissionHelper::requirePermission('purchase.create');

        try {
            $branchId = PermissionHelper::getBranchFilter()
                ?? (int)($_POST['branch_id'] ?? 0) ?: null;

            $data = [
                'vendor_id'  => (int)($_POST['vendor_id'] ?? 0) ?: null,
                'branch_id'  => $branchId,
                'items'     => $_POST['items'] ?? [],
                'discount'  => (float)($_POST['discount'] ?? 0),
                'notes'     => sanitize($_POST['notes'] ?? ''),
                'due_date' => sanitize($_POST['due_date'] ?? ''),
                'created_by' => $_SESSION['user_id'] ?? null,
            ];

            if (empty($data['vendor_id'])) {
                $_SESSION['error'] = 'Vendor is required.';
                redirect(base_url('purchase/bills/create'));
                return;
            }

            if (empty($data['items'])) {
                $_SESSION['error'] = 'At least one line item is required.';
                redirect(base_url('purchase/bills/create'));
                return;
            }

            $billId = $this->purchaseService->createBill($data);

            $_SESSION['success'] = 'Purchase bill created successfully.';
            redirect(base_url('purchase/bills/view/' . $billId));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('purchase/bills/create'));
        }
    }

    public function viewBill(int $id): void
    {
        PermissionHelper::requirePermission('purchase.view');

        $bill = $this->purchaseService->getBill($id);
        if (!$bill) {
            $_SESSION['error'] = 'Bill not found.';
            redirect(base_url('purchase/bills'));
            return;
        }

        $payments = $this->purchaseService->getBillPayments($id);

        $pageTitle      = 'Bill #' . $bill['bill_number'];
        $currentPage   = 'purchase';
        $currentSubPage = 'bills';
        $viewFile     = BASE_PATH . '/views/purchase/bill_view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function recordPayment(int $id): void
    {
        PermissionHelper::requirePermission('purchase.payment');

        try {
            $amount = (float)($_POST['amount'] ?? 0);
            $method = sanitize($_POST['payment_method'] ?? 'cash');
            $reference = sanitize($_POST['reference'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');

            if ($amount <= 0) {
                $_SESSION['error'] = 'Payment amount must be greater than zero.';
                redirect(base_url('purchase/bills/view/' . $id));
                return;
            }

            $this->purchaseService->recordPayment($id, $amount, $method, $reference, $notes);
            $_SESSION['success'] = 'Payment recorded successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('purchase/bills/view/' . $id));
    }

    public function ledger(): void
    {
        PermissionHelper::requirePermission('purchase.view');

        $vendorId = (int)($_GET['vendor_id'] ?? 0) ?: null;

        if ($vendorId) {
            $ledger = $this->purchaseService->getVendorLedger($vendorId);
        } else {
            $ledger = ['bills' => [], 'payments' => [], 'summary' => []];
        }

        $vendors = $this->purchaseService->getActiveVendors();

        $pageTitle      = 'Vendor Ledger';
        $currentPage   = 'purchase';
        $currentSubPage = 'ledger';
        $viewFile     = BASE_PATH . '/views/purchase/ledger.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function reports(): void
    {
        PermissionHelper::requirePermission('purchase.reports');

        $branchId = PermissionHelper::getBranchFilter();
        $report = $this->purchaseService->getPurchaseReport($branchId);

        $pageTitle      = 'Purchase Reports';
        $currentPage   = 'purchase';
        $currentSubPage = 'purchase-reports';
        $viewFile     = BASE_PATH . '/views/purchase/reports.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}