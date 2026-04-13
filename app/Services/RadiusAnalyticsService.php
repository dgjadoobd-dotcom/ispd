<?php

class RadiusAnalyticsService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Returns top users by total bytes (in+out) for the given period.
     * Period: 'today', 'week', 'month'.
     */
    public function getTopUsersByUsage(int $limit = 10, string $period = 'today'): array {
        $dateExpr = match ($period) {
            'week'  => 'DATE(start_time) >= DATE(NOW() - INTERVAL 7 DAY)',
            'month' => 'DATE(start_time) >= DATE(NOW() - INTERVAL 30 DAY)',
            default => 'DATE(start_time) = CURDATE()',
        };

        $stmt = $this->pdo->prepare(
            "SELECT username,
                    COALESCE(SUM(bytes_in), 0)  AS total_bytes_in,
                    COALESCE(SUM(bytes_out), 0) AS total_bytes_out,
                    COALESCE(SUM(bytes_in + bytes_out), 0) AS total_bytes,
                    COUNT(*) AS session_count
             FROM radius_sessions
             WHERE {$dateExpr}
             GROUP BY username
             ORDER BY total_bytes DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            return [
                'username'        => $row['username'],
                'total_bytes_in'  => (int) $row['total_bytes_in'],
                'total_bytes_out' => (int) $row['total_bytes_out'],
                'total_bytes'     => (int) $row['total_bytes'],
                'session_count'   => (int) $row['session_count'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Returns session start counts grouped by hour (0-23) for a given date (Y-m-d).
     * Always returns 24 elements.
     */
    public function getHourlySessionCounts(string $date): array {
        $stmt = $this->pdo->prepare(
            'SELECT HOUR(start_time) AS hour, COUNT(*) AS session_count
             FROM radius_sessions
             WHERE DATE(start_time) = :date
             GROUP BY HOUR(start_time)'
        );
        $stmt->execute([':date' => $date]);

        $byHour = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $byHour[(int) $row['hour']] = (int) $row['session_count'];
        }

        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[] = [
                'hour'          => $h,
                'session_count' => $byHour[$h] ?? 0,
            ];
        }
        return $result;
    }

    /**
     * Returns recent sessions for a user ordered by start_time DESC.
     */
    public function getUserSessionHistory(string $username, int $limit = 20): array {
        $stmt = $this->pdo->prepare(
            'SELECT session_id,
                    nas_ip,
                    framed_ip,
                    start_time,
                    stop_time,
                    bytes_in,
                    bytes_out,
                    TIMESTAMPDIFF(SECOND, start_time, COALESCE(stop_time, NOW())) AS duration_seconds,
                    terminate_cause,
                    status
             FROM radius_sessions
             WHERE username = :username
             ORDER BY start_time DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            return [
                'session_id'       => $row['session_id'],
                'nas_ip'           => $row['nas_ip'],
                'framed_ip'        => $row['framed_ip'],
                'start_time'       => $row['start_time'],
                'stop_time'        => $row['stop_time'],
                'bytes_in'         => (int) $row['bytes_in'],
                'bytes_out'        => (int) $row['bytes_out'],
                'duration_seconds' => (int) $row['duration_seconds'],
                'terminate_cause'  => $row['terminate_cause'],
                'status'           => $row['status'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Returns daily totals from radius_usage_daily for the given date range (inclusive).
     */
    public function getDailyUsageSummary(string $dateFrom, string $dateTo): array {
        $stmt = $this->pdo->prepare(
            'SELECT date,
                    SUM(bytes_in)       AS total_bytes_in,
                    SUM(bytes_out)      AS total_bytes_out,
                    SUM(session_count)  AS total_sessions,
                    COUNT(DISTINCT username) AS unique_users
             FROM radius_usage_daily
             WHERE date BETWEEN :date_from AND :date_to
             GROUP BY date
             ORDER BY date ASC'
        );
        $stmt->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);

        return array_map(static function (array $row): array {
            return [
                'date'            => $row['date'],
                'total_bytes_in'  => (int) $row['total_bytes_in'],
                'total_bytes_out' => (int) $row['total_bytes_out'],
                'total_sessions'  => (int) $row['total_sessions'],
                'unique_users'    => (int) $row['unique_users'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
