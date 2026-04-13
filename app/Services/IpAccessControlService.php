<?php

class IpAccessControlService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Check if an IP address is allowed.
     * - No rules: allow all.
     * - Whitelist rules exist: IP must match at least one.
     * - Only blacklist rules: IP must not match any.
     */
    public function isAllowed(string $ipAddress): bool {
        $rules = $this->getRules();

        if (empty($rules)) {
            return true;
        }

        $whitelists = array_filter($rules, fn($r) => $r['type'] === 'whitelist');
        $blacklists = array_filter($rules, fn($r) => $r['type'] === 'blacklist');

        if (!empty($whitelists)) {
            foreach ($whitelists as $rule) {
                if ($this->matchesCidr($ipAddress, $rule['cidr'])) {
                    return true;
                }
            }
            return false;
        }

        // Only blacklist rules exist
        foreach ($blacklists as $rule) {
            if ($this->matchesCidr($ipAddress, $rule['cidr'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add an IP rule to the ip_access_rules table.
     */
    public function addRule(string $cidr, string $type = 'whitelist', ?string $comment = null): bool {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ip_access_rules (cidr, type, comment) VALUES (:cidr, :type, :comment)'
        );
        return $stmt->execute([
            ':cidr'    => $cidr,
            ':type'    => $type,
            ':comment' => $comment,
        ]);
    }

    /**
     * Remove a rule by ID.
     */
    public function removeRule(int $ruleId): bool {
        $stmt = $this->pdo->prepare('DELETE FROM ip_access_rules WHERE id = :id');
        $stmt->execute([':id' => $ruleId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all rules ordered by type, cidr.
     */
    public function getRules(): array {
        $stmt = $this->pdo->query('SELECT * FROM ip_access_rules ORDER BY type, cidr');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if an IP matches a CIDR range (supports IPv4 and single IPs).
     */
    public function matchesCidr(string $ip, string $cidr): bool {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $prefix = (int) $prefix;

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $prefix === 0 ? 0 : (~0 << (32 - $prefix));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
