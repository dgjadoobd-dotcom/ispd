<?php

/**
 * SalesInvoiceService — Business logic for Sales & Service Invoicing module.
 *
 * Covers Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7:
 *   - Invoice CRUD with multi-line items
 *   - Invoice number generation (SI-YYYY-NNNN)
 *   - Partial payment processing
 *   - Invoice cancellation with payment reversal
 *   - Payment recording and tracking
 *
 * @see database/migrations/2024_01_03_001_create_all_new_modules_tables.sql
 */
class SalesInvoiceService extends BaseService
{
    private const INVOICE_PREFIX = 'SI-';
    private const PAYMENT_STATUSES = ['unpaid', 'partial', 'paid', 'cancelled'];
    private const INVOICE_TYPES = ['installation', 'product', 'service'];
    private const PAYMENT_METHODS = ['cash', 'mobile_banking', 'bank_transfer', 'online', 'other'];

    public function generateInvoiceNumber(): string
    {
        $prefix = Database::getInstance()->fetchOne(
            "SELECT value FROM configuration_settings WHERE setting_key = 'sales_invoice_prefix'",
            []
        )['value'] ?? self::INVOICE_PREFIX;

        $year = date('Y');
        $seqKey = "sales_invoice_seq_{$year}";

        $seqRow = $this->db->fetchOne(
            "SELECT value FROM configuration_settings WHERE setting_key = ?",
            [$seqKey]
        );

        if ($seqRow) {
            $nextSeq = (int)$seqRow['value'] + 1;
        } else {
            $nextSeq = 1;
            $this->db->insert('configuration_settings', [
                'setting_key' => $seqKey,
                'value'     => '1',
                'type'     => 'number',
                'category' => 'sales',
            ]);
        }

        $this->db->update('configuration_settings', [
            'value' => (string)$nextSeq,
        ], 'setting_key = ?', [$seqKey]);

        return $prefix . $year . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function getInvoices(array $filters = []): array
    {
        $page  = max(1, (int)($filters['page']  ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $params     = [];
        $conditions = [];

        if (!empty($filters['status'])) {
            $conditions[] = "i.payment_status = ?";
            $params[]    = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $conditions[] = "i.invoice_type = ?";
            $params[]   = $filters['type'];
        }
        if (!empty($filters['branch_id'])) {
            $conditions[] = "i.branch_id = ?";
            $params[]    = (int)$filters['branch_id'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = "(i.invoice_number LIKE ? OR c.full_name LIKE ? OR c.customer_code LIKE ?)";
            $like        = '%' . $filters['search'] . '%';
            $params[]   = $like;
            $params[]   = $like;
            $params[]   = $like;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $countRow = $this->db->fetchOne(
                "SELECT COUNT(*) AS total FROM sales_invoices i
                 LEFT JOIN customers c ON c.id = i.customer_id{$where}",
                $params
            );
            $total = (int)($countRow['total'] ?? 0);

            $sql = "SELECT i.*, c.full_name AS customer_name, c.customer_code, c.phone AS customer_phone,
                          b.name AS branch_name, u.full_name AS created_by_name
                   FROM sales_invoices i
                   LEFT JOIN customers c ON c.id = i.customer_id
                   LEFT JOIN branches b ON b.id = i.branch_id
                   LEFT JOIN users u ON u.id = i.created_by
                   {$where}
                   ORDER BY i.created_at DESC
                   LIMIT {$limit} OFFSET {$offset}";

            $data       = $this->db->fetchAll($sql, $params);
            $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

            return [
                'data'       => $data,
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $limit,
                'totalPages' => $totalPages,
                'hasNext'    => $page < $totalPages,
                'hasPrev'    => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError('getInvoices failed', $e, $filters);
            return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        }
    }

    public function getInvoice(int $id): ?array
    {
        try {
            $invoice = $this->db->fetchOne(
                "SELECT i.*, c.full_name AS customer_name, c.customer_code, c.phone AS customer_phone,
                        c.address AS customer_address, b.name AS branch_name, u.full_name AS created_by_name
                 FROM sales_invoices i
                 LEFT JOIN customers c ON c.id = i.customer_id
                 LEFT JOIN branches b ON b.id = i.branch_id
                 LEFT JOIN users u ON u.id = i.created_by
                 WHERE i.id = ? LIMIT 1",
                [$id]
            );

            if (!$invoice) {
                return null;
            }

            $invoice['items'] = $this->db->fetchAll(
                "SELECT * FROM sales_invoice_items WHERE invoice_id = ? ORDER BY id ASC",
                [$id]
            );

            return $invoice;
        } catch (\Throwable $e) {
            $this->logError('getInvoice failed', $e, ['id' => $id]);
            return null;
        }
    }

    public function createInvoice(array $data): int
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new \RuntimeException("At least one line item is required.");
        }

        $invoiceNumber = $this->generateInvoiceNumber();
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['unit_price'] ?? 0);
            $item['line_total'] = $qty * $price;
            $subtotal += $item['line_total'];
        }

        $discount = (float)($data['discount'] ?? 0);
        $vat = ($subtotal - $discount) * 0.15;
        $total = $subtotal - $discount + $vat;
        $otcAmount = (float)($data['otc_amount'] ?? 0);
        $total += $otcAmount;

        $invoiceId = $this->db->insert('sales_invoices', [
            'invoice_number'  => $invoiceNumber,
            'customer_id'     => (int)$data['customer_id'],
            'branch_id'       => (int)$data['branch_id'],
            'invoice_type'    => in_array($data['invoice_type'] ?? 'service', self::INVOICE_TYPES) ? $data['invoice_type'] : 'service',
            'subtotal'        => $subtotal,
            'discount'       => $discount,
            'vat'            => $vat,
            'total'          => $total,
            'paid_amount'    => 0,
            'due_amount'     => $total,
            'payment_status' => 'unpaid',
            'connection_date' => !empty($data['connection_date']) ? $data['connection_date'] : null,
            'otc_amount'     => $otcAmount,
            'notes'          => $data['notes'] ?? '',
            'created_by'      => (int)($data['created_by'] ?? 0) ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        foreach ($items as $item) {
            $this->db->insert('sales_invoice_items', [
                'invoice_id'  => $invoiceId,
                'description' => $item['description'] ?? '',
                'quantity'   => (float)($item['quantity'] ?? 1),
                'unit_price'  => (float)($item['unit_price'] ?? 0),
                'line_total'  => (float)($item['line_total'] ?? 0),
            ]);
        }

        return $invoiceId;
    }

    public function updateInvoice(int $id, array $data): void
    {
        $invoice = $this->getInvoice($id);
        if (!$invoice) {
            throw new \RuntimeException("Invoice #{$id} not found.");
        }

        if ($invoice['payment_status'] === 'paid' || $invoice['payment_status'] === 'cancelled') {
            throw new \RuntimeException("Cannot edit a paid or cancelled invoice.");
        }

        $items = $data['items'] ?? [];
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['unit_price'] ?? 0);
            $item['line_total'] = $qty * $price;
            $subtotal += $item['line_total'];
        }

        $discount = (float)($data['discount'] ?? 0);
        $vat = ($subtotal - $discount) * 0.15;
        $total = $subtotal - $discount + $vat;
        $otcAmount = (float)($data['otc_amount'] ?? 0);
        $total += $otcAmount;
        $paidAmount = (float)$invoice['paid_amount'];
        $dueAmount = $total - $paidAmount;

        $this->db->update('sales_invoices', [
            'invoice_type'   => $data['invoice_type'] ?? 'service',
            'subtotal'     => $subtotal,
            'discount'    => $discount,
            'vat'         => $vat,
            'total'       => $total,
            'due_amount'  => $dueAmount,
            'notes'       => $data['notes'] ?? '',
            'connection_date' => !empty($data['connection_date']) ? $data['connection_date'] : null,
            'otc_amount'  => $otcAmount,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->db->delete('sales_invoice_items', 'invoice_id = ?', [$id]);

        foreach ($items as $item) {
            $this->db->insert('sales_invoice_items', [
                'invoice_id'  => $id,
                'description' => $item['description'] ?? '',
                'quantity'   => (float)($item['quantity'] ?? 1),
                'unit_price'  => (float)($item['unit_price'] ?? 0),
                'line_total'  => (float)($item['line_total'] ?? 0),
            ]);
        }
    }

    public function recordPayment(int $invoiceId, float $amount, string $method, string $reference, string $notes): void
    {
        $invoice = $this->getInvoice($invoiceId);
        if (!$invoice) {
            throw new \RuntimeException("Invoice #{$invoiceId} not found.");
        }

        if ($invoice['payment_status'] === 'cancelled') {
            throw new \RuntimeException("Cannot record payment for a cancelled invoice.");
        }

        $customerId = $invoice['customer_id'];
        $branchId = $invoice['branch_id'];
        $collectedBy = $_SESSION['user_id'] ?? null;

        $this->db->insert('sales_payments', [
            'invoice_id'    => $invoiceId,
            'customer_id' => $customerId,
            'branch_id'  => $branchId,
            'amount'     => $amount,
            'method'    => $method,
            'reference' => $reference,
            'notes'     => $notes,
            'collected_by' => $collectedBy,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $newPaidAmount = $invoice['paid_amount'] + $amount;
        $total = $invoice['total'];
        $dueAmount = $total - $newPaidAmount;

        $newStatus = 'unpaid';
        if ($newPaidAmount >= $total) {
            $newStatus = 'paid';
            $dueAmount = 0;
        } elseif ($newPaidAmount > 0) {
            $newStatus = 'partial';
        }

        $this->db->update('sales_invoices', [
            'paid_amount'   => $newPaidAmount,
            'due_amount'  => $dueAmount,
            'payment_status' => $newStatus,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$invoiceId]);
    }

    public function cancelInvoice(int $id, string $reason): void
    {
        $invoice = $this->getInvoice($id);
        if (!$invoice) {
            throw new \RuntimeException("Invoice #{$id} not found.");
        }

        if ($invoice['payment_status'] === 'cancelled') {
            throw new \RuntimeException("Invoice is already cancelled.");
        }

        if ($invoice['payment_status'] === 'paid' || $invoice['paid_amount'] > 0) {
            $this->db->update('sales_payments', [
                'notes' => 'CANCELLED - ' . $reason,
            ], 'invoice_id = ? AND id IS NOT NULL', [$id]);
        }

        $this->db->update('sales_invoices', [
            'payment_status' => 'cancelled',
            'notes'       => ($invoice['notes'] ?? '') . "\n[CANCELLED: {$reason}]",
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public function getInvoicePayments(int $invoiceId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT p.*, u.full_name AS collected_by_name
                 FROM sales_payments p
                 LEFT JOIN users u ON u.id = p.collected_by
                 WHERE p.invoice_id = ?
                 ORDER BY p.created_at DESC",
                [$invoiceId]
            );
        } catch (\Throwable $e) {
            $this->logError('getInvoicePayments failed', $e);
            return [];
        }
    }

    public function getPayments(array $filters = []): array
    {
        $page  = max(1, (int)($filters['page']  ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $params     = [];
        $conditions = [];

        if (!empty($filters['branch_id'])) {
            $conditions[] = "p.branch_id = ?";
            $params[]    = (int)$filters['branch_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(p.created_at) >= ?";
            $params[]    = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(p.created_at) <= ?";
            $params[]   = $filters['date_to'];
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $countRow = $this->db->fetchOne(
                "SELECT COUNT(*) AS total FROM sales_payments p{$where}",
                $params
            );
            $total = (int)($countRow['total'] ?? 0);

            $sql = "SELECT p.*, i.invoice_number, c.full_name AS customer_name, u.full_name AS collected_by_name
                   FROM sales_payments p
                   LEFT JOIN sales_invoices i ON i.id = p.invoice_id
                   LEFT JOIN customers c ON c.id = p.customer_id
                   LEFT JOIN users u ON u.id = p.collected_by
                   {$where}
                   ORDER BY p.created_at DESC
                   LIMIT {$limit} OFFSET {$offset}";

            $data = $this->db->fetchAll($sql, $params);
            $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

            return [
                'data'        => $data,
                'total'       => $total,
                'page'       => $page,
                'perPage'    => $limit,
                'totalPages'  => $totalPages,
                'hasNext'    => $page < $totalPages,
                'hasPrev'    => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError('getPayments failed', $e, $filters);
            return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        }
    }

    public function getActiveCustomers(?int $branchId = null): array
    {
        $params = [];
        $sql = "SELECT id, full_name, customer_code, phone, address, zone_id
               FROM customers WHERE status = 'active'";

        if ($branchId !== null) {
            $sql .= " AND branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY full_name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getActiveCustomers failed', $e);
            return [];
        }
    }

    public function getSalesReport(?int $branchId = null): array
    {
        $params = $branchId !== null ? [$branchId] : [];
        $filter = $branchId !== null ? ' AND i.branch_id = ?' : '';

        try {
            $summary = $this->db->fetchOne(
                "SELECT
                     COUNT(*) AS total_invoices,
                     SUM(i.total) AS total_amount,
                     SUM(i.paid_amount) AS total_paid,
                     SUM(i.due_amount) AS total_due,
                     SUM(CASE WHEN i.payment_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
                     SUM(CASE WHEN i.payment_status = 'partial' THEN 1 ELSE 0 END) AS partial_count,
                     SUM(CASE WHEN i.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count
                  FROM sales_invoices i
                  WHERE i.payment_status != 'cancelled'{$filter}",
                $params
            ) ?? [];

            $byType = $this->db->fetchAll(
                "SELECT
                     i.invoice_type,
                     COUNT(*) AS count,
                     SUM(i.total) AS total
                  FROM sales_invoices i
                  WHERE i.payment_status != 'cancelled'{$filter}
                  GROUP BY i.invoice_type",
                $params
            );

            $recentInvoices = $this->db->fetchAll(
                "SELECT i.id, i.invoice_number, i.total, i.paid_amount, i.due_amount,
                        i.payment_status, i.created_at, c.full_name AS customer_name
                 FROM sales_invoices i
                 LEFT JOIN customers c ON c.id = i.customer_id
                 WHERE i.payment_status != 'cancelled'{$filter}
                 ORDER BY i.created_at DESC
                 LIMIT 10",
                $params
            );

            return [
                'summary'       => $summary,
                'by_type'       => $byType,
                'recent_invoices' => $recentInvoices,
            ];
        } catch (\Throwable $e) {
            $this->logError('getSalesReport failed', $e);
            return ['summary' => [], 'by_type' => [], 'recent_invoices' => []];
        }
    }
}