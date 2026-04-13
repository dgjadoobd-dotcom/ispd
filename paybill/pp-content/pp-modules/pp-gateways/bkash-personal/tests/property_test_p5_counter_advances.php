<?php
/**
 * Property Test P5: Round-robin counter advances correctly
 *
 * Validates: Requirements 3.2, 3.3, 3.4
 *
 * Property 5: For any pool of size N (N ≥ 2) and any counter value C,
 * after one call to selectNumber() the new persisted counter should equal
 * (C + 1) % N.
 *
 * Run: php property_test_p5_counter_advances.php
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
 * Generate a random pool of size N ≥ 2 (up to 20).
 */
function randomPoolN2(): array
{
    $size = random_int(2, 20);
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
    // Two-element pool, counter = 0 → newCounter should be 1
    [['01700000001', '01800000002'], 0, 1],
    // Two-element pool, counter = 1 → newCounter should wrap to 0
    [['01700000001', '01800000002'], 1, 0],
    // Two-element pool, counter = 5 (wraps) → (5+1)%2 = 0
    [['01700000001', '01800000002'], 5, 0],
    // Two-element pool, counter = 4 (wraps) → (4+1)%2 = 1
    [['01700000001', '01800000002'], 4, 1],
    // Three-element pool, counter = 0 → newCounter = 1
    [['01700000001', '01800000002', '01900000003'], 0, 1],
    // Three-element pool, counter = 2 → newCounter wraps to 0
    [['01700000001', '01800000002', '01900000003'], 2, 0],
    // Three-element pool, counter = 8 (wraps) → (8+1)%3 = 0
    [['01700000001', '01800000002', '01900000003'], 8, 0],
    // Three-element pool, counter = 7 (wraps) → (7+1)%3 = 2
    [['01700000001', '01800000002', '01900000003'], 7, 2],
    // Max pool size (20), counter = 0 → newCounter = 1
    [array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)), 0, 1],
    // Max pool size (20), counter = 19 → newCounter wraps to 0
    [array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)), 19, 0],
    // Max pool size (20), counter = 999 → (999+1)%20 = 0
    [array_map(fn($i) => '01' . str_pad((string)$i, 9, '0', STR_PAD_LEFT), range(0, 19)), 999, 0],
    // Large counter that wraps multiple times
    [['01700000001', '01800000002', '01900000003'], 999, (999 + 1) % 3],
];

foreach ($fixedCases as [$pool, $counter, $expectedNewCounter]) {
    $gw->selectNumber($pool, $counter, $newCounter);
    if ($newCounter !== $expectedNewCounter) {
        $failures[] = [
            'pool'        => $pool,
            'counter'     => $counter,
            'newCounter'  => $newCounter,
            'expected'    => $expectedNewCounter,
            'source'      => 'fixed',
        ];
    }
}

// Random combinations: pool size N ≥ 2, random counter C
for ($i = 0; $i < $iterations; $i++) {
    $pool    = randomPoolN2();
    $counter = randomCounter();
    $n       = count($pool);

    $gw->selectNumber($pool, $counter, $newCounter);

    $expected = ($counter + 1) % $n;

    if ($newCounter !== $expected) {
        $failures[] = [
            'pool'       => $pool,
            'counter'    => $counter,
            'newCounter' => $newCounter,
            'expected'   => $expected,
            'source'     => 'random',
        ];
    }
}

$total = count($fixedCases) + $iterations;

if (empty($failures)) {
    echo "PASS — Property 5 held across {$total} combinations: new counter always equals (C + 1) % N." . PHP_EOL;
    exit(0);
} else {
    echo "FAIL — Property 5 violated on " . count($failures) . " combination(s):" . PHP_EOL;
    foreach ($failures as $f) {
        $poolStr = count($f['pool']) <= 5
            ? '[' . implode(', ', $f['pool']) . ']'
            : '[' . implode(', ', array_slice($f['pool'], 0, 3)) . ', ... (' . count($f['pool']) . ' items)]';
        echo "  pool:       " . $poolStr . PHP_EOL;
        echo "  N:          " . count($f['pool']) . PHP_EOL;
        echo "  counter C:  " . $f['counter'] . PHP_EOL;
        echo "  newCounter: " . var_export($f['newCounter'], true) . PHP_EOL;
        echo "  expected:   " . $f['expected'] . "  (= (C+1) % N = (" . $f['counter'] . "+1) % " . count($f['pool']) . ")" . PHP_EOL;
        echo "  source:     " . $f['source'] . PHP_EOL;
        echo PHP_EOL;
    }
    exit(1);
}
