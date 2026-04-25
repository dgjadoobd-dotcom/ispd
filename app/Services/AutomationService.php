<?php

/**
 * Billing Automation Service
 * Handles: invoice generation, overdue suspension, reconnection on payment,
 *          due reminders, expiry reminders.
 */
class AutomationService {
    private Database $db;
    private ?SmsService $sms = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function sms(): SmsService {
        if (!$this->sms) {
            require_once BASE_PATH . '/app/Services/SmsService.php';
            $this->sms = new SmsService();
        }
        return $this->sms;
    }

    // ── 1. Auto Invoice Generation ────────────────────────────────
    /**
     * Generate invoices for all active customers for the current billing month.
     * Skips customers who already have an invoice for this month.
     */
    public function generateMonthlyInvoices(?string $billingMonth = null): array {
        $billingMonth = $billingMonth ?? date('Y-m-01');
        $generated = 0; $skipped = 0; $errors = [];

        $customers = $this->db->fetchAll(
            "SELECT c.*, p.price FROM customers c
             LEFT JOIN packages p ON p.id=c.package_id
             WHERE c.status='active'"
        );

        foreach ($customers as $c) {
            $existing = $this->db->fetchOne(
                "SELECT id FROM invoices WHERE customer_id=? AND billing_month=?",
                [$c['id'], $billingMonth]
            );
            if ($existing) { $skipped++; continue; }

            $amount = (float)($c['monthly_charge'] ?: ($c['price'] ?? 0));
            if ($amount <= 0) { $skipped++; continue; }

            // Pro-rata for new connections this month
            $isProrata = false; $proratadays = 0;
            if ($c['connection_date'] && date('Y-m', strtotime($c['connection_date'])) === date('Y-m', strtotime($billingMonth))) {
                $daysInMonth = (int)date('t', strtotime($billingMonth));
                $connDay     = (int)date('j', strtotime($c['connection_date']));
                $proratadays = $daysInMonth - $connDay + 1;
                $amount      = round(($amount / $daysInMonth) * $proratadays, 2);
                $isProrata   = true;
            }

            $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $dueDate   = date('Y-m-' . str_pad($c['billing_day'] ?? 10, 2, '0', STR_PAD_LEFT), strtotime('+1 month', strtotime($billingMonth)));

            try {
                $this->db->insert('invoices', [
                    'invoice_number' => $invoiceNo,
                    'customer_id'    => $c['id'],
                    'branch_id'      => $c['branch_id'],
                    'package_id'     => $c['package_id'],
                    'billing_month'  => $billingMonth,
                    'amount'         => $amount,
                    'discount'       => 0,
                    'vat'            => 0,
                    'total'          => $amount,
                    'paid_amount'    => 0,
                    'due_amount'     => $amount,
                    'status'         => 'unpaid',
                    'due_date'       => $dueDate,
                    'is_prorata'     => $isProrata ? 1 : 0,
                    'prorata_days'   => $proratadays,
                    'generated_by'   => null,
                ]);
                $cur = $this->db->fetchOne("SELECT due_amount FROM customers WHERE id=?", [$c['id']]);
                $this->db->update('customers', ['due_amount' => ($cur['due_amount'] ?? 0) + $amount], 'id=?', [$c['id']]);
                $generated++;
            } catch (Exception $e) {
                $errors[] = "Customer #{$c['id']}: " . $e->getMessage();
            }
        }

        return $this->log('invoice_generation', $generated, "Generated {$generated}, skipped {$skipped}", $errors);
    }

    // ── 2. Auto Suspension on Overdue ─────────────────────────────
    /**
     * Suspend active customers whose invoices are overdue by $graceDays days.
     */
    public function suspendOverdue(int $graceDays = 0): array {
        $cutoff = date('Y-m-d', strtotime("-{$graceDays} days"));

        $overdue = $this->db->fetchAll(
            "SELECT DISTINCT c.id, c.full_name, c.phone, c.pppoe_username
             FROM customers c
             JOIN invoices i ON i.customer_id = c.id
             WHERE c.status = 'active'
               AND i.status IN ('unpaid','partial')
               AND i.due_date < ?",
            [$cutoff]
        );

        // Load services once
        require_once BASE_PATH . '/app/Services/RadiusService.php';
        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        $radius = new RadiusService();

        // Build MikroTik connections per NAS
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active=1");
        $mikrotikMap = [];
        foreach ($nasDevices as $nas) {
            $mt = new MikroTikService([
                'ip' => $nas['ip_address'], 'port' => $nas['api_port'],
                'username' => $nas['username'], 'password' => $nas['password'], 'timeout' => 5,
            ]);
            if ($mt->connect()) $mikrotikMap[$nas['id']] = $mt;
        }

        $suspended = 0;
        foreach ($overdue as $c) {
            $this->db->update('customers', ['status' => 'suspended'], 'id=?', [$c['id']]);
            $this->db->insert('customer_status_log', [
                'customer_id' => $c['id'],
                'old_status'  => 'active',
                'new_status'  => 'suspended',
                'reason'      => 'Auto-suspended: overdue invoice',
                'changed_by'  => null,
            ]);

            // Disable in RADIUS
            if (!empty($c['pppoe_username']) && $radius->isEnabled()) {
                $radius->deleteUser($c['pppoe_username']);
            }

            // Kick active session from MikroTik
            if (!empty($c['pppoe_username'])) {
                foreach ($mikrotikMap as $mt) {
                    $mt->disablePPPoEUser($c['pppoe_username']);
                    $mt->kickSession($c['pppoe_username']);
                }
            }

            $suspended++;
        }

        return $this->log('auto_suspension', $suspended, "Suspended {$suspended} overdue customers (grace: {$graceDays} days)");
    }

    // ── 3. Auto Reconnection on Full Payment ──────────────────────
    /**
     * Reconnect suspended customers who have no remaining due invoices.
     */
    public function reconnectPaidCustomers(): array {
        $candidates = $this->db->fetchAll(
            "SELECT c.id, c.full_name, c.phone, c.pppoe_username, c.pppoe_password,
                    (SELECT groupname FROM radusergroup WHERE username=c.pppoe_username LIMIT 1) as profile
             FROM customers c
             WHERE c.status = 'suspended'
               AND c.due_amount <= 0
               AND NOT EXISTS (
                   SELECT 1 FROM invoices i
                   WHERE i.customer_id = c.id
                     AND i.status IN ('unpaid','partial')
               )"
        );

        require_once BASE_PATH . '/app/Services/RadiusService.php';
        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        $radius = new RadiusService();

        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active=1");
        $mikrotikMap = [];
        foreach ($nasDevices as $nas) {
            $mt = new MikroTikService([
                'ip' => $nas['ip_address'], 'port' => $nas['api_port'],
                'username' => $nas['username'], 'password' => $nas['password'], 'timeout' => 5,
            ]);
            if ($mt->connect()) $mikrotikMap[$nas['id']] = $mt;
        }

        $reconnected = 0;
        foreach ($candidates as $c) {
            $this->db->update('customers', ['status' => 'active'], 'id=?', [$c['id']]);
            $this->db->insert('customer_status_log', [
                'customer_id' => $c['id'],
                'old_status'  => 'suspended',
                'new_status'  => 'active',
                'reason'      => 'Auto-reconnected: all invoices paid',
                'changed_by'  => null,
            ]);

            // Re-add to RADIUS
            if (!empty($c['pppoe_username']) && !empty($c['pppoe_password']) && $radius->isEnabled()) {
                $radius->addUser($c['pppoe_username'], $c['pppoe_password']);
                if (!empty($c['profile'])) {
                    $radius->assignGroup($c['pppoe_username'], $c['profile']);
                }
            }

            // Re-enable on MikroTik
            if (!empty($c['pppoe_username'])) {
                foreach ($mikrotikMap as $mt) {
                    $mt->enablePPPoEUser($c['pppoe_username']);
                }
            }

            $reconnected++;
        }

        return $this->log('auto_reconnection', $reconnected, "Reconnected {$reconnected} customers");
    }

    // ── 3b. Manual Bulk Unlock ────────────────────────────────────
    /**
     * Unlock ALL suspended customers (manual bulk unlock - no payment check)
     */
    public function unlockAllSuspended(): array {
        // Get count before
        $before = $this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='suspended'");
        $count = $before['c'] ?? 0;

        if ($count === 0) {
            return $this->log('bulk_unlock', 0, "No suspended customers to unlock");
        }

        // Get all suspended
        $suspended = $this->db->fetchAll("SELECT id, full_name, pppoe_username FROM customers WHERE status='suspended'");

        $unlocked = 0;
        foreach ($suspended as $c) {
            // Update status
            $this->db->update('customers', ['status' => 'active'], 'id=?', [$c['id']]);
            
            // Log status change
            $this->db->insert('customer_status_log', [
                'customer_id' => $c['id'],
                'old_status'  => 'suspended',
                'new_status'  => 'active',
                'reason'      => 'Manual bulk unlock',
                'changed_by'  => $_SESSION['user_id'] ?? null,
            ]);
            
            $unlocked++;
        }

        return $this->log('bulk_unlock', $unlocked, "Bulk unlocked {$unlocked} customers");
    }

    // ── 4. Due Reminder SMS ───────────────────────────────────────
    /**
     * Send SMS reminders to customers with invoices due within $daysAhead days.
     */
    public function sendDueReminders(int $daysAhead = 3): array {
        $targetDate = date('Y-m-d', strtotime("+{$daysAhead} days"));

        $due = $this->db->fetchAll(
            "SELECT c.id, c.full_name, c.phone, SUM(i.due_amount) as total_due
             FROM customers c
             JOIN invoices i ON i.customer_id = c.id
             WHERE c.status = 'active'
               AND i.status IN ('unpaid','partial')
               AND i.due_date <= ?
               AND c.phone IS NOT NULL AND c.phone != ''
             GROUP BY c.id",
            [$targetDate]
        );

        $sent = 0;
        foreach ($due as $c) {
            $ok = $this->sms()->sendTemplate('due_reminder', $c['phone'], [
                'name'   => $c['full_name'],
                'amount' => number_format($c['total_due'], 0),
            ], $c['id']);
            if ($ok) $sent++;
        }

        return $this->log('due_reminder', $sent, "Sent {$sent} due reminders (due within {$daysAhead} days)");
    }

    // ── 5. Expiry Reminder SMS ────────────────────────────────────
    /**
     * Send SMS to customers whose service expires within $daysAhead days
     * (based on latest invoice due date as proxy for service period end).
     */
    public function sendExpiryReminders(int $daysAhead = 5): array {
        $from = date('Y-m-d');
        $to   = date('Y-m-d', strtotime("+{$daysAhead} days"));

        $expiring = $this->db->fetchAll(
            "SELECT c.id, c.full_name, c.phone, MAX(i.due_date) as expiry_date
             FROM customers c
             JOIN invoices i ON i.customer_id = c.id
             WHERE c.status = 'active'
               AND i.status IN ('unpaid','partial')
               AND i.due_date BETWEEN ? AND ?
               AND c.phone IS NOT NULL AND c.phone != ''
             GROUP BY c.id",
            [$from, $to]
        );

        $sent = 0;
        foreach ($expiring as $c) {
            $ok = $this->sms()->sendTemplate('due_reminder', $c['phone'], [
                'name'     => $c['full_name'],
                'due_date' => date('d M Y', strtotime($c['expiry_date'])),
                'amount'   => '0',
            ], $c['id']);
            if ($ok) $sent++;
        }

        return $this->log('expiry_reminder', $sent, "Sent {$sent} expiry reminders (expiring within {$daysAhead} days)");
    }

    // ── Stats for dashboard ───────────────────────────────────────
    public function getStats(): array {
        return [
            'active'    => $this->db->fetchOne("SELECT COUNT(*) c FROM customers WHERE status='active'")['c'] ?? 0,
            'suspended' => $this->db->fetchOne("SELECT COUNT(*) c FROM customers WHERE status='suspended'")['c'] ?? 0,
            'overdue'   => $this->db->fetchOne("SELECT COUNT(DISTINCT customer_id) c FROM invoices WHERE status IN ('unpaid','partial') AND due_date < DATE('now')")['c'] ?? 0,
            'due_today' => $this->db->fetchOne("SELECT COUNT(DISTINCT customer_id) c FROM invoices WHERE status IN ('unpaid','partial') AND due_date = DATE('now')")['c'] ?? 0,
            'unpaid_invoices' => $this->db->fetchOne("SELECT COUNT(*) c FROM invoices WHERE status IN ('unpaid','partial')")['c'] ?? 0,
            'total_due_amount' => $this->db->fetchOne("SELECT COALESCE(SUM(due_amount),0) c FROM invoices WHERE status IN ('unpaid','partial')")['c'] ?? 0,
        ];
    }

    public function getRecentLogs(int $limit = 20): array {
        return $this->db->fetchAll(
            "SELECT * FROM automation_logs ORDER BY run_at DESC LIMIT ?", [$limit]
        );
    }

    // ── Internal log helper ───────────────────────────────────────
    private function log(string $jobType, int $affected, string $message, array $errors = []): array {
        $status = empty($errors) ? 'success' : ($affected > 0 ? 'success' : 'error');
        $this->db->insert('automation_logs', [
            'job_type' => $jobType,
            'status'   => $status,
            'affected' => $affected,
            'message'  => $message,
            'details'  => !empty($errors) ? implode("\n", array_slice($errors, 0, 10)) : null,
        ]);
        return ['success' => $status !== 'error', 'affected' => $affected, 'message' => $message, 'errors' => $errors];
    }
}
