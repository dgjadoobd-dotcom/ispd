<?php

/**
 * BandwidthService — Business logic for Bandwidth Purchase & Sales module.
 *
 * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8
 */
class BandwidthService extends BaseService
{
    public function getProviders(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        try {
            $row = $this->db->fetchOne("SELECT COUNT(*) AS t FROM bandwidth_providers", []);
            $total = (int)($row['t'] ?? 0);
            $data = $this->db->fetchAll("SELECT * FROM bandwidth_providers ORDER BY name LIMIT {$limit} OFFSET {$offset}", []);
            return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $limit, 'totalPages' => max(1, ceil($total / $limit)), 'hasNext' => $page < ceil($total / $limit), 'hasPrev' => $page > 1];
        } catch (\Throwable $e) { $this->logError('getProviders', $e); return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false]; }
    }

    public function getProvider(int $id): ?array
    {
        try { return $this->db->fetchOne("SELECT * FROM bandwidth_providers WHERE id = ?", [$id]); }
        catch (\Throwable $e) { $this->logError('getProvider', $e, ['id' => $id]); return null; }
    }

    public function createProvider(array $data): int
    {
        return $this->create('bandwidth_providers', ['name' => $data['name'], 'contact_person' => $data['contact_person'] ?? '', 'phone' => $data['phone'] ?? '', 'email' => $data['email'] ?? '', 'address' => $data['address'] ?? '', 'bandwidth_capacity' => $data['bandwidth_capacity'] ?? 0, 'price_per_mbps' => $data['price_per_mbps'] ?? 0, 'created_by' => $data['created_by'], 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function updateProvider(int $id, array $data): void
    {
        $this->update('bandwidth_providers', $id, ['name' => $data['name'], 'contact_person' => $data['contact_person'] ?? '', 'phone' => $data['phone'] ?? '', 'email' => $data['email'] ?? '', 'address' => $data['address'] ?? '', 'bandwidth_capacity' => $data['bandwidth_capacity'] ?? 0, 'price_per_mbps' => $data['price_per_mbps'] ?? 0]);
    }

    public function getActiveProviders(): array
    {
        try { return $this->db->fetchAll("SELECT id, name FROM bandwidth_providers WHERE is_active = 1 ORDER BY name", []); }
        catch (\Throwable $e) { return []; }
    }

    public function getResellers(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $params = [];

        if (!empty($filters['provider_id'])) {
            $params[] = (int)$filters['provider_id'];
            $where = " WHERE provider_id = ?";
        } else { $where = ""; }

        try {
            $row = $this->db->fetchOne("SELECT COUNT(*) AS t FROM bandwidth_resellers" . ($where ? " {$where}" : ""), $params);
            $total = (int)($row['t'] ?? 0);
            $sql = "SELECT br.*, bp.name AS provider_name FROM bandwidth_resellers br LEFT JOIN bandwidth_providers bp ON bp.id = br.provider_id" . ($where ? " {$where}" : "") . " ORDER BY br.name LIMIT {$limit} OFFSET {$offset}";
            $data = $this->db->fetchAll($sql, $params);
            return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $limit, 'totalPages' => max(1, ceil($total / $limit)), 'hasNext' => $page < ceil($total / $limit), 'hasPrev' => $page > 1];
        } catch (\Throwable $e) { $this->logError('getResellers', $e); return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false]; }
    }

    public function getReseller(int $id): ?array
    {
        try { return $this->db->fetchOne("SELECT br.*, bp.name AS provider_name FROM bandwidth_resellers br LEFT JOIN bandwidth_providers bp ON bp.id = br.provider_id WHERE br.id = ?", [$id]); }
        catch (\Throwable $e) { return null; }
    }

    public function createReseller(array $data): int
    {
        return $this->create('bandwidth_resellers', ['provider_id' => $data['provider_id'], 'name' => $data['name'], 'contact_person' => $data['contact_person'] ?? '', 'phone' => $data['phone'] ?? '', 'email' => $data['email'] ?? '', 'address' => $data['address'] ?? '', 'credit_limit' => $data['credit_limit'] ?? 0, 'price_per_mbps' => $data['price_per_mbps'] ?? 0, 'is_active' => 1, 'created_by' => $data['created_by'], 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function updateReseller(int $id, array $data): void
    {
        $this->update('bandwidth_resellers', $id, ['provider_id' => $data['provider_id'], 'name' => $data['name'], 'contact_person' => $data['contact_person'] ?? '', 'phone' => $data['phone'] ?? '', 'email' => $data['email'] ?? '', 'address' => $data['address'] ?? '', 'credit_limit' => $data['credit_limit'] ?? 0, 'price_per_mbps' => $data['price_per_mbps'] ?? 0]);
    }

    public function getPurchases(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));

        try {
            $row = $this->db->fetchOne("SELECT COUNT(*) AS t FROM bandwidth_purchases", []);
            $total = (int)($row['t'] ?? 0);
            $data = $this->db->fetchAll("SELECT bp.*, p.name AS provider_name FROM bandwidth_purchases bp LEFT JOIN bandwidth_providers p ON p.id = bp.provider_id ORDER BY bp.created_at DESC LIMIT {$limit} OFFSET " . (($page - 1) * $limit), []);
            return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $limit, 'totalPages' => max(1, ceil($total / $limit))];
        } catch (\Throwable $e) { return ['data' => [], 'total' => 0]; }
    }

    public function createPurchase(array $data): int
    {
        return $this->create('bandwidth_purchases', ['provider_id' => $data['provider_id'], 'mbps_quantity' => $data['mbps_quantity'], 'price_per_mbps' => $data['price_per_mbps'], 'total_amount' => $data['total_amount'], 'bill_number' => $data['bill_number'] ?? '', 'due_date' => $data['due_date'] ?? null, 'notes' => $data['notes'] ?? '', 'created_by' => $data['created_by'], 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function getInvoices(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));

        try {
            $row = $this->db->fetchOne("SELECT COUNT(*) AS t FROM bandwidth_invoices", []);
            $total = (int)($row['t'] ?? 0);
            $data = $this->db->fetchAll("SELECT bi.*, br.name AS reseller_name FROM bandwidth_invoices bi LEFT JOIN bandwidth_resellers br ON br.id = bi.reseller_id ORDER BY bi.created_at DESC LIMIT {$limit} OFFSET " . (($page - 1) * $limit), []);
            return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $limit, 'totalPages' => max(1, ceil($total / $limit))];
        } catch (\Throwable $e) { return ['data' => [], 'total' => 0]; }
    }

    public function getInvoice(int $id): ?array
    {
        try { return $this->db->fetchOne("SELECT bi.*, br.name AS reseller_name FROM bandwidth_invoices bi LEFT JOIN bandwidth_resellers br ON br.id = bi.reseller_id WHERE bi.id = ?", [$id]); }
        catch (\Throwable $e) { return null; }
    }

    public function getReport(): array
    {
        try {
            $providers = $this->db->fetchOne("SELECT COUNT(*) AS c FROM bandwidth_providers", [])['c'] ?? 0;
            $resellers = $this->db->fetchOne("SELECT COUNT(*) AS c FROM bandwidth_resellers", [])['c'] ?? 0;
            $purchases = $this->db->fetchOne("SELECT SUM(total_amount) AS t FROM bandwidth_purchases", [])['t'] ?? 0;
            $invoices = $this->db->fetchOne("SELECT SUM(total_amount) AS t FROM bandwidth_invoices", [])['t'] ?? 0;
            return ['providers' => $providers, 'resellers' => $resellers, 'purchases' => $purchases, 'invoices' => $invoices];
        } catch (\Throwable $e) { return []; }
    }
}