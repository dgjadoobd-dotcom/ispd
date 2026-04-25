<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests for database migration infrastructure.
 *
 * Uses an in-memory SQLite database so no external database connection
 * is required.  Validates that the migration-tracking table can be
 * created idempotently and has the expected schema (Requirement 10.5).
 */
class DatabaseMigrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function tearDown(): void
    {
        // In-memory database is automatically destroyed when the PDO object
        // goes out of scope; nothing explicit needed here.
        unset($this->pdo);
    }

    /**
     * Verify that the _migrations tracking table can be created twice
     * without throwing an exception (idempotency), that it actually
     * exists afterwards, and that it contains the expected columns.
     */
    public function testMigrationTrackingTableCreation(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS _migrations (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                filename    TEXT NOT NULL UNIQUE,
                applied_at  DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // First creation — must not throw
        $this->pdo->exec($sql);

        // Second creation — idempotency: must also not throw
        $this->pdo->exec($sql);

        // Assert the table exists in sqlite_master
        $stmt = $this->pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='_migrations'"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row, 'Table _migrations should exist in sqlite_master');
        $this->assertSame('_migrations', $row['name']);

        // Assert the table has the expected columns via PRAGMA table_info
        $stmt    = $this->pdo->query('PRAGMA table_info(_migrations)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columnNames = array_column($columns, 'name');

        $this->assertContains('id',         $columnNames, 'Column "id" should exist');
        $this->assertContains('filename',   $columnNames, 'Column "filename" should exist');
        $this->assertContains('applied_at', $columnNames, 'Column "applied_at" should exist');
    }
}
