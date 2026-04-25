<?php

/**
 * ConfigurationService — Business logic for Business Configuration module.
 *
 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7, 17.8, 17.9, 17.10
 *   - Zone, Sub-zone, POP, BOX management
 *   - Package setup with MikroTik/RADIUS profiles
 *   - Billing automation rules
 *   - Template management
 */
class ConfigurationService extends BaseService
{
    public function getZones(): array
    {
        try { return $this->db->fetchAll("SELECT * FROM zones ORDER BY name", []); }
        catch (\Throwable $e) { return []; }
    }

    public function getZone(int $id): ?array
    {
        try { return $this->db->fetchOne("SELECT * FROM zones WHERE id = ?", [$id]); }
        catch (\Throwable $e) { return null; }
    }

    public function createZone(array $data): int
    {
        return $this->create('zones', ['name' => $data['name'], 'code' => $data['code'], 'description' => $data['description'] ?? '', 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function updateZone(int $id, array $data): void
    {
        $this->update('zones', $id, ['name' => $data['name'], 'code' => $data['code'], 'description' => $data['description'] ?? '']);
    }

    public function deleteZone(int $id): void
    {
        $this->delete('zones', $id);
    }

    public function getPOPs(): array
    {
        try { return $this->db->fetchAll("SELECT p.*, z.name AS zone_name FROM pop_nodes p LEFT JOIN zones z ON z.id = p.zone_id ORDER BY p.name", []); }
        catch (\Throwable $e) { return []; }
    }

    public function getPop(int $id): ?array
    {
        try { return $this->db->fetchOne("SELECT p.*, z.name AS zone_name FROM pop_nodes p LEFT JOIN zones z ON z.id = p.zone_id WHERE p.id = ?", [$id]); }
        catch (\Throwable $e) { return null; }
    }

    public function createPop(array $data): int
    {
        return $this->create('pop_nodes', ['name' => $data['name'], 'code' => $data['code'], 'zone_id' => $data['zone_id'], 'ip_address' => $data['ip_address'] ?? '', 'location' => $data['location'] ?? '', 'status' => $data['status'] ?? 'active', 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function updatePop(int $id, array $data): void
    {
        $this->update('pop_nodes', $id, ['name' => $data['name'], 'code' => $data['code'], 'zone_id' => $data['zone_id'], 'ip_address' => $data['ip_address'] ?? '', 'location' => $data['location'] ?? '', 'status' => $data['status'] ?? 'active']);
    }

    public function getPackages(): array
    {
        try { return $this->db->fetchAll("SELECT p.*, rp.name AS profile_name FROM packages p LEFT JOIN radreply rp ON rp.id = p.profile_id ORDER BY p.name", []); }
        catch (\Throwable $e) { return []; }
    }

    public function getPackage(int $id): ?array
    {
        try { return $this->db->fetchOne("SELECT p.*, rp.name AS profile_name FROM packages p LEFT JOIN radreply rp ON rp.id = p.profile_id WHERE p.id = ?", [$id]); }
        catch (\Throwable $e) { return null; }
    }

    public function createPackage(array $data): int
    {
        return $this->create('packages', ['name' => $data['name'], 'code' => $data['code'], 'price' => $data['price'], 'download_speed' => $data['download_speed'], 'upload_speed' => $data['upload_speed'], 'data_limit' => $data['data_limit'], 'profile_id' => $data['profile_id'] ?? null, 'is_active' => $data['is_active'] ?? 1, 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function updatePackage(int $id, array $data): void
    {
        $this->update('packages', $id, ['name' => $data['name'], 'code' => $data['code'], 'price' => $data['price'], 'download_speed' => $data['download_speed'], 'upload_speed' => $data['upload_speed'], 'data_limit' => $data['data_limit'], 'profile_id' => $data['profile_id'] ?? null, 'is_active' => $data['is_active'] ?? 1]);
    }

    public function getSettings(): array
    {
        try {
            $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM configuration_settings", []);
            $settings = [];
            foreach ($rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
            return $settings;
        } catch (\Throwable $e) { return []; }
    }

    public function updateSetting(string $key, string $value): void
    {
        $this->db->update('configuration_settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
    }

    public function getRadiusProfiles(): array
    {
        try { return $this->db->fetchAll("SELECT id, name FROM radreply WHERE active = 1 ORDER BY name", []); }
        catch (\Throwable $e) { return []; }
    }

    public function getBillingRules(): array
    {
        try { return $this->db->fetchAll("SELECT * FROM billing_rules WHERE is_active = 1 ORDER BY priority", []); }
        catch (\Throwable $e) { return []; }
    }

    public function createBillingRule(array $data): int
    {
        return $this->create('billing_rules', ['rule_name' => $data['rule_name'], 'rule_type' => $data['rule_type'], 'conditions' => $data['conditions'] ?? '', 'action' => $data['action'] ?? '', 'priority' => $data['priority'] ?? 0, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function getInvoiceTemplates(): array
    {
        try { return $this->db->fetchAll("SELECT * FROM invoice_templates WHERE is_active = 1 ORDER BY name", []); }
        catch (\Throwable $e) { return []; }
    }
}