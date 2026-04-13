<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class IpAccessControlServiceTest extends TestCase
{
    private \IpAccessControlService $service;
    /** @var MockObject&\PDO */
    private MockObject $pdo;

    protected function setUp(): void
    {
        $this->pdo     = $this->createMock(\PDO::class);
        $this->service = new \IpAccessControlService($this->pdo);
    }

    // ── matchesCidr ───────────────────────────────────────────────────────────

    public function testMatchesCidrReturnsTrueForExactIp(): void
    {
        $this->assertTrue($this->service->matchesCidr('192.168.1.1', '192.168.1.1'));
    }

    public function testMatchesCidrReturnsTrueForIpInRange(): void
    {
        $this->assertTrue($this->service->matchesCidr('10.0.0.5', '10.0.0.0/24'));
    }

    public function testMatchesCidrReturnsFalseForIpOutsideRange(): void
    {
        $this->assertFalse($this->service->matchesCidr('10.0.1.1', '10.0.0.0/24'));
    }

    // ── isAllowed ─────────────────────────────────────────────────────────────

    public function testIsAllowedReturnsTrueWhenNoRules(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $this->pdo->method('query')->willReturn($stmt);

        $this->assertTrue($this->service->isAllowed('192.168.1.100'));
    }
}
