<?php

/**
 * PdoDatabase — thin subclass of Database that wraps a raw PDO connection.
 *
 * Extends Database to satisfy the type constraint on BaseService::$db,
 * but bypasses the Database constructor entirely so no real DB connection
 * is attempted. All methods delegate to the injected PDO instance.
 *
 * Used only in integration tests.
 */
class PdoDatabase extends Database
{
    private \PDO $pdo;

    /**
     * Use a static factory instead of a constructor to avoid calling
     * Database::__construct() (which is private and connects to MySQL).
     */
    public static function fromPdo(\PDO $pdo): self
    {
        // Bypass the private Database constructor via ReflectionClass
        $instance = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $instance->pdo = $pdo;
        return $instance;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function insert(string $table, array $data): int
    {
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(', ', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})", array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set  = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $stmt = $this->query(
            "UPDATE `{$table}` SET {$set} WHERE {$where}",
            [...array_values($data), ...$whereParams]
        );
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->query("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
    }
}

/**
 * TestableCrossModuleDataFlowService — subclass of CrossModuleDataFlowService
 * that accepts a raw PDO connection for integration testing.
 *
 * Bypasses the Database::getInstance() singleton and LoggingService
 * filesystem side-effects so tests can run against an in-memory SQLite DB.
 */
class TestableCrossModuleDataFlowService extends CrossModuleDataFlowService
{
    public function __construct(\PDO $pdo)
    {
        // Skip parent constructor entirely; inject dependencies directly.
        $this->db     = PdoDatabase::fromPdo($pdo);
        $this->logger = new NullLoggingService();
    }
}
