<?php
/**
 * Property Test P7: Mobile number validator accepts exactly valid Bangladeshi numbers
 *
 * Validates: Requirements 2.4
 *
 * Property: For any string, the client-side regex validator (/^01[0-9]{9}$/)
 * should accept it if and only if it is exactly 11 characters, starts with "01",
 * and the remaining 9 characters are all digits.
 *
 * Run: php property_test_p7_validator_regex.php
 */

declare(strict_types=1);

/**
 * Independent reference implementation of the acceptance criterion.
 * Returns true iff $s is exactly 11 chars, starts with "01", and chars 2-10 are digits.
 */
function isValidBangladeshiNumber(string $s): bool
{
    if (strlen($s) !== 11) return false;
    if (substr($s, 0, 2) !== '01') return false;
    for ($i = 2; $i < 11; $i++) {
        if ($s[$i] < '0' || $s[$i] > '9') return false;
    }
    return true;
}

function randomDigit(): string { return (string) random_int(0, 9); }

function randomDigits(int $len): string
{
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= randomDigit();
    return $s;
}

function randomPrintableChar(): string { return chr(random_int(32, 126)); }

function randomPrintableString(int $len): string
{
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= randomPrintableChar();
    return $s;
}

function genValid(): string { return '01' . randomDigits(9); }

function genWrongLength(): string
{
    $len = (random_int(0, 1) === 0) ? 10 : 12;
    return '01' . randomDigits($len - 2);
}

function genWrongPrefix(): string
{
    do { $prefix = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT); } while ($prefix === '01');
    return $prefix . randomDigits(9);
}

function genNonDigitSuffix(): string
{
    $suffix = randomDigits(9);
    $pos = random_int(0, 8);
    do { $ch = randomPrintableChar(); } while ($ch >= '0' && $ch <= '9');
    $suffix[$pos] = $ch;
    return '01' . $suffix;
}

function genRandom(): string { return randomPrintableString(random_int(0, 20)); }

function edgeCases(): array
{
    return [
        '',
        ' ',
        '01' . str_repeat('0', 9),
        '01' . str_repeat('9', 9),
        '01' . str_repeat('0', 8),
        '01' . str_repeat('0', 10),
        '00' . randomDigits(9),
        '02' . randomDigits(9),
        '11' . randomDigits(9),
        '1' . randomDigits(10),
        ' 01' . randomDigits(9),
        '01' . randomDigits(9) . ' ',
        '01' . randomDigits(8) . 'a',
        '01' . 'a' . randomDigits(8),
    ];
}

$passed   = 0;
$failed   = 0;
$failures = [];
$REGEX    = '/^01[0-9]{9}$/';

function checkString(string $s, string $regex, int &$passed, int &$failed, array &$failures): void
{
    $regexResult    = preg_match($regex, $s) === 1;
    $expectedResult = isValidBangladeshiNumber($s);
    if ($regexResult === $expectedResult) {
        $passed++;
    } else {
        $failed++;
        $failures[] = sprintf("MISMATCH: s=%s | regex=%s | expected=%s",
            json_encode($s),
            $regexResult ? 'true' : 'false',
            $expectedResult ? 'true' : 'false'
        );
    }
}

foreach (edgeCases() as $s) {
    checkString($s, $REGEX, $passed, $failed, $failures);
}

for ($i = 0; $i < 200; $i++) {
    $s = match (random_int(0, 4)) {
        0 => genValid(),
        1 => genWrongLength(),
        2 => genWrongPrefix(),
        3 => genNonDigitSuffix(),
        default => genRandom(),
    };
    checkString($s, $REGEX, $passed, $failed, $failures);
}

$total = $passed + $failed;
echo "Property Test P7: validator accepts exactly valid Bangladeshi numbers\n";
echo "Validates: Requirements 2.4\n";
echo str_repeat('-', 60) . "\n";
echo "Total checks : {$total}\n";
echo "Passed       : {$passed}\n";
echo "Failed       : {$failed}\n";
echo str_repeat('-', 60) . "\n";

if ($failed === 0) {
    echo "PASS" . PHP_EOL;
    exit(0);
} else {
    echo "FAIL\n\nFailing examples:\n";
    foreach ($failures as $f) echo "  {$f}\n";
    exit(1);
}
