<?php // views/superadmin/settings/index.php
$get = fn($k, $d = '') => htmlspecialchars($config[$k] ?? $d);
?>

<!-- Page Header -->
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">System Settings</h1>
        <div class="page-breadcrumb">Global platform configuration</div>
    </div>
</div>

<form id="settingsForm">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="fade-in">

    <!-- General Settings -->
    <div class="card" style="padding:22px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-gear" style="color:var(--purple);"></i> General
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div>
                <label class="form-label">Application Name</label>
                <input type="text" name="app_name" class="form-input" value="<?= $get('app_name', 'Digital ISP ERP') ?>">
            </div>
            <div>
                <label class="form-label">Application URL</label>
                <input type="url" name="app_url" class="form-input" value="<?= $get('app_url') ?>" placeholder="https://yourdomain.com">
            </div>
            <div>
                <label class="form-label">Timezone</label>
                <select name="timezone" class="form-input">
                    <?php
                    $tz = $config['timezone'] ?? 'Asia/Dhaka';
                    $zones = ['Asia/Dhaka','Asia/Kolkata','Asia/Karachi','Asia/Dubai','UTC','America/New_York','Europe/London'];
                    foreach ($zones as $z): ?>
                    <option value="<?= $z ?>" <?= $tz === $z ? 'selected' : '' ?>><?= $z ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Currency Code</label>
                    <input type="text" name="currency" class="form-input" value="<?= $get('currency', 'BDT') ?>" placeholder="BDT">
                </div>
                <div>
                    <label class="form-label">Currency Symbol</label>
                    <input type="text" name="currency_symbol" class="form-input" value="<?= $get('currency_symbol', '৳') ?>" placeholder="৳">
                </div>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="card" style="padding:22px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-shield-halved" style="color:var(--purple);"></i> Security
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div>
                <label class="form-label">Max Login Attempts</label>
                <input type="number" name="max_login_attempts" class="form-input" value="<?= $get('max_login_attempts', '5') ?>" min="1" max="20">
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:var(--bg3);border-radius:8px;border:1px solid var(--border);">
                <div>
                    <div style="font-size:13px;font-weight:600;">Maintenance Mode</div>
                    <div style="font-size:11px;color:var(--text2);">Disable access for non-admins</div>
                </div>
                <label style="position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= ($config['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?> style="opacity:0;width:0;height:0;">
                    <span style="position:absolute;cursor:pointer;inset:0;background:#cbd5e1;border-radius:24px;transition:0.3s;">
                        <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:0.3s;"></span>
                    </span>
                </label>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:var(--bg3);border-radius:8px;border:1px solid var(--border);">
                <div>
                    <div style="font-size:13px;font-weight:600;">Open Registration</div>
                    <div style="font-size:11px;color:var(--text2);">Allow new customer self-registration</div>
                </div>
                <label style="position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;">
                    <input type="checkbox" name="registration_open" value="1" <?= ($config['registration_open'] ?? '0') === '1' ? 'checked' : '' ?> style="opacity:0;width:0;height:0;">
                    <span style="position:absolute;cursor:pointer;inset:0;background:#cbd5e1;border-radius:24px;transition:0.3s;">
                        <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:0.3s;"></span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <!-- SMTP Settings -->
    <div class="card" style="padding:22px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-envelope" style="color:var(--purple);"></i> Email (SMTP)
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div style="display:grid;grid-template-columns:1fr 100px;gap:12px;">
                <div>
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-input" value="<?= $get('smtp_host') ?>" placeholder="smtp.gmail.com">
                </div>
                <div>
                    <label class="form-label">Port</label>
                    <input type="number" name="smtp_port" class="form-input" value="<?= $get('smtp_port', '587') ?>">
                </div>
            </div>
            <div>
                <label class="form-label">SMTP Username</label>
                <input type="text" name="smtp_user" class="form-input" value="<?= $get('smtp_user') ?>" placeholder="your@email.com">
            </div>
            <div>
                <label class="form-label">SMTP Password</label>
                <input type="password" name="smtp_pass" class="form-input" value="<?= $get('smtp_pass') ?>" placeholder="App password">
            </div>
            <div>
                <label class="form-label">From Email</label>
                <input type="email" name="smtp_from" class="form-input" value="<?= $get('smtp_from') ?>" placeholder="noreply@yourdomain.com">
            </div>
        </div>
    </div>

    <!-- SMS Settings -->
    <div class="card" style="padding:22px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-comment-sms" style="color:var(--purple);"></i> SMS Gateway
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div>
                <label class="form-label">SMS Gateway</label>
                <select name="sms_gateway" class="form-input">
                    <?php $gw = $config['sms_gateway'] ?? ''; ?>
                    <option value="">Select Gateway</option>
                    <option value="twilio" <?= $gw==='twilio'?'selected':'' ?>>Twilio</option>
                    <option value="nexmo" <?= $gw==='nexmo'?'selected':'' ?>>Vonage (Nexmo)</option>
                    <option value="bulksms" <?= $gw==='bulksms'?'selected':'' ?>>BulkSMS</option>
                    <option value="custom" <?= $gw==='custom'?'selected':'' ?>>Custom API</option>
                </select>
            </div>
            <div>
                <label class="form-label">API Key</label>
                <input type="text" name="sms_api_key" class="form-input" value="<?= $get('sms_api_key') ?>" placeholder="Your SMS API key">
            </div>
            <div>
                <label class="form-label">Sender ID</label>
                <input type="text" name="sms_sender_id" class="form-input" value="<?= $get('sms_sender_id') ?>" placeholder="ISPNAME">
            </div>
        </div>
    </div>

</div>

<!-- Save Button -->
<div style="margin-top:20px;display:flex;justify-content:flex-end;" class="fade-in">
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> Save All Settings
    </button>
</div>
</form>

<script>
// Toggle switch visual
document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    const slider = cb.nextElementSibling;
    const dot = slider?.querySelector('span');
    function update() {
        if (slider) slider.style.background = cb.checked ? '#7c3aed' : '#cbd5e1';
        if (dot) dot.style.transform = cb.checked ? 'translateX(20px)' : 'translateX(0)';
    }
    update();
    cb.addEventListener('change', update);
});

document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    // Ensure unchecked checkboxes send 0
    ['maintenance_mode','registration_open'].forEach(k => {
        if (!fd.has(k)) fd.append(k, '0');
    });
    const r = await fetch('<?= base_url('superadmin/settings/save') ?>', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) showToast(d.message, 'success');
    else showToast(d.message, 'error');
});
</script>
