<?php

/**
 * SupportService — Business logic for the Support & Ticketing module.
 *
 * Covers Requirements 3.2, 3.3, 3.4, 3.5, 3.7, 3.8, 3.9, 3.10:
 *   - Ticket CRUD with SLA deadline calculation (urgent=2h, high=8h, medium=24h, low=72h)
 *   - SLA breach detection and marking
 *   - Ticket assignment with SMS notification to assigned employee
 *   - Comment/activity thread per ticket
 *   - Resolution recording with notes and resolver identity
 *   - SLA compliance dashboard per category and per employee
 *   - Duplicate ticket warning within 24 hours
 *
 * @see database/migrations/2024_01_04_001_support_tables.sql
 */
class SupportService extends BaseService
{
    /** SLA hours per priority level — Requirement 3.2 */
    private const SLA_HOURS = [
        'urgent' => 2,
        'high'   => 8,
        'medium' => 24,
        'low'    => 72,
    ];

    private SmsService $smsService;

    public function __construct()
    {
        parent::__construct();
        $this->smsService = new SmsService();
    }

    // ── Tickets ───────────────────────────────────────────────────

    /**
     * Return a paginated list of tickets with optional filters.
     *
     * Requirement 3.1: paginated admin list with ticket number, customer,
     * category, priority, assigned employee, SLA deadline, and status.
     *
     * @param  array $filters  Keys: status, priority, category_id, branch_id, search, page, limit
     * @return array           Pagination result with 'data', 'total', 'page', etc.
     */
    public function getTickets(array $filters = []): array
    {
        $page  = max(1, (int)($filters['page']  ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $params      = [];
        $conditions  = [];

        if (!empty($filters['status'])) {
            $conditions[] = "t.status = ?";
            $params[]     = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $conditions[] = "t.priority = ?";
            $params[]     = $filters['priority'];
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = "t.category_id = ?";
            $params[]     = (int)$filters['category_id'];
        }
        if (!empty($filters['branch_id'])) {
            $conditions[] = "t.branch_id = ?";
            $params[]     = (int)$filters['branch_id'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = "(t.subject LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ?)";
            $like         = '%' . $filters['search'] . '%';
            $params[]     = $like;
            $params[]     = $like;
            $params[]     = $like;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM support_tickets t
                     LEFT JOIN customers c ON c.id = t.customer_id
                     {$where}";

        try {
            $countRow = $this->db->fetchOne($countSql, $params);
            $total    = (int)($countRow['total'] ?? 0);

            $sql = "SELECT t.*,
                           c.full_name      AS customer_name,
                           c.customer_code,
                           pc.name          AS category_name,
                           u.full_name      AS assigned_name
                    FROM support_tickets t
                    LEFT JOIN customers        c  ON c.id  = t.customer_id
                    LEFT JOIN problem_categories pc ON pc.id = t.category_id
                    LEFT JOIN users            u  ON u.id  = t.assigned_to
                    {$where}
                    ORDER BY
                        CASE t.status
                            WHEN 'open'        THEN 1
                            WHEN 'in_progress' THEN 2
                            WHEN 'sla_breached' THEN 3
                            WHEN 'resolved'    THEN 4
                            WHEN 'closed'      THEN 5
                            ELSE 6
                        END,
                        CASE t.priority
                            WHEN 'urgent' THEN 1
                            WHEN 'high'   THEN 2
                            WHEN 'medium' THEN 3
                            WHEN 'low'    THEN 4
                            ELSE 5
                        END,
                        t.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";

            $data       = $this->db->fetchAll($sql, $params);
            $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

            return [
                'data'       => $data,
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $limit,
                'totalPages' => $totalPages,
                'hasNext'    => $page < $totalPages,
                'hasPrev'    => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError('getTickets failed', $e, $filters);
            return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        }
    }

    /**
     * Return a single ticket with customer, category, assigned user, and comments.
     *
     * @param  int $id
     * @return array|null
     */
    public function getTicket(int $id): ?array
    {
        try {
            $ticket = $this->db->fetchOne(
                "SELECT t.*,
                        c.full_name      AS customer_name,
                        c.customer_code,
                        c.phone          AS customer_phone,
                        pc.name          AS category_name,
                        u.full_name      AS assigned_name,
                        u.phone          AS assigned_phone,
                        b.name           AS branch_name
                 FROM support_tickets t
                 LEFT JOIN customers         c  ON c.id  = t.customer_id
                 LEFT JOIN problem_categories pc ON pc.id = t.category_id
                 LEFT JOIN users             u  ON u.id  = t.assigned_to
                 LEFT JOIN branches          b  ON b.id  = t.branch_id
                 WHERE t.id = ? LIMIT 1",
                [$id]
            );

            if (!$ticket) {
                return null;
            }

            // Attach comments
            $ticket['comments'] = $this->db->fetchAll(
                "SELECT tc.*, u.full_name AS user_name
                 FROM ticket_comments tc
                 LEFT JOIN users u ON u.id = tc.user_id
                 WHERE tc.ticket_id = ?
                 ORDER BY tc.created_at ASC",
                [$id]
            );

            // Attach assignment history
            $ticket['assignments'] = $this->db->fetchAll(
                "SELECT ta.*,
                        u1.full_name AS assigned_to_name,
                        u2.full_name AS assigned_by_name
                 FROM ticket_assignments ta
                 LEFT JOIN users u1 ON u1.id = ta.assigned_to
                 LEFT JOIN users u2 ON u2.id = ta.assigned_by
                 WHERE ta.ticket_id = ?
                 ORDER BY ta.assigned_at DESC",
                [$id]
            );

            return $ticket;
        } catch (\Throwable $e) {
            $this->logError('getTicket failed', $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Create a new support ticket.
     *
     * Requirement 3.2: sets SLA deadline based on priority.
     * Requirement 3.10: warns if duplicate ticket within 24 hours (returns warning flag).
     *
     * @param  array $data  Ticket fields
     * @return array        ['id' => int, 'duplicate_warning' => bool]
     * @throws \RuntimeException on failure
     */
    public function createTicket(array $data): array
    {
        $priority  = $data['priority'] ?? 'medium';
        $createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');

        // Req 3.2: calculate SLA deadline
        $slaDeadline = $this->calculateSlaDeadline($priority, $createdAt);

        // Req 3.10: check for duplicate ticket within 24 hours
        $duplicateWarning = false;
        if (!empty($data['customer_id'])) {
            $duplicateWarning = $this->checkDuplicateTicket((int)$data['customer_id'], $data['subject'] ?? '');
        }

        $ticketId = $this->create('support_tickets', [
            'customer_id'      => (int)($data['customer_id'] ?? 0) ?: null,
            'branch_id'        => (int)($data['branch_id']   ?? 0) ?: null,
            'subject'          => $data['subject']      ?? '',
            'description'      => $data['description']  ?? '',
            'priority'         => $priority,
            'status'           => 'open',
            'category_id'      => (int)($data['category_id'] ?? 0) ?: null,
            'assigned_to'      => (int)($data['assigned_to'] ?? 0) ?: null,
            'sla_deadline'     => $slaDeadline->format('Y-m-d H:i:s'),
            'sla_breached'     => 0,
            'created_at'       => $createdAt,
        ]);

        return [
            'id'                => $ticketId,
            'duplicate_warning' => $duplicateWarning,
        ];
    }

    /**
     * Update an existing ticket's fields.
     *
     * @param  int   $id
     * @param  array $data
     * @return void
     */
    public function updateTicket(int $id, array $data): void
    {
        $allowed = [
            'subject', 'description', 'priority', 'status',
            'category_id', 'assigned_to', 'branch_id',
        ];

        $update = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        // If priority changed, recalculate SLA deadline
        if (isset($data['priority'])) {
            $ticket = $this->findById('support_tickets', $id);
            if ($ticket) {
                $createdAt   = $ticket['created_at'] ?? date('Y-m-d H:i:s');
                $slaDeadline = $this->calculateSlaDeadline($data['priority'], $createdAt);
                $update['sla_deadline'] = $slaDeadline->format('Y-m-d H:i:s');
            }
        }

        if (!empty($update)) {
            $this->update('support_tickets', $id, $update);
        }
    }

    /**
     * Assign a ticket to an employee and send SMS notification.
     *
     * Requirement 3.4: assign to any active employee via dropdown.
     * Requirement 3.5: send SMS to assigned employee's phone.
     *
     * @param  int    $ticketId    Ticket ID
     * @param  int    $employeeId  User ID of the employee to assign
     * @param  string $notes       Optional assignment notes
     * @return void
     * @throws \RuntimeException if ticket or employee not found
     */
    public function assignTicket(int $ticketId, int $employeeId, string $notes = ''): void
    {
        $ticket = $this->findById('support_tickets', $ticketId);
        if (!$ticket) {
            throw new \RuntimeException("Ticket #{$ticketId} not found.");
        }

        $employee = $this->db->fetchOne(
            "SELECT id, full_name, phone FROM users WHERE id = ? AND is_active = 1 LIMIT 1",
            [$employeeId]
        );
        if (!$employee) {
            throw new \RuntimeException("Employee #{$employeeId} not found or inactive.");
        }

        $assignedBy = $_SESSION['user_id'] ?? null;

        // Record assignment history
        $this->db->insert('ticket_assignments', [
            'ticket_id'   => $ticketId,
            'assigned_to' => $employeeId,
            'assigned_by' => $assignedBy,
            'assigned_at' => date('Y-m-d H:i:s'),
            'notes'       => $notes,
        ]);

        // Update ticket's assigned_to and status
        $newStatus = $ticket['status'] === 'open' ? 'in_progress' : $ticket['status'];
        $this->db->update('support_tickets', [
            'assigned_to' => $employeeId,
            'status'      => $newStatus,
        ], 'id = ?', [$ticketId]);

        // Req 3.5: send SMS to assigned employee
        if (!empty($employee['phone'])) {
            $message = "You have been assigned support ticket #{$ticketId}: {$ticket['subject']}. Priority: {$ticket['priority']}.";
            if ($notes) {
                $message .= " Note: {$notes}";
            }
            try {
                $this->smsService->send($employee['phone'], $message);
            } catch (\Throwable $e) {
                // Non-fatal: log but don't fail the assignment
                $this->logError('SMS notification failed for ticket assignment', $e, [
                    'ticket_id'   => $ticketId,
                    'employee_id' => $employeeId,
                ]);
            }
        }

        // Add activity comment
        $assignerName = $_SESSION['user_name'] ?? 'System';
        $this->addComment(
            $ticketId,
            "Ticket assigned to {$employee['full_name']} by {$assignerName}." . ($notes ? " Notes: {$notes}" : ''),
            true,
            $assignedBy,
            $assignerName
        );
    }

    /**
     * Add a comment to a ticket's activity thread.
     *
     * Requirement 3.7: full comment/activity thread per ticket.
     *
     * @param  int         $ticketId   Ticket ID
     * @param  string      $message    Comment text
     * @param  bool        $isInternal Whether the comment is internal (not visible to customer)
     * @param  int|null    $userId     Author user ID
     * @param  string|null $authorName Author display name
     * @return int                     New comment ID
     */
    public function addComment(
        int $ticketId,
        string $message,
        bool $isInternal = false,
        ?int $userId = null,
        ?string $authorName = null
    ): int {
        $userId     = $userId     ?? ($_SESSION['user_id']   ?? null);
        $authorName = $authorName ?? ($_SESSION['user_name'] ?? 'Unknown');

        return $this->db->insert('ticket_comments', [
            'ticket_id'   => $ticketId,
            'user_id'     => $userId,
            'author_name' => $authorName,
            'message'     => $message,
            'is_internal' => $isInternal ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Resolve a ticket with resolution notes.
     *
     * Requirement 3.8: record resolution notes, resolver identity, and resolved timestamp.
     *
     * @param  int    $id    Ticket ID
     * @param  string $notes Resolution notes
     * @return void
     * @throws \RuntimeException if ticket not found
     */
    public function resolveTicket(int $id, string $notes): void
    {
        $ticket = $this->findById('support_tickets', $id);
        if (!$ticket) {
            throw new \RuntimeException("Ticket #{$id} not found.");
        }

        $resolverId   = $_SESSION['user_id']   ?? null;
        $resolverName = $_SESSION['user_name'] ?? 'Unknown';
        $resolvedAt   = date('Y-m-d H:i:s');

        $this->db->update('support_tickets', [
            'status'           => 'resolved',
            'resolution_notes' => $notes,
            'assigned_to'      => $ticket['assigned_to'] ?? $resolverId,
        ], 'id = ?', [$id]);

        // Add resolution activity comment
        $this->addComment(
            $id,
            "Ticket resolved by {$resolverName}. Notes: {$notes}",
            true,
            $resolverId,
            $resolverName
        );

        // If there was an SLA violation record, update resolution time
        $slaViolation = $this->db->fetchOne(
            "SELECT id, violated_at FROM sla_violations WHERE ticket_id = ? LIMIT 1",
            [$id]
        );
        if ($slaViolation) {
            $violatedAt      = new \DateTime($slaViolation['violated_at']);
            $resolvedAtDt    = new \DateTime($resolvedAt);
            $diffMinutes     = (int)(($resolvedAtDt->getTimestamp() - $violatedAt->getTimestamp()) / 60);
            $this->db->update('sla_violations', [
                'resolution_time_minutes' => max(0, $diffMinutes),
            ], 'id = ?', [$slaViolation['id']]);
        }
    }

    /**
     * Close a ticket.
     *
     * @param  int $id  Ticket ID
     * @return void
     */
    public function closeTicket(int $id): void
    {
        $ticket = $this->findById('support_tickets', $id);
        if (!$ticket) {
            throw new \RuntimeException("Ticket #{$id} not found.");
        }

        $this->db->update('support_tickets', ['status' => 'closed'], 'id = ?', [$id]);

        $closerName = $_SESSION['user_name'] ?? 'Unknown';
        $closerId   = $_SESSION['user_id']   ?? null;
        $this->addComment($id, "Ticket closed by {$closerName}.", true, $closerId, $closerName);
    }

    /**
     * Scan all open/in-progress tickets past their SLA deadline and mark them as sla_breached.
     *
     * Requirement 3.3: when current_time > sla_deadline and status not resolved/closed,
     * mark ticket as sla_breached and record in sla_violations.
     *
     * @return int  Number of tickets newly marked as breached
     */
    public function checkAndMarkSlaBreaches(): int
    {
        $now = date('Y-m-d H:i:s');

        try {
            // Find tickets that are past deadline and not yet resolved/closed/already breached
            $breachable = $this->db->fetchAll(
                "SELECT id, priority, sla_deadline
                 FROM support_tickets
                 WHERE sla_deadline IS NOT NULL
                   AND sla_deadline < ?
                   AND status NOT IN ('resolved', 'closed', 'sla_breached')
                   AND sla_breached = 0",
                [$now]
            );

            $count = 0;
            foreach ($breachable as $ticket) {
                // Mark ticket as sla_breached
                $this->db->update('support_tickets', [
                    'status'      => 'sla_breached',
                    'sla_breached' => 1,
                ], 'id = ?', [(int)$ticket['id']]);

                // Record in sla_violations (UNIQUE on ticket_id — skip if already exists)
                $existing = $this->db->fetchOne(
                    "SELECT id FROM sla_violations WHERE ticket_id = ? LIMIT 1",
                    [(int)$ticket['id']]
                );
                if (!$existing) {
                    $this->db->insert('sla_violations', [
                        'ticket_id'    => (int)$ticket['id'],
                        'priority'     => $ticket['priority'],
                        'sla_deadline' => $ticket['sla_deadline'],
                        'violated_at'  => $now,
                    ]);
                }

                $count++;
            }

            return $count;
        } catch (\Throwable $e) {
            $this->logError('checkAndMarkSlaBreaches failed', $e);
            return 0;
        }
    }

    /**
     * Return SLA compliance dashboard data.
     *
     * Requirement 3.9: percentage of tickets resolved within SLA per category and per employee.
     *
     * @param  int|null $branchId  Optional branch filter
     * @return array               Keys: by_category, by_employee, summary
     */
    public function getSlaComplianceDashboard(?int $branchId = null): array
    {
        $branchFilter = '';
        $params       = [];

        if ($branchId !== null) {
            $branchFilter = ' AND t.branch_id = ?';
            $params[]     = $branchId;
        }

        try {
            // ── Per category ──────────────────────────────────────
            $categoryRows = $this->db->fetchAll(
                "SELECT
                     pc.name                                                    AS category_name,
                     COUNT(t.id)                                                AS total_tickets,
                     SUM(CASE WHEN t.sla_breached = 0
                               AND t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS within_sla,
                     SUM(CASE WHEN t.sla_breached = 1 THEN 1 ELSE 0 END)       AS breached
                 FROM support_tickets t
                 LEFT JOIN problem_categories pc ON pc.id = t.category_id
                 WHERE t.status IN ('resolved','closed','sla_breached'){$branchFilter}
                 GROUP BY t.category_id, pc.name
                 ORDER BY total_tickets DESC",
                $params
            );

            $byCategory = [];
            foreach ($categoryRows as $row) {
                $total      = (int)$row['total_tickets'];
                $withinSla  = (int)$row['within_sla'];
                $compliance = $total > 0 ? round(($withinSla / $total) * 100, 1) : 0.0;

                $byCategory[] = [
                    'category_name'    => $row['category_name'] ?? 'Uncategorised',
                    'total_tickets'    => $total,
                    'within_sla'       => $withinSla,
                    'breached'         => (int)$row['breached'],
                    'compliance_pct'   => $compliance,
                ];
            }

            // ── Per employee ──────────────────────────────────────
            $employeeRows = $this->db->fetchAll(
                "SELECT
                     u.full_name                                                AS employee_name,
                     COUNT(t.id)                                                AS total_tickets,
                     SUM(CASE WHEN t.sla_breached = 0
                               AND t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS within_sla,
                     SUM(CASE WHEN t.sla_breached = 1 THEN 1 ELSE 0 END)       AS breached
                 FROM support_tickets t
                 LEFT JOIN users u ON u.id = t.assigned_to
                 WHERE t.status IN ('resolved','closed','sla_breached')
                   AND t.assigned_to IS NOT NULL{$branchFilter}
                 GROUP BY t.assigned_to, u.full_name
                 ORDER BY total_tickets DESC",
                $params
            );

            $byEmployee = [];
            foreach ($employeeRows as $row) {
                $total      = (int)$row['total_tickets'];
                $withinSla  = (int)$row['within_sla'];
                $compliance = $total > 0 ? round(($withinSla / $total) * 100, 1) : 0.0;

                $byEmployee[] = [
                    'employee_name'  => $row['employee_name'] ?? 'Unassigned',
                    'total_tickets'  => $total,
                    'within_sla'     => $withinSla,
                    'breached'       => (int)$row['breached'],
                    'compliance_pct' => $compliance,
                ];
            }

            // ── Overall summary ───────────────────────────────────
            $summaryParams = $branchId !== null ? [$branchId] : [];
            $summaryFilter = $branchId !== null ? ' AND t.branch_id = ?' : '';

            $summary = $this->db->fetchOne(
                "SELECT
                     COUNT(*)                                                   AS total,
                     SUM(CASE WHEN t.status = 'open'        THEN 1 ELSE 0 END) AS open_count,
                     SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
                     SUM(CASE WHEN t.status = 'resolved'    THEN 1 ELSE 0 END) AS resolved_count,
                     SUM(CASE WHEN t.status = 'closed'      THEN 1 ELSE 0 END) AS closed_count,
                     SUM(CASE WHEN t.status = 'sla_breached' THEN 1 ELSE 0 END) AS breached_count,
                     SUM(CASE WHEN t.sla_breached = 0
                               AND t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS within_sla_total
                 FROM support_tickets t
                 WHERE 1=1{$summaryFilter}",
                $summaryParams
            ) ?? [];

            $totalClosed = (int)($summary['resolved_count'] ?? 0) + (int)($summary['closed_count'] ?? 0);
            $withinSlaTotal = (int)($summary['within_sla_total'] ?? 0);
            $overallCompliance = $totalClosed > 0
                ? round(($withinSlaTotal / $totalClosed) * 100, 1)
                : 0.0;

            return [
                'by_category'        => $byCategory,
                'by_employee'        => $byEmployee,
                'summary'            => $summary,
                'overall_compliance' => $overallCompliance,
            ];
        } catch (\Throwable $e) {
            $this->logError('getSlaComplianceDashboard failed', $e);
            return ['by_category' => [], 'by_employee' => [], 'summary' => [], 'overall_compliance' => 0.0];
        }
    }

    /**
     * Return all active problem categories.
     *
     * @return array
     */
    public function getCategories(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM problem_categories WHERE is_active = 1 ORDER BY name ASC",
                []
            );
        } catch (\Throwable $e) {
            $this->logError('getCategories failed', $e);
            return [];
        }
    }

    /**
     * Return all active employees (users) available for ticket assignment.
     *
     * Requirement 3.4: any active employee via dropdown.
     *
     * @param  int|null $branchId  Optional branch filter
     * @return array
     */
    public function getAssignableEmployees(?int $branchId = null): array
    {
        $params = [];
        $sql    = "SELECT id, full_name, phone, role_id, branch_id
                   FROM users
                   WHERE is_active = 1";

        if ($branchId !== null) {
            $sql    .= " AND branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY full_name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getAssignableEmployees failed', $e);
            return [];
        }
    }

    /**
     * Calculate the SLA deadline DateTime for a given priority and creation time.
     *
     * Requirement 3.2: urgent=2h, high=8h, medium=24h, low=72h.
     *
     * @param  string $priority  urgent|high|medium|low
     * @param  string $createdAt Y-m-d H:i:s creation timestamp
     * @return \DateTime         SLA deadline
     */
    public function calculateSlaDeadline(string $priority, string $createdAt): \DateTime
    {
        $hours    = self::SLA_HOURS[$priority] ?? self::SLA_HOURS['medium'];
        $deadline = new \DateTime($createdAt);
        $deadline->modify("+{$hours} hours");
        return $deadline;
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * Check whether a duplicate ticket exists for the same customer within 24 hours.
     *
     * Requirement 3.10: warn if duplicate ticket within 24 hours.
     *
     * @param  int    $customerId
     * @param  string $subject
     * @return bool   True if a potential duplicate exists
     */
    private function checkDuplicateTicket(int $customerId, string $subject): bool
    {
        try {
            $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $existing = $this->db->fetchOne(
                "SELECT id FROM support_tickets
                 WHERE customer_id = ?
                   AND created_at >= ?
                   AND status NOT IN ('resolved', 'closed')
                 LIMIT 1",
                [$customerId, $since]
            );
            return $existing !== null;
        } catch (\Throwable $e) {
            $this->logError('checkDuplicateTicket failed', $e);
            return false;
        }
    }
}
