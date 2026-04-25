<?php // views/ott/package-form.php
// Variables: $package (array|null), $providers (array), $internetPackages (array)
$isEdit = !empty($package);
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Edit OTT Package' : 'Add OTT Package' ?></h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i>
            <a href="<?= base_url('ott') ?>" style="color:var(--blue);">OTT</a> &rsaquo;
            <a href="<?= base_url('ott/packages') ?>" style="color:var(--blue);">Packages</a> &rsaquo;
            <?= $isEdit ? 'Edit' : 'New' ?>
        </div>
    </div>
    <a href="<?= base_url('ott/packages') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<div class="card fade-in" style="max-width:640px;padding:24px;">
    <form method="POST" action="<?= $isEdit
        ? base_url('ott/packages/update/' . $package['id'])
        : base_url('ott/packages/store') ?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:4px;">

            <div>
                <label class="form-label">Provider <span style="color:var(--red);">*</span></label>
                <select name="provider_id" class="form-input" required>
                    <option value="">— Select Provider —</option>
                    <?php foreach ($providers as $p): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= (int)($package['provider_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label">Linked Internet Package</label>
                <select name="package_id" class="form-input">
                    <option value="">— All Packages —</option>
                    <?php foreach ($internetPackages as $ip): ?>
                    <option value="<?= $ip['id'] ?>"
                        <?= (int)($package['package_id'] ?? 0) === (int)$ip['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ip['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:var(--text2);margin-top:3px;">
                    Leave blank to make available for all internet packages.
                </div>
            </div>

            <div style="grid-column:1/-1;">
                <label class="form-label">Package Name <span style="color:var(--red);">*</span></label>
                <input type="text" name="name" class="form-input" required
                       value="<?= htmlspecialchars($package['name'] ?? '') ?>"
                       placeholder="e.g. Netflix Standard, Hoichoi Monthly">
            </div>

            <div style="grid-column:1/-1;">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="2"
                          placeholder="Brief description of this OTT bundle..."
                          style="resize:vertical;"><?= htmlspecialchars($package['description'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="form-label">Price (৳)</label>
                <input type="number" name="price" class="form-input" min="0" step="0.01"
                       value="<?= number_format((float)($package['price'] ?? 0), 2, '.', '') ?>"
                       placeholder="0.00">
                <div style="font-size:11px;color:var(--text2);margin-top:3px;">
                    Additional charge for this OTT bundle. Enter 0 for free.
                </div>
            </div>

            <div>
                <label class="form-label">Validity (days) <span style="color:var(--red);">*</span></label>
                <input type="number" name="validity_days" class="form-input" min="1" max="3650" required
                       value="<?= (int)($package['validity_days'] ?? 30) ?>">
            </div>

            <div>
                <label class="form-label">Auto-Renewal</label>
                <select name="auto_renewal" class="form-input">
                    <option value="1" <?= ($package['auto_renewal'] ?? 1) ? 'selected' : '' ?>>Enabled</option>
                    <option value="0" <?= !($package['auto_renewal'] ?? 1) ? 'selected' : '' ?>>Disabled</option>
                </select>
            </div>

            <?php if ($isEdit): ?>
            <div>
                <label class="form-label">Status</label>
                <select name="is_active" class="form-input">
                    <option value="1" <?= ($package['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !($package['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <?php endif; ?>

        </div>

        <div style="display:flex;gap:10px;margin-top:20px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid <?= $isEdit ? 'fa-save' : 'fa-plus' ?>"></i>
                <?= $isEdit ? 'Save Changes' : 'Create Package' ?>
            </button>
            <a href="<?= base_url('ott/packages') ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
