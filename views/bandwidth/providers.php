<?php
/**
 * Bandwidth Providers List View
 */
$providers = $result['data'] ?? [];
$total = $result['total'] ?? 0;
?>

<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-network-wired" style="color:var(--blue);margin-right:10px;"></i>Bandwidth Providers</h1></div>
    <?php if (PermissionHelper::hasPermission('bandwidth.create')): ?>
    <a href="<?= base_url('bandwidth/providers/create') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Provider</a>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['success'])): ?><div class="alert-success"><?= $_SESSION['success'] ?></div><?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?><div class="alert-error"><?= $_SESSION['error'] ?></div><?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><span style="font-weight:700;"><?= number_format($total) ?> provider(s)</span></div>
    <?php if (empty($providers)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);"><p>No providers found.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Capacity (Mbps)</th><th>Price/Mbps</th><th>Contact</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($providers as $p): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
                <td><?= number_format($p['bandwidth_capacity']) ?></td>
                <td><?= number_format($p['price_per_mbps'], 2) ?></td>
                <td><?= htmlspecialchars($p['phone'] ?? '') ?></td>
                <td>
                    <a href="<?= base_url('bandwidth/providers/edit/' . $p['id']) ?>" class="btn btn-ghost btn-xs">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>