<?php

class RateLimiterService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if the request is allowed (under limit).
     * Returns true if allowed, false if rate limited.
     */
    public function check(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT hits, window_start FROM rate_limit_hits WHERE `key` = :key LIMIT 1'
        );
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return true;
        }

        $windowAge = time() - strtotime($row['window_start']);
        if ($windowAge >= $windowSeconds) {
            // Window expired — reset and allow
            $this->reset($key);
            return true;
        }

        return (int) $row['hits'] < $maxAttempts;
    }

    /**
     * Increment hit count for a key within the window.
     * Returns the current hit count after increment.
     */
    public function increment(string $key, int $windowSeconds): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limit_hits (`key`, hits, window_start)
             VALUES (:key, 1, NOW())
             ON DUPLICATE KEY UPDATE
               hits = IF(TIMESTAMPDIFF(SECOND, window_start, NOW()) >= :window, 1, hits + 1),
               window_start = IF(TIMESTAMPDIFF(SECOND, window_start, NOW()) >= :window2, NOW(), window_start)'
        );
        $stmt->execute([
            ':key'     => $key,
            ':window'  => $windowSeconds,
            ':window2' => $windowSeconds,
        ]);

        $stmt = $this->pdo->prepare(
            'SELECT hits FROM rate_limit_hits WHERE `key` = :key LIMIT 1'
        );
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['hits'] : 1;
    }

    /**
     * Clear rate limit data for a key.
     */
    public function reset(string $key): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM rate_limit_hits WHERE `key` = :key');
        $stmt->execute([':key' => $key]);
    }

    /**
     * Convenience method for IP-based blocking.
     */
    public function isBlocked(string $ipAddress, int $maxAttempts = 100, int $windowSeconds = 60): bool
    {
        $key = 'ip:' . $ipAddress;
        return !$this->check($key, $maxAttempts, $windowSeconds);
    }
}
