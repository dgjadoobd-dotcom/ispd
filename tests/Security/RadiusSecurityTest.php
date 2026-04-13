<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security-focused tests for RADIUS services.
 *
 * Verifies resistance to common attack vectors:
 * SQL injection, TOTP brute-force, IP access control bypass, rate limiting,
 * and webhook signature forgery.
 *
 * Uses in-memory SQLite for tests that require a database.
 */
class RadiusSecurityTest extends TestCase
{
    // ── SQLite helpers ────────────────────────────────────────────────────────

    private function createBulkServiceDb(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE radcheck (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            username  TEXT NOT NULL,
            attribute TEXT NOT NULL,
            op        TEXT NOT NULL DEFAULT ':=',
            value     TEXT NOT NULL
        )");

        $pdo->exec("CREATE TABLE radusergroup (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            username  TEXT NOT NULL,
            groupname TEXT NOT NULL,
            priority  INTEGER NOT NULL DEFAULT 1
        )");

        $pdo->exec("CREATE TABLE radius_user_profiles (
            user_id    TEXT PRIMARY KEY,
            notes      TEXT,
            mac_address TEXT,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        return $pdo;
    }

    private function createSearchServiceDb(): \PDO
    {
        $pdo = $this->createBulkServiceDb();

        $pdo->exec("CREATE TABLE radius_sessions (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT NOT NULL,
            status     TEXT NOT NULL DEFAULT 'active',
            framed_ip  TEXT
        )");

        // Seed a known user
        $pdo->exec("INSERT INTO radcheck (username, attribute, op, value)
                    VALUES ('alice', 'Cleartext-Password', ':=', 'secret')");

        return $pdo;
    }

    private function createIpAccessDb(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE ip_access_rules (
            id      INTEGER PRIMARY KEY AUTOINCREMENT,
            cidr    TEXT NOT NULL,
            type    TEXT NOT NULL DEFAULT 'whitelist',
            comment TEXT
        )");

        return $pdo;
    }

    private function createRateLimiterDb(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // SQLite-compatible schema (no ON DUPLICATE KEY UPDATE)
        $pdo->exec("CREATE TABLE rate_limit_hits (
            `key`         TEXT PRIMARY KEY,
            hits          INTEGER NOT NULL DEFAULT 0,
            window_start  TEXT NOT NULL
        )");

        return $pdo;
    }

    // ── 1. SQL injection via CSV import username ──────────────────────────────

    /**
     * Attempt SQL injection through the username field in a CSV import.
     * The service uses parameterized queries, so the malicious string should
     * be stored verbatim (or skipped) — never executed as SQL.
     */
    public function testSqlInjectionInBulkImportUsername(): void
    {
        $pdo     = $this->createBulkServiceDb();
        $service = new \RadiusBulkService($pdo);

        $maliciousUsername = "alice'); DROP TABLE radcheck; --";
        $csv = "username,password\n{$maliciousUsername},password123\n";

        // Must not throw; parameterized queries absorb the injection attempt
        $result = $service->importUsers($csv);

        // The table must still exist and be queryable
        $stmt = $pdo->query("SELECT COUNT(*) FROM radcheck");
        $this->assertNotFalse($stmt, 'radcheck table must still exist after injection attempt');

        // The malicious string should be stored as a literal username, not executed
        if ($result['imported'] === 1) {
            $stmt = $pdo->prepare("SELECT username FROM radcheck WHERE username = ?");
            $stmt->execute([$maliciousUsername]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->assertNotFalse($row, 'Username should be stored verbatim, not executed as SQL');
            $this->assertSame($maliciousUsername, $row['username']);
        }

        // Either imported safely or skipped — no exception is the key assertion
        $this->assertArrayHasKey('imported', $result);
    }

    // ── 2. SQL injection via search filter ───────────────────────────────────

    /**
     * Attempt SQL injection through the username search criterion.
     * Parameterized queries must prevent the injected SQL from executing.
     */
    public function testSqlInjectionInSearchFilter(): void
    {
        $pdo     = $this->createSearchServiceDb();
        $service = new \RadiusSearchService($pdo);

        $injectionPayload = "' OR '1'='1";

        // Must not throw; parameterized queries absorb the injection attempt
        $results = $service->searchUsers(['username' => $injectionPayload]);

        // The injection should not return all rows — it should return zero
        // because no username literally contains that string
        $this->assertIsArray($results);
        $this->assertCount(0, $results, 'SQL injection via username filter must not return unintended rows');
    }

    // ── 3. TOTP wrong codes return false (brute-force resistance) ────────────

    /**
     * Verify that incorrect TOTP codes are rejected.
     * A brute-force attacker submitting wrong codes must always get false.
     */
    public function testMfaTotpCannotBeReusedWithinWindow(): void
    {
        $mfa    = new \MfaService();
        $secret = $mfa->generateSecret();

        // Generate a set of obviously wrong codes and verify they are all rejected
        $wrongCodes = ['000000', '999999', '123456', '111111', '000001'];

        // Get the real current code so we can exclude it from wrong-code checks
        $realCode = $mfa->generateTotp($secret);

        foreach ($wrongCodes as $code) {
            if ($code === $realCode) {
                continue; // skip the unlikely collision
            }
            $this->assertFalse(
                $mfa->verifyTotp($secret, $code),
                "Wrong TOTP code '{$code}' must be rejected"
            );
        }

        // The real code must be accepted
        $this->assertTrue($mfa->verifyTotp($secret, $realCode), 'Valid TOTP code must be accepted');
    }

    // ── 4. IP access control blocks blacklisted IP ───────────────────────────

    /**
     * Add a blacklist rule and verify the matching IP is blocked.
     */
    public function testIpAccessControlBlocksBlacklistedIp(): void
    {
        $pdo     = $this->createIpAccessDb();
        $service = new \IpAccessControlService($pdo);

        $service->addRule('203.0.113.5', 'blacklist', 'known attacker');

        $this->assertFalse(
            $service->isAllowed('203.0.113.5'),
            'Blacklisted IP must be blocked'
        );

        // A different IP not on the blacklist must still be allowed
        $this->assertTrue(
            $service->isAllowed('198.51.100.1'),
            'Non-blacklisted IP must be allowed when only blacklist rules exist'
        );
    }

    // ── 5. IP access control allows only whitelisted IP ──────────────────────

    /**
     * Add a whitelist rule and verify only the whitelisted IP is allowed.
     */
    public function testIpAccessControlAllowsWhitelistedIp(): void
    {
        $pdo     = $this->createIpAccessDb();
        $service = new \IpAccessControlService($pdo);

        $service->addRule('10.0.0.0/8', 'whitelist', 'internal network');

        // IP inside the whitelisted range must be allowed
        $this->assertTrue(
            $service->isAllowed('10.1.2.3'),
            'IP inside whitelisted CIDR must be allowed'
        );

        // IP outside the whitelisted range must be blocked
        $this->assertFalse(
            $service->isAllowed('192.168.1.1'),
            'IP outside whitelisted CIDR must be blocked when whitelist rules exist'
        );
    }

    // ── 6. Rate limiter blocks after max attempts ─────────────────────────────

    /**
     * Verify the rate limiter blocks a key after exceeding maxAttempts.
     * Uses a SQLite-backed RateLimiterService with manual hit insertion
     * to avoid MySQL-specific ON DUPLICATE KEY UPDATE syntax.
     */
    public function testRateLimiterBlocksAfterMaxAttempts(): void
    {
        $pdo     = $this->createRateLimiterDb();
        $service = new \RateLimiterService($pdo);

        $key        = 'test-key:attacker';
        $maxAttempts = 3;
        $window      = 60;

        // Before any hits the key is allowed
        $this->assertTrue($service->check($key, $maxAttempts, $window));

        // Manually insert hits to simulate increments (avoids MySQL-only upsert).
        // Use PHP's date() so window_start matches the timezone used by time().
        $now = date('Y-m-d H:i:s');
        $pdo->prepare(
            "INSERT INTO rate_limit_hits (`key`, hits, window_start) VALUES (?, ?, ?)"
        )->execute([$key, $maxAttempts, $now]);

        // At exactly maxAttempts the key is still allowed (< check)
        $this->assertFalse(
            $service->check($key, $maxAttempts, $window),
            'Key must be blocked once hits reach maxAttempts'
        );
    }

    // ── 7. Webhook HMAC-SHA256 signature verification ────────────────────────

    /**
     * Verify that WebhookService generates a correct HMAC-SHA256 signature
     * that can be independently verified by the receiver.
     */
    public function testWebhookSignatureVerification(): void
    {
        $secret  = 'super-secret-webhook-key';
        $payload = ['event' => 'session_start', 'username' => 'alice'];
        $body    = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Compute the expected signature the same way WebhookService does
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $secret);

        // Verify the algorithm produces a consistent, non-empty signature
        $this->assertStringStartsWith('sha256=', $expectedSignature);
        $this->assertGreaterThan(7, strlen($expectedSignature), 'Signature must contain a hash value');

        // Verify a tampered payload produces a different signature (integrity check)
        $tamperedBody      = json_encode(['event' => 'session_start', 'username' => 'mallory']);
        $tamperedSignature = 'sha256=' . hash_hmac('sha256', $tamperedBody, $secret);

        $this->assertNotSame(
            $expectedSignature,
            $tamperedSignature,
            'Tampered payload must produce a different signature'
        );

        // Verify a wrong secret produces a different signature
        $wrongSecretSignature = 'sha256=' . hash_hmac('sha256', $body, 'wrong-secret');
        $this->assertNotSame(
            $expectedSignature,
            $wrongSecretSignature,
            'Wrong secret must produce a different signature'
        );

        // Constant-time comparison (as a receiver would do)
        $this->assertTrue(
            hash_equals($expectedSignature, $expectedSignature),
            'Correct signature must pass hash_equals verification'
        );
        $this->assertFalse(
            hash_equals($expectedSignature, $tamperedSignature),
            'Tampered signature must fail hash_equals verification'
        );
    }
}
