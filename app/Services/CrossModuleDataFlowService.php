<?php

/**
 * CrossModuleDataFlowService — Orchestrates data flows that span multiple modules.
 *
 * Implements three cross-module integration points:
 *   1. Purchase Bill → Inventory Update
 *      When a purchase bill is saved/approved, stock quantities are updated
 *      and a stock movement record is created for each line item.
 *
 *   2. Sales Invoice → Accounts Income Entry
 *      When a sales invoice is fully paid, a corresponding income entry is
 *      created in the accounts module (idempotent — skips if already exists).
 *
 *   3. Ticket Assignment → Employee Portal Notification
 *      When a ticket is assigned to an employee, a notification record is
 *      inserted into employee_portal_notifications so the employee portal
 *      surfaces the new ticket.
 *
 * All methods are idempotent and wrapped in try/catch with error logging.
 *
 * @see database/migrations/2024_01_07_001_cross_module_data_flows.sql
 */
class CrossModuleDataFlowService extends BaseService
{
    // ── 1. Purchase Bill → Inventory Update ──────────────────────

    /**
     * Called when a purchase bill is saved/approved.
     * Updates inventory stock for each bill line item and records a
     * stock movement of type 'purchase'.
     *
     * Idempotent: a stock movement with the same reference and item_id
     * will not be inserted twice.
     *
     * @param int $purchaseBillId
     * @return void
     */
    public function onPurchaseBillSaved(int $purchaseBillId): void
    {
        try {
            // Fetch the purchase bill
            $bill = $this->db->fetchOne(
                "SELECT * FROM purchase_bills WHERE id = ? LIMIT 1",
                [$purchaseBillId]
            );

            if (!$bill) {
                $this->logger->warning('CrossModuleDataFlowService::onPurchaseBillSaved — bill not found', [
                    'purchase_bill_id' => $purchaseBillId,
                ]);
                return;
            }

            $billNumber = $bill['bill_number'] ?? "PB-{$purchaseBillId}";
            $reference  = "PB-{$billNumber}";

            // Decode line items stored as JSON in the `items` column
            $lineItems = [];
            if (!empty($bill['items'])) {
                $decoded = json_decode($bill['items'], true);
                if (is_array($decoded)) {
                    $lineItems = $decoded;
                }
            }

            if (empty($lineItems)) {
                $this->logger->info('CrossModuleDataFlowService::onPurchaseBillSaved — no line items', [
                    'purchase_bill_id' => $purchaseBillId,
                ]);
                return;
            }

            $performedBy = $_SESSION['user_id'] ?? null;
            $now         = date('Y-m-d H:i:s');

            foreach ($lineItems as $lineItem) {
                $itemId   = isset($lineItem['item_id'])   ? (int)$lineItem['item_id']   : null;
                $itemCode = $lineItem['item_code'] ?? null;
                $qty      = isset($lineItem['qty'])       ? (int)$lineItem['qty']       : 0;
                $unitPrice = isset($lineItem['unit_price']) ? (float)$lineItem['unit_price'] : 0.00;

                if ($qty <= 0) {
                    continue;
                }

                // Resolve inventory item by item_id or item_code
                $inventoryItem = null;
                if ($itemId) {
                    $inventoryItem = $this->db->fetchOne(
                        "SELECT * FROM inventory_items WHERE id = ? LIMIT 1",
                        [$itemId]
                    );
                }
                if (!$inventoryItem && $itemCode) {
                    $inventoryItem = $this->db->fetchOne(
                        "SELECT * FROM inventory_items WHERE code = ? LIMIT 1",
                        [$itemCode]
                    );
                }

                if (!$inventoryItem) {
                    $this->logger->warning('CrossModuleDataFlowService::onPurchaseBillSaved — inventory item not found', [
                        'purchase_bill_id' => $purchaseBillId,
                        'item_id'          => $itemId,
                        'item_code'        => $itemCode,
                    ]);
                    continue;
                }

                $resolvedItemId  = (int)$inventoryItem['id'];
                $warehouseId     = isset($inventoryItem['warehouse_id']) ? (int)$inventoryItem['warehouse_id'] : null;

                // Idempotency check: skip if a stock movement for this reference + item already exists
                $existingMovement = $this->db->fetchOne(
                    "SELECT id FROM stock_movements
                     WHERE item_id = ?
                       AND reference_type = 'purchase_bill'
                       AND notes LIKE ?
                     LIMIT 1",
                    [$resolvedItemId, "%{$reference}%"]
                );

                if ($existingMovement) {
                    $this->logger->info('CrossModuleDataFlowService::onPurchaseBillSaved — movement already exists, skipping', [
                        'purchase_bill_id' => $purchaseBillId,
                        'item_id'          => $resolvedItemId,
                        'reference'        => $reference,
                    ]);
                    continue;
                }

                // Update inventory quantity
                $this->db->update(
                    'inventory_items',
                    ['quantity' => (int)$inventoryItem['quantity'] + $qty],
                    'id = ?',
                    [$resolvedItemId]
                );

                // Insert stock movement record
                $this->db->insert('stock_movements', [
                    'item_id'        => $resolvedItemId,
                    'warehouse_id'   => $warehouseId,
                    'movement_type'  => 'purchase',
                    'quantity'       => $qty,
                    'unit_price'     => $unitPrice,
                    'total_amount'   => round($qty * $unitPrice, 2),
                    'reference_type' => 'purchase_bill',
                    'reference_id'   => $purchaseBillId,
                    'performed_by'   => $performedBy,
                    'notes'          => "Auto-created from {$reference}",
                    'movement_date'  => $now,
                ]);
            }

            $this->logger->info('CrossModuleDataFlowService::onPurchaseBillSaved — completed', [
                'purchase_bill_id' => $purchaseBillId,
                'reference'        => $reference,
                'items_processed'  => count($lineItems),
            ]);
        } catch (\Throwable $e) {
            $this->logError('CrossModuleDataFlowService::onPurchaseBillSaved failed', $e, [
                'purchase_bill_id' => $purchaseBillId,
            ]);
        }
    }

    // ── 2. Sales Invoice → Accounts Income Entry ─────────────────

    /**
     * Called when a sales invoice payment is recorded and the invoice is
     * fully paid. Creates an income entry in the accounts module.
     *
     * Idempotent: if an income entry already exists for this invoice
     * (reference_type = 'sales_invoice', reference_id = $salesInvoiceId),
     * the method returns without inserting a duplicate.
     *
     * @param int $salesInvoiceId
     * @return void
     */
    public function onSalesInvoicePaid(int $salesInvoiceId): void
    {
        try {
            // Fetch the sales invoice
            $invoice = $this->db->fetchOne(
                "SELECT * FROM sales_invoices WHERE id = ? LIMIT 1",
                [$salesInvoiceId]
            );

            if (!$invoice) {
                $this->logger->warning('CrossModuleDataFlowService::onSalesInvoicePaid — invoice not found', [
                    'sales_invoice_id' => $salesInvoiceId,
                ]);
                return;
            }

            // Only proceed if the invoice is fully paid
            if (($invoice['payment_status'] ?? '') !== 'paid') {
                $this->logger->info('CrossModuleDataFlowService::onSalesInvoicePaid — invoice not fully paid, skipping', [
                    'sales_invoice_id' => $salesInvoiceId,
                    'payment_status'   => $invoice['payment_status'] ?? 'unknown',
                ]);
                return;
            }

            // Idempotency check: skip if income entry already exists for this invoice
            $existing = $this->db->fetchOne(
                "SELECT id FROM income_entries
                 WHERE reference_type = 'sales_invoice'
                   AND reference_id   = ?
                 LIMIT 1",
                [$salesInvoiceId]
            );

            if ($existing) {
                $this->logger->info('CrossModuleDataFlowService::onSalesInvoicePaid — income entry already exists, skipping', [
                    'sales_invoice_id' => $salesInvoiceId,
                    'income_entry_id'  => $existing['id'],
                ]);
                return;
            }

            $invoiceNumber = $invoice['invoice_number'] ?? $salesInvoiceId;
            $amount        = (float)($invoice['total'] ?? 0);
            $branchId      = (int)($invoice['branch_id'] ?? 0);
            $recordedBy    = $_SESSION['user_id'] ?? null;

            // Determine payment date: use the most recent payment date if available
            $latestPayment = $this->db->fetchOne(
                "SELECT payment_date FROM sales_payments
                 WHERE invoice_id = ?
                 ORDER BY payment_date DESC
                 LIMIT 1",
                [$salesInvoiceId]
            );
            $paymentDate = $latestPayment
                ? date('Y-m-d', strtotime($latestPayment['payment_date']))
                : date('Y-m-d');

            // Insert income entry
            $this->db->insert('income_entries', [
                'branch_id'      => $branchId,
                'source'         => "Sales Invoice #{$invoiceNumber}",
                'amount'         => $amount,
                'reference_type' => 'sales_invoice',
                'reference_id'   => $salesInvoiceId,
                'income_date'    => $paymentDate,
                'recorded_by'    => $recordedBy,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            $this->logger->info('CrossModuleDataFlowService::onSalesInvoicePaid — income entry created', [
                'sales_invoice_id' => $salesInvoiceId,
                'invoice_number'   => $invoiceNumber,
                'amount'           => $amount,
                'payment_date'     => $paymentDate,
            ]);
        } catch (\Throwable $e) {
            $this->logError('CrossModuleDataFlowService::onSalesInvoicePaid failed', $e, [
                'sales_invoice_id' => $salesInvoiceId,
            ]);
        }
    }

    // ── 3. Ticket Assignment → Employee Portal Notification ───────

    /**
     * Called when a ticket is assigned to an employee.
     * Creates a notification record in employee_portal_notifications so
     * the employee portal surfaces the new ticket.
     *
     * Idempotent: if a notification for the same ticket + employee already
     * exists, the method returns without inserting a duplicate.
     *
     * @param int $ticketId         The support ticket ID
     * @param int $employeeUserId   The user ID of the assigned employee
     * @return void
     */
    public function onTicketAssigned(int $ticketId, int $employeeUserId): void
    {
        try {
            // Fetch the ticket
            $ticket = $this->db->fetchOne(
                "SELECT * FROM support_tickets WHERE id = ? LIMIT 1",
                [$ticketId]
            );

            if (!$ticket) {
                $this->logger->warning('CrossModuleDataFlowService::onTicketAssigned — ticket not found', [
                    'ticket_id'          => $ticketId,
                    'employee_user_id'   => $employeeUserId,
                ]);
                return;
            }

            // Fetch the employee record linked to the user
            $employee = $this->db->fetchOne(
                "SELECT * FROM employees WHERE user_id = ? LIMIT 1",
                [$employeeUserId]
            );

            if (!$employee) {
                $this->logger->warning('CrossModuleDataFlowService::onTicketAssigned — employee record not found for user', [
                    'ticket_id'        => $ticketId,
                    'employee_user_id' => $employeeUserId,
                ]);
                return;
            }

            $employeeId = (int)$employee['id'];

            // Idempotency check: skip if notification already exists for this ticket + employee
            $existing = $this->db->fetchOne(
                "SELECT id FROM employee_portal_notifications
                 WHERE employee_id    = ?
                   AND reference_type = 'ticket'
                   AND reference_id   = ?
                   AND type           = 'ticket_assigned'
                 LIMIT 1",
                [$employeeId, $ticketId]
            );

            if ($existing) {
                $this->logger->info('CrossModuleDataFlowService::onTicketAssigned — notification already exists, skipping', [
                    'ticket_id'   => $ticketId,
                    'employee_id' => $employeeId,
                ]);
                return;
            }

            $ticketNumber = $ticket['id'];
            $subject      = $ticket['subject'] ?? '';
            $message      = "New ticket assigned: #{$ticketNumber} - {$subject}";

            // Insert portal notification
            $this->db->insert('employee_portal_notifications', [
                'employee_id'    => $employeeId,
                'type'           => 'ticket_assigned',
                'reference_id'   => $ticketId,
                'reference_type' => 'ticket',
                'message'        => $message,
                'is_read'        => 0,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            $this->logger->info('CrossModuleDataFlowService::onTicketAssigned — notification created', [
                'ticket_id'   => $ticketId,
                'employee_id' => $employeeId,
                'message'     => $message,
            ]);
        } catch (\Throwable $e) {
            $this->logError('CrossModuleDataFlowService::onTicketAssigned failed', $e, [
                'ticket_id'        => $ticketId,
                'employee_user_id' => $employeeUserId,
            ]);
        }
    }
}
