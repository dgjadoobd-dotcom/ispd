<?php
/**
 * Bandwidth Invoices View
 */
$invoices = $result['data'] ?? [];
$total = $result['total'] ?? 0;
?>
<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-file-invoice" style="color:var(--blue);margin-right:10px;"></i>Reseller Invoices</h1></div>
</div>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><span style="font-weight:700;"><?= number_format($total) ?> invoice(s)</span></div>
    <?php if (empty($invoices)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);"><p>No invoices found.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Invoice #</th><th>Reseller</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($invoices as $i): ?>
            <tr>
                <td style="font-weight:600;color:var(--blue);"><?= htmlspecialchars($i['invoice_number']) ?></td>
                <td><?= htmlspecialchars($i['reseller_name'] ?? '—') ?></td>
                <td style="font-weight:600;"><?= number_format($i['total_amount'], 2) ?></td>
                <td><span class="badge <?= $i['payment_status'] === 'paid' ? 'badge-green' : ($i['payment_status'] === 'partial' ? 'badge-yellow' : 'badge-red') ?>"><?= ucfirst($i['payment_status']) ?></span></td>
                <td><?= date('d M Y', strtotime($i['created_at'])) ?></td>
                <td><a href="<?= base_url('bandwidth/invoices/view/' . $i['id']) ?>" class="btn btn-ghost btn-xs">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>