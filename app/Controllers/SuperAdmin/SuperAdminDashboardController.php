<?php
/**
 * SuperAdmin Main Controller
 * Handles the owner-level admin panel dashboard and core features.
 */
class SuperAdminDashboardController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $pageTitle   = 'Super Admin — Owner Dashboard';
        $currentPage = 'sa-dashboard';

        // ── Platform-wide KPIs ────────────────────────────────────
        $stats = $this->getPlatformStats();

        // ── Revenue (last 12 months) ──────────────────────────────
        $revenueChart = $this->getMonthlyRevenue(12);

        // ── Recent activity ───────────────────────────────────────
        $recentLogs   = $this->db->fetchAll(
            "SELECT al.*, u.full_name, u.username FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC LIMIT 15"
        );

        // ── Top branches by revenue ───────────────────────────────
        $topBranches = $this->db->fetchAll(
            "SELECT b.name, b.code,
                    COUNT(DISTINCT c.id) as customers,
                    COALESCE(SUM(p.amount),0) as revenue
             FROM branches b
             LEFT JOIN customers c ON c.branch_id = b.id
             LEFT JOIN payments p ON p.branch_id = b.id
                   AND p.payment_date >= DATE('now','-30 days')
             WHERE b.is_active = 1
             GROUP BY b.id ORDER BY revenue DESC LIMIT 8"
        );

        // ── System health ─────────────────────────────────────────
        $systemHealth = $this->getSystemHealth();

        // ── Pending approvals ─────────────────────────────────────
        $pendingSignups  = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='pending'")['c'] ?? 0);
        $pendingTickets  = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM support_tickets WHERE status='open'")['c'] ?? 0);
        $pendingPayments = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM invoices WHERE status='unpaid'")['c'] ?? 0);

        $viewFile = BASE_PATH . '/views/superadmin/dashboard/index.php';
        require_once BASE_PATH . '/views/superadmin/layouts/main.php';
    }

    // ── API: live stats ───────────────────────────────────────────
    public function liveStats(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'stats'        => $this->getPlatformStats(),
            'system'       => $this->getSystemHealth(),
            'timestamp'    => date('Y-m-d H:i:s'),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function getPlatformStats(): array
    {
        $totalCustomers  = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM customers")['c'] ?? 0);
        $activeCustomers = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE status='active'")['c'] ?? 0);
        $totalRevenue    = (float)($this->db->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM payments")['s'] ?? 0);
        $monthRevenue    = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) as s FROM payments WHERE strftime('%Y-%m',payment_date)=strftime('%Y-%m','now')"
        )['s'] ?? 0);
        $totalInvoices   = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM invoices")['c'] ?? 0);
        $unpaidInvoices  = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM invoices WHERE status='unpaid'")['c'] ?? 0);
        $totalDue        = (float)($this->db->fetchOne("SELECT COALESCE(SUM(due_amount),0) as s FROM invoices WHERE status IN ('unpaid','partial')")['s'] ?? 0);
        $totalBranches   = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM branches WHERE is_active=1")['c'] ?? 0);
        $totalUsers      = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_active=1")['c'] ?? 0);
        $totalTickets    = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM support_tickets")['c'] ?? 0);
        $openTickets     = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM support_tickets WHERE status='open'")['c'] ?? 0);
        $totalNas        = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM nas_devices WHERE is_active=1")['c'] ?? 0);
        $onlineNas       = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM nas_devices WHERE connection_status='online' AND is_active=1")['c'] ?? 0);
        $totalPackages   = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM packages WHERE is_active=1")['c'] ?? 0);
        $totalResellers  = (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM resellers WHERE is_active=1")['c'] ?? 0);

        return compact(
            'totalCustomers','activeCustomers','totalRevenue','monthRevenue',
            'totalInvoices','unpaidInvoices','totalDue','totalBranches',
            'totalUsers','totalTickets','openTickets','totalNas','onlineNas',
            'totalPackages','totalResellers'
        );
    }

    private function getMonthlyRevenue(int $months = 12): array
    {
        $rows = $this->db->fetchAll(
            "SELECT strftime('%Y-%m', payment_date) as month,
                    COALESCE(SUM(amount),0) as revenue,
                    COUNT(*) as count
             FROM payments
             WHERE payment_date >= DATE('now', '-{$months} months')
             GROUP BY strftime('%Y-%m', payment_date)
             ORDER BY month ASC"
        );
        return $rows;
    }

    private function getSystemHealth(): array
    {
        $diskFree  = disk_free_space(BASE_PATH);
        $diskTotal = disk_total_space(BASE_PATH);
        $diskUsed  = $diskTotal - $diskFree;
        $diskPct   = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

        $logSize = 0;
        $logDir  = BASE_PATH . '/storage/logs';
        if (is_dir($logDir)) {
            foreach (glob($logDir . '/*.log') as $f) {
                $logSize += filesize($f);
            }
        }

        return [
            'php_version'  => PHP_VERSION,
            'disk_free_gb' => round($diskFree / 1073741824, 2),
            'disk_total_gb'=> round($diskTotal / 1073741824, 2),
            'disk_pct'     => $diskPct,
            'log_size_mb'  => round($logSize / 1048576, 2),
            'memory_limit' => ini_get('memory_limit'),
            'uptime'       => $this->getUptime(),
            'db_ok'        => true,
        ];
    }

    private function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $secs = (int)explode(' ', $uptime)[0];
                $days = floor($secs / 86400);
                $hrs  = floor(($secs % 86400) / 3600);
                return "{$days}d {$hrs}h";
            }
        }
        return 'N/A';
    }
}
