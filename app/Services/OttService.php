<?php

/**
 * OttService — Business logic for the OTT Subscription Management module.
 *
 * Covers Requirements:
 *   16.1 — OTT provider management (CRUD)
 *   16.2 — OTT package bundling with internet packages
 *   16.3 — Subscription creation when customer assigned a bundled package
 *   16.4 — Auto-renewal attempt and result logging
 *   16.5 — Renewal failure: set status to expired, send SMS notification
 *   16.6 — Subscriber dashboard: active, expired, pending renewal counts
 *   16.7 — Manual activation and deactivation per customer
 *   16.8 — Deactivation reason and timestamp recorded
 */
class OttService extends BaseService
{
    private SmsService $sms;

    public function __construct()
    {
        parent::__construct();
        $this->sms = new SmsService();
    }

    // ── Providers ─────────────────────────────────────────────────

    /**
     * Return all OTT providers (active or all).
     * Req 16.1
     */
    public function getProviders(bool $activeOnly = false): array
    {
        try {
            $sql = "SELECT p.*,
                           COUNT(DISTINCT pkg.id) AS package_count,
                           COUNT(DISTINCT s.id)   AS subscription_count
                    FROM ott_providers p
                    LEFT JOIN ott_packages pkg ON pkg.provider_id = p.id AND pkg.is_active = 1
                    LEFT JOIN ott_subscriptions s ON s.provider_id = p.id AND s.status = 'active'";

            if ($activeOnly) {
                $sql .= " WHERE p.is_active = 1";
            }

            $sql .= " GROUP BY p.id ORDER BY p.name ASC";

            return $this->db->fetchAll($sql, []);
        } catch (\Throwable $e) {
            $this->logError('getProviders failed', $e);
            return [];
        }
    }

    /**
     * Return a single provider by ID.
     */
    public function getProvider(int $id): ?array
    {
        return $this->findById('ott_providers', $id);
    }

    /**
     * Create a new OTT provider.
     * Req 16.1: name, logo, API endpoint, API key, supported plan types.
     */
    public function createProvider(array $data): int
    {
        return $this->create('ott_providers', [
            'name'         => trim($data['name'] ?? ''),
            'logo_url'     => trim($data['logo_url'] ?? ''),
            'api_endpoint' => trim($data['api_endpoint'] ?? ''),
            'api_key'      => trim($data['api_key'] ?? ''),
            'plan_types'   => trim($data['plan_types'] ?? ''),
            'is_active'    => 1,
            'notes'        => trim($data['notes'] ?? ''),
            'created_by'   => $_SESSION['user_id'] ?? null,
        ]);
    }

    /**
     * Update an existing OTT provider.
     */
    public function updateProvider(int $id, array $data): void
    {
        $this->update('ott_providers', $id, [
            'name'         => trim($data['name'] ?? ''),
            'logo_url'     => trim($data['logo_url'] ?? ''),
            'api_endpoint' => trim($data['api_endpoint'] ?? ''),
            'api_key'      => trim($data['api_key'] ?? ''),
            'plan_types'   => trim($data['plan_types'] ?? ''),
            'is_active'    => (int)($data['is_active'] ?? 1),
            'notes'        => trim($data['notes'] ?? ''),
        ]);
    }

    /**
     * Toggle provider active status.
     */
    public function toggleProvider(int $id): void
    {
        $provider = $this->getProvider($id);
        if (!$provider) {
            throw new \RuntimeException("OTT provider #{$id} not found.");
        }
        $this->db->update('ott_providers', ['is_active' => $provider['is_active'] ? 0 : 1], 'id = ?', [$id]);
    }

    // ── Packages ──────────────────────────────────────────────────

    /**
     * Return all OTT packages with provider info.
     * Req 16.2
     */
    public function getPackages(?int $providerId = null, bool $activeOnly = false): array
    {
        try {
            $params = [];
            $where  = [];

            if ($providerId !== null) {
                $where[]  = 'pkg.provider_id = ?';
                $params[] = $providerId;
            }
            if ($activeOnly) {
                $where[] = 'pkg.is_active = 1';
            }

            $whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            return $this->db->fetchAll(
                "SELECT pkg.*,
                        p.name AS provider_name,
                        p.logo_url AS provider_logo,
                        pk.name AS internet_package_name
                 FROM ott_packages pkg
                 JOIN ott_providers p ON p.id = pkg.provider_id
                 LEFT JOIN packages pk ON pk.id = pkg.package_id
                 {$whereClause}
                 ORDER BY p.name ASC, pkg.name ASC",
                $params
            );
        } catch (\Throwable $e) {
            $this->logError('getPackages failed', $e);
            return [];
        }
    }

    /**
     * Return a single OTT package with provider info.
     */
    public function getPackage(int $id): ?array
    {
        try {
            return $this->db->fetchOne(
                "SELECT pkg.*,
                        p.name AS provider_name,
                        p.logo_url AS provider_logo
                 FROM ott_packages pkg
                 JOIN ott_providers p ON p.id = pkg.provider_id
                 WHERE pkg.id = ?
                 LIMIT 1",
                [$id]
            );
        } catch (\Throwable $e) {
            $this->logError('getPackage failed', $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Create a new OTT package.
     * Req 16.2: price, validity_days, auto_renewal, linked to internet package.
     */
    public function createPackage(array $data): int
    {
        return $this->create('ott_packages', [
            'provider_id'   => (int)$data['provider_id'],
            'package_id'    => !empty($data['package_id']) ? (int)$data['package_id'] : null,
            'name'          => trim($data['name'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
            'price'         => (float)($data['price'] ?? 0),
            'validity_days' => (int)($data['validity_days'] ?? 30),
            'auto_renewal'  => (int)($data['auto_renewal'] ?? 1),
            'is_active'     => 1,
            'created_by'    => $_SESSION['user_id'] ?? null,
        ]);
    }

    /**
     * Update an existing OTT package.
     */
    public function updatePackage(int $id, array $data): void
    {
        $this->update('ott_packages', $id, [
            'provider_id'   => (int)$data['provider_id'],
            'package_id'    => !empty($data['package_id']) ? (int)$data['package_id'] : null,
            'name'          => trim($data['name'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
            'price'         => (float)($data['price'] ?? 0),
            'validity_days' => (int)($data['validity_days'] ?? 30),
            'auto_renewal'  => (int)($data['auto_renewal'] ?? 1),
            'is_active'     => (int)($data['is_active'] ?? 1),
        ]);
    }

    /**
     * Delete an OTT package (only if no active subscriptions).
     */
    public function deletePackage(int $id): void
    {
        $active = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ott_subscriptions WHERE ott_package_id = ? AND status = 'active'",
            [$id]
        );
        if ((int)($active['cnt'] ?? 0) > 0) {
            throw new \RuntimeException("Cannot delete OTT package with active subscriptions.");
        }
        $this->delete('ott_packages', $id);
    }

    // ── Subscriptions ─────────────────────────────────────────────

    /**
     * Return subscriptions with full details, optionally filtered.
     * Req 16.6: supports dashboard queries.
     */
    public function getSubscriptions(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        try {
            $where  = [];
            $params = [];

            if (!empty($filters['status'])) {
                $where[]  = 's.status = ?';
                $params[] = $filters['status'];
            }
            if (!empty($filters['provider_id'])) {
                $where[]  = 's.provider_id = ?';
                $params[] = (int)$filters['provider_id'];
            }
            if (!empty($filters['customer_id'])) {
                $where[]  = 's.customer_id = ?';
                $params[] = (int)$filters['customer_id'];
            }
            if (!empty($filters['expiring_soon'])) {
                // Subscriptions expiring within the next N days
                $days     = (int)$filters['expiring_soon'];
                $where[]  = "s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                $params[] = $days;
            }

            $whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $countRow = $this->db->fetchOne(
                "SELECT COUNT(*) AS total FROM ott_subscriptions s{$whereClause}",
                $params
            );
            $total = (int)($countRow['total'] ?? 0);

            $offset = ($page - 1) * $perPage;

            $rows = $this->db->fetchAll(
                "SELECT s.*,
                        c.full_name AS customer_name,
                        c.customer_code,
                        c.phone AS customer_phone,
                        pkg.name AS ott_package_name,
                        p.name AS provider_name,
                        p.logo_url AS provider_logo,
                        u.full_name AS activated_by_name,
                        d.full_name AS deactivated_by_name
                 FROM ott_subscriptions s
                 JOIN customers c ON c.id = s.customer_id
                 JOIN ott_packages pkg ON pkg.id = s.ott_package_id
                 JOIN ott_providers p ON p.id = s.provider_id
                 LEFT JOIN users u ON u.id = s.activated_by
                 LEFT JOIN users d ON d.id = s.deactivated_by
                 {$whereClause}
                 ORDER BY s.created_at DESC
                 LIMIT {$perPage} OFFSET {$offset}",
                $params
            );

            $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

            return [
                'data'       => $rows,
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $perPage,
                'totalPages' => $totalPages,
                'hasNext'    => $page < $totalPages,
                'hasPrev'    => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError('getSubscriptions failed', $e, $filters);
            return ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        }
    }

    /**
     * Return a single subscription with full details.
     */
    public function getSubscription(int $id): ?array
    {
        try {
            return $this->db->fetchOne(
                "SELECT s.*,
                        c.full_name AS customer_name,
                        c.customer_code,
                        c.phone AS customer_phone,
                        pkg.name AS ott_package_name,
                        pkg.validity_days,
                        pkg.price AS package_price,
                        p.name AS provider_name,
                        p.logo_url AS provider_logo,
                        u.full_name AS activated_by_name,
                        d.full_name AS deactivated_by_name
                 FROM ott_subscriptions s
                 JOIN customers c ON c.id = s.customer_id
                 JOIN ott_packages pkg ON pkg.id = s.ott_package_id
                 JOIN ott_providers p ON p.id = s.provider_id
                 LEFT JOIN users u ON u.id = s.activated_by
                 LEFT JOIN users d ON d.id = s.deactivated_by
                 WHERE s.id = ?
                 LIMIT 1",
                [$id]
            );
        } catch (\Throwable $e) {
            $this->logError('getSubscription failed', $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Create a new OTT subscription for a customer.
     * Req 16.3: start date, expiry date, status = active.
     *
     * @throws \RuntimeException if package not found or already has active subscription
     */
    public function createSubscription(int $customerId, int $ottPackageId, ?string $startDate = null): int
    {
        $package = $this->getPackage($ottPackageId);
        if (!$package) {
            throw new \RuntimeException("OTT package #{$ottPackageId} not found.");
        }

        // Check for duplicate active subscription for same package
        $existing = $this->db->fetchOne(
            "SELECT id FROM ott_subscriptions
             WHERE customer_id = ? AND ott_package_id = ? AND status = 'active'
             LIMIT 1",
            [$customerId, $ottPackageId]
        );
        if ($existing) {
            throw new \RuntimeException("Customer already has an active subscription for this OTT package.");
        }

        $start  = $startDate ?? date('Y-m-d');
        $expiry = date('Y-m-d', strtotime($start . ' +' . (int)$package['validity_days'] . ' days'));

        $subscriptionId = $this->create('ott_subscriptions', [
            'customer_id'    => $customerId,
            'ott_package_id' => $ottPackageId,
            'provider_id'    => (int)$package['provider_id'],
            'start_date'     => $start,
            'expiry_date'    => $expiry,
            'status'         => 'active',
            'auto_renewal'   => (int)$package['auto_renewal'],
            'activated_by'   => $_SESSION['user_id'] ?? null,
        ]);

        // Log the activation
        $this->logRenewalAction($subscriptionId, $customerId, 'manual_activate', null, 'active', null, $expiry);

        return $subscriptionId;
    }

    /**
     * Manually activate a subscription (set status to active).
     * Req 16.7: manual activation per customer.
     */
    public function activateSubscription(int $id): void
    {
        $sub = $this->getSubscription($id);
        if (!$sub) {
            throw new \RuntimeException("Subscription #{$id} not found.");
        }

        $oldStatus = $sub['status'];
        $newExpiry = date('Y-m-d', strtotime('now +' . (int)$sub['validity_days'] . ' days'));

        $this->db->update('ott_subscriptions', [
            'status'         => 'active',
            'expiry_date'    => $newExpiry,
            'activated_by'   => $_SESSION['user_id'] ?? null,
            'deactivated_at' => null,
        ], 'id = ?', [$id]);

        $this->logRenewalAction($id, (int)$sub['customer_id'], 'manual_activate', $oldStatus, 'active', null, $newExpiry);
    }

    /**
     * Manually deactivate a subscription.
     * Req 16.7: manual deactivation per customer.
     * Req 16.8: record deactivation reason and timestamp.
     */
    public function deactivateSubscription(int $id, string $reason = ''): void
    {
        $sub = $this->getSubscription($id);
        if (!$sub) {
            throw new \RuntimeException("Subscription #{$id} not found.");
        }

        $oldStatus = $sub['status'];
        $now       = date('Y-m-d H:i:s');

        $this->db->update('ott_subscriptions', [
            'status'              => 'cancelled',
            'deactivated_at'      => $now,
            'deactivation_reason' => $reason,
            'deactivated_by'      => $_SESSION['user_id'] ?? null,
        ], 'id = ?', [$id]);

        $this->logRenewalAction($id, (int)$sub['customer_id'], 'manual_deactivate', $oldStatus, 'cancelled', null, null, $reason);
    }

    /**
     * Attempt auto-renewal for a single subscription.
     * Req 16.4: attempt renewal and log result.
     * Req 16.5: on failure, set status to expired and send SMS.
     *
     * @return bool  true = renewed successfully, false = failed
     */
    public function renewSubscription(int $id): bool
    {
        $sub = $this->getSubscription($id);
        if (!$sub) {
            return false;
        }

        // Log the attempt
        $this->logRenewalAction($id, (int)$sub['customer_id'], 'renewal_attempt', $sub['status'], null, $sub['expiry_date'], null);

        try {
            // Increment attempt counter
            $this->db->update('ott_subscriptions', [
                'renewal_attempts' => (int)$sub['renewal_attempts'] + 1,
            ], 'id = ?', [$id]);

            // Calculate new expiry
            $newExpiry = date('Y-m-d', strtotime($sub['expiry_date'] . ' +' . (int)$sub['validity_days'] . ' days'));

            $this->db->update('ott_subscriptions', [
                'status'           => 'active',
                'expiry_date'      => $newExpiry,
                'last_renewed_at'  => date('Y-m-d H:i:s'),
                'renewal_attempts' => 0,
            ], 'id = ?', [$id]);

            $this->logRenewalAction($id, (int)$sub['customer_id'], 'renewal_success', $sub['status'], 'active', $sub['expiry_date'], $newExpiry);

            return true;
        } catch (\Throwable $e) {
            // Req 16.5: renewal failed — set to expired and send SMS
            $this->db->update('ott_subscriptions', [
                'status' => 'expired',
            ], 'id = ?', [$id]);

            $this->logRenewalAction($id, (int)$sub['customer_id'], 'renewal_failed', $sub['status'], 'expired', $sub['expiry_date'], null, $e->getMessage());

            // Send SMS notification to customer (Req 16.5)
            $this->sendRenewalFailureSms($sub);

            return false;
        }
    }

    /**
     * Process all due auto-renewals (called by cron).
     * Req 16.4: attempt renewal for all subscriptions with auto_renewal=1 that have expired.
     *
     * @return array{renewed: int, failed: int}
     */
    public function processAutoRenewals(): array
    {
        $renewed = 0;
        $failed  = 0;

        try {
            $due = $this->db->fetchAll(
                "SELECT s.id
                 FROM ott_subscriptions s
                 WHERE s.auto_renewal = 1
                   AND s.status IN ('active', 'expired')
                   AND s.expiry_date <= CURDATE()
                 ORDER BY s.expiry_date ASC
                 LIMIT 200",
                []
            );

            foreach ($due as $row) {
                if ($this->renewSubscription((int)$row['id'])) {
                    $renewed++;
                } else {
                    $failed++;
                }
            }
        } catch (\Throwable $e) {
            $this->logError('processAutoRenewals failed', $e);
        }

        return ['renewed' => $renewed, 'failed' => $failed];
    }

    // ── Dashboard ─────────────────────────────────────────────────

    /**
     * Return dashboard summary counts.
     * Req 16.6: active, expired, pending renewal subscriptions.
     */
    public function getDashboardStats(): array
    {
        try {
            $row = $this->db->fetchOne(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) AS expired_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
                    SUM(CASE WHEN status = 'active' AND auto_renewal = 1
                              AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                         THEN 1 ELSE 0 END) AS pending_renewal_count
                 FROM ott_subscriptions",
                []
            );

            return [
                'total'                => (int)($row['total'] ?? 0),
                'active_count'         => (int)($row['active_count'] ?? 0),
                'expired_count'        => (int)($row['expired_count'] ?? 0),
                'cancelled_count'      => (int)($row['cancelled_count'] ?? 0),
                'pending_renewal_count'=> (int)($row['pending_renewal_count'] ?? 0),
            ];
        } catch (\Throwable $e) {
            $this->logError('getDashboardStats failed', $e);
            return ['total' => 0, 'active_count' => 0, 'expired_count' => 0, 'cancelled_count' => 0, 'pending_renewal_count' => 0];
        }
    }

    /**
     * Return subscriptions expiring within the next N days (for renewal alerts).
     */
    public function getExpiringSoon(int $days = 3): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT s.*,
                        c.full_name AS customer_name,
                        c.customer_code,
                        c.phone AS customer_phone,
                        pkg.name AS ott_package_name,
                        p.name AS provider_name
                 FROM ott_subscriptions s
                 JOIN customers c ON c.id = s.customer_id
                 JOIN ott_packages pkg ON pkg.id = s.ott_package_id
                 JOIN ott_providers p ON p.id = s.provider_id
                 WHERE s.status = 'active'
                   AND s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                 ORDER BY s.expiry_date ASC",
                [$days]
            );
        } catch (\Throwable $e) {
            $this->logError('getExpiringSoon failed', $e);
            return [];
        }
    }

    // ── Renewal Logs ──────────────────────────────────────────────

    /**
     * Return renewal log entries for a subscription.
     */
    public function getRenewalLogs(int $subscriptionId, int $limit = 50): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT l.*,
                        u.full_name AS performed_by_name
                 FROM ott_renewal_logs l
                 LEFT JOIN users u ON u.id = l.performed_by
                 WHERE l.subscription_id = ?
                 ORDER BY l.performed_at DESC
                 LIMIT ?",
                [$subscriptionId, $limit]
            );
        } catch (\Throwable $e) {
            $this->logError('getRenewalLogs failed', $e, ['subscription_id' => $subscriptionId]);
            return [];
        }
    }

    /**
     * Return all recent renewal log entries (for admin overview).
     */
    public function getAllRenewalLogs(int $limit = 100): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT l.*,
                        c.full_name AS customer_name,
                        c.customer_code,
                        u.full_name AS performed_by_name
                 FROM ott_renewal_logs l
                 JOIN customers c ON c.id = l.customer_id
                 LEFT JOIN users u ON u.id = l.performed_by
                 ORDER BY l.performed_at DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (\Throwable $e) {
            $this->logError('getAllRenewalLogs failed', $e);
            return [];
        }
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * Insert a renewal log entry.
     */
    private function logRenewalAction(
        int $subscriptionId,
        int $customerId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $oldExpiry,
        ?string $newExpiry,
        string $errorMessage = '',
        string $notes = ''
    ): void {
        try {
            $this->db->insert('ott_renewal_logs', [
                'subscription_id' => $subscriptionId,
                'customer_id'     => $customerId,
                'action'          => $action,
                'old_status'      => $oldStatus,
                'new_status'      => $newStatus,
                'old_expiry'      => $oldExpiry,
                'new_expiry'      => $newExpiry,
                'error_message'   => $errorMessage ?: null,
                'performed_by'    => $_SESSION['user_id'] ?? null,
                'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? null,
                'notes'           => $notes ?: null,
            ]);
        } catch (\Throwable $e) {
            // Log silently — don't let audit logging break the main flow
            $this->logError('logRenewalAction failed', $e);
        }
    }

    /**
     * Send SMS notification to customer on renewal failure.
     * Req 16.5
     */
    private function sendRenewalFailureSms(array $sub): void
    {
        try {
            $phone = $sub['customer_phone'] ?? '';
            if (!$phone) {
                return;
            }

            $message = "Dear {$sub['customer_name']}, your {$sub['provider_name']} OTT subscription "
                     . "({$sub['ott_package_name']}) has expired and could not be renewed automatically. "
                     . "Please contact us to reactivate.";

            $sent = $this->sms->send($phone, $message, (int)$sub['customer_id']);

            $logAction = $sent ? 'sms_sent' : 'sms_failed';
            $this->logRenewalAction(
                (int)$sub['id'],
                (int)$sub['customer_id'],
                $logAction,
                null,
                null,
                null,
                null,
                $sent ? '' : 'SMS delivery failed'
            );
        } catch (\Throwable $e) {
            $this->logError('sendRenewalFailureSms failed', $e, ['subscription_id' => $sub['id']]);
        }
    }
}
