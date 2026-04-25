<?php

/**
 * OttController — Handles all OTT Subscription Management module HTTP requests.
 *
 * Routes are prefixed with /ott.
 * Delegates business logic to OttService.
 *
 * Requirements: 16.1–16.8
 */
class OttController
{
    private OttService $service;

    public function __construct()
    {
        $this->service = new OttService();
    }

    // ── Dashboard ─────────────────────────────────────────────────

    /**
     * OTT subscriber dashboard.
     * Req 16.6: active, expired, pending renewal subscriptions.
     */
    public function index(): void
    {
        PermissionHelper::requirePermission('ott.view');

        $stats        = $this->service->getDashboardStats();
        $expiringSoon = $this->service->getExpiringSoon(3);
        $recentLogs   = $this->service->getAllRenewalLogs(20);

        $pageTitle      = 'OTT Subscriptions';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-dashboard';
        $viewFile       = BASE_PATH . '/views/ott/dashboard.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Providers ─────────────────────────────────────────────────

    /**
     * List all OTT providers.
     * Req 16.1
     */
    public function providers(): void
    {
        PermissionHelper::requirePermission('ott.view');

        $providers = $this->service->getProviders();

        $pageTitle      = 'OTT Providers';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-providers';
        $viewFile       = BASE_PATH . '/views/ott/providers.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Show create provider form.
     */
    public function createProvider(): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $pageTitle      = 'Add OTT Provider';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-providers';
        $viewFile       = BASE_PATH . '/views/ott/provider-form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save a new OTT provider (POST).
     */
    public function storeProvider(): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $data = [
            'name'         => sanitize($_POST['name'] ?? ''),
            'logo_url'     => sanitize($_POST['logo_url'] ?? ''),
            'api_endpoint' => sanitize($_POST['api_endpoint'] ?? ''),
            'api_key'      => sanitize($_POST['api_key'] ?? ''),
            'plan_types'   => sanitize($_POST['plan_types'] ?? ''),
            'notes'        => sanitize($_POST['notes'] ?? ''),
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Provider name is required.';
            redirect(base_url('ott/providers/create'));
            return;
        }

        try {
            $this->service->createProvider($data);
            $_SESSION['success'] = 'OTT provider created successfully.';
            redirect(base_url('ott/providers'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to create provider: ' . $e->getMessage();
            redirect(base_url('ott/providers/create'));
        }
    }

    /**
     * Show edit provider form.
     */
    public function editProvider(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $provider = $this->service->getProvider($id);
        if (!$provider) {
            $_SESSION['error'] = 'OTT provider not found.';
            redirect(base_url('ott/providers'));
            return;
        }

        $pageTitle      = 'Edit OTT Provider';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-providers';
        $viewFile       = BASE_PATH . '/views/ott/provider-form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Update an OTT provider (POST).
     */
    public function updateProvider(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $data = [
            'name'         => sanitize($_POST['name'] ?? ''),
            'logo_url'     => sanitize($_POST['logo_url'] ?? ''),
            'api_endpoint' => sanitize($_POST['api_endpoint'] ?? ''),
            'api_key'      => sanitize($_POST['api_key'] ?? ''),
            'plan_types'   => sanitize($_POST['plan_types'] ?? ''),
            'is_active'    => (int)($_POST['is_active'] ?? 1),
            'notes'        => sanitize($_POST['notes'] ?? ''),
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Provider name is required.';
            redirect(base_url('ott/providers/edit/' . $id));
            return;
        }

        try {
            $this->service->updateProvider($id, $data);
            $_SESSION['success'] = 'OTT provider updated successfully.';
            redirect(base_url('ott/providers'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to update provider: ' . $e->getMessage();
            redirect(base_url('ott/providers/edit/' . $id));
        }
    }

    /**
     * Toggle provider active status (POST).
     */
    public function toggleProvider(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        try {
            $this->service->toggleProvider($id);
            $_SESSION['success'] = 'Provider status updated.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('ott/providers'));
    }

    // ── Packages ──────────────────────────────────────────────────

    /**
     * List all OTT packages.
     * Req 16.2
     */
    public function packages(): void
    {
        PermissionHelper::requirePermission('ott.view');

        $providerId = !empty($_GET['provider_id']) ? (int)$_GET['provider_id'] : null;
        $packages   = $this->service->getPackages($providerId);
        $providers  = $this->service->getProviders(true);
        $db         = Database::getInstance();
        $internetPackages = $db->fetchAll("SELECT id, name FROM packages WHERE is_active = 1 ORDER BY name ASC", []);

        $pageTitle      = 'OTT Packages';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-packages';
        $viewFile       = BASE_PATH . '/views/ott/packages.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Show create package form.
     */
    public function createPackage(): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $providers        = $this->service->getProviders(true);
        $db               = Database::getInstance();
        $internetPackages = $db->fetchAll("SELECT id, name FROM packages WHERE is_active = 1 ORDER BY name ASC", []);

        $pageTitle      = 'Add OTT Package';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-packages';
        $viewFile       = BASE_PATH . '/views/ott/package-form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save a new OTT package (POST).
     */
    public function storePackage(): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $data = [
            'provider_id'   => (int)($_POST['provider_id'] ?? 0),
            'package_id'    => !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null,
            'name'          => sanitize($_POST['name'] ?? ''),
            'description'   => sanitize($_POST['description'] ?? ''),
            'price'         => (float)($_POST['price'] ?? 0),
            'validity_days' => (int)($_POST['validity_days'] ?? 30),
            'auto_renewal'  => (int)($_POST['auto_renewal'] ?? 1),
        ];

        if (!$data['provider_id']) {
            $_SESSION['error'] = 'Please select a provider.';
            redirect(base_url('ott/packages/create'));
            return;
        }

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Package name is required.';
            redirect(base_url('ott/packages/create'));
            return;
        }

        if ($data['validity_days'] < 1) {
            $_SESSION['error'] = 'Validity days must be at least 1.';
            redirect(base_url('ott/packages/create'));
            return;
        }

        try {
            $this->service->createPackage($data);
            $_SESSION['success'] = 'OTT package created successfully.';
            redirect(base_url('ott/packages'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to create package: ' . $e->getMessage();
            redirect(base_url('ott/packages/create'));
        }
    }

    /**
     * Show edit package form.
     */
    public function editPackage(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $package = $this->service->getPackage($id);
        if (!$package) {
            $_SESSION['error'] = 'OTT package not found.';
            redirect(base_url('ott/packages'));
            return;
        }

        $providers        = $this->service->getProviders(true);
        $db               = Database::getInstance();
        $internetPackages = $db->fetchAll("SELECT id, name FROM packages WHERE is_active = 1 ORDER BY name ASC", []);

        $pageTitle      = 'Edit OTT Package';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-packages';
        $viewFile       = BASE_PATH . '/views/ott/package-form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Update an OTT package (POST).
     */
    public function updatePackage(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $data = [
            'provider_id'   => (int)($_POST['provider_id'] ?? 0),
            'package_id'    => !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null,
            'name'          => sanitize($_POST['name'] ?? ''),
            'description'   => sanitize($_POST['description'] ?? ''),
            'price'         => (float)($_POST['price'] ?? 0),
            'validity_days' => (int)($_POST['validity_days'] ?? 30),
            'auto_renewal'  => (int)($_POST['auto_renewal'] ?? 1),
            'is_active'     => (int)($_POST['is_active'] ?? 1),
        ];

        if (!$data['provider_id']) {
            $_SESSION['error'] = 'Please select a provider.';
            redirect(base_url('ott/packages/edit/' . $id));
            return;
        }

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Package name is required.';
            redirect(base_url('ott/packages/edit/' . $id));
            return;
        }

        try {
            $this->service->updatePackage($id, $data);
            $_SESSION['success'] = 'OTT package updated successfully.';
            redirect(base_url('ott/packages'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to update package: ' . $e->getMessage();
            redirect(base_url('ott/packages/edit/' . $id));
        }
    }

    /**
     * Delete an OTT package (POST).
     */
    public function deletePackage(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        try {
            $this->service->deletePackage($id);
            $_SESSION['success'] = 'OTT package deleted.';
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to delete package: ' . $e->getMessage();
        }

        redirect(base_url('ott/packages'));
    }

    // ── Subscriptions ─────────────────────────────────────────────

    /**
     * List all subscriptions with filters.
     * Req 16.6: dashboard showing active, expired, pending renewal.
     */
    public function subscriptions(): void
    {
        PermissionHelper::requirePermission('ott.view');

        $filters = [
            'status'      => sanitize($_GET['status'] ?? ''),
            'provider_id' => !empty($_GET['provider_id']) ? (int)$_GET['provider_id'] : null,
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $result  = $this->service->getSubscriptions(array_filter($filters), $page, 25);

        $providers = $this->service->getProviders(true);

        $pageTitle      = 'OTT Subscriptions';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-subscriptions';
        $viewFile       = BASE_PATH . '/views/ott/subscriptions.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * View a single subscription with renewal log.
     */
    public function viewSubscription(int $id): void
    {
        PermissionHelper::requirePermission('ott.view');

        $subscription = $this->service->getSubscription($id);
        if (!$subscription) {
            $_SESSION['error'] = 'Subscription not found.';
            redirect(base_url('ott/subscriptions'));
            return;
        }

        $logs = $this->service->getRenewalLogs($id);

        $pageTitle      = 'OTT Subscription #' . $id;
        $currentPage    = 'ott';
        $currentSubPage = 'ott-subscriptions';
        $viewFile       = BASE_PATH . '/views/ott/subscription-view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Show create subscription form.
     */
    public function createSubscription(): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $db        = Database::getInstance();
        $customers = $db->fetchAll(
            "SELECT id, full_name, customer_code, phone FROM customers WHERE status = 'active' ORDER BY full_name ASC",
            []
        );
        $packages  = $this->service->getPackages(null, true);

        $pageTitle      = 'Add OTT Subscription';
        $currentPage    = 'ott';
        $currentSubPage = 'ott-subscriptions';
        $viewFile       = BASE_PATH . '/views/ott/subscription-form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Save a new subscription (POST).
     * Req 16.3: create subscription with start date, expiry, status = active.
     */
    public function storeSubscription(): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $customerId    = (int)($_POST['customer_id'] ?? 0);
        $ottPackageId  = (int)($_POST['ott_package_id'] ?? 0);
        $startDate     = sanitize($_POST['start_date'] ?? date('Y-m-d'));

        if (!$customerId || !$ottPackageId) {
            $_SESSION['error'] = 'Customer and OTT package are required.';
            redirect(base_url('ott/subscriptions/create'));
            return;
        }

        try {
            $id = $this->service->createSubscription($customerId, $ottPackageId, $startDate);
            $_SESSION['success'] = 'OTT subscription created successfully.';
            redirect(base_url('ott/subscriptions/view/' . $id));
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('ott/subscriptions/create'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to create subscription: ' . $e->getMessage();
            redirect(base_url('ott/subscriptions/create'));
        }
    }

    /**
     * Manually activate a subscription (POST).
     * Req 16.7
     */
    public function activateSubscription(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        try {
            $this->service->activateSubscription($id);
            $_SESSION['success'] = 'Subscription activated successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to activate subscription: ' . $e->getMessage();
        }

        redirect(base_url('ott/subscriptions/view/' . $id));
    }

    /**
     * Manually deactivate a subscription (POST).
     * Req 16.7, 16.8: record reason and timestamp.
     */
    public function deactivateSubscription(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $reason = sanitize($_POST['reason'] ?? '');

        try {
            $this->service->deactivateSubscription($id, $reason);
            $_SESSION['success'] = 'Subscription deactivated.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to deactivate subscription: ' . $e->getMessage();
        }

        redirect(base_url('ott/subscriptions/view/' . $id));
    }

    /**
     * Manually trigger renewal for a subscription (POST).
     * Req 16.4
     */
    public function renewSubscription(int $id): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $success = $this->service->renewSubscription($id);

        if ($success) {
            $_SESSION['success'] = 'Subscription renewed successfully.';
        } else {
            $_SESSION['error'] = 'Renewal failed. Subscription has been marked as expired and the customer has been notified.';
        }

        redirect(base_url('ott/subscriptions/view/' . $id));
    }

    /**
     * Process all auto-renewals (POST — typically called by cron or admin).
     * Req 16.4
     */
    public function processRenewals(): void
    {
        PermissionHelper::requirePermission('ott.manage');

        $result = $this->service->processAutoRenewals();
        $_SESSION['success'] = "Auto-renewal complete: {$result['renewed']} renewed, {$result['failed']} failed.";
        redirect(base_url('ott'));
    }
}
