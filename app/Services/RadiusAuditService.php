<?php

class RadiusAuditService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Insert an audit log entry.
     */
    public function log(
        string $adminUser,
        string $action,
        ?string $targetUsername,
        array $details = [],
        ?string $ipAddress = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO radius_audit_logs (admin_user, action, target_username, details, ip_address)
             VALUES (:admin_user, :action, :target_username, :details, :ip_address)'
        );
        $stmt->execute([
            ':admin_user'       => $adminUser,
            ':action'           => $action,
            ':target_username'  => $targetUsername,
            ':details'          => empty($details) ? null : json_encode($details),
            ':ip_address'       => $ipAddress,
        ]);
    }

    /**
     * Query audit logs with optional filters.
     *
     * Supported $filters keys:
     *   admin_user       (exact match)
     *   action           (exact match)
     *   target_username  (exact match)
     *   date_from        (Y-m-d, inclusive)
     *   date_to          (Y-m-d, inclusive)
     */
    public function getLogs(array $filters = [], int $limit = 50, int $offset = 0): array {
        $where  = [];
        $params = [];

        if (!empty($filters['admin_user'])) {
            $where[]  = 'admin_user = :admin_user';
            $params[':admin_user'] = $filters['admin_user'];
        }
        if (!empty($filters['action'])) {
            $where[]  = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['target_username'])) {
            $where[]  = 'target_username = :target_username';
            $params[':target_username'] = $filters['target_username'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = 'SELECT * FROM radius_audit_logs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent audit logs for a specific target username.
     */
    public function getLogsForUser(string $username, int $limit = 20): array {
        return $this->getLogs(['target_username' => $username], $limit);
    }
}
