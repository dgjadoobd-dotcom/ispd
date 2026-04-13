<?php

class RadiusUsageTrackingService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Returns aggregated usage totals for a user from radius_sessions
     * for the given date range (inclusive).
     */
    public function getUserUsage(string $username, string $dateFrom, string $dateTo): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                :username AS username,
                COALESCE(SUM(bytes_in), 0)  AS total_bytes_in,
                COALESCE(SUM(bytes_out), 0) AS total_bytes_out,
                COALESCE(SUM(bytes_in + bytes_out), 0) AS total_bytes,
                COUNT(*) AS session_count,
                COALESCE(SUM(
                    TIMESTAMPDIFF(SECOND, start_time, COALESCE(stop_time, NOW()))
                ), 0) AS total_duration_seconds
             FROM radius_sessions
             WHERE username = :username2
               AND DATE(start_time) >= :date_from
               AND DATE(start_time) <= :date_to"
        );
        $stmt->execute([
            ':username'  => $username,
            ':username2' => $username,
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'username'               => $row['username'],
            'total_bytes_in'         => (int) $row['total_bytes_in'],
            'total_bytes_out'        => (int) $row['total_bytes_out'],
            'total_bytes'            => (int) $row['total_bytes'],
            'session_count'          => (int) $row['session_count'],
            'total_duration_seconds' => (int) $row['total_duration_seconds'],
        ];
    }

    /**
     * Shorthand for getUserUsage covering the current calendar month
     * (first day of month to today).
     */
    public function getCurrentMonthUsage(string $username): array {
        $dateFrom = date('Y-m-01');
        $dateTo   = date('Y-m-d');
        return $this->getUserUsage($username, $dateFrom, $dateTo);
    }

    /**
     * Aggregates radius_sessions data for the given date (Y-m-d) and
     * upserts one row per username into radius_usage_daily.
     * Returns ['processed' => int] with the number of rows upserted.
     */
    public function recordDailyRollup(string $date): array {
        $stmt = $this->pdo->prepare(
            "INSERT INTO radius_usage_daily
                (username, date, bytes_in, bytes_out, session_count, total_duration_seconds)
             SELECT
                username,
                :date,
                COALESCE(SUM(bytes_in), 0),
                COALESCE(SUM(bytes_out), 0),
                COUNT(*),
                COALESCE(SUM(
                    TIMESTAMPDIFF(SECOND, start_time, COALESCE(stop_time, NOW()))
                ), 0)
             FROM radius_sessions
             WHERE DATE(start_time) = :date2
             GROUP BY username
             ON DUPLICATE KEY UPDATE
                bytes_in               = VALUES(bytes_in),
                bytes_out              = VALUES(bytes_out),
                session_count          = VALUES(session_count),
                total_duration_seconds = VALUES(total_duration_seconds)"
        );
        $stmt->execute([':date' => $date, ':date2' => $date]);

        // rowCount returns 1 per insert and 2 per update for ON DUPLICATE KEY UPDATE.
        // Normalise to actual rows processed.
        $affected = $stmt->rowCount();
        $processed = (int) ceil($affected / 2) ?: $affected;

        return ['processed' => $processed];
    }

    /**
     * Returns daily usage rows from radius_usage_daily for the last N days
     * for the given user.
     */
    public function getUserDailyUsage(string $username, int $days = 30): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                date,
                bytes_in,
                bytes_out,
                session_count,
                total_duration_seconds
             FROM radius_usage_daily
             WHERE username = :username
               AND date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             ORDER BY date ASC"
        );
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            return [
                'date'                   => $row['date'],
                'bytes_in'               => (int) $row['bytes_in'],
                'bytes_out'              => (int) $row['bytes_out'],
                'session_count'          => (int) $row['session_count'],
                'total_duration_seconds' => (int) $row['total_duration_seconds'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
