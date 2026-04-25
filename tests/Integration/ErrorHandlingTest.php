<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for error handling in cross-module scenarios.
 *
 * Verifies that CrossModuleDataFlowService handles missing/invalid data
 * gracefully (no exceptions, no partial writes) and that
 * PortalSessionMiddleware rejects unknown portal types.
 */
class ErrorHandlingTest extends TestCase
{
    private static \PDO $pdo;

    // ── Schema bootstrap ──────────────────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new \PDO('sqlite::memory:');
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // Minimal schema — only the tables the service reads/writes
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS purchase_bills (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                bill_number TEXT    NOT NULL,
                items       TEXT    NOT NULL DEFAULT '[]',
                status      TEXT    NOT NULL DEFAULT 'draft',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS inventory_items (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                code         TEXT,
                name         TEXT    NOT NULL,
                quantity     INTEGER NOT NULL DEFAULT 0,
                warehouse_id INTEGER,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS stock_movements (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                item_id        INTEGER NOT NULL,
                warehouse_id   INTEGER,
                movement_type  TEXT    NOT NULL,
                quantity       INTEGER NOT NULL,
                unit_price     REAL    NOT NULL DEFAULT 0,
                total_amount   REAL    NOT NULL DEFAULT 0,
                reference_type TEXT,
                reference_id   INTEGER,
                performed_by   INTEGER,
                notes          TEXT,
                movement_date  TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS sales_invoices (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number TEXT    NOT NULL,
                total          REAL    NOT NULL DEFAULT 0,
                payment_status TEXT    NOT NULL DEFAULT 'unpaid',
                branch_id      INTEGER NOT NULL DEFAULT 0,
                created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS sales_payments (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id   INTEGER NOT NULL,
                amount       REAL    NOT NULL,
                payment_date TEXT    NOT NULL DEFAULT (date('now')),
                created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS income_entries (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id      INTEGER NOT NULL DEFAULT 0,
                source         TEXT    NOT NULL,
                amount         REAL    NOT NULL,
                reference_type TEXT,
                reference_id   INTEGER,
                income_date    TEXT    NOT NULL DEFAULT (date('now')),
                recorded_by    INTEGER,
                created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS support_tickets (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                subject    TEXT    NOT NULL,
                status     TEXT    NOT NULL DEFAULT 'open',
                created_at TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS employees (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL UNIQUE,
                name       TEXT    NOT NULL,
                created_at TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS employee_portal_notifications (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                employee_id    INTEGER NOT NULL,
                type           TEXT    NOT NULL,
                reference_id   INTEGER,
                reference_type TEXT,
                message        TEXT    NOT NULL,
                is_read        INTEGER NOT NULL DEFAULT 0,
                created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");
    }

    protected function tearDown(): void
    {
        $tables = [
            'purchase_bills',
            'inventory_items',
            'stock_movements',
            'sales_invoices',
            'sales_payments',
            'income_entries',
            'support_tickets',
            'employees',
            'employee_portal_notifications',
        ];
        foreach ($tables as $table) {
            self::$pdo->exec("DELETE FROM `{$table}`");
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function service(): \TestableCrossModuleDataFlowService
    {
        return new \TestableCrossModuleDataFlowService(self::$pdo);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /**
     * Calling onPurchaseBillSaved() with a non-existent bill ID must not throw
     * an exception and must not create any stock_movements rows.
     */
    public function testCrossModuleDataFlowHandlesMissingBill(): void
    {
        try {
            $this->service()->onPurchaseBillSaved(99999);
        } catch (\Throwable $e) {
            $this->fail('onPurchaseBillSaved() threw an exception for a missing bill: ' . $e->getMessage());
        }

        $stmt = self::$pdo->query("SELECT COUNT(*) AS cnt FROM stock_movements");
        $count = (int)$stmt->fetch()['cnt'];
        $this->assertSame(0, $count, 'No stock movements should be created for a missing bill');
    }

    /**
     * Calling onSalesInvoicePaid() with a non-existent invoice ID must not throw
     * an exception and must not create any income_entries rows.
     */
    public function testCrossModuleDataFlowHandlesMissingInvoice(): void
    {
        try {
            $this->service()->onSalesInvoicePaid(99999);
        } catch (\Throwable $e) {
            $this->fail('onSalesInvoicePaid() threw an exception for a missing invoice: ' . $e->getMessage());
        }

        $stmt = self::$pdo->query("SELECT COUNT(*) AS cnt FROM income_entries");
        $count = (int)$stmt->fetch()['cnt'];
        $this->assertSame(0, $count, 'No income entries should be created for a missing invoice');
    }

    /**
     * Calling onTicketAssigned() with a non-existent employee user ID must not
     * throw an exception and must not create any notification rows.
     */
    public function testCrossModuleDataFlowHandlesMissingEmployee(): void
    {
        // Insert a real ticket but no employee record for user 99999
        $stmt = self::$pdo->prepare("INSERT INTO support_tickets (subject) VALUES (?)");
        $stmt->execute(['Orphaned ticket']);
        $ticketId = (int)self::$pdo->lastInsertId();

        try {
            $this->service()->onTicketAssigned($ticketId, 99999);
        } catch (\Throwable $e) {
            $this->fail('onTicketAssigned() threw an exception for a missing employee: ' . $e->getMessage());
        }

        $stmt = self::$pdo->query("SELECT COUNT(*) AS cnt FROM employee_portal_notifications");
        $count = (int)$stmt->fetch()['cnt'];
        $this->assertSame(0, $count, 'No notifications should be created when employee is missing');
    }

    /**
     * Constructing PortalSessionMiddleware with an unknown portal type must
     * throw an InvalidArgumentException.
     */
    public function testPortalSessionMiddlewareHandlesUnknownPortalType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new \PortalSessionMiddleware('invalid_type');
    }
}
