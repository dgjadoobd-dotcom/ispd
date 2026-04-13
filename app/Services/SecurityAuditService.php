<?php

class SecurityAuditService {
    private RadiusAuditService $auditService;
    private PDO $pdo;

    public function __construct(RadiusAuditService $auditService, PDO $pdo) {
        $this->auditService = $auditService;
        $this->pdo = $pdo;
    }

    /**
     * Log a login attempt (success or failure).
     */
    public function logAuthAttempt(string $username, bool $success, string $ipAddress, array $context = []): void {
        $details = array_merge(['success' => $success], $context);
        $this->auditService->log('system', 'auth_attempt', $username, $details, $ipAddress);
    }

    /**
     * Log a password change event.
     */
    public function logPasswordChange(string $adminUser, string $targetUsername, string $ipAddress): void {
        $this->auditService->log($adminUser, 'password_change', $targetUsername, [], $ipAddress);
    }

    /**
     * Log a forced session termination.
     */
    public function logSessionKill(string $adminUser, string $targetUsername, string $sessionId, string $ipAddress): void {
        $this->auditService->log($adminUser, 'session_kill', $targetUsername, ['session_id' => $sessionId], $ipAddress);
    }

    /**
     * Log suspicious activity (e.g. too many failed logins).
     */
    public function logSuspiciousActivity(string $ipAddress, string $description, array $context = []): void {
        $details = array_merge(['description' => $description], $context);
        $this->auditService->log('system', 'suspicious_activity', null, $details, $ipAddress);
    }

    /**
     * Return recent failed auth attempts from the last N minutes.
     */
    public function getRecentFailedLogins(int $minutes = 60, int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM radius_audit_logs
             WHERE action = :action
               AND created_at >= :since
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':action', 'auth_attempt');
        $stmt->bindValue(':since', date('Y-m-d H:i:s', time() - $minutes * 60));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter to only failed attempts (success === false in details JSON)
        return array_values(array_filter($rows, function (array $row): bool {
            $details = json_decode($row['details'] ?? '{}', true);
            return isset($details['success']) && $details['success'] === false;
        }));
    }
}
