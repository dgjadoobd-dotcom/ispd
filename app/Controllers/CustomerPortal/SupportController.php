<?php

class PortalSupportController extends PortalController {

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        $this->requireAuth();
        $customerId = $this->getCustomerId();

        $status = sanitize($_GET['status'] ?? '');
        $where = "WHERE customer_id = ?";
        $params = [$customerId];

        if ($status && in_array($status, ['open', 'in_progress', 'pending_customer', 'resolved', 'closed'])) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $tickets = $this->db->fetchAll(
            "SELECT * FROM support_tickets {$where} ORDER BY created_at DESC",
            $params
        );

        $pageTitle = 'Support Tickets';
        $currentPage = 'support';
        $portalCustomer = $this->customer;
        $content = BASE_PATH . '/views/portal/support/index_content.php';

        require_once BASE_PATH . '/views/portal/layouts/main.php';
    }

    public function create(): void {
        $this->requireAuth();
        $pageTitle = 'Create Support Ticket';
        $currentPage = 'support';
        $portalCustomer = $this->customer;
        $content = BASE_PATH . '/views/portal/support/create_content.php';

        require_once BASE_PATH . '/views/portal/layouts/main.php';
    }

    public function store(): void {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('portal/support'));
        }

        $customerId = $this->getCustomerId();
        $customer = $this->getCustomer();

        $category = sanitize($_POST['category'] ?? 'general');
        $priority = sanitize($_POST['priority'] ?? 'normal');
        $subject = sanitize($_POST['subject'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if (empty($subject) || empty($description)) {
            $_SESSION['portal_error'] = 'Subject and description are required.';
            redirect(base_url('portal/support/create'));
        }

        if (!in_array($category, ['billing', 'technical', 'complaint', 'general', 'new_connection', 'disconnection'])) {
            $category = 'general';
        }
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'])) {
            $priority = 'normal';
        }

        // AI Analysis
        $aiService = new AiService();
        if (env('AI_ENABLED')) {
            $analysis = $aiService->analyzeTicket($subject, $description);
            if ($analysis) {
                if (!empty($analysis['category']) && in_array($analysis['category'], ['billing', 'technical', 'complaint', 'general', 'new_connection', 'disconnection'])) {
                    $category = $analysis['category'];
                }
                if (!empty($analysis['priority']) && in_array($analysis['priority'], ['low', 'normal', 'high', 'urgent'])) {
                    $priority = $analysis['priority'];
                }
            }
        }

        $ticketNumber = 'TKT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $ticketId = $this->db->insert('support_tickets', [
            'ticket_number' => $ticketNumber,
            'customer_id' => $customerId,
            'branch_id' => $customer['branch_id'] ?? 1,
            'category' => $category,
            'priority' => $priority,
            'subject' => $subject,
            'description' => $description,
            'status' => 'open',
        ]);

        $this->db->insert('support_ticket_replies', [
            'ticket_id' => $ticketId,
            'customer_id' => $customerId,
            'message' => $description,
        ]);

        $_SESSION['portal_success'] = 'Ticket created successfully. Ticket #: ' . $ticketNumber;
        redirect(base_url('portal/support/view/' . $ticketId));
    }

    public function view(string $id): void {
        $this->requireAuth();
        $customerId = $this->getCustomerId();

        $ticket = $this->db->fetchOne(
            "SELECT t.*, u.full_name as assigned_to_name 
             FROM support_tickets t 
             LEFT JOIN users u ON u.id = t.assigned_to 
             WHERE t.id = ? AND t.customer_id = ?",
            [$id, $customerId]
        );

        if (!$ticket) {
            $_SESSION['portal_error'] = 'Ticket not found.';
            redirect(base_url('portal/support'));
        }

        $replies = $this->db->fetchAll(
            "SELECT tr.*, 
                    c.full_name as customer_name,
                    u.full_name as staff_name
             FROM support_ticket_replies tr
             LEFT JOIN customers c ON c.id = tr.customer_id
             LEFT JOIN users u ON u.id = tr.staff_user_id
             WHERE tr.ticket_id = ? AND (tr.is_internal = 0 OR tr.customer_id = ?)
             ORDER BY tr.created_at ASC",
            [$id, $customerId]
        );

        // AI Assistant
        $aiService = new AiService();
        $aiSuggestion = null;
        if (env('AI_ENABLED')) {
            $aiSuggestion = $aiService->suggestSupportResponse($ticket['subject'], $ticket['description'], $replies);
        }

        $pageTitle = 'Ticket #' . $ticket['ticket_number'];
        $currentPage = 'support';
        $portalCustomer = $this->customer;
        $content = BASE_PATH . '/views/portal/support/view_content.php';

        require_once BASE_PATH . '/views/portal/layouts/main.php';
    }

    public function reply(): void {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('portal/support'));
        }

        $customerId = $this->getCustomerId();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = sanitize($_POST['message'] ?? '');

        if (!$ticketId || empty($message)) {
            $_SESSION['portal_error'] = 'Message is required.';
            redirect(base_url('portal/support/view/' . $ticketId));
        }

        $ticket = $this->db->fetchOne(
            "SELECT * FROM support_tickets WHERE id = ? AND customer_id = ?",
            [$ticketId, $customerId]
        );

        if (!$ticket) {
            $_SESSION['portal_error'] = 'Ticket not found.';
            redirect(base_url('portal/support'));
        }

        $this->db->insert('support_ticket_replies', [
            'ticket_id' => $ticketId,
            'customer_id' => $customerId,
            'message' => $message,
        ]);

        if ($ticket['status'] === 'pending_customer') {
            $this->db->update('support_tickets', ['status' => 'open'], 'id=?', [$ticketId]);
        }

        $_SESSION['portal_success'] = 'Reply sent successfully.';
        redirect(base_url('portal/support/view/' . $ticketId));
    }

    public function close(): void {
        $this->requireAuth();
        
        $customerId = $this->getCustomerId();
        $ticketId = (int)($_POST['ticket_id'] ?? 0);

        $ticket = $this->db->fetchOne(
            "SELECT * FROM support_tickets WHERE id = ? AND customer_id = ?",
            [$ticketId, $customerId]
        );

        if (!$ticket) {
            $_SESSION['portal_error'] = 'Ticket not found.';
            redirect(base_url('portal/support'));
        }

        $this->db->update('support_tickets', [
            'status' => 'closed',
            'closed_at' => date('Y-m-d H:i:s'),
        ], 'id=?', [$ticketId]);

        $_SESSION['portal_success'] = 'Ticket closed.';
        redirect(base_url('portal/support'));
    }

    public function getCategories(): void {
        $categories = [
            ['value' => 'billing', 'label' => 'Billing Issue'],
            ['value' => 'technical', 'label' => 'Technical Support'],
            ['value' => 'complaint', 'label' => 'Complaint'],
            ['value' => 'general', 'label' => 'General Inquiry'],
            ['value' => 'new_connection', 'label' => 'New Connection Request'],
            ['value' => 'disconnection', 'label' => 'Disconnection Request'],
        ];
        $this->jsonResponse(['categories' => $categories]);
    }
}
