<?php $isEdit = isset($provider); ?>
<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-network-wired" style="color:var(--blue);margin-right:10px;"></i><?= $isEdit ? 'Edit Provider' : 'Add Provider' ?></h1></div>
    <a href="<?= base_url('bandwidth/providers') ?>" class="btn btn-ghost">Back</a>
</div>
<div class="card fade-in" style="max-width:600px;">
    <form method="POST" action="<?= $isEdit ? base_url('bandwidth/providers/update/' . $provider['id']) : base_url('bandwidth/providers/store') ?>">
        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div><label class="form-label">Provider Name *</label><input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($provider['name'] ?? '') ?>"></div>
            <div><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-input" value="<?= htmlspecialchars($provider['contact_person'] ?? '') ?>"></div>
            <div><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($provider['phone'] ?? '') ?>"></div>
            <div><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="<?= htmlspecialchars($provider['email'] ?? '') ?>"></div>
            <div><label class="form-label">Bandwidth Capacity (Mbps)</label><input type="number" name="bandwidth_capacity" class="form-input" value="<?= $provider['bandwidth_capacity'] ?? '' ?>"></div>
            <div><label class="form-label">Price per Mbps</label><input type="number" name="price_per_mbps" class="form-input" step="0.01" value="<?= $provider['price_per_mbps'] ?? '' ?>"></div>
            <div><label class="form-label">Address</label><textarea name="address" class="form-input" rows="3"><?= htmlspecialchars($provider['address'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:12px;padding-top:12px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
            </div>
        </div>
    </form>
</div>