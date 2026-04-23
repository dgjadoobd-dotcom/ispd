<?php

/**
 * ValidationHelper — static utility class for input validation.
 *
 * All methods either return true/false or throw no exceptions.
 * The `validate()` method aggregates errors across multiple fields.
 *
 * Usage:
 *   $errors = ValidationHelper::validate($_POST, [
 *       'email'  => ['required', 'email'],
 *       'phone'  => ['required', 'phone'],
 *       'amount' => ['required', 'numeric:0:999999'],
 *   ]);
 *   if (!empty($errors)) { ... }
 */
class ValidationHelper
{
    // ── Individual validators ─────────────────────────────────────

    /**
     * Validate that a value is not empty (null, empty string, or whitespace-only).
     *
     * @param  mixed  $value      The value to check
     * @param  string $fieldName  Field label used in the error message
     * @return string|null        Error message, or null on success
     */
    public static function required(mixed $value, string $fieldName): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '') || $value === '') {
            return "{$fieldName} is required.";
        }
        return null;
    }

    /**
     * Validate an email address format.
     *
     * @param  mixed $value  The value to check
     * @return string|null   Error message, or null on success
     */
    public static function email(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null; // Use required() separately for presence checks
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email address format.";
        }
        return null;
    }

    /**
     * Validate a Bangladeshi phone number.
     *
     * Accepts formats:
     *   - 01XXXXXXXXX  (11 digits, starting with 01)
     *   - +8801XXXXXXXXX (country code prefix)
     *   - 8801XXXXXXXXX  (country code without +)
     *
     * @param  mixed $value  The value to check
     * @return string|null   Error message, or null on success
     */
    public static function phone(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null; // Use required() separately for presence checks
        }

        $normalized = preg_replace('/[\s\-\(\)]/', '', (string)$value);

        // Strip country code prefix if present
        if (str_starts_with($normalized, '+880')) {
            $normalized = '0' . substr($normalized, 4);
        } elseif (str_starts_with($normalized, '880') && strlen($normalized) === 13) {
            $normalized = '0' . substr($normalized, 3);
        }

        // Must be 11 digits starting with 01[3-9]
        if (!preg_match('/^01[3-9]\d{8}$/', $normalized)) {
            return "Invalid phone number. Use Bangladeshi format (e.g. 01XXXXXXXXX).";
        }

        return null;
    }

    /**
     * Validate that a value is numeric and optionally within a range.
     *
     * @param  mixed       $value      The value to check
     * @param  string      $fieldName  Field label used in the error message
     * @param  float|null  $min        Minimum allowed value (inclusive), or null to skip
     * @param  float|null  $max        Maximum allowed value (inclusive), or null to skip
     * @return string|null             Error message, or null on success
     */
    public static function numeric(
        mixed $value,
        string $fieldName,
        ?float $min = null,
        ?float $max = null
    ): ?string {
        if ($value === null || $value === '') {
            return null; // Use required() separately for presence checks
        }

        if (!is_numeric($value)) {
            return "{$fieldName} must be a number.";
        }

        $num = (float)$value;

        if ($min !== null && $num < $min) {
            return "{$fieldName} must be at least {$min}.";
        }

        if ($max !== null && $num > $max) {
            return "{$fieldName} must not exceed {$max}.";
        }

        return null;
    }

    /**
     * Validate a date string against a given format.
     *
     * @param  mixed  $value   The value to check
     * @param  string $format  Expected date format (default: 'Y-m-d')
     * @return string|null     Error message, or null on success
     */
    public static function date(mixed $value, string $format = 'Y-m-d'): ?string
    {
        if ($value === null || $value === '') {
            return null; // Use required() separately for presence checks
        }

        $d = \DateTime::createFromFormat($format, (string)$value);

        if ($d === false || $d->format($format) !== (string)$value) {
            return "Invalid date. Expected format: {$format}.";
        }

        return null;
    }

    /**
     * Validate that a value exists in an allowed list.
     *
     * @param  mixed  $value      The value to check
     * @param  array  $allowed    List of allowed values
     * @param  string $fieldName  Field label used in the error message
     * @return string|null        Error message, or null on success
     */
    public static function inArray(mixed $value, array $allowed, string $fieldName): ?string
    {
        if ($value === null || $value === '') {
            return null; // Use required() separately for presence checks
        }

        if (!in_array($value, $allowed, strict: true)) {
            $list = implode(', ', array_map(fn($v) => "'{$v}'", $allowed));
            return "{$fieldName} must be one of: {$list}.";
        }

        return null;
    }

    /**
     * Validate that a string does not exceed a maximum length.
     *
     * @param  mixed  $value      The value to check
     * @param  int    $max        Maximum allowed character length
     * @param  string $fieldName  Field label used in the error message
     * @return string|null        Error message, or null on success
     */
    public static function maxLength(mixed $value, int $max, string $fieldName): ?string
    {
        if ($value === null || $value === '') {
            return null; // Use required() separately for presence checks
        }

        if (mb_strlen((string)$value) > $max) {
            return "{$fieldName} must not exceed {$max} characters.";
        }

        return null;
    }

    /**
     * Check that a value is unique in the database for a given table/column.
     *
     * Optionally excludes a specific row ID (useful for update operations).
     *
     * @param  string      $table      Table name
     * @param  string      $field      Column name
     * @param  mixed       $value      Value to check for uniqueness
     * @param  int|null    $excludeId  Row ID to exclude from the check (for updates)
     * @return string|null             Error message, or null if unique
     */
    public static function unique(
        string $table,
        string $field,
        mixed $value,
        ?int $excludeId = null
    ): ?string {
        try {
            $db = Database::getInstance();

            $sql    = "SELECT id FROM `{$table}` WHERE `{$field}` = ?";
            $params = [$value];

            if ($excludeId !== null) {
                $sql    .= " AND id != ?";
                $params[] = $excludeId;
            }

            $sql .= " LIMIT 1";

            $row = $db->fetchOne($sql, $params);

            if ($row !== null) {
                return "This {$field} is already in use.";
            }
        } catch (\Throwable $e) {
            // If the DB check fails, fail open (do not block the user)
            // The caller should handle DB errors separately
        }

        return null;
    }

    // ── Bulk validation ───────────────────────────────────────────

    /**
     * Validate an associative data array against a rules map.
     *
     * Rules format:
     *   'fieldName' => ['rule1', 'rule2:arg1:arg2', ...]
     *
     * Supported rules:
     *   required
     *   email
     *   phone
     *   numeric              (no range)
     *   numeric:min          (min only)
     *   numeric:min:max      (min and max)
     *   date                 (default Y-m-d format)
     *   date:FORMAT          (custom format)
     *   in:val1,val2,val3
     *   max:N                (max string length)
     *   unique:table:field
     *   unique:table:field:excludeId
     *
     * @param  array $data   Associative array of field => value
     * @param  array $rules  Associative array of field => rule list
     * @return array         Associative array of field => first error message (empty if valid)
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = self::applyRule($rule, $value, $field, $data);

                if ($error !== null) {
                    $errors[$field] = $error;
                    break; // Report only the first error per field
                }
            }
        }

        return $errors;
    }

    // ── Internal rule dispatcher ──────────────────────────────────

    /**
     * Apply a single rule string to a value and return an error message or null.
     *
     * @param  string $rule       Rule string (e.g. "numeric:0:1000")
     * @param  mixed  $value      The value being validated
     * @param  string $field      Field name for error messages
     * @param  array  $allData    Full data array (available for cross-field rules)
     * @return string|null
     */
    private static function applyRule(
        string $rule,
        mixed $value,
        string $field,
        array $allData
    ): ?string {
        $parts = explode(':', $rule, 4);
        $name  = strtolower($parts[0]);

        return match ($name) {
            'required' => self::required($value, self::label($field)),

            'email'    => self::email($value),

            'phone'    => self::phone($value),

            'numeric'  => self::numeric(
                $value,
                self::label($field),
                isset($parts[1]) && $parts[1] !== '' ? (float)$parts[1] : null,
                isset($parts[2]) && $parts[2] !== '' ? (float)$parts[2] : null
            ),

            'date'     => self::date($value, $parts[1] ?? 'Y-m-d'),

            'in'       => isset($parts[1])
                ? self::inArray($value, explode(',', $parts[1]), self::label($field))
                : null,

            'max'      => isset($parts[1])
                ? self::maxLength($value, (int)$parts[1], self::label($field))
                : null,

            'unique'   => isset($parts[1], $parts[2])
                ? self::unique(
                    $parts[1],
                    $parts[2],
                    $value,
                    isset($parts[3]) && $parts[3] !== '' ? (int)$parts[3] : null
                )
                : null,

            default    => null, // Unknown rules are silently ignored
        };
    }

    /**
     * Convert a snake_case or camelCase field name to a human-readable label.
     *
     * @param  string $field  e.g. "full_name" or "emailAddress"
     * @return string         e.g. "Full Name" or "Email Address"
     */
    private static function label(string $field): string
    {
        // Convert camelCase to spaces, then replace underscores
        $spaced = preg_replace('/([a-z])([A-Z])/', '$1 $2', $field) ?? $field;
        return ucwords(str_replace('_', ' ', $spaced));
    }
}
