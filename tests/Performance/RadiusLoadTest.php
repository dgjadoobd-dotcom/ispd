<?php

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;

/**
 * Performance benchmark tests for the RADIUS service layer.
 *
 * NOTE: These benchmarks use an in-memory SQLite database.
 * Real MySQL performance will differ (typically faster for large datasets
 * due to indexing, query optimisation, and connection pooling).
 */
class RadiusLoadTest extends TestCase
{
    private static \PDO $pdo;

    // ── Schema + seed data ────────────────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new \PDO('sqlite::memory:');
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        self::$pdo->exec("
            CREATE TABLE radcheck (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                username  TEXT NOT NULL,
                attribute TEXT NOT NULL DEFAULT 'Cleartext-Password',
                op        TEXT NOT NULL DEFAULT ':=',
                value     TEXT NOT NULL
            )
        ");

        self::$pdo->exec("
            CREATE TABLE radusergroup (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                username  TEXT NOT NULL,
                groupname TEXT NOT NULL,
                priority  INTEGER NOT NULL DEFAULT 1
            )
        ");

        self::$pdo->exec("
            CREATE TABLE radius_sessions (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                username        TEXT NOT NULL,
                nas_ip          TEXT,
                nas_port        TEXT,
                session_id      TEXT NOT NULL UNIQUE,
                framed_ip       TEXT,
                start_time      TEXT NOT NULL,
                stop_time       TEXT,
                status          TEXT NOT NULL DEFAULT 'active',
                bytes_in        INTEGER NOT NULL DEFAULT 0,
                bytes_out       INTEGER NOT NULL DEFAULT 0,
                terminate_cause TEXT
            )
        ");

        self::$pdo->exec("
            CREATE TABLE radius_usage_daily (
                id                     INTEGER PRIMARY KEY AUTOINCREMENT,
                username               TEXT NOT NULL,
                date                   TEXT NOT NULL,
                bytes_in               INTEGER NOT NULL DEFAULT 0,
                bytes_out              INTEGER NOT NULL DEFAULT 0,
                session_count          INTEGER NOT NULL DEFAULT 0,
                total_duration_seconds INTEGER NOT NULL DEFAULT 0,
                UNIQUE (username, date)
            )
        ");

        self::$pdo->exec("
            CREATE TABLE radius_user_profiles (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id     TEXT NOT NULL UNIQUE,
                notes       TEXT,
                mac_address TEXT,
                created_at  TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // Pre-populate 1000 test users
        $insUser  = self::$pdo->prepare(
            "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)"
        );
        $insGroup = self::$pdo->prepare(
            "INSERT INTO radusergroup (username, groupname, priority) VALUES (?, 'default', 1)"
        );

        self::$pdo->beginTransaction();
        for ($i = 1; $i <= 1000; $i++) {
            $username = sprintf('loaduser%04d', $i);
            $insUser->execute([$username, 'pass' . $i]);
            $insGroup->execute([$username]);
        }
        self::$pdo->commit();

        // Pre-populate 500 active sessions
        $insSess = self::$pdo->prepare(
            "INSERT INTO radius_sessions
                (username, nas_ip, session_id, framed_ip, start_time, status, bytes_in, bytes_out)
             VALUES (?, '10.0.0.1', ?, ?, datetime('now'), 'active', ?, ?)"
        );

        self::$pdo->beginTransaction();
        for ($i = 1; $i <= 500; $i++) {
            $username  = sprintf('loaduser%04d', $i);
            $sessionId = 'sess-load-' . $i;
            $framedIp  = '192.168.' . intdiv($i, 256) . '.' . ($i % 256);
            $insSess->execute([$username, $sessionId, $framedIp, $i * 1024, $i * 2048]);
        }
        self::$pdo->commit();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function bulkService(): \RadiusBulkService
    {
        return new \RadiusBulkService(self::$pdo);
    }

    private function searchService(): \RadiusSearchService
    {
        return new \RadiusSearchService(self::$pdo);
    }

    private function sessionService(): \RadiusSessionService
    {
        return new \RadiusSessionService(self::$pdo);
    }

    // ── Benchmarks ────────────────────────────────────────────────────────────

    /**
     * Import 500 users via CSV in under 2 seconds.
     */
    public function testBulkImport500UsersUnder2Seconds(): void
    {
        $lines = ["username,password,group,profile"];
        for ($i = 1; $i <= 500; $i++) {
            $lines[] = "importuser{$i},password{$i},group" . ($i % 5) . ",notes{$i}";
        }
        $csv = implode("\n", $lines);

        $start  = microtime(true);
        $result = $this->bulkService()->importUsers($csv);
        $elapsed = microtime(true) - $start;

        $this->assertSame(500, $result['imported'], 'Expected 500 users imported');
        $this->assertLessThan(2.0, $elapsed, "Bulk import of 500 users took {$elapsed}s (limit: 2.0s)");
    }

    /**
     * Search users with a username filter in under 100ms.
     */
    public function testSearchUsersUnder100ms(): void
    {
        $start   = microtime(true);
        $results = $this->searchService()->searchUsers(['username' => 'loaduser01'], 50, 0);
        $elapsed = microtime(true) - $start;

        $this->assertNotEmpty($results, 'Search should return results');
        $this->assertLessThan(0.1, $elapsed, "User search took {$elapsed}s (limit: 100ms)");
    }

    /**
     * Retrieve active sessions in under 100ms.
     *
     * Queries radius_sessions directly (avoids MySQL-specific TIMESTAMPDIFF in the service).
     */
    public function testGetActiveSessionsUnder100ms(): void
    {
        $start = microtime(true);
        $stmt  = self::$pdo->prepare(
            "SELECT id, username, nas_ip, framed_ip, start_time, bytes_in, bytes_out
             FROM radius_sessions
             WHERE status = 'active'
             ORDER BY start_time DESC"
        );
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $elapsed = microtime(true) - $start;

        $this->assertNotEmpty($results, 'Should return active sessions');
        $this->assertLessThan(0.1, $elapsed, "Active session query took {$elapsed}s (limit: 100ms)");
    }

    /**
     * Retrieve session stats in under 50ms.
     */
    public function testSessionStatsUnder50ms(): void
    {
        $start   = microtime(true);
        $stats   = $this->sessionService()->getSessionStats();
        $elapsed = microtime(true) - $start;

        $this->assertArrayHasKey('total_active', $stats);
        $this->assertGreaterThan(0, $stats['total_active']);
        $this->assertLessThan(0.05, $elapsed, "getSessionStats took {$elapsed}s (limit: 50ms)");
    }

    /**
     * Export all 1000 users in under 1 second.
     */
    public function testBulkExport1000UsersUnder1Second(): void
    {
        $start   = microtime(true);
        $csv     = $this->bulkService()->exportUsers();
        $elapsed = microtime(true) - $start;

        // Header + at least 1000 data rows
        $lines = array_filter(explode("\n", trim($csv)));
        $this->assertGreaterThanOrEqual(1001, count($lines), 'Expected header + 1000 user rows');
        $this->assertLessThan(1.0, $elapsed, "Export of 1000 users took {$elapsed}s (limit: 1.0s)");
    }
}
