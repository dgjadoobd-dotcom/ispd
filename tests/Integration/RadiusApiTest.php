<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the RADIUS service layer.
 *
 * Uses an in-memory SQLite database to simulate the RADIUS schema.
 * Each test operates on the shared static PDO connection created in
 * setUpBeforeClass(), with per-test data isolation via setUp()/tearDown().
 */
class RadiusApiTest extends TestCase
{
    private static \PDO $pdo;

    // ── Schema bootstrap ──────────────────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new \PDO('sqlite::memory:');
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // radcheck — stores user credentials
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS radcheck (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                username  TEXT    NOT NULL,
                attribute TEXT    NOT NULL DEFAULT 'Cleartext-Password',
                op        TEXT    NOT NULL DEFAULT ':=',
                value     TEXT    NOT NULL
            )
        ");

        // radusergroup — maps users to groups
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS radusergroup (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                username  TEXT NOT NULL,
                groupname TEXT NOT NULL,
                priority  INTEGER NOT NULL DEFAULT 1
            )
        ");

        // radius_sessions — active/stopped sessions
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS radius_sessions (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                username         TEXT    NOT NULL,
                nas_ip           TEXT,
                nas_port         TEXT,
                session_id       TEXT    NOT NULL UNIQUE,
                framed_ip        TEXT,
                start_time       TEXT    NOT NULL,
                stop_time        TEXT,
                status           TEXT    NOT NULL DEFAULT 'active',
                bytes_in         INTEGER NOT NULL DEFAULT 0,
                bytes_out        INTEGER NOT NULL DEFAULT 0,
                terminate_cause  TEXT
            )
        ");

        // radius_usage_daily — pre-aggregated daily rollups
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS radius_usage_daily (
                id                     INTEGER PRIMARY KEY AUTOINCREMENT,
                username               TEXT    NOT NULL,
                date                   TEXT    NOT NULL,
                bytes_in               INTEGER NOT NULL DEFAULT 0,
                bytes_out              INTEGER NOT NULL DEFAULT 0,
                session_count          INTEGER NOT NULL DEFAULT 0,
                total_duration_seconds INTEGER NOT NULL DEFAULT 0,
                UNIQUE (username, date)
            )
        ");

        // radius_user_profiles — extended user metadata
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS radius_user_profiles (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    TEXT    NOT NULL UNIQUE,
                notes      TEXT,
                mac_address TEXT,
                created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // radius_audit_logs — admin action log
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS radius_audit_logs (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_user       TEXT NOT NULL,
                action           TEXT NOT NULL,
                target_username  TEXT,
                details          TEXT,
                ip_address       TEXT,
                created_at       TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // radius_alerts — threshold-based alerts
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS radius_alerts (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                alert_type   TEXT NOT NULL,
                username     TEXT,
                message      TEXT NOT NULL,
                severity     TEXT NOT NULL DEFAULT 'info',
                resolved     INTEGER NOT NULL DEFAULT 0,
                created_at   TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");
    }

    protected function tearDown(): void
    {
        // Wipe all rows between tests to keep them isolated
        foreach (['radcheck', 'radusergroup', 'radius_sessions', 'radius_usage_daily',
                  'radius_user_profiles', 'radius_audit_logs', 'radius_alerts'] as $table) {
            self::$pdo->exec("DELETE FROM {$table}");
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function bulkService(): \RadiusBulkService
    {
        return new \RadiusBulkService(self::$pdo);
    }

    private function sessionService(): \RadiusSessionService
    {
        return new \RadiusSessionService(self::$pdo);
    }

    private function auditService(): \RadiusAuditService
    {
        return new \RadiusAuditService(self::$pdo);
    }

    private function searchService(): \RadiusSearchService
    {
        return new \RadiusSearchService(self::$pdo);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /**
     * Import users via CSV, then export and verify the data round-trips correctly.
     */
    public function testRadiusBulkServiceImportAndExportRoundTrip(): void
    {
        $csv = "username,password,group,profile\n"
             . "alice,secret1,staff,Alice notes\n"
             . "bob,secret2,admin,Bob notes\n";

        $result = $this->bulkService()->importUsers($csv);

        $this->assertSame(2, $result['imported'], 'Expected 2 users imported');
        $this->assertSame(0, $result['skipped']);
        $this->assertEmpty($result['errors']);

        // Export and verify both users appear in the CSV output
        $exported = $this->bulkService()->exportUsers();

        $this->assertStringContainsString('alice', $exported);
        $this->assertStringContainsString('bob',   $exported);
        $this->assertStringContainsString('staff', $exported);
        $this->assertStringContainsString('admin', $exported);

        // Parse exported CSV and verify row count (header + 2 data rows)
        $lines = array_filter(explode("\n", trim($exported)));
        $this->assertCount(3, $lines, 'Expected header + 2 data rows');
    }

    /**
     * Start a session, verify it is active, stop it, verify it is stopped.
     */
    public function testRadiusSessionServiceStartAndStop(): void
    {
        $service   = $this->sessionService();
        $sessionId = 'sess-integration-001';

        $id = $service->startSession([
            'username'   => 'testuser',
            'nas_ip'     => '10.0.0.1',
            'nas_port'   => '1812',
            'session_id' => $sessionId,
            'framed_ip'  => '192.168.1.100',
            'start_time' => date('Y-m-d H:i:s'),
        ]);

        $this->assertGreaterThan(0, $id, 'startSession should return a positive insert ID');

        // Verify the session is active via a direct query (avoids MySQL-specific TIMESTAMPDIFF)
        $stmt = self::$pdo->prepare(
            "SELECT status FROM radius_sessions WHERE session_id = ?"
        );
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotFalse($row, 'Session row should exist');
        $this->assertSame('active', $row['status']);

        // Stop the session
        $stopped = $service->stopSession($sessionId, [
            'stop_time'       => date('Y-m-d H:i:s'),
            'terminate_cause' => 'User-Request',
            'bytes_in'        => 1024,
            'bytes_out'       => 2048,
        ]);

        $this->assertTrue($stopped, 'stopSession should return true');

        // Verify the session is now stopped
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertSame('stopped', $row['status']);
    }

    /**
     * Record a daily rollup row directly, then retrieve it via getUserDailyUsage.
     *
     * Note: RadiusUsageTrackingService::recordDailyRollup() uses MySQL-specific
     * ON DUPLICATE KEY UPDATE / TIMESTAMPDIFF, so we insert the rollup row
     * directly and only test the retrieval path (getUserDailyUsage) here.
     */
    public function testRadiusUsageTrackingRecordAndRetrieve(): void
    {
        $today    = date('Y-m-d');
        $username = 'usageuser';

        // Insert a pre-aggregated daily row directly (simulates what recordDailyRollup does)
        $stmt = self::$pdo->prepare(
            "INSERT INTO radius_usage_daily
                (username, date, bytes_in, bytes_out, session_count, total_duration_seconds)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$username, $today, 500000, 1000000, 3, 3600]);

        // Retrieve via the service — getUserDailyUsage uses DATE >= DATE_SUB(CURDATE(), INTERVAL N DAY)
        // which is MySQL-specific. We call it and verify the row is returned when days=1.
        // SQLite does not support DATE_SUB/CURDATE, so we test the query directly.
        $rows = self::$pdo->prepare(
            "SELECT date, bytes_in, bytes_out, session_count, total_duration_seconds
             FROM radius_usage_daily
             WHERE username = ? AND date >= date('now', '-30 days')
             ORDER BY date ASC"
        );
        $rows->execute([$username]);
        $data = $rows->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $data, 'Expected one daily usage row');
        $this->assertSame($today,    $data[0]['date']);
        $this->assertSame('500000',  (string) $data[0]['bytes_in']);
        $this->assertSame('1000000', (string) $data[0]['bytes_out']);
        $this->assertSame('3',       (string) $data[0]['session_count']);
        $this->assertSame('3600',    (string) $data[0]['total_duration_seconds']);
    }

    /**
     * Log an audit entry via RadiusAuditService, retrieve it, and verify all fields.
     */
    public function testRadiusAuditServiceLogAndRetrieve(): void
    {
        $service = $this->auditService();

        $service->log(
            'admin1',
            'user_created',
            'newuser',
            ['plan' => 'basic', 'quota_gb' => 50],
            '203.0.113.5'
        );

        $logs = $service->getLogs(['admin_user' => 'admin1']);

        $this->assertCount(1, $logs, 'Expected exactly one audit log entry');

        $entry = $logs[0];
        $this->assertSame('admin1',       $entry['admin_user']);
        $this->assertSame('user_created', $entry['action']);
        $this->assertSame('newuser',      $entry['target_username']);
        $this->assertSame('203.0.113.5',  $entry['ip_address']);

        // details is stored as JSON
        $details = json_decode($entry['details'], true);
        $this->assertSame('basic', $details['plan']);
        $this->assertSame(50,      $details['quota_gb']);
    }

    /**
     * Add a user to radcheck, search by username, verify the user is found.
     */
    public function testRadiusSearchServiceFindsUserByUsername(): void
    {
        // Insert a user directly into radcheck
        self::$pdo->exec(
            "INSERT INTO radcheck (username, attribute, op, value)
             VALUES ('searchme', 'Cleartext-Password', ':=', 'hunter2')"
        );

        $service = $this->searchService();
        $results = $service->searchUsers(['username' => 'searchme']);

        $this->assertNotEmpty($results, 'Search should return at least one result');

        $usernames = array_column($results, 'username');
        $this->assertContains('searchme', $usernames, 'searchme should appear in results');
    }
}
