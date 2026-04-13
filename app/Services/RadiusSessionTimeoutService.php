<?php

class RadiusSessionTimeoutService {
    private PDO $pdo;
    private RadiusSessionService $sessionService;

    public function __construct(PDO $pdo, RadiusSessionService $sessionService) {
        $this->pdo            = $pdo;
        $this->sessionService = $sessionService;
    }

    /**
     * Returns active sessions that have exceeded the timeout threshold.
     */
    public function getTimedOutSessions(int $timeoutMinutes = 1440): array {
        $stmt = $this->pdo->prepare(
            'SELECT session_id, username, nas_ip, start_time,
                    TIMESTAMPDIFF(MINUTE, start_time, NOW()) AS duration_minutes
             FROM radius_sessions
             WHERE status = \'active\'
               AND TIMESTAMPDIFF(MINUTE, start_time, NOW()) >= :timeout'
        );
        $stmt->execute([':timeout' => $timeoutMinutes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Terminates a single session by session_id.
     */
    public function terminateSession(string $sessionId, string $reason = 'Session-Timeout'): bool {
        return $this->sessionService->stopSession($sessionId, [
            'stop_time'       => date('Y-m-d H:i:s'),
            'terminate_cause' => $reason,
            'bytes_in'        => 0,
            'bytes_out'       => 0,
        ]);
    }

    /**
     * Finds and terminates all timed-out sessions.
     * Returns ['terminated' => int, 'errors' => array].
     */
    public function terminateTimedOutSessions(int $timeoutMinutes = 1440): array {
        $sessions   = $this->getTimedOutSessions($timeoutMinutes);
        $terminated = 0;
        $errors     = [];

        foreach ($sessions as $session) {
            try {
                if ($this->terminateSession($session['session_id'])) {
                    $terminated++;
                } else {
                    $errors[] = "Failed to terminate session {$session['session_id']} for {$session['username']}";
                }
            } catch (\Exception $e) {
                $errors[] = "Error terminating session {$session['session_id']}: " . $e->getMessage();
            }
        }

        return ['terminated' => $terminated, 'errors' => $errors];
    }

    /**
     * Terminates all active sessions for a given username.
     * Returns the count of terminated sessions.
     */
    public function terminateUserSessions(string $username, string $reason = 'Admin-Reset'): int {
        $stmt = $this->pdo->prepare(
            'SELECT session_id FROM radius_sessions
             WHERE username = :username AND status = \'active\''
        );
        $stmt->execute([':username' => $username]);
        $sessions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $terminated = 0;
        foreach ($sessions as $sessionId) {
            if ($this->terminateSession($sessionId, $reason)) {
                $terminated++;
            }
        }

        return $terminated;
    }
}
