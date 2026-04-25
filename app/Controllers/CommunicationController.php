<?php

/**
 * Communication Hub Controller
 * Bulk SMS, logs, templates, campaigns
 */
class CommunicationController {
    private Database $db;
    private SmsService $sms;

    public function __construct() {
        $this->db  = Database::getInstance();
        require_once BASE_PATH . '/app/Services/SmsService.php';
        $this->sms = new SmsService();
    }

    // ── Dashboard ────────────────────────────────────────────────
    public function index(): void {
        $pageTitle  = 'Communication Hub';
        $currentPage = 'comms';
        $currentSubPage = 'comms-dashboard';

        $stats = [
            'total_sent'   => $this->db->fetchOne("SELECT COUNT(*) c FROM sms_logs WHERE status='sent'")['c'] ?? 0,
            'total_failed' => $this->db->fetchOne("SELECT COUNT(*) c FROM sms_logs WHERE status='failed'")['c'] ?? 0,
            'today_sent'   => $this->db->fetchOne("SELECT COUNT(*) c FROM sms_logs WHERE status='sent' AND DATE(sent_at)=DATE('now')")['c'] ?? 0,
            'campaigns'    => $this->db->fetchOne("SELECT COUNT(*) c FROM sms_campaigns")['c'] ?? 0,
        ];

        $recentLogs = $this->db->fetchAll(
            "SELECT l.*, c.full_name FROM sms_logs l
             LEFT JOIN customers c ON c.id=l.customer_id
             ORDER BY l.sent_at DESC LIMIT 10"
        );

        $recentCampaigns = $this->db->fetchAll(
            "SELECT * FROM sms_campaigns ORDER BY created_at DESC LIMIT 5"
        );

        $viewFile = BASE_PATH . '/views/comms/index.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Bulk SMS ─────────────────────────────────────────────────
    public function bulk(): void {
        $pageTitle  = 'Bulk SMS';
        $currentPage = 'comms';
        $currentSubPage = 'bulk';

        $zones    = $this->db->fetchAll("SELECT id, name FROM zones WHERE is_active=1 ORDER BY name");
        $packages = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_active=1 ORDER BY name");
        $branches = $this->db->fetchAll("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name");
        $templates = $this->db->fetchAll("SELECT id, name, message_bn FROM sms_templates WHERE is_active=1 ORDER BY name");

        $viewFile = BASE_PATH . '/views/comms/bulk.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function sendBulk(): void {
        $message     = sanitize($_POST['message'] ?? '');
        $filterType  = sanitize($_POST['filter_type'] ?? 'all');
        $filterValue = sanitize($_POST['filter_value'] ?? '');
        $campaignName = sanitize($_POST['campaign_name'] ?? 'Campaign ' . date('d M Y H:i'));

        if (empty($message)) {
            $_SESSION['error'] = 'Message cannot be empty.';
            redirect(base_url('comms/bulk'));
        }

        // Build recipient query
        $where  = "c.status='active' AND c.phone IS NOT NULL AND c.phone != ''";
        $params = [];

        if ($filterType === 'zone' && $filterValue) {
            $where .= ' AND c.zone_id=?'; $params[] = (int)$filterValue;
        } elseif ($filterType === 'package' && $filterValue) {
            $where .= ' AND c.package_id=?'; $params[] = (int)$filterValue;
        } elseif ($filterType === 'status' && $filterValue) {
            $where .= ' AND c.status=?'; $params[] = $filterValue;
        } elseif ($filterType === 'branch' && $filterValue) {
            $where .= ' AND c.branch_id=?'; $params[] = (int)$filterValue;
        } elseif ($filterType === 'due') {
            $where .= " AND EXISTS (SELECT 1 FROM invoices i WHERE i.customer_id=c.id AND i.status IN ('unpaid','partial'))";
        }

        $recipients = $this->db->fetchAll(
            "SELECT c.id, c.full_name, c.phone FROM customers c WHERE {$where} ORDER BY c.full_name",
            $params
        );

        if (empty($recipients)) {
            $_SESSION['error'] = 'No recipients found for the selected filter.';
            redirect(base_url('comms/bulk'));
        }

        // Create campaign record
        $campaignId = $this->db->insert('sms_campaigns', [
            'name'             => $campaignName,
            'message'          => $message,
            'filter_type'      => $filterType,
            'filter_value'     => $filterValue,
            'total_recipients' => count($recipients),
            'status'           => 'sending',
            'started_at'       => date('Y-m-d H:i:s'),
            'created_by'       => $_SESSION['user_id'] ?? null,
        ]);

        $sent = 0; $failed = 0;
        foreach ($recipients as $r) {
            $ok = $this->sms->send($r['phone'], $message, $r['id']);
            $ok ? $sent++ : $failed++;
        }

        $this->db->update('sms_campaigns', [
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id=?', [$campaignId]);

        $_SESSION['success'] = "Campaign sent: {$sent} delivered, {$failed} failed out of " . count($recipients) . " recipients.";
        redirect(base_url('comms/campaigns'));
    }

    // ── Preview recipients (AJAX) ─────────────────────────────────
    public function previewRecipients(): void {
        $filterType  = sanitize($_GET['filter_type'] ?? 'all');
        $filterValue = sanitize($_GET['filter_value'] ?? '');

        $where  = "c.status='active' AND c.phone IS NOT NULL AND c.phone != ''";
        $params = [];

        if ($filterType === 'zone' && $filterValue) {
            $where .= ' AND c.zone_id=?'; $params[] = (int)$filterValue;
        } elseif ($filterType === 'package' && $filterValue) {
            $where .= ' AND c.package_id=?'; $params[] = (int)$filterValue;
        } elseif ($filterType === 'status' && $filterValue) {
            $where .= ' AND c.status=?'; $params[] = $filterValue;
        } elseif ($filterType === 'branch' && $filterValue) {
            $where .= ' AND c.branch_id=?'; $params[] = (int)$filterValue;
        } elseif ($filterType === 'due') {
            $where .= " AND EXISTS (SELECT 1 FROM invoices i WHERE i.customer_id=c.id AND i.status IN ('unpaid','partial'))";
        }

        $count = $this->db->fetchOne(
            "SELECT COUNT(*) c FROM customers c WHERE {$where}", $params
        )['c'] ?? 0;

        json_response(['count' => (int)$count]);
    }

    // ── SMS Logs ─────────────────────────────────────────────────
    public function logs(): void {
        $pageTitle  = 'SMS Logs';
        $currentPage = 'comms';
        $currentSubPage = 'comms-logs';

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $search = sanitize($_GET['search'] ?? '');
        $status = sanitize($_GET['status'] ?? '');

        $where  = '1=1';
        $params = [];
        if ($search) { $where .= ' AND (l.phone LIKE ? OR c.full_name LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
        if ($status) { $where .= ' AND l.status=?'; $params[] = $status; }

        $total = $this->db->fetchOne(
            "SELECT COUNT(*) c FROM sms_logs l LEFT JOIN customers c ON c.id=l.customer_id WHERE {$where}", $params
        )['c'] ?? 0;

        $logs = $this->db->fetchAll(
            "SELECT l.*, c.full_name, t.name as template_name
             FROM sms_logs l
             LEFT JOIN customers c ON c.id=l.customer_id
             LEFT JOIN sms_templates t ON t.id=l.template_id
             WHERE {$where}
             ORDER BY l.sent_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $totalPages = max(1, ceil($total / $perPage));
        $viewFile = BASE_PATH . '/views/comms/logs.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Templates ────────────────────────────────────────────────
    public function templates(): void {
        $pageTitle  = 'SMS Templates';
        $currentPage = 'comms';
        $currentSubPage = 'templates';

        $templates = $this->db->fetchAll("SELECT * FROM sms_templates ORDER BY event_type");
        $viewFile = BASE_PATH . '/views/comms/templates.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeTemplate(): void {
        $data = [
            'name'       => sanitize($_POST['name'] ?? ''),
            'event_type' => sanitize($_POST['event_type'] ?? 'custom'),
            'message_bn' => $_POST['message_bn'] ?? '',
            'message_en' => $_POST['message_en'] ?? '',
            'variables'  => sanitize($_POST['variables'] ?? ''),
            'is_active'  => 1,
        ];
        if (empty($data['name']) || empty($data['message_bn'])) {
            $_SESSION['error'] = 'Name and Bangla message are required.';
            redirect(base_url('comms/templates'));
        }
        $this->db->insert('sms_templates', $data);
        $_SESSION['success'] = 'Template created.';
        redirect(base_url('comms/templates'));
    }

    public function updateTemplate(): void {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name'       => sanitize($_POST['name'] ?? ''),
            'event_type' => sanitize($_POST['event_type'] ?? 'custom'),
            'message_bn' => $_POST['message_bn'] ?? '',
            'message_en' => $_POST['message_en'] ?? '',
            'variables'  => sanitize($_POST['variables'] ?? ''),
            'is_active'  => (int)($_POST['is_active'] ?? 1),
        ];
        $this->db->update('sms_templates', $data, 'id=?', [$id]);
        $_SESSION['success'] = 'Template updated.';
        redirect(base_url('comms/templates'));
    }

    public function deleteTemplate(string $id): void {
        $this->db->delete('sms_templates', 'id=?', [(int)$id]);
        $_SESSION['success'] = 'Template deleted.';
        redirect(base_url('comms/templates'));
    }

    // ── Campaigns list ───────────────────────────────────────────
    public function campaigns(): void {
        $pageTitle  = 'SMS Campaigns';
        $currentPage = 'comms';
        $currentSubPage = 'campaigns';

        $campaigns = $this->db->fetchAll(
            "SELECT * FROM sms_campaigns ORDER BY created_at DESC LIMIT 100"
        );
        $viewFile = BASE_PATH . '/views/comms/campaigns.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Send due reminders ───────────────────────────────────────
    public function sendDueReminders(): void {
        $result = $this->sms->sendDueReminders();
        $_SESSION['success'] = "Due reminders sent: {$result['sent']} of {$result['attempted']} customers.";
        redirect(base_url('comms'));
    }
}
