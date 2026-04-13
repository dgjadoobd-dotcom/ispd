<?php

/**
 * MfaService — TOTP-based Multi-Factor Authentication
 *
 * Implements RFC 6238 (TOTP) compatible with Google Authenticator.
 * No external libraries required — uses PHP built-in functions only.
 */
class MfaService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random 16-character Base32 secret key.
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(10); // 10 bytes → 80 bits → 16 Base32 chars
        return $this->base32Encode($bytes);
    }

    /**
     * Generate the current 6-digit TOTP code using HMAC-SHA1.
     *
     * @param string $secret  Base32-encoded secret
     * @param int    $timeStep Time step in seconds (default 30)
     * @return string Zero-padded 6-digit code
     */
    public function generateTotp(string $secret, int $timeStep = 30): string
    {
        $counter = (int) floor(time() / $timeStep);
        return $this->computeHotp($secret, $counter);
    }

    /**
     * Verify a TOTP code, allowing ±$window time steps for clock drift.
     *
     * @param string $secret  Base32-encoded secret
     * @param string $code    6-digit code to verify
     * @param int    $window  Number of steps to check on each side (default 1)
     * @return bool
     */
    public function verifyTotp(string $secret, string $code, int $window = 1): bool
    {
        $currentStep = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $expected = $this->computeHotp($secret, $currentStep + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a Google Authenticator-compatible otpauth:// URI.
     * Clients can encode this as a QR code for scanning.
     *
     * @param string $username Account label (e.g. user@example.com)
     * @param string $secret   Base32-encoded secret
     * @param string $issuer   Issuer name shown in the authenticator app
     * @return string otpauth URI
     */
    public function getQrCodeUrl(string $username, string $secret, string $issuer = 'DigitalISP'): string
    {
        $label = rawurlencode($issuer . ':' . $username);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ]);

        return 'otpauth://totp/' . $label . '?' . $params;
    }

    /**
     * Generate $count random 8-character alphanumeric backup codes.
     *
     * @param int $count Number of codes to generate (default 8)
     * @return string[]
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $codes[] = $code;
        }

        return $codes;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Compute an HOTP value for the given counter (RFC 4226).
     */
    private function computeHotp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);

        // Pack counter as 8-byte big-endian
        $counterBytes = pack('N*', 0) . pack('N*', $counter);

        $hash = hash_hmac('sha1', $counterBytes, $key, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Encode binary data to Base32 (RFC 4648).
     */
    private function base32Encode(string $data): string
    {
        $chars = self::BASE32_CHARS;
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $result .= $chars[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        if ($bitsLeft > 0) {
            $result .= $chars[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $result;
    }

    /**
     * Decode a Base32 string to binary data (RFC 4648).
     */
    private function base32Decode(string $data): string
    {
        $data = strtoupper(rtrim($data, '='));
        $charMap = array_flip(str_split(self::BASE32_CHARS));

        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $char) {
            if (!isset($charMap[$char])) {
                continue; // skip invalid characters
            }

            $buffer = ($buffer << 5) | $charMap[$char];
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }
}
