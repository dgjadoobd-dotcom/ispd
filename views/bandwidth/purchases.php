<?php
/**
 * Bandwidth Purchases View
 */
$purchases = $result['data'] ?? [];
$total = $result['total'] ?? 0;
?>
<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-shopping-cart" style="color:var(--blue);margin-right:10px;"></i>Bandwidth Purchases</h1></div>
</div>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><span style="font-weight:700;"><?= number_format($total) ?> purchase(s)</span></div>
    <?php if (empty($purchases)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);"><p>No purchases found.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Provider</th><th>Mbps</th><th>Price/Mbps</th><th>Total</th><th>Due Date</th><th>Date</th></tr></thead>
        <tbody>
            <?php foreach ($purchases as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['provider_name'] ?? '—') ?></td>
                <td><?= number_format($p['mbps_quantity']) ?></td>
                <td><?= number_format($p['price_per_mbps'], 2) ?></td>
                <td style="font-weight:600;"><?= number_format($p['total_amount'], 2) ?></td>
                <td><?= $p['due_date'] ? date('d M Y', strtotime($p['due_date'])) : '—' ?></td>
                <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>