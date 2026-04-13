<?php

/**
 * RadiusBillingIntegrationService
 *
 * Bridges RADIUS usage data (radius_usage_daily) with the main billing system.
 */
class RadiusBillingIntegrationService
{
    private PDO $radiusPdo;

    public function __construct(PDO $radiusPdo)
    {
        $this->radiusPdo = $radiusPdo;
    }

    /**
     * Returns aggregated usage totals for a user from radius_usage_daily
     * for the given billing period (inclusive).
     *
     * @param string $username    RADIUS username
     * @param string $periodStart Start date (Y-m-d)
     * @param string $periodEnd   End date   (Y-m-d)
     * @return array{
     *   username: string,
     *   period_start: string,
     *   period_end: string,
     *   total_bytes_in: int,
     *   total_bytes_out: int,
     *   total_bytes_gb: float,
     *   session_count: int
     * }
     */
    public function getUsageForBillingPeriod(
        string $username,
        string $periodStart,
        string $periodEnd
    ): array {
        $stmt = $this->radiusPdo->prepare(
            "SELECT
                COALESCE(SUM(bytes_in), 0)    AS total_bytes_in,
                COALESCE(SUM(bytes_out), 0)   AS total_bytes_out,
                COALESCE(SUM(session_count), 0) AS session_count
             FROM radius_usage_daily
             WHERE username = :username
               AND date >= :period_start
               AND date <= :period_end"
        );
        $stmt->execute([
            ':username'     => $username,
            ':period_start' => $periodStart,
            ':period_end'   => $periodEnd,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalBytesIn  = (int) ($row['total_bytes_in']  ?? 0);
        $totalBytesOut = (int) ($row['total_bytes_out'] ?? 0);
        $totalBytes    = $totalBytesIn + $totalBytesOut;

        return [
            'username'        => $username,
            'period_start'    => $periodStart,
            'period_end'      => $periodEnd,
            'total_bytes_in'  => $totalBytesIn,
            'total_bytes_out' => $totalBytesOut,
            'total_bytes_gb'  => round($totalBytes / 1073741824, 4),
            'session_count'   => (int) ($row['session_count'] ?? 0),
        ];
    }

    /**
     * Looks up the RADIUS username for a customer from the main database.
     * Prefers pppoe_username; falls back to username column.
     *
     * @param int $customerId Customer ID in the main DB
     * @param PDO $mainDb     Connection to the main (digital_isp) database
     * @return string|null    RADIUS username, or null if not found
     */
    public function getCustomerRadiusUsername(int $customerId, PDO $mainDb): ?string
    {
        $stmt = $mainDb->prepare(
            "SELECT pppoe_username, username
             FROM customers
             WHERE id = :customer_id
             LIMIT 1"
        );
        $stmt->execute([':customer_id' => $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        // Prefer pppoe_username if populated, otherwise fall back to username
        if (!empty($row['pppoe_username'])) {
            return $row['pppoe_username'];
        }

        return !empty($row['username']) ? $row['username'] : null;
    }

    /**
     * Returns all users' aggregated usage for the given period from
     * radius_usage_daily, grouped by username.
     *
     * @param string $periodStart Start date (Y-m-d)
     * @param string $periodEnd   End date   (Y-m-d)
     * @param PDO    $radiusPdo   RADIUS database connection
     * @return array<int, array{
     *   username: string,
     *   total_bytes_in: int,
     *   total_bytes_out: int,
     *   total_bytes_gb: float,
     *   session_count: int
     * }>
     */
    public function generateUsageReport(
        string $periodStart,
        string $periodEnd,
        PDO $radiusPdo
    ): array {
        $stmt = $radiusPdo->prepare(
            "SELECT
                username,
                COALESCE(SUM(bytes_in), 0)      AS total_bytes_in,
                COALESCE(SUM(bytes_out), 0)     AS total_bytes_out,
                COALESCE(SUM(session_count), 0) AS session_count
             FROM radius_usage_daily
             WHERE date >= :period_start
               AND date <= :period_end
             GROUP BY username
             ORDER BY username ASC"
        );
        $stmt->execute([
            ':period_start' => $periodStart,
            ':period_end'   => $periodEnd,
        ]);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bytesIn  = (int) $row['total_bytes_in'];
            $bytesOut = (int) $row['total_bytes_out'];
            $rows[] = [
                'username'        => $row['username'],
                'total_bytes_in'  => $bytesIn,
                'total_bytes_out' => $bytesOut,
                'total_bytes_gb'  => round(($bytesIn + $bytesOut) / 1073741824, 4),
                'session_count'   => (int) $row['session_count'],
            ];
        }

        return $rows;
    }
}
