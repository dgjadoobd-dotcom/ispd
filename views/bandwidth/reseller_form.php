<?php $isEdit = isset($reseller); ?>
<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-users" style="color:var(--blue);margin-right:10px;"></i><?= $isEdit ? 'Edit Reseller' : 'Add Reseller' ?></h1></div>
    <a href="<?= base_url('bandwidth/resellers') ?>" class="btn btn-ghost">Back</a>
</div>
<div class="card fade-in" style="max-width:600px;">
    <form method="POST" action="<?= $isEdit ? base_url('bandwidth/resellers/update/' . $reseller['id']) : base_url('bandwidth/resellers/store') ?>">
        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div><label class="form-label">Provider *</label>
                <select name="provider_id" class="form-input" required>
                    <option value="">Select Provider</option>
                    <?php foreach ($providers as $p): ?><option value="<?= $p['id'] ?>" <?= $isEdit && $reseller['provider_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="form-label">Reseller Name *</label><input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($reseller['name'] ?? '') ?>"></div>
            <div><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-input" value="<?= htmlspecialchars($reseller['contact_person'] ?? '') ?>"></div>
            <div><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($reseller['phone'] ?? '') ?>"></div>
            <div><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="<?= htmlspecialchars($reseller['email'] ?? '') ?>"></div>
            <div><label class="form-label">Credit Limit</label><input type="number" name="credit_limit" class="form-input" step="0.01" value="<?= $reseller['credit_limit'] ?? '' ?>"></div>
            <div><label class="form-label">Price per Mbps</label><input type="number" name="price_per_mbps" class="form-input" step="0.01" value="<?= $reseller['price_per_mbps'] ?? '' ?>"></div>
            <div><label class="form-label">Address</label><textarea name="address" class="form-input" rows="3"><?= htmlspecialchars($reseller['address'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:12px;padding-top:12px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
            </div>
        </div>
    </form>
</div>