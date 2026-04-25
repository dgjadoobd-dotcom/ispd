<?php
/**
 * Vendors List View
 */
$vendors = $result['data'] ?? [];
$total = $result['total'] ?? 0;
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-truck" style="color:var(--blue);margin-right:10px;"></i>Vendors</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Vendors</span>
        </div>
    </div>
    <?php if (PermissionHelper::hasPermission('purchase.create')): ?>
    <a href="<?= base_url('purchases/vendors/create') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add Vendor
    </a>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']) ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in" style="padding:16px;margin-bottom:20px;">
    <form method="GET" action="<?= base_url('purchases/vendors') ?>" style="display:flex;gap:12px;">
        <input type="text" name="search" class="form-input" placeholder="Search vendors..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i></button>
        <a href="<?= base_url('purchases/vendors') ?>" class="btn btn-ghost btn-sm">Clear</a>
    </form>
</div>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <span style="font-weight:700;"><?= number_format($total) ?> vendor<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($vendors)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-truck" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
        <p>No vendors found.</p>
        <?php if (PermissionHelper::hasPermission('purchase.create')): ?>
        <a href="<?= base_url('purchases/vendors/create') ?>" class="btn btn-primary btn-sm" style="margin-top:12px;">Add First Vendor</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Vendor Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendors as $v): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($v['name']) ?></td>
                    <td><?= htmlspecialchars($v['contact_person'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($v['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($v['email'] ?? '—') ?></td>
                    <td>
                        <?php if (PermissionHelper::hasPermission('purchase.edit')): ?>
                        <a href="<?= base_url('purchases/vendors/edit/' . $v['id']) ?>" class="btn btn-ghost btn-xs"><i class="fa-solid fa-pen"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>