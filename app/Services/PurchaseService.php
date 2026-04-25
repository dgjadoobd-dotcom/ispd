<?php

/**
 * PurchaseService — Business logic for Purchase Management module.
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8
 *   - Vendor management with validation
 *   - Purchase bill recording
 *   - Vendor payment tracking
 *   - Vendor ledger reports
 */
class PurchaseService extends BaseService
{
    private const BILL_PREFIX = 'PB-';
    private const STATUSES = ['pending', 'partial', 'paid', 'cancelled'];
    private const PAYMENT_METHODS = ['cash', 'mobile_banking', 'bank_transfer', 'online', 'other'];

    public function getVendors(array $filters = []): array
    {
        $page  = max(1, (int)($filters['page']  ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $params = [];
        $conditions = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $countRow = $this->db->fetchOne("SELECT COUNT(*) AS total FROM vendors{$where}", $params);
            $total = (int)($countRow['total'] ?? 0);

            $sql = "SELECT v.*, b.name AS branch_name, u.full_name AS created_by_name
                   FROM vendors v
                   LEFT JOIN branches b ON b.id = v.branch_id
                   LEFT JOIN users u ON u.id = v.created_by
                   {$where}
                   ORDER BY v.name ASC
                   LIMIT {$limit} OFFSET {$offset}";

            $data = $this->db->fetchAll($sql, $params);
            $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'perPage' => $limit,
                'totalPages' => $totalPages,
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError('getVendors failed', $e);
            return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        }
    }

    public function getVendor(int $id): ?array
    {
        try {
            return $this->db->fetchOne(
                "SELECT v.*, b.name AS branch_name FROM vendors v LEFT JOIN branches b ON b.id = v.branch_id WHERE v.id = ? LIMIT 1",
                [$id]
            );
        } catch (\Throwable $e) {
            $this->logError('getVendor failed', $e, ['id' => $id]);
            return null;
        }
    }

    public function createVendor(array $data): int
    {
        return $this->create('vendors', [
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'address' => $data['address'] ?? '',
            'branch_id' => (int)($data['branch_id'] ?? 0) ?: null,
            'created_by' => (int)($data['created_by'] ?? 0) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateVendor(int $id, array $data): void
    {
        $this->update('vendors', $id, [
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'address' => $data['address'] ?? '',
        ]);
    }

    public function getActiveVendors(?int $branchId = null): array
    {
        $params = [];
        $sql = "SELECT id, name, phone, email FROM vendors WHERE is_active = 1";
        if ($branchId !== null) {
            $sql .= " AND branch_id = ?";
            $params[] = $branchId;
        }
        $sql .= " ORDER BY name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getActiveVendors failed', $e);
            return [];
        }
    }

    public function getBills(array $filters = []): array
    {
        $page  = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $params = [];
        $conditions = [];

        if (!empty($filters['vendor_id'])) {
            $conditions[] = "pb.vendor_id = ?";
            $params[] = (int)$filters['vendor_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = "pb.payment_status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['branch_id'])) {
            $conditions[] = "pb.branch_id = ?";
            $params[] = (int)$filters['branch_id'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = "(pb.bill_number LIKE ? OR v.name LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $countRow = $this->db->fetchOne("SELECT COUNT(*) AS total FROM purchase_bills pb LEFT JOIN vendors v ON v.id = pb.vendor_id{$where}", $params);
            $total = (int)($countRow['total'] ?? 0);

            $sql = "SELECT pb.*, v.name AS vendor_name, b.name AS branch_name, u.full_name AS created_by_name
                   FROM purchase_bills pb
                   LEFT JOIN vendors v ON v.id = pb.vendor_id
                   LEFT JOIN branches b ON b.id = pb.branch_id
                   LEFT JOIN users u ON u.id = pb.created_by
                   {$where}
                   ORDER BY pb.created_at DESC
                   LIMIT {$limit} OFFSET {$offset}";

            $data = $this->db->fetchAll($sql, $params);
            $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'perPage' => $limit,
                'totalPages' => $totalPages,
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError('getBills failed', $e);
            return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        }
    }

    public function getBill(int $id): ?array
    {
        try {
            $bill = $this->db->fetchOne(
                "SELECT pb.*, v.name AS vendor_name, v.phone AS vendor_phone, b.name AS branch_name, u.full_name AS created_by_name
                 FROM purchase_bills pb
                 LEFT JOIN vendors v ON v.id = pb.vendor_id
                 LEFT JOIN branches b ON b.id = pb.branch_id
                 LEFT JOIN users u ON u.id = pb.created_by
                 WHERE pb.id = ? LIMIT 1",
                [$id]
            );

            if ($bill) {
                $bill['items'] = $this->db->fetchAll(
                    "SELECT * FROM purchase_bill_items WHERE bill_id = ? ORDER BY id ASC",
                    [$id]
                );
            }
            return $bill;
        } catch (\Throwable $e) {
            $this->logError('getBill failed', $e, ['id' => $id]);
            return null;
        }
    }

    public function createBill(array $data): int
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new \RuntimeException("At least one line item is required.");
        }

        $billNumber = $this->generateBillNumber();
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['unit_price'] ?? 0);
            $item['line_total'] = $qty * $price;
            $subtotal += $item['line_total'];
        }

        $discount = (float)($data['discount'] ?? 0);
        $total = $subtotal - $discount;

        $billId = $this->db->insert('purchase_bills', [
            'bill_number' => $billNumber,
            'vendor_id' => (int)$data['vendor_id'],
            'branch_id' => (int)($data['branch_id'] ?? 0) ?: null,
            'items' => json_encode($items),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'paid_amount' => 0,
            'due_amount' => $total,
            'payment_status' => 'pending',
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? '',
            'created_by' => (int)($data['created_by'] ?? 0) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $billId;
    }

    public function recordPayment(int $billId, float $amount, string $method, string $reference, string $notes): void
    {
        $bill = $this->getBill($billId);
        if (!$bill) {
            throw new \RuntimeException("Bill #{$billId} not found.");
        }

        if ($bill['payment_status'] === 'cancelled') {
            throw new \RuntimeException("Cannot record payment for a cancelled bill.");
        }

        $this->db->insert('vendor_payments', [
            'bill_id' => $billId,
            'vendor_id' => $bill['vendor_id'],
            'branch_id' => $bill['branch_id'],
            'amount' => $amount,
            'payment_method' => $method,
            'reference' => $reference,
            'notes' => $notes,
            'collected_by' => $_SESSION['user_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $newPaid = $bill['paid_amount'] + $amount;
        $dueAmount = $bill['total'] - $newPaid;
        $newStatus = 'pending';
        if ($newPaid >= $bill['total']) {
            $newStatus = 'paid';
            $dueAmount = 0;
        } elseif ($newPaid > 0) {
            $newStatus = 'partial';
        }

        $this->db->update('purchase_bills', [
            'paid_amount' => $newPaid,
            'due_amount' => $dueAmount,
            'payment_status' => $newStatus,
        ], 'id = ?', [$billId]);
    }

    public function getBillPayments(int $billId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT vp.*, u.full_name AS collected_by_name
                 FROM vendor_payments vp
                 LEFT JOIN users u ON u.id = vp.collected_by
                 WHERE vp.bill_id = ?
                 ORDER BY vp.created_at DESC",
                [$billId]
            );
        } catch (\Throwable $e) {
            $this->logError('getBillPayments failed', $e);
            return [];
        }
    }

    public function getVendorLedger(int $vendorId): array
    {
        try {
            $vendor = $this->getVendor($vendorId);
            $bills = $this->db->fetchAll(
                "SELECT * FROM purchase_bills WHERE vendor_id = ? ORDER BY created_at DESC",
                [$vendorId]
            );
            $payments = $this->db->fetchAll(
                "SELECT vp.*, pb.bill_number FROM vendor_payments vp LEFT JOIN purchase_bills pb ON pb.id = vp.bill_id WHERE vp.vendor_id = ? ORDER BY vp.created_at DESC",
                [$vendorId]
            );

            $totalBills = 0;
            $totalPaid = 0;
            foreach ($bills as $b) {
                $totalBills += $b['total'];
                $totalPaid += $b['paid_amount'];
            }

            return [
                'vendor' => $vendor,
                'bills' => $bills,
                'payments' => $payments,
                'summary' => [
                    'total_bills' => $totalBills,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalBills - $totalPaid,
                ],
            ];
        } catch (\Throwable $e) {
            $this->logError('getVendorLedger failed', $e);
            return ['vendor' => null, 'bills' => [], 'payments' => [], 'summary' => []];
        }
    }

    public function getPurchaseReport(?int $branchId = null): array
    {
        $params = $branchId !== null ? [$branchId] : [];
        $filter = $branchId !== null ? ' WHERE pb.branch_id = ?' : '';

        try {
            $summary = $this->db->fetchOne(
                "SELECT
                     COUNT(*) AS total_bills,
                     SUM(pb.total) AS total_amount,
                     SUM(pb.paid_amount) AS total_paid,
                     SUM(pb.due_amount) AS total_due,
                     SUM(CASE WHEN pb.payment_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                     SUM(CASE WHEN pb.payment_status = 'partial' THEN 1 ELSE 0 END) AS partial_count,
                     SUM(CASE WHEN pb.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count
                  FROM purchase_bills pb{$filter}",
                $params
            ) ?? [];

            $byVendor = $this->db->fetchAll(
                "SELECT v.name, COUNT(pb.id) AS bill_count, SUM(pb.total) AS total
                 FROM purchase_bills pb
                 LEFT JOIN vendors v ON v.id = pb.vendor_id
                 {$filter}
                 GROUP BY pb.vendor_id, v.name
                 ORDER BY total DESC",
                $params
            );

            return [
                'summary' => $summary,
                'by_vendor' => $byVendor,
            ];
        } catch (\Throwable $e) {
            $this->logError('getPurchaseReport failed', $e);
            return ['summary' => [], 'by_vendor' => []];
        }
    }

    private function generateBillNumber(): string
    {
        $year = date('Y');
        $seqKey = "purchase_bill_seq_{$year}";

        $seqRow = $this->db->fetchOne("SELECT value FROM configuration_settings WHERE setting_key = ?", [$seqKey]);
        $nextSeq = $seqRow ? (int)$seqRow['value'] + 1 : 1;

        if (!$seqRow) {
            $this->db->insert('configuration_settings', [
                'setting_key' => $seqKey,
                'value' => (string)$nextSeq,
                'type' => 'number',
                'category' => 'purchase',
            ]);
        } else {
            $this->db->update('configuration_settings', ['value' => (string)$nextSeq], 'setting_key = ?', [$seqKey]);
        }

        return self::BILL_PREFIX . $year . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}