<?php // views/branches/index.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Branches</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-code-branch" style="color:var(--blue)"></i> Branch Management</div>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if (PermissionHelper::hasPermission('branches.create')): ?>
        <a href="<?= base_url('branches/create') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Branch</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.08);">
    <span style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></span>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<?php
$total    = count($branches);
$active   = count(array_filter($branches, fn($b) => (int)$b['is_active'] === 1));
$inactive = $total - $active;
?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;" class="fade-in">
    <div class="card stat-card" style="padding:14px;">
        <div class="stat-label">Total Branches</div>
        <div class="stat-value" style="font-size:22px;"><?= $total ?></div>
    </div>
    <div class="card stat-card" style="padding:14px;">
        <div class="stat-label">Active</div>
        <div class="stat-value" style="font-size:22px;color:var(--green);"><?= $active ?></div>
    </div>
    <div class="card stat-card" style="padding:14px;">
        <div class="stat-label">Inactive</div>
        <div class="stat-value" style="font-size:22px;color:var(--red);"><?= $inactive ?></div>
    </div>
</div>

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Branch Name / Code</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Manager</th>
                <th>Customers</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($branches)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text2);">No branches found. Add your first branch.</td></tr>
            <?php else: foreach ($branches as $branch): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($branch['name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);font-family:monospace;"><?= htmlspecialchars($branch['code'] ?? '—') ?></div>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($branch['address'] ?? '—') ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($branch['phone'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($branch['manager'] ?? '—') ?></td>
                <td style="font-weight:600;"><?= (int)($branch['customer_count'] ?? 0) ?></td>
                <td>
                    <?php if ((int)$branch['is_active'] === 1): ?>
                        <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Active</span>
                    <?php else: ?>
                        <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="<?= base_url('branches/view/' . $branch['id']) ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if (PermissionHelper::hasPermission('branches.edit')): ?>
                        <a href="<?= base_url('branches/edit/' . $branch['id']) ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php if ((int)$branch['is_active'] === 1): ?>
                        <form method="POST" action="<?= base_url('branches/deactivate/' . $branch['id']) ?>" style="display:inline;" onsubmit="return confirm('Deactivate this branch?')">
                            <button type="submit" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;color:var(--red);" title="Deactivate"><i class="fa-solid fa-ban"></i></button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="<?= base_url('branches/activate/' . $branch['id']) ?>" style="display:inline;" onsubmit="return confirm('Activate this branch?')">
                            <button type="submit" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;color:var(--green);" title="Activate"><i class="fa-solid fa-circle-check"></i></button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
