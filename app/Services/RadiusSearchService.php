<?php

class RadiusSearchService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Search RADIUS users with optional filters and pagination.
     *
     * @param array $criteria Supported keys: username, group, ip_address, online (bool), mac_address
     * @param int   $limit
     * @param int   $offset
     * @return array  Each row: username, group, framed_ip, online_status, mac_address
     */
    public function searchUsers(array $criteria, int $limit = 50, int $offset = 0): array {
        [$where, $params] = $this->buildWhere($criteria);

        $sql = "SELECT
                    rc.username,
                    rug.groupname        AS `group`,
                    rs.framed_ip,
                    CASE WHEN rs.id IS NOT NULL THEN 1 ELSE 0 END AS online_status,
                    rup.mac_address
                FROM radcheck rc
                LEFT JOIN radusergroup  rug ON rug.username  = rc.username
                LEFT JOIN radius_sessions rs  ON rs.username  = rc.username AND rs.status = 'active'
                LEFT JOIN radius_user_profiles rup ON rup.user_id = rc.username
                WHERE rc.attribute = 'Cleartext-Password'" .
                ($where ? " AND $where" : '') .
                " ORDER BY rc.username
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count RADIUS users matching the given criteria (for pagination).
     *
     * @param array $criteria Same keys as searchUsers
     * @return int
     */
    public function countUsers(array $criteria): int {
        [$where, $params] = $this->buildWhere($criteria);

        $sql = "SELECT COUNT(*) AS total
                FROM radcheck rc
                LEFT JOIN radusergroup  rug ON rug.username  = rc.username
                LEFT JOIN radius_sessions rs  ON rs.username  = rc.username AND rs.status = 'active'
                LEFT JOIN radius_user_profiles rup ON rup.user_id = rc.username
                WHERE rc.attribute = 'Cleartext-Password'" .
                ($where ? " AND $where" : '');

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Build the WHERE clause and parameter map from $criteria.
     *
     * @return array{0: string, 1: array}
     */
    private function buildWhere(array $criteria): array {
        $conditions = [];
        $params     = [];

        if (!empty($criteria['username'])) {
            $conditions[] = 'rc.username LIKE :username';
            $params[':username'] = '%' . $criteria['username'] . '%';
        }

        if (!empty($criteria['group'])) {
            $conditions[] = 'rug.groupname = :group';
            $params[':group'] = $criteria['group'];
        }

        if (!empty($criteria['ip_address'])) {
            $conditions[] = 'rs.framed_ip = :ip_address';
            $params[':ip_address'] = $criteria['ip_address'];
        }

        if (isset($criteria['online'])) {
            $conditions[] = $criteria['online']
                ? 'rs.id IS NOT NULL'
                : 'rs.id IS NULL';
        }

        if (!empty($criteria['mac_address'])) {
            $conditions[] = 'rup.mac_address = :mac_address';
            $params[':mac_address'] = $criteria['mac_address'];
        }

        return [implode(' AND ', $conditions), $params];
    }
}
