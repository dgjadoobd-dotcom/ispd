<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RadiusBulkServiceTest extends TestCase
{
    private \RadiusBulkService $service;
    /** @var MockObject&\PDO */
    private MockObject $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->service = new \RadiusBulkService($this->pdo);
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function testExportUsersReturnsCSVWithHeaders(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['username' => 'alice', 'password' => 'pass1', 'group' => 'staff', 'profile' => ''],
        ]);

        $this->pdo->method('prepare')->willReturn($stmt);

        $csv = $this->service->exportUsers();

        $lines = explode("\n", trim($csv));
        $this->assertStringContainsString('username', $lines[0]);
        $this->assertStringContainsString('password', $lines[0]);
        $this->assertStringContainsString('group',    $lines[0]);
        $this->assertStringContainsString('profile',  $lines[0]);
        $this->assertCount(2, $lines); // header + 1 data row
    }

    // ── Import ────────────────────────────────────────────────────────────────

    public function testImportUsersSuccessfully(): void
    {
        $csv = "username,password,group\nalice,secret,staff\n";

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $this->pdo->method('prepare')->willReturn($stmt);
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);
        $this->pdo->method('getAttribute')->willReturn('mysql');

        $result = $this->service->importUsers($csv);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
    }

    public function testImportUsersValidatesEmptyUsername(): void
    {
        $csv = "username,password\n,secret\n";

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $this->pdo->method('prepare')->willReturn($stmt);
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);

        $result = $this->service->importUsers($csv);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('username', $result['errors'][0]);
    }

    public function testImportUsersEnforcesRowLimit(): void
    {
        // Build a CSV with 501 data rows (exceeds MAX_IMPORT_ROWS = 500)
        $lines = ["username,password"];
        for ($i = 1; $i <= 501; $i++) {
            $lines[] = "user{$i},pass{$i}";
        }
        $csv = implode("\n", $lines) . "\n";

        $result = $this->service->importUsers($csv);

        $this->assertSame(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('500', $result['errors'][0]);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function testValidateCsvRowReturnsNullForValidRow(): void
    {
        $error = $this->service->validateCsvRow(['username' => 'alice', 'password' => 'secret']);
        $this->assertNull($error);
    }

    public function testValidateCsvRowReturnsErrorForEmptyUsername(): void
    {
        $error = $this->service->validateCsvRow(['username' => '', 'password' => 'secret']);
        $this->assertNotNull($error);
        $this->assertStringContainsString('username', $error);
    }
}
