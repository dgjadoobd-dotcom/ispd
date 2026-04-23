<?php // views/mac-reseller/billing.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Daily Billing — <?= htmlspecialchars($reseller['business_name']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('mac-resellers') ?>" style="color:var(--blue);text-decoration:none;">MAC Resellers</a> ›
            <a href="<?= base_url("mac-resellers/view/{$reseller['id']}") ?>" style="color:var(--blue);text-decoration:none;"><?= htmlspecialchars($reseller['business_name']) ?></a>
            › Billing
        </div>
    </div>
    <a href="<?= base_url("mac-resellers/view/{$reseller['id']}") ?>" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:10px;color:var(--green);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['success']) ?>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;color:var(--red);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['error']) ?>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<!-- Date picker + Generate -->
<div style="display:grid;grid-template-columns:1fr auto;gap:12px;margin-bottom:16px;align-items:end;" class="fade-in">
    <form method="GET" style="display:flex;gap:10px;align-items:center;">
        <label class="form-label" style="margin:0;white-space:nowrap;">Billing Date:</label>
        <input type="date" name="date" class="form-input" value="<?= htmlspecialchars($date) ?>" style="width:180px;">
        <button type="submit" class="btn btn-ghost"><i class="fa-solid fa-search"></i> View</button>
    </form>
    <form method="POST" action="<?= base_url("mac-resellers/{$reseller['id']}/billing/generate") ?>">
        <input type="hidden" name="billing_date" value="<?= htmlspecialchars($date) ?>">
        <button type="submit" class="btn btn-primary" onclick="return confirm('Generate billing for all active clients on <?= htmlspecialchars($date) ?>?')">
            <i class="fa-solid fa-bolt"></i> Generate Billing for <?= htmlspecialchars($date) ?>
        </button>
    </form>
</div>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;" class="fade-in">
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Total Records</div>
        <div style="font-size:26px;font-weight:900;color:var(--blue);"><?= $summary['total_records'] ?? 0 ?></div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Paid</div>
        <div style="font-size:26px;font-weight:900;color:var(--green);"><?= $summary['paid_count'] ?? 0 ?></div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Total Billed</div>
        <div style="font-size:22px;font-weight:900;color:var(--yellow);">৳<?= number_format($summary['total_billed'] ?? 0, 2) ?></div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Collected</div>
        <div style="font-size:22px;font-weight:900;color:var(--green);">৳<?= number_format($summary['total_collected'] ?? 0, 2) ?></div>
    </div>
</div>

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr><th>Client</th><th>MAC Address</th><th>Tariff</th><th>Amount</th><th>Status</th><th>Paid At</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($bills)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text2);">
                <i class="fa-solid fa-file-invoice-dollar" style="font-size:32px;display:block;margin-bottom:10px;opacity:.3;"></i>
                No billing records for <?= htmlspecialchars($date) ?>. Click "Generate Billing" to create them.
            </td></tr>
            <?php else: foreach ($bills as $b): ?>
            <tr>
                <td>
                    <div style="font-weight:700;"><?= htmlspecialchars($b['full_name']) ?></div>
                </td>
                <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($b['mac_address']) ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($b['tariff_name'] ?? '—') ?></td>
                <td style="font-weight:700;color:var(--green);">৳<?= number_format($b['amount'], 2) ?></td>
                <td>
                    <span class="badge <?= $b['status'] === 'paid' ? 'badge-green' : 'badge-yellow' ?>">
                        <?= ucfirst($b['status']) ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--text2);">
                    <?= $b['paid_at'] ? date('d M Y H:i', strtotime($b['paid_at'])) : '—' ?>
                </td>
                <td>
                    <?php if ($b['status'] !== 'paid'): ?>
                    <form method="POST" action="<?= base_url("mac-resellers/billing/pay/{$b['id']}") ?>" style="display:inline;">
                        <button type="submit" class="btn btn-success btn-sm" title="Mark as Paid">
                            <i class="fa-solid fa-check"></i> Pay
                        </button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:11px;color:var(--text2);">Paid</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
