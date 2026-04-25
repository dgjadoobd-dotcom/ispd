<?php
/**
 * Zone Form View
 */
$zone = $zone ?? null;
$isEdit = !empty($zone);
?>

<div class="page-header fade-in">
    <div>
        <a href="<?= base_url('configuration') ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <h1 class="page-title"><?= $isEdit ? 'Edit Zone' : 'Add Zone' ?></h1>
    </div>
</div>

<div class="card fade-in" style="max-width:600px;">
    <form method="POST" action="<?= base_url('configuration/zones/' . ($isEdit ? 'update/' . $zone['id'] : 'store')) ?>">
        <?= csrf_field() ?>
        
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Zone Name *</label>
            <input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($zone['name'] ?? '') ?>" placeholder="e.g., North Zone">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Zone Code *</label>
            <input type="text" name="code" class="form-input" required value="<?= htmlspecialchars($zone['code'] ?? '') ?>" placeholder="e.g., NZ">
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Description</label>
            <textarea name="description" class="form-input" rows="3" placeholder="Optional description"><?= htmlspecialchars($zone['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Zone' : 'Create Zone' ?></button>
            <a href="<?= base_url('configuration') ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>