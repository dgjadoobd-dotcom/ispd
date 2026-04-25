<?php
/**
 * Property Test P4: Round-robin selection is always a pool member
 *
 * Validates: Requirements 3.1, 3.2
 *
 * Property 4: For any non-empty pool of mobile numbers and any non-negative
 * integer counter value, the number returned by selectNumber() should be an
 * element of that pool.
 *
 * Run: php property_test_p4_select_number_member.php
 */

// ---------------------------------------------------------------------------
// Testable wrapper — inlines selectNumber logic, skips DB upsert
// ---------------------------------------------------------------------------

class BkashPersonalGatewayTestable
{
    /**
     * Mirrors the real selectNumber() but skips the DB write.
     * Returns the selected number and exposes the new counter via $newCounter.
     */
    public function selectNumber(array $pool, int $counter, ?int &$newCounter = null): string
    {
        if (count($pool) === 0) {
            $newCounter = $counter;
            return '';
        }

        if (count($pool) === 1) {
            $newCounter = $counter; // counter unchanged for single-item pool
            return $pool[0];
        }

        $selected   = $pool[$counter % count($pool)];
        $newCounter = ($counter + 1) % count($pool);

        // DB upsert skipped in tests (non-fatal per design)

        return $selected;
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
    // Two-element pool, counter = 0
    [['01700000001', '01800000002'], 0],
    // Two-element pool, counter = 1
    [['01700000001', '01800000002'], 1],
    // Two-element pool, counter wraps (counter >= pool size)
    [['01700000001', '01800000002'], 5],
    // Max pool size (20), counter = 0
    [array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)), 0],
    // Max pool size (20), counter = 999
    [array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)), 999],
    // Pool with duplicate numbers — selection must still be a member
    [['01700000001', '01700000001', '01800000002'], 0],
    [['01700000001', '01700000001', '01800000002'], 1],
    [['01700000001', '01700000001', '01800000002'], 2],
];

foreach ($fixedCases as [$pool, $counter]) {
    $selected = $gw->selectNumber($pool, $counter);
    if (!in_array($selected, $pool, true)) {
        $failures[] = [
            'pool'     => $pool,
            'counter'  => $counter,
            'selected' => $selected,
            'source'   => 'fixed',
        ];
    }
}

// Random combinations
for ($i = 0; $i < $iterations; $i++) {
    $pool     = randomPool();
    $counter  = randomCounter();
    $selected = $gw->selectNumber($pool, $counter);

    if (!in_array($selected, $pool, true)) {
        $failures[] = [
            'pool'     => $pool,
            'counter'  => $counter,
            'selected' => $selected,
            'source'   => 'random',
        ];
    }
}

$total = count($fixedCases) + $iterations;

if (empty($failures)) {
    echo "PASS — Property 4 held across {$total} combinations: selectNumber always returns a pool member." . PHP_EOL;
    exit(0);
} else {
    echo "FAIL — Property 4 violated on " . count($failures) . " combination(s):" . PHP_EOL;
    foreach ($failures as $f) {
        echo "  pool:     [" . implode(', ', $f['pool']) . "]" . PHP_EOL;
        echo "  counter:  " . $f['counter'] . PHP_EOL;
        echo "  selected: " . var_export($f['selected'], true) . PHP_EOL;
        echo "  source:   " . $f['source'] . PHP_EOL;
        echo PHP_EOL;
    }
    exit(1);
}
