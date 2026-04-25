# Implementation Plan: bkash-personal Multi-Number Support

## Overview

Three files are modified to add multi-number round-robin support to the bkash-personal gateway. Changes are backward-compatible — existing single-number configurations continue to work without a migration script.

## Tasks

- [x] 1. Add `normalizePool()` and `selectNumber()` helpers to `BkashPersonalGateway`
  - [x] 1.1 Implement `private normalizePool(mixed $raw): array` in `class.php`
    - Return `$raw` filtered of empty strings if already an array
    - `json_decode` if `$raw` is a non-empty string starting with `[`; fall back to wrapping in `[$raw]` for plain strings
    - Return `[]` for empty/null input
    - _Requirements: 1.5, 5.1, 5.3_

  - [x] 1.2 Write property test for `normalizePool` — P3: always returns array
    - **Property 3: `normalizePool` always returns an array**
    - **Validates: Requirements 1.5, 5.1, 5.3**
    - Generate 100+ random inputs: plain strings, JSON array strings, PHP arrays, null, empty string
    - Assert `is_array(normalizePool($v)) === true` for every input

  - [x] 1.3 Implement `private selectNumber(array $pool, array &$options, string $gatewayId, string $brandId): string` in `class.php`
    - Return `''` when `count($pool) === 0`
    - Return `$pool[0]` without touching the counter when `count($pool) === 1`
    - Read `$counter = (int)($options['rr_counter'] ?? 0)`; select `$pool[$counter % count($pool)]`
    - Compute `$newCounter = ($counter + 1) % count($pool)` and upsert it to `gateways_parameter` via existing `updateData`/`insertData` helpers
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [x] 1.4 Write property test for `selectNumber` — P4: selection is always a pool member
    - **Property 4: Round-robin selection is always a pool member**
    - **Validates: Requirements 3.1, 3.2**
    - Generate 100+ combinations of random non-empty pool (1–20 items) and random counter (0–999)
    - Assert `in_array(selectNumber($pool, $counter), $pool) === true`

  - [x] 1.5 Write property test for `selectNumber` — P5: counter advances correctly
    - **Property 5: Round-robin counter advances correctly**
    - **Validates: Requirements 3.2, 3.3, 3.4**
    - Generate 100+ combinations of pool size N ≥ 2 and counter C
    - Assert persisted new counter equals `($C + 1) % N`

- [x] 2. Update `BkashPersonalGateway::instructions()` to use the new helpers
  - Replace `$mobileNumber = $options['mobile_number'] ?? ''` with:
    ```php
    $pool         = $this->normalizePool($options['mobile_number'] ?? '');
    $mobileNumber = $this->selectNumber($pool, $options, $data['gateway']['gateway_id'], $data['brand']['id']);
    ```
  - Leave the rest of `instructions()` unchanged
  - _Requirements: 4.1, 4.2, 4.3_

  - [x] 2.1 Write property test for `instructions()` — P6: instructions carry the selected number
    - **Property 6: Payment instructions carry the selected number**
    - **Validates: Requirements 4.1, 4.2**
    - Generate 100+ combinations of random non-empty pool and random counter
    - Assert `instructions()[2]['value']` and `instructions()[2]['vars']['{mobile_number}']` both equal the number chosen by `selectNumber()`

- [x] 3. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Update `edit.php` to inject the `multi_mobile` field descriptor for bkash-personal
  - Inside the `gateway_type == 'automation'` extraFields block, wrap the existing `mobile_number` push in an `if ($slug === 'bkash-personal')` / `else` branch
  - For the `bkash-personal` branch, push `['name' => 'mobile_number', 'label' => 'Mobile Numbers', 'type' => 'multi_mobile']` instead of the plain text descriptor
  - _Requirements: 2.6_

- [x] 5. Add `case 'multi_mobile':` rendering branch in `edit.php`
  - In the field-rendering `switch`, add a `case 'multi_mobile':` block that:
    - Decodes the stored `$value` (JSON array or legacy plain string) into a PHP array; defaults to `['']` when empty
    - Renders `<div id="mobile-number-list">` with one `.mobile-number-row` input-group per number, each with `name="mobile_number[]"`, `pattern="^01[0-9]{9}$"`, and a remove button
    - Renders an `#add-mobile-number` button and a hidden `#mobile-number-error` div below the list
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 6. Inject inline JavaScript for add/remove/validation in `edit.php`
  - Append the JS block once, guarded by `if ($slug === 'bkash-personal')`, after the field loop
  - Add-row handler: clone first `.mobile-number-row`, clear its input value, append to `#mobile-number-list`; disable `#add-mobile-number` when row count reaches 20
  - Remove-row handler: delegated click on `#mobile-number-list`; only remove if more than 1 row remains
  - Pre-submit validation: on `.form-submit` submit, collect all `[name="mobile_number[]"]` inputs, filter by `/^01[0-9]{9}$/`; if none valid, `preventDefault()` and show `#mobile-number-error`
  - _Requirements: 2.2, 2.3, 2.4, 2.5_

  - [x] 6.1 Write property test for client-side regex — P7: validator accepts exactly valid Bangladeshi numbers
    - **Property 7: Mobile number validator accepts exactly valid Bangladeshi numbers**
    - **Validates: Requirements 2.4**
    - Generate 100+ random strings (mix of valid 11-digit `01XXXXXXXXX` and invalid variants)
    - Assert `preg_match('/^01[0-9]{9}$/', $s) === 1` iff string is exactly 11 chars, starts with `01`, remaining 9 are digits

- [x] 7. Update `pp-adapter.php` to serialize `mobile_number[]` POST array
  - In the `gateway-setting-update` POST handler's field-iteration loop, add a special case **before** the generic `is_array → json_encode` branch:
    ```php
    if ($key === 'mobile_number' && is_array($value)) {
        $filtered = array_values(array_filter($value, fn($n) => trim($n) !== ''));
        $filtered = array_slice($filtered, 0, 20);
        $value = json_encode($filtered);
        $configData[$key] = $value;
        continue;
    }
    ```
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 7.1 Write property test for adapter filter — P2: whitespace entries stripped before save
    - **Property 2: Whitespace entries are stripped before save**
    - **Validates: Requirements 1.4**
    - Generate 100+ arrays mixing valid number strings and whitespace-only strings (`' '`, `"\t"`, `''`)
    - Assert filtered result contains no entries where `trim($n) === ''`

  - [x] 7.2 Write property test for serialization — P1: serialization round-trip
    - **Property 1: Serialization round-trip**
    - **Validates: Requirements 1.1, 1.2**
    - Generate 100+ random arrays of 1–20 valid number strings
    - Assert `json_decode(json_encode($arr), true) === $arr`

- [x] 8. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Property tests use a simple PHP generator loop (100+ iterations each); no external library required
- `rr_counter` is never submitted via the admin form — the adapter loop never touches it
- DB write failure on `rr_counter` update is non-fatal; the selected number is still returned
- Legacy plain-string `mobile_number` values are handled transparently by `normalizePool()` — no migration needed
