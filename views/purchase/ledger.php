<?php
/**
 * Vendor Ledger View
 */
$summary = $ledger['summary'] ?? [];
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-book" style="color:var(--blue);margin-right:10px;"></i>Vendor Ledger</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Ledger</span>
        </div>
    </div>
    <a href="<?= base_url('purchases/bills') ?>" class="btn btn-ghost btn-sm">Back</a>
</div>

<div class="card fade-in" style="padding:16px;margin-bottom:20px;">
    <form method="GET" action="<?= base_url('purchases/ledger') ?>" style="display:flex;gap:12px;align-items:end;">
        <select name="vendor_id" class="form-input">
            <option value="">— Select Vendor —</option>
            <?php foreach ($vendors as $v): ?>
            <option value="<?= $v['id'] ?>" <?= (int)($_GET['vendor_id'] ?? 0) === $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">View</button>
    </form>
</div>

<?php if (!empty($ledger['vendor'])): ?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:24px;font-weight:700;color:var(--blue);"><?= number_format($summary['total_bills'] ?? 0, 2) ?></div><div style="font-size:12px;color:var(--text2);">Total Bills</div></div>
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:24px;font-weight:700;color:#15803d;"><?= number_format($summary['total_paid'] ?? 0, 2) ?></div><div style="font-size:12px;color:var(--text2);">Total Paid</div></div>
    <div class="card fade-in" style="padding:20px;text-align:center;"><div style="font-size:24px;font-weight:700;color:#dc2626;"><?= number_format($summary['total_due'] ?? 0, 2) ?></div><div style="font-size:12px;color:var(--text2);">Total Due</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card fade-in">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><h3 style="font-size:14px;font-weight:700;">Bills</h3></div>
        <div style="padding:16px 20px;"><?php foreach($ledger['bills'] as $b): ?>
            <div style="padding:12px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;">
                <div><div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($b['bill_number']) ?></div><div style="font-size:11px;color:var(--text2);"><?= date('d M Y', strtotime($b['created_at'])) ?></div></div>
                <div style="text-align:right;"><div><?= number_format($b['total'], 2) ?></div><div style="font-size:11px;color:var(--text2);"><?= ucfirst($b['payment_status']) ?></div></div>
            </div>
        <?php endforeach; ?></div>
    </div>
    <div class="card fade-in">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><h3 style="font-size:14px;font-weight:700;">Payments</h3></div>
        <div style="padding:16px 20px;"><?php foreach($ledger['payments'] as $p): ?>
            <div style="padding:12px 0;border-bottom:1px solid var(--border);">
                <div style="font-weight:600;font-size:13px;color:#15803d;">+<?= number_format($p['amount'], 2) ?></div>
                <div style="font-size:11px;color:var(--text2);"><?= date('d M Y', strtotime($p['created_at'])) ?></div>
            </div>
        <?php endforeach; ?></div>
    </div>
</div>
<?php else: ?>
<div class="card fade-in" style="padding:48px;text-align:center;color:var(--text2);">
    <i class="fa-solid fa-book" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
    <p>Select a vendor to view ledger.</p>
</div>
<?php endif; ?>