<?php

class AutomationController {
    private Database $db;
    private AutomationService $automation;

    public function __construct() {
        $this->db = Database::getInstance();
        require_once BASE_PATH . '/app/Services/AutomationService.php';
        $this->automation = new AutomationService();
    }

    public function index(): void {
        $pageTitle   = 'Automation';
        $currentPage = 'automation';
        $currentSubPage = 'auto-dashboard';

        $stats = $this->automation->getStats();
        $logs  = $this->automation->getRecentLogs(15);

        // Load automation settings
        $settingsRaw = $this->db->fetchAll("SELECT `key`,`value` FROM settings WHERE `key` LIKE 'auto_%'");
        $settings = [];
        foreach ($settingsRaw as $s) { $settings[$s['key']] = $s['value']; }

        $viewFile = BASE_PATH . '/views/automation/index.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function run(string $job): void {
        $result = match($job) {
            'invoices'    => $this->automation->generateMonthlyInvoices(),
            'suspend'     => $this->automation->suspendOverdue((int)($_POST['grace_days'] ?? 0)),
            'reconnect'   => $this->automation->reconnectPaidCustomers(),
            'due-reminders'    => $this->automation->sendDueReminders((int)($_POST['days_ahead'] ?? 3)),
            'expiry-reminders' => $this->automation->sendExpiryReminders((int)($_POST['days_ahead'] ?? 5)),
            default       => ['success' => false, 'message' => 'Unknown job'],
        };

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        redirect(base_url('automation'));
    }

    public function saveSettings(): void {
        $fields = [
            'auto_invoice_enabled', 'auto_invoice_day',
            'auto_suspend_enabled', 'auto_suspend_grace_days',
            'auto_reconnect_enabled',
            'auto_due_reminder_enabled', 'auto_due_reminder_days',
            'auto_expiry_reminder_enabled', 'auto_expiry_reminder_days',
        ];
        foreach ($fields as $f) {
            $val = sanitize($_POST[$f] ?? '');
            $exists = $this->db->fetchOne("SELECT id FROM settings WHERE `key`=?", [$f]);
            if ($exists) { $this->db->update('settings', ['value'=>$val], '`key`=?', [$f]); }
            else { $this->db->insert('settings', ['key'=>$f,'value'=>$val]); }
        }
        $_SESSION['success'] = 'Automation settings saved.';
        redirect(base_url('automation'));
    }

    public function logs(): void {
        $pageTitle   = 'Automation Logs';
        $currentPage = 'automation';
        $currentSubPage = 'auto-logs';

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $total = $this->db->fetchOne("SELECT COUNT(*) c FROM automation_logs")['c'] ?? 0;
        $logs  = $this->db->fetchAll("SELECT * FROM automation_logs ORDER BY run_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $totalPages = max(1, ceil($total / $perPage));

        $viewFile = BASE_PATH . '/views/automation/logs.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}
