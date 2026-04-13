<?php

class RadiusSessionService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get active sessions with optional filters.
     * Supported filters: username (LIKE), nas_ip (exact), framed_ip (exact).
     */
    public function getActiveSessions(array $filters = []): array {
        $where = ['status = :status'];
        $params = [':status' => 'active'];

        if (!empty($filters['username'])) {
            $where[] = 'username LIKE :username';
            $params[':username'] = '%' . $filters['username'] . '%';
        }
        if (!empty($filters['nas_ip'])) {
            $where[] = 'nas_ip = :nas_ip';
            $params[':nas_ip'] = $filters['nas_ip'];
        }
        if (!empty($filters['framed_ip'])) {
            $where[] = 'framed_ip = :framed_ip';
            $params[':framed_ip'] = $filters['framed_ip'];
        }

        $sql = 'SELECT id, username, nas_ip, framed_ip, start_time, bytes_in, bytes_out,
                       TIMESTAMPDIFF(SECOND, start_time, NOW()) AS duration_seconds
                FROM radius_sessions
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY start_time DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get summary stats for active sessions.
     */
    public function getSessionStats(): array {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total_active,
                    COALESCE(SUM(bytes_in), 0) AS total_bytes_in,
                    COALESCE(SUM(bytes_out), 0) AS total_bytes_out,
                    COUNT(DISTINCT nas_ip) AS unique_nas_count
             FROM radius_sessions
             WHERE status = 'active'"
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_active'     => (int) $row['total_active'],
            'total_bytes_in'   => (int) $row['total_bytes_in'],
            'total_bytes_out'  => (int) $row['total_bytes_out'],
            'unique_nas_count' => (int) $row['unique_nas_count'],
        ];
    }

    /**
     * Insert a new session record. Returns the inserted row ID.
     * Required keys: username, nas_ip, nas_port, session_id, framed_ip, start_time.
     */
    public function startSession(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO radius_sessions
                (username, nas_ip, nas_port, session_id, framed_ip, start_time, status)
             VALUES
                (:username, :nas_ip, :nas_port, :session_id, :framed_ip, :start_time, \'active\')'
        );
        $stmt->execute([
            ':username'   => $data['username'],
            ':nas_ip'     => $data['nas_ip'],
            ':nas_port'   => $data['nas_port'] ?? null,
            ':session_id' => $data['session_id'],
            ':framed_ip'  => $data['framed_ip'] ?? null,
            ':start_time' => $data['start_time'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update bytes_in / bytes_out for an active session.
     */
    public function updateSession(string $sessionId, array $data): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE radius_sessions
             SET bytes_in = :bytes_in, bytes_out = :bytes_out
             WHERE session_id = :session_id AND status = \'active\''
        );
        $stmt->execute([
            ':bytes_in'   => $data['bytes_in'],
            ':bytes_out'  => $data['bytes_out'],
            ':session_id' => $sessionId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Stop a session: set status, stop_time, terminate_cause, and final byte counts.
     */
    public function stopSession(string $sessionId, array $data): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE radius_sessions
             SET status = \'stopped\',
                 stop_time = :stop_time,
                 terminate_cause = :terminate_cause,
                 bytes_in = :bytes_in,
                 bytes_out = :bytes_out
             WHERE session_id = :session_id AND status = \'active\''
        );
        $stmt->execute([
            ':stop_time'       => $data['stop_time'],
            ':terminate_cause' => $data['terminate_cause'] ?? null,
            ':bytes_in'        => $data['bytes_in'] ?? 0,
            ':bytes_out'       => $data['bytes_out'] ?? 0,
            ':session_id'      => $sessionId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
