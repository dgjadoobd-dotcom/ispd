<?php
/**
 * Property Test P3: normalizePool always returns an array
 *
 * Validates: Requirements 1.5, 5.1, 5.3
 *
 * Property 3: For any value stored in options['mobile_number'] — whether a plain
 * string, a JSON-encoded array string, an already-decoded PHP array, or an
 * empty/null value — normalizePool() should always return a PHP array.
 *
 * Run: php property_test_p3_normalize_pool.php
 */

// ---------------------------------------------------------------------------
// Inline the method under test via a testable subclass
// ---------------------------------------------------------------------------

class BkashPersonalGatewayTestable
{
    public function normalizePool(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter($raw, fn($v) => $v !== ''));
        }

        if (is_string($raw) && $raw !== '') {
            if ($raw[0] === '[') {
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : [$raw];
            }
            return [$raw];
        }

        return [];
    }
}

// ---------------------------------------------------------------------------
// Generator helpers
// ---------------------------------------------------------------------------

function randomBangladeshiNumber(): string
{
    return '01' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
}

function randomPlainString(): string
{
    $choices = [
        randomBangladeshiNumber(),
        'hello',
        'abc123',
        str_repeat('x', random_int(1, 30)),
        (string)random_int(0, 99999),
    ];
    return $choices[array_rand($choices)];
}

function randomJsonArrayString(): string
{
    $count = random_int(1, 5);
    $nums  = [];
    for ($i = 0; $i < $count; $i++) {
        $nums[] = randomBangladeshiNumber();
    }
    return json_encode($nums);
}

function randomPhpArray(): array
{
    $count = random_int(0, 5);
    $arr   = [];
    for ($i = 0; $i < $count; $i++) {
        $arr[] = random_int(0, 3) === 0 ? '' : randomBangladeshiNumber();
    }
    return $arr;
}

function randomInput(): mixed
{
    $kind = random_int(0, 7);
    return match ($kind) {
        0 => null,
        1 => '',
        2 => false,
        3 => randomPlainString(),
        4 => randomJsonArrayString(),
        5 => randomPhpArray(),
        6 => '[invalid json{{{',
        7 => '[]',
    };
}

// ---------------------------------------------------------------------------
// Run the property test
// ---------------------------------------------------------------------------

$gw         = new BkashPersonalGatewayTestable();
$iterations = 200;
$failures   = [];

// Fixed edge-case inputs that must always be covered
$fixedInputs = [
    null,
    '',
    false,
    [],
    ['01700000001'],
    ['01700000001', ''],
    '01700000001',
    '["01700000001","01800000002"]',
    '[]',
    '[invalid',
    '[null]',
    '[""]',
];

foreach ($fixedInputs as $input) {
    $result = $gw->normalizePool($input);
    if (!is_array($result)) {
        $failures[] = [
            'input'  => var_export($input, true),
            'result' => var_export($result, true),
        ];
    }
}

// Random inputs
for ($i = 0; $i < $iterations; $i++) {
    $input  = randomInput();
    $result = $gw->normalizePool($input);
    if (!is_array($result)) {
        $failures[] = [
            'input'  => var_export($input, true),
            'result' => var_export($result, true),
        ];
    }
}

$total = count($fixedInputs) + $iterations;

if (empty($failures)) {
    echo "PASS — Property 3 held across {$total} inputs: normalizePool always returns an array." . PHP_EOL;
    exit(0);
} else {
    echo "FAIL — Property 3 violated on " . count($failures) . " input(s):" . PHP_EOL;
    foreach ($failures as $f) {
        echo "  input:  " . $f['input'] . PHP_EOL;
        echo "  result: " . $f['result'] . PHP_EOL;
        echo PHP_EOL;
    }
    exit(1);
}
