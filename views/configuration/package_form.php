<?php
/**
 * Package Form View
 */
$package = $package ?? null;
$profiles = $profiles ?? [];
$isEdit = !empty($package);
?>

<div class="page-header fade-in">
    <div>
        <a href="<?= base_url('configuration') ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <h1 class="page-title"><?= $isEdit ? 'Edit Package' : 'Add Package' ?></h1>
    </div>
</div>

<div class="card fade-in" style="max-width:600px;">
    <form method="POST" action="<?= base_url('configuration/packages/' . ($isEdit ? 'update/' . $package['id'] : 'store')) ?>">
        <?= csrf_field() ?>
        
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Package Name *</label>
            <input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($package['name'] ?? '') ?>" placeholder="e.g., 10Mbps Unlimited">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Package Code *</label>
            <input type="text" name="code" class="form-input" required value="<?= htmlspecialchars($package['code'] ?? '') ?>" placeholder="e.g., PKG-10M">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Price (<?= get_settings('currency_symbol', '$') ?>) *</label>
            <input type="number" name="price" class="form-input" required step="0.01" value="<?= $package['price'] ?? '' ?>" placeholder="0.00">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Download Speed (Kbps) *</label>
            <input type="number" name="download_speed" class="form-input" required value="<?= $package['download_speed'] ?? '' ?>" placeholder="10240">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Upload Speed (Kbps) *</label>
            <input type="number" name="upload_speed" class="form-input" required value="<?= $package['upload_speed'] ?? '' ?>" placeholder="5120">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Data Limit (GB) *</label>
            <input type="number" name="data_limit" class="form-input" required value="<?= $package['data_limit'] ?? '' ?>" placeholder="0 for unlimited">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">RADIUS Profile</label>
            <select name="profile_id" class="form-input">
                <option value="">None</option>
                <?php foreach ($profiles as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (isset($package['profile_id']) && $package['profile_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" <?= (!isset($package['is_active']) || $package['is_active']) ? 'checked' : '' ?>>
                <span style="font-size:12px;font-weight:600;">Active</span>
            </label>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Package' : 'Create Package' ?></button>
            <a href="<?= base_url('configuration') ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>