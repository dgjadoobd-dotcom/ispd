<?php

/**
 * BandwidthController — Handles Bandwidth Purchase & Sales module.
 *
 * Routes are prefixed with /bandwidth.
 * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8
 */
class BandwidthController
{
    private BandwidthService $service;

    public function __construct()
    {
        $this->service = new BandwidthService();
    }

    public function index(): void
    {
        redirect(base_url('bandwidth/providers'));
    }

    // ── Providers ─────────────────────────────────────
    public function providers(): void
    {
        PermissionHelper::requirePermission('bandwidth.view');
        $result = $this->service->getProviders(['page' => max(1, (int)($_GET['page'] ?? 1)), 'limit' => 25]);
        $pageTitle = 'Bandwidth Providers';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-providers';
        $viewFile = BASE_PATH . '/views/bandwidth/providers.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function createProvider(): void
    {
        PermissionHelper::requirePermission('bandwidth.create');
        $pageTitle = 'Add Provider';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-providers';
        $viewFile = BASE_PATH . '/views/bandwidth/provider_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeProvider(): void
    {
        PermissionHelper::requirePermission('bandwidth.create');
        try {
            $id = $this->service->createProvider([
                'name' => sanitize($_POST['name']),
                'contact_person' => sanitize($_POST['contact_person']),
                'phone' => sanitize($_POST['phone']),
                'email' => sanitize($_POST['email']),
                'address' => sanitize($_POST['address']),
                'bandwidth_capacity' => (float)$_POST['bandwidth_capacity'],
                'price_per_mbps' => (float)$_POST['price_per_mbps'],
                'created_by' => $_SESSION['user_id'],
            ]);
            $_SESSION['success'] = 'Provider added.';
            redirect(base_url('bandwidth/providers'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('bandwidth/providers/create'));
        }
    }

    public function editProvider(int $id): void
    {
        PermissionHelper::requirePermission('bandwidth.edit');
        $provider = $this->service->getProvider($id);
        if (!$provider) { $_SESSION['error'] = 'Not found.'; redirect(base_url('bandwidth/providers')); return; }
        $pageTitle = 'Edit Provider';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-providers';
        $viewFile = BASE_PATH . '/views/bandwidth/provider_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updateProvider(int $id): void
    {
        PermissionHelper::requirePermission('bandwidth.edit');
        try {
            $this->service->updateProvider($id, [
                'name' => sanitize($_POST['name']),
                'contact_person' => sanitize($_POST['contact_person']),
                'phone' => sanitize($_POST['phone']),
                'email' => sanitize($_POST['email']),
                'address' => sanitize($_POST['address']),
                'bandwidth_capacity' => (float)$_POST['bandwidth_capacity'],
                'price_per_mbps' => (float)$_POST['price_per_mbps'],
            ]);
            $_SESSION['success'] = 'Provider updated.';
            redirect(base_url('bandwidth/providers'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('bandwidth/providers/edit/' . $id));
        }
    }

    // ── Resellers ────────────────────────────────────
    public function resellers(): void
    {
        PermissionHelper::requirePermission('bandwidth.view');
        $result = $this->service->getResellers(['page' => max(1, (int)($_GET['page'] ?? 1)), 'limit' => 25]);
        $providers = $this->service->getActiveProviders();
        $pageTitle = 'Resellers';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-resellers';
        $viewFile = BASE_PATH . '/views/bandwidth/resellers.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function createReseller(): void
    {
        PermissionHelper::requirePermission('bandwidth.create');
        $providers = $this->service->getActiveProviders();
        $pageTitle = 'Add Reseller';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-resellers';
        $viewFile = BASE_PATH . '/views/bandwidth/reseller_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeReseller(): void
    {
        PermissionHelper::requirePermission('bandwidth.create');
        try {
            $id = $this->service->createReseller([
                'provider_id' => (int)$_POST['provider_id'],
                'name' => sanitize($_POST['name']),
                'contact_person' => sanitize($_POST['contact_person']),
                'phone' => sanitize($_POST['phone']),
                'email' => sanitize($_POST['email']),
                'address' => sanitize($_POST['address']),
                'credit_limit' => (float)$_POST['credit_limit'],
                'price_per_mbps' => (float)$_POST['price_per_mbps'],
                'created_by' => $_SESSION['user_id'],
            ]);
            $_SESSION['success'] = 'Reseller added.';
            redirect(base_url('bandwidth/resellers'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('bandwidth/resellers/create'));
        }
    }

    public function editReseller(int $id): void
    {
        PermissionHelper::requirePermission('bandwidth.edit');
        $reseller = $this->service->getReseller($id);
        $providers = $this->service->getActiveProviders();
        if (!$reseller) { $_SESSION['error'] = 'Not found.'; redirect(base_url('bandwidth/resellers')); return; }
        $pageTitle = 'Edit Reseller';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-resellers';
        $viewFile = BASE_PATH . '/views/bandwidth/reseller_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updateReseller(int $id): void
    {
        PermissionHelper::requirePermission('bandwidth.edit');
        try {
            $this->service->updateReseller($id, [
                'provider_id' => (int)$_POST['provider_id'],
                'name' => sanitize($_POST['name']),
                'contact_person' => sanitize($_POST['contact_person']),
                'phone' => sanitize($_POST['phone']),
                'email' => sanitize($_POST['email']),
                'address' => sanitize($_POST['address']),
                'credit_limit' => (float)$_POST['credit_limit'],
                'price_per_mbps' => (float)$_POST['price_per_mbps'],
            ]);
            $_SESSION['success'] = 'Reseller updated.';
            redirect(base_url('bandwidth/resellers'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('bandwidth/resellers/edit/' . $id));
        }
    }

    // ── Purchases ────────────────────────────────────
    public function purchases(): void
    {
        PermissionHelper::requirePermission('bandwidth.view');
        $result = $this->service->getPurchases(['page' => max(1, (int)($_GET['page'] ?? 1)), 'limit' => 25]);
        $providers = $this->service->getActiveProviders();
        $pageTitle = 'Bandwidth Purchases';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-purchases';
        $viewFile = BASE_PATH . '/views/bandwidth/purchases.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storePurchase(): void
    {
        PermissionHelper::requirePermission('bandwidth.create');
        try {
            $id = $this->service->createPurchase([
                'provider_id' => (int)$_POST['provider_id'],
                'mbps_quantity' => (float)$_POST['mbps_quantity'],
                'price_per_mbps' => (float)$_POST['price_per_mbps'],
                'total_amount' => (float)$_POST['total_amount'],
                'bill_number' => sanitize($_POST['bill_number']),
                'due_date' => sanitize($_POST['due_date']),
                'notes' => sanitize($_POST['notes']),
                'created_by' => $_SESSION['user_id'],
            ]);
            $_SESSION['success'] = 'Purchase recorded.';
            redirect(base_url('bandwidth/purchases'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('bandwidth/purchases'));
        }
    }

    // ── Invoices ─────────────────────────────────────
    public function invoices(): void
    {
        PermissionHelper::requirePermission('bandwidth.view');
        $result = $this->service->getInvoices(['page' => max(1, (int)($_GET['page'] ?? 1)), 'limit' => 25]);
        $pageTitle = 'Reseller Invoices';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-invoices';
        $viewFile = BASE_PATH . '/views/bandwidth/invoices.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function viewInvoice(int $id): void
    {
        PermissionHelper::requirePermission('bandwidth.view');
        $invoice = $this->service->getInvoice($id);
        if (!$invoice) { $_SESSION['error'] = 'Not found.'; redirect(base_url('bandwidth/invoices')); return; }
        $pageTitle = 'Invoice #' . $invoice['invoice_number'];
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-invoices';
        $viewFile = BASE_PATH . '/views/bandwidth/invoice_view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Dashboard/Reports ──────────────────────────────
    public function dashboard(): void
    {
        PermissionHelper::requirePermission('bandwidth.reports');
        $report = $this->service->getReport();
        $pageTitle = 'Bandwidth Dashboard';
        $currentPage = 'bandwidth';
        $currentSubPage = 'bw-dashboard';
        $viewFile = BASE_PATH . '/views/bandwidth/dashboard.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}