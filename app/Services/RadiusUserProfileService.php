<?php

/**
 * RadiusUserProfileService
 *
 * Manages RADIUS user profiles stored in the radius_user_profiles table.
 * Requires a PDO connection to the RADIUS database.
 */
class RadiusUserProfileService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch a user profile by username (user_id).
     *
     * @return array|null Profile row, or null if not found.
     */
    public function getProfile(string $username): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM radius_user_profiles WHERE user_id = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Upsert a user profile.
     *
     * @param array $data Accepted keys: mac_address, ip_binding, concurrent_session_limit, notes
     * @throws InvalidArgumentException if concurrent_session_limit is out of range [1-10]
     */
    public function saveProfile(string $username, array $data): bool {
        if (isset($data['concurrent_session_limit'])) {
            $limit = (int) $data['concurrent_session_limit'];
            if ($limit < 1 || $limit > 10) {
                throw new \InvalidArgumentException(
                    'concurrent_session_limit must be between 1 and 10.'
                );
            }
            $data['concurrent_session_limit'] = $limit;
        }

        $existing = $this->getProfile($username);

        if ($existing === null) {
            // INSERT
            $stmt = $this->pdo->prepare(
                'INSERT INTO radius_user_profiles
                    (user_id, mac_address, ip_binding, concurrent_session_limit, notes)
                 VALUES (?, ?, ?, ?, ?)'
            );
            return $stmt->execute([
                $username,
                $data['mac_address'] ?? null,
                $data['ip_binding'] ?? null,
                $data['concurrent_session_limit'] ?? 1,
                $data['notes'] ?? null,
            ]);
        }

        // UPDATE — only touch supplied keys
        $fields = [];
        $params = [];
        foreach (['mac_address', 'ip_binding', 'concurrent_session_limit', 'notes'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($fields)) {
            return true; // nothing to update
        }

        $params[] = $username;
        $stmt = $this->pdo->prepare(
            'UPDATE radius_user_profiles SET ' . implode(', ', $fields) . ' WHERE user_id = ?'
        );
        return $stmt->execute($params);
    }

    /**
     * Delete a user profile.
     *
     * @return bool True if a row was deleted, false otherwise.
     */
    public function deleteProfile(string $username): bool {
        $stmt = $this->pdo->prepare(
            'DELETE FROM radius_user_profiles WHERE user_id = ?'
        );
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Return merged profile data together with RADIUS check attributes and group.
     *
     * Includes:
     *  - All radius_user_profiles columns
     *  - cleartext_password  (radcheck Cleartext-Password value)
     *  - simultaneous_use    (radcheck Simultaneous-Use value)
     *  - groupname           (radusergroup groupname)
     *
     * @return array Merged data; profile keys are null when no profile row exists.
     */
    public function getProfileWithRadiusAttributes(string $username): array {
        // Profile
        $profile = $this->getProfile($username) ?? [];

        // Cleartext-Password
        $stmt = $this->pdo->prepare(
            "SELECT value FROM radcheck WHERE username = ? AND attribute = 'Cleartext-Password' LIMIT 1"
        );
        $stmt->execute([$username]);
        $pwRow = $stmt->fetch(PDO::FETCH_ASSOC);

        // Simultaneous-Use
        $stmt = $this->pdo->prepare(
            "SELECT value FROM radcheck WHERE username = ? AND attribute = 'Simultaneous-Use' LIMIT 1"
        );
        $stmt->execute([$username]);
        $simRow = $stmt->fetch(PDO::FETCH_ASSOC);

        // Group
        $stmt = $this->pdo->prepare(
            'SELECT groupname FROM radusergroup WHERE username = ? ORDER BY priority ASC LIMIT 1'
        );
        $stmt->execute([$username]);
        $groupRow = $stmt->fetch(PDO::FETCH_ASSOC);

        return array_merge($profile, [
            'cleartext_password' => $pwRow  ? $pwRow['value']   : null,
            'simultaneous_use'   => $simRow ? $simRow['value']  : null,
            'groupname'          => $groupRow ? $groupRow['groupname'] : null,
        ]);
    }
}
