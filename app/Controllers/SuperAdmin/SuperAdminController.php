<?php
/**
 * SuperAdminController
 * Handles login, user management, logs, settings, branches, and NOC for the super-admin panel.
 */
class SuperAdminController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Login ─────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['superadmin', 'comadmin'], true)) {
            redirect(base_url('superadmin/dashboard'));
            return;
        }
        $pageTitle = 'Super Admin Login';
        require_once BASE_PATH . '/views/superadmin/auth/login.php';
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['sa_login_error'] = 'Username and password are required.';
            redirect(base_url('superadmin/login'));
            return;
        }

        $user = $this->db->fetchOne(
            "SELECT u.*, r.name as role_name FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.username = ? AND u.is_active = 1 LIMIT 1",
            [$username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['sa_login_error'] = 'Invalid credentials.';
            $this->db->execute(
                "INSERT INTO activity_logs (user_id, action, module, description, ip_address, created_at)
                 VALUES (0, 'sa_login_fail', 'SuperAdmin', ?, ?, datetime('now'))",
                ["Failed super-admin login attempt for: {$username}", $_SERVER['REMOTE_ADDR'] ?? '']
            );
            redirect(base_url('superadmin/login'));
            return;
        }

        $role = $user['role_name'] ?? '';
        if (!in_array($role, ['superadmin', 'comadmin'], true)) {
            $_SESSION['sa_login_error'] = 'Access denied. Super admin privileges required.';
            redirect(base_url('superadmin/login'));
            return;
        }

        // Regenerate session for security
        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_name']     = $user['full_name'] ?? $user['username'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role']     = $role;
        $_SESSION['user_email']    = $user['email'] ?? '';
        $_SESSION['branch_id']     = $user['branch_id'] ?? null;

        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, module, description, ip_address, created_at)
             VALUES (?, 'sa_login', 'SuperAdmin', 'Super admin login', ?, datetime('now'))",
            [$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']
        );

        redirect(base_url('superadmin/dashboard'));
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->db->execute(
                "INSERT INTO activity_logs (user_id, action, module, description, ip_address, created_at)
                 VALUES (?, 'sa_logout', 'SuperAdmin', 'Super admin logout', ?, datetime('now'))",
                [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']
            );
        }
        session_destroy();
        redirect(base_url('superadmin/login'));
    }

    // ── Users ─────────────────────────────────────────────────────

    public function users(): void
    {
        $pageTitle   = 'User Management';
        $currentPage = 'sa-users';

        $search = trim($_GET['search'] ?? '');
        $role   = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($role !== '') {
            $where[]  = "r.name = ?";
            $params[] = $role;
        }
        if ($status !== '') {
            $where[]  = "u.is_active = ?";
            $params[] = ($status === 'active') ? 1 : 0;
        }

        $whereStr = implode(' AND ', $where);
        $users = $this->db->fetchAll(
            "SELECT u.*, r.name as role_name, b.name as branch_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE {$whereStr}
             ORDER BY u.created_at DESC",
            $params
        );

        $roles    = $this->db->fetchAll("SELECT * FROM roles ORDER BY name");
        $branches = $this->db->fetchAll("SELECT * FROM branches WHERE is_active=1 ORDER BY name");

        $viewFile = BASE_PATH . '/views/superadmin/users/index.php';
        require_once BASE_PATH . '/views/superadmin/layouts/main.php';
    }

    public function storeUser(): void
    {
        $data = [
            'full_name'  => trim($_POST['full_name'] ?? ''),
            'username'   => trim($_POST['username'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'password'   => $_POST['password'] ?? '',
            'role_id'    => (int)($_POST['role_id'] ?? 0),
            'branch_id'  => ($_POST['branch_id'] ?? '') !== '' ? (int)$_POST['branch_id'] : null,
            'is_active'  => 1,
        ];

        if (empty($data['full_name']) || empty($data['username']) || empty($data['password'])) {
            $this->jsonError('Name, username and password are required.');
            return;
        }

        $exists = $this->db->fetchOne("SELECT id FROM users WHERE username=?", [$data['username']]);
        if ($exists) {
            $this->jsonError('Username already exists.');
            return;
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $this->db->execute(
            "INSERT INTO users (full_name, username, email, password, role_id, branch_id, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'))",
            [$data['full_name'], $data['username'], $data['email'], $data['password'], $data['role_id'], $data['branch_id']]
        );

        $this->logAction('sa_user_create', "Created user: {$data['username']}");
        $this->jsonSuccess('User created successfully.');
    }

    public function updateUser(int $id): void
    {
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'role_id'   => (int)($_POST['role_id'] ?? 0),
            'branch_id' => ($_POST['branch_id'] ?? '') !== '' ? (int)$_POST['branch_id'] : null,
        ];

        $this->db->execute(
            "UPDATE users SET full_name=?, email=?, role_id=?, branch_id=?, updated_at=datetime('now') WHERE id=?",
            [$data['full_name'], $data['email'], $data['role_id'], $data['branch_id'], $id]
        );

        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $this->db->execute("UPDATE users SET password=? WHERE id=?", [$hash, $id]);
        }

        $this->logAction('sa_user_update', "Updated user ID: {$id}");
        $this->jsonSuccess('User updated successfully.');
    }

    public function deleteUser(int $id): void
    {
        $user = $this->db->fetchOne("SELECT username FROM users WHERE id=?", [$id]);
        if (!$user) { $this->jsonError('User not found.'); return; }

        // Prevent deleting own account
        if ((int)$_SESSION['user_id'] === $id) {
            $this->jsonError('You cannot delete your own account.');
            return;
        }

        $this->db->execute("DELETE FROM users WHERE id=?", [$id]);
        $this->logAction('sa_user_delete', "Deleted user: {$user['username']}");
        $this->jsonSuccess('User deleted.');
    }

    public function toggleUser(int $id): void
    {
        if ((int)$_SESSION['user_id'] === $id) {
            $this->jsonError('You cannot disable your own account.');
            return;
        }
        $this->db->execute(
            "UPDATE users SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?",
            [$id]
        );
        $this->logAction('sa_user_toggle', "Toggled active status for user ID: {$id}");
        $this->jsonSuccess('User status updated.');
    }

    public function resetPassword(int $id): void
    {
        $newPass = $_POST['password'] ?? '';
        if (strlen($newPass) < 6) {
            $this->jsonError('Password must be at least 6 characters.');
            return;
        }
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $this->db->execute("UPDATE users SET password=? WHERE id=?", [$hash, $id]);
        $this->logAction('sa_password_reset', "Reset password for user ID: {$id}");
        $this->jsonSuccess('Password reset successfully.');
    }

    // ── Activity Logs ─────────────────────────────────────────────

    public function logs(): void
    {
        $pageTitle   = 'Activity Logs';
        $currentPage = 'sa-logs';

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $search = trim($_GET['search'] ?? '');
        $action = $_GET['action'] ?? '';
        $userId = (int)($_GET['user_id'] ?? 0);

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = "(al.description LIKE ? OR u.username LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($action !== '') {
            $where[]  = "al.action = ?";
            $params[] = $action;
        }
        if ($userId > 0) {
            $where[]  = "al.user_id = ?";
            $params[] = $userId;
        }

        $whereStr = implode(' AND ', $where);

        $total = (int)($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM activity_logs al LEFT JOIN users u ON u.id=al.user_id WHERE {$whereStr}",
            $params
        )['c'] ?? 0);

        $logs = $this->db->fetchAll(
            "SELECT al.*, u.full_name, u.username FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE {$whereStr}
             ORDER BY al.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $actions  = $this->db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");
        $allUsers = $this->db->fetchAll("SELECT id, full_name, username FROM users ORDER BY full_name");
        $pages    = (int)ceil($total / $perPage);

        $viewFile = BASE_PATH . '/views/superadmin/logs/index.php';
        require_once BASE_PATH . '/views/superadmin/layouts/main.php';
    }

    public function clearLogs(): void
    {
        $days = (int)($_POST['days'] ?? 30);
        if ($days < 1) { $this->jsonError('Invalid days value.'); return; }

        $this->db->execute(
            "DELETE FROM activity_logs WHERE created_at < datetime('now', '-{$days} days')"
        );
        $this->logAction('sa_logs_clear', "Cleared activity logs older than {$days} days");
        $this->jsonSuccess("Logs older than {$days} days cleared.");
    }

    // ── Settings ──────────────────────────────────────────────────

    public function settings(): void
    {
        $pageTitle   = 'System Settings';
        $currentPage = 'sa-settings';

        $config = [];
        $rows   = $this->db->fetchAll("SELECT key, value FROM settings");
        foreach ($rows as $row) {
            $config[$row['key']] = $row['value'];
        }

        $viewFile = BASE_PATH . '/views/superadmin/settings/index.php';
        require_once BASE_PATH . '/views/superadmin/layouts/main.php';
    }

    public function saveSettings(): void
    {
        $allowed = [
            'app_name', 'app_url', 'timezone', 'currency', 'currency_symbol',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from',
            'sms_api_key', 'sms_sender_id', 'sms_gateway',
            'maintenance_mode', 'registration_open', 'max_login_attempts',
        ];

        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                $exists = $this->db->fetchOne("SELECT id FROM settings WHERE key=?", [$key]);
                if ($exists) {
                    $this->db->execute("UPDATE settings SET value=? WHERE key=?", [$val, $key]);
                } else {
                    $this->db->execute("INSERT INTO settings (key, value) VALUES (?, ?)", [$key, $val]);
                }
            }
        }

        $this->logAction('sa_settings_save', 'Updated system settings');
        $this->jsonSuccess('Settings saved successfully.');
    }

    // ── Branches Overview ─────────────────────────────────────────

    public function branches(): void
    {
        $pageTitle   = 'Branches Overview';
        $currentPage = 'sa-branches';

        $branches = $this->db->fetchAll(
            "SELECT b.*,
                    COUNT(DISTINCT c.id) as total_customers,
                    COUNT(DISTINCT CASE WHEN c.status='active' THEN c.id END) as active_customers,
                    COUNT(DISTINCT u.id) as total_users,
                    COALESCE(SUM(p.amount),0) as total_revenue,
                    COALESCE(SUM(CASE WHEN strftime('%Y-%m',p.payment_date)=strftime('%Y-%m','now') THEN p.amount END),0) as month_revenue
             FROM branches b
             LEFT JOIN customers c ON c.branch_id = b.id
             LEFT JOIN users u ON u.branch_id = b.id AND u.is_active = 1
             LEFT JOIN payments p ON p.branch_id = b.id
             GROUP BY b.id
             ORDER BY b.name"
        );

        $viewFile = BASE_PATH . '/views/superadmin/branches/index.php';
        require_once BASE_PATH . '/views/superadmin/layouts/main.php';
    }

    // ── NOC / System Health ───────────────────────────────────────

    public function noc(): void
    {
        $pageTitle   = 'Network Operations Center';
        $currentPage = 'sa-noc';

        $nasDevices = $this->db->fetchAll(
            "SELECT * FROM nas_devices WHERE is_active=1 ORDER BY connection_status DESC, name"
        );

        $recentAlerts = $this->db->fetchAll(
            "SELECT * FROM radius_alerts ORDER BY created_at DESC LIMIT 20"
        );

        $diskFree  = disk_free_space(BASE_PATH);
        $diskTotal = disk_total_space(BASE_PATH);
        $diskPct   = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0;

        $logSize = 0;
        $logDir  = BASE_PATH . '/storage/logs';
        if (is_dir($logDir)) {
            foreach (glob($logDir . '/*.log') as $f) {
                $logSize += filesize($f);
            }
        }

        $systemInfo = [
            'php_version'   => PHP_VERSION,
            'disk_free_gb'  => round($diskFree / 1073741824, 2),
            'disk_total_gb' => round($diskTotal / 1073741824, 2),
            'disk_pct'      => $diskPct,
            'log_size_mb'   => round($logSize / 1048576, 2),
            'memory_limit'  => ini_get('memory_limit'),
            'max_execution' => ini_get('max_execution_time'),
            'upload_max'    => ini_get('upload_max_filesize'),
            'os'            => PHP_OS_FAMILY,
        ];

        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $secs = (int)explode(' ', $uptime)[0];
                $systemInfo['uptime'] = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
            }
            $load = sys_getloadavg();
            $systemInfo['load_avg'] = implode(', ', array_map(fn($v) => round($v, 2), $load ?: [0, 0, 0]));
        }

        $viewFile = BASE_PATH . '/views/superadmin/noc/index.php';
        require_once BASE_PATH . '/views/superadmin/layouts/main.php';
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function logAction(string $action, string $description): void
    {
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, module, description, ip_address, created_at)
             VALUES (?, ?, 'SuperAdmin', ?, ?, datetime('now'))",
            [$_SESSION['user_id'] ?? 0, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']
        );
    }

    private function jsonSuccess(string $message, array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true, 'message' => $message], $extra));
        exit;
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
