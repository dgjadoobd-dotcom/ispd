<?php
/**
 * Property Test P1: Serialization round-trip
 *
 * Validates: Requirements 1.1, 1.2
 *
 * Property: For any non-empty array of mobile number strings, JSON-encoding it
 * and then JSON-decoding the result should produce an array equal to the original.
 *
 * Run: php property_test_p1_serialization_roundtrip.php
 */

function random_valid_number_p1(): string
{
    return '01' . str_pad((string)rand(0, 999999999), 9, '0', STR_PAD_LEFT);
}

function generate_number_array(int $minLen = 1, int $maxLen = 20): array
{
    $len = rand($minLen, $maxLen);
    $arr = [];
    for ($i = 0; $i < $len; $i++) $arr[] = random_valid_number_p1();
    return $arr;
}

function check_roundtrip(array $input): bool
{
    $encoded = json_encode($input);
    $decoded = json_decode($encoded, true);
    if ($decoded !== $input) {
        echo "FAIL: Round-trip produced a different array.\n";
        echo "  Input:   " . json_encode($input) . "\n";
        echo "  Decoded: " . json_encode($decoded) . "\n";
        return false;
    }
    return true;
}

$iterations = 120;
$passed     = 0;
$failed     = 0;

for ($i = 0; $i < $iterations; $i++) {
    check_roundtrip(generate_number_array(1, 20)) ? $passed++ : $failed++;
}

$edgeCases = [
    ['01700000001'],
    ['01700000001', '01800000002'],
    array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)),
    ['01000000000', '01099999999'],
    array_fill(0, 5, '01700000001'),
];

foreach ($edgeCases as $input) {
    check_roundtrip($input) ? $passed++ : $failed++;
}

$total = $passed + $failed;
echo "\nProperty P1: Serialization round-trip\n";
echo "Validates: Requirements 1.1, 1.2\n";
echo "Iterations: {$total} ({$iterations} random + " . count($edgeCases) . " edge cases)\n\n";

if ($failed === 0) {
    echo "PASS — all {$total} cases confirmed: json_decode(json_encode(\$arr), true) === \$arr." . PHP_EOL;
    exit(0);
} else {
    echo "FAIL — {$failed} of {$total} cases failed (see details above).\n";
    exit(1);
}
