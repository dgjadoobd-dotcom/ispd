<?php

/**
 * SupabaseService - Local Supabase integration for FCN ISP ERP
 * 
 * Provides database sync, auth, and real-time subscriptions
 * 
 * Requirements:
 * - Install Supabase CLI: https://github.com/supabase/cli
 * - Run: supabase start
 * - Get keys: supabase status
 */
class SupabaseService {
    private bool   $enabled;
    private string $url;
    private string $anonKey;
    private string $serviceKey;
    private string $databaseUrl;
    private ?PDO $pdo = null;

    public function __construct() {
        $this->enabled = (bool)env('SUPABASE_ENABLED', false);
        $this->url    = rtrim(env('SUPABASE_URL', 'http://127.0.0.1:54321'), '/');
        $this->anonKey = env('SUPABASE_ANON_KEY', '');
        $this->serviceKey = env('SUPABASE_SERVICE_ROLE_KEY', '');
        $this->databaseUrl = $this->url . '/rest/v1';
    }

    /**
     * Test connection to Supabase
     */
    public function testConnection(): array {
        if (!$this->enabled) {
            return ['ok' => false, 'error' => 'Supabase is disabled'];
        }

        $ch = curl_init($this->url . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['ok' => true, 'url' => $this->url];
        }

        return ['ok' => false, 'error' => "HTTP $httpCode"];
    }

    /**
     * Get database connection (PDO)
     */
    public function getConnection(): ?PDO {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $dbUrl = parse_url($this->databaseUrl);
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=postgres;user=%s;password=%s',
                $dbUrl['host'],
                $dbUrl['port'] ?? 5432,
                $this->anonKey,
                $this->serviceKey
            );
            $this->pdo = new PDO($dsn, $this->anonKey, $this->serviceKey, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $e) {
            error_log("Supabase connection failed: " . $e->getMessage());
            return null;
        }

        return $this->pdo;
    }

    /**
     * Fetch from Supabase REST API
     */
    public function select(string $table, array $filters = []): array {
        $url = $this->databaseUrl . '/' . urlencode($table);
        
        if (!empty($filters)) {
            $url .= '?' . http_build_query($filters);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->anonKey
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    /**
     * Insert into Supabase
     */
    public function insert(string $table, array $data): bool {
        $url = $this->databaseUrl . '/' . urlencode($table);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: application/json',
            'Prefer: return=minimal'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 201;
    }

    /**
     * Update in Supabase
     */
    public function update(string $table, int $id, array $data): bool {
        $url = $this->databaseUrl . '/' . urlencode($table) . '?id=eq.' . $id;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOM, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: application/json',
            'Prefer: return=minimal'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Delete from Supabase
     */
    public function delete(string $table, int $id): bool {
        $url = $this->databaseUrl . '/' . urlencode($table) . '?id=eq.' . $id;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOM, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 204 || $httpCode === 200;
    }

    /**
     * Get realtime channel URL for subscriptions
     */
    public function getRealtimeUrl(string $table): string {
        return $this->url . '/realtime/v1/websocket?apikey=' . $this->anonKey 
            . '&schema=public&table=' . $table;
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }
}