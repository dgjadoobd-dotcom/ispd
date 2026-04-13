<?php

class RadiusSessionPersistenceService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Sync active radacct records into radius_sessions via upsert.
     * Returns ['synced' => int, 'errors' => array].
     */
    public function syncFromRadacct(): array {
        $stmt = $this->pdo->prepare(
            "SELECT acctuniqueid, username, nasipaddress, nasportid,
                    framedipaddress, acctstarttime, acctinputoctets, acctoutputoctets
             FROM radacct
             WHERE acctstoptime IS NULL"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $synced = 0;
        $errors = [];

        $upsert = $this->pdo->prepare(
            "INSERT INTO radius_sessions
                (session_id, username, nas_ip, nas_port, framed_ip, start_time, bytes_in, bytes_out, status)
             VALUES
                (:session_id, :username, :nas_ip, :nas_port, :framed_ip, :start_time, :bytes_in, :bytes_out, 'active')
             ON DUPLICATE KEY UPDATE
                username   = VALUES(username),
                nas_ip     = VALUES(nas_ip),
                nas_port   = VALUES(nas_port),
                framed_ip  = VALUES(framed_ip),
                start_time = VALUES(start_time),
                bytes_in   = VALUES(bytes_in),
                bytes_out  = VALUES(bytes_out),
                status     = 'active'"
        );

        foreach ($rows as $row) {
            try {
                $upsert->execute([
                    ':session_id' => $row['acctuniqueid'],
                    ':username'   => $row['username'],
                    ':nas_ip'     => $row['nasipaddress'],
                    ':nas_port'   => $row['nasportid'],
                    ':framed_ip'  => $row['framedipaddress'],
                    ':start_time' => $row['acctstarttime'],
                    ':bytes_in'   => (int) $row['acctinputoctets'],
                    ':bytes_out'  => (int) $row['acctoutputoctets'],
                ]);
                $synced++;
            } catch (\PDOException $e) {
                $errors[] = ['session_id' => $row['acctuniqueid'], 'error' => $e->getMessage()];
            }
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Mark recently stopped radacct sessions as stopped in radius_sessions.
     * Returns ['updated' => int].
     */
    public function syncStoppedSessions(): array {
        $stmt = $this->pdo->prepare(
            "SELECT acctuniqueid, acctstoptime, acctterminatecause
             FROM radacct
             WHERE acctstoptime IS NOT NULL
               AND acctstoptime >= NOW() - INTERVAL 1 HOUR"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updated = 0;

        $update = $this->pdo->prepare(
            "UPDATE radius_sessions
             SET status = 'stopped',
                 stop_time = :stop_time,
                 terminate_cause = :terminate_cause
             WHERE session_id = :session_id
               AND status = 'active'"
        );

        foreach ($rows as $row) {
            $update->execute([
                ':stop_time'       => $row['acctstoptime'],
                ':terminate_cause' => $row['acctterminatecause'],
                ':session_id'      => $row['acctuniqueid'],
            ]);
            $updated += $update->rowCount();
        }

        return ['updated' => $updated];
    }

    /**
     * Fetch a single session from radius_sessions by session_id.
     */
    public function getSessionBySessionId(string $sessionId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM radius_sessions WHERE session_id = :session_id LIMIT 1"
        );
        $stmt->execute([':session_id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
}
