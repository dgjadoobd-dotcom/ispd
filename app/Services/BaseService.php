<?php

/**
 * BaseService — abstract base class for all service layer classes.
 *
 * Provides:
 *  - Shared $db (Database) and $logger (LoggingService) properties
 *  - Common CRUD helpers: findById, findAll, create, update, delete, paginate
 *  - Consistent error handling with automatic logging
 *
 * Usage:
 *   class HrService extends BaseService {
 *       public function getEmployee(int $id): ?array {
 *           return $this->findById('employees', $id);
 *       }
 *   }
 */
abstract class BaseService
{
    /** @var Database Shared database connection */
    protected Database $db;

    /** @var LoggingService Shared structured logger */
    protected LoggingService $logger;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->logger = new LoggingService();
    }

    // ── Generic CRUD helpers ──────────────────────────────────────

    /**
     * Fetch a single row by primary key.
     *
     * @param  string   $table  Table name
     * @param  int|string $id   Primary key value
     * @return array|null       Row as associative array, or null if not found
     */
    protected function findById(string $table, int|string $id): ?array
    {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `{$table}` WHERE id = ? LIMIT 1",
                [$id]
            );
        } catch (\Throwable $e) {
            $this->logError("findById failed on {$table}", $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Fetch multiple rows with optional WHERE conditions, ORDER BY, and LIMIT.
     *
     * @param  string        $table       Table name
     * @param  array         $conditions  Associative array of column => value (all joined with AND =)
     * @param  string|null   $orderBy     ORDER BY clause, e.g. "created_at DESC"
     * @param  int|null      $limit       Maximum rows to return
     * @return array                      Array of rows
     */
    protected function findAll(
        string $table,
        array $conditions = [],
        ?string $orderBy = null,
        ?int $limit = null
    ): array {
        try {
            [$whereClause, $params] = $this->buildWhere($conditions);

            $sql = "SELECT * FROM `{$table}`{$whereClause}";

            if ($orderBy !== null) {
                $sql .= " ORDER BY {$orderBy}";
            }

            if ($limit !== null) {
                $sql .= " LIMIT " . (int)$limit;
            }

            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError("findAll failed on {$table}", $e, ['conditions' => $conditions]);
            return [];
        }
    }

    /**
     * Insert a new row and return the new primary key.
     *
     * @param  string $table  Table name
     * @param  array  $data   Associative array of column => value
     * @return int            New row ID, or 0 on failure
     * @throws \RuntimeException on database error
     */
    protected function create(string $table, array $data): int
    {
        try {
            return $this->db->insert($table, $data);
        } catch (\Throwable $e) {
            $this->logError("create failed on {$table}", $e);
            throw new \RuntimeException("Failed to create record in {$table}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update rows matching the given ID.
     *
     * @param  string    $table  Table name
     * @param  int|string $id    Primary key value
     * @param  array     $data   Associative array of column => value
     * @return int               Number of affected rows
     * @throws \RuntimeException on database error
     */
    protected function update(string $table, int|string $id, array $data): int
    {
        try {
            return $this->db->update($table, $data, 'id = ?', [$id]);
        } catch (\Throwable $e) {
            $this->logError("update failed on {$table}", $e, ['id' => $id]);
            throw new \RuntimeException("Failed to update record in {$table}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a row by primary key.
     *
     * @param  string    $table  Table name
     * @param  int|string $id    Primary key value
     * @return int               Number of affected rows
     * @throws \RuntimeException on database error
     */
    protected function delete(string $table, int|string $id): int
    {
        try {
            return $this->db->delete($table, 'id = ?', [$id]);
        } catch (\Throwable $e) {
            $this->logError("delete failed on {$table}", $e, ['id' => $id]);
            throw new \RuntimeException("Failed to delete record in {$table}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Paginate rows from a table with optional WHERE conditions.
     *
     * Returns an array with:
     *   - data:         array of rows for the current page
     *   - total:        total matching row count
     *   - page:         current page number (1-based)
     *   - perPage:      rows per page
     *   - totalPages:   total number of pages
     *   - hasNext:      whether a next page exists
     *   - hasPrev:      whether a previous page exists
     *
     * @param  string $table       Table name
     * @param  array  $conditions  Associative array of column => value
     * @param  int    $page        Current page (1-based)
     * @param  int    $perPage     Rows per page
     * @param  string|null $orderBy ORDER BY clause
     * @return array
     */
    protected function paginate(
        string $table,
        array $conditions = [],
        int $page = 1,
        int $perPage = 25,
        ?string $orderBy = null
    ): array {
        try {
            $page    = max(1, $page);
            $perPage = max(1, min(200, $perPage));
            $offset  = ($page - 1) * $perPage;

            [$whereClause, $params] = $this->buildWhere($conditions);

            $countRow = $this->db->fetchOne(
                "SELECT COUNT(*) AS total FROM `{$table}`{$whereClause}",
                $params
            );
            $total = (int)($countRow['total'] ?? 0);

            $sql = "SELECT * FROM `{$table}`{$whereClause}";
            if ($orderBy !== null) {
                $sql .= " ORDER BY {$orderBy}";
            }
            $sql .= " LIMIT {$perPage} OFFSET {$offset}";

            $data = $this->db->fetchAll($sql, $params);

            $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

            return [
                'data'       => $data,
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $perPage,
                'totalPages' => $totalPages,
                'hasNext'    => $page < $totalPages,
                'hasPrev'    => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError("paginate failed on {$table}", $e, ['page' => $page, 'perPage' => $perPage]);
            return [
                'data'       => [],
                'total'      => 0,
                'page'       => $page,
                'perPage'    => $perPage,
                'totalPages' => 1,
                'hasNext'    => false,
                'hasPrev'    => false,
            ];
        }
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * Build a WHERE clause and parameter list from an associative conditions array.
     *
     * Each key is treated as a column name (equality check).
     * Returns ['', []] when $conditions is empty.
     *
     * @param  array $conditions  column => value pairs
     * @return array{0: string, 1: array}  [whereClause, params]
     */
    private function buildWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $clauses = [];
        $params  = [];

        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $clauses[] = "`{$column}` IS NULL";
            } else {
                $clauses[] = "`{$column}` = ?";
                $params[]  = $value;
            }
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * Log an error with context and re-usable formatting.
     *
     * @param  string     $message  Human-readable description
     * @param  \Throwable $e        The caught exception
     * @param  array      $context  Additional context data
     */
    protected function logError(string $message, \Throwable $e, array $context = []): void
    {
        $this->logger->error($message, array_merge($context, [
            'exception' => get_class($e),
            'error'     => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]));
    }
}
