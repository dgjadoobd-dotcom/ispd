<?php
/**
 * Vendor Form (Create/Edit)
 */
$isEdit = isset($vendor);
$formUrl = $isEdit ? base_url('purchases/vendors/update/' . $vendor['id']) : base_url('purchases/vendors/store');
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-truck" style="color:var(--blue);margin-right:10px;"></i><?= $isEdit ? 'Edit Vendor' : 'Add Vendor' ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('purchases/vendors') ?>">Vendors</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span><?= $isEdit ? 'Edit' : 'New' ?></span>
        </div>
    </div>
    <a href="<?= base_url('purchases/vendors') ?>" class="btn btn-ghost">Back</a>
</div>

<div class="card fade-in" style="max-width:600px;">
    <form method="POST" action="<?= $formUrl ?>">
        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="form-label">Vendor Name <span style="color:var(--red);">*</span></label>
                <input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($vendor['name'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-input" value="<?= htmlspecialchars($vendor['contact_person'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($vendor['email'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Address</label>
                <textarea name="address" class="form-input" rows="3"><?= htmlspecialchars($vendor['address'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:12px;padding-top:12px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> <?= $isEdit ? 'Update' : 'Create' ?></button>
                <a href="<?= base_url('purchases/vendors') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>