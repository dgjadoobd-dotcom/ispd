<?php

/**
 * RadiusBulkService
 *
 * Handles bulk CSV import/export of RADIUS users.
 * Operates directly on a PDO connection (radius database).
 *
 * CSV format: username,password,group,profile
 *   - username : radcheck.username
 *   - password : radcheck.value (Cleartext-Password)
 *   - group    : radusergroup.groupname
 *   - profile  : radius_user_profiles.notes (optional column)
 */
class RadiusBulkService
{
    private const MAX_IMPORT_ROWS = 500;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Export ────────────────────────────────────────────────────────────────

    /**
     * Export RADIUS users as a CSV string.
     *
     * @param array $filters  Optional: ['group' => string, 'username_like' => string]
     * @return string  CSV content including header row
     */
    public function exportUsers(array $filters = []): string
    {
        $params = [];

        $sql = "SELECT c.username,
                       c.value        AS password,
                       COALESCE(g.groupname, '') AS `group`,
                       COALESCE(p.notes, '')     AS profile
                FROM radcheck c
                LEFT JOIN radusergroup g ON g.username = c.username
                LEFT JOIN radius_user_profiles p ON p.user_id = c.username
                WHERE c.attribute = 'Cleartext-Password'";

        if (!empty($filters['group'])) {
            $sql .= " AND g.groupname = ?";
            $params[] = $filters['group'];
        }

        if (!empty($filters['username_like'])) {
            $sql .= " AND c.username LIKE ?";
            $params[] = '%' . $filters['username_like'] . '%';
        }

        $sql .= " ORDER BY c.username";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build CSV in memory
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['username', 'password', 'group', 'profile']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['username'],
                $row['password'],
                $row['group'],
                $row['profile'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    // ── Import ────────────────────────────────────────────────────────────────

    /**
     * Import RADIUS users from a CSV string.
     *
     * @param string $csvContent  Raw CSV text (headers: username,password,group,profile)
     * @return array  ['imported' => int, 'skipped' => int, 'errors' => array]
     */
    public function importUsers(string $csvContent): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csvContent);
        rewind($handle);

        // Read and normalise header
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV is empty or unreadable']];
        }
        $header = array_map('strtolower', array_map('trim', $header));

        // Validate required columns
        if (!in_array('username', $header, true) || !in_array('password', $header, true)) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV must contain username and password columns']];
        }

        $hasGroup   = in_array('group', $header, true);
        $hasProfile = in_array('profile', $header, true);

        // Read all data rows first so we can enforce the row limit before touching the DB
        $dataRows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue; // skip blank lines
            }
            $dataRows[] = array_combine($header, array_pad($row, count($header), ''));
        }
        fclose($handle);

        if (count($dataRows) > self::MAX_IMPORT_ROWS) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['Import exceeds maximum of ' . self::MAX_IMPORT_ROWS . ' rows'],
            ];
        }

        // Process inside a transaction so a fatal DB error rolls everything back
        $this->db->beginTransaction();

        try {
            foreach ($dataRows as $index => $row) {
                $lineNum = $index + 2; // +2: 1-based + header row

                $validationError = $this->validateCsvRow($row);
                if ($validationError !== null) {
                    $errors[] = "Row {$lineNum}: {$validationError}";
                    $skipped++;
                    continue;
                }

                $username = trim($row['username']);
                $password = trim($row['password']);
                $group    = $hasGroup   ? trim($row['group'])   : '';
                $profile  = $hasProfile ? trim($row['profile']) : '';

                // Upsert radcheck (Cleartext-Password)
                $this->upsertRadcheck($username, $password);

                // Upsert radusergroup
                if ($group !== '') {
                    $this->upsertRadusergroup($username, $group);
                }

                // Upsert radius_user_profiles (notes column)
                if ($hasProfile && $profile !== '') {
                    $this->upsertUserProfile($username, $profile);
                }

                $imported++;
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'imported' => 0,
                'skipped'  => $skipped,
                'errors'   => array_merge($errors, ['Fatal DB error: ' . $e->getMessage()]),
            ];
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    // ── Validation ────────────────────────────────────────────────────────────

    /**
     * Validate a single CSV data row.
     *
     * @param array $row  Associative row from CSV (keys already lowercased)
     * @return string|null  Error message, or null if valid
     */
    public function validateCsvRow(array $row): ?string
    {
        $username = trim($row['username'] ?? '');
        $password = trim($row['password'] ?? '');

        if ($username === '') {
            return 'username is required';
        }

        if ($password === '') {
            return 'password is required';
        }

        return null;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function upsertRadcheck(string $username, string $password): void
    {
        // Delete existing Cleartext-Password entry then re-insert (portable upsert)
        $del = $this->db->prepare(
            "DELETE FROM radcheck WHERE username = ? AND attribute = 'Cleartext-Password'"
        );
        $del->execute([$username]);

        $ins = $this->db->prepare(
            "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)"
        );
        $ins->execute([$username, $password]);
    }

    private function upsertRadusergroup(string $username, string $groupname): void
    {
        $del = $this->db->prepare("DELETE FROM radusergroup WHERE username = ?");
        $del->execute([$username]);

        $ins = $this->db->prepare(
            "INSERT INTO radusergroup (username, groupname, priority) VALUES (?, ?, 1)"
        );
        $ins->execute([$username, $groupname]);
    }

    private function upsertUserProfile(string $username, string $notes): void
    {
        // Use INSERT ... ON DUPLICATE KEY UPDATE when available (MySQL/MariaDB)
        // Falls back to delete+insert for SQLite compatibility
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $this->db->prepare(
                "INSERT OR REPLACE INTO radius_user_profiles (user_id, notes) VALUES (?, ?)"
            );
            $stmt->execute([$username, $notes]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO radius_user_profiles (user_id, notes)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE notes = VALUES(notes), updated_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$username, $notes]);
        }
    }
}
