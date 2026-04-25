<?php
/**
 * Bandwidth Resellers List View
 */
$resellers = $result['data'] ?? [];
$total = $result['total'] ?? 0;
?>

<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-users" style="color:var(--blue);margin-right:10px;"></i>Resellers</h1></div>
    <?php if (PermissionHelper::hasPermission('bandwidth.create')): ?>
    <a href="<?= base_url('bandwidth/resellers/create') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Reseller</a>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['success'])): ?><div class="alert-success"><?= $_SESSION['success'] ?></div><?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?><div class="alert-error"><?= $_SESSION['error'] ?></div><?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><span style="font-weight:700;"><?= number_format($total) ?> reseller(s)</span></div>
    <?php if (empty($resellers)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);"><p>No resellers found.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Provider</th><th>Credit Limit</th><th>Price/Mbps</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($resellers as $r): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['provider_name'] ?? '—') ?></td>
                <td><?= number_format($r['credit_limit'], 2) ?></td>
                <td><?= number_format($r['price_per_mbps'], 2) ?></td>
                <td><span class="badge <?= $r['is_active'] ? 'badge-green' : 'badge-gray' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td><a href="<?= base_url('bandwidth/resellers/edit/' . $r['id']) ?>" class="btn btn-ghost btn-xs">Edit</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>