<?php
/**
 * Invoice Print View
 * Requirement 5.5: PDF invoice generation
 */
?>

<div style="max-width:800px;margin:0 auto;padding:40px;font-family:Arial,sans-serif;background:#fff;">
    <div style="text-align:center;margin-bottom:30px;">
        <h1 style="font-size:24px;margin:0;">INVOICE</h1>
        <p style="font-size:14px;color:#666;margin:5px 0 0;"><?= htmlspecialchars($invoice['invoice_number']) ?></p>
    </div>

    <div style="display:flex;justify-content:space-between;margin-bottom:30px;">
        <div>
            <strong>Bill To:</strong>
            <p style="margin:5px 0;"><?= htmlspecialchars($invoice['customer_name']) ?></p>
            <p style="margin:0;font-size:12px;color:#666;"><?= htmlspecialchars($invoice['customer_code']) ?></p>
            <p style="margin:0;font-size:12px;color:#666;"><?= htmlspecialchars($invoice['customer_phone'] ?? '') ?></p>
            <p style="margin:0;font-size:12px;color:#666;"><?= htmlspecialchars($invoice['customer_address'] ?? '') ?></p>
        </div>
        <div style="text-align:right;">
            <p><strong>Date:</strong> <?= date('d M Y', strtotime($invoice['created_at'])) ?></p>
            <p><strong>Due:</strong> <?= date('d M Y', strtotime($invoice['created_at'] . ' +30 days')) ?></p>
            <p><strong>Status:</strong> <?= ucfirst($invoice['payment_status']) ?></p>
        </div>
    </div>

    <table style="width:100%;border-collapse:collapse;margin-bottom:30px;">
        <thead>
            <tr style="background:#f5f5f5;">
                <th style="padding:10px;text-align:left;border-bottom:1px solid #ddd;">Description</th>
                <th style="padding:10px;text-align:right;border-bottom:1px solid #ddd;">Qty</th>
                <th style="padding:10px;text-align:right;border-bottom:1px solid #ddd;">Unit Price</th>
                <th style="padding:10px;text-align:right;border-bottom:1px solid #ddd;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice['items'] as $item): ?>
            <tr>
                <td style="padding:10px;border-bottom:1px solid #ddd;"><?= htmlspecialchars($item['description']) ?></td>
                <td style="padding:10px;text-align:right;border-bottom:1px solid #ddd;"><?= number_format($item['quantity'], 2) ?></td>
                <td style="padding:10px;text-align:right;border-bottom:1px solid #ddd;"><?= number_format($item['unit_price'], 2) ?></td>
                <td style="padding:10px;text-align:right;border-bottom:1px solid #ddd;"><?= number_format($item['line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="display:flex;justify-content:flex-end;">
        <table style="width:250px;">
            <tr>
                <td style="padding:8px;text-align:left;border-bottom:1px solid #ddd;">Subtotal</td>
                <td style="padding:8px;text-align:right;border-bottom:1px solid #ddd;"><?= number_format($invoice['subtotal'], 2) ?></td>
            </tr>
            <?php if ($invoice['discount'] > 0): ?>
            <tr>
                <td style="padding:8px;text-align:left;border-bottom:1px solid #ddd;">Discount</td>
                <td style="padding:8px;text-align:right;border-bottom:1px solid #ddd;">-<?= number_format($invoice['discount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding:8px;text-align:left;border-bottom:1px solid #ddd;">VAT (15%)</td>
                <td style="padding:8px;text-align:right;border-bottom:1px solid #ddd;"><?= number_format($invoice['vat'], 2) ?></td>
            </tr>
            <?php if ($invoice['otc_amount'] > 0): ?>
            <tr>
                <td style="padding:8px;text-align:left;border-bottom:1px solid #ddd;">OTC</td>
                <td style="padding:8px;text-align:right;border-bottom:1px solid #ddd;"><?= number_format($invoice['otc_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr style="font-weight:bold;font-size:18px;">
                <td style="padding:12px 8px;">TOTAL</td>
                <td style="padding:12px 8px;text-align:right;"><?= number_format($invoice['total'], 2) ?></td>
            </tr>
            <tr style="color:#15803d;">
                <td style="padding:8px;text-align:left;border-bottom:1px solid #ddd;">Paid</td>
                <td style="padding:8px;text-align:right;border-bottom:1px solid #ddd;"><?= number_format($invoice['paid_amount'], 2) ?></td>
            </tr>
            <tr style="color:#dc2626;font-weight:bold;">
                <td style="padding:8px;text-align:left;">DUE</td>
                <td style="padding:8px;text-align:right;"><?= number_format($invoice['due_amount'], 2) ?></td>
            </tr>
        </table>
    </div>

    <?php if (!empty($invoice['notes'])): ?>
    <div style="margin-top:40px;padding-top:20px;border-top:1px solid #ddd;">
        <strong>Notes:</strong>
        <p style="font-size:12px;color:#666;margin-top:5px;"><?= htmlspecialchars($invoice['notes']) ?></p>
    </div>
    <?php endif; ?>

    <div style="margin-top:50px;text-align:center;font-size:12px;color:#666;">
        <p>Thank you for your business!</p>
    </div>
</div>