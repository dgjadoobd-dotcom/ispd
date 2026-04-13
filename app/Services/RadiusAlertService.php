<?php

class RadiusAlertService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Insert a new alert record. Returns the inserted row ID.
     * Severity must be 'critical', 'warning', or 'info'.
     */
    public function createAlert(string $alertType, string $severity, string $message, array $context = []): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO radius_alerts (alert_type, severity, message, context)
             VALUES (:alert_type, :severity, :message, :context)'
        );
        $stmt->execute([
            ':alert_type' => $alertType,
            ':severity'   => $severity,
            ':message'    => $message,
            ':context'    => empty($context) ? null : json_encode($context),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mark an alert as resolved by setting resolved_at = NOW().
     * Returns true if a row was updated.
     */
    public function resolveAlert(int $alertId): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE radius_alerts SET resolved_at = NOW() WHERE id = :id AND resolved_at IS NULL'
        );
        $stmt->execute([':id' => $alertId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Return unresolved alerts (resolved_at IS NULL), newest first.
     * Optionally filter by severity ('critical', 'warning', 'info').
     */
    public function getUnresolvedAlerts(string $severity = null): array {
        $sql    = 'SELECT * FROM radius_alerts WHERE resolved_at IS NULL';
        $params = [];

        if ($severity !== null) {
            $sql           .= ' AND severity = :severity';
            $params[':severity'] = $severity;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a 'warning' alert if active session count exceeds $threshold,
     * unless an unresolved 'high_session_count' alert already exists.
     */
    public function checkHighSessionCount(int $threshold = 1000): void {
        // Avoid duplicate unresolved alerts
        $dup = $this->pdo->prepare(
            "SELECT COUNT(*) FROM radius_alerts
             WHERE alert_type = 'high_session_count' AND resolved_at IS NULL"
        );
        $dup->execute();
        if ((int) $dup->fetchColumn() > 0) {
            return;
        }

        $count = $this->pdo->prepare(
            "SELECT COUNT(*) FROM radius_sessions WHERE status = 'active'"
        );
        $count->execute();
        $active = (int) $count->fetchColumn();

        if ($active >= $threshold) {
            $this->createAlert(
                'high_session_count',
                'warning',
                "Active session count ({$active}) has exceeded the threshold of {$threshold}.",
                ['active_sessions' => $active, 'threshold' => $threshold]
            );
        }
    }

    /**
     * Create a 'critical' alert if failed auth count in the last $windowMinutes
     * minutes meets or exceeds $threshold.
     */
    public function checkFailedAuthRate(PDO $pdo, int $windowMinutes = 5, int $threshold = 50): void {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM radpostauth
             WHERE reply = 'Access-Reject'
               AND authdate >= DATE_SUB(NOW(), INTERVAL :window MINUTE)"
        );
        $stmt->execute([':window' => $windowMinutes]);
        $failed = (int) $stmt->fetchColumn();

        if ($failed >= $threshold) {
            $this->createAlert(
                'high_failed_auth_rate',
                'critical',
                "Failed authentication count ({$failed}) in the last {$windowMinutes} minute(s) has reached the threshold of {$threshold}.",
                ['failed_count' => $failed, 'window_minutes' => $windowMinutes, 'threshold' => $threshold]
            );
        }
    }
}
