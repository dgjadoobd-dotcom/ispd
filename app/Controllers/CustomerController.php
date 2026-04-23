<?php

class CustomerController {
    private Database $db;
    private RadiusService $radius;

    public function __construct() {
        $this->db = Database::getInstance();
        require_once BASE_PATH . '/app/Services/RadiusService.php';
        $this->radius = new RadiusService();
    }

    public function index(): void {
        $pageTitle    = 'Customers';
        $currentPage  = 'clients';
        $currentSubPage = 'client-list';

        $search     = sanitize($_GET['search'] ?? '');
        $status     = sanitize($_GET['status'] ?? '');
        $package_id = (int)($_GET['package_id'] ?? 0);
        $zone_id    = (int)($_GET['zone_id'] ?? 0);
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $limit      = 20;
        $offset     = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = '(c.full_name LIKE ? OR c.phone LIKE ? OR c.customer_code LIKE ? OR c.pppoe_username LIKE ?)';
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }
        if (!empty($status)) { $where[] = 'c.status = ?'; $params[] = $status; }
        if ($package_id)      { $where[] = 'c.package_id = ?'; $params[] = $package_id; }
        if ($zone_id)         { $where[] = 'c.zone_id = ?'; $params[] = $zone_id; }

        // Branch filter for non-super admins
        if (($_SESSION['user_role'] ?? '') !== 'superadmin' && !empty($_SESSION['branch_id'])) {
            $where[] = 'c.branch_id = ?';
            $params[] = $_SESSION['branch_id'];
        }

        $whereStr = implode(' AND ', $where);

        $total  = $this->db->fetchOne("SELECT COUNT(*) as c FROM customers c WHERE $whereStr", $params)['c'];

        // Handle Export
        $export = $_GET['export'] ?? '';
        if ($export === 'csv' || $export === 'pdf') {
            $allRows = $this->db->fetchAll(
                "SELECT c.*, p.name as package_name, z.name as zone_name, b.name as branch_name
                 FROM customers c
                 LEFT JOIN packages p ON p.id = c.package_id
                 LEFT JOIN zones z ON z.id = c.zone_id
                 LEFT JOIN branches b ON b.id = c.branch_id
                 WHERE $whereStr ORDER BY c.created_at DESC",
                $params
            );
            if ($export === 'csv') {
                $this->exportCsv($allRows);
            } else {
                $this->exportPdf($allRows);
            }
            return;
        }

        $customers = $this->db->fetchAll(
            "SELECT c.*, p.name as package_name, p.speed_download, p.speed_upload,
                    z.name as zone_name, b.name as branch_name, n.name as nas_name,
                    (SELECT mac_address FROM mac_bindings mb WHERE mb.username = c.pppoe_username AND mb.is_active = 1 LIMIT 1) as mac_from_binding
             FROM customers c
             LEFT JOIN packages p ON p.id = c.package_id
             LEFT JOIN zones z ON z.id = c.zone_id
             LEFT JOIN branches b ON b.id = c.branch_id
             LEFT JOIN nas_devices n ON n.id = c.nas_id
             WHERE $whereStr ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset",
            $params
        );

        $packages = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_active=1 ORDER BY name");
        $zones    = $this->db->fetchAll("SELECT id, name FROM zones WHERE is_active=1 ORDER BY name");
        $totalPages = ceil($total / $limit);

        $viewFile = BASE_PATH . '/views/customers/list.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function create(): void {
        $pageTitle   = 'New Customer';
        $currentPage = 'clients';
        $currentSubPage = 'client-create';
        $packages    = $this->db->fetchAll("SELECT * FROM packages WHERE is_active=1 ORDER BY price ASC");
        $zones       = $this->db->fetchAll("SELECT z.*, b.name as branch_name FROM zones z JOIN branches b ON b.id=z.branch_id WHERE z.is_active=1");
        $branches    = $this->db->fetchAll("SELECT * FROM branches WHERE is_active=1");
        $nasDevices  = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active=1");
        $viewFile    = BASE_PATH . '/views/customers/create.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function store(): void {
        $data = [
            'customer_code'   => $this->generateCustomerCode(),
            'branch_id'       => (int)$_POST['branch_id'],
            'nas_id'          => !empty($_POST['nas_id']) ? (int)$_POST['nas_id'] : null,
            'zone_id'         => !empty($_POST['zone_id']) ? (int)$_POST['zone_id'] : null,
            'package_id'      => !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null,
            'full_name'       => sanitize($_POST['full_name'] ?? ''),
            'father_name'     => sanitize($_POST['father_name'] ?? ''),
            'phone'           => sanitize($_POST['phone'] ?? ''),
            'phone_alt'       => sanitize($_POST['phone_alt'] ?? ''),
            'email'           => sanitize($_POST['email'] ?? ''),
            'address'         => sanitize($_POST['address'] ?? ''),
            'nid_number'      => sanitize($_POST['nid_number'] ?? ''),
            'connection_type' => sanitize($_POST['connection_type'] ?? 'pppoe'),
            'pppoe_username'  => sanitize($_POST['pppoe_username'] ?? ''),
            'pppoe_password'  => !empty($_POST['pppoe_password']) ? sanitize($_POST['pppoe_password']) : null,
            'mikrotik_profile' => sanitize($_POST['mikrotik_profile'] ?? ''),
            'static_ip'       => sanitize($_POST['static_ip'] ?? ''),
            'status'          => 'pending',
            'connection_date' => $_POST['connection_date'] ? sanitize($_POST['connection_date']) : date('Y-m-d'),
            'monthly_charge'  => (float)($_POST['monthly_charge'] ?? 0),
            'billing_day'     => (int)($_POST['billing_day'] ?? 1),
            'notes'           => sanitize($_POST['notes'] ?? ''),
            'created_by'      => $_SESSION['user_id'],
            // Extended client fields
            'mac_address'          => strtoupper(trim(sanitize($_POST['mac_address'] ?? ''))),
            'road_no'              => sanitize($_POST['road_no'] ?? ''),
            'house_no'             => sanitize($_POST['house_no'] ?? ''),
            'sub_zone'             => sanitize($_POST['sub_zone'] ?? ''),
            'box_no'               => sanitize($_POST['box_no'] ?? ''),
            'client_type'          => in_array($_POST['client_type'] ?? '', ['home','business','corporate','other']) ? $_POST['client_type'] : 'home',
            'thana'                => sanitize($_POST['thana'] ?? ''),
            'district'             => sanitize($_POST['district'] ?? ''),
            'device_name'          => sanitize($_POST['device_name'] ?? ''),
            'device_purchase_date' => !empty($_POST['device_purchase_date']) ? sanitize($_POST['device_purchase_date']) : null,
            'assigned_employee'    => sanitize($_POST['assigned_employee'] ?? ''),
        ];

        if (empty($data['full_name']) || empty($data['phone'])) {
            $_SESSION['error'] = 'Name and phone are required.';
            redirect(base_url('customers/create'));
        }

        $id = $this->db->insert('customers', $data);

        // Handle NID/photo uploads
        $this->handleFileUpload('nid_photo', $id, 'kyc');
        $this->handleFileUpload('customer_photo', $id, 'photos');

        // Create Radius user if PPPoE
        if (!empty($data['pppoe_username'])) {
            $pkg = !empty($data['package_id']) ? $this->db->fetchOne("SELECT * FROM packages WHERE id=?", [$data['package_id']]) : null;
            $radiusPassword = $data['pppoe_password'] ?? 'default123';
            $radiusProfile  = !empty($data['mikrotik_profile']) ? $data['mikrotik_profile'] : ($pkg['radius_profile'] ?? 'default');

            // Sync to local DB
            $this->db->insert('radius_users', [
                'customer_id' => $id,
                'username'    => $data['pppoe_username'],
                'password'    => $radiusPassword,
                'profile'     => $radiusProfile,
                'is_active'   => 1,
            ]);

            // Sync to Real FreeRADIUS
            if ($this->radius->isEnabled()) {
                $this->radius->addUser($data['pppoe_username'], $radiusPassword);
                $this->radius->assignGroup($data['pppoe_username'], $radiusProfile);
            }
        }

        // Log activity
        $this->log('customer_created', 'customers', $id, null, $data);

        // Create welcome work order
        $this->createInstallationWorkOrder($id, $data);

        redirect(base_url("customers/view/{$id}"));
    }

    public function view(string $id): void {
        $pageTitle = 'Customer Details';
        $currentPage = 'customers';
        $customer = $this->db->fetchOne(
            "SELECT c.*, p.name as package_name, p.speed_download, p.speed_upload, p.mikrotik_profile,
                    z.name as zone_name, b.name as branch_name, a.name as area_name
             FROM customers c
             LEFT JOIN packages p ON p.id=c.package_id
             LEFT JOIN zones z ON z.id=c.zone_id
             LEFT JOIN branches b ON b.id=c.branch_id
             LEFT JOIN areas a ON a.id=c.area_id
             WHERE c.id=?",
            [$id]
        );
        if (!$customer) { http_response_code(404); die('Customer not found'); }

        $invoices = $this->db->fetchAll(
            "SELECT * FROM invoices WHERE customer_id=? ORDER BY billing_month DESC LIMIT 12", [$id]);
        $payments = $this->db->fetchAll(
            "SELECT * FROM payments WHERE customer_id=? ORDER BY payment_date DESC LIMIT 10", [$id]);
        $onu = $this->db->fetchOne("SELECT * FROM onus WHERE customer_id=?", [$id]);
        $workOrders = $this->db->fetchAll(
            "SELECT wo.*, t.name as technician FROM work_orders wo LEFT JOIN technicians t ON t.id=wo.technician_id WHERE wo.customer_id=? ORDER BY wo.created_at DESC LIMIT 5", [$id]);

        // AI Account Health Summary
        $aiService = new AiService();
        $aiSummary = null;
        if (env('AI_ENABLED')) {
            $billingStatus = !empty($invoices) ? ($invoices[0]['status'] === 'unpaid' ? 'Overdue' : 'Paid') : 'No History';
            $lastPayment = !empty($payments) ? date('d M Y', strtotime($payments[0]['payment_date'])) : 'Never';
            $prompt = "Provide a 1-2 sentence professional summary of this ISP customer's account health for an admin.
            Name: {$customer['full_name']}
            Status: {$customer['status']}
            Billing: $billingStatus
            Last Payment: $lastPayment
            Package: {$customer['package_name']}
            Recent Work Orders: " . count($workOrders);
            
            $aiSummary = $aiService->getChatCompletion([
                ['role' => 'system', 'content' => 'You are an ISP business analyst. Be brief and professional.'],
                ['role' => 'user', 'content' => $prompt]
            ], ['max_tokens' => 100]);
        }

        $viewFile = BASE_PATH . '/views/customers/view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function edit(string $id): void {
        $pageTitle   = 'Edit Customer';
        $currentPage = 'customers';
        $customer    = $this->db->fetchOne("SELECT * FROM customers WHERE id=?", [$id]);
        if (!$customer) { http_response_code(404); die('Not found'); }
        $packages    = $this->db->fetchAll("SELECT * FROM packages WHERE is_active=1 ORDER BY price ASC");
        $zones       = $this->db->fetchAll("SELECT z.*, b.name as branch_name FROM zones z JOIN branches b ON b.id=z.branch_id WHERE z.is_active=1");
        $branches    = $this->db->fetchAll("SELECT * FROM branches WHERE is_active=1");
        $nasDevices  = $this->db->fetchAll("SELECT * FROM nas_devices WHERE is_active=1");
        $viewFile    = BASE_PATH . '/views/customers/edit.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function update(string $id): void {
        $old = $this->db->fetchOne("SELECT * FROM customers WHERE id=?", [$id]);
        $data = [
            'full_name'       => sanitize($_POST['full_name'] ?? ''),
            'phone'           => sanitize($_POST['phone'] ?? ''),
            'phone_alt'       => sanitize($_POST['phone_alt'] ?? ''),
            'email'           => sanitize($_POST['email'] ?? ''),
            'address'         => sanitize($_POST['address'] ?? ''),
            'package_id'      => !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null,
            'nas_id'          => !empty($_POST['nas_id']) ? (int)$_POST['nas_id'] : null,
            'zone_id'         => !empty($_POST['zone_id']) ? (int)$_POST['zone_id'] : null,
            'mikrotik_profile' => sanitize($_POST['mikrotik_profile'] ?? ''),
            'monthly_charge'  => (float)($_POST['monthly_charge'] ?? 0),
            'billing_day'     => (int)($_POST['billing_day'] ?? 1),
            'notes'           => sanitize($_POST['notes'] ?? ''),
            // Extended client fields
            'mac_address'          => strtoupper(trim(sanitize($_POST['mac_address'] ?? ''))),
            'road_no'              => sanitize($_POST['road_no'] ?? ''),
            'house_no'             => sanitize($_POST['house_no'] ?? ''),
            'sub_zone'             => sanitize($_POST['sub_zone'] ?? ''),
            'box_no'               => sanitize($_POST['box_no'] ?? ''),
            'client_type'          => in_array($_POST['client_type'] ?? '', ['home','business','corporate','other']) ? $_POST['client_type'] : 'home',
            'thana'                => sanitize($_POST['thana'] ?? ''),
            'district'             => sanitize($_POST['district'] ?? ''),
            'device_name'          => sanitize($_POST['device_name'] ?? ''),
            'device_purchase_date' => !empty($_POST['device_purchase_date']) ? sanitize($_POST['device_purchase_date']) : null,
            'assigned_employee'    => sanitize($_POST['assigned_employee'] ?? ''),
        ];
        $this->db->update('customers', $data, 'id=?', [$id]);
        
        // Sync to Radius if profile or attributes changed
        if (!empty($old['pppoe_username'])) {
            $radiusProfile = !empty($data['mikrotik_profile']) ? $data['mikrotik_profile'] : ($old['mikrotik_profile'] ?? 'default');
            
            $this->db->update('radius_users', ['profile' => $radiusProfile], 'customer_id = ?', [$id]);
            
            if ($this->radius->isEnabled()) {
                $this->radius->assignGroup($old['pppoe_username'], $radiusProfile);
            }
        }

        $this->log('customer_updated', 'customers', $id, $old, $data);
        redirect(base_url("customers/view/{$id}"));
    }

    public function suspend(string $id): void {
        $reason = sanitize($_POST['reason'] ?? 'Non-payment');
        $old = $this->db->fetchOne("SELECT status FROM customers WHERE id=?", [$id]);
        $this->db->update('customers', ['status' => 'suspended'], 'id=?', [$id]);
        $this->db->insert('customer_status_log', [
            'customer_id' => $id, 'old_status' => $old['status'],
            'new_status' => 'suspended', 'reason' => $reason, 'changed_by' => $_SESSION['user_id']
        ]);
        redirect(base_url("customers/view/{$id}"));
    }

    public function reconnect(string $id): void {
        $old = $this->db->fetchOne("SELECT status FROM customers WHERE id=?", [$id]);
        $this->db->update('customers', ['status' => 'active'], 'id=?', [$id]);
        $this->db->insert('customer_status_log', [
            'customer_id' => $id, 'old_status' => $old['status'],
            'new_status' => 'active', 'reason' => 'Reconnection', 'changed_by' => $_SESSION['user_id']
        ]);
        redirect(base_url("customers/view/{$id}"));
    }

    private function generateCustomerCode(): string {
        $last = $this->db->fetchOne("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1");
        if ($last) {
            $num = (int)substr($last['customer_code'], -5) + 1;
        } else {
            $num = 1;
        }
        return 'ISP-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    private function handleFileUpload(string $field, int $customerId, string $folder): ?string {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
        $uploadDir = BASE_PATH . "/public/uploads/{$folder}/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext  = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $name = "{$customerId}_{$field}_" . time() . ".{$ext}";
        $dest = $uploadDir . $name;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
            $this->db->update('customers', [$field => "uploads/{$folder}/{$name}"], 'id=?', [$customerId]);
            return $name;
        }
        return null;
    }

    private function createInstallationWorkOrder(int $customerId, array $data): void {
        $num = 'WO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $this->db->insert('work_orders', [
            'wo_number'   => $num,
            'customer_id' => $customerId,
            'branch_id'   => $data['branch_id'],
            'zone_id'     => $data['zone_id'],
            'type'        => 'new_connection',
            'priority'    => 'normal',
            'title'       => 'New Connection Installation — ' . $data['full_name'],
            'description' => 'New customer connection installation. Address: ' . $data['address'],
            'address'     => $data['address'],
            'status'      => 'pending',
            'created_by'  => $_SESSION['user_id'],
        ]);
    }

    private function log(string $action, string $module, int $recordId, ?array $old, array $new): void {
        $this->db->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? null,
            'action'     => $action,
            'module'     => $module,
            'record_id'  => $recordId,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => json_encode($new),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }

    private function exportCsv(array $data): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=customers_export_' . date('Ymd_His') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Code', 'Full Name', 'Phone', 'Package', 'Zone', 'Address', 'Status', 'Due', 'Created At']);
        foreach ($data as $row) {
            fputcsv($output, [
                $row['id'], $row['customer_code'], $row['full_name'], $row['phone'],
                $row['package_name'], $row['zone_name'], $row['address'],
                $row['status'], $row['due_amount'], $row['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    private function exportPdf(array $data): void {
        $title = "Customer List - " . date('Y-m-d');
        echo "<html><head><title>$title</title><style>
            body{font-family:sans-serif;padding:20px;}
            table{width:100%;border-collapse:collapse;margin-top:20px;}
            th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px;}
            th{background:#f4f4f4;}
            h1{font-size:18px;}
            @media print { .no-print { display:none; } }
        </style></head><body>
        <div class='no-print' style='background:#fff9c4;padding:10px;margin-bottom:20px;border:1px solid #fbc02d;border-radius:4px;'>
            <strong>Print Preview:</strong> Use <code>Ctrl + P</code> (or Cmd + P) and select <strong>Save as PDF</strong>.
            <button onclick='window.print()' style='float:right;cursor:pointer;'>Print Now</button>
        </div>
        <h1>$title</h1>
        <table><thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Package</th><th>Zone</th><th>Status</th><th>Due</th></tr></thead><tbody>";
        foreach ($data as $row) {
            echo "<tr>
                <td>{$row['customer_code']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['package_name']}</td>
                <td>{$row['zone_name']}</td>
                <td>" . ucfirst($row['status']) . "</td>
                <td>" . number_format($row['due_amount'], 2) . "</td>
            </tr>";
        }
        echo "</tbody></table></body></html>";
        exit;
    }

    public function import(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['csv_file']['tmp_name'])) {
            $_SESSION['error'] = 'No file uploaded.';
            redirect(base_url('customers'));
            return;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $ext  = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            $_SESSION['error'] = 'Only CSV, XLSX, or XLS files are accepted.';
            redirect(base_url('customers'));
            return;
        }

        // ── Required columns (exact header names) ──────────────────────────
        $requiredCols = ['Client Name', 'Mobile'];

        // ── All recognised columns → DB field mapping ──────────────────────
        $colMap = [
            'ID/IP'              => 'pppoe_username',
            'C.Code'             => '_skip',           // we generate our own
            'Client Name'        => 'full_name',
            'Email'              => 'email',
            'Mobile'             => 'phone',
            'Road No'            => 'road_no',
            'House No'           => 'house_no',
            'NID'                => 'nid_number',
            'Zone'               => '_zone',
            'Sub Zone'           => 'sub_zone',
            'Box'                => 'box_no',
            'Address'            => 'address',
            'Package'            => '_package',
            'Server'             => '_nas',
            'Speed'              => '_skip',
            'Ex.Date'            => 'expiration',
            'Password'           => 'pppoe_password',
            'Conn. Type'         => 'connection_type',
            'Client Type'        => 'client_type',
            'M.Bill'             => 'monthly_charge',
            'MACAddress'         => 'mac_address',
            'Protocol'           => '_skip',
            'B.Status'           => '_status',
            'ClientJoiningDate'  => 'connection_date',
            'Remarks'            => 'notes',
            'Thana'              => 'thana',
            'District'           => 'district',
            'Device'             => 'device_name',
            'PurchaseDate'       => 'device_purchase_date',
            'AssignedEmployee'   => 'assigned_employee',
        ];

        // ── Parse file into rows ────────────────────────────────────────────
        $rows = [];
        if ($ext === 'csv') {
            $handle = fopen($file, 'r');
            $headers = fgetcsv($handle);
            if (!$headers) {
                $_SESSION['error'] = 'CSV file is empty or unreadable.';
                redirect(base_url('customers'));
                return;
            }
            // Trim BOM and whitespace from headers
            $headers = array_map(fn($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)), $headers);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($headers)) {
                    $rows[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        } else {
            // XLSX/XLS: parse manually (no external library — read as ZIP/XML)
            $rows = $this->parseXlsx($file);
            if ($rows === null) {
                $_SESSION['error'] = 'Could not parse XLSX file. Please use CSV format or ensure the file is a valid Excel file.';
                redirect(base_url('customers'));
                return;
            }
        }

        if (empty($rows)) {
            $_SESSION['error'] = 'The file contains no data rows.';
            redirect(base_url('customers'));
            return;
        }

        // ── Validate headers ────────────────────────────────────────────────
        $fileHeaders = array_keys($rows[0]);
        $missingRequired = [];
        foreach ($requiredCols as $req) {
            if (!in_array($req, $fileHeaders, true)) {
                $missingRequired[] = $req;
            }
        }
        if (!empty($missingRequired)) {
            $_SESSION['error'] = 'Missing required columns: ' . implode(', ', $missingRequired) . '. Please download the template and use the correct column headers.';
            redirect(base_url('customers'));
            return;
        }

        // ── Pre-load lookup tables ──────────────────────────────────────────
        $packages  = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_active=1");
        $zones     = $this->db->fetchAll("SELECT id, name FROM zones WHERE is_active=1");
        $nasDevices = $this->db->fetchAll("SELECT id, name FROM nas_devices WHERE is_active=1");
        $branch    = $this->db->fetchOne("SELECT id FROM branches WHERE is_active=1 LIMIT 1");
        $branchId  = $branch['id'] ?? 1;

        $pkgIndex  = [];
        foreach ($packages  as $p) $pkgIndex[strtolower(trim($p['name']))]  = $p['id'];
        $zoneIndex = [];
        foreach ($zones     as $z) $zoneIndex[strtolower(trim($z['name']))] = $z['id'];
        $nasIndex  = [];
        foreach ($nasDevices as $n) $nasIndex[strtolower(trim($n['name']))]  = $n['id'];

        // ── Process rows ────────────────────────────────────────────────────
        $imported  = 0;
        $skipped   = 0;
        $rejected  = [];

        foreach ($rows as $lineNo => $row) {
            $rowNum = $lineNo + 2; // +2 because line 1 = header

            // Skip completely empty rows
            $rowValues = array_filter(array_map('trim', $row));
            if (empty($rowValues)) { $skipped++; continue; }

            // ── Required field check ────────────────────────────────────────
            $fullName = trim($row['Client Name'] ?? '');
            $phone    = trim($row['Mobile'] ?? '');

            if (empty($fullName)) {
                $rejected[] = "Row {$rowNum}: Client Name is required.";
                continue;
            }
            if (empty($phone)) {
                $rejected[] = "Row {$rowNum} ({$fullName}): Mobile is required.";
                continue;
            }

            // ── Duplicate check: phone ──────────────────────────────────────
            $dupPhone = $this->db->fetchOne("SELECT id FROM customers WHERE phone=?", [$phone]);
            if ($dupPhone) {
                $rejected[] = "Row {$rowNum} ({$fullName}): Phone {$phone} already exists — skipped.";
                continue;
            }

            // ── Duplicate check: pppoe_username ─────────────────────────────
            $pppoeUser = trim($row['ID/IP'] ?? '');
            if (!empty($pppoeUser)) {
                $dupUser = $this->db->fetchOne("SELECT id FROM customers WHERE pppoe_username=?", [$pppoeUser]);
                if ($dupUser) {
                    $rejected[] = "Row {$rowNum} ({$fullName}): PPPoE username '{$pppoeUser}' already exists — skipped.";
                    continue;
                }
            }

            // ── Duplicate check: MAC address ────────────────────────────────
            $macRaw = strtoupper(trim($row['MACAddress'] ?? ''));
            if (!empty($macRaw)) {
                $dupMac = $this->db->fetchOne("SELECT id FROM customers WHERE mac_address=?", [$macRaw]);
                if ($dupMac) {
                    $rejected[] = "Row {$rowNum} ({$fullName}): MAC address '{$macRaw}' already exists — skipped.";
                    continue;
                }
            }

            // ── Resolve lookups ─────────────────────────────────────────────
            $pkgName  = strtolower(trim($row['Package'] ?? ''));
            $zoneName = strtolower(trim($row['Zone'] ?? ''));
            $nasName  = strtolower(trim($row['Server'] ?? ''));

            $packageId = $pkgIndex[$pkgName] ?? null;
            $zoneId    = $zoneIndex[$zoneName] ?? null;
            $nasId     = $nasIndex[$nasName] ?? null;

            // ── Parse status ────────────────────────────────────────────────
            $rawStatus = strtolower(trim($row['B.Status'] ?? 'active'));
            $status = match($rawStatus) {
                'active', '1', 'yes' => 'active',
                'inactive', 'suspended', '0', 'no' => 'suspended',
                default => 'active',
            };

            // ── Parse connection date ────────────────────────────────────────
            $rawDate = trim($row['ClientJoiningDate'] ?? '');
            $connDate = date('Y-m-d');
            if (!empty($rawDate)) {
                $ts = strtotime($rawDate);
                if ($ts !== false) $connDate = date('Y-m-d', $ts);
            }

            // ── Parse expiry date ────────────────────────────────────────────
            $rawExpiry = trim($row['Ex.Date'] ?? '');
            $expiry = null;
            if (!empty($rawExpiry)) {
                $ts = strtotime($rawExpiry);
                if ($ts !== false) $expiry = date('Y-m-d', $ts);
            }

            // ── Parse device purchase date ───────────────────────────────────
            $rawPurchase = trim($row['PurchaseDate'] ?? '');
            $purchaseDate = null;
            if (!empty($rawPurchase)) {
                $ts = strtotime($rawPurchase);
                if ($ts !== false) $purchaseDate = date('Y-m-d', $ts);
            }

            // ── Parse monthly bill ───────────────────────────────────────────
            $monthlyCharge = (float)preg_replace('/[^0-9.]/', '', $row['M.Bill'] ?? '0');

            // ── Parse connection type ────────────────────────────────────────
            $rawConnType = strtolower(trim($row['Conn. Type'] ?? 'pppoe'));
            $connType = in_array($rawConnType, ['pppoe','hotspot','static','cgnat']) ? $rawConnType : 'pppoe';

            // ── Parse client type ────────────────────────────────────────────
            $rawClientType = strtolower(trim($row['Client Type'] ?? 'home'));
            $clientType = in_array($rawClientType, ['home','business','corporate','other']) ? $rawClientType : 'home';

            // ── Build insert data ────────────────────────────────────────────
            $data = [
                'customer_code'        => $this->generateCustomerCode(),
                'branch_id'            => $branchId,
                'package_id'           => $packageId,
                'zone_id'              => $zoneId,
                'nas_id'               => $nasId,
                'full_name'            => sanitize($fullName),
                'phone'                => sanitize($phone),
                'email'                => sanitize(trim($row['Email'] ?? '')),
                'address'              => sanitize(trim($row['Address'] ?? '')),
                'nid_number'           => sanitize(trim($row['NID'] ?? '')),
                'pppoe_username'       => sanitize($pppoeUser),
                'pppoe_password'       => sanitize(trim($row['Password'] ?? '')),
                'connection_type'      => $connType,
                'client_type'          => $clientType,
                'mac_address'          => $macRaw,
                'road_no'              => sanitize(trim($row['Road No'] ?? '')),
                'house_no'             => sanitize(trim($row['House No'] ?? '')),
                'sub_zone'             => sanitize(trim($row['Sub Zone'] ?? '')),
                'box_no'               => sanitize(trim($row['Box'] ?? '')),
                'monthly_charge'       => $monthlyCharge,
                'status'               => $status,
                'connection_date'      => $connDate,
                'expiration'           => $expiry,
                'notes'                => sanitize(trim($row['Remarks'] ?? '')),
                'thana'                => sanitize(trim($row['Thana'] ?? '')),
                'district'             => sanitize(trim($row['District'] ?? '')),
                'device_name'          => sanitize(trim($row['Device'] ?? '')),
                'device_purchase_date' => $purchaseDate,
                'assigned_employee'    => sanitize(trim($row['AssignedEmployee'] ?? '')),
                'created_by'           => $_SESSION['user_id'] ?? 0,
            ];

            try {
                $this->db->insert('customers', $data);
                $imported++;
            } catch (\Exception $e) {
                $rejected[] = "Row {$rowNum} ({$fullName}): Database error — " . $e->getMessage();
            }
        }

        // ── Build result message ─────────────────────────────────────────────
        $msg = "Import complete: {$imported} imported";
        if ($skipped > 0)       $msg .= ", {$skipped} empty rows skipped";
        if (!empty($rejected))  $msg .= ", " . count($rejected) . " rejected";
        $msg .= '.';

        if (!empty($rejected)) {
            $_SESSION['import_errors'] = $rejected;
        }
        $_SESSION['success'] = $msg;
        redirect(base_url('customers'));
    }

    /**
     * Parse a simple XLSX file without external libraries.
     * Returns array of associative rows, or null on failure.
     */
    private function parseXlsx(string $filePath): ?array {
        if (!class_exists('ZipArchive')) return null;

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) return null;

        // Read shared strings
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $ss = simplexml_load_string($ssXml);
            if ($ss) {
                foreach ($ss->si as $si) {
                    // Concatenate all <t> elements (handles rich text)
                    $text = '';
                    foreach ($si->r ?? [$si] as $r) {
                        $text .= (string)($r->t ?? $r);
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // Read first sheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$sheetXml) return null;

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet) return null;

        $rawRows = [];
        foreach ($sheet->sheetData->row ?? [] as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $colLetter = preg_replace('/[0-9]/', '', (string)$cell['r']);
                $colIndex  = $this->xlsxColToIndex($colLetter);
                $type      = (string)($cell['t'] ?? '');
                $val       = (string)($cell->v ?? '');
                if ($type === 's') {
                    $val = $sharedStrings[(int)$val] ?? '';
                }
                $rowData[$colIndex] = $val;
            }
            if (!empty($rowData)) $rawRows[] = $rowData;
        }

        if (count($rawRows) < 2) return [];

        // First row = headers
        $headers = $rawRows[0];
        $maxCol  = max(array_keys($headers));
        $result  = [];
        for ($i = 1; $i < count($rawRows); $i++) {
            $assoc = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $header = trim($headers[$c] ?? '');
                $assoc[$header] = trim($rawRows[$i][$c] ?? '');
            }
            $result[] = $assoc;
        }
        return $result;
    }

    private function xlsxColToIndex(string $col): int {
        $col   = strtoupper($col);
        $index = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    /**
     * Download import template — generates CSV inline with all columns,
     * a rules row, and 3 sample data rows matching the Excel format.
     * GET /customers/download-template?type=csv|xlsx
     */
    public function downloadTemplate(): void {
        $type = strtolower(sanitize($_GET['type'] ?? 'csv'));

        // ── Column definitions ──────────────────────────────────────────────
        $columns = [
            'ID/IP', 'C.Code', 'Client Name', 'Email', 'Mobile',
            'Road No', 'House No', 'NID', 'Zone', 'Sub Zone', 'Box',
            'Address', 'Package', 'Server', 'Speed', 'Ex.Date',
            'Password', 'Conn. Type', 'Client Type', 'M.Bill',
            'MACAddress', 'Protocol', 'B.Status', 'ClientJoiningDate',
            'Remarks', 'Thana', 'District', 'Device', 'PurchaseDate',
            'AssignedEmployee',
        ];

        // ── Rules row (shown as row 2 in the file) ──────────────────────────
        $rules = [
            'PPPoE username or IP', 'Auto-generated (leave blank)', 'REQUIRED — Full name',
            'Optional email', 'REQUIRED — 01XXXXXXXXX', 'Optional', 'Optional',
            'National ID number', 'Zone name (must match system)', 'Sub-zone name',
            'Box/cabinet number', 'Installation address', 'Package name (must match system)',
            'NAS/Server name (must match system)', 'Auto from package', 'YYYY-MM-DD or MM/DD/YYYY',
            'PPPoE password', 'pppoe / hotspot / static', 'home / business / corporate',
            'Monthly bill amount (numbers only)', 'AA:BB:CC:DD:EE:FF format',
            'PPPOE / HOTSPOT', 'Active / Inactive', 'YYYY-MM-DD or MM/DD/YYYY',
            'Optional remarks', 'Thana/Upazila', 'District name',
            'Device model name', 'YYYY-MM-DD', 'Employee name',
        ];

        // ── Sample data rows ────────────────────────────────────────────────
        $samples = [
            [
                'sohel@majpara', '', 'Md. Sohel', 'sohel@gmail.com', '01795381737',
                '', '', '1234567890', 'Majpara', '', '',
                'Majpara Mor', 'Home Basic Plus', 'MR3-R1', '20', '2025-12-31',
                '12345', 'pppoe', 'home', '515.00',
                '30:16:9D:52:F3:48', 'PPPOE', 'Active', '2024-12-20',
                '', 'chapai-nawabgonj', 'chapai-nawabgonj', 'TP-Link', '2024-01-01', 'Rahim',
            ],
            [
                'bairul@batenkha', '', 'Md. Bairul Islam', '', '01716332903',
                '', '', '', 'Batenkha', '', '',
                'Batenkha Mor', 'Home Basic Plus', 'MR3-R1', '16', '',
                '12345', 'pppoe', 'home', '515.00',
                'B8:3A:08:A6:64:0F', 'PPPOE', 'Active', '2024-12-16',
                '', '', '', '', '', '',
            ],
            [
                'office', '', 'Office User', 'office@isp.com', '01856308066',
                '5', '12', '', 'Kharopara', '', '9',
                'Kharopara Office', 'Home Premium Plus', 'MR3-R1', '30', '2025-06-30',
                'office123', 'pppoe', 'business', '515.00',
                '04:95:E6:FB:B9:CF', 'PPPOE', 'Active', '2025-01-13',
                'Office connection', 'chapai-nawabgonj', 'chapai-nawabgonj', 'Mikrotik hAP', '2024-06-01', 'Karim',
            ],
        ];

        // ── Output CSV ──────────────────────────────────────────────────────
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customers_import_template.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, $columns);
        fputcsv($out, $rules);
        foreach ($samples as $sample) {
            fputcsv($out, $sample);
        }
        fclose($out);
        exit;
    }

    public function apiSearch(): void {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $q = sanitize($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode([]);
            return;
        }

        $sql = "SELECT id, customer_code, full_name, phone, pppoe_username, status
                FROM customers
                WHERE (full_name LIKE ? OR customer_code LIKE ? OR phone LIKE ? OR pppoe_username LIKE ?)
                AND status != 'deleted'
                LIMIT 15";
        $term = "%$q%";
        $results = $this->db->fetchAll($sql, [$term, $term, $term, $term]);

        echo json_encode($results);
    }

    // ── Bulk Operations for High-Volume Customer Management ───────────────────

    /**
     * Bulk create customers (optimized for 500+ clients)
     * @return void
     */
    public function bulkCreate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            redirect(base_url('customers'));
        }

        $customers = json_decode($_POST['customers'] ?? '[]', true);
        if (empty($customers)) {
            $_SESSION['error'] = 'No customer data provided.';
            redirect(base_url('customers'));
        }

        $success = 0;
        $errors = [];
        $batchSize = 50; // Process in batches to avoid timeout

        foreach (array_chunk($customers, $batchSize) as $batch) {
            foreach ($batch as $customerData) {
                try {
                    $data = [
                        'customer_code'   => $this->generateCustomerCode(),
                        'branch_id'       => (int)($customerData['branch_id'] ?? 1),
                        'nas_id'          => !empty($customerData['nas_id']) ? (int)$customerData['nas_id'] : null,
                        'zone_id'         => !empty($customerData['zone_id']) ? (int)$customerData['zone_id'] : null,
                        'package_id'      => !empty($customerData['package_id']) ? (int)$customerData['package_id'] : null,
                        'full_name'       => sanitize($customerData['full_name'] ?? ''),
                        'father_name'     => sanitize($customerData['father_name'] ?? ''),
                        'phone'           => sanitize($customerData['phone'] ?? ''),
                        'phone_alt'       => sanitize($customerData['phone_alt'] ?? ''),
                        'email'           => sanitize($customerData['email'] ?? ''),
                        'address'         => sanitize($customerData['address'] ?? ''),
                        'nid_number'      => sanitize($customerData['nid_number'] ?? ''),
                        'connection_type' => sanitize($customerData['connection_type'] ?? 'pppoe'),
                        'pppoe_username'  => sanitize($customerData['pppoe_username'] ?? ''),
                        'pppoe_password'  => !empty($customerData['pppoe_password']) ? sanitize($customerData['pppoe_password']) : null,
                        'mikrotik_profile' => sanitize($customerData['mikrotik_profile'] ?? ''),
                        'static_ip'       => sanitize($customerData['static_ip'] ?? ''),
                        'status'          => sanitize($customerData['status'] ?? 'pending'),
                        'connection_date' => $customerData['connection_date'] ?? date('Y-m-d'),
                        'monthly_charge'  => (float)($customerData['monthly_charge'] ?? 0),
                        'billing_day'     => (int)($customerData['billing_day'] ?? 1),
                        'notes'           => sanitize($customerData['notes'] ?? ''),
                        'created_by'      => $_SESSION['user_id'],
                    ];

                    if (empty($data['full_name']) || empty($data['phone'])) {
                        $errors[] = "Customer {$data['full_name']}: Name and phone are required.";
                        continue;
                    }

                    $id = $this->db->insert('customers', $data);

                    // Create Radius user if PPPoE
                    if (!empty($data['pppoe_username'])) {
                        $pkg = !empty($data['package_id']) ? $this->db->fetchOne("SELECT * FROM packages WHERE id=?", [$data['package_id']]) : null;
                        $radiusPassword = $data['pppoe_password'] ?? 'default123';
                        $radiusProfile  = !empty($data['mikrotik_profile']) ? $data['mikrotik_profile'] : ($pkg['radius_profile'] ?? 'default');

                        // Sync to local DB
                        $this->db->insert('radius_users', [
                            'customer_id' => $id,
                            'username'    => $data['pppoe_username'],
                            'password'    => $radiusPassword,
                            'profile'     => $radiusProfile,
                            'is_active'   => 1,
                        ]);

                        // Sync to Real FreeRADIUS
                        if ($this->radius->isEnabled()) {
                            $this->radius->addUser($data['pppoe_username'], $radiusPassword);
                            $this->radius->assignGroup($data['pppoe_username'], $radiusProfile);
                        }
                    }

                    // Create installation work order
                    $this->createInstallationWorkOrder($id, $data);

                    $success++;
                } catch (Exception $e) {
                    $errors[] = "Customer {$customerData['full_name']}: " . $e->getMessage();
                }
            }
        }

        $result = "Successfully created $success customers.";
        if (!empty($errors)) {
            $result .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $result .= " (and " . (count($errors) - 5) . " more)";
        }

        $_SESSION['success'] = $result;
        redirect(base_url('customers'));
    }

    /**
     * Bulk update customers
     * @return void
     */
    public function bulkUpdate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            redirect(base_url('customers'));
        }

        $updates = json_decode($_POST['updates'] ?? '[]', true);
        if (empty($updates)) {
            $_SESSION['error'] = 'No update data provided.';
            redirect(base_url('customers'));
        }

        $success = 0;
        $errors = [];
        $batchSize = 100;

        foreach (array_chunk($updates, $batchSize) as $batch) {
            foreach ($batch as $update) {
                try {
                    $id = (int)$update['id'];
                    $old = $this->db->fetchOne("SELECT * FROM customers WHERE id=?", [$id]);

                    if (!$old) {
                        $errors[] = "Customer ID $id not found.";
                        continue;
                    }

                    $data = [];
                    foreach (['package_id', 'zone_id', 'mikrotik_profile', 'monthly_charge', 'billing_day', 'status'] as $field) {
                        if (isset($update[$field])) {
                            $data[$field] = is_numeric($update[$field]) ? (float)$update[$field] : sanitize($update[$field]);
                        }
                    }

                    if (!empty($data)) {
                        $this->db->update('customers', $data, 'id=?', [$id]);

                        // Sync to Radius if profile changed
                        if (isset($data['mikrotik_profile']) && !empty($old['pppoe_username'])) {
                            $this->db->update('radius_users', ['profile' => $data['mikrotik_profile']], 'customer_id = ?', [$id]);
                            if ($this->radius->isEnabled()) {
                                $this->radius->assignGroup($old['pppoe_username'], $data['mikrotik_profile']);
                            }
                        }

                        $this->log('customer_updated', 'customers', $id, $old, $data);
                    }

                    $success++;
                } catch (Exception $e) {
                    $errors[] = "Customer ID {$update['id']}: " . $e->getMessage();
                }
            }
        }

        $result = "Successfully updated $success customers.";
        if (!empty($errors)) {
            $result .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        $_SESSION['success'] = $result;
        redirect(base_url('customers'));
    }

    /**
     * Bulk suspend customers
     * @return void
     */
    public function bulkSuspend(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            redirect(base_url('customers'));
        }

        $ids = json_decode($_POST['customer_ids'] ?? '[]', true);
        $reason = sanitize($_POST['reason'] ?? 'Bulk suspension');

        if (empty($ids)) {
            $_SESSION['error'] = 'No customer IDs provided.';
            redirect(base_url('customers'));
        }

        $success = 0;
        $errors = [];
        $batchSize = 200;

        foreach (array_chunk($ids, $batchSize) as $batch) {
            foreach ($batch as $id) {
                try {
                    $old = $this->db->fetchOne("SELECT status FROM customers WHERE id=?", [$id]);
                    if (!$old) {
                        $errors[] = "Customer ID $id not found.";
                        continue;
                    }

                    $this->db->update('customers', ['status' => 'suspended'], 'id=?', [$id]);
                    $this->db->insert('customer_status_log', [
                        'customer_id' => $id,
                        'old_status' => $old['status'],
                        'new_status' => 'suspended',
                        'reason' => $reason,
                        'changed_by' => $_SESSION['user_id']
                    ]);

                    $success++;
                } catch (Exception $e) {
                    $errors[] = "Customer ID $id: " . $e->getMessage();
                }
            }
        }

        $result = "Successfully suspended $success customers.";
        if (!empty($errors)) {
            $result .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        $_SESSION['success'] = $result;
        redirect(base_url('customers'));
    }

    /**
     * Bulk reconnect customers
     * @return void
     */
    public function bulkReconnect(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            redirect(base_url('customers'));
        }

        $ids = json_decode($_POST['customer_ids'] ?? '[]', true);

        if (empty($ids)) {
            $_SESSION['error'] = 'No customer IDs provided.';
            redirect(base_url('customers'));
        }

        $success = 0;
        $errors = [];
        $batchSize = 200;

        foreach (array_chunk($ids, $batchSize) as $batch) {
            foreach ($batch as $id) {
                try {
                    $old = $this->db->fetchOne("SELECT status FROM customers WHERE id=?", [$id]);
                    if (!$old) {
                        $errors[] = "Customer ID $id not found.";
                        continue;
                    }

                    $this->db->update('customers', ['status' => 'active'], 'id=?', [$id]);
                    $this->db->insert('customer_status_log', [
                        'customer_id' => $id,
                        'old_status' => $old['status'],
                        'new_status' => 'active',
                        'reason' => 'Bulk reconnection',
                        'changed_by' => $_SESSION['user_id']
                    ]);

                    $success++;
                } catch (Exception $e) {
                    $errors[] = "Customer ID $id: " . $e->getMessage();
                }
            }
        }

        $result = "Successfully reconnected $success customers.";
        if (!empty($errors)) {
            $result .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        $_SESSION['success'] = $result;
        redirect(base_url('customers'));
    }

    /**
     * Bulk delete customers (soft delete)
     * @return void
     */
    public function bulkDelete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            redirect(base_url('customers'));
        }

        $ids = json_decode($_POST['customer_ids'] ?? '[]', true);

        if (empty($ids)) {
            $_SESSION['error'] = 'No customer IDs provided.';
            redirect(base_url('customers'));
        }

        $success = 0;
        $errors = [];
        $batchSize = 100;

        foreach (array_chunk($ids, $batchSize) as $batch) {
            foreach ($batch as $id) {
                try {
                    $old = $this->db->fetchOne("SELECT * FROM customers WHERE id=?", [$id]);
                    if (!$old) {
                        $errors[] = "Customer ID $id not found.";
                        continue;
                    }

                    // Soft delete
                    $this->db->update('customers', ['status' => 'deleted'], 'id=?', [$id]);

                    // Log the deletion
                    $this->log('customer_deleted', 'customers', $id, $old, ['status' => 'deleted']);

                    $success++;
                } catch (Exception $e) {
                    $errors[] = "Customer ID $id: " . $e->getMessage();
                }
            }
        }

        $result = "Successfully deleted $success customers.";
        if (!empty($errors)) {
            $result .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }

        $_SESSION['success'] = $result;
        redirect(base_url('customers'));
    }

    /**
     * Delete a customer (soft delete)
     * @param string $id
     * @return void
     */
    public function delete(string $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            redirect(base_url('customers'));
        }

        $customer = $this->db->fetchOne("SELECT * FROM customers WHERE id=? AND status != 'deleted'", [$id]);
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            redirect(base_url('customers'));
        }

        try {
            // Soft delete - set status to deleted
            $this->db->update('customers', ['status' => 'deleted'], 'id=?', [$id]);

            // Log the deletion
            $this->log('customer_deleted', 'customers', $id, $customer, ['status' => 'deleted']);

            $_SESSION['success'] = 'Customer deleted successfully.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to delete customer: ' . $e->getMessage();
        }

        redirect(base_url('customers'));
    }

    /**
     * Get customer statistics for dashboard
     * @return void
     */
    public function getStatistics(): void {
        header('Content-Type: application/json');

        $stats = $this->db->fetchOne("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN connection_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_last_30_days
            FROM customers
            WHERE status != 'deleted'
        ");

        // Get package distribution
        $packages = $this->db->fetchAll("
            SELECT p.name, COUNT(c.id) as count
            FROM packages p
            LEFT JOIN customers c ON c.package_id = p.id AND c.status = 'active'
            GROUP BY p.id, p.name
            ORDER BY count DESC
            LIMIT 10
        ");

        // Get zone distribution
        $zones = $this->db->fetchAll("
            SELECT z.name, COUNT(c.id) as count
            FROM zones z
            LEFT JOIN customers c ON c.zone_id = z.id AND c.status = 'active'
            GROUP BY z.id, z.name
            ORDER BY count DESC
            LIMIT 10
        ");

        echo json_encode([
            'total_customers' => (int)$stats['total'],
            'active_customers' => (int)$stats['active'],
            'suspended_customers' => (int)$stats['suspended'],
            'pending_customers' => (int)$stats['pending'],
            'new_customers_30d' => (int)$stats['new_last_30_days'],
            'package_distribution' => $packages,
            'zone_distribution' => $zones
        ]);
    }
}
