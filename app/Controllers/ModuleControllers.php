<?php
// Stub controllers for remaining modules
// Each follows the same pattern as CustomerController

class GponController {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void { redirect(base_url('gpon/olts')); }

    public function olts(): void {
        $pageTitle = 'OLT Management'; $currentPage = 'gpon'; $currentSubPage = 'olts';
        $olts = $this->db->fetchAll("SELECT o.*, b.name as branch_name FROM olts o LEFT JOIN branches b ON b.id=o.branch_id ORDER BY o.created_at DESC");
        $branches = $this->db->fetchAll("SELECT id,name FROM branches WHERE is_active=1");
        $viewFile = BASE_PATH . '/views/gpon/olts.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeOlt(): void {
        $data = [
            'branch_id'   => (int)$_POST['branch_id'],
            'name'        => sanitize($_POST['name'] ?? ''),
            'model'       => sanitize($_POST['model'] ?? ''),
            'ip_address'  => sanitize($_POST['ip_address'] ?? ''),
            'protocol'    => sanitize($_POST['protocol'] ?? 'ssh'),
            'access_port' => (int)($_POST['access_port'] ?? 22),
            'username'    => sanitize($_POST['username'] ?? ''),
            'password'    => sanitize($_POST['password'] ?? ''),
            'snmp_community' => sanitize($_POST['snmp_community'] ?? ''),
            'total_ports' => (int)($_POST['total_ports'] ?? 16),
            'location'    => sanitize($_POST['location'] ?? ''),
            'is_active'   => 1,
        ];
        if(empty($data['password'])) unset($data['password']);
        $this->db->insert('olts', $data);
        redirect(base_url('gpon/olts'));
    }

    public function updateOlt(): void {
        $id = (int)($_POST['id'] ?? 0);
        if(!$id) redirect(base_url('gpon/olts'));
        
        $data = [
            'branch_id'     => (int)$_POST['branch_id'],
            'name'          => sanitize($_POST['name'] ?? ''),
            'model'         => sanitize($_POST['model'] ?? ''),
            'ip_address'    => sanitize($_POST['ip_address'] ?? ''),
            'protocol'      => sanitize($_POST['protocol'] ?? 'ssh'),
            'access_port'   => (int)($_POST['access_port'] ?? 22),
            'username'      => sanitize($_POST['username'] ?? ''),
            'snmp_community'=> sanitize($_POST['snmp_community'] ?? ''),
            'snmp_version'  => sanitize($_POST['snmp_version'] ?? 'v2c'),
            'total_ports'   => (int)($_POST['total_ports'] ?? 16),
            'location'      => sanitize($_POST['location'] ?? ''),
            'is_active'     => (int)($_POST['is_active'] ?? 1),
        ];
        if(!empty($_POST['password'])) {
            $data['password'] = sanitize($_POST['password']);
        }
        $this->db->update('olts', $data, 'id = ?', [$id]);
        redirect(base_url('gpon/olts'));
    }

    public function oltOnus(): void {
        $pageTitle = 'OLT ONUs List'; $currentPage = 'gpon';

        $oltId  = (int)($_GET['olt_id'] ?? 0);
        $search = sanitize($_GET['search'] ?? '');

        $where  = '1=1';
        $params = [];

        if ($oltId) {
            // Match ONUs directly linked to OLT, or via splitter
            $where .= ' AND (o.olt_id = ? OR s.olt_id = ?)';
            $params[] = $oltId;
            $params[] = $oltId;
        }
        if ($search) {
            $where .= ' AND (o.serial_number LIKE ? OR c.full_name LIKE ? OR o.mac_address LIKE ? OR o.description LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $onus = $this->db->fetchAll("
            SELECT o.*,
                   s.name as splitter_name, s.olt_id as splitter_olt_id, s.distance_from_olt,
                   COALESCE(olt_direct.name, olt_via_splitter.name) as olt_name,
                   COALESCE(olt_direct.ip_address, olt_via_splitter.ip_address) as olt_ip,
                   c.full_name as customer_name, c.customer_code, c.pppoe_username as customer_username
            FROM onus o
            LEFT JOIN splitters s ON s.id = o.splitter_id
            LEFT JOIN olts olt_direct ON olt_direct.id = o.olt_id
            LEFT JOIN olts olt_via_splitter ON olt_via_splitter.id = s.olt_id
            LEFT JOIN customers c ON c.id = o.customer_id
            WHERE $where
            ORDER BY olt_name, o.created_at DESC
        ", $params);

        $olts      = $this->db->fetchAll("SELECT id, name FROM olts WHERE is_active=1 ORDER BY name");
        $splitters = $this->db->fetchAll("SELECT id, name FROM splitters WHERE is_active=1");

        $viewFile = BASE_PATH . '/views/gpon/olt-onus.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── SNMP API methods ─────────────────────────────────────────────────────

    /** GET /gpon/api/snmp/test/{id} — Test SNMP connection for an OLT */
    public function snmpTest(string $id = '0'): void
    {
        $id  = (int)$id;
        $olt = $id ? $this->db->fetchOne("SELECT * FROM olts WHERE id=?", [$id]) : null;
        if (!$olt) { jsonResponse(['success' => false, 'error' => 'OLT not found'], 404); }

        require_once BASE_PATH . '/app/Services/SnmpOltService.php';
        $community = $olt['snmp_community'] ?: 'public';
        $version   = str_replace('v', '', $olt['snmp_version'] ?? '2c');
        $svc       = new SnmpOltService($olt['ip_address'], $community, $version);

        $conn    = $svc->testConnection();
        $sysInfo = [];
        $onuList = [];

        if ($conn['success'] && $conn['method'] === 'snmp') {
            $sysInfo = $svc->getSystemInfo();
            $onuList = $svc->getOnuList();
        } elseif ($conn['method'] === 'tcp_ping') {
            // Try Telnet as fallback
            if (!empty($olt['username'])) {
                require_once BASE_PATH . '/app/Services/TelnetOltService.php';
                $telnet = new TelnetOltService($olt['ip_address'], $olt['username'], $olt['password'] ?? '', 23, 8);
                $telnetConn = $telnet->testConnection();
                if ($telnetConn['success']) {
                    $conn    = $telnetConn;
                    $sysInfo = $telnetConn['system_info'] ?? [];
                    $onuList = $telnet->getOnuList();
                } else {
                    $sysInfo = ['sysDescr' => 'SNMP blocked (firewall/community)', 'sysName' => $olt['name'], 'sysUpTime' => '—', 'vendor' => 'Unknown'];
                }
            } else {
                $sysInfo = ['sysDescr' => 'SNMP blocked (firewall/community)', 'sysName' => $olt['name'], 'sysUpTime' => '—', 'vendor' => 'Unknown'];
            }
        }

        // Update OLT connection status
        $this->db->update('olts', [
            'connection_status' => $conn['success'] ? 'online' : 'offline',
            'last_checked_at'   => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);

        jsonResponse([
            'success'     => $conn['success'],
            'description' => $conn['description'],
            'method'      => $conn['method'],
            'system_info' => $sysInfo,
            'onu_count'   => count($onuList),
            'snmp_available' => $svc->isAvailable(),
            'error'       => $conn['error'],
        ]);
    }

    /** POST /gpon/api/snmp/sync/{id} — Sync ONU list from OLT via SNMP or Telnet */
    public function syncOnus(string $id = '0'): void
    {
        $id  = (int)$id;
        $olt = $id ? $this->db->fetchOne("SELECT * FROM olts WHERE id=?", [$id]) : null;
        if (!$olt) { jsonResponse(['success' => false, 'error' => 'OLT not found'], 404); }

        require_once BASE_PATH . '/app/Services/SnmpOltService.php';
        require_once BASE_PATH . '/app/Services/TelnetOltService.php';

        $community = $olt['snmp_community'] ?: 'public';
        $version   = str_replace('v', '', $olt['snmp_version'] ?? '2c');
        $onuList   = [];
        $method    = 'none';

        // Try SNMP first
        $snmpSvc = new SnmpOltService($olt['ip_address'], $community, $version);
        $conn    = $snmpSvc->testConnection();

        if ($conn['success'] && ($conn['method'] ?? '') === 'snmp') {
            $onuList = $snmpSvc->getOnuList();
            $method  = 'snmp';
        }

        // Fallback to Telnet if SNMP fails or returns no ONUs
        if (empty($onuList) && !empty($olt['username'])) {
            $telnetSvc = new TelnetOltService(
                $olt['ip_address'],
                $olt['username'],
                $olt['password'] ?? '',
                23,
                10
            );
            $onuList = $telnetSvc->getOnuList();
            $method  = 'telnet';
        }

        if (empty($onuList)) {
            jsonResponse([
                'success' => false,
                'error'   => 'No ONUs returned via SNMP or Telnet. Check credentials and connectivity.',
                'synced'  => 0, 'new_count' => 0, 'updated_count' => 0, 'errors' => [],
            ]);
        }

        $newCount     = 0;
        $updatedCount = 0;
        $errors       = [];
        $now          = date('Y-m-d H:i:s');

        foreach ($onuList as $onu) {
            if (empty($onu['serial'])) continue;
            try {
                // Match by MAC address first (for Telnet-synced ONUs), then serial
                $existing = null;
                if (!empty($onu['mac_address'])) {
                    $existing = $this->db->fetchOne("SELECT * FROM onus WHERE mac_address=?", [$onu['mac_address']]);
                }
                if (!$existing) {
                    $existing = $this->db->fetchOne("SELECT * FROM onus WHERE serial_number=?", [$onu['serial']]);
                }

                if ($existing) {
                    $snapshot = json_encode([
                        'signal_level' => $existing['signal_level'],
                        'status'       => $existing['status'],
                        'olt_port'     => $existing['olt_port'],
                        'synced_at'    => $existing['last_synced_at'],
                    ]);
                    $this->db->update('onus', [
                        'olt_id'            => $id,
                        'signal_level'      => $onu['signal_dbm'] ?? $existing['signal_level'],
                        'status'            => ($onu['status'] !== 'unknown') ? $onu['status'] : $existing['status'],
                        'olt_port'          => $onu['olt_port'],
                        'mac_address'       => !empty($onu['mac_address']) ? $onu['mac_address'] : $existing['mac_address'],
                        'description'       => ($onu['description'] ?? '') ?: $existing['description'],
                        'deregister_reason' => $onu['deregister_reason'] ?? $existing['deregister_reason'],
                        'last_synced_at'    => $now,
                        'previous_snapshot' => $snapshot,
                    ], 'id=?', [$existing['id']]);
                    $updatedCount++;
                } else {
                    $this->db->insert('onus', [
                        'serial_number'     => $onu['serial'],
                        'mac_address'       => $onu['mac_address'] ?? '',
                        'olt_id'            => $id,
                        'branch_id'         => $olt['branch_id'],
                        'olt_port'          => $onu['olt_port'],
                        'signal_level'      => $onu['signal_dbm'] ?? null,
                        'status'            => ($onu['status'] !== 'unknown') ? $onu['status'] : 'installed',
                        'description'       => $onu['description'] ?? '',
                        'deregister_reason' => $onu['deregister_reason'] ?? null,
                        'last_synced_at'    => $now,
                        'created_at'        => $now,
                    ]);
                    $newCount++;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Serial ' . $onu['serial'] . ': ' . $e->getMessage();
            }
        }

        jsonResponse([
            'success'       => true,
            'synced'        => $newCount + $updatedCount,
            'new_count'     => $newCount,
            'updated_count' => $updatedCount,
            'method'        => $method,
            'errors'        => $errors,
        ]);
    }

    /** GET /gpon/api/olts/{id}/onus — Return ONU list for an OLT as JSON */
    public function getOltOnuList(string $id = '0'): void
    {
        $id  = (int)$id;
        $olt = $id ? $this->db->fetchOne("SELECT id, name FROM olts WHERE id=?", [$id]) : null;
        if (!$olt) { jsonResponse(['success' => false, 'error' => 'OLT not found'], 404); }

        $onus = $this->db->fetchAll("
            SELECT o.*, c.full_name as customer_name, c.customer_code
            FROM onus o
            LEFT JOIN customers c ON c.id = o.customer_id
            LEFT JOIN splitters s ON s.id = o.splitter_id
            WHERE o.olt_id = ? OR s.olt_id = ?
            ORDER BY o.created_at DESC
        ", [$id, $id]);

        jsonResponse(['success' => true, 'olt' => $olt, 'onus' => $onus]);
    }

    /** POST /gpon/api/onus/update/{id} — Update ONU fields via JSON API */
    public function updateOnuApi(string $id = '0'): void
    {
        $id  = (int)$id;
        $onu = $id ? $this->db->fetchOne("SELECT id FROM onus WHERE id=?", [$id]) : null;
        if (!$onu) { jsonResponse(['success' => false, 'error' => 'ONU not found'], 404); }

        $allowed = ['serial_number','model','brand','mac_address','ip_address','olt_port','signal_level','status','description','customer_id'];
        $data    = [];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = in_array($field, ['signal_level','customer_id'])
                    ? (float)$_POST[$field] ?: null
                    : sanitize($_POST[$field]);
            }
        }
        if (empty($data)) { jsonResponse(['success' => false, 'error' => 'No data provided']); }

        $this->db->update('onus', $data, 'id=?', [$id]);
        $updated = $this->db->fetchOne("SELECT * FROM onus WHERE id=?", [$id]);
        jsonResponse(['success' => true, 'onu' => $updated]);
    }

    /** POST /gpon/api/onus/delete/{id} — Delete an ONU via JSON API */
    public function deleteOnuApi(string $id = '0'): void
    {
        $id  = (int)$id;
        $onu = $id ? $this->db->fetchOne("SELECT id FROM onus WHERE id=?", [$id]) : null;
        if (!$onu) { jsonResponse(['success' => false, 'error' => 'ONU not found'], 404); }

        $this->db->delete('onus', 'id=?', [$id]);
        jsonResponse(['success' => true, 'message' => 'ONU deleted']);
    }

    /**
     * GET /gpon/api/olts/check/{id}
     * Protocol-aware connection check: SNMP → Telnet → TCP ping fallback.
     */
    public function checkOltConnection(string $id = '0'): void {
        $id  = (int)$id;
        $olt = $id ? $this->db->fetchOne("SELECT * FROM olts WHERE id=?", [$id]) : null;
        if (!$olt) { jsonResponse(['status' => 'error', 'message' => 'OLT not found'], 404); }

        $result = $this->probeOlt($olt);

        $this->db->update('olts', [
            'connection_status' => $result['online'] ? 'online' : 'offline',
            'last_checked_at'   => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);

        jsonResponse([
            'status'      => 'success',
            'online'      => $result['online'],
            'method'      => $result['method'],
            'description' => $result['description'],
            'ip'          => $olt['ip_address'],
            'port'        => $olt['access_port'] ?? 22,
        ]);
    }

    /**
     * GET /gpon/api/olts/check-all
     * Protocol-aware check for all active OLTs.
     */
    public function checkAllOltConnections(): void {
        $olts    = $this->db->fetchAll("SELECT * FROM olts WHERE is_active=1");
        $results = [];

        foreach ($olts as $olt) {
            $result = $this->probeOlt($olt);
            $this->db->update('olts', [
                'connection_status' => $result['online'] ? 'online' : 'offline',
                'last_checked_at'   => date('Y-m-d H:i:s'),
            ], 'id=?', [$olt['id']]);
            $results[] = [
                'id'          => $olt['id'],
                'name'        => $olt['name'],
                'online'      => $result['online'],
                'method'      => $result['method'],
                'description' => $result['description'],
            ];
        }

        jsonResponse(['status' => 'success', 'results' => $results]);
    }

    /**
     * GET /gpon/api/olts/{id}/onus/live
     * Fetch live ONU list directly from OLT (SNMP or Telnet), no DB cache.
     */
    public function getLiveOnuList(string $id = '0'): void
    {
        $id  = (int)$id;
        $olt = $id ? $this->db->fetchOne("SELECT * FROM olts WHERE id=?", [$id]) : null;
        if (!$olt) { jsonResponse(['success' => false, 'error' => 'OLT not found'], 404); }

        require_once BASE_PATH . '/app/Services/SnmpOltService.php';
        require_once BASE_PATH . '/app/Services/TelnetOltService.php';

        $community = $olt['snmp_community'] ?: 'public';
        $version   = str_replace('v', '', $olt['snmp_version'] ?? '2c');
        $onuList   = [];
        $method    = 'none';

        // Try SNMP first
        $snmpSvc = new SnmpOltService($olt['ip_address'], $community, $version);
        $conn    = $snmpSvc->testConnection();

        if ($conn['success'] && ($conn['method'] ?? '') === 'snmp') {
            $onuList = $snmpSvc->getOnuList();
            $method  = 'snmp';
        }

        // Fallback to Telnet
        if (empty($onuList) && !empty($olt['username'])) {
            $telnet  = new TelnetOltService($olt['ip_address'], $olt['username'], $olt['password'] ?? '', 23, 10);
            $onuList = $telnet->getOnuList();
            $method  = 'telnet';
        }

        // Annotate each ONU with DB customer info if MAC or serial matches
        foreach ($onuList as &$onu) {
            $customer = null;
            if (!empty($onu['mac_address'])) {
                $customer = $this->db->fetchOne(
                    "SELECT c.full_name, c.customer_code FROM onus o JOIN customers c ON c.id=o.customer_id WHERE o.mac_address=? LIMIT 1",
                    [$onu['mac_address']]
                );
            }
            if (!$customer && !empty($onu['serial'])) {
                $customer = $this->db->fetchOne(
                    "SELECT c.full_name, c.customer_code FROM onus o JOIN customers c ON c.id=o.customer_id WHERE o.serial_number=? LIMIT 1",
                    [$onu['serial']]
                );
            }
            $onu['customer_name'] = $customer['full_name']    ?? null;
            $onu['customer_code'] = $customer['customer_code'] ?? null;
        }
        unset($onu);

        jsonResponse([
            'success'   => true,
            'method'    => $method,
            'olt_name'  => $olt['name'],
            'olt_ip'    => $olt['ip_address'],
            'total'     => count($onuList),
            'online'    => count(array_filter($onuList, fn($o) => ($o['status'] ?? '') === 'online')),
            'offline'   => count(array_filter($onuList, fn($o) => ($o['status'] ?? '') !== 'online')),
            'onus'      => $onuList,
        ]);
    }

    /**
     * Probe an OLT using its configured protocol.
     * Returns ['online' => bool, 'method' => string, 'description' => string]
     */
    private function probeOlt(array $olt): array {
        $ip       = $olt['ip_address'] ?? '';
        $protocol = strtolower($olt['protocol'] ?? 'ssh');
        $port     = (int)($olt['access_port'] ?? 22);

        if (empty($ip)) {
            return ['online' => false, 'method' => 'none', 'description' => 'No IP configured'];
        }

        // SNMP protocol: use SnmpOltService
        if ($protocol === 'snmp') {
            require_once BASE_PATH . '/app/Services/SnmpOltService.php';
            $community = $olt['snmp_community'] ?: 'public';
            $version   = str_replace('v', '', $olt['snmp_version'] ?? '2c');
            $svc       = new SnmpOltService($ip, $community, $version);
            $conn      = $svc->testConnection();
            return [
                'online'      => $conn['success'],
                'method'      => $conn['method'],
                'description' => $conn['description'] ?: ($conn['error'] ?? ''),
            ];
        }

        // Telnet protocol: use TelnetOltService
        if ($protocol === 'telnet' && !empty($olt['username'])) {
            require_once BASE_PATH . '/app/Services/TelnetOltService.php';
            $telnet = new TelnetOltService($ip, $olt['username'], $olt['password'] ?? '', $port ?: 23, 6);
            $conn   = $telnet->testConnection();
            return [
                'online'      => $conn['success'],
                'method'      => 'telnet',
                'description' => $conn['description'] ?? ($conn['error'] ?? ''),
            ];
        }

        // SSH / HTTP / HTTPS / generic: TCP socket check on configured port
        $sock = @fsockopen($ip, $port ?: 22, $errno, $errstr, 4);
        if ($sock) {
            fclose($sock);
            return ['online' => true, 'method' => 'tcp', 'description' => "TCP port $port open"];
        }

        // Last resort: ICMP ping
        $pingCmd = PHP_OS_FAMILY === 'Windows'
            ? "ping -n 1 -w 2000 " . escapeshellarg($ip) . " 2>nul"
            : "ping -c 1 -W 2 "    . escapeshellarg($ip) . " 2>/dev/null";
        exec($pingCmd, $out, $ret);
        return [
            'online'      => $ret === 0,
            'method'      => 'icmp',
            'description' => $ret === 0 ? 'Host responds to ping' : 'Host unreachable',
        ];
    }

    public function splitters(): void {
        $pageTitle = 'Splitters'; $currentPage = 'gpon'; $currentSubPage = 'splitters';
        $splitters = $this->db->fetchAll("SELECT s.*, o.name as olt_name FROM splitters s LEFT JOIN olts o ON o.id=s.olt_id ORDER BY s.id DESC");
        $olts      = $this->db->fetchAll("SELECT id,name FROM olts WHERE is_active=1");
        $viewFile  = BASE_PATH . '/views/gpon/splitters.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeSplitter(): void {
        $this->db->insert('splitters', [
            'olt_id'   => (int)$_POST['olt_id'] ?: null,
            'name'     => sanitize($_POST['name'] ?? ''),
            'ratio'    => sanitize($_POST['ratio'] ?? '1:8'),
            'location' => sanitize($_POST['location'] ?? ''),
            'olt_port' => (int)($_POST['olt_port'] ?? 0) ?: null,
            'is_active'=> 1,
        ]);
        redirect(base_url('gpon/splitters'));
    }

    public function onus(): void {
        $pageTitle = 'ONU / CPE Devices'; $currentPage = 'gpon'; $currentSubPage = 'onus';
        $page   = max(1,(int)($_GET['page']??1)); $limit=50; $offset=($page-1)*$limit;
        $onus   = $this->db->fetchAll(
            "SELECT o.*,
                    c.full_name as customer_name, c.customer_code, c.id as customer_id,
                    s.name as splitter_name,
                    COALESCE(olt_d.name, olt_s.name) as olt_name,
                    COALESCE(olt_d.id, olt_s.id) as olt_id_resolved
             FROM onus o
             LEFT JOIN customers c ON c.id=o.customer_id
             LEFT JOIN splitters s ON s.id=o.splitter_id
             LEFT JOIN olts olt_d ON olt_d.id=o.olt_id
             LEFT JOIN olts olt_s ON olt_s.id=s.olt_id
             ORDER BY o.id DESC LIMIT $limit OFFSET $offset"
        );
        $splitters = $this->db->fetchAll("SELECT id,name FROM splitters WHERE is_active=1");
        $olts      = $this->db->fetchAll("SELECT id,name FROM olts WHERE is_active=1 ORDER BY name");
        $viewFile  = BASE_PATH . '/views/gpon/onus.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeOnu(): void {
        $serial = strtoupper(trim(sanitize($_POST['serial_number'] ?? '')));
        if (empty($serial)) { $_SESSION['error'] = 'Serial number is required.'; redirect(base_url('gpon/onus')); }

        // Check duplicate
        $exists = $this->db->fetchOne("SELECT id FROM onus WHERE serial_number=?", [$serial]);
        if ($exists) { $_SESSION['error'] = "ONU with serial $serial already exists."; redirect(base_url('gpon/onus')); }

        $this->db->insert('onus', [
            'serial_number'  => $serial,
            'model'          => sanitize($_POST['model'] ?? ''),
            'brand'          => sanitize($_POST['brand'] ?? ''),
            'onu_type'       => sanitize($_POST['onu_type'] ?? 'indoor'),
            'mac_address'    => strtoupper(sanitize($_POST['mac_address'] ?? '')),
            'splitter_id'    => (int)($_POST['splitter_id'] ?? 0) ?: null,
            'olt_id'         => (int)($_POST['olt_id'] ?? 0) ?: null,
            'branch_id'      => (int)($_POST['branch_id'] ?? $_SESSION['branch_id'] ?? 0) ?: null,
            'status'         => 'stock',
            'purchase_price' => (float)($_POST['purchase_price'] ?? 0) ?: null,
            'installed_date' => !empty($_POST['installed_date']) ? $_POST['installed_date'] : null,
            'warranty_expiry'=> !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
            'notes'          => sanitize($_POST['notes'] ?? ''),
        ]);
        $_SESSION['success'] = "ONU $serial added successfully.";
        redirect(base_url('gpon/onus'));
    }

    public function incidents(): void {
        $pageTitle = 'Fiber Incidents'; $currentPage = 'gpon'; $currentSubPage = 'incidents';
        $incidents = $this->db->fetchAll(
            "SELECT fi.*, b.name as branch_name, z.name as zone_name
             FROM fiber_incidents fi LEFT JOIN branches b ON b.id=fi.branch_id LEFT JOIN zones z ON z.id=fi.zone_id
             ORDER BY fi.created_at DESC LIMIT 50"
        );
        $branches = $this->db->fetchAll("SELECT id,name FROM branches WHERE is_active=1");
        $viewFile  = BASE_PATH . '/views/gpon/incidents.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeIncident(): void {
        $this->db->insert('fiber_incidents', [
            'branch_id'          => (int)$_POST['branch_id'],
            'zone_id'            => (int)$_POST['zone_id'] ?: null,
            'title'              => sanitize($_POST['title'] ?? ''),
            'description'        => sanitize($_POST['description'] ?? ''),
            'location'           => sanitize($_POST['location'] ?? ''),
            'severity'           => sanitize($_POST['severity'] ?? 'medium'),
            'affected_customers' => (int)($_POST['affected_customers'] ?? 0),
            'status'             => 'open',
            'reported_by'        => $_SESSION['user_id'],
        ]);
        redirect(base_url('gpon/incidents'));
    }

    public function deleteOlt(string $id): void {
        $this->db->delete('olts', 'id=?', [$id]);
        $_SESSION['success'] = 'OLT deleted.';
        redirect(base_url('gpon/olts'));
    }

    public function updateSplitter(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('gpon/splitters'));
        $this->db->update('splitters', [
            'name'      => sanitize($_POST['name'] ?? ''),
            'olt_id'    => (int)$_POST['olt_id'] ?: null,
            'ratio'     => sanitize($_POST['ratio'] ?? '1:8'),
            'olt_port'  => (int)$_POST['olt_port'] ?: null,
            'location'  => sanitize($_POST['location'] ?? ''),
            'is_active' => (int)($_POST['is_active'] ?? 1),
        ], 'id=?', [$id]);
        redirect(base_url('gpon/splitters'));
    }

    public function deleteSplitter(string $id): void {
        $this->db->delete('splitters', 'id=?', [$id]);
        redirect(base_url('gpon/splitters'));
    }

    public function updateOnu(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('gpon/onus'));
        $this->db->update('onus', [
            'serial_number'  => strtoupper(trim(sanitize($_POST['serial_number'] ?? ''))),
            'model'          => sanitize($_POST['model'] ?? ''),
            'brand'          => sanitize($_POST['brand'] ?? ''),
            'onu_type'       => sanitize($_POST['onu_type'] ?? 'indoor'),
            'mac_address'    => strtoupper(sanitize($_POST['mac_address'] ?? '')),
            'splitter_id'    => (int)($_POST['splitter_id'] ?? 0) ?: null,
            'customer_id'    => (int)($_POST['customer_id'] ?? 0) ?: null,
            'status'         => sanitize($_POST['status'] ?? 'stock'),
            'signal_level'   => $_POST['signal_level'] !== '' ? (float)$_POST['signal_level'] : null,
            'purchase_price' => (float)($_POST['purchase_price'] ?? 0) ?: null,
            'installed_date' => !empty($_POST['installed_date']) ? $_POST['installed_date'] : null,
            'warranty_expiry'=> !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
            'notes'          => sanitize($_POST['notes'] ?? ''),
        ], 'id=?', [$id]);
        $_SESSION['success'] = 'ONU updated successfully.';
        redirect(base_url('gpon/onus'));
    }

    public function deleteOnu(string $id): void {
        $this->db->delete('onus', 'id=?', [$id]);
        redirect(base_url('gpon/onus'));
    }

    public function updateIncident(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('gpon/incidents'));
        $data = [
            'status'           => sanitize($_POST['status'] ?? 'open'),
            'severity'         => sanitize($_POST['severity'] ?? 'medium'),
            'resolution_notes' => sanitize($_POST['resolution_notes'] ?? ''),
        ];
        if ($_POST['status'] === 'resolved') $data['resolved_at'] = date('Y-m-d H:i:s');
        $this->db->update('fiber_incidents', $data, 'id=?', [$id]);
        redirect(base_url('gpon/incidents'));
    }

    public function deleteIncident(string $id): void {
        $this->db->delete('fiber_incidents', 'id=?', [$id]);
        redirect(base_url('gpon/incidents'));
    }
}

class WorkOrderController {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void {
        $pageTitle = 'Work Orders'; $currentPage = 'workorders'; $currentSubPage = 'workorder-list';
        $status = sanitize($_GET['status'] ?? '');
        $where  = '1=1'; $params = [];
        if ($status) { $where .= ' AND wo.status=?'; $params[] = $status; }
        $workOrders = $this->db->fetchAll(
            "SELECT wo.*, c.full_name as customer_name, c.customer_code, t.name as technician
             FROM work_orders wo LEFT JOIN customers c ON c.id=wo.customer_id LEFT JOIN technicians t ON t.id=wo.technician_id
             WHERE $where ORDER BY CASE wo.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END, wo.created_at DESC LIMIT 100",
            $params
        );
        $pending     = count(array_filter($workOrders, fn($w) => $w['status']==='pending'));
        $inProgress  = count(array_filter($workOrders, fn($w) => $w['status']==='in_progress'));
        $completed   = count(array_filter($workOrders, fn($w) => $w['status']==='completed'));
        $technicians = $this->db->fetchAll("SELECT id,name FROM technicians WHERE is_active=1");
        $viewFile = BASE_PATH . '/views/workorders/list.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function create(): void {
        $pageTitle   = 'Create Work Order'; $currentPage = 'workorders'; $currentSubPage = 'workorder-create';
        $customers   = []; // Loaded via AJAX search now
        $technicians = $this->db->fetchAll("SELECT id,name FROM technicians WHERE is_active=1");
        $branches    = $this->db->fetchAll("SELECT id,name FROM branches WHERE is_active=1");
        $zones       = $this->db->fetchAll("SELECT id,name FROM zones WHERE is_active=1");
        $viewFile    = BASE_PATH . '/views/workorders/create.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function store(): void {
        $num = 'WO-' . date('Ymd') . '-' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'normal');

        // AI Analysis for priority
        if (env('AI_ENABLED')) {
            $aiService = new AiService();
            $analysis = $aiService->analyzeTicket($title, $description);
            if ($analysis && !empty($analysis['priority'])) {
                $priority = $analysis['priority'];
            }
        }

        $this->db->insert('work_orders', [
            'wo_number'    => $num,
            'customer_id'  => (int)$_POST['customer_id'] ?: null,
            'branch_id'    => (int)$_POST['branch_id'],
            'zone_id'      => (int)$_POST['zone_id'] ?: null,
            'technician_id'=> (int)$_POST['technician_id'] ?: null,
            'type'         => sanitize($_POST['type'] ?? 'other'),
            'priority'     => $priority,
            'title'        => $title,
            'description'  => $description,
            'address'      => sanitize($_POST['address'] ?? ''),
            'scheduled_date'=> !empty($_POST['scheduled_date']) ? sanitize($_POST['scheduled_date']) : null,
            'status'       => 'pending',
            'created_by'   => $_SESSION['user_id'],
        ]);
        redirect(base_url('workorders'));
    }

    public function view(string $id): void {
        $pageTitle = 'Work Order Detail'; $currentPage = 'workorders';
        $wo = $this->db->fetchOne(
            "SELECT wo.*, c.full_name as customer_name, c.phone as customer_phone, c.address as customer_address,
                    t.name as technician_name, t.phone as technician_phone, b.name as branch_name
             FROM work_orders wo LEFT JOIN customers c ON c.id=wo.customer_id
             LEFT JOIN technicians t ON t.id=wo.technician_id LEFT JOIN branches b ON b.id=wo.branch_id
             WHERE wo.id=?", [$id]
        );
        if (!$wo) { http_response_code(404); die('Work order not found'); }

        // AI Assistant for Technician
        $aiService = new AiService();
        $aiSuggestion = null;
        if (env('AI_ENABLED')) {
            $messages = [
                ['role' => 'system', 'content' => "You are an expert ISP field technician assistant. 
                Analyze the work order and provide a concise list of possible causes and recommended tools/steps for the technician.
                Focus on practical ISP infrastructure like ONU, Splitter, Fiber, or MikroTik configuration."],
                ['role' => 'user', 'content' => "Work Order Title: {$wo['title']}\nType: {$wo['type']}\nDescription: {$wo['description']}"]
            ];
            $aiSuggestion = $aiService->getChatCompletion($messages);
        }

        $technicians = $this->db->fetchAll("SELECT id,name FROM technicians WHERE is_active=1");
        $viewFile = BASE_PATH . '/views/workorders/view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updateStatus(string $id): void {
        $status = sanitize($_POST['status'] ?? '');
        $notes  = sanitize($_POST['completion_notes'] ?? '');
        $update = ['status' => $status, 'completion_notes' => $notes];
        if ($status === 'completed') $update['completed_at'] = date('Y-m-d H:i:s');
        if (!empty($_POST['technician_id'])) $update['technician_id'] = (int)$_POST['technician_id'];
        $this->db->update('work_orders', $update, 'id=?', [$id]);
        redirect(base_url("workorders/view/{$id}"));
    }

    public function delete(string $id): void {
        $this->db->delete('work_orders', 'id=?', [$id]);
        $_SESSION['success'] = 'Work order deleted.';
        redirect(base_url('workorders'));
    }
}

class ResellerController {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void {
        $pageTitle = 'Resellers'; $currentPage = 'resellers'; $currentSubPage = 'reseller-list';
        $resellers = $this->db->fetchAll(
            "SELECT r.*, b.name as branch_name,
                    (SELECT COUNT(*) FROM customers c WHERE c.reseller_id=r.id) as customer_count
             FROM resellers r LEFT JOIN branches b ON b.id=r.branch_id ORDER BY r.created_at DESC"
        );
        $viewFile = BASE_PATH . '/views/reseller/list.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function create(): void {
        $pageTitle = 'Add Reseller'; $currentPage = 'resellers'; $currentSubPage = 'reseller-create';
        $branches  = $this->db->fetchAll("SELECT id,name FROM branches WHERE is_active=1");
        $zones     = $this->db->fetchAll("SELECT id,name FROM zones WHERE is_active=1");
        $parents   = $this->db->fetchAll("SELECT id,contact_person,business_name FROM resellers WHERE status='active'");
        $viewFile  = BASE_PATH . '/views/reseller/create.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function store(): void {
        $this->db->insert('resellers', [
            'branch_id'       => (int)$_POST['branch_id'],
            'zone_id'         => (int)$_POST['zone_id'] ?: null,
            'parent_reseller_id' => (int)$_POST['parent_reseller_id'] ?: null,
            'business_name'   => sanitize($_POST['business_name'] ?? ''),
            'contact_person'  => sanitize($_POST['contact_person'] ?? ''),
            'phone'           => sanitize($_POST['phone'] ?? ''),
            'email'           => sanitize($_POST['email'] ?? ''),
            'address'         => sanitize($_POST['address'] ?? ''),
            'commission_rate' => (float)($_POST['commission_rate'] ?? 0),
            'credit_limit'    => (float)($_POST['credit_limit'] ?? 0),
            'status'          => 'active',
            'joined_date'     => date('Y-m-d'),
        ]);
        redirect(base_url('resellers'));
    }

    public function view(string $id): void {
        $pageTitle = 'Reseller Detail'; $currentPage = 'resellers';
        $reseller = $this->db->fetchOne("SELECT r.*, b.name as branch_name FROM resellers r LEFT JOIN branches b ON b.id=r.branch_id WHERE r.id=?", [$id]);
        if (!$reseller) { http_response_code(404); die('Not found'); }
        $transactions = $this->db->fetchAll("SELECT * FROM reseller_transactions WHERE reseller_id=? ORDER BY transaction_date DESC LIMIT 20", [$id]);
        $customers    = $this->db->fetchAll("SELECT id,customer_code,full_name,status FROM customers WHERE reseller_id=? LIMIT 20", [$id]);
        $viewFile = BASE_PATH . '/views/reseller/view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function topup(string $id): void {
        $reseller = $this->db->fetchOne("SELECT * FROM resellers WHERE id=?", [$id]);
        $amount   = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) { redirect(base_url("resellers/view/{$id}")); }
        $newBal = $reseller['balance'] + $amount;
        $this->db->update('resellers', ['balance' => $newBal], 'id=?', [$id]);
        $this->db->insert('reseller_transactions', [
            'reseller_id'      => $id,
            'transaction_type' => 'topup',
            'amount'           => $amount,
            'balance_before'   => $reseller['balance'],
            'balance_after'    => $newBal,
            'notes'            => sanitize($_POST['notes'] ?? ''),
            'performed_by'     => $_SESSION['user_id'],
        ]);
        redirect(base_url("resellers/view/{$id}"));
    }

    public function edit(string $id): void {
        $pageTitle = 'Edit Reseller'; $currentPage = 'resellers';
        $reseller  = $this->db->fetchOne("SELECT * FROM resellers WHERE id=?", [$id]);
        if (!$reseller) { http_response_code(404); die('Not found'); }
        $branches  = $this->db->fetchAll("SELECT id,name FROM branches WHERE is_active=1");
        $zones     = $this->db->fetchAll("SELECT id,name FROM zones WHERE is_active=1");
        $viewFile  = BASE_PATH . '/views/reseller/create.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function update(string $id): void {
        $this->db->update('resellers', [
            'branch_id'       => (int)$_POST['branch_id'],
            'zone_id'         => (int)$_POST['zone_id'] ?: null,
            'business_name'   => sanitize($_POST['business_name'] ?? ''),
            'contact_person'  => sanitize($_POST['contact_person'] ?? ''),
            'phone'           => sanitize($_POST['phone'] ?? ''),
            'email'           => sanitize($_POST['email'] ?? ''),
            'address'         => sanitize($_POST['address'] ?? ''),
            'commission_rate' => (float)($_POST['commission_rate'] ?? 0),
            'credit_limit'    => (float)($_POST['credit_limit'] ?? 0),
            'status'          => sanitize($_POST['status'] ?? 'active'),
        ], 'id=?', [$id]);
        redirect(base_url("resellers/view/{$id}"));
    }

    public function delete(string $id): void {
        $this->db->update('resellers', ['status' => 'inactive'], 'id=?', [$id]);
        redirect(base_url('resellers'));
    }
}

class InventoryController {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void { redirect(base_url('inventory/stock')); }

    public function stock(): void {
        $pageTitle = 'Inventory Stock'; $currentPage = 'inventory'; $currentSubPage = 'stock';
        $items = $this->db->fetchAll(
            "SELECT i.*, c.name as category_name, w.name as warehouse_name
             FROM inventory_items i LEFT JOIN item_categories c ON c.id=i.category_id LEFT JOIN warehouses w ON w.id=i.warehouse_id
             WHERE i.is_active=1 ORDER BY i.name"
        );
        $categories = $this->db->fetchAll("SELECT id,name FROM item_categories");
        $warehouses = $this->db->fetchAll("SELECT id,name FROM warehouses WHERE is_active=1");
        $viewFile   = BASE_PATH . '/views/inventory/stock.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function stockIn(): void {
        $itemId = (int)$_POST['item_id'];
        $qty    = (int)$_POST['quantity'];
        $price  = (float)($_POST['unit_price'] ?? 0);
        $item   = $this->db->fetchOne("SELECT quantity FROM inventory_items WHERE id=?", [$itemId]);
        if (!$item) { redirect(base_url('inventory/stock')); }
        $this->db->update('inventory_items', ['quantity' => $item['quantity'] + $qty], 'id=?', [$itemId]);
        $this->db->insert('stock_movements', [
            'item_id'        => $itemId,
            'movement_type'  => 'purchase',
            'quantity'       => $qty,
            'unit_price'     => $price,
            'total_amount'   => $qty * $price,
            'notes'          => sanitize($_POST['notes'] ?? ''),
            'performed_by'   => $_SESSION['user_id'],
        ]);
        redirect(base_url('inventory/stock'));
    }

    public function purchases(): void {
        $pageTitle = 'Purchase Orders'; $currentPage = 'inventory'; $currentSubPage = 'purchases';
        $pos = $this->db->fetchAll(
            "SELECT po.*, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON s.id=po.supplier_id ORDER BY po.created_at DESC LIMIT 50"
        );
        $suppliers = $this->db->fetchAll("SELECT id,name FROM suppliers WHERE is_active=1");
        $viewFile  = BASE_PATH . '/views/inventory/purchases.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storePurchase(): void {
        $poNum = 'PO-' . date('Ymd') . '-' . rand(100,999);
        $this->db->insert('purchase_orders', [
            'po_number'    => $poNum,
            'supplier_id'  => (int)$_POST['supplier_id'],
            'branch_id'    => (int)($_POST['branch_id'] ?? $_SESSION['branch_id']),
            'total_amount' => (float)($_POST['total_amount'] ?? 0),
            'status'       => 'ordered',
            'order_date'   => date('Y-m-d'),
            'notes'        => sanitize($_POST['notes'] ?? ''),
            'created_by'   => $_SESSION['user_id'],
        ]);
        redirect(base_url('inventory/purchases'));
    }

    public function storeItem(): void {
        $this->db->insert('inventory_items', [
            'name'          => sanitize($_POST['name'] ?? ''),
            'code'          => sanitize($_POST['code'] ?? '') ?: null,
            'category_id'   => (int)$_POST['category_id'] ?: null,
            'warehouse_id'  => (int)$_POST['warehouse_id'] ?: null,
            'unit'          => sanitize($_POST['unit'] ?? 'pcs'),
            'quantity'      => (int)($_POST['quantity'] ?? 0),
            'minimum_stock' => (int)($_POST['minimum_stock'] ?? 5),
            'purchase_price'=> (float)($_POST['purchase_price'] ?? 0),
            'sale_price'    => (float)($_POST['sale_price'] ?? 0),
            'description'   => sanitize($_POST['description'] ?? ''),
            'is_active'     => 1,
        ]);
        redirect(base_url('inventory/stock'));
    }

    public function updateItem(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('inventory/stock'));
        $this->db->update('inventory_items', [
            'name'          => sanitize($_POST['name'] ?? ''),
            'code'          => sanitize($_POST['code'] ?? '') ?: null,
            'category_id'   => (int)$_POST['category_id'] ?: null,
            'warehouse_id'  => (int)$_POST['warehouse_id'] ?: null,
            'unit'          => sanitize($_POST['unit'] ?? 'pcs'),
            'minimum_stock' => (int)($_POST['minimum_stock'] ?? 5),
            'purchase_price'=> (float)($_POST['purchase_price'] ?? 0),
            'sale_price'    => (float)($_POST['sale_price'] ?? 0),
            'description'   => sanitize($_POST['description'] ?? ''),
        ], 'id=?', [$id]);
        redirect(base_url('inventory/stock'));
    }

    public function deleteItem(string $id): void {
        $this->db->update('inventory_items', ['is_active' => 0], 'id=?', [$id]);
        redirect(base_url('inventory/stock'));
    }

    public function stockOut(): void {
        $itemId = (int)$_POST['item_id'];
        $qty    = (int)$_POST['quantity'];
        $item   = $this->db->fetchOne("SELECT quantity FROM inventory_items WHERE id=?", [$itemId]);
        if (!$item || $item['quantity'] < $qty) {
            $_SESSION['error'] = 'Insufficient stock.';
            redirect(base_url('inventory/stock'));
        }
        $this->db->update('inventory_items', ['quantity' => $item['quantity'] - $qty], 'id=?', [$itemId]);
        $this->db->insert('stock_movements', [
            'item_id'       => $itemId,
            'movement_type' => 'installation',
            'quantity'      => -$qty,
            'notes'         => sanitize($_POST['notes'] ?? ''),
            'performed_by'  => $_SESSION['user_id'],
        ]);
        redirect(base_url('inventory/stock'));
    }

    public function receivePO(string $id): void {
        $po = $this->db->fetchOne("SELECT * FROM purchase_orders WHERE id=?", [$id]);
        if (!$po) redirect(base_url('inventory/purchases'));
        $this->db->update('purchase_orders', [
            'status'        => 'received',
            'received_date' => date('Y-m-d'),
            'paid_amount'   => $po['total_amount'],
        ], 'id=?', [$id]);
        redirect(base_url('inventory/purchases'));
    }

    public function deletePO(string $id): void {
        $this->db->update('purchase_orders', ['status' => 'cancelled'], 'id=?', [$id]);
        redirect(base_url('inventory/purchases'));
    }
}

class ReportController {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void { redirect(base_url('reports/income')); }

    public function income(): void {
        $pageTitle    = 'Income Report'; $currentPage = 'reports'; $currentSubPage = 'income';
        $from = sanitize($_GET['from'] ?? date('Y-m-01'));
        $to   = sanitize($_GET['to'] ?? date('Y-m-t'));
        $branchId = $_SESSION['branch_id'] ?? null;
        $bf = $branchId ? 'AND p.branch_id=?' : ''; $bp = $branchId ? [$from, $to, $branchId] : [$from, $to];

        $daily = $this->db->fetchAll(
            "SELECT DATE(payment_date) as day, SUM(amount) as total, COUNT(*) as count
             FROM payments p WHERE DATE(payment_date) BETWEEN ? AND ? $bf GROUP BY day ORDER BY day",
            $bp
        );
        $totalIncome = array_sum(array_column($daily, 'total'));
        $viewFile    = BASE_PATH . '/views/reports/income.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function due(): void {
        $pageTitle = 'Due Report'; $currentPage = 'reports'; $currentSubPage = 'due';
        $dueCustomers = $this->db->fetchAll(
            "SELECT c.customer_code, c.full_name, c.phone, c.due_amount, c.status, p.name as package_name, z.name as zone_name
             FROM customers c LEFT JOIN packages p ON p.id=c.package_id LEFT JOIN zones z ON z.id=c.zone_id
             WHERE c.due_amount > 0 ORDER BY c.due_amount DESC LIMIT 200"
        );
        $totalDue = array_sum(array_column($dueCustomers, 'due_amount'));
        $viewFile = BASE_PATH . '/views/reports/due.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function collection(): void {
        $pageTitle = 'Collection Report'; $currentPage = 'reports'; $currentSubPage = 'collection';
        $date = sanitize($_GET['date'] ?? date('Y-m-d'));
        $collections = $this->db->fetchAll(
            "SELECT u.full_name as collector, SUM(p.amount) as total, COUNT(*) as count
             FROM payments p LEFT JOIN users u ON u.id=p.collector_id
             WHERE DATE(p.payment_date)=? GROUP BY p.collector_id ORDER BY total DESC",
            [$date]
        );
        $viewFile = BASE_PATH . '/views/reports/collection.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function customers(): void {
        $pageTitle = 'Customer Growth'; $currentPage = 'reports'; $currentSubPage = 'customers-report';
        $growth = $this->db->fetchAll(
            "SELECT strftime('%Y-%m', created_at) as month, COUNT(*) as new_customers
             FROM customers WHERE created_at >= date('now', '-12 months')
             GROUP BY month ORDER BY month"
        );
        $viewFile = BASE_PATH . '/views/reports/customers.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}

class FinanceController {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void { redirect(base_url('finance/cashbook')); }

    public function cashbook(): void {
        $pageTitle = 'Cashbook'; $currentPage = 'finance'; $currentSubPage = 'cashbook';
        $date      = sanitize($_GET['date'] ?? date('Y-m-d'));
        $branchId  = $_SESSION['branch_id'] ?? 1;
        $entries   = $this->db->fetchAll("SELECT * FROM cashbook_entries WHERE branch_id=? AND entry_date=? ORDER BY created_at", [$branchId, $date]);
        $credit    = array_sum(array_column(array_filter($entries, fn($e)=>$e['entry_type']==='credit'), 'amount'));
        $debit     = array_sum(array_column(array_filter($entries, fn($e)=>$e['entry_type']==='debit'), 'amount'));
        $viewFile  = BASE_PATH . '/views/finance/cashbook.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function expenses(): void {
        $pageTitle = 'Expenses'; $currentPage = 'finance'; $currentSubPage = 'expenses';
        $month     = sanitize($_GET['month'] ?? date('Y-m'));
        $expenses  = $this->db->fetchAll(
            "SELECT e.*, ec.name as category_name FROM expenses e LEFT JOIN expense_categories ec ON ec.id=e.category_id
             WHERE strftime('%Y-%m', e.expense_date)=? ORDER BY e.expense_date DESC",
            [$month]
        );
        $categories = $this->db->fetchAll("SELECT id,name FROM expense_categories");
        $total      = array_sum(array_column($expenses, 'amount'));
        $viewFile   = BASE_PATH . '/views/finance/expenses.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeExpense(): void {
        $this->db->insert('expenses', [
            'branch_id'      => (int)($_POST['branch_id'] ?? $_SESSION['branch_id'] ?? 1),
            'category_id'    => (int)$_POST['category_id'] ?: null,
            'title'          => sanitize($_POST['title'] ?? ''),
            'amount'         => (float)($_POST['amount'] ?? 0),
            'vendor'         => sanitize($_POST['vendor'] ?? ''),
            'payment_method' => sanitize($_POST['payment_method'] ?? 'cash'),
            'expense_date'   => sanitize($_POST['expense_date'] ?? date('Y-m-d')),
            'notes'          => sanitize($_POST['notes'] ?? ''),
            'created_by'     => $_SESSION['user_id'],
        ]);
        // Add to cashbook as debit
        $this->db->insert('cashbook_entries', [
            'branch_id'      => (int)($_POST['branch_id'] ?? $_SESSION['branch_id'] ?? 1),
            'entry_type'     => 'debit',
            'entry_category' => 'expense',
            'amount'         => (float)($_POST['amount'] ?? 0),
            'description'    => sanitize($_POST['title'] ?? ''),
            'entry_date'     => sanitize($_POST['expense_date'] ?? date('Y-m-d')),
            'created_by'     => $_SESSION['user_id'],
        ]);
        redirect(base_url('finance/expenses'));
    }

    public function deleteExpense(string $id): void {
        $this->db->delete('expenses', 'id=?', [$id]);
        redirect(base_url('finance/expenses'));
    }

    public function dailyClose(): void {
        $branchId = (int)($_POST['branch_id'] ?? $_SESSION['branch_id'] ?? 1);
        $date     = sanitize($_POST['date'] ?? date('Y-m-d'));
        $credit   = (float)$this->db->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM cashbook_entries WHERE branch_id=? AND entry_date=? AND entry_type='credit'", [$branchId, $date])['s'];
        $debit    = (float)$this->db->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM cashbook_entries WHERE branch_id=? AND entry_date=? AND entry_type='debit'", [$branchId, $date])['s'];

        $existing = $this->db->fetchOne("SELECT id FROM daily_closings WHERE branch_id=? AND closing_date=?", [$branchId, $date]);
        if ($existing) {
            $this->db->update('daily_closings', ['total_collection'=>$credit, 'total_expense'=>$debit, 'closing_balance'=>$credit-$debit, 'closed_by'=>$_SESSION['user_id'], 'closed_at'=>date('Y-m-d H:i:s')], 'id=?', [$existing['id']]);
        } else {
            $this->db->insert('daily_closings', ['branch_id'=>$branchId,'closing_date'=>$date,'opening_balance'=>0,'total_collection'=>$credit,'total_expense'=>$debit,'closing_balance'=>$credit-$debit,'closed_by'=>$_SESSION['user_id']]);
        }
        redirect(base_url('finance/cashbook'));
    }
}

class SettingsController {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void {
        $pageTitle = 'Settings'; $currentPage = 'settings';
        $branches  = $this->db->fetchAll("SELECT * FROM branches ORDER BY name");
        $zones     = $this->db->fetchAll("SELECT z.*, b.name as branch_name FROM zones z JOIN branches b ON b.id=z.branch_id ORDER BY z.name");
        $gateways  = $this->db->fetchAll("SELECT * FROM sms_gateways ORDER BY is_active DESC");
        $roles     = $this->db->fetchAll("SELECT * FROM roles");
        $users     = $this->db->fetchAll("SELECT u.*, r.display_name as role_name, b.name as branch_name FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN branches b ON b.id=u.branch_id ORDER BY u.created_at DESC");
        $packages  = $this->db->fetchAll("SELECT p.*, pc.name as category_name FROM packages p LEFT JOIN package_categories pc ON pc.id=p.category_id ORDER BY p.price");
        // Flatten company settings from a key-value table if exists
        $settingsRaw = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
        $settings = []; foreach ($settingsRaw as $s) { $settings[$s['key']] = $s['value']; }
        $viewFile  = BASE_PATH . '/views/settings/index.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function saveGeneral(): void {
        $fields = ['company_name','company_phone','company_email','website','company_address','currency','vat_percent'];
        foreach ($fields as $f) {
            $val = sanitize($_POST[$f] ?? '');
            $exists = $this->db->fetchOne("SELECT id FROM settings WHERE `key`=?", [$f]);
            if ($exists) { $this->db->update('settings', ['value'=>$val], '`key`=?', [$f]); }
            else { $this->db->insert('settings', ['key'=>$f,'value'=>$val]); }
        }
        redirect(base_url('settings'));
    }

    public function saveReseller(): void {
        $fields = [
            'reseller_panel_enabled', 'reseller_portal_name', 'reseller_support_phone', 
            'reseller_support_email', 'reseller_login_text', 'reseller_theme_color'
        ];
        foreach ($fields as $f) {
            $val = sanitize($_POST[$f] ?? '');
            $exists = $this->db->fetchOne("SELECT id FROM settings WHERE `key`=?", [$f]);
            if ($exists) { $this->db->update('settings', ['value'=>$val], '`key`=?', [$f]); }
            else { $this->db->insert('settings', ['key'=>$f,'value'=>$val]); }
        }
        $_SESSION['success'] = "Reseller panel settings updated and portals synchronized.";
        redirect(base_url('settings#reseller'));
    }

    public function storePackage(): void {
        $name = sanitize($_POST['name'] ?? '');
        $code = sanitize($_POST['code'] ?? '');
        if (empty($code)) {
            $code = strtolower(str_replace(' ', '-', $name)) . '-' . rand(100, 999);
        }

        $this->db->insert('packages', [
            'name'           => $name,
            'code'           => $code,
            'speed_download' => sanitize($_POST['speed_download'] ?? '0'),
            'speed_upload'   => sanitize($_POST['speed_upload'] ?? '0'),
            'price'          => (float)($_POST['price'] ?? 0),
            'type'           => sanitize($_POST['package_type'] ?? 'pppoe'),
            'billing_type'   => 'monthly',
            'data_limit'     => 'Unlimited',
            'radius_profile' => sanitize($_POST['radius_profile'] ?? ''),
            'is_active'      => 1,
        ]);
        redirect(base_url('settings#packages'));
    }

    public function updatePackage(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('settings#packages'));

        $this->db->update('packages', [
            'name'           => sanitize($_POST['name'] ?? ''),
            'speed_download' => sanitize($_POST['speed_download'] ?? '0'),
            'speed_upload'   => sanitize($_POST['speed_upload'] ?? '0'),
            'price'          => (float)($_POST['price'] ?? 0),
            'type'           => sanitize($_POST['package_type'] ?? 'pppoe'),
            'radius_profile' => sanitize($_POST['radius_profile'] ?? ''),
            'is_active'      => (int)($_POST['is_active'] ?? 1),
        ], 'id=?', [$id]);

        $_SESSION['success'] = "Package updated successfully.";
        redirect(base_url('settings#packages'));
    }

    public function deletePackage(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->db->query("DELETE FROM packages WHERE id = ?", [$id]);
            $_SESSION['success'] = "Package deleted successfully.";
        }
        redirect(base_url('settings#packages'));
    }

    public function storeBranch(): void {
        $data = [
            'name'         => sanitize($_POST['name'] ?? ''),
            'code'         => sanitize($_POST['code'] ?? ''),
            'address'      => sanitize($_POST['address'] ?? ''),
            'phone'        => sanitize($_POST['phone'] ?? ''),
            'email'        => sanitize($_POST['email'] ?? ''),
            'manager_name' => sanitize($_POST['manager_name'] ?? ''),
            'is_active'    => (int)($_POST['is_active'] ?? 1)
        ];
        $this->db->insert('branches', $data);
        $_SESSION['success'] = "Branch created successfully.";
        redirect(base_url('settings#branches'));
    }

    public function updateBranch(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('settings#branches'));
        $data = [
            'name'         => sanitize($_POST['name'] ?? ''),
            'code'         => sanitize($_POST['code'] ?? ''),
            'address'      => sanitize($_POST['address'] ?? ''),
            'phone'        => sanitize($_POST['phone'] ?? ''),
            'email'        => sanitize($_POST['email'] ?? ''),
            'manager_name' => sanitize($_POST['manager_name'] ?? ''),
            'is_active'    => (int)($_POST['is_active'] ?? 1)
        ];
        $this->db->update('branches', $data, 'id=?', [$id]);
        $_SESSION['success'] = "Branch updated successfully.";
        redirect(base_url('settings#branches'));
    }

    public function deleteBranch(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->db->query("DELETE FROM branches WHERE id = ?", [$id]);
            $_SESSION['success'] = "Branch deleted successfully.";
        }
        redirect(base_url('settings#branches'));
    }

    public function storeZone(): void {
        $data = [
            'branch_id'   => (int)($_POST['branch_id'] ?? 0),
            'name'        => sanitize($_POST['name'] ?? ''),
            'code'        => sanitize($_POST['code'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'is_active'   => (int)($_POST['is_active'] ?? 1)
        ];
        $this->db->insert('zones', $data);
        $_SESSION['success'] = "Zone created successfully.";
        redirect(base_url('settings#branches'));
    }

    public function updateZone(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('settings#branches'));
        $data = [
            'branch_id'   => (int)($_POST['branch_id'] ?? 0),
            'name'        => sanitize($_POST['name'] ?? ''),
            'code'        => sanitize($_POST['code'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'is_active'   => (int)($_POST['is_active'] ?? 1)
        ];
        $this->db->update('zones', $data, 'id=?', [$id]);
        $_SESSION['success'] = "Zone updated successfully.";
        redirect(base_url('settings#branches'));
    }

    public function deleteZone(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->db->query("DELETE FROM zones WHERE id = ?", [$id]);
            $_SESSION['success'] = "Zone deleted successfully.";
        }
        redirect(base_url('settings#branches'));
    }

    public function storeUser(): void {
        $username = sanitize($_POST['username'] ?? '');
        
        $exists = $this->db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exists) {
            $_SESSION['error'] = "Username '$username' already exists.";
            redirect(base_url('settings#users'));
        }
        
        $data = [
            'full_name'     => sanitize($_POST['full_name'] ?? ''),
            'username'      => $username,
            'email'         => sanitize($_POST['email'] ?? ''),
            'phone'         => sanitize($_POST['phone'] ?? ''),
            'role_id'       => (int)($_POST['role_id'] ?? 0),
            'branch_id'     => (int)($_POST['branch_id'] ?? 0),
            'is_active'     => (int)($_POST['is_active'] ?? 1),
            'password_hash' => password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT)
        ];
        $this->db->insert('users', $data);
        $_SESSION['success'] = "Staff user created successfully.";
        redirect(base_url('settings#users'));
    }

    public function updateUser(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('settings#users'));

        $username = sanitize($_POST['username'] ?? '');
        
        $exists = $this->db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
        if ($exists) {
            $_SESSION['error'] = "Username '$username' already exists.";
            redirect(base_url('settings#users'));
        }

        $data = [
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'username'  => $username,
            'email'     => sanitize($_POST['email'] ?? ''),
            'phone'     => sanitize($_POST['phone'] ?? ''),
            'role_id'   => (int)($_POST['role_id'] ?? 0),
            'branch_id' => (int)($_POST['branch_id'] ?? 0),
            'is_active' => (int)($_POST['is_active'] ?? 1)
        ];

        if (!empty($_POST['password'])) {
            $data['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $this->db->update('users', $data, 'id=?', [$id]);
        $_SESSION['success'] = "Staff user updated successfully.";
        redirect(base_url('settings#users'));
    }

    public function deleteUser(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id != $_SESSION['user_id']) {
            $this->db->query("DELETE FROM users WHERE id = ?", [$id]);
            $_SESSION['success'] = "User deleted successfully.";
        } else {
            $_SESSION['error'] = "Cannot delete currently logged in user.";
        }
        redirect(base_url('settings#users'));
    }

    public function savePaymentSettings(): void {
        $fields = [
            'bkash_enabled', 'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password', 'bkash_mode',
            'nagad_enabled', 'nagad_merchant_id', 'nagad_public_key', 'nagad_private_key', 'nagad_mode',
            'rocket_enabled', 'rocket_merchant_id', 'rocket_app_key', 'rocket_mode',
            'piprapay_enabled', 'piprapay_merchant_id', 'piprapay_api_key', 'piprapay_api_secret', 'piprapay_mode',
            'selfhosted_piprapay_enabled', 'selfhosted_piprapay_webhook_secret', 'selfhosted_piprapay_auto_billing_enabled',
            'selfhosted_piprapay_retry_attempts', 'selfhosted_piprapay_retry_interval_hours',
        ];

        $checkboxFields = [
            'bkash_enabled',
            'nagad_enabled',
            'rocket_enabled',
            'piprapay_enabled',
            'selfhosted_piprapay_enabled',
            'selfhosted_piprapay_auto_billing_enabled',
        ];

        $existing = [];
        $existingRows = $this->db->fetchAll("SELECT `key`, `value` FROM settings WHERE `key` LIKE 'piprapay_%' OR `key` LIKE 'selfhosted_piprapay_%'");
        foreach ($existingRows as $row) {
            $existing[$row['key']] = (string)($row['value'] ?? '');
        }

        $values = [];
        foreach ($fields as $f) {
            if (in_array($f, $checkboxFields, true)) {
                $values[$f] = isset($_POST[$f]) ? '1' : '0';
                continue;
            }

            $values[$f] = sanitize($_POST[$f] ?? '');
        }

        // Preserve secrets when form submits blank values.
        if ($values['piprapay_api_secret'] === '' && !empty($existing['piprapay_api_secret'])) {
            $values['piprapay_api_secret'] = $existing['piprapay_api_secret'];
        }
        if ($values['selfhosted_piprapay_webhook_secret'] === '' && !empty($existing['selfhosted_piprapay_webhook_secret'])) {
            $values['selfhosted_piprapay_webhook_secret'] = $existing['selfhosted_piprapay_webhook_secret'];
        }

        // PipraPay strict validation for production safety.
        if ($values['piprapay_enabled'] === '1') {
            if ($values['piprapay_merchant_id'] === '' || $values['piprapay_api_key'] === '' || $values['piprapay_api_secret'] === '') {
                $_SESSION['error'] = 'PipraPay requires Merchant ID, API Key, and API Secret.';
                redirect(base_url('settings#payment'));
            }

            if (!in_array($values['piprapay_mode'], ['live', 'sandbox'], true)) {
                $values['piprapay_mode'] = 'sandbox';
            }
        }

        // Self-hosted webhook secret should be strong.
        if ($values['selfhosted_piprapay_enabled'] === '1' && strlen($values['selfhosted_piprapay_webhook_secret']) < 16) {
            $_SESSION['error'] = 'Self-Hosted PipraPay webhook secret must be at least 16 characters.';
            redirect(base_url('settings#payment'));
        }

        foreach ($fields as $f) {
            $exists = $this->db->fetchOne("SELECT id FROM settings WHERE `key`=?", [$f]);
            if ($exists) {
                $this->db->update('settings', ['value' => $values[$f]], '`key`=?', [$f]);
            } else {
                $this->db->insert('settings', ['key' => $f, 'value' => $values[$f]]);
            }
        }
        $_SESSION['success'] = "Payment gateway settings saved successfully.";
        redirect(base_url('settings#payment'));
    }

    public function apiGetMikrotikProfiles(): void {
        header('Content-Type: application/json');
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1");
        $allProfiles = [];

        foreach ($nasDevices as $nas) {
            $mikrotik = new MikroTikService([
                'ip'       => $nas['ip_address'],
                'port'     => $nas['api_port'],
                'username' => $nas['username'],
                'password' => $nas['password'],
                'timeout'  => 5,
            ]);

            if ($mikrotik->connect()) {
                $profiles = $mikrotik->getProfiles();
                foreach ($profiles as $p) {
                    $name = $p['name'] ?? '';
                    if ($name === 'default' || $name === 'default-encryption') continue;
                    
                    $limit = $p['rate-limit'] ?? ''; // Format: "10M/10M"
                    $rx = 0; $tx = 0;
                    if (!empty($limit)) {
                        $parts = explode('/', $limit);
                        $tx = (int)($parts[0] ?? 0); // MikroTik tx = Download for user
                        $rx = (int)($parts[1] ?? 0); // MikroTik rx = Upload for user
                    }

                    $allProfiles[] = [
                        'name' => $name,
                        'rate_limit' => $limit,
                        'download' => $tx,
                        'upload' => $rx,
                        'nas_name' => $nas['name']
                    ];
                }
            }
        }
        echo json_encode($allProfiles);
        exit;
    }

    public function profiles(): void {
        $pageTitle      = 'PPPoE Profiles';
        $currentPage    = 'configuration';
        $currentSubPage = 'profiles';
        $profiles       = $this->db->fetchAll("SELECT p.*, n.name as nas_name FROM pppoe_profiles p LEFT JOIN nas_devices n ON n.id=p.nas_id ORDER BY p.name");
        $nasDevices     = $this->db->fetchAll("SELECT id, name FROM nas_devices WHERE is_active=1 ORDER BY name");
        $successMsg     = $_SESSION['success'] ?? null; unset($_SESSION['success']);
        $errorMsg       = $_SESSION['error']   ?? null; unset($_SESSION['error']);
        $viewFile       = BASE_PATH . '/views/settings/profiles.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeProfile(): void {
        $this->db->insert('pppoe_profiles', [
            'nas_id'         => (int)($_POST['nas_id'] ?? 0) ?: null,
            'name'           => sanitize($_POST['name'] ?? ''),
            'speed_download' => (int)($_POST['speed_download'] ?? 0),
            'speed_upload'   => (int)($_POST['speed_upload'] ?? 0),
            'description'    => sanitize($_POST['description'] ?? ''),
            'is_active'      => 1,
        ]);
        $_SESSION['success'] = 'Profile added.';
        redirect(base_url('settings/profiles'));
    }

    public function updateProfile(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect(base_url('settings/profiles'));
        $this->db->update('pppoe_profiles', [
            'nas_id'         => (int)($_POST['nas_id'] ?? 0) ?: null,
            'name'           => sanitize($_POST['name'] ?? ''),
            'speed_download' => (int)($_POST['speed_download'] ?? 0),
            'speed_upload'   => (int)($_POST['speed_upload'] ?? 0),
            'description'    => sanitize($_POST['description'] ?? ''),
            'is_active'      => (int)($_POST['is_active'] ?? 1),
        ], 'id=?', [$id]);
        $_SESSION['success'] = 'Profile updated.';
        redirect(base_url('settings/profiles'));
    }

    public function deleteProfile(string $id): void {
        $this->db->delete('pppoe_profiles', 'id=?', [$id]);
        $_SESSION['success'] = 'Profile deleted.';
        redirect(base_url('settings/profiles'));
    }

    public function storeConfigItem(): void {
        $type = sanitize($_POST['type'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        if (empty($type) || empty($name)) {
            redirect(base_url("settings/{$type}"));
        }
        $this->db->insert('config_items', [
            'type'      => $type,
            'name'      => $name,
            'details'   => sanitize($_POST['details'] ?? ''),
            'is_active' => 1,
        ]);
        $_SESSION['success'] = ucfirst(str_replace('-', ' ', $type)) . ' item added.';
        redirect(base_url("settings/{$type}"));
    }

    public function updateConfigItem(): void {
        $id      = (int)($_POST['id'] ?? 0);
        $type    = sanitize($_POST['type'] ?? '');
        $name    = sanitize($_POST['name'] ?? '');
        if (!$id || empty($name)) {
            redirect(base_url("settings/{$type}"));
        }
        $this->db->update('config_items', [
            'name'    => $name,
            'details' => sanitize($_POST['details'] ?? ''),
        ], 'id=?', [$id]);
        $_SESSION['success'] = 'Item updated.';
        redirect(base_url("settings/{$type}"));
    }

    public function deleteConfigItem(string $id): void {
        $item = $this->db->fetchOne("SELECT type FROM config_items WHERE id=?", [$id]);
        $this->db->delete('config_items', 'id=?', [$id]);
        $type = $item['type'] ?? '';
        redirect(base_url("settings/{$type}"));
    }

    public function configPage(string $type): void {
        $allowed = [
            'zone'            => 'Zone',
            'sub-zone'        => 'Sub Zone',
            'box'             => 'Box',
            'connection-type' => 'Connection Type',
            'client-type'     => 'Client Type',
            'protocol-type'   => 'Protocol Type',
            'billing-status'  => 'Billing Status',
            'package'         => 'Package',
            'district'        => 'District',
            'upazila'         => 'Upazila',
        ];

        if (!array_key_exists($type, $allowed)) {
            http_response_code(404);
            die("Configuration page not found.");
        }

        $pageTitle      = $allowed[$type];
        $currentPage    = 'configuration';
        $currentSubPage = $type;

        if ($type === 'zone') {
            $items = $this->db->fetchAll(
                "SELECT id, name, description as details FROM zones ORDER BY name"
            );
        } elseif ($type === 'package') {
            $items = $this->db->fetchAll(
                "SELECT id, name, ('Speed: ' || speed_download || ' / ' || speed_upload || ', Price: ৳' || price) as details FROM packages ORDER BY price"
            );
        } else {
            $items = $this->db->fetchAll(
                "SELECT id, name, details FROM config_items WHERE type = ? ORDER BY name",
                [$type]
            );
        }

        // Flash messages
        $successMsg = $_SESSION['success'] ?? null; unset($_SESSION['success']);
        $errorMsg   = $_SESSION['error']   ?? null; unset($_SESSION['error']);

        $viewFile = BASE_PATH . '/views/settings/config-page.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}
