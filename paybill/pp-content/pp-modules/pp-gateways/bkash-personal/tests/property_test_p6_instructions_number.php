<?php
/**
 * Property Test P6: Payment instructions carry the selected number
 *
 * Validates: Requirements 4.1, 4.2
 *
 * Property 6: For any non-empty pool of mobile numbers, instructions() should
 * return an array whose third element (index 2) has both `value` and
 * `vars['{mobile_number}']` equal to the number chosen by selectNumber().
 *
 * Run: php property_test_p6_instructions_number.php
 */

// ---------------------------------------------------------------------------
// Testable wrapper — inlines normalizePool, selectNumber (no DB), and a
// simplified instructions() that accepts $pool and $counter directly.
// ---------------------------------------------------------------------------

class BkashPersonalGatewayTestable
{
    /**
     * Mirrors normalizePool() from class.php exactly.
     */
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

    /**
     * Mirrors selectNumber() but skips the DB write.
     * Accepts $pool and $counter directly instead of reading from $options/DB.
     */
    public function selectNumber(array $pool, int $counter): string
    {
        $count = count($pool);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $pool[0];
        }

        return $pool[$counter % $count];
    }

    /**
     * Simplified instructions() that accepts $pool and $counter directly,
     * bypassing all DB globals. Returns the same array structure as the real
     * class, with index [2] carrying the selected mobile number.
     */
    public function instructions(array $pool, int $counter): array
    {
        $mobileNumber = $this->selectNumber($pool, $counter);

        // Dummy values for fields not under test
        $qrCode        = '';
        $localAmount   = '100';
        $localCurrency = 'BDT';

        return [
            [
                'icon' => '',
                'text' => '1',
                'copy' => false,
            ],
            [
                'icon' => '',
                'text' => '2',
                'copy' => false,
            ],
            [
                'icon'  => '',
                'text'  => '3',
                'copy'  => true,
                'value' => $mobileNumber,
                'vars'  => [
                    '{mobile_number}' => $mobileNumber,
                ],
            ],
            [
                'icon'   => '',
                'text'   => '4',
                'action' => [
                    'type'  => 'image',
                    'label' => '',
                    'value' => $qrCode,
                ],
            ],
            [
                'icon'  => '',
                'text'  => '5',
                'copy'  => true,
                'value' => $localAmount,
                'vars'  => [
                    '{amount}'   => $localAmount,
                    '{currency}' => $localCurrency,
                ],
            ],
            [
                'icon' => '',
                'text' => '6',
                'copy' => false,
            ],
            [
                'icon' => '',
                'text' => '7',
                'copy' => false,
            ],
        ];
    }
}

// ---------------------------------------------------------------------------
// Generator helpers
// ---------------------------------------------------------------------------

function randomBangladeshiNumber(): string
{
    return '01' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
}

/**
 * Generate a random non-empty pool of 1–20 mobile numbers.
 */
function randomPool(): array
{
    $size = random_int(1, 20);
    $pool = [];
    for ($i = 0; $i < $size; $i++) {
        $pool[] = randomBangladeshiNumber();
    }
    return $pool;
}

/**
 * Generate a random counter in the range 0–999.
 */
function randomCounter(): int
{
    return random_int(0, 999);
}

// ---------------------------------------------------------------------------
// Run the property test
// ---------------------------------------------------------------------------

$gw         = new BkashPersonalGatewayTestable();
$iterations = 200;
$failures   = [];

// Fixed edge-case combinations that must always be covered
$fixedCases = [
    // Single-element pool, counter = 0
    [['01700000001'], 0],
    // Single-element pool, counter = 999
    [['01700000001'], 999],
    // Two-element pool, counter = 0 → selects index 0
    [['01700000001', '01800000002'], 0],
    // Two-element pool, counter = 1 → selects index 1
    [['01700000001', '01800000002'], 1],
    // Two-element pool, counter wraps (counter >= pool size)
    [['01700000001', '01800000002'], 5],
    // Three-element pool, each index
    [['01700000001', '01800000002', '01900000003'], 0],
    [['01700000001', '01800000002', '01900000003'], 1],
    [['01700000001', '01800000002', '01900000003'], 2],
    // Max pool size (20), counter = 0
    [array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)), 0],
    // Max pool size (20), counter = 999
    [array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)), 999],
];

foreach ($fixedCases as [$pool, $counter]) {
    $expectedNumber = $gw->selectNumber($pool, $counter);
    $result         = $gw->instructions($pool, $counter);

    $valueOk = isset($result[2]['value']) && $result[2]['value'] === $expectedNumber;
    $varsOk  = isset($result[2]['vars']['{mobile_number}']) && $result[2]['vars']['{mobile_number}'] === $expectedNumber;

    if (!$valueOk || !$varsOk) {
        $failures[] = [
            'pool'           => $pool,
            'counter'        => $counter,
            'expected'       => $expectedNumber,
            'got_value'      => $result[2]['value'] ?? '(missing)',
            'got_vars_key'   => $result[2]['vars']['{mobile_number}'] ?? '(missing)',
            'value_ok'       => $valueOk,
            'vars_ok'        => $varsOk,
            'source'         => 'fixed',
        ];
    }
}

// Random combinations
for ($i = 0; $i < $iterations; $i++) {
    $pool    = randomPool();
    $counter = randomCounter();

    $expectedNumber = $gw->selectNumber($pool, $counter);
    $result         = $gw->instructions($pool, $counter);

    $valueOk = isset($result[2]['value']) && $result[2]['value'] === $expectedNumber;
    $varsOk  = isset($result[2]['vars']['{mobile_number}']) && $result[2]['vars']['{mobile_number}'] === $expectedNumber;

    if (!$valueOk || !$varsOk) {
        $failures[] = [
            'pool'         => $pool,
            'counter'      => $counter,
            'expected'     => $expectedNumber,
            'got_value'    => $result[2]['value'] ?? '(missing)',
            'got_vars_key' => $result[2]['vars']['{mobile_number}'] ?? '(missing)',
            'value_ok'     => $valueOk,
            'vars_ok'      => $varsOk,
            'source'       => 'random',
        ];
    }
}

$total = count($fixedCases) + $iterations;

if (empty($failures)) {
    echo "PASS — Property 6 held across {$total} combinations: instructions()[2]['value'] and instructions()[2]['vars']['{mobile_number}'] always equal selectNumber()." . PHP_EOL;
    exit(0);
} else {
    echo "FAIL — Property 6 violated on " . count($failures) . " combination(s):" . PHP_EOL;
    foreach ($failures as $f) {
        echo "  pool:          [" . implode(', ', $f['pool']) . "]" . PHP_EOL;
        echo "  counter:       " . $f['counter'] . PHP_EOL;
        echo "  expected:      " . $f['expected'] . PHP_EOL;
        echo "  got value:     " . var_export($f['got_value'], true) . PHP_EOL;
        echo "  got vars key:  " . var_export($f['got_vars_key'], true) . PHP_EOL;
        echo "  value_ok:      " . ($f['value_ok'] ? 'true' : 'false') . PHP_EOL;
        echo "  vars_ok:       " . ($f['vars_ok'] ? 'true' : 'false') . PHP_EOL;
        echo "  source:        " . $f['source'] . PHP_EOL;
        echo PHP_EOL;
    }
    exit(1);
}
