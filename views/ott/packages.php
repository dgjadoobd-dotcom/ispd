<?php // views/ott/packages.php
// Variables: $packages (array), $providers (array), $internetPackages (array), $providerId (int|null)
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">OTT Packages</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i>
            <a href="<?= base_url('ott') ?>" style="color:var(--blue);">OTT</a> &rsaquo; Packages
        </div>
    </div>
    <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
    <a href="<?= base_url('ott/packages/create') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add Package
    </a>
    <?php endif; ?>
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

<!-- Provider Filter -->
<div class="card fade-in" style="padding:12px 18px;margin-bottom:16px;">
    <form method="GET" action="<?= base_url('ott/packages') ?>" style="display:flex;gap:10px;align-items:flex-end;">
        <div>
            <label class="form-label" style="font-size:12px;">Filter by Provider</label>
            <select name="provider_id" class="form-input" style="width:200px;padding:7px 10px;">
                <option value="">All Providers</option>
                <?php foreach ($providers as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (int)$providerId === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="<?= base_url('ott/packages') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-xmark"></i> Clear
        </a>
    </form>
</div>

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Package Name</th>
                <th>Provider</th>
                <th>Linked Internet Package</th>
                <th>Price</th>
                <th>Validity</th>
                <th>Auto-Renew</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($packages)): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:36px;color:var(--text2);">
                    <i class="fa-solid fa-box" style="font-size:24px;margin-bottom:8px;display:block;"></i>
                    No OTT packages configured.
                    <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
                    <a href="<?= base_url('ott/packages/create') ?>" style="color:var(--blue);">Add one</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: foreach ($packages as $pkg): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($pkg['name']) ?></div>
                    <?php if ($pkg['description']): ?>
                    <div style="font-size:11px;color:var(--text2);">
                        <?= htmlspecialchars(substr($pkg['description'], 0, 60)) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;"><?= htmlspecialchars($pkg['provider_name']) ?></td>
                <td style="font-size:12px;color:var(--text2);">
                    <?= $pkg['internet_package_name'] ? htmlspecialchars($pkg['internet_package_name']) : '<em>All packages</em>' ?>
                </td>
                <td style="font-weight:600;">
                    <?= $pkg['price'] > 0 ? '৳ ' . number_format((float)$pkg['price'], 2) : '<span style="color:var(--text2);">Free</span>' ?>
                </td>
                <td style="font-size:13px;"><?= (int)$pkg['validity_days'] ?> days</td>
                <td>
                    <?php if ($pkg['auto_renewal']): ?>
                    <span class="badge badge-blue" style="font-size:11px;"><i class="fa-solid fa-rotate"></i> Yes</span>
                    <?php else: ?>
                    <span class="badge badge-gray" style="font-size:11px;">No</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($pkg['is_active']): ?>
                    <span class="badge badge-green">Active</span>
                    <?php else: ?>
                    <span class="badge badge-gray">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
                        <a href="<?= base_url('ott/packages/edit/' . $pkg['id']) ?>"
                           class="btn btn-ghost" style="padding:4px 10px;font-size:12px;" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form method="POST" action="<?= base_url('ott/packages/delete/' . $pkg['id']) ?>"
                              style="display:inline;"
                              onsubmit="return confirm('Delete this OTT package? This cannot be undone if there are no active subscriptions.')">
                            <button type="submit" class="btn btn-ghost"
                                    style="padding:4px 10px;font-size:12px;color:var(--red);" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
