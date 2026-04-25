<?php
/**
 * POP Form View
 */
$pop = $pop ?? null;
$zones = $zones ?? [];
$isEdit = !empty($pop);
?>

<div class="page-header fade-in">
    <div>
        <a href="<?= base_url('configuration') ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <h1 class="page-title"><?= $isEdit ? 'Edit POP' : 'Add POP' ?></h1>
    </div>
</div>

<div class="card fade-in" style="max-width:600px;">
    <form method="POST" action="<?= base_url('configuration/pops/' . ($isEdit ? 'update/' . $pop['id'] : 'store')) ?>">
        <?= csrf_field() ?>
        
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">POP Name *</label>
            <input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($pop['name'] ?? '') ?>" placeholder="e.g., POP Central">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">POP Code *</label>
            <input type="text" name="code" class="form-input" required value="<?= htmlspecialchars($pop['code'] ?? '') ?>" placeholder="e.g., POP-01">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Zone *</label>
            <select name="zone_id" class="form-input" required>
                <option value="">Select Zone</option>
                <?php foreach ($zones as $z): ?>
                <option value="<?= $z['id'] ?>" <?= (isset($pop['zone_id']) && $pop['zone_id'] == $z['id']) ? 'selected' : '' ?>><?= htmlspecialchars($z['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">IP Address</label>
            <input type="text" name="ip_address" class="form-input" value="<?= htmlspecialchars($pop['ip_address'] ?? '') ?>" placeholder="e.g., 192.168.1.1">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Location</label>
            <input type="text" name="location" class="form-input" value="<?= htmlspecialchars($pop['location'] ?? '') ?>" placeholder="e.g., Downtown">
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Status</label>
            <select name="status" class="form-input">
                <option value="active" <?= (isset($pop['status']) && $pop['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= (isset($pop['status']) && $pop['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update POP' : 'Create POP' ?></button>
            <a href="<?= base_url('configuration') ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>