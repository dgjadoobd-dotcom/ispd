<?php
/**
 * Sales Reports View
 * Requirement 5.5: payment tracking dashboard
 */
$summary = $report['summary'] ?? [];
$byType = $report['by_type'] ?? [];
$recent = $report['recent_invoices'] ?? [];
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-chart-pie" style="color:var(--blue);margin-right:10px;"></i>Sales Reports</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Sales Reports</span>
        </div>
    </div>
    <a href="<?= base_url('sales/invoices') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Invoices
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:var(--blue);"><?= number_format($summary['total_invoices'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Total Invoices</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:#15803d;"><?= number_format($summary['total_amount'] ?? 0, 2) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Total Amount</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:#2563eb;"><?= number_format($summary['total_paid'] ?? 0, 2) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Total Paid</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:#dc2626;"><?= number_format($summary['total_due'] ?? 0, 2) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Total Due</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:20px;font-weight:700;color:#dc2626;"><?= number_format($summary['unpaid_count'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Unpaid</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:20px;font-weight:700;color:#eab308;"><?= number_format($summary['partial_count'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Partial</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:20px;font-weight:700;color:#15803d;"><?= number_format($summary['paid_count'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Paid</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card fade-in">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:700;">By Invoice Type</h3>
        </div>
        <?php if (empty($byType)): ?>
        <div style="padding:32px;text-align:center;color:var(--text2);">No data</div>
        <?php else: ?>
        <div style="padding:16px 20px;">
            <?php foreach ($byType as $t): ?>
            <div style="margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-weight:600;"><?= ucfirst($t['invoice_type']) ?></span>
                    <span><?= number_format($t['total'], 2) ?></span>
                </div>
                <div style="height:8px;background:var(--bg2);border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:<?= ($summary['total_amount'] ?? 0) > 0 ? ($t['total'] / ($summary['total_amount'] ?? 1)) * 100 : 0 ?>%;background:var(--blue);border-radius:4px;"></div>
                </div>
                <div style="font-size:11px;color:var(--text2);margin-top:4px;"><?= $t['count'] ?> invoice(s)</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card fade-in">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:700;">Recent Invoices</h3>
        </div>
        <?php if (empty($recent)): ?>
        <div style="padding:32px;text-align:center;color:var(--text2);">No invoices</div>
        <?php else: ?>
        <div style="padding:16px 20px;">
            <?php foreach ($recent as $r): ?>
            <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($r['invoice_number']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($r['customer_name']) ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:600;"><?= number_format($r['total'], 2) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= ucfirst($r['payment_status']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>