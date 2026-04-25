<?php
/**
 * Invoice Detail View
 * Requirements 5.4, 5.5, 5.6
 */
$statusColors = [
    'unpaid'    => 'badge-red',
    'partial'  => 'badge-yellow',
    'paid'     => 'badge-green',
    'cancelled' => 'badge-gray',
];
$typeColors = [
    'installation' => 'badge-purple',
    'product'     => 'badge-blue',
    'service'    => 'badge-green',
];
$canEdit = PermissionHelper::hasPermission('sales.edit');
$canPay = PermissionHelper::hasPermission('sales.payment');
$canCancel = PermissionHelper::hasPermission('sales.cancel');
$isPaidOrCancelled = in_array($invoice['payment_status'], ['paid', 'cancelled']);
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-file-invoice" style="color:var(--blue);margin-right:10px;"></i>
            Invoice <?= htmlspecialchars($invoice['invoice_number']) ?>
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('sales/invoices') ?>">Invoices</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span><?= htmlspecialchars($invoice['invoice_number']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="<?= base_url('sales/print/' . $invoice['id']) ?>" class="btn btn-ghost btn-sm" target="_blank">
            <i class="fa-solid fa-print"></i> Print
        </a>
        <?php if ($canEdit && !$isPaidOrCancelled): ?>
        <a href="<?= base_url('sales/edit/' . $invoice['id']) ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-pen"></i> Edit
        </a>
        <?php endif; ?>
        <a href="<?= base_url('sales/invoices') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php foreach (['success','error'] as $msgType): ?>
<?php if (!empty($_SESSION[$msgType])): ?>
<?php
$styles = [
    'success' => 'background:#dcfce7;border-color:#86efac;color:#15803d;',
    'error'   => 'background:#fee2e2;border-color:#fecaca;color:#b91c1c;',
];
$icons = ['success' => 'circle-check', 'error' => 'circle-xmark'];
?>
<div style="<?= $styles[$msgType] ?>border:1px solid;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-<?= $icons[$msgType] ?>"></i> <?= htmlspecialchars($_SESSION[$msgType]) ?>
</div>
<?php unset($_SESSION[$msgType]); endif; endforeach; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;">

    <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card fade-in">
            <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h2 style="font-size:17px;font-weight:700;"><?= htmlspecialchars($invoice['customer_name']) ?></h2>
                    <div style="font-size:13px;color:var(--text2);"><?= htmlspecialchars($invoice['customer_code']) ?></div>
                    <div style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($invoice['customer_phone'] ?? '') ?></div>
                </div>
                <div style="text-align:right;">
                    <span class="badge <?= $statusColors[$invoice['payment_status']] ?? 'badge-gray' ?>">
                        <?= ucfirst($invoice['payment_status']) ?>
                    </span>
                    <span class="badge <?= $typeColors[$invoice['invoice_type']] ?? 'badge-gray' ?>" style="margin-left:6px;">
                        <?= ucfirst($invoice['invoice_type']) ?>
                    </span>
                </div>
            </div>
            <div style="padding:20px 24px;">
                <table style="width:100%;">
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid var(--border);">Subtotal</td>
                        <td style="text-align:right;padding:8px 0;border-bottom:1px solid var(--border);"><?= number_format($invoice['subtotal'], 2) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid var(--border);">Discount</td>
                        <td style="text-align:right;padding:8px 0;border-bottom:1px solid var(--border);">-<?= number_format($invoice['discount'], 2) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid var(--border);">VAT (15%)</td>
                        <td style="text-align:right;padding:8px 0;border-bottom:1px solid var(--border);"><?= number_format($invoice['vat'], 2) ?></td>
                    </tr>
                    <?php if ($invoice['otc_amount'] > 0): ?>
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid var(--border);">OTC</td>
                        <td style="text-align:right;padding:8px 0;border-bottom:1px solid var(--border);"><?= number_format($invoice['otc_amount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr style="font-weight:700;font-size:18px;">
                        <td style="padding:12px 0;">Total</td>
                        <td style="text-align:right;padding:12px 0;"><?= number_format($invoice['total'], 2) ?></td>
                    </tr>
                    <tr style="color:#15803d;">
                        <td style="padding:8px 0;border-top:1px solid var(--border);">Paid</td>
                        <td style="text-align:right;padding:8px 0;border-top:1px solid var(--border);"><?= number_format($invoice['paid_amount'], 2) ?></td>
                    </tr>
                    <tr style="color:#dc2626;font-weight:700;">
                        <td style="padding:8px 0;">Due</td>
                        <td style="text-align:right;padding:8px 0;"><?= number_format($invoice['due_amount'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Line Items</h3>
            </div>
            <div style="padding:16px 20px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= number_format($item['quantity'], 2) ?></td>
                            <td><?= number_format($item['unit_price'], 2) ?></td>
                            <td><?= number_format($item['line_total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Notes</h3>
            </div>
            <div style="padding:16px 20px;font-size:13px;white-space:pre-wrap;"><?= htmlspecialchars($invoice['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px;">
        <?php if ($canPay && !$isPaidOrCancelled && $invoice['due_amount'] > 0): ?>
        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Record Payment</h3>
            </div>
            <form method="POST" action="<?= base_url('sales/payment/' . $invoice['id']) ?>">
                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-input" value="<?= $invoice['due_amount'] ?>" min="0.01" step="0.01" required>
                    </div>
                    <div>
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-input">
                            <?php foreach (['cash' => 'Cash', 'mobile_banking' => 'Mobile Banking', 'bank_transfer' => 'Bank Transfer', 'online' => 'Online'] as $val => $label): ?>
                            <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-input" placeholder="Transaction ref (optional)">
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($canCancel && !$isPaidOrCancelled): ?>
        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;color:#dc2626;">Cancel Invoice</h3>
            </div>
            <form method="POST" action="<?= base_url('sales/cancel/' . $invoice['id']) ?>">
                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-input" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-ghost" style="color:#dc2626;border-color:#dc2626;" onclick="return confirm('Are you sure? This cannot be undone.');">
                        <i class="fa-solid fa-times"></i> Cancel Invoice
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Payment History</h3>
            </div>
            <?php if (empty($payments)): ?>
            <div style="padding:20px;text-align:center;color:var(--text2);font-size:13px;">
                No payments recorded.
            </div>
            <?php else: ?>
            <div style="padding:16px 20px;">
                <?php foreach ($payments as $pmt): ?>
                <div style="padding:12px 0;border-bottom:1px solid var(--border);">
                    <div style="font-weight:600;font-size:13px;"><?= number_format($pmt['amount'], 2) ?></div>
                    <div style="font-size:12px;color:var(--text2);">
                        <?= ucfirst($pmt['payment_method']) ?> • <?= date('d M Y H:i', strtotime($pmt['created_at'])) ?>
                    </div>
                    <div style="font-size:11px;color:var(--text2);">by <?= htmlspecialchars($pmt['collected_by_name'] ?? '—') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Details</h3>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                <div>
                    <div style="font-size:11px;color:var(--text2);">Invoice #</div>
                    <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2);">Branch</div>
                    <div style="font-size:13px;"><?= htmlspecialchars($invoice['branch_name'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2);">Created By</div>
                    <div style="font-size:13px;"><?= htmlspecialchars($invoice['created_by_name'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2);">Created</div>
                    <div style="font-size:13px;"><?= date('d M Y H:i', strtotime($invoice['created_at'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>