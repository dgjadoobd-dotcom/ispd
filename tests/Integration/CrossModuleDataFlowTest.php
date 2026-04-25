<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for CrossModuleDataFlowService.
 *
 * Tests the three cross-module data flows:
 *   1. Purchase Bill → Inventory Update + Stock Movement
 *   2. Sales Invoice (paid) → Income Entry
 *   3. Ticket Assignment → Employee Portal Notification
 *
 * Uses an in-memory SQLite database. Each test gets a fresh schema via
 * setUpBeforeClass() and row-level isolation via tearDown().
 */
class CrossModuleDataFlowTest extends TestCase
{
    private static \PDO $pdo;

    // ── Schema bootstrap ──────────────────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new \PDO('sqlite::memory:');
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // purchase_bills — stores purchase orders with JSON line items
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS purchase_bills (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                bill_number TEXT    NOT NULL,
                items       TEXT    NOT NULL DEFAULT '[]',
                status      TEXT    NOT NULL DEFAULT 'draft',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // inventory_items — tracks stock quantities
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

        // stock_movements — audit trail for inventory changes
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

        // sales_invoices — customer invoices
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

        // sales_payments — payment records linked to invoices
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS sales_payments (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id   INTEGER NOT NULL,
                amount       REAL    NOT NULL,
                payment_date TEXT    NOT NULL DEFAULT (date('now')),
                created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // income_entries — accounts module income records
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

        // support_tickets — customer support tickets
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS support_tickets (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                subject    TEXT    NOT NULL,
                status     TEXT    NOT NULL DEFAULT 'open',
                created_at TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // employees — employee records linked to user accounts
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS employees (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL UNIQUE,
                name       TEXT    NOT NULL,
                created_at TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // employee_portal_notifications — in-app notifications for employees
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
        // Wipe all rows between tests to keep them isolated
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

    private function insertPurchaseBill(array $items, string $billNumber = 'PB-001'): int
    {
        $stmt = self::$pdo->prepare(
            "INSERT INTO purchase_bills (bill_number, items) VALUES (?, ?)"
        );
        $stmt->execute([$billNumber, json_encode($items)]);
        return (int)self::$pdo->lastInsertId();
    }

    private function insertInventoryItem(int $quantity = 10, ?string $code = null): int
    {
        $stmt = self::$pdo->prepare(
            "INSERT INTO inventory_items (name, quantity, code) VALUES (?, ?, ?)"
        );
        $stmt->execute(['Test Item', $quantity, $code]);
        return (int)self::$pdo->lastInsertId();
    }

    private function insertSalesInvoice(float $total, string $paymentStatus = 'paid'): int
    {
        $stmt = self::$pdo->prepare(
            "INSERT INTO sales_invoices (invoice_number, total, payment_status) VALUES (?, ?, ?)"
        );
        $stmt->execute(['INV-001', $total, $paymentStatus]);
        return (int)self::$pdo->lastInsertId();
    }

    private function insertSalesPayment(int $invoiceId, float $amount): void
    {
        $stmt = self::$pdo->prepare(
            "INSERT INTO sales_payments (invoice_id, amount, payment_date) VALUES (?, ?, date('now'))"
        );
        $stmt->execute([$invoiceId, $amount]);
    }

    private function insertTicket(string $subject = 'Test Ticket'): int
    {
        $stmt = self::$pdo->prepare(
            "INSERT INTO support_tickets (subject) VALUES (?)"
        );
        $stmt->execute([$subject]);
        return (int)self::$pdo->lastInsertId();
    }

    private function insertEmployee(int $userId, string $name = 'Test Employee'): int
    {
        $stmt = self::$pdo->prepare(
            "INSERT INTO employees (user_id, name) VALUES (?, ?)"
        );
        $stmt->execute([$userId, $name]);
        return (int)self::$pdo->lastInsertId();
    }

    // ── Tests: Purchase Bill → Inventory ─────────────────────────────────────

    /**
     * Saving a purchase bill increments inventory quantity and creates a
     * stock movement record with movement_type = 'purchase'.
     */
    public function testPurchaseBillSavedUpdatesInventoryStock(): void
    {
        $itemId = $this->insertInventoryItem(10);
        $billId = $this->insertPurchaseBill([
            ['item_id' => $itemId, 'qty' => 5, 'unit_price' => 100],
        ]);

        $this->service()->onPurchaseBillSaved($billId);

        // Inventory quantity should be 10 + 5 = 15
        $stmt = self::$pdo->prepare("SELECT quantity FROM inventory_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $row = $stmt->fetch();
        $this->assertSame(15, (int)$row['quantity'], 'Inventory quantity should be incremented by 5');

        // A stock movement of type 'purchase' should have been created
        $stmt = self::$pdo->prepare(
            "SELECT * FROM stock_movements WHERE item_id = ? AND movement_type = 'purchase'"
        );
        $stmt->execute([$itemId]);
        $movement = $stmt->fetch();
        $this->assertNotFalse($movement, 'A stock movement row should have been created');
        $this->assertSame('purchase', $movement['movement_type']);
        $this->assertSame(5, (int)$movement['quantity']);
    }

    /**
     * Calling onPurchaseBillSaved() twice must not double-increment inventory
     * or create duplicate stock movement rows (idempotency).
     */
    public function testPurchaseBillSavedIsIdempotent(): void
    {
        $itemId = $this->insertInventoryItem(10);
        $billId = $this->insertPurchaseBill([
            ['item_id' => $itemId, 'qty' => 5, 'unit_price' => 100],
        ]);

        $this->service()->onPurchaseBillSaved($billId);
        $this->service()->onPurchaseBillSaved($billId);

        // Quantity should only be incremented once
        $stmt = self::$pdo->prepare("SELECT quantity FROM inventory_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $row = $stmt->fetch();
        $this->assertSame(15, (int)$row['quantity'], 'Quantity should only be incremented once');

        // Only one stock movement row should exist
        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) AS cnt FROM stock_movements WHERE item_id = ? AND movement_type = 'purchase'"
        );
        $stmt->execute([$itemId]);
        $count = (int)$stmt->fetch()['cnt'];
        $this->assertSame(1, $count, 'Only one stock movement row should exist');
    }

    // ── Tests: Sales Invoice → Income Entry ──────────────────────────────────

    /**
     * When a paid sales invoice is processed, an income_entries row is created
     * with the correct amount and reference_type = 'sales_invoice'.
     */
    public function testSalesInvoicePaidCreatesIncomeEntry(): void
    {
        $invoiceId = $this->insertSalesInvoice(5000.00, 'paid');
        $this->insertSalesPayment($invoiceId, 5000.00);

        $this->service()->onSalesInvoicePaid($invoiceId);

        $stmt = self::$pdo->prepare(
            "SELECT * FROM income_entries WHERE reference_type = 'sales_invoice' AND reference_id = ?"
        );
        $stmt->execute([$invoiceId]);
        $entry = $stmt->fetch();

        $this->assertNotFalse($entry, 'An income entry should have been created');
        $this->assertSame(5000.0, (float)$entry['amount']);
        $this->assertSame('sales_invoice', $entry['reference_type']);
        $this->assertSame($invoiceId, (int)$entry['reference_id']);
    }

    /**
     * Calling onSalesInvoicePaid() twice must not create duplicate income entries
     * (idempotency).
     */
    public function testSalesInvoicePaidIsIdempotent(): void
    {
        $invoiceId = $this->insertSalesInvoice(5000.00, 'paid');
        $this->insertSalesPayment($invoiceId, 5000.00);

        $this->service()->onSalesInvoicePaid($invoiceId);
        $this->service()->onSalesInvoicePaid($invoiceId);

        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) AS cnt FROM income_entries WHERE reference_type = 'sales_invoice' AND reference_id = ?"
        );
        $stmt->execute([$invoiceId]);
        $count = (int)$stmt->fetch()['cnt'];
        $this->assertSame(1, $count, 'Only one income entry should exist');
    }

    /**
     * An invoice with payment_status != 'paid' must not generate an income entry.
     */
    public function testSalesInvoiceNotPaidSkipsIncomeEntry(): void
    {
        $invoiceId = $this->insertSalesInvoice(3000.00, 'partial');

        $this->service()->onSalesInvoicePaid($invoiceId);

        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) AS cnt FROM income_entries WHERE reference_type = 'sales_invoice' AND reference_id = ?"
        );
        $stmt->execute([$invoiceId]);
        $count = (int)$stmt->fetch()['cnt'];
        $this->assertSame(0, $count, 'No income entry should be created for a partially-paid invoice');
    }

    // ── Tests: Ticket Assignment → Employee Notification ─────────────────────

    /**
     * Assigning a ticket to an employee creates an employee_portal_notifications
     * row with type = 'ticket_assigned'.
     */
    public function testTicketAssignedCreatesEmployeeNotification(): void
    {
        $ticketId = $this->insertTicket('Network outage');
        $userId   = 42;
        $this->insertEmployee($userId);

        $this->service()->onTicketAssigned($ticketId, $userId);

        $stmt = self::$pdo->prepare(
            "SELECT * FROM employee_portal_notifications WHERE reference_type = 'ticket' AND reference_id = ?"
        );
        $stmt->execute([$ticketId]);
        $notification = $stmt->fetch();

        $this->assertNotFalse($notification, 'A notification row should have been created');
        $this->assertSame('ticket_assigned', $notification['type']);
        $this->assertSame($ticketId, (int)$notification['reference_id']);
    }

    /**
     * Calling onTicketAssigned() twice must not create duplicate notifications
     * (idempotency).
     */
    public function testTicketAssignedIsIdempotent(): void
    {
        $ticketId = $this->insertTicket('Duplicate notification test');
        $userId   = 43;
        $this->insertEmployee($userId);

        $this->service()->onTicketAssigned($ticketId, $userId);
        $this->service()->onTicketAssigned($ticketId, $userId);

        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) AS cnt FROM employee_portal_notifications
             WHERE reference_type = 'ticket' AND reference_id = ? AND type = 'ticket_assigned'"
        );
        $stmt->execute([$ticketId]);
        $count = (int)$stmt->fetch()['cnt'];
        $this->assertSame(1, $count, 'Only one notification row should exist');
    }
}
