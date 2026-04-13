<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MfaServiceTest extends TestCase
{
    private \MfaService $service;

    protected function setUp(): void
    {
        $this->service = new \MfaService();
    }

    public function testGenerateSecretReturns16CharBase32String(): void
    {
        $secret = $this->service->generateSecret();

        $this->assertSame(16, strlen($secret));
        // Base32 alphabet: A-Z and 2-7
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16}$/', $secret);
    }

    public function testGenerateTotpReturns6DigitString(): void
    {
        $secret = $this->service->generateSecret();
        $totp   = $this->service->generateTotp($secret);

        $this->assertSame(6, strlen($totp));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $totp);
    }

    public function testVerifyTotpReturnsTrueForCurrentCode(): void
    {
        $secret = $this->service->generateSecret();
        $code   = $this->service->generateTotp($secret);

        $this->assertTrue($this->service->verifyTotp($secret, $code));
    }

    public function testVerifyTotpReturnsFalseForWrongCode(): void
    {
        $secret = $this->service->generateSecret();

        $this->assertFalse($this->service->verifyTotp($secret, '000000'));
    }

    public function testGenerateBackupCodesReturnsCorrectCount(): void
    {
        $codes = $this->service->generateBackupCodes(8);

        $this->assertCount(8, $codes);
        foreach ($codes as $code) {
            $this->assertSame(8, strlen($code));
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code);
        }
    }

    public function testGetQrCodeUrlContainsOtpauthScheme(): void
    {
        $secret = $this->service->generateSecret();
        $url    = $this->service->getQrCodeUrl('user@example.com', $secret, 'TestIssuer');

        $this->assertStringStartsWith('otpauth://totp/', $url);
        $this->assertStringContainsString($secret, $url);
        $this->assertStringContainsString('TestIssuer', $url);
    }
}
