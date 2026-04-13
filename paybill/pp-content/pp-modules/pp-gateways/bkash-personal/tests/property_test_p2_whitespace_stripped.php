<?php
/**
 * Property Test P2: Whitespace entries are stripped before save
 *
 * Validates: Requirements 1.4
 *
 * Property: For any array of mobile number strings that includes one or more
 * entries composed entirely of whitespace, the filtered array passed to
 * json_encode should contain none of those whitespace entries.
 *
 * Run: php property_test_p2_whitespace_stripped.php
 */

// Inline the adapter filter logic (mirrors pp-adapter.php)
function adapter_filter_mobile_number(array $value): string
{
    $filtered = array_values(array_filter($value, fn($n) => trim($n) !== ''));
    $filtered = array_slice($filtered, 0, 20);
    return json_encode($filtered);
}

// --- Generators ---

function random_valid_number(): string
{
    // Generate a valid-looking Bangladeshi number string (not necessarily real)
    return '01' . str_pad((string)rand(0, 999999999), 9, '0', STR_PAD_LEFT);
}

function random_whitespace_entry(): string
{
    $whitespaceChars = [' ', "\t", "\n", "\r", "  ", " \t ", ""];
    return $whitespaceChars[array_rand($whitespaceChars)];
}

function generate_mixed_array(int $minLen = 1, int $maxLen = 25): array
{
    $len = rand($minLen, $maxLen);
    $arr = [];
    for ($i = 0; $i < $len; $i++) {
        // ~40% chance of whitespace entry
        if (rand(0, 9) < 4) {
            $arr[] = random_whitespace_entry();
        } else {
            $arr[] = random_valid_number();
        }
    }
    return $arr;
}

// --- Property check ---

function check_no_whitespace_entries(array $input): bool
{
    $encoded  = adapter_filter_mobile_number($input);
    $filtered = json_decode($encoded, true);

    if (!is_array($filtered)) {
        echo "FAIL: json_decode returned non-array for input: " . json_encode($input) . "\n";
        return false;
    }

    foreach ($filtered as $entry) {
        if (trim($entry) === '') {
            echo "FAIL: Whitespace entry survived filter.\n";
            echo "  Input:    " . json_encode($input) . "\n";
            echo "  Filtered: " . json_encode($filtered) . "\n";
            echo "  Offending entry: " . json_encode($entry) . "\n";
            return false;
        }
    }

    return true;
}

// --- Run 100+ iterations ---

$iterations = 120;
$passed     = 0;
$failed     = 0;

// Seed for reproducibility (optional; remove for true randomness each run)
// srand(42);

for ($i = 0; $i < $iterations; $i++) {
    $input = generate_mixed_array(1, 25);

    // Guarantee at least one whitespace entry in every test case so the
    // property is always exercised (not just when the random mix happens to
    // include one).
    $insertAt        = rand(0, count($input));
    array_splice($input, $insertAt, 0, [random_whitespace_entry()]);

    if (check_no_whitespace_entries($input)) {
        $passed++;
    } else {
        $failed++;
    }
}

// Also test a few edge-case arrays explicitly
$edgeCases = [
    // All whitespace
    [' ', "\t", '', "  ", "\n"],
    // Single whitespace
    [' '],
    // Single empty string
    [''],
    // Mix: valid + various whitespace forms
    ['01700000001', ' ', "\t", '', '01800000002'],
    // Only valid numbers (no whitespace — filtered result should equal input)
    ['01700000001', '01800000002'],
    // Empty array
    [],
    // 20 valid + 5 whitespace (tests slice-to-20 interaction)
    array_merge(
        array_fill(0, 20, '01700000001'),
        [' ', "\t", '', '  ', "\r\n"]
    ),
];

foreach ($edgeCases as $input) {
    if (check_no_whitespace_entries($input)) {
        $passed++;
    } else {
        $failed++;
    }
}

// --- Report ---

$total = $passed + $failed;
echo "\n";
echo "Property P2: Whitespace entries stripped before save\n";
echo "Validates: Requirements 1.4\n";
echo "Iterations: {$total} ({$iterations} random + " . count($edgeCases) . " edge cases)\n";
echo "\n";

if ($failed === 0) {
    echo "PASS — all {$total} cases confirmed: no whitespace-only entries survive the filter.\n";
    exit(0);
} else {
    echo "FAIL — {$failed} of {$total} cases failed (see details above).\n";
    exit(1);
}
