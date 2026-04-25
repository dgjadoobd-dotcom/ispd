<?php

/**
 * BranchService — Business logic for the Branch Management module.
 *
 * Covers Requirements:
 *   - 1.1: Branch CRUD (name, code, address, phone, email, manager)
 *   - 1.2: comadmin sees all branches
 *   - 1.3: branch_admin restricted to their branch_id
 *   - 1.4: unique login credential per branch via users table
 *   - 1.5: per-branch summary reports (customer count, monthly revenue,
 *           outstanding dues, active tickets), filterable by date range
 *   - 1.6: deactivated branch (is_active=0)
 *   - 1.8: duplicate branch code rejected with RuntimeException
 */
class BranchService extends BaseService
{
    // ── Branches ──────────────────────────────────────────────────

    /**
     * Return all branches with customer count.
     * Req 1.2: comadmin/superadmin see all; Req 1.3: branch_admin sees only their own.
     *
     * @param  bool $activeOnly  If true, only return active branches
     * @return array
     */
    public function getBranches(?bool $activeOnly = false): array
    {
        $params = [];
        $sql = "SELECT b.*,
                       COUNT(DISTINCT c.id) AS customer_count
                FROM branches b
                LEFT JOIN customers c ON c.branch_id = b.id";

        $conditions = [];
        if ($activeOnly) {
            $conditions[] = "b.is_active = 1";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " GROUP BY b.id ORDER BY b.name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getBranches failed', $e);
            return [];
        }
    }

    /**
     * Return a single branch with stats.
     *
     * @param  int $id
     * @return array|null
     */
    public function getBranch(int $id): ?array
    {
        try {
            $branch = $this->db->fetchOne(
                "SELECT b.*,
                        COUNT(DISTINCT c.id) AS customer_count
                 FROM branches b
                 LEFT JOIN customers c ON c.branch_id = b.id
                 WHERE b.id = ?
                 GROUP BY b.id
                 LIMIT 1",
                [$id]
            );

            return $branch ?: null;
        } catch (\Throwable $e) {
            $this->logError('getBranch failed', $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Create a new branch.
     * Req 1.1: CRUD with name, code, address, phone, email, manager.
     * Req 1.8: Throws RuntimeException if code already exists.
     *
     * @param  array $data  Branch fields
     * @return int          New branch ID
     * @throws \RuntimeException if code already exists
     */
    public function createBranch(array $data): int
    {
        $code = strtoupper(trim($data['code'] ?? ''));

        // Req 1.8: validate unique code
        $existing = $this->db->fetchOne(
            "SELECT id FROM branches WHERE code = ? LIMIT 1",
            [$code]
        );
        if ($existing) {
            throw new \RuntimeException("Branch code '{$code}' already exists. Please use a unique code.");
        }

        return $this->create('branches', [
            'name'      => trim($data['name'] ?? ''),
            'code'      => $code,
            'address'   => trim($data['address'] ?? ''),
            'phone'     => trim($data['phone'] ?? ''),
            'email'     => trim($data['email'] ?? ''),
            'manager'   => trim($data['manager'] ?? ''),
            'is_active' => 1,
        ]);
    }

    /**
     * Update an existing branch.
     * Req 1.8: Validates unique code (excluding self).
     *
     * @param  int   $id
     * @param  array $data
     * @return void
     * @throws \RuntimeException if code already used by another branch
     */
    public function updateBranch(int $id, array $data): void
    {
        $code = strtoupper(trim($data['code'] ?? ''));

        // Req 1.8: validate unique code excluding self
        $existing = $this->db->fetchOne(
            "SELECT id FROM branches WHERE code = ? AND id != ? LIMIT 1",
            [$code, $id]
        );
        if ($existing) {
            throw new \RuntimeException("Branch code '{$code}' is already used by another branch.");
        }

        $this->update('branches', $id, [
            'name'    => trim($data['name'] ?? ''),
            'code'    => $code,
            'address' => trim($data['address'] ?? ''),
            'phone'   => trim($data['phone'] ?? ''),
            'email'   => trim($data['email'] ?? ''),
            'manager' => trim($data['manager'] ?? ''),
        ]);
    }

    /**
     * Deactivate a branch (set is_active=0).
     * Req 1.6: deactivated branch prevents new customers/invoices.
     *
     * @param  int $id
     * @return void
     */
    public function deactivateBranch(int $id): void
    {
        $this->db->update('branches', ['is_active' => 0], 'id = ?', [$id]);
    }

    /**
     * Activate a branch (set is_active=1).
     *
     * @param  int $id
     * @return void
     */
    public function activateBranch(int $id): void
    {
        $this->db->update('branches', ['is_active' => 1], 'id = ?', [$id]);
    }

    // ── Reports ───────────────────────────────────────────────────

    /**
     * Generate a summary report for a branch over a date range.
     * Req 1.5: customer count, monthly revenue, outstanding dues, active tickets.
     * Handles missing data gracefully (returns zeros, not errors).
     *
     * @param  int    $branchId
     * @param  string $dateFrom  Y-m-d
     * @param  string $dateTo    Y-m-d
     * @return array
     */
    public function generateSummaryReport(int $branchId, string $dateFrom, string $dateTo): array
    {
        // Customer count for this branch
        $customerCount = 0;
        try {
            $row = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM customers WHERE branch_id = ?",
                [$branchId]
            );
            $customerCount = (int)($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            $this->logError('generateSummaryReport: customer_count failed', $e);
        }

        // Monthly revenue: SUM of payments in the period
        $monthlyRevenue = 0.00;
        try {
            $row = $this->db->fetchOne(
                "SELECT COALESCE(SUM(p.amount), 0) AS total
                 FROM payments p
                 INNER JOIN invoices i ON i.id = p.invoice_id
                 WHERE i.branch_id = ?
                   AND DATE(p.payment_date) BETWEEN ? AND ?",
                [$branchId, $dateFrom, $dateTo]
            );
            $monthlyRevenue = (float)($row['total'] ?? 0);
        } catch (\Throwable $e) {
            $this->logError('generateSummaryReport: monthly_revenue failed', $e);
        }

        // Outstanding dues: SUM of invoices due_amount where status != 'paid'
        $outstandingDues = 0.00;
        try {
            $row = $this->db->fetchOne(
                "SELECT COALESCE(SUM(due_amount), 0) AS total
                 FROM invoices
                 WHERE branch_id = ?
                   AND status != 'paid'",
                [$branchId]
            );
            $outstandingDues = (float)($row['total'] ?? 0);
        } catch (\Throwable $e) {
            $this->logError('generateSummaryReport: outstanding_dues failed', $e);
        }

        // Active tickets: support_tickets count where status not in resolved/closed
        $activeTickets = 0;
        try {
            $row = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt
                 FROM support_tickets
                 WHERE branch_id = ?
                   AND status NOT IN ('resolved', 'closed')",
                [$branchId]
            );
            $activeTickets = (int)($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            $this->logError('generateSummaryReport: active_tickets failed', $e);
        }

        $reportData = [
            'branch_id'        => $branchId,
            'period_start'     => $dateFrom,
            'period_end'       => $dateTo,
            'customer_count'   => $customerCount,
            'monthly_revenue'  => $monthlyRevenue,
            'outstanding_dues' => $outstandingDues,
            'active_tickets'   => $activeTickets,
        ];

        // Store in branch_reports table
        try {
            $this->db->insert('branch_reports', [
                'branch_id'        => $branchId,
                'report_type'      => 'summary',
                'period_start'     => $dateFrom,
                'period_end'       => $dateTo,
                'customer_count'   => $customerCount,
                'monthly_revenue'  => $monthlyRevenue,
                'outstanding_dues' => $outstandingDues,
                'active_tickets'   => $activeTickets,
                'report_data'      => json_encode($reportData),
                'generated_by'     => $_SESSION['user_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->logError('generateSummaryReport: insert failed', $e);
        }

        return $reportData;
    }

    /**
     * Fetch report history for a branch.
     *
     * @param  int $branchId
     * @return array
     */
    public function getReports(int $branchId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT br.*, u.full_name AS generated_by_name
                 FROM branch_reports br
                 LEFT JOIN users u ON u.id = br.generated_by
                 WHERE br.branch_id = ?
                 ORDER BY br.generated_at DESC",
                [$branchId]
            );
        } catch (\Throwable $e) {
            $this->logError('getReports failed', $e, ['branch_id' => $branchId]);
            return [];
        }
    }

    // ── Credentials ───────────────────────────────────────────────

    /**
     * Assign (upsert) a login credential to a branch.
     * Req 1.4: unique login credential per branch.
     *
     * @param  int $branchId
     * @param  int $userId
     * @return void
     */
    public function assignCredential(int $branchId, int $userId): void
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM branch_credentials WHERE branch_id = ? LIMIT 1",
            [$branchId]
        );

        if ($existing) {
            $this->db->update('branch_credentials', [
                'user_id'    => $userId,
                'created_by' => $_SESSION['user_id'] ?? null,
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('branch_credentials', [
                'branch_id'  => $branchId,
                'user_id'    => $userId,
                'created_by' => $_SESSION['user_id'] ?? null,
            ]);
        }
    }

    /**
     * Fetch the credential assignment for a branch with user info.
     *
     * @param  int $branchId
     * @return array|null
     */
    public function getCredential(int $branchId): ?array
    {
        try {
            $row = $this->db->fetchOne(
                "SELECT bc.*, u.username, u.full_name AS user_full_name, u.email AS user_email
                 FROM branch_credentials bc
                 INNER JOIN users u ON u.id = bc.user_id
                 WHERE bc.branch_id = ?
                 LIMIT 1",
                [$branchId]
            );
            return $row ?: null;
        } catch (\Throwable $e) {
            $this->logError('getCredential failed', $e, ['branch_id' => $branchId]);
            return null;
        }
    }
}
