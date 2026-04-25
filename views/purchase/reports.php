<?php
/**
 * Purchase Reports View
 */
$summary = $report['summary'] ?? [];
$byVendor = $report['by_vendor'] ?? [];
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-chart-pie" style="color:var(--blue);margin-right:10px;"></i>Purchase Reports</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Reports</span>
        </div>
    </div>
    <a href="<?= base_url('purchases/bills') ?>" class="btn btn-ghost btn-sm">Back</a>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:24px;font-weight:700;color:var(--blue);"><?= number_format($summary['total_bills'] ?? 0) ?></div><div style="font-size:12px;color:var(--text2);">Total Bills</div></div>
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:24px;font-weight:700;"><?= number_format($summary['total_amount'] ?? 0, 2) ?></div><div style="font-size:12px;color:var(--text2);">Total Amount</div></div>
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:24px;font-weight:700;color:#15803d;"><?= number_format($summary['total_paid'] ?? 0, 2) ?></div><div style="font-size:12px;color:var(--text2);">Total Paid</div></div>
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:24px;font-weight:700;color:#dc2626;"><?= number_format($summary['total_due'] ?? 0, 2) ?></div><div style="font-size:12px;color:var(--text2);">Total Due</div></div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:20px;font-weight:700;color:#dc2626;"><?= number_format($summary['pending_count'] ?? 0) ?></div><div style="font-size:12px;color:var(--text2);">Pending</div></div>
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:20px;font-weight:700;color:#eab308;"><?= number_format($summary['partial_count'] ?? 0) ?></div><div style="font-size:12px;color:var(--text2);">Partial</div></div>
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:20px;font-weight:700;color:#15803d;"><?= number_format($summary['paid_count'] ?? 0) ?></div><div style="font-size:12px;color:var(--text2);">Paid</div></div>
</div>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><h3 style="font-size:14px;font-weight:700;">By Vendor</h3></div>
    <?php if(empty($byVendor)): ?><div style="padding:32px;text-align:center;color:var(--text2);">No data</div>
    <?php else: ?><div style="padding:16px 20px;"><?php foreach($byVendor as $v): ?>
        <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;"><span style="font-weight:600;"><?= htmlspecialchars($v['name']) ?></span><span><?= number_format($v['total'], 2) ?></span></div>
            <div style="height:8px;background:var(--bg2);border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:<?= ($summary['total_amount'] ?? 0) > 0 ? ($v['total'] / ($summary['total_amount'] ?? 1)) * 100 : 0 ?>%;background:var(--blue);border-radius:4px;"></div>
            </div>
            <div style="font-size:11px;color:var(--text2);margin-top:4px;"><?= $v['bill_count'] ?> bill(s)</div>
        </div>
    <?php endforeach; ?></div><?php endif; ?>
</div>