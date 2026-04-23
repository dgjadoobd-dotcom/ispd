<?php // views/mac-reseller/create.php
$isEdit      = isset($reseller) && $reseller !== null;
$formAction  = $isEdit ? base_url("mac-resellers/update/{$reseller['id']}") : base_url('mac-resellers/store');
$pageHeading = $isEdit ? 'Edit MAC Reseller' : 'Add MAC Reseller';
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= $pageHeading ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('mac-resellers') ?>" style="color:var(--blue);text-decoration:none;">MAC Resellers</a>
            › <?= $isEdit ? 'Edit' : 'New' ?>
        </div>
    </div>
    <a href="<?= $isEdit ? base_url("mac-resellers/view/{$reseller['id']}") : base_url('mac-resellers') ?>" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;color:var(--red);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['error']) ?>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $formAction ?>">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
    <div class="card fade-in" style="padding:20px;">
        <div style="font-size:14px;font-weight:700;margin-bottom:16px;">
            <i class="fa-solid fa-network-wired" style="color:var(--blue);margin-right:8px;"></i>MAC Reseller Information
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label class="form-label">Business Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="business_name" class="form-input" required
                       value="<?= htmlspecialchars($reseller['business_name'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Contact Person <span style="color:var(--red)">*</span></label>
                <input type="text" name="contact_person" class="form-input" required
                       value="<?= htmlspecialchars($reseller['contact_person'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Phone <span style="color:var(--red)">*</span></label>
                <input type="tel" name="phone" class="form-input" placeholder="01XXXXXXXXX" required
                       value="<?= htmlspecialchars($reseller['phone'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input"
                       value="<?= htmlspecialchars($reseller['email'] ?? '') ?>">
            </div>
            <div style="grid-column:1/-1;">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-input" rows="2"><?= htmlspecialchars($reseller['address'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-input">
                    <option value="">— Select Branch —</option>
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ($reseller['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Commission Rate (%)</label>
                <input type="number" name="commission_rate" class="form-input" step="0.5" min="0" max="100"
                       value="<?= htmlspecialchars($reseller['commission_rate'] ?? '0') ?>">
            </div>
            <div>
                <label class="form-label">Credit Limit (৳)</label>
                <input type="number" name="credit_limit" class="form-input" step="0.01" min="0"
                       value="<?= htmlspecialchars($reseller['credit_limit'] ?? '0') ?>">
            </div>
            <?php if ($isEdit): ?>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="active"    <?= ($reseller['status'] ?? '') === 'active'    ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= ($reseller['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    <option value="inactive"  <?= ($reseller['status'] ?? '') === 'inactive'  ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <?php endif; ?>
            <div style="grid-column:1/-1;">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="2"><?= htmlspecialchars($reseller['notes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card fade-in" style="padding:18px;">
            <div style="font-size:12px;color:var(--text2);line-height:1.7;">
                <i class="fa-solid fa-info-circle" style="color:var(--blue);margin-right:6px;"></i>
                MAC Resellers manage clients by MAC address. After creating the reseller, add tariff plans and clients from the detail page.
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;">
            <i class="fa-solid <?= $isEdit ? 'fa-save' : 'fa-plus' ?>"></i>
            <?= $isEdit ? 'Save Changes' : 'Add MAC Reseller' ?>
        </button>
        <?php if ($isEdit): ?>
        <a href="<?= base_url("mac-resellers/view/{$reseller['id']}") ?>" class="btn btn-ghost" style="width:100%;justify-content:center;padding:13px;">
            Cancel
        </a>
        <?php endif; ?>
    </div>
</div>
</form>
