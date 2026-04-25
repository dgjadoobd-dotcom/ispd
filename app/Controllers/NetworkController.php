<?php

class NetworkController {
    private Database $db;
    private RadiusService $radius;

    public function __construct() {
        $this->db = Database::getInstance();
        require_once BASE_PATH . '/app/Services/RadiusService.php';
        $this->radius = new RadiusService();
    }

    public function index(): void {
        redirect(base_url('network/ip-pools'));
    }

    public function ipPools(): void {
        $pageTitle   = 'IP Pools';
        $currentPage = 'network';
        $currentSubPage = 'ip-pools';
        $pools = $this->db->fetchAll(
            "SELECT ip.*, b.name as branch_name,
                    (SELECT COUNT(*) FROM ip_assignments ia WHERE ia.pool_id=ip.id AND ia.is_assigned=1) as used_count
             FROM ip_pools ip LEFT JOIN branches b ON b.id=ip.branch_id ORDER BY ip.created_at DESC"
        );
        $branches = $this->db->fetchAll("SELECT id,name FROM branches WHERE is_active=1");
        $viewFile = BASE_PATH . '/views/network/ip-pools.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storePool(): void {
        $data = [
            'name'        => sanitize($_POST['name'] ?? ''),
            'branch_id'   => (int)$_POST['branch_id'],
            'network_cidr'=> sanitize($_POST['network_cidr'] ?? ''),
            'gateway'     => sanitize($_POST['gateway'] ?? ''),
            'dns1'        => sanitize($_POST['dns1'] ?? '8.8.8.8'),
            'dns2'        => sanitize($_POST['dns2'] ?? '8.8.4.4'),
            'ip_type'     => sanitize($_POST['ip_type'] ?? 'private'),
            'total_ips'   => (int)($_POST['total_ips'] ?? 254),
            'is_active'   => 1,
        ];
        $this->db->insert('ip_pools', $data);
        redirect(base_url('network/ip-pools'));
    }

    public function nas(): void {
        $pageTitle   = 'MikroTik / NAS';
        $currentPage = 'network';
        $currentSubPage = 'nas';
        $nasDevices = $this->db->fetchAll(
            "SELECT n.*, b.name as branch_name FROM nas_devices n LEFT JOIN branches b ON b.id=n.branch_id ORDER BY n.created_at DESC"
        );
        $branches = $this->db->fetchAll("SELECT id,name FROM branches WHERE is_active=1");
        $viewFile = BASE_PATH . '/views/network/nas.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeNas(): void {
        $data = [
            'name'             => sanitize($_POST['name'] ?? ''),
            'branch_id'        => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null,
            'ip_address'       => sanitize($_POST['ip_address'] ?? ''),
            'api_port'         => (int)($_POST['api_port'] ?? 8728),
            'username'         => sanitize($_POST['username'] ?? 'admin'),
            'password'         => sanitize($_POST['password'] ?? ''),
            'secret'           => sanitize($_POST['secret'] ?? ''),
            'mikrotik_version' => sanitize($_POST['mikrotik_version'] ?? 'v2'),
            'timeout'          => (int)($_POST['timeout'] ?? 10),
            'type'             => 'mikrotik',
            'is_active'        => 1,
        ];
        $this->db->insert('nas_devices', $data);
        $_SESSION['success'] = 'MikroTik server added successfully.';
        redirect(base_url('network/nas'));
    }

    public function updateNas(string $id): void {
        $data = [
            'name'             => sanitize($_POST['name'] ?? ''),
            'branch_id'        => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null,
            'ip_address'       => sanitize($_POST['ip_address'] ?? ''),
            'api_port'         => (int)($_POST['api_port'] ?? 8728),
            'username'         => sanitize($_POST['username'] ?? 'admin'),
            'secret'           => sanitize($_POST['secret'] ?? ''),
            'mikrotik_version' => sanitize($_POST['mikrotik_version'] ?? 'v2'),
            'timeout'          => (int)($_POST['timeout'] ?? 10),
            'type'             => 'mikrotik',
            'is_active'        => (int)($_POST['is_active'] ?? 1),
        ];
        // Only update password if provided
        if (!empty($_POST['password'])) {
            $data['password'] = sanitize($_POST['password']);
        }
        $this->db->update('nas_devices', $data, 'id=?', [$id]);
        $_SESSION['success'] = 'MikroTik server updated successfully.';
        redirect(base_url('network/nas'));
    }

    public function deleteNas(string $id): void {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->db->delete('nas_devices', 'id=?', [$id]);
            jsonResponse(['success' => true, 'message' => 'Server deleted.']);
        } else {
            $this->db->delete('nas_devices', 'id=?', [$id]);
            $_SESSION['success'] = 'NAS device deleted successfully.';
            redirect(base_url('network/nas'));
        }
    }

    public function toggleNas(string $id): void {
        $nas = $this->db->fetchOne("SELECT id, is_active FROM nas_devices WHERE id=?", [$id]);
        if (!$nas) { jsonResponse(['success' => false, 'message' => 'Server not found.'], 404); }
        $newStatus = $nas['is_active'] ? 0 : 1;
        $this->db->update('nas_devices', ['is_active' => $newStatus], 'id=?', [$id]);
        jsonResponse(['success' => true, 'is_active' => $newStatus]);
    }

    public function getNas(string $id): void {
        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id=?", [$id]);
        if (!$nas) { jsonResponse(['success' => false], 404); }
        jsonResponse(['success' => true, 'data' => $nas]);
    }

    public function testConnection(string $id): void {
        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id=?", [$id]);
        if (!$nas) { jsonResponse(['success' => false, 'message' => 'NAS not found'], 404); }

        $mikrotik = new MikroTikService([
            'ip'       => $nas['ip_address'],
            'port'     => $nas['api_port'],
            'username' => $nas['username'],
            'password' => $nas['password'],
            'timeout'  => 5,
        ]);

        $connected = $mikrotik->connect();
        $info      = $connected ? $mikrotik->getSystemInfo() : [];

        $this->db->update('nas_devices', [
            'connection_status' => $connected ? 1 : 0,
            'last_checked'      => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);

        jsonResponse([
            'success'   => $connected,
            'message'   => $connected ? 'Connected successfully' : 'Connection failed',
            'info'      => $info,
        ]);
    }

    public function refreshStatusAll(): void {
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1");
        $results = [];

        foreach ($nasDevices as $nas) {
            $mikrotik = new MikroTikService([
                'ip'       => $nas['ip_address'],
                'port'     => $nas['api_port'],
                'username' => $nas['username'],
                'password' => $nas['password'],
                'timeout'  => 3,
            ]);

            $connected = $mikrotik->connect();
            $this->db->update('nas_devices', [
                'connection_status' => $connected ? 1 : 0,
                'last_checked'      => date('Y-m-d H:i:s'),
            ], 'id = ?', [$nas['id']]);

            $results[] = ['id' => $nas['id'], 'success' => $connected];
        }

        jsonResponse(['success' => true, 'results' => $results]);
    }

    public function onlineClients(): void {
        $pageTitle      = 'Online Clients Monitoring';
        $currentPage    = 'monitoring';
        $currentSubPage = 'online-clients';

        // Filter options for dropdowns
        $nasDevices  = $this->db->fetchAll("SELECT id, name FROM nas_devices WHERE is_active=1 ORDER BY name");
        $zones       = $this->db->fetchAll("SELECT id, name FROM zones WHERE is_active=1 ORDER BY name");
        $areas       = $this->db->fetchAll("SELECT id, name FROM areas WHERE is_active=1 ORDER BY name");
        $packages    = $this->db->fetchAll("SELECT id, name, mikrotik_profile FROM packages WHERE is_active=1 ORDER BY name");

        // Stat counts from database (customers joined with online status)
        $totalUsers   = (int)($this->db->fetchOne("SELECT COUNT(*) as cnt FROM customers WHERE status='active'")['cnt'] ?? 0);
        // Online / Offline fetched via JS (live data)

        $viewFile = BASE_PATH . '/views/network/online-clients.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function onlineClientsData(): void {
        header('Content-Type: application/json');

        $serverFilter   = $_GET['server']          ?? '';
        $protocolFilter = strtolower($_GET['protocol'] ?? '');
        $statusFilter   = $_GET['status']           ?? '';
        $zoneFilter     = $_GET['zone_id']          ?? '';
        $areaFilter     = $_GET['area_id']          ?? '';
        $connFilter     = $_GET['connection_type']  ?? '';
        $profileFilter  = $_GET['profile']          ?? '';

        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active=1");
        $rows = [];

        foreach ($nasDevices as $nas) {
            if (!empty($serverFilter) && $nas['id'] != $serverFilter) continue;

            $mikrotik = new MikroTikService([
                'ip'       => $nas['ip_address'],
                'port'     => $nas['api_port'],
                'username' => $nas['username'],
                'password' => $nas['password'],
                'timeout'  => 8,
            ]);

            if (!$mikrotik->connect()) continue;

            $sessions   = $mikrotik->getActiveSessions();
            $ifaceStats = $mikrotik->getInterfaceStats();

            foreach ($sessions as $s) {
                // Protocol filter
                $service = strtolower($s['service'] ?? 'pppoe');
                if (!empty($protocolFilter) && $service !== $protocolFilter) continue;

                // Merge traffic data
                $ifaceName = "<pppoe-" . ($s['name'] ?? '') . ">";
                if (isset($ifaceStats[$ifaceName])) {
                    $s['dl_bytes'] = (int)$ifaceStats[$ifaceName]['tx'];
                    $s['ul_bytes'] = (int)$ifaceStats[$ifaceName]['rx'];
                } else {
                    $alt = "pppoe-" . ($s['name'] ?? '');
                    $s['dl_bytes'] = isset($ifaceStats[$alt]) ? (int)$ifaceStats[$alt]['tx'] : 0;
                    $s['ul_bytes'] = isset($ifaceStats[$alt]) ? (int)$ifaceStats[$alt]['rx'] : 0;
                }

                // Match customer from DB
                $customer = $this->db->fetchOne(
                    "SELECT c.id, c.customer_code, c.full_name, c.phone, c.connection_type,
                            c.status, c.pppoe_username, c.static_ip,
                            z.name AS zone_name, z.id AS zone_id,
                            a.name AS area_name, a.id AS area_id,
                            p.name AS package_name, p.mikrotik_profile
                     FROM customers c
                     LEFT JOIN zones z ON z.id = c.zone_id
                     LEFT JOIN areas a ON a.id = c.area_id
                     LEFT JOIN packages p ON p.id = c.package_id
                     WHERE c.pppoe_username = ?",
                    [$s['name'] ?? '']
                );

                // Apply DB filters
                if (!empty($zoneFilter) && ($customer['zone_id'] ?? '') != $zoneFilter) continue;
                if (!empty($areaFilter) && ($customer['area_id'] ?? '') != $areaFilter) continue;
                if (!empty($connFilter) && strtolower($customer['connection_type'] ?? '') !== strtolower($connFilter)) continue;

                $finalProfile = $s['profile'] ?? $customer['mikrotik_profile'] ?? '—';
                if (!empty($profileFilter) && strtolower($finalProfile) !== strtolower($profileFilter)) continue;

                $rows[] = [
                    'customer_code'   => $customer['customer_code']   ?? '—',
                    'customer_id'     => $customer['id']              ?? null,
                    'pppoe_username'  => $s['name']                   ?? '—',
                    'ip_address'      => $s['address']                ?? '—',
                    'full_name'       => $customer['full_name']        ?? $s['name'] ?? '—',
                    'phone'           => $customer['phone']            ?? '—',
                    'zone'            => $customer['zone_name']        ?? '—',
                    'area'            => $customer['area_name']        ?? '—',
                    'connection_type' => $customer['connection_type']  ?? $service,
                    'server'          => $nas['name'],
                    'profile'         => $s['profile']                 ?? $customer['mikrotik_profile'] ?? '—',
                    'service'         => $customer['package_name']     ?? strtoupper($service),
                    'uptime'          => $s['uptime']                  ?? '—',
                    'online_status'   => 'Connected',
                    'nas_id'          => $nas['id'],
                ];
            }
        }

        // Status filter (Connected / Disconnected - here all are Connected from live session)
        if (!empty($statusFilter) && strtolower($statusFilter) === 'offline') {
            $rows = []; // live sessions are always online; offline would need DB history
        }

        echo json_encode($rows);
        exit;
    }

    public function pppoeActive(): void {
        $pageTitle   = 'Active PPPoE Sessions';
        $currentPage = 'network';
        $currentSubPage = 'pppoe-active';
        
        $nasIdFilter = $_GET['nas_id'] ?? '';
        
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1");
        $allSessions = [];

        foreach ($nasDevices as $nas) {
            // Apply filter if specified
            if (!empty($nasIdFilter) && $nas['id'] != $nasIdFilter) {
                continue;
            }
            $mikrotik = new MikroTikService([
                'ip'       => $nas['ip_address'],
                'port'     => $nas['api_port'],
                'username' => $nas['username'],
                'password' => $nas['password'],
                'timeout'  => 10,
            ]);

            if ($mikrotik->connect()) {
                $sessions   = $mikrotik->getActiveSessions();
                $ifaceStats = $mikrotik->getInterfaceStats(); // Real per-interface traffic data

                foreach ($sessions as &$s) {
                    $s['nas_name'] = $nas['name'];
                    $s['nas_id']   = $nas['id'];

                    // Merge real traffic from interface stats (Pattern: <pppoe-USERNAME>)
                    $ifaceName = "<pppoe-" . ($s['name'] ?? '') . ">";
                    if (isset($ifaceStats[$ifaceName])) {
                        $st = $ifaceStats[$ifaceName];
                        $s['bytes-out'] = $st['tx']; // router→user = download
                        $s['bytes-in']  = $st['rx']; // user→router = upload
                    } else {
                        // Sometimes the name is just the username or pppoe-username
                        $altName = "pppoe-" . ($s['name'] ?? '');
                        if (isset($ifaceStats[$altName])) {
                            $st = $ifaceStats[$altName];
                            $s['bytes-out'] = $st['tx'];
                            $s['bytes-in']  = $st['rx'];
                        }
                    }

                    // Try to match with customer
                    $customer = $this->db->fetchOne("SELECT id, full_name, customer_code FROM customers WHERE pppoe_username = ?", [$s['name'] ?? '']);
                    if ($customer) {
                        $s['customer_name'] = $customer['full_name'];
                        $s['customer_id']   = $customer['id'];
                        $s['customer_code'] = $customer['customer_code'];

                        // Sync to database
                        $this->db->update('customers', [
                            'last_online_at' => date('Y-m-d H:i:s'),
                            'current_ip'     => $s['address'] ?? null
                        ], 'id = ?', [$customer['id']]);
                    }
                }
                $allSessions = array_merge($allSessions, $sessions);
            }
        }

        $viewFile = BASE_PATH . '/views/network/pppoe-active.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function kickPppoeSession(string $nasId, string $username): void {
        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id = ?", [$nasId]);
        if (!$nas) redirect(base_url('network/pppoe-active'));

        $mikrotik = new MikroTikService([
            'ip'       => $nas['ip_address'],
            'port'     => $nas['api_port'],
            'username' => $nas['username'],
            'password' => $nas['password'],
            'timeout'  => 10,
        ]);

        if ($mikrotik->connect()) {
            if ($mikrotik->kickSession($username)) {
                $_SESSION['success'] = "Session for $username kicked successfully.";
            } else {
                $_SESSION['error'] = "Failed to kick session for $username.";
            }
        }
        redirect(base_url('network/pppoe-active'));
    }

    public function apiGetProfiles(): void {
        header('Content-Type: application/json');
        $nasId = $_GET['nas_id'] ?? '';
        if (empty($nasId)) { echo json_encode([]); exit; }

        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id = ?", [$nasId]);
        if (!$nas) { echo json_encode([]); exit; }

        // Try MikroTik first
        $mikrotik = new MikroTikService([
            'ip'       => $nas['ip_address'],
            'port'     => $nas['api_port'] ?? 8728,
            'username' => $nas['username'],
            'password' => $nas['password'],
            'timeout'  => 3,
        ]);

        if ($mikrotik->connect()) {
            $profiles = $mikrotik->getProfiles();
            if (!empty($profiles)) {
                echo json_encode($profiles);
                exit;
            }
        }
        
        // Fallback: Get profiles from database
        $profiles = $this->db->fetchAll(
            "SELECT name FROM pppoe_profiles WHERE nas_id = ? OR nas_id IS NULL ORDER BY name",
            [$nasId]
        );
        
        // If no profiles in DB either, return default common profiles
        if (empty($profiles)) {
            $profiles = [
                ['name' => '1Mbps'],
                ['name' => '2Mbps'],
                ['name' => '3Mbps'],
                ['name' => '5Mbps'],
                ['name' => '10Mbps'],
            ];
        }
        
        echo json_encode($profiles);
        exit;
    }

    public function apiLiveSessions(): void {
        header('Content-Type: application/json');
        $nasIdFilter = $_GET['nas_id'] ?? '';
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1");
        $allSessions = [];

        foreach ($nasDevices as $nas) {
            if (!empty($nasIdFilter) && $nas['id'] != $nasIdFilter) continue;

            $mikrotik = new MikroTikService([
                'ip'       => $nas['ip_address'],
                'port'     => $nas['api_port'],
                'username' => $nas['username'],
                'password' => $nas['password'],
                'timeout'  => 8,
            ]);

            if ($mikrotik->connect()) {
                $sessions   = $mikrotik->getActiveSessions();
                $ifaceStats = $mikrotik->getInterfaceStats();

                foreach ($sessions as &$s) {
                    $s['nas_name'] = $nas['name'];
                    $s['nas_id']   = $nas['id'];

                    // Merge real traffic from interface stats
                    $ifaceName = "<pppoe-" . ($s['name'] ?? '') . ">";
                    if (isset($ifaceStats[$ifaceName])) {
                        $st = $ifaceStats[$ifaceName];
                        $s['bytes-out'] = $st['tx'];
                        $s['bytes-in']  = $st['rx'];
                    } else {
                        $altName = "pppoe-" . ($s['name'] ?? '');
                        if (isset($ifaceStats[$altName])) {
                            $st = $ifaceStats[$altName];
                            $s['bytes-out'] = $st['tx'];
                            $s['bytes-in']  = $st['rx'];
                        }
                    }

                    // bytes-in = download, bytes-out = upload (for JSON output)
                    $s['dl_bytes'] = (int)($s['bytes-out'] ?? 0);
                    $s['ul_bytes'] = (int)($s['bytes-in']  ?? 0);
                }
                $allSessions = array_merge($allSessions, $sessions);
            }
        }
        echo json_encode($allSessions);
        exit;
    }

    public function radius(): void {
        $pageTitle = 'RADIUS AAA';
        $currentPage = 'radius';
        $currentSubPage = 'radius';

        $users = $this->db->fetchAll(
            "SELECT c.id, c.username, c.value as password, c.attribute,
                   g.groupname as profile,
                   r.value as ip_address,
                   cust.id as customer_id, cust.full_name as customer_name, cust.customer_code
             FROM radcheck c
             LEFT JOIN radusergroup g ON g.username = c.username
             LEFT JOIN radreply r ON r.username = c.username AND r.attribute = 'Framed-IP-Address'
             LEFT JOIN customers cust ON cust.pppoe_username = c.username
             WHERE c.attribute = 'Cleartext-Password'
             ORDER BY c.username ASC"
        );

        // Get online users directly from MikroTik
        $onlineUsers = [];
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1");
        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        
        foreach ($nasDevices as $nas) {
            try {
                $mikrotik = new MikroTikService([
                    'ip' => $nas['ip_address'],
                    'port' => $nas['api_port'],
                    'username' => $nas['username'],
                    'password' => $nas['password'],
                    'timeout' => 5
                ]);
                if ($mikrotik->connect()) {
                    $sessions = $mikrotik->getActiveSessions();
                    foreach ($sessions as $s) {
                        $onlineUsers[] = $s['name'] ?? '';
                    }
                    $mikrotik->disconnect();
                }
            } catch (Exception $e) {
                // Skip unreachable NAS
            }
        }

        // Mark online status
        $onlineUsernames = array_flip($onlineUsers);
        foreach ($users as &$u) {
            $u['is_online'] = isset($onlineUsernames[$u['username']]) ? 1 : 0;
        }

        $profiles = $this->db->fetchAll("SELECT groupname FROM radgroupreply GROUP BY groupname ORDER BY groupname");
        $customers = $this->db->fetchAll("SELECT id, customer_code, full_name, pppoe_username FROM customers WHERE status = 'active' ORDER BY full_name");
        
        $viewFile = BASE_PATH . '/views/network/radius.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeRadiusUser(): void {
        $username = sanitize($_POST['username'] ?? '');
        $password = sanitize($_POST['password'] ?? '');
        $profile  = sanitize($_POST['profile'] ?? '');
        $ip_address = sanitize($_POST['ip_address'] ?? '');
        $customer_id = sanitize($_POST['customer_id'] ?? '');

        if ($this->radius->addUser($username, $password)) {
            if ($profile) $this->radius->assignGroup($username, $profile);
            
            // Handle static IP in radreply
            if ($ip_address) {
                $this->db->insert('radreply', [
                    'username' => $username,
                    'attribute' => 'Framed-IP-Address',
                    'op' => '=',
                    'value' => $ip_address
                ]);
            }
            
            // Link to customer if selected
            if (!empty($customer_id)) {
                $this->db->update('customers', [
                    'pppoe_username' => $username,
                    'pppoe_password' => $password
                ], 'id = ?', [$customer_id]);
            }
            
            $_SESSION['success'] = "RADIUS user $username created successfully.";
        } else {
            $_SESSION['error'] = "Failed to create RADIUS user. Ensure the RADIUS database connection is active.";
        }
        redirect(base_url('network/radius'));
    }

    public function updateRadiusUser(string $username): void {
        $username = urldecode($username);
        $new_password = sanitize($_POST['password'] ?? '');
        $profile  = sanitize($_POST['profile'] ?? '');
        $ip_address = sanitize($_POST['ip_address'] ?? '');

        if ($new_password) {
            $this->radius->updatePassword($username, $new_password);
            
            // Also update customer PPPoE password if linked
            $this->db->update('customers', ['pppoe_password' => $new_password], 'pppoe_username = ?', [$username]);
        }
        if ($profile) {
            $this->radius->assignGroup($username, $profile);
        }

        // Handle static IP
        $this->db->delete('radreply', "username = ? AND attribute = 'Framed-IP-Address'", [$username]);
        if ($ip_address) {
            $this->db->insert('radreply', [
                'username'  => $username,
                'attribute' => 'Framed-IP-Address',
                'op'        => '=',
                'value'     => $ip_address
            ]);
        }

        $_SESSION['success'] = "RADIUS user $username updated.";
        redirect(base_url('network/radius'));
    }

    public function deleteRadiusUser(string $username): void {
        $username = urldecode($username);
        
        // Unlink from customer
        $this->db->update('customers', [
            'pppoe_username' => null,
            'pppoe_password' => null
        ], 'pppoe_username = ?', [$username]);
        
        $this->radius->deleteUser($username);
        $_SESSION['success'] = "RADIUS user $username deleted.";
        redirect(base_url('network/radius'));
    }

    public function kickRadiusUser(string $username): void {
        $username = urldecode($username);
        // Find NAS for this user if online
        $active = $this->db->fetchOne("SELECT nasipaddress FROM radacct WHERE username = ? AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1", [$username]);
        if ($active && $active['nasipaddress']) {
            $nas = $this->db->fetchOne("SELECT id FROM nas_devices WHERE ip_address = ?", [$active['nasipaddress']]);
            if ($nas) {
                $this->kickPppoeSession($nas['id'], $username);
                return;
            }
        }
        $_SESSION['error'] = "User is not currently online or NAS is unreachable.";
        redirect(base_url('network/radius'));
    }

    // ── PPPoE Users Management ─────────────────────────────────────
    
    public function pppoeUsers(): void {
        $pageTitle = 'PPPoE Users';
        $currentPage = 'network';
        $currentSubPage = 'pppoe-users';

        $customers = $this->db->fetchAll(
            "SELECT c.*, b.name as branch_name,
                    (SELECT COUNT(*) FROM radacct a WHERE a.username = c.pppoe_username AND a.acctstoptime IS NULL) as is_online
             FROM customers c
             LEFT JOIN branches b ON b.id = c.branch_id
             WHERE c.pppoe_username IS NOT NULL AND c.pppoe_username != ''
             ORDER BY c.full_name"
        );
        
        // Pre-fetch profile and static IP for each customer
        $customerProfiles = [];
        $customerStaticIp = [];
        foreach ($customers as $c) {
            if (!empty($c['pppoe_username'])) {
                $profile = $this->db->fetchOne("SELECT groupname FROM radusergroup WHERE username = ?", [$c['pppoe_username']]);
                $staticIp = $this->db->fetchOne("SELECT value FROM radreply WHERE username = ? AND attribute = 'Framed-IP-Address'", [$c['pppoe_username']]);
                $customerProfiles[$c['id']] = $profile['groupname'] ?? '';
                $customerStaticIp[$c['id']] = $staticIp['value'] ?? '';
            }
        }
        
        $nasDevices = $this->db->fetchAll("SELECT id, name, ip_address FROM nas_devices WHERE is_active = 1");
        $profiles = $this->db->fetchAll("SELECT groupname FROM radgroupreply GROUP BY groupname ORDER BY groupname");
        
        $viewFile = BASE_PATH . '/views/network/pppoe-users.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function updatePppoeUser(string $id): void {
        $id = (int)$id;
        $customer = $this->db->fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);
        if (!$customer || empty($customer['pppoe_username'])) {
            $_SESSION['error'] = "Customer not found or has no PPPoE account.";
            redirect(base_url('network/pppoe-users'));
        }

        $new_password = sanitize($_POST['pppoe_password'] ?? '');
        $profile = sanitize($_POST['profile'] ?? '');
        $static_ip = sanitize($_POST['static_ip'] ?? '');
        $nas_id = sanitize($_POST['nas_id'] ?? '');

        // Update password in RADIUS
        if ($new_password) {
            $this->radius->updatePassword($customer['pppoe_username'], $new_password);
            $this->db->update('customers', ['pppoe_password' => $new_password], 'id = ?', [$id]);
        }

        // Update profile
        if ($profile) {
            $this->radius->assignGroup($customer['pppoe_username'], $profile);
        }

        // Update static IP
        $this->db->delete('radreply', "username = ? AND attribute = 'Framed-IP-Address'", [$customer['pppoe_username']]);
        if ($static_ip) {
            $this->db->insert('radreply', [
                'username'  => $customer['pppoe_username'],
                'attribute' => 'Framed-IP-Address',
                'op'        => '=',
                'value'     => $static_ip
            ]);
            $this->db->update('customers', ['static_ip' => $static_ip], 'id = ?', [$id]);
        }

        $_SESSION['success'] = "PPPoE user updated successfully.";
        redirect(base_url('network/pppoe-users'));
    }

    public function resetPppoePassword(string $id): void {
        $id = (int)$id;
        $customer = $this->db->fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);
        if (!$customer || empty($customer['pppoe_username'])) {
            $_SESSION['error'] = "Customer not found.";
            redirect(base_url('network/pppoe-users'));
        }

        $newPassword = generate_random_string(8);
        $this->radius->updatePassword($customer['pppoe_username'], $newPassword);
        $this->db->update('customers', ['pppoe_password' => $newPassword], 'id = ?', [$id]);

        $_SESSION['success'] = "Password reset to: $newPassword";
        redirect(base_url('network/pppoe-users'));
    }

    public function disablePppoe(string $id): void {
        $id = (int)$id;
        $customer = $this->db->fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);
        if ($customer && !empty($customer['pppoe_username'])) {
            $this->radius->deleteUser($customer['pppoe_username']);
            $this->db->update('customers', [
                'pppoe_username' => null,
                'pppoe_password' => null,
                'status' => 'inactive'
            ], 'id = ?', [$id]);
            $_SESSION['success'] = "PPPoE disabled for customer.";
        }
        redirect(base_url('network/pppoe-users'));
    }

    public function createPppoeForCustomer(string $id): void {
        $id = (int)$id;
        $customer = $this->db->fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);
        if (!$customer) {
            $_SESSION['error'] = "Customer not found.";
            redirect(base_url('network/pppoe-users'));
        }

        $username = sanitize($_POST['pppoe_username'] ?? '');
        $password = sanitize($_POST['pppoe_password'] ?? generate_random_string(8));
        $profile = sanitize($_POST['profile'] ?? '');
        $static_ip = sanitize($_POST['static_ip'] ?? '');

        if (empty($username)) {
            $_SESSION['error'] = "Username is required.";
            redirect(base_url('network/pppoe-users'));
        }

        // Check if username exists
        $exists = $this->db->fetchOne("SELECT id FROM radcheck WHERE username = ?", [$username]);
        if ($exists) {
            $_SESSION['error'] = "Username already exists in RADIUS.";
            redirect(base_url('network/pppoe-users'));
        }

        // Create RADIUS user
        $this->radius->addUser($username, $password);
        if ($profile) {
            $this->radius->assignGroup($username, $profile);
        }
        if ($static_ip) {
            $this->db->insert('radreply', [
                'username'  => $username,
                'attribute' => 'Framed-IP-Address',
                'op'        => '=',
                'value'     => $static_ip
            ]);
        }

        // Update customer
        $this->db->update('customers', [
            'pppoe_username' => $username,
            'pppoe_password' => $password,
            'static_ip' => $static_ip ?: null,
            'status' => 'active'
        ], 'id = ?', [$id]);

        $_SESSION['success'] = "PPPoE account created - User: $username, Pass: $password";
        redirect(base_url('network/pppoe-users'));
    }

    public function kickPppoeUser(string $id): void {
        $id = (int)$id;
        $customer = $this->db->fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);
        if (!$customer || empty($customer['pppoe_username'])) {
            $_SESSION['error'] = "Customer not found or has no PPPoE.";
            redirect(base_url('network/pppoe-users'));
        }

        // Find active session
        $active = $this->db->fetchOne(
            "SELECT nasipaddress FROM radacct WHERE username = ? AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1",
            [$customer['pppoe_username']]
        );

        if ($active && $active['nasipaddress']) {
            $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE ip_address = ?", [$active['nasipaddress']]);
            if ($nas) {
                $this->kickPppoeSession($nas['id'], $customer['pppoe_username']);
                return;
            }
        }
        $_SESSION['error'] = "User is not currently online.";
        redirect(base_url('network/pppoe-users'));
    }

    // ── PPPoE Profiles ─────────────────────────────────────────────

    public function pppoeProfiles(): void {
        $pageTitle = 'PPPoE Profiles';
        $currentPage = 'network';
        $currentSubPage = 'pppoe-profiles';

        $profiles = $this->db->fetchAll("SELECT p.*, n.name as nas_name FROM pppoe_profiles p LEFT JOIN nas_devices n ON n.id=p.nas_id ORDER BY p.name");
        $nasDevices = $this->db->fetchAll("SELECT id, name, ip_address FROM nas_devices WHERE is_active = 1");

        $viewFile = BASE_PATH . '/views/network/pppoe-profiles.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storePppoeProfile(): void {
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'nas_id' => !empty($_POST['nas_id']) ? (int)$_POST['nas_id'] : null,
            'local_address' => sanitize($_POST['local_address'] ?? ''),
            'remote_address' => sanitize($_POST['remote_address'] ?? ''),
            'dns_server' => sanitize($_POST['dns_server'] ?? ''),
            'session_timeout' => (int)($_POST['session_timeout'] ?? 0),
            'idle_timeout' => (int)($_POST['idle_timeout'] ?? 0),
            'rate_limit' => sanitize($_POST['rate_limit'] ?? ''),
            'is_active' => 1,
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = "Profile name is required.";
            redirect(base_url('network/pppoe-profiles'));
        }

        $this->db->insert('pppoe_profiles', $data);
        $_SESSION['success'] = "PPPoE profile created.";
        redirect(base_url('network/pppoe-profiles'));
    }

    public function updatePppoeProfile(string $id): void {
        $id = (int)$id;
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'nas_id' => !empty($_POST['nas_id']) ? (int)$_POST['nas_id'] : null,
            'local_address' => sanitize($_POST['local_address'] ?? ''),
            'remote_address' => sanitize($_POST['remote_address'] ?? ''),
            'dns_server' => sanitize($_POST['dns_server'] ?? ''),
            'session_timeout' => (int)($_POST['session_timeout'] ?? 0),
            'idle_timeout' => (int)($_POST['idle_timeout'] ?? 0),
            'rate_limit' => sanitize($_POST['rate_limit'] ?? ''),
        ];

        $this->db->update('pppoe_profiles', $data, 'id = ?', [$id]);
        $_SESSION['success'] = "PPPoE profile updated.";
        redirect(base_url('network/pppoe-profiles'));
    }

    public function deletePppoeProfile(string $id): void {
        $id = (int)$id;
        $this->db->delete('pppoe_profiles', 'id = ?', [$id]);
        $_SESSION['success'] = "PPPoE profile deleted.";
        redirect(base_url('network/pppoe-profiles'));
    }

    public function syncProfileToNas(string $id): void {
        $id = (int)$id;
        $profile = $this->db->fetchOne("SELECT p.*, n.ip_address, n.username, n.password, n.api_port FROM pppoe_profiles p LEFT JOIN nas_devices n ON n.id = p.nas_id WHERE p.id = ?", [$id]);
        
        if (!$profile || !$profile['nas_id']) {
            $_SESSION['error'] = "Profile not found or no NAS assigned.";
            redirect(base_url('network/pppoe-profiles'));
        }

        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        $mikrotik = new MikroTikService([
            'ip' => $profile['ip_address'],
            'port' => $profile['api_port'],
            'username' => $profile['username'],
            'password' => $profile['password'],
        ]);

        if ($mikrotik->connect()) {
            $result = $mikrotik->createPppoeProfile($profile['name'], [
                'local-address' => $profile['local_address'],
                'remote-address' => $profile['remote_address'],
                'dns-server' => $profile['dns_server'],
                'session-timeout' => $profile['session_timeout'] ?: '',
                'idle-timeout' => $profile['idle_timeout'] ?: '',
                'rate-limit' => $profile['rate_limit'] ?: '',
            ]);

            if ($result) {
                $this->db->update('pppoe_profiles', ['is_synced' => 1, 'last_synced' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
                $_SESSION['success'] = "Profile synced to MikroTik.";
            } else {
                $_SESSION['error'] = "Failed to sync to MikroTik.";
            }
        } else {
            $_SESSION['error'] = "Cannot connect to MikroTik.";
        }
        redirect(base_url('network/pppoe-profiles'));
    }

    public function radiusProfiles(): void {
        $pageTitle = 'RADIUS Profiles';
        $currentPage = 'radius';
        $currentSubPage = 'radius_profiles';
        
        // Local RADIUS profiles from database
        $profiles = $this->db->fetchAll(
            "SELECT groupname, COUNT(*) as attr_count 
             FROM radgroupreply 
             GROUP BY groupname 
             ORDER BY groupname"
        );
        
        $profileAttrs = [];
        $profileUserCount = [];
        foreach ($profiles as $p) {
            $attrs = $this->db->fetchAll(
                "SELECT attribute, op, value FROM radgroupreply WHERE groupname = ?",
                [$p['groupname']]
            );
            $profileAttrs[$p['groupname']] = $attrs;
            
            $cnt = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM radusergroup WHERE groupname = ?",
                [$p['groupname']]
            );
            $profileUserCount[$p['groupname']] = $cnt['cnt'] ?? 0;
        }
        
        // MikroTik PPPoE Profiles
        $mikrotikProfiles = [];
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1 AND type = 'mikrotik' LIMIT 1");
        
        if (!empty($nasDevices)) {
            require_once BASE_PATH . '/app/Services/MikroTikService.php';
            $nas = $nasDevices[0];
            $mikrotik = new MikroTikService([
                'ip' => $nas['ip_address'],
                'port' => $nas['api_port'],
                'username' => $nas['username'],
                'password' => $nas['password'],
            ]);
            
            if ($mikrotik->connect()) {
                $mikrotikProfiles = $mikrotik->getProfiles();
                $mikrotik->disconnect();
            }
        }
        
        $viewFile = BASE_PATH . '/views/network/radius-profiles.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeRadiusProfile(): void {
        $name = sanitize($_POST['name'] ?? '');
        $rate_limit = sanitize($_POST['rate_limit'] ?? '');
        $session_timeout = sanitize($_POST['session_timeout'] ?? '86400');
        $idle_timeout = sanitize($_POST['idle_timeout'] ?? '1800');
        
        if (empty($name)) {
            $_SESSION['error'] = "Profile name is required.";
            redirect(base_url('network/radius/profiles'));
        }
        
        $this->db->delete('radgroupreply', 'groupname = ?', [$name]);
        
        $this->db->insert('radgroupreply', [
            'groupname' => $name,
            'attribute' => 'Mikrotik-Rate-Limit',
            'op' => '=',
            'value' => $rate_limit ?: '10M/10M/20M/0/0'
        ]);
        
        $this->db->insert('radgroupreply', [
            'groupname' => $name,
            'attribute' => 'Session-Timeout',
            'op' => '=',
            'value' => $session_timeout
        ]);
        
        $this->db->insert('radgroupreply', [
            'groupname' => $name,
            'attribute' => 'Idle-Timeout',
            'op' => '=',
            'value' => $idle_timeout
        ]);
        
        $_SESSION['success'] = "Profile '$name' created.";
        redirect(base_url('network/radius/profiles'));
    }

    public function deleteRadiusProfile(string $name): void {
        $name = urldecode($name);
        $this->db->delete('radgroupreply', 'groupname = ?', [$name]);
        $this->db->delete('radgroupcheck', 'groupname = ?', [$name]);
        $_SESSION['success'] = "Profile '$name' deleted.";
        redirect(base_url('network/radius/profiles'));
    }

    // ── MikroTik RADIUS Configuration ───────────────────────────────

    public function mikrotikRadiusConfig(string $nas_id): void {
        $pageTitle = 'MikroTik RADIUS Config';
        $currentPage = 'network';
        $currentSubPage = 'nas';
        
        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id = ?", [$nas_id]);
        if (!$nas) redirect(base_url('network/nas'));
        
        $mikrotik = new MikroTikService([
            'ip'       => $nas['ip_address'],
            'port'     => $nas['api_port'],
            'username' => $nas['username'],
            'password' => $nas['password'],
            'timeout'  => $nas['timeout'] ?? 10,
        ]);
        
        $radiusConfig = [];
        $pppAaa = [];
        $pppoeServers = [];
        
        if ($mikrotik->connect()) {
            $radiusConfig = $mikrotik->getRadiusConfig();
            $pppAaa = $mikrotik->getPppRadiusStatus();
            $pppoeServers = $mikrotik->getPppoeServers();
            $mikrotik->disconnect();
        }
        
        $viewFile = BASE_PATH . '/views/network/mikrotik-radius.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function configureMikrotikRadius(string $nas_id): void {
        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id = ?", [$nas_id]);
        if (!$nas) {
            $_SESSION['error'] = "NAS not found.";
            redirect(base_url('network/nas'));
        }
        
        $radiusAddress = sanitize($_POST['radius_address'] ?? '');
        $radiusSecret = sanitize($_POST['radius_secret'] ?? '');
        $radiusPort = (int)($_POST['radius_port'] ?? 1812);
        
        if (empty($radiusAddress) || empty($radiusSecret)) {
            $_SESSION['error'] = "RADIUS address and secret are required.";
            redirect(base_url("network/mikrotik-radius/{$nas_id}"));
        }
        
        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        $mikrotik = new MikroTikService([
            'ip'       => $nas['ip_address'],
            'port'     => $nas['api_port'],
            'username' => $nas['username'],
            'password' => $nas['password'],
        ]);
        
        if ($mikrotik->connect()) {
            $result = $mikrotik->configureRadius($radiusAddress, $radiusSecret, $radiusPort);
            $mikrotik->disconnect();
            
            if ($result) {
                $_SESSION['success'] = "RADIUS configured successfully on {$nas['name']}";
            } else {
                $_SESSION['error'] = "Failed to configure RADIUS on MikroTik.";
            }
        } else {
            $_SESSION['error'] = "Cannot connect to MikroTik {$nas['name']}";
        }
        
        redirect(base_url("network/mikrotik-radius/{$nas_id}"));
    }

    public function enablePppRadius(string $nas_id): void {
        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id = ?", [$nas_id]);
        if (!$nas) {
            $_SESSION['error'] = "NAS not found.";
            redirect(base_url('network/nas'));
        }
        
        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        $mikrotik = new MikroTikService([
            'ip'       => $nas['ip_address'],
            'port'     => $nas['api_port'],
            'username' => $nas['username'],
            'password' => $nas['password'],
        ]);
        
        if ($mikrotik->connect()) {
            $result = $mikrotik->enablePppRadius();
            $mikrotik->disconnect();
            
            if ($result) {
                $_SESSION['success'] = "PPP RADIUS enabled on {$nas['name']}";
            } else {
                $_SESSION['error'] = "Failed to enable PPP RADIUS.";
            }
        } else {
            $_SESSION['error'] = "Cannot connect to MikroTik.";
        }
        
        redirect(base_url("network/mikrotik-radius/{$nas_id}"));
    }

    public function syncMikrotikUsers(string $nas_id): void {
        $nas = $this->db->fetchOne("SELECT * FROM nas_devices WHERE id = ?", [$nas_id]);
        if (!$nas) {
            $_SESSION['error'] = "NAS not found.";
            redirect(base_url('network/nas'));
        }
        
        // Get users from RADIUS database
        $radiusUsers = $this->db->fetchAll(
            "SELECT c.username, c.value as password, g.groupname as profile
             FROM radcheck c
             LEFT JOIN radusergroup g ON g.username = c.username
             WHERE c.attribute = 'Cleartext-Password'"
        );
        
        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        $mikrotik = new MikroTikService([
            'ip'       => $nas['ip_address'],
            'port'     => $nas['api_port'],
            'username' => $nas['username'],
            'password' => $nas['password'],
        ]);
        
        if ($mikrotik->connect()) {
            $result = $mikrotik->syncUsersFromRadius($radiusUsers);
            $mikrotik->disconnect();
            
            $_SESSION['success'] = "Synced {$result['synced']} users to MikroTik" . 
                (count($result['errors']) > 0 ? ". " . count($result['errors']) . " errors." : "");
        } else {
            $_SESSION['error'] = "Cannot connect to MikroTik.";
        }
        
        redirect(base_url("network/mikrotik-radius/{$nas_id}"));
    }

    public function macBindings(): void {
        $pageTitle = 'MAC Bindings & CallerID';
        $currentPage = 'network';
        $currentSubPage = 'mac-bindings';
        
        $bindings = $this->db->fetchAll(
            "SELECT mb.*, n.name as nas_name, c.full_name as customer_name, c.customer_code,
                    o.serial_number as onu_serial_linked, o.model as onu_model
             FROM mac_bindings mb
             LEFT JOIN nas_devices n ON n.id = mb.nas_id
             LEFT JOIN customers c ON c.id = mb.customer_id
             LEFT JOIN onus o ON o.customer_id = mb.customer_id
             ORDER BY mb.created_at DESC"
        );
        
        $nasDevices = $this->db->fetchAll("SELECT id, name, ip_address FROM nas_devices WHERE is_active = 1");
        $customers  = $this->db->fetchAll(
            "SELECT c.id, c.customer_code, c.full_name, c.phone, c.pppoe_username,
                    o.serial_number as onu_serial, o.mac_address as onu_mac, o.model as onu_model
             FROM customers c
             LEFT JOIN onus o ON o.customer_id = c.id
             WHERE c.status = 'active'
             ORDER BY c.full_name"
        );
        
        $viewFile = BASE_PATH . '/views/network/mac-bindings.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeMacBinding(): void {
        $mac = strtoupper(sanitize($_POST['mac_address'] ?? ''));
        if (!preg_match('/^([0-9A-F]{2}[:\-]){5}[0-9A-F]{2}$/', $mac)) {
            $_SESSION['error'] = "Invalid MAC address format (e.g. AA:BB:CC:DD:EE:FF)";
            redirect(base_url('network/mac-bindings'));
        }
        
        $data = [
            'username'     => sanitize($_POST['username'] ?? ''),
            'device_type'  => in_array($_POST['device_type'] ?? '', ['onu','router','other']) ? $_POST['device_type'] : 'router',
            'binding_type' => in_array($_POST['binding_type'] ?? '', ['pppoe_callerid','mac_auth','static_ip']) ? $_POST['binding_type'] : 'pppoe_callerid',
            'mac_address'  => $mac,
            'caller_id'    => sanitize($_POST['caller_id'] ?? ''),
            'onu_serial'   => sanitize($_POST['onu_serial'] ?? ''),
            'router_brand' => sanitize($_POST['router_brand'] ?? ''),
            'router_model' => sanitize($_POST['router_model'] ?? ''),
            'ip_address'   => sanitize($_POST['ip_address'] ?? ''),
            'nas_id'       => !empty($_POST['nas_id']) ? (int)$_POST['nas_id'] : null,
            'customer_id'  => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
            'is_active'    => 1,
            'is_allowed'   => isset($_POST['is_allowed']) ? 1 : 0,
            'description'  => sanitize($_POST['description'] ?? ''),
        ];
        
        $this->db->insert('mac_bindings', $data);
        $_SESSION['success'] = 'MAC binding created successfully';
        redirect(base_url('network/mac-bindings'));
    }

    public function updateMacBinding(string $id): void {
        $mac = strtoupper(sanitize($_POST['mac_address'] ?? ''));
        if (!empty($mac) && !preg_match('/^([0-9A-F]{2}[:\-]){5}[0-9A-F]{2}$/', $mac)) {
            $_SESSION['error'] = "Invalid MAC address format";
            redirect(base_url('network/mac-bindings'));
        }
        
        $data = [
            'username'     => sanitize($_POST['username'] ?? ''),
            'device_type'  => in_array($_POST['device_type'] ?? '', ['onu','router','other']) ? $_POST['device_type'] : 'router',
            'binding_type' => in_array($_POST['binding_type'] ?? '', ['pppoe_callerid','mac_auth','static_ip']) ? $_POST['binding_type'] : 'pppoe_callerid',
            'caller_id'    => sanitize($_POST['caller_id'] ?? ''),
            'onu_serial'   => sanitize($_POST['onu_serial'] ?? ''),
            'router_brand' => sanitize($_POST['router_brand'] ?? ''),
            'router_model' => sanitize($_POST['router_model'] ?? ''),
            'ip_address'   => sanitize($_POST['ip_address'] ?? ''),
            'nas_id'       => !empty($_POST['nas_id']) ? (int)$_POST['nas_id'] : null,
            'customer_id'  => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
            'is_allowed'   => isset($_POST['is_allowed']) ? 1 : 0,
            'description'  => sanitize($_POST['description'] ?? ''),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        
        if (!empty($mac)) {
            $data['mac_address'] = $mac;
        }
        
        $this->db->update('mac_bindings', $data, 'id=?', [$id]);
        $_SESSION['success'] = 'MAC binding updated successfully';
        redirect(base_url('network/mac-bindings'));
    }

    public function deleteMacBinding(string $id): void {
        $this->db->delete('mac_bindings', 'id=?', [$id]);
        $_SESSION['success'] = 'MAC binding deleted successfully';
        redirect(base_url('network/mac-bindings'));
    }

    public function toggleMacBinding(string $id): void {
        $binding = $this->db->fetchOne("SELECT * FROM mac_bindings WHERE id=?", [$id]);
        if ($binding) {
            $this->db->update('mac_bindings', ['is_active' => $binding['is_active'] ? 0 : 1], 'id=?', [$id]);
        }
        redirect(base_url('network/mac-bindings'));
    }

    public function macFilters(): void {
        $pageTitle = 'MAC Filters';
        $currentPage = 'network';
        $currentSubPage = 'mac-filters';
        
        $filters = $this->db->fetchAll(
            "SELECT mf.*, n.name as nas_name, c.full_name as customer_name, c.customer_code
             FROM mac_filters mf
             LEFT JOIN nas_devices n ON n.id = mf.nas_id
             LEFT JOIN customers c ON c.id = mf.customer_id
             ORDER BY mf.created_at DESC"
        );
        
        $nasDevices = $this->db->fetchAll("SELECT id, name, ip_address FROM nas_devices WHERE is_active = 1");
        $customers = $this->db->fetchAll("SELECT id, customer_code, full_name, phone FROM customers WHERE status = 'active' ORDER BY full_name");
        
        $viewFile = BASE_PATH . '/views/network/mac-filters.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function storeMacFilter(): void {
        $mac = strtoupper(sanitize($_POST['mac_address'] ?? ''));
        if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac)) {
            $_SESSION['error'] = "Invalid MAC address format";
            redirect(base_url('network/mac-filters'));
        }
        
        $data = [
            'mac_address' => $mac,
            'action'      => sanitize($_POST['action'] ?? 'allow'),
            'nas_id'      => !empty($_POST['nas_id']) ? (int)$_POST['nas_id'] : null,
            'customer_id' => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
            'reason'      => sanitize($_POST['reason'] ?? ''),
            'is_active'   => 1,
            'expires_at'  => !empty($_POST['expires_at']) ? sanitize($_POST['expires_at']) : null,
        ];
        
        $this->db->insert('mac_filters', $data);
        $_SESSION['success'] = 'MAC filter created successfully';
        redirect(base_url('network/mac-filters'));
    }

    public function deleteMacFilter(string $id): void {
        $this->db->delete('mac_filters', 'id=?', [$id]);
        $_SESSION['success'] = 'MAC filter deleted successfully';
        redirect(base_url('network/mac-filters'));
    }

    public function toggleMacFilter(string $id): void {
        $filter = $this->db->fetchOne("SELECT * FROM mac_filters WHERE id=?", [$id]);
        if ($filter) {
            $this->db->update('mac_filters', ['is_active' => $filter['is_active'] ? 0 : 1], 'id=?', [$id]);
        }
        redirect(base_url('network/mac-filters'));
    }

    public function syncProfilesFromMikrotik(): void {
        $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1 AND type = 'mikrotik' LIMIT 1");
        
        if (empty($nasDevices)) {
            $_SESSION['error'] = "No active MikroTik NAS found.";
            redirect(base_url('network/radius/profiles'));
        }
        
        require_once BASE_PATH . '/app/Services/MikroTikService.php';
        $nas = $nasDevices[0];
        $mikrotik = new MikroTikService([
            'ip' => $nas['ip_address'],
            'port' => $nas['api_port'],
            'username' => $nas['username'],
            'password' => $nas['password'],
        ]);
        
        if (!$mikrotik->connect()) {
            $_SESSION['error'] = "Cannot connect to MikroTik.";
            redirect(base_url('network/radius/profiles'));
        }
        
        $mtProfiles = $mikrotik->getProfiles();
        $mikrotik->disconnect();
        
        $synced = 0;
        foreach ($mtProfiles as $mp) {
            $name = $mp['name'] ?? null;
            if (empty($name)) continue;
            
            $rateLimit = $mp['rate-limit'] ?? '';
            
            $this->db->delete('radgroupreply', 'groupname = ?', [$name]);
            
            if (!empty($rateLimit)) {
                $this->db->insert('radgroupreply', [
                    'groupname' => $name,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => '=',
                    'value' => $rateLimit
                ]);
                $synced++;
            }
        }
        
        $_SESSION['success'] = "Synced $synced profiles from MikroTik.";
        redirect(base_url('network/radius/profiles'));
    }

    // ── RADIUS Dashboard ──────────────────────────────────────────────────────

    public function radiusDashboard(): void {
        $pageTitle      = 'RADIUS Dashboard';
        $currentPage    = 'radius';
        $currentSubPage = 'radius_dashboard';

        $stats = ['total_active' => 0, 'total_bytes_in' => 0, 'total_bytes_out' => 0, 'unique_nas_count' => 0];
        $activeSessions   = [];
        $unresolvedAlerts = [];
        $totalUsers       = 0;
        $onlineUsers      = [];

        try {
            $pdo = Database::getInstance('radius')->getConnection();

            // Total registered users
            $totalUsers = (int) $pdo->query("SELECT COUNT(DISTINCT username) FROM radcheck WHERE attribute='Cleartext-Password'")->fetchColumn();

            // Active sessions from radacct (acctstoptime IS NULL)
            $acctRows = $pdo->query(
                "SELECT username, nasipaddress AS nas_ip, framedipaddress AS framed_ip,
                        acctstarttime AS start_time,
                        acctinputoctets AS bytes_in, acctoutputoctets AS bytes_out,
                        TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) AS duration_seconds
                 FROM radacct WHERE acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 100"
            )->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($acctRows)) {
                $activeSessions = $acctRows;
                $stats['total_active']     = count($acctRows);
                $stats['total_bytes_in']   = array_sum(array_column($acctRows, 'bytes_in'));
                $stats['total_bytes_out']  = array_sum(array_column($acctRows, 'bytes_out'));
                $stats['unique_nas_count'] = count(array_unique(array_column($acctRows, 'nas_ip')));
            } else {
                // radacct empty — try MikroTik live sessions as fallback
                require_once BASE_PATH . '/app/Services/MikroTikService.php';
                $nasDevices = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active = 1 LIMIT 5");
                foreach ($nasDevices as $nas) {
                    try {
                        $mt = new MikroTikService([
                            'ip' => $nas['ip_address'], 'port' => $nas['api_port'],
                            'username' => $nas['username'], 'password' => $nas['password'], 'timeout' => 5
                        ]);
                        if ($mt->connect()) {
                            $sessions = $mt->getActiveSessions();
                            foreach ($sessions as $s) {
                                $activeSessions[] = [
                                    'username'         => $s['name'] ?? '—',
                                    'nas_ip'           => $nas['ip_address'],
                                    'framed_ip'        => $s['address'] ?? '—',
                                    'start_time'       => $s['uptime'] ?? '—',
                                    'bytes_in'         => (int)($s['bytes-in'] ?? 0),
                                    'bytes_out'        => (int)($s['bytes-out'] ?? 0),
                                    'duration_seconds' => 0,
                                ];
                            }
                            $mt->disconnect();
                        }
                    } catch (Exception $e) { /* skip unreachable NAS */ }
                }
                $stats['total_active']     = count($activeSessions);
                $stats['total_bytes_in']   = array_sum(array_column($activeSessions, 'bytes_in'));
                $stats['total_bytes_out']  = array_sum(array_column($activeSessions, 'bytes_out'));
                $stats['unique_nas_count'] = count(array_unique(array_column($activeSessions, 'nas_ip')));
            }

            // Unresolved alerts
            require_once BASE_PATH . '/app/Services/RadiusAlertService.php';
            $alertService     = new RadiusAlertService($pdo);
            $unresolvedAlerts = $alertService->getUnresolvedAlerts();

        } catch (Exception $e) {
            $_SESSION['error'] = 'RADIUS database error: ' . $e->getMessage();
        }

        $viewFile = BASE_PATH . '/views/radius/dashboard.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── RADIUS Sessions ───────────────────────────────────────────────────────

    public function radiusSessions(): void {
        $pageTitle      = 'RADIUS Sessions';
        $currentPage    = 'radius';
        $currentSubPage = 'radius_sessions';

        $activeSessions = [];
        $stats = ['total_active' => 0, 'total_bytes_in' => 0, 'total_bytes_out' => 0, 'unique_nas_count' => 0];

        try {
            $pdo = Database::getInstance('radius')->getConnection();

            $where  = ['acctstoptime IS NULL'];
            $params = [];
            if (!empty($_GET['username'])) {
                $where[]  = 'username LIKE :username';
                $params[':username'] = '%' . $_GET['username'] . '%';
            }
            if (!empty($_GET['nas_ip'])) {
                $where[]  = 'nasipaddress = :nas_ip';
                $params[':nas_ip'] = $_GET['nas_ip'];
            }

            $sql = "SELECT username, nasipaddress AS nas_ip, framedipaddress AS framed_ip,
                           acctstarttime AS start_time, acctuniqueid AS session_id,
                           acctinputoctets AS bytes_in, acctoutputoctets AS bytes_out,
                           TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) AS duration_seconds
                    FROM radacct WHERE " . implode(' AND ', $where) . "
                    ORDER BY acctstarttime DESC LIMIT 200";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $activeSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats['total_active']     = count($activeSessions);
            $stats['total_bytes_in']   = (int) array_sum(array_column($activeSessions, 'bytes_in'));
            $stats['total_bytes_out']  = (int) array_sum(array_column($activeSessions, 'bytes_out'));
            $stats['unique_nas_count'] = count(array_unique(array_column($activeSessions, 'nas_ip')));

        } catch (Exception $e) {
            $_SESSION['error'] = 'RADIUS database error: ' . $e->getMessage();
        }

        $viewFile = BASE_PATH . '/views/radius/sessions.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function terminateRadiusSession(string $sessionId): void {
        require_once BASE_PATH . '/app/Services/RadiusSessionService.php';
        require_once BASE_PATH . '/app/Services/RadiusSessionTimeoutService.php';

        try {
            $pdo            = Database::getInstance('radius')->getConnection();
            $sessionService = new RadiusSessionService($pdo);
            $timeoutService = new RadiusSessionTimeoutService($pdo, $sessionService);
            $timeoutService->terminateSession($sessionId, 'Admin-Reset');
            $_SESSION['success'] = "Session terminated.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to terminate session: ' . $e->getMessage();
        }

        redirect(base_url('network/radius/sessions'));
    }

    // ── RADIUS Analytics ──────────────────────────────────────────────────────

    public function radiusAnalytics(): void {
        $pageTitle      = 'RADIUS Analytics';
        $currentPage    = 'radius';
        $currentSubPage = 'radius_analytics';

        $period       = in_array($_GET['period'] ?? '', ['today','week','month']) ? $_GET['period'] : 'today';
        $topUsers     = [];
        $hourlyCounts = array_map(fn($h) => ['hour' => $h, 'session_count' => 0], range(0, 23));
        $dailySummary = [];
        $totalUsers   = 0;

        try {
            $pdo   = Database::getInstance('radius')->getConnection();
            $today = date('Y-m-d');

            // Total registered users
            $totalUsers = (int) $pdo->query("SELECT COUNT(DISTINCT username) FROM radcheck WHERE attribute='Cleartext-Password'")->fetchColumn();

            // Top users from radacct (if data exists), else from radcheck
            $dateExpr = match ($period) {
                'week'  => "DATE(acctstarttime) >= DATE(NOW() - INTERVAL 7 DAY)",
                'month' => "DATE(acctstarttime) >= DATE(NOW() - INTERVAL 30 DAY)",
                default => "DATE(acctstarttime) = CURDATE()",
            };

            $acctCount = (int) $pdo->query("SELECT COUNT(*) FROM radacct")->fetchColumn();

            if ($acctCount > 0) {
                $topUsers = $pdo->query(
                    "SELECT username,
                            COALESCE(SUM(acctinputoctets),0)  AS total_bytes_in,
                            COALESCE(SUM(acctoutputoctets),0) AS total_bytes_out,
                            COALESCE(SUM(acctinputoctets+acctoutputoctets),0) AS total_bytes,
                            COUNT(*) AS session_count
                     FROM radacct WHERE {$dateExpr}
                     GROUP BY username ORDER BY total_bytes DESC LIMIT 10"
                )->fetchAll(PDO::FETCH_ASSOC);

                // Hourly session counts from radacct
                $hourlyRaw = $pdo->query(
                    "SELECT HOUR(acctstarttime) AS hour, COUNT(*) AS session_count
                     FROM radacct WHERE DATE(acctstarttime) = '{$today}'
                     GROUP BY HOUR(acctstarttime)"
                )->fetchAll(PDO::FETCH_ASSOC);
                $byHour = [];
                foreach ($hourlyRaw as $r) { $byHour[(int)$r['hour']] = (int)$r['session_count']; }
                $hourlyCounts = array_map(fn($h) => ['hour' => $h, 'session_count' => $byHour[$h] ?? 0], range(0, 23));

                // Daily summary from radacct
                $dailySummary = $pdo->query(
                    "SELECT DATE(acctstarttime) AS date,
                            SUM(acctinputoctets)  AS total_bytes_in,
                            SUM(acctoutputoctets) AS total_bytes_out,
                            COUNT(*) AS total_sessions,
                            COUNT(DISTINCT username) AS unique_users
                     FROM radacct
                     WHERE DATE(acctstarttime) >= DATE(NOW() - INTERVAL 6 DAY)
                     GROUP BY DATE(acctstarttime) ORDER BY date ASC"
                )->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // No radacct data — show top users by registration (radcheck)
                $topUsers = $pdo->query(
                    "SELECT c.username, g.groupname AS profile,
                            0 AS total_bytes_in, 0 AS total_bytes_out, 0 AS total_bytes, 0 AS session_count
                     FROM radcheck c LEFT JOIN radusergroup g ON g.username = c.username
                     WHERE c.attribute = 'Cleartext-Password'
                     ORDER BY c.username ASC LIMIT 10"
                )->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'RADIUS database error: ' . $e->getMessage();
        }

        $viewFile = BASE_PATH . '/views/radius/analytics.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── RADIUS Audit Log ──────────────────────────────────────────────────────

    public function radiusAudit(): void {
        $pageTitle      = 'RADIUS Audit Log';
        $currentPage    = 'radius';
        $currentSubPage = 'radius_audit';

        require_once BASE_PATH . '/app/Services/RadiusAuditService.php';

        $logs = [];

        try {
            $pdo          = Database::getInstance('radius')->getConnection();
            $auditService = new RadiusAuditService($pdo);

            $filters = [];
            if (!empty($_GET['admin_user']))      $filters['admin_user']      = $_GET['admin_user'];
            if (!empty($_GET['action']))          $filters['action']          = $_GET['action'];
            if (!empty($_GET['target_username'])) $filters['target_username'] = $_GET['target_username'];
            if (!empty($_GET['date_from']))       $filters['date_from']       = $_GET['date_from'];
            if (!empty($_GET['date_to']))         $filters['date_to']         = $_GET['date_to'];

            $logs = $auditService->getLogs($filters, 100);
        } catch (Exception $e) {
            $_SESSION['error'] = 'RADIUS database not available: ' . $e->getMessage();
        }

        $viewFile = BASE_PATH . '/views/radius/audit.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}
