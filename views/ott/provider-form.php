<?php // views/ott/provider-form.php
// Variables: $provider (array|null — null for create)
$isEdit = !empty($provider);
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Edit OTT Provider' : 'Add OTT Provider' ?></h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i>
            <a href="<?= base_url('ott') ?>" style="color:var(--blue);">OTT</a> &rsaquo;
            <a href="<?= base_url('ott/providers') ?>" style="color:var(--blue);">Providers</a> &rsaquo;
            <?= $isEdit ? 'Edit' : 'New' ?>
        </div>
    </div>
    <a href="<?= base_url('ott/providers') ?>" class="btn btn-ghost btn-sm">
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
        ? base_url('ott/providers/update/' . $provider['id'])
        : base_url('ott/providers/store') ?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div style="grid-column:1/-1;">
                <label class="form-label">Provider Name <span style="color:var(--red);">*</span></label>
                <input type="text" name="name" class="form-input" required
                       value="<?= htmlspecialchars($provider['name'] ?? '') ?>"
                       placeholder="e.g. Netflix, Hoichoi, Chorki">
            </div>

            <div style="grid-column:1/-1;">
                <label class="form-label">Logo URL</label>
                <input type="url" name="logo_url" class="form-input"
                       value="<?= htmlspecialchars($provider['logo_url'] ?? '') ?>"
                       placeholder="https://example.com/logo.png">
                <div style="font-size:12px;color:var(--text2);margin-top:4px;">
                    Full URL to the provider's logo image.
                </div>
            </div>

            <div style="grid-column:1/-1;">
                <label class="form-label">API Endpoint</label>
                <input type="url" name="api_endpoint" class="form-input"
                       value="<?= htmlspecialchars($provider['api_endpoint'] ?? '') ?>"
                       placeholder="https://api.provider.com/v1/">
                <div style="font-size:12px;color:var(--text2);margin-top:4px;">
                    Base URL for provider API calls (activation/deactivation).
                </div>
            </div>

            <div style="grid-column:1/-1;">
                <label class="form-label">API Key</label>
                <input type="text" name="api_key" class="form-input"
                       value="<?= htmlspecialchars($provider['api_key'] ?? '') ?>"
                       placeholder="Enter API key or token"
                       autocomplete="off">
            </div>

            <div style="grid-column:1/-1;">
                <label class="form-label">Supported Plan Types</label>
                <input type="text" name="plan_types" class="form-input"
                       value="<?= htmlspecialchars($provider['plan_types'] ?? '') ?>"
                       placeholder="monthly,yearly,weekly">
                <div style="font-size:12px;color:var(--text2);margin-top:4px;">
                    Comma-separated list of plan types this provider supports.
                </div>
            </div>

            <?php if ($isEdit): ?>
            <div>
                <label class="form-label">Status</label>
                <select name="is_active" class="form-input">
                    <option value="1" <?= ($provider['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !($provider['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <?php endif; ?>

            <div style="grid-column:1/-1;">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3"
                          placeholder="Internal notes about this provider..."
                          style="resize:vertical;"><?= htmlspecialchars($provider['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid <?= $isEdit ? 'fa-save' : 'fa-plus' ?>"></i>
                <?= $isEdit ? 'Save Changes' : 'Create Provider' ?>
            </button>
            <a href="<?= base_url('ott/providers') ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
