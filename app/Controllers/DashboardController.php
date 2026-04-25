<?php

class DashboardController {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function index(): void {
        $pageTitle   = 'Dashboard';
        // Stats
        $radius = null;
        if (file_exists(BASE_PATH . '/app/Services/RadiusService.php')) {
            require_once BASE_PATH . '/app/Services/RadiusService.php';
            $radius = new RadiusService();
        }

        $stats = $this->getStats($radius);
        $recentPayments   = $this->getRecentPayments();
        $recentCustomers  = $this->getRecentCustomers();
        $pendingWorkOrders = $this->getPendingWorkOrders();
        $monthlyRevenue   = $this->getMonthlyRevenue();
        $packageBreakdown = $this->getPackageBreakdown();
        $nasDevices       = $this->db->fetchAll("SELECT id, name, ip_address, connection_status, last_checked FROM nas_devices WHERE is_active = 1");
        $oltDevices       = $this->db->fetchAll("SELECT id, name, ip_address, connection_status, last_checked_at, model, total_ports FROM olts WHERE is_active = 1 ORDER BY name");
        $onuStats         = $this->db->fetchOne("SELECT COUNT(*) as total, SUM(CASE WHEN status='online' THEN 1 ELSE 0 END) as online, SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) as offline FROM onus");

        // Attach per-OLT ONU counts
        foreach ($oltDevices as &$olt) {
            $counts = $this->db->fetchOne(
                "SELECT COUNT(*) as total, SUM(CASE WHEN status='online' THEN 1 ELSE 0 END) as online FROM onus WHERE olt_id=?",
                [$olt['id']]
            );
            $olt['onu_total']  = (int)($counts['total']  ?? 0);
            $olt['onu_online'] = (int)($counts['online'] ?? 0);
        }
        unset($olt);

        $crossModuleWidgets = $this->getCrossModuleWidgets();

        $viewFile = BASE_PATH . '/views/dashboard/index.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function getLiveNetworkStats(): void {
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1");
        $totalSessions = 0;
        $nasResults = [];

        foreach ($nasDevices as $nas) {
            $mikrotik = new MikroTikService([
                'ip'       => $nas['ip_address'],
                'port'     => $nas['api_port'],
                'username' => $nas['username'],
                'password' => $nas['password'],
                'timeout'  => 3,
            ]);

            $online = false;
            $sessions = 0;
            if ($mikrotik->connect()) {
                $online = true;
                $sessions = count($mikrotik->getActiveSessions());
                $totalSessions += $sessions;
            }

            // Sync status back to DB
            $this->db->update('nas_devices', [
                'connection_status' => $online ? 1 : 0,
                'last_checked'      => date('Y-m-d H:i:s'),
            ], 'id = ?', [$nas['id']]);

            $nasResults[] = [
                'id' => $nas['id'],
                'online' => $online,
                'sessions' => $sessions
            ];
        }

        $radiusSessions = 0;
        try {
            $radiusSessions = (int)$this->db->fetchOne(
                "SELECT COUNT(*) as c FROM radacct WHERE acctstoptime IS NULL"
            )['c'];
        } catch (Exception $e) {
            // Table may not exist
        }

        jsonResponse([
            'success' => true,
            'total_sessions' => $totalSessions,
            'radius_sessions' => $radiusSessions,
            'nas_results' => $nasResults
        ]);
    }

    private function getStats(?RadiusService $radius = null): array {
        $branchFilter = '';
        $branchParams = [];
        if (($_SESSION['user_role'] ?? '') !== 'superadmin' && !empty($_SESSION['branch_id'])) {
            $branchFilter = 'AND branch_id = ?';
            $branchParams = [$_SESSION['branch_id']];
        }

        $total      = $this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE 1=1 $branchFilter", $branchParams)['c'];
        $active     = $this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='active' $branchFilter", $branchParams)['c'];
        $suspended  = $this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='suspended' $branchFilter", $branchParams)['c'];
        $pending    = $this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='pending' $branchFilter", $branchParams)['c'];
        $cancelled  = $this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='cancelled' $branchFilter", $branchParams)['c'];
        $newThisMonth = $this->db->fetchOne(
            "SELECT COUNT(*) as c FROM customers WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now') $branchFilter",
            $branchParams
        )['c'];
        // Billing stats
        $billingStats = $this->db->fetchOne(
            "SELECT COUNT(*) as total_inv,
             SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as paid_inv,
             SUM(CASE WHEN status='partial' THEN 1 ELSE 0 END) as partial_inv,
             SUM(CASE WHEN status='unpaid' THEN 1 ELSE 0 END) as unpaid_inv
             FROM invoices WHERE strftime('%Y-%m', billing_month) = strftime('%Y-%m', 'now')"
        );
        $billingClients = $billingStats['total_inv'] ?? 0;
        $paidClients    = $billingStats['paid_inv'] ?? 0;
        $partialClients = $billingStats['partial_inv'] ?? 0;
        $unpaidClients  = $billingStats['unpaid_inv'] ?? 0;

        $todayCol   = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as s FROM payments WHERE DATE(payment_date) = DATE('now') $branchFilter",
            $branchParams
        )['s'];
        $monthCol   = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as s FROM payments WHERE strftime('%m', payment_date) = strftime('%m', 'now') AND strftime('%Y', payment_date) = strftime('%Y', 'now') $branchFilter",
            $branchParams
        )['s'];
        $totalDue   = $this->db->fetchOne(
            "SELECT COALESCE(SUM(due_amount),0) as s FROM invoices WHERE status IN ('unpaid','partial') $branchFilter",
            $branchParams
        )['s'];
        $pendingWO  = $this->db->fetchOne("SELECT COUNT(*) as c FROM work_orders WHERE status IN ('pending','assigned')")['c'];
        $completedWO = $this->db->fetchOne("SELECT COUNT(*) as c FROM work_orders WHERE status = 'completed' AND strftime('%Y-%m', completed_at) = strftime('%Y-%m', 'now')")['c'];
        $activeIncidents = $this->db->fetchOne("SELECT COUNT(*) as c FROM fiber_incidents WHERE status IN ('open','in_progress')")['c'];
        $totalOnus  = $this->db->fetchOne("SELECT COUNT(*) as c FROM onus WHERE status='installed'")['c'];
        
        // RADIUS stats - count active sessions from radacct
        $radiusOnline = 0;
        $totalRadiusUsers = $this->db->fetchOne("SELECT COUNT(*) as c FROM radcheck WHERE attribute = 'Cleartext-Password'")['c'];
        $radiusProfiles = $this->db->fetchOne("SELECT COUNT(DISTINCT groupname) as c FROM radgroupreply")['c'];
        
        try {
            $radiusOnline = (int)$this->db->fetchOne(
                "SELECT COUNT(*) as c FROM radacct WHERE acctstoptime IS NULL"
            )['c'];
        } catch (Exception $e) {
            // radacct table may not exist in default DB
        }

        return compact('total', 'active', 'suspended', 'pending', 'cancelled', 'newThisMonth',
            'billingClients', 'paidClients', 'partialClients', 'unpaidClients',
            'todayCol', 'monthCol', 'totalDue', 'pendingWO', 'completedWO', 'activeIncidents', 'totalOnus', 
            'radiusOnline', 'totalRadiusUsers', 'radiusProfiles');
    }

    private function getRecentPayments(): array {
        return $this->db->fetchAll(
            "SELECT p.receipt_number, p.amount, p.payment_method, p.payment_date, c.full_name, c.customer_code
             FROM payments p JOIN customers c ON c.id = p.customer_id
             ORDER BY p.payment_date DESC LIMIT 8"
        );
    }

    private function getRecentCustomers(): array {
        return $this->db->fetchAll(
            "SELECT c.customer_code, c.full_name, c.phone, c.status, c.created_at, p.name as package_name
             FROM customers c
             LEFT JOIN packages p ON p.id = c.package_id
             ORDER BY c.created_at DESC LIMIT 6"
        );
    }

    private function getPendingWorkOrders(): array {
        return $this->db->fetchAll(
            "SELECT wo.wo_number, wo.title, wo.type, wo.priority, wo.status, wo.created_at,
                    c.full_name as customer_name, t.name as technician
             FROM work_orders wo
             LEFT JOIN customers c ON c.id = wo.customer_id
             LEFT JOIN technicians t ON t.id = wo.technician_id
             WHERE wo.status IN ('pending','assigned','in_progress')
             ORDER BY CASE wo.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END, wo.created_at ASC
             LIMIT 6"
        );
    }

    private function getMonthlyRevenue(): array {
        return $this->db->fetchAll(
            "SELECT strftime('%m-%Y', payment_date) as month,
                    strftime('%Y-%m', payment_date) as month_key,
                    SUM(amount) as total
             FROM payments
             WHERE payment_date >= date('now', '-6 months')
             GROUP BY month_key ORDER BY month_key ASC"
        );
    }

    private function getPackageBreakdown(): array {
        return $this->db->fetchAll(
            "SELECT p.name, COUNT(c.id) as count
             FROM packages p
             LEFT JOIN customers c ON c.package_id = p.id AND c.status='active'
             GROUP BY p.id ORDER BY count DESC LIMIT 6"
        );
    }

    private function getCrossModuleWidgets(): array {
        // Open support tickets count
        $openTickets = 0;
        try {
            $openTickets = (int)($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM support_tickets WHERE status IN ('open','in_progress')"
            )['c'] ?? 0);
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        // Overdue tasks count
        $overdueTasks = 0;
        try {
            $overdueTasks = (int)($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM tasks WHERE status NOT IN ('completed','cancelled') AND due_date < DATE('now')"
            )['c'] ?? 0);
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        // Low stock items count
        $lowStockItems = 0;
        try {
            $lowStockItems = (int)($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM inventory_items WHERE quantity <= reorder_level AND reorder_level > 0"
            )['c'] ?? 0);
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        // Pending purchase requisitions count
        $pendingRequisitions = 0;
        try {
            $pendingRequisitions = (int)($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM purchase_requisitions WHERE status = 'pending'"
            )['c'] ?? 0);
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        // Assets nearing warranty expiry (within 30 days)
        $warrantyExpiring = 0;
        try {
            $warrantyExpiring = (int)($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM assets WHERE warranty_expiry BETWEEN DATE('now') AND DATE('now', '+30 days') AND status != 'disposed'"
            )['c'] ?? 0);
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        // Active OTT subscriptions count
        $activeOttSubs = 0;
        try {
            $activeOttSubs = (int)($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM ott_subscriptions WHERE status = 'active'"
            )['c'] ?? 0);
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        return [
            'open_tickets'        => $openTickets,
            'overdue_tasks'       => $overdueTasks,
            'low_stock_items'     => $lowStockItems,
            'pending_requisitions'=> $pendingRequisitions,
            'warranty_expiring'   => $warrantyExpiring,
            'active_ott_subs'     => $activeOttSubs,
        ];
    }
}
