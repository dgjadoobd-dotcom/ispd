<?php

class Database {
    private static $instance = null;
    private $connection;
    private bool $isSqlite = false;

    private function __construct(string $name = 'default') {
        $envFile = BASE_PATH . '/.env';
        $env = [];
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (!str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k); $v = trim($v);
                if (strlen($v) >= 2 && (
                    ($v[0] === '"' && $v[-1] === '"') ||
                    ($v[0] === "'" && $v[-1] === "'")
                )) { $v = substr($v, 1, -1); }
                $env[$k] = $v;
            }
        }
        
        $prefix = ($name === 'default') ? 'DB_' : strtoupper($name) . '_DB_';
        
        $connectionType = $env[$prefix . 'CONNECTION'] ?? ($name === 'default' ? 'mysql' : 'mysql');
        $host = $env[$prefix . 'HOST'] ?? '127.0.0.1';
        $port = $env[$prefix . 'PORT'] ?? '3306';
        $db   = $env[$prefix . 'DATABASE'] ?? ($name === 'default' ? 'digital_isp' : 'radius');
        $user = $env[$prefix . 'USERNAME'] ?? 'root';
        $pass = $env[$prefix . 'PASSWORD'] ?? '';

        try {
            if ($connectionType === 'sqlite') {
                $dbPath = str_starts_with($db, '/') ? $db : BASE_PATH . '/' . $db;
                $this->connection = new PDO("sqlite:{$dbPath}");
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->isSqlite = true;
                $this->connection->exec('PRAGMA foreign_keys = ON');
                $this->ensureSqliteSchema();
            } else {
                $this->connection = new PDO(
                    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            }
        } catch (PDOException $e) {
            if ($name === 'default') {
                http_response_code(500);
                die(json_encode(['error' => "Database connection ($name) failed: " . $e->getMessage()]));
            } else {
                // For services like RADIUS, we might want to fail gracefully elsewhere
                throw $e;
            }
        }
    }

    private static $instances = [];

    public static function getInstance(string $name = 'default'): self {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name);
        }
        return self::$instances[$name];
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    private function ensureSqliteSchema(): void {
        $schemaPath = BASE_PATH . '/database/sqlite_schema.sql';
        if (!file_exists($schemaPath)) {
            return;
        }

        $requiredTables = ['users', 'customers', 'packages', 'nas_devices', 'config_items', 'sms_campaigns', 'automation_logs'];
        $missingTables = [];

        foreach ($requiredTables as $table) {
            $stmt = $this->connection->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?");
            $stmt->execute([$table]);
            if (!$stmt->fetchColumn()) {
                $missingTables[] = $table;
            }
        }

        if ($missingTables === []) {
            // Table structure may still be outdated — run column migrations
            $this->runColumnMigrations();
            return;
        }

        // Load main schema first
        $sql = file_get_contents($schemaPath);
        if ($sql === false || trim($sql) === '') {
            return;
        }

        $this->connection->exec($sql);

        // If config_items is still missing after schema load, run the helper script
        $stmt = $this->connection->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'config_items'");
        if (!$stmt->execute() || !$stmt->fetchColumn()) {
            $helperScript = BASE_PATH . '/create_config_table.php';
            if (file_exists($helperScript)) {
                require_once $helperScript;
                if (function_exists('createConfigItemsTable')) {
                    createConfigItemsTable();
                }
            }
        }

        $this->runColumnMigrations();
    }

    /**
     * Add missing columns to existing tables without dropping data.
     */
    private function runColumnMigrations(): void {
        $migrations = [
            'nas_devices' => [
                "ALTER TABLE nas_devices ADD COLUMN mikrotik_version VARCHAR(20) DEFAULT 'v2'",
                "ALTER TABLE nas_devices ADD COLUMN timeout INTEGER DEFAULT 10",
                "ALTER TABLE nas_devices ADD COLUMN connection_status INTEGER DEFAULT 0",
                "ALTER TABLE nas_devices ADD COLUMN last_checked TIMESTAMP NULL",
            ],
            'customers' => [
                "ALTER TABLE customers ADD COLUMN auto_payment_enabled INTEGER DEFAULT 0",
                "ALTER TABLE customers ADD COLUMN last_online_at DATETIME NULL",
                "ALTER TABLE customers ADD COLUMN current_ip VARCHAR(45) NULL",
                "ALTER TABLE customers ADD COLUMN mac_address VARCHAR(20) NULL",
                "ALTER TABLE customers ADD COLUMN road_no VARCHAR(50) NULL",
                "ALTER TABLE customers ADD COLUMN house_no VARCHAR(50) NULL",
                "ALTER TABLE customers ADD COLUMN sub_zone VARCHAR(100) NULL",
                "ALTER TABLE customers ADD COLUMN box_no VARCHAR(50) NULL",
                "ALTER TABLE customers ADD COLUMN client_type TEXT DEFAULT 'home'",
                "ALTER TABLE customers ADD COLUMN thana VARCHAR(100) NULL",
                "ALTER TABLE customers ADD COLUMN district VARCHAR(100) NULL",
                "ALTER TABLE customers ADD COLUMN device_name VARCHAR(100) NULL",
                "ALTER TABLE customers ADD COLUMN device_purchase_date DATE NULL",
                "ALTER TABLE customers ADD COLUMN assigned_employee VARCHAR(100) NULL",
            ],
            'mac_bindings' => [
                "ALTER TABLE mac_bindings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            ],
            'onus' => [
                "ALTER TABLE onus ADD COLUMN last_synced_at DATETIME NULL",
                "ALTER TABLE onus ADD COLUMN previous_snapshot TEXT NULL",
                "ALTER TABLE onus ADD COLUMN deregister_reason VARCHAR(200) NULL",
                "ALTER TABLE onus ADD COLUMN olt_id INTEGER NULL",
            ],
            'support_tickets' => [
                "ALTER TABLE support_tickets ADD COLUMN sla_deadline DATETIME NULL",
                "ALTER TABLE support_tickets ADD COLUMN sla_breached INTEGER DEFAULT 0",
                "ALTER TABLE support_tickets ADD COLUMN category_id INTEGER NULL",
                "ALTER TABLE support_tickets ADD COLUMN resolution_notes TEXT NULL",
            ],
        ];

        foreach ($migrations as $table => $statements) {
            // Get current columns for this table
            $cols = $this->connection->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_COLUMN, 1);
            foreach ($statements as $sql) {
                // Extract column name from ALTER TABLE ... ADD COLUMN <name> ...
                if (preg_match('/ADD COLUMN\s+(\w+)/i', $sql, $m)) {
                    if (!in_array($m[1], $cols, true)) {
                        $this->connection->exec($sql);
                    }
                }
            }
        }
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function insert(string $table, array $data): int {
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(', ', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})", array_values($data));
        return (int)$this->connection->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $stmt = $this->query("UPDATE `{$table}` SET {$set} WHERE {$where}", [...array_values($data), ...$whereParams]);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int {
        return $this->query("DELETE FROM {$table} WHERE {$where}", $params)->rowCount();
    }

    /**
     * Execute a raw SQL statement (used by migration scripts).
     */
    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }

    public function isSqlite(): bool {
        return $this->isSqlite;
    }
}
