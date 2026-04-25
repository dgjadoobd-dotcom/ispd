<?php

/**
 * ConfigurationController — Handles Business Configuration module.
 *
 * Routes: /configuration
 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7, 17.8, 17.9, 17.10
 */
class ConfigurationController
{
    private ConfigurationService $service;

    public function __construct()
    {
        $this->service = new ConfigurationService();
    }

    public function index(): void
    {
        PermissionHelper::requirePermission('configuration.view');
        
        $zones = $this->service->getZones();
        $pops = $this->service->getPOPs();
        $packages = $this->service->getPackages();
        $settings = $this->service->getSettings();
        
        $pageTitle = 'Business Configuration';
        $currentPage = 'configuration';
        $currentSubPage = 'config-index';
        $viewFile = BASE_PATH . '/views/configuration/index.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Zones ─────────────────────────────────────────────
    public function createZone(): void
    {
        PermissionHelper::requirePermission('configuration.create');
        $pageTitle = 'Add Zone';
        $currentPage = 'configuration';
        $viewFile = BASE_PATH . '/views/configuration/zone_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeZone(): void
    {
        PermissionHelper::requirePermission('configuration.create');
        try {
            $id = $this->service->createZone([
                'name' => sanitize($_POST['name']),
                'code' => sanitize($_POST['code']),
                'description' => sanitize($_POST['description']),
            ]);
            $_SESSION['success'] = 'Zone created.';
            redirect(base_url('configuration'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('configuration/zones/create'));
        }
    }

    public function editZone(int $id): void
    {
        PermissionHelper::requirePermission('configuration.edit');
        $zone = $this->service->getZone($id);
        if (!$zone) { $_SESSION['error'] = 'Zone not found.'; redirect(base_url('configuration')); return; }
        $pageTitle = 'Edit Zone';
        $currentPage = 'configuration';
        $viewFile = BASE_PATH . '/views/configuration/zone_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updateZone(int $id): void
    {
        PermissionHelper::requirePermission('configuration.edit');
        try {
            $this->service->updateZone($id, [
                'name' => sanitize($_POST['name']),
                'code' => sanitize($_POST['code']),
                'description' => sanitize($_POST['description']),
            ]);
            $_SESSION['success'] = 'Zone updated.';
            redirect(base_url('configuration'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('configuration/zones/edit/' . $id));
        }
    }

    public function deleteZone(int $id): void
    {
        PermissionHelper::requirePermission('configuration.delete');
        try {
            $this->service->deleteZone($id);
            $_SESSION['success'] = 'Zone deleted.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        redirect(base_url('configuration'));
    }

    // ── POPs ────────────────────────────────────────────
    public function createPop(): void
    {
        PermissionHelper::requirePermission('configuration.create');
        $zones = $this->service->getZones();
        $pageTitle = 'Add POP';
        $currentPage = 'configuration';
        $viewFile = BASE_PATH . '/views/configuration/pop_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storePop(): void
    {
        PermissionHelper::requirePermission('configuration.create');
        try {
            $id = $this->service->createPop([
                'name' => sanitize($_POST['name']),
                'code' => sanitize($_POST['code']),
                'zone_id' => (int)$_POST['zone_id'],
                'ip_address' => sanitize($_POST['ip_address']),
                'location' => sanitize($_POST['location']),
                'status' => sanitize($_POST['status']) ?: 'active',
            ]);
            $_SESSION['success'] = 'POP created.';
            redirect(base_url('configuration'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('configuration/pops/create'));
        }
    }

    public function editPop(int $id): void
    {
        PermissionHelper::requirePermission('configuration.edit');
        $pop = $this->service->getPop($id);
        $zones = $this->service->getZones();
        if (!$pop) { $_SESSION['error'] = 'POP not found.'; redirect(base_url('configuration')); return; }
        $pageTitle = 'Edit POP';
        $currentPage = 'configuration';
        $viewFile = BASE_PATH . '/views/configuration/pop_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updatePop(int $id): void
    {
        PermissionHelper::requirePermission('configuration.edit');
        try {
            $this->service->updatePop($id, [
                'name' => sanitize($_POST['name']),
                'code' => sanitize($_POST['code']),
                'zone_id' => (int)$_POST['zone_id'],
                'ip_address' => sanitize($_POST['ip_address']),
                'location' => sanitize($_POST['location']),
                'status' => sanitize($_POST['status']) ?: 'active',
            ]);
            $_SESSION['success'] = 'POP updated.';
            redirect(base_url('configuration'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('configuration/pops/edit/' . $id));
        }
    }

    // ── Packages ────────────────────────────────────────
    public function createPackage(): void
    {
        PermissionHelper::requirePermission('configuration.create');
        $profiles = $this->service->getRadiusProfiles();
        $pageTitle = 'Add Package';
        $currentPage = 'configuration';
        $viewFile = BASE_PATH . '/views/configuration/package_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storePackage(): void
    {
        PermissionHelper::requirePermission('configuration.create');
        try {
            $id = $this->service->createPackage([
                'name' => sanitize($_POST['name']),
                'code' => sanitize($_POST['code']),
                'price' => (float)$_POST['price'],
                'download_speed' => (int)$_POST['download_speed'],
                'upload_speed' => (int)$_POST['upload_speed'],
                'data_limit' => (int)$_POST['data_limit'],
                'profile_id' => sanitize($_POST['profile_id']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ]);
            $_SESSION['success'] = 'Package created.';
            redirect(base_url('configuration'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('configuration/packages/create'));
        }
    }

    public function editPackage(int $id): void
    {
        PermissionHelper::requirePermission('configuration.edit');
        $package = $this->service->getPackage($id);
        $profiles = $this->service->getRadiusProfiles();
        if (!$package) { $_SESSION['error'] = 'Package not found.'; redirect(base_url('configuration')); return; }
        $pageTitle = 'Edit Package';
        $currentPage = 'configuration';
        $viewFile = BASE_PATH . '/views/configuration/package_form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updatePackage(int $id): void
    {
        PermissionHelper::requirePermission('configuration.edit');
        try {
            $this->service->updatePackage($id, [
                'name' => sanitize($_POST['name']),
                'code' => sanitize($_POST['code']),
                'price' => (float)$_POST['price'],
                'download_speed' => (int)$_POST['download_speed'],
                'upload_speed' => (int)$_POST['upload_speed'],
                'data_limit' => (int)$_POST['data_limit'],
                'profile_id' => sanitize($_POST['profile_id']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ]);
            $_SESSION['success'] = 'Package updated.';
            redirect(base_url('configuration'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('configuration/packages/edit/' . $id));
        }
    }

    // ── Settings ─────────────────────────────────────────
    public function saveSettings(): void
    {
        PermissionHelper::requirePermission('configuration.edit');
        try {
            $settings = $_POST['settings'] ?? [];
            foreach ($settings as $key => $value) {
                $this->service->updateSetting($key, $value);
            }
            $_SESSION['success'] = 'Settings saved.';
            redirect(base_url('configuration'));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('configuration'));
        }
    }
}