<?php

/**
 * RadiusBillingBridgeService
 *
 * Bridges RADIUS usage data with the billing system's invoice/customer data.
 * Connections are passed per-method — no constructor dependencies.
 */
class RadiusBillingBridgeService
{
    /**
     * Checks whether a customer's RADIUS user should be suspended or activated
     * based on their billing status (overdue invoices).
     *
     * @param int $customerId Customer ID in the main DB
     * @param PDO $mainDb     Connection to the main (digital_isp) database
     * @param PDO $radiusPdo  Connection to the RADIUS database
     * @return array{
     *   username: string|null,
     *   billing_status: string,
     *   radius_action_needed: bool,
     *   action: string
     * }
     */
    public function syncCustomerRadiusStatus(int $customerId, PDO $mainDb, PDO $radiusPdo): array
    {
        // Fetch customer billing info and RADIUS username
        $stmt = $mainDb->prepare(
            "SELECT c.pppoe_username, c.status AS customer_status,
                    COUNT(i.id) AS overdue_invoices
             FROM customers c
             LEFT JOIN invoices i
               ON i.customer_id = c.id
              AND i.status IN ('unpaid', 'partial')
              AND i.due_date < CURDATE()
             WHERE c.id = :customer_id
             GROUP BY c.id, c.pppoe_username, c.status
             LIMIT 1"
        );
        $stmt->execute([':customer_id' => $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return [
                'username'             => null,
                'billing_status'       => 'unknown',
                'radius_action_needed' => false,
                'action'               => 'none',
            ];
        }

        $username       = $row['pppoe_username'] ?? null;
        $overdueCount   = (int) $row['overdue_invoices'];
        $billingStatus  = $overdueCount > 0 ? 'overdue' : 'current';

        // Determine current RADIUS active state
        $radiusActive = false;
        if ($username !== null) {
            $rStmt = $radiusPdo->prepare(
                "SELECT is_active FROM radius_users WHERE username = :username LIMIT 1"
            );
            $rStmt->execute([':username' => $username]);
            $rRow = $rStmt->fetch(PDO::FETCH_ASSOC);
            if ($rRow !== false) {
                $radiusActive = (bool) $rRow['is_active'];
            }
        }

        // Decide action
        $action = 'none';
        $actionNeeded = false;

        if ($billingStatus === 'overdue' && $radiusActive) {
            $action = 'suspend';
            $actionNeeded = true;
        } elseif ($billingStatus === 'current' && !$radiusActive && $username !== null) {
            $action = 'activate';
            $actionNeeded = true;
        }

        return [
            'username'             => $username,
            'billing_status'       => $billingStatus,
            'radius_action_needed' => $actionNeeded,
            'action'               => $action,
        ];
    }

    /**
     * Returns customers whose current-month RADIUS usage exceeds the given threshold.
     *
     * @param PDO   $mainDb      Connection to the main (digital_isp) database
     * @param PDO   $radiusPdo   Connection to the RADIUS database
     * @param float $thresholdGb Usage threshold in gigabytes (default 100 GB)
     * @return array<int, array{
     *   customer_id: int,
     *   username: string,
     *   usage_gb: float,
     *   threshold_gb: float
     * }>
     */
    public function getCustomersWithUsageOverage(
        PDO $mainDb,
        PDO $radiusPdo,
        float $thresholdGb = 100.0
    ): array {
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        // Aggregate current-month usage per username from RADIUS DB
        $stmt = $radiusPdo->prepare(
            "SELECT username,
                    (SUM(bytes_in) + SUM(bytes_out)) / 1073741824 AS usage_gb
             FROM radius_usage_daily
             WHERE date >= :month_start
               AND date <= :month_end
             GROUP BY username
             HAVING usage_gb > :threshold"
        );
        $stmt->execute([
            ':month_start' => $monthStart,
            ':month_end'   => $monthEnd,
            ':threshold'   => $thresholdGb,
        ]);
        $usageRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($usageRows)) {
            return [];
        }

        // Map usernames to customer IDs via main DB
        $usernames    = array_column($usageRows, 'username');
        $placeholders = implode(',', array_fill(0, count($usernames), '?'));

        $cStmt = $mainDb->prepare(
            "SELECT id AS customer_id, pppoe_username AS username
             FROM customers
             WHERE pppoe_username IN ($placeholders)"
        );
        $cStmt->execute($usernames);
        $customerMap = [];
        foreach ($cStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $customerMap[$c['username']] = (int) $c['customer_id'];
        }

        $result = [];
        foreach ($usageRows as $row) {
            $uname = $row['username'];
            if (!isset($customerMap[$uname])) {
                continue; // skip RADIUS users not linked to a customer
            }
            $result[] = [
                'customer_id'  => $customerMap[$uname],
                'username'     => $uname,
                'usage_gb'     => round((float) $row['usage_gb'], 4),
                'threshold_gb' => $thresholdGb,
            ];
        }

        return $result;
    }

    /**
     * Generates a monthly usage summary for all customers for a given month.
     *
     * @param string $yearMonth Month in 'YYYY-MM' format (e.g. '2024-03')
     * @param PDO    $mainDb    Connection to the main (digital_isp) database
     * @param PDO    $radiusPdo Connection to the RADIUS database
     * @return array<int, array{
     *   customer_id: int,
     *   username: string,
     *   pppoe_username: string,
     *   usage_gb: float,
     *   session_count: int
     * }>
     */
    public function generateMonthlyUsageSummary(
        string $yearMonth,
        PDO $mainDb,
        PDO $radiusPdo
    ): array {
        // Derive first and last day of the requested month
        $monthStart = $yearMonth . '-01';
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        // Aggregate usage from RADIUS DB for the month
        $stmt = $radiusPdo->prepare(
            "SELECT username,
                    (SUM(bytes_in) + SUM(bytes_out)) / 1073741824 AS usage_gb,
                    SUM(session_count) AS session_count
             FROM radius_usage_daily
             WHERE date >= :month_start
               AND date <= :month_end
             GROUP BY username
             ORDER BY username ASC"
        );
        $stmt->execute([
            ':month_start' => $monthStart,
            ':month_end'   => $monthEnd,
        ]);
        $usageRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($usageRows)) {
            return [];
        }

        // Fetch matching customers from main DB
        $usernames    = array_column($usageRows, 'username');
        $placeholders = implode(',', array_fill(0, count($usernames), '?'));

        $cStmt = $mainDb->prepare(
            "SELECT id AS customer_id, full_name AS username, pppoe_username
             FROM customers
             WHERE pppoe_username IN ($placeholders)"
        );
        $cStmt->execute($usernames);

        $customerMap = [];
        foreach ($cStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $customerMap[$c['pppoe_username']] = [
                'customer_id'   => (int) $c['customer_id'],
                'username'      => $c['username'],
                'pppoe_username' => $c['pppoe_username'],
            ];
        }

        $summary = [];
        foreach ($usageRows as $row) {
            $pppoe = $row['username'];
            if (!isset($customerMap[$pppoe])) {
                continue;
            }
            $customer  = $customerMap[$pppoe];
            $summary[] = [
                'customer_id'    => $customer['customer_id'],
                'username'       => $customer['username'],
                'pppoe_username' => $pppoe,
                'usage_gb'       => round((float) $row['usage_gb'], 4),
                'session_count'  => (int) $row['session_count'],
            ];
        }

        return $summary;
    }
}
