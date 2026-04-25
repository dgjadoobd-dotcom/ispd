<?php // views/settings/index.php ?>
<style>
.settings-nav-card { padding: 12px; align-self: start; }
.settings-nav-title {
    padding: 2px 8px 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--text2);
}
.settings-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.settings-tab {
    width: 100%;
    padding: 11px 14px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text2);
    transition: all .2s ease;
}
.settings-tab:hover { background: var(--bg3); color: var(--text); }
.settings-tab.active {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    box-shadow: 0 10px 18px -12px rgba(37,99,235,0.85);
}
.settings-tab i {
    width: 16px;
    text-align: center;
    flex-shrink: 0;
}
.settings-pane { display:none; }
.settings-pane.active { display:block; }
@media (max-width: 900px) {
    .settings-layout {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="page-header fade-in">
    <h1 class="page-title">Settings</h1>
</div>

<div style="display:grid;grid-template-columns:220px minmax(0,1fr);gap:20px;" class="fade-in settings-layout">
    <!-- Sidebar Tabs -->
    <div class="card settings-nav-card">
        <div class="settings-nav-title">Settings Menu</div>
        <div class="settings-nav">
            <?php foreach([
                ['general','fa-gear','General'],
                ['app','fa-mobile-screen','App Settings'],
                ['sms','fa-message','SMS Gateway'],
                ['ai','fa-wand-magic-sparkles','AI Assistant'],
                ['packages','fa-wifi','Packages'],
                ['reseller', 'fa-store', 'Reseller Panel'],
                ['users', 'fa-users', 'Staff Users'],
                ['branches', 'fa-building', 'Branches & Zones'],
                ['payment', 'fa-credit-card', 'Payment Gateways'],
            ] as [$id, $icon, $label]): ?>
            <div class="settings-tab <?= $id==='general'?'active':'' ?>" onclick="switchTab('<?= $id ?>')" id="tab-<?= $id ?>">
                <i class="fa-solid <?= $icon ?>"></i> <?= $label ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Panes -->
    <div>
        <!-- General -->
        <div class="settings-pane active" id="pane-general">
            <div class="card" style="padding:20px;">
                <div style="font-size:15px;font-weight:700;margin-bottom:18px;"><i class="fa-solid fa-gear" style="color:var(--blue);margin-right:8px;"></i>General Settings</div>
                <form method="POST" action="<?= base_url('settings/general') ?>" style="display:grid;gap:14px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div><label class="form-label">Company Name</label><input type="text" name="company_name" class="form-input" value="<?= htmlspecialchars($settings['company_name']??'Digital ISP ERP') ?>"></div>
                        <div><label class="form-label">Company Phone</label><input type="tel" name="company_phone" class="form-input" value="<?= htmlspecialchars($settings['company_phone']??'') ?>"></div>
                        <div><label class="form-label">Company Email</label><input type="email" name="company_email" class="form-input" value="<?= htmlspecialchars($settings['company_email']??'') ?>"></div>
                        <div><label class="form-label">Website</label><input type="text" name="website" class="form-input" value="<?= htmlspecialchars($settings['website']??'') ?>"></div>
                        <div style="grid-column:1/-1;"><label class="form-label">Address</label><textarea name="company_address" class="form-input" rows="2"><?= htmlspecialchars($settings['company_address']??'') ?></textarea></div>
                        <div><label class="form-label">Currency</label>
                            <select name="currency" class="form-input"><option value="BDT" selected>BDT (৳)</option></select>
                        </div>
                        <div><label class="form-label">VAT %</label><input type="number" name="vat_percent" class="form-input" value="<?= $settings['vat_percent']??0 ?>" step="0.5" min="0"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:fit-content;"><i class="fa-solid fa-save"></i> Save General Settings</button>
                </form>
            </div>
        </div>

        <!-- App Settings -->
        <div class="settings-pane" id="pane-app">
            <div class="card" style="padding:20px;">
                <div style="font-size:15px;font-weight:700;margin-bottom:18px;"><i class="fa-solid fa-mobile-screen" style="color:var(--purple);margin-right:8px;"></i>Mobile App Settings</div>
                <form method="POST" action="<?= base_url('settings/app') ?>" style="display:grid;gap:14px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div><label class="form-label">Google Play Store URL</label><input type="url" name="app_playstore_url" class="form-input" value="<?= htmlspecialchars($settings['app_playstore_url']??'') ?>" placeholder="https://play.google.com/store/apps/details?id=..."></div>
                        <div><label class="form-label">Apple App Store URL</label><input type="url" name="app_appstore_url" class="form-input" value="<?= htmlspecialchars($settings['app_appstore_url']??'') ?>" placeholder="https://apps.apple.com/us/app/... "></div>
                        <div><label class="form-label">Latest Android Version</label><input type="text" name="app_android_version" class="form-input" value="<?= htmlspecialchars($settings['app_android_version']??'1.0.0') ?>"></div>
                        <div><label class="form-label">Latest iOS Version</label><input type="text" name="app_ios_version" class="form-input" value="<?= htmlspecialchars($settings['app_ios_version']??'1.0.0') ?>"></div>
                        <div>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-top:28px;">
                                <input type="checkbox" name="app_force_update" value="1" <?= ($settings['app_force_update']??'')==='1'?'checked':'' ?> style="width:18px;height:18px;">
                                <span style="font-weight:600;">Force Mobile App Update</span>
                            </label>
                        </div>
                        <div>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-top:28px;">
                                <input type="checkbox" name="app_maintenance_mode" value="1" <?= ($settings['app_maintenance_mode']??'')==='1'?'checked':'' ?> style="width:18px;height:18px;">
                                <span style="font-weight:600;color:var(--red);">Enable Maintenance Mode</span>
                            </label>
                        </div>
                        <div style="grid-column:1/-1;">
                            <label class="form-label">App Notice / Announcement Message</label>
                            <textarea name="app_announcement" class="form-input" rows="2" placeholder="Message shown on app launch..."><?= htmlspecialchars($settings['app_announcement']??'') ?></textarea>
                        </div>
                        <div>
                            <label class="form-label">App Primary Color</label>
                            <input type="color" name="app_primary_color" class="form-input" style="height:42px;padding:4px;" value="<?= htmlspecialchars($settings['app_primary_color']??'#2563eb') ?>">
                        </div>
                        <div>
                            <label class="form-label">Support Phone (In-App)</label>
                            <input type="tel" name="app_support_phone" class="form-input" value="<?= htmlspecialchars($settings['app_support_phone']??'') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:fit-content;"><i class="fa-solid fa-save"></i> Save App Settings</button>
                </form>
            </div>
        </div>

        <!-- AI Assistant -->
        <div class="settings-pane" id="pane-ai">
            <div class="card" style="padding:20px;">
                <div style="font-size:15px;font-weight:700;margin-bottom:18px;"><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--purple);margin-right:8px;"></i>AI Assistant Configuration</div>

                <!-- Connection test result -->
                <div id="aiTestResult" style="display:none;margin-bottom:14px;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;"></div>

                <form method="POST" action="<?= base_url('settings/ai') ?>" style="display:grid;gap:14px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="ai_enabled" value="1" <?= (env('AI_ENABLED') ? 'checked' : '') ?> style="width:18px;height:18px;">
                                <span style="font-weight:600;">Enable AI Features</span>
                            </label>
                            <div style="font-size:12px;color:var(--text2);margin-top:4px;">Supports Ollama (<code>/api</code>) and OpenAI-compatible (<code>/v1</code>) APIs</div>
                        </div>
                        <div>
                            <label class="form-label">AI API Base URL</label>
                            <input type="text" name="ai_base_url" id="ai_base_url" class="form-input"
                                value="<?= htmlspecialchars(env('AI_BASE_URL') ?: 'http://localhost:11434/api') ?>"
                                placeholder="http://localhost:11434/api">
                            <div style="font-size:11px;color:var(--text2);margin-top:4px;">Ollama: <code>…:11434/api</code> &nbsp;|&nbsp; LM Studio: <code>…:1234/v1</code></div>
                        </div>
                        <div>
                            <label class="form-label">AI Model</label>
                            <input type="text" name="ai_model" id="ai_model" class="form-input"
                                value="<?= htmlspecialchars(env('AI_MODEL') ?: 'gemma3:latest') ?>"
                                placeholder="gemma3:latest">
                            <div style="font-size:11px;color:var(--text2);margin-top:4px;" id="aiModelHint">Available models will appear after Test Connection</div>
                        </div>
                        <div>
                            <label class="form-label">API Timeout (seconds)</label>
                            <input type="number" name="ai_timeout" class="form-input"
                                value="<?= htmlspecialchars(env('AI_TIMEOUT') ?: 60) ?>" min="5" max="300">
                        </div>
                    </div>

                    <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:16px;">
                        <div style="font-size:14px;font-weight:600;margin-bottom:8px;color:var(--text);">AI Features</div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
                            <?php foreach(['Customer Account Insights','Support Ticket Analysis','Work Order Assistance','Portal Chat Assistant'] as $f): ?>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--green);"></i>
                                <span style="font-size:13px;"><?= $f ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-ghost" onclick="testAiConnection()">
                            <i class="fa-solid fa-plug-circle-check"></i> Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save AI Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function testAiConnection() {
            var btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Testing…';
            var res = document.getElementById('aiTestResult');
            res.style.display = 'none';

            fetch('<?= base_url('settings/ai/test') ?>')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        var models = (data.models || []).join(', ') || 'none listed';
                        res.style.background = 'rgba(16,185,129,0.1)';
                        res.style.border     = '1px solid var(--green)';
                        res.style.color      = 'var(--green)';
                        res.innerHTML = '<i class="fa-solid fa-circle-check"></i> Connected! API type: <strong>' + data.api_type + '</strong> &nbsp;|&nbsp; Models: <strong>' + models + '</strong>';
                        document.getElementById('aiModelHint').innerHTML = 'Available: <strong>' + models + '</strong>';
                    } else {
                        res.style.background = 'rgba(239,68,68,0.1)';
                        res.style.border     = '1px solid var(--red)';
                        res.style.color      = 'var(--red)';
                        res.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Connection failed: ' + (data.error || 'Unknown error');
                    }
                    res.style.display = 'block';
                })
                .catch(function(e) {
                    res.style.background = 'rgba(239,68,68,0.1)';
                    res.style.border     = '1px solid var(--red)';
                    res.style.color      = 'var(--red)';
                    res.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Request failed: ' + e.message;
                    res.style.display = 'block';
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-plug-circle-check"></i> Test Connection';
                });
        }
        </script>

        <!-- SMS Gateway -->
        <div class="settings-pane" id="pane-sms">
            <div class="card" style="padding:20px;">
                <div style="font-size:15px;font-weight:700;margin-bottom:18px;"><i class="fa-solid fa-message" style="color:var(--purple);margin-right:8px;"></i>SMS Gateway Configuration</div>
                <?php if(empty($gateways)): ?>
                <div style="text-align:center;padding:24px;color:var(--text2);">No SMS gateways configured.</div>
                <?php else: foreach($gateways as $gw): ?>
                <div style="border:1px solid var(--border);border-radius:12px;padding:14px;margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <div style="font-weight:700;"><?= htmlspecialchars($gw['name']) ?></div>
                        <span class="badge <?= $gw['is_active']?'badge-green':'badge-gray' ?>"><?= $gw['is_active']?'Active':'Inactive' ?></span>
                    </div>
                    <div style="font-size:12px;color:var(--text2);display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div>Provider: <strong><?= htmlspecialchars($gw['provider']) ?></strong></div>
                        <div>Sender ID: <strong><?= htmlspecialchars($gw['sender_id']??'—') ?></strong></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Packages -->
        <div class="settings-pane" id="pane-packages">
            <div class="card" style="overflow:hidden;">
                <div style="padding:16px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);">
                    <div style="font-size:15px;font-weight:700;"><i class="fa-solid fa-wifi" style="color:var(--green);margin-right:8px;"></i>Internet Packages</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-ghost btn-sm" onclick="openSyncModal()"><i class="fa-solid fa-sync"></i> Sync from MikroTik</button>
                        <button class="btn btn-primary btn-sm" onclick="document.getElementById('addPackageModal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead><tr><th>Package</th><th>Speed</th><th>Price</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if(empty($packages)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text2);">No packages defined.</td></tr>
                        <?php else: foreach($packages as $pkg): ?>
                        <tr>
                            <td><div style="font-weight:600;"><?= htmlspecialchars($pkg['name']) ?></div><div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($pkg['description']??'') ?></div></td>
                            <td style="font-family:monospace;"><?= $pkg['speed_download'] ?>↓ / <?= $pkg['speed_upload'] ?>↑ Mbps</td>
                            <td style="font-weight:700;color:var(--green);">৳<?= number_format($pkg['price'],0) ?></td>
                            <td><span class="badge badge-blue"><?= ucfirst($pkg['type'] ?? 'pppoe') ?></span></td>
                            <td><span class="badge <?= $pkg['is_active']?'badge-green':'badge-gray' ?>"><?= $pkg['is_active']?'Active':'Inactive' ?></span></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <button class="icon-btn btn-sm" onclick='openEditPackageModal(<?= json_encode($pkg) ?>)' title="Edit"><i class="fa-solid fa-edit"></i></button>
                                    <button class="icon-btn btn-sm" style="color:var(--red);" onclick="deletePackage(<?= $pkg['id'] ?>)" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users -->
        <div class="settings-pane" id="pane-users">
            <div class="card" style="overflow:hidden;">
                <div style="padding:16px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);">
                    <div style="font-size:15px;font-weight:700;"><i class="fa-solid fa-users" style="color:var(--blue);margin-right:8px;"></i>Staff Users</div>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addUserModal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add Staff User</button>
                </div>
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Branch</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text2);">No users found.</td></tr>
                        <?php else: foreach($users as $u): ?>
                        <tr>
                            <td><div style="font-weight:600;"><?= htmlspecialchars($u['full_name']) ?></div><div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($u['email']??'') ?></div></td>
                            <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($u['username']) ?></td>
                            <td><span class="badge badge-blue"><?= htmlspecialchars($u['role_name']??'—') ?></span></td>
                            <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($u['branch_name']??'—') ?></td>
                            <td><span class="badge <?= $u['is_active']?'badge-green':'badge-gray' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <button class="icon-btn btn-sm" onclick='openEditUserModal(<?= json_encode($u) ?>)' title="Edit"><i class="fa-solid fa-edit"></i></button>
                                    <button class="icon-btn btn-sm" style="color:var(--red);" onclick="deleteUser(<?= $u['id'] ?>)" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Gateways -->
        <div class="settings-pane" id="pane-payment">
            <div class="card" style="padding:20px;">
                <div style="font-size:15px;font-weight:700;margin-bottom:18px;"><i class="fa-solid fa-credit-card" style="color:var(--green);margin-right:8px;"></i>Payment Gateways</div>
                <form method="POST" action="<?= base_url('settings/payments/save') ?>">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <!-- bKash -->
                        <div style="border:1px solid var(--border);border-radius:12px;padding:16px;background:rgba(216,27,96,0.03);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                <div style="font-weight:700;color:#D81B60;">bKash Payment</div>
                                <label class="switch"><input type="checkbox" name="bkash_enabled" value="1" <?= ($settings['bkash_enabled']??'')==='1'?'checked':'' ?>><span class="slider"></span></label>
                            </div>
                            <div style="display:grid;gap:10px;">
                                <div><label class="form-label">App Key</label><input type="text" name="bkash_app_key" class="form-input" value="<?= htmlspecialchars($settings['bkash_app_key']??'') ?>"></div>
                                <div><label class="form-label">App Secret</label><input type="password" name="bkash_app_secret" class="form-input" value="<?= htmlspecialchars($settings['bkash_app_secret']??'') ?>"></div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                    <div><label class="form-label">Username</label><input type="text" name="bkash_username" class="form-input" value="<?= htmlspecialchars($settings['bkash_username']??'') ?>"></div>
                                    <div><label class="form-label">Password</label><input type="password" name="bkash_password" class="form-input" value="<?= htmlspecialchars($settings['bkash_password']??'') ?>"></div>
                                </div>
                                <div><label class="form-label">Mode</label>
                                    <select name="bkash_mode" class="form-input"><option value="sandbox" <?= ($settings['bkash_mode']??'')==='sandbox'?'selected':'' ?>>Sandbox (Test)</option><option value="live" <?= ($settings['bkash_mode']??'')==='live'?'selected':'' ?>>Live (Production)</option></select>
                                </div>
                            </div>
                        </div>

                        <!-- Nagad -->
                        <div style="border:1px solid var(--border);border-radius:12px;padding:16px;background:rgba(237,28,36,0.03);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                <div style="font-weight:700;color:#ED1C24;">Nagad Payment</div>
                                <label class="switch"><input type="checkbox" name="nagad_enabled" value="1" <?= ($settings['nagad_enabled']??'')==='1'?'checked':'' ?>><span class="slider"></span></label>
                            </div>
                            <div style="display:grid;gap:10px;">
                                <div><label class="form-label">Merchant ID</label><input type="text" name="nagad_merchant_id" class="form-input" value="<?= htmlspecialchars($settings['nagad_merchant_id']??'') ?>"></div>
                                <div><label class="form-label">Public Key</label><textarea name="nagad_public_key" class="form-input" rows="2" style="font-size:10px;font-family:monospace;"><?= htmlspecialchars($settings['nagad_public_key']??'') ?></textarea></div>
                                <div><label class="form-label">Private Key</label><textarea name="nagad_private_key" class="form-input" rows="2" style="font-size:10px;font-family:monospace;"><?= htmlspecialchars($settings['nagad_private_key']??'') ?></textarea></div>
                                <div><label class="form-label">Mode</label>
                                    <select name="nagad_mode" class="form-input"><option value="sandbox" <?= ($settings['nagad_mode']??'')==='sandbox'?'selected':'' ?>>Sandbox (Test)</option><option value="live" <?= ($settings['nagad_mode']??'')==='live'?'selected':'' ?>>Live (Production)</option></select>
                                </div>
                            </div>
                        </div>

                        <!-- Rocket -->
                        <div style="border:1px solid var(--border);border-radius:12px;padding:16px;background:rgba(140,43,142,0.03);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                <div style="font-weight:700;color:#8C2B8E;">Rocket Payment</div>
                                <label class="switch"><input type="checkbox" name="rocket_enabled" value="1" <?= ($settings['rocket_enabled']??'')==='1'?'checked':'' ?>><span class="slider"></span></label>
                            </div>
                            <div style="display:grid;gap:10px;">
                                <div><label class="form-label">Merchant ID</label><input type="text" name="rocket_merchant_id" class="form-input" value="<?= htmlspecialchars($settings['rocket_merchant_id']??'') ?>"></div>
                                <div><label class="form-label">App Key</label><input type="text" name="rocket_app_key" class="form-input" value="<?= htmlspecialchars($settings['rocket_app_key']??'') ?>"></div>
                                <div><label class="form-label">Mode</label>
                                    <select name="rocket_mode" class="form-input"><option value="sandbox" <?= ($settings['rocket_mode']??'')==='sandbox'?'selected':'' ?>>Sandbox (Test)</option><option value="live" <?= ($settings['rocket_mode']??'')==='live'?'selected':'' ?>>Live (Production)</option></select>
                                </div>
                            </div>
                        </div>

                        <!-- PipraPay -->
                        <div style="border:2px solid <?= ($settings['piprapay_enabled']??'')==='1'?'#0ea5e9':'var(--border)' ?>;border-radius:12px;padding:16px;background:rgba(14,165,233,0.04);grid-column:1/-1;" id="piprapay-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#0ea5e9,#0284c7);display:flex;align-items:center;justify-content:center;">
                                        <i class="fa-solid fa-p" style="color:#fff;font-size:18px;font-weight:900;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:800;font-size:15px;color:#0ea5e9;">PipraPay</div>
                                        <div style="font-size:11px;color:var(--text2);">Bangladeshi All-in-One Payment Gateway · bKash · Nagad · Rocket · Upay · 15+ Banks</div>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <a href="https://piprapay.com" target="_blank" style="font-size:11px;color:var(--blue);text-decoration:none;"><i class="fa-solid fa-arrow-up-right-from-square"></i> piprapay.com</a>
                                    <label class="switch"><input type="checkbox" name="piprapay_enabled" value="1" id="piprapay_toggle" onchange="togglePiprapay(this)" <?= ($settings['piprapay_enabled']??'')==='1'?'checked':'' ?>><span class="slider"></span></label>
                                </div>
                            </div>

                            <!-- Supported methods badges -->
                            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;">
                                <?php foreach(['bKash','Nagad','Rocket','Upay','DBBL','BRAC Bank','City Bank','EBL','15+ Banks'] as $m): ?>
                                <span style="font-size:10px;font-weight:600;padding:3px 8px;border-radius:20px;background:rgba(14,165,233,0.12);color:#0ea5e9;border:1px solid rgba(14,165,233,0.2);"><?= $m ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div id="piprapay_fields" style="display:<?= ($settings['piprapay_enabled']??'')==='1'?'grid':'none' ?>;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                                <div>
                                    <label class="form-label">Merchant ID <span style="color:var(--red)">*</span></label>
                                    <input type="text" name="piprapay_merchant_id" class="form-input" value="<?= htmlspecialchars($settings['piprapay_merchant_id']??'') ?>" placeholder="PP-XXXXXXXX">
                                </div>
                                <div>
                                    <label class="form-label">API Key <span style="color:var(--red)">*</span></label>
                                    <input type="text" name="piprapay_api_key" class="form-input" value="<?= htmlspecialchars($settings['piprapay_api_key']??'') ?>" placeholder="pk_live_...">
                                </div>
                                <div>
                                    <label class="form-label">API Secret <span style="color:var(--red)">*</span></label>
                                    <input type="password" name="piprapay_api_secret" class="form-input" value="<?= htmlspecialchars($settings['piprapay_api_secret']??'') ?>" placeholder="sk_live_...">
                                </div>
                                <div>
                                    <label class="form-label">Mode</label>
                                    <select name="piprapay_mode" class="form-input">
                                        <option value="sandbox" <?= ($settings['piprapay_mode']??'sandbox')==='sandbox'?'selected':'' ?>>Sandbox (Test)</option>
                                        <option value="live" <?= ($settings['piprapay_mode']??'')==='live'?'selected':'' ?>>Live (Production)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Callback URL</label>
                                    <input type="text" class="form-input" value="<?= base_url('payment/piprapay/callback') ?>" readonly style="font-size:11px;font-family:monospace;background:var(--bg3);cursor:copy;" onclick="navigator.clipboard.writeText(this.value);this.style.borderColor='var(--green)';" title="Click to copy">
                                </div>
                                <div>
                                    <label class="form-label">Success URL</label>
                                    <input type="text" class="form-input" value="<?= base_url('payment/piprapay/success') ?>" readonly style="font-size:11px;font-family:monospace;background:var(--bg3);cursor:copy;" onclick="navigator.clipboard.writeText(this.value);this.style.borderColor='var(--green)';" title="Click to copy">
                                </div>
                                <div style="grid-column:1/-1;">
                                    <div style="background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.2);border-radius:8px;padding:12px;font-size:12px;color:var(--text2);">
                                        <i class="fa-solid fa-circle-info" style="color:#0ea5e9;margin-right:6px;"></i>
                                        Get your credentials from <a href="https://piprapay.com/merchant" target="_blank" style="color:#0ea5e9;">piprapay.com/merchant</a>.
                                        Add the Callback URL and Success URL to your PipraPay merchant dashboard whitelist.
                                        Contact: <strong>support@piprapay.com</strong> · <strong>+880 1806-579249</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Self-Hosted PipraPay -->
                        <div style="background:var(--bg2); border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:4px;">
                            <label style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                                <input type="checkbox" name="selfhosted_piprapay_enabled" value="1" onchange="toggleSelfHostedPiprapay(this)" <?= ($settings['selfhosted_piprapay_enabled']??'')==='1'?'checked':'' ?> style="width:18px; height:18px;">
                                <div>
                                    <div style="font-weight:700; font-size:14px;">Self-Hosted PipraPay</div>
                                    <div style="font-size:12px; color:var(--text2);">Local payment processing without external API dependency</div>
                                </div>
                            </label>

                            <!-- Supported methods badges -->
                            <div style="display:flex;gap:6px;flex-wrap:wrap;margin:12px 0;">
                                <?php foreach(['bKash','Nagad','Rocket','Upay','Bank Transfer'] as $m): ?>
                                <span style="font-size:10px;font-weight:600;padding:3px 8px;border-radius:20px;background:rgba(34,197,94,0.12);color:#22c55e;border:1px solid rgba(34,197,94,0.2);"><?= $m ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div id="selfhosted_piprapay_fields" style="display:<?= ($settings['selfhosted_piprapay_enabled']??'')==='1'?'grid':'none' ?>;grid-template-columns:1fr 1fr;gap:14px;">
                                <div>
                                    <label class="form-label">Webhook Secret <span style="color:var(--red)">*</span></label>
                                    <input type="password" name="selfhosted_piprapay_webhook_secret" class="form-input"
                                           value="<?= htmlspecialchars($settings['selfhosted_piprapay_webhook_secret']??'') ?>"
                                           placeholder="Enter secure webhook secret">
                                </div>
                                <div>
                                    <label class="form-label">Auto Billing Enabled</label>
                                    <select name="selfhosted_piprapay_auto_billing_enabled" class="form-input">
                                        <option value="1" <?= ($settings['selfhosted_piprapay_auto_billing_enabled']??'1')==='1'?'selected':'' ?>>Yes</option>
                                        <option value="0" <?= ($settings['selfhosted_piprapay_auto_billing_enabled']??'')==='0'?'selected':'' ?>>No</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Retry Attempts</label>
                                    <input type="number" name="selfhosted_piprapay_retry_attempts" class="form-input"
                                           value="<?= htmlspecialchars($settings['selfhosted_piprapay_retry_attempts']??'3') ?>"
                                           min="1" max="10">
                                </div>
                                <div>
                                    <label class="form-label">Retry Interval (Hours)</label>
                                    <input type="number" name="selfhosted_piprapay_retry_interval_hours" class="form-input"
                                           value="<?= htmlspecialchars($settings['selfhosted_piprapay_retry_interval_hours']??'24') ?>"
                                           min="1" max="168">
                                </div>
                                <div>
                                    <label class="form-label">Webhook URL</label>
                                    <input type="text" class="form-input" value="<?= base_url('payment/selfhosted/webhook') ?>"
                                           readonly style="font-size:11px;font-family:monospace;background:var(--bg3);cursor:copy;"
                                           onclick="navigator.clipboard.writeText(this.value);this.style.borderColor='var(--green);'"
                                           title="Click to copy">
                                </div>
                                <div>
                                    <label class="form-label">Checkout URL</label>
                                    <input type="text" class="form-input" value="<?= base_url('payment/selfhosted/checkout/') ?>"
                                           readonly style="font-size:11px;font-family:monospace;background:var(--bg3);cursor:copy;"
                                           onclick="navigator.clipboard.writeText(this.value + '{session_id}');this.style.borderColor='var(--green);'"
                                           title="Click to copy base URL">
                                </div>
                                <div style="grid-column:1/-1;">
                                    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:12px;font-size:12px;color:var(--text2);">
                                        <i class="fa-solid fa-server" style="color:#22c55e;margin-right:6px;"></i>
                                        <strong>Self-hosted payment processing</strong> - No external API dependency. Set up automated billing cron job:
                                        <code style="background:var(--bg3);padding:2px 4px;border-radius:3px;">php cron_selfhosted_piprapay.php</code>
                                        (run every 15 minutes). Configure customer payment subscriptions for automated collection.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Gateway Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reseller Panel -->
        <div class="settings-pane" id="pane-reseller">
            <div class="card" style="padding:20px;">
                <div style="font-size:15px;font-weight:700;margin-bottom:18px;"><i class="fa-solid fa-store" style="color:var(--purple);margin-right:8px;"></i>Reseller Panel Generator</div>
                <form method="POST" action="<?= base_url('settings/reseller') ?>" style="display:grid;gap:16px;">
                    <div style="background:var(--bg2); border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:4px;">
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                            <input type="checkbox" name="reseller_panel_enabled" value="1" <?= ($settings['reseller_panel_enabled']??'')==='1'?'checked':'' ?> style="width:18px; height:18px;">
                            <div>
                                <div style="font-weight:700; font-size:14px;">Enable Reseller Portal</div>
                                <div style="font-size:12px; color:var(--text2);">Allows resellers to log in and manage their own customers.</div>
                            </div>
                        </label>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                        <div>
                            <label class="form-label">Portal Brand Name</label>
                            <input type="text" name="reseller_portal_name" class="form-input" placeholder="e.g. Reseller Hub" value="<?= htmlspecialchars($settings['reseller_portal_name']??'') ?>">
                        </div>
                        <div>
                            <label class="form-label">Support Contact (Phone)</label>
                            <input type="text" name="reseller_support_phone" class="form-input" value="<?= htmlspecialchars($settings['reseller_support_phone']??'') ?>">
                        </div>
                        <div>
                            <label class="form-label">Support Contact (Email)</label>
                            <input type="email" name="reseller_support_email" class="form-input" value="<?= htmlspecialchars($settings['reseller_support_email']??'') ?>">
                        </div>
                        <div>
                            <label class="form-label">Theme Primary Color</label>
                            <input type="color" name="reseller_theme_color" class="form-input" style="height:42px; padding:4px;" value="<?= htmlspecialchars($settings['reseller_theme_color']??'#3b82f6') ?>">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label class="form-label">Login Page Welcome Text</label>
                            <textarea name="reseller_login_text" class="form-input" rows="2" placeholder="Welcome to our Reseller Partner Portal"><?= htmlspecialchars($settings['reseller_login_text']??'') ?></textarea>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:12px; padding:12px; border:1px dashed var(--blue); border-radius:12px; background:rgba(59,130,246,0.05);">
                        <div style="font-size:12px; color:var(--blue); font-weight:600;"><i class="fa-solid fa-wand-magic-sparkles" style="margin-right:6px;"></i>Panel Generation</div>
                        <div style="font-size:11px; color:var(--text2);">Clicking the button below will synchronize your branding and generate necessary portal assets.</div>
                        <button type="submit" class="btn btn-primary" style="width:fit-content;"><i class="fa-solid fa-sync"></i> Generate / Update Portals</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Branches -->
        <div class="settings-pane" id="pane-branches">
            <div class="card" style="overflow:hidden; margin-bottom: 20px;">
                <div style="padding:16px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);">
                    <div style="font-size:15px;font-weight:700;"><i class="fa-solid fa-building" style="color:var(--yellow);margin-right:8px;"></i>Branches</div>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addBranchModal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add Branch</button>
                </div>
                <table class="data-table">
                    <thead><tr><th>Branch</th><th>Code</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if(empty($branches)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text2);">No branches.</td></tr>
                        <?php else: foreach($branches as $b): ?>
                        <tr>
                            <td><div style="font-weight:600;"><?= htmlspecialchars($b['name']) ?></div><div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($b['address']??'') ?></div></td>
                            <td style="font-family:monospace;font-weight:700;"><?= htmlspecialchars($b['code']??'—') ?></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($b['phone']??'—') ?></td>
                            <td><span class="badge <?= $b['is_active']?'badge-green':'badge-gray' ?>"><?= $b['is_active']?'Active':'Inactive' ?></span></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <button class="icon-btn btn-sm" onclick='openEditBranchModal(<?= json_encode($b) ?>)' title="Edit"><i class="fa-solid fa-edit"></i></button>
                                    <button class="icon-btn btn-sm" style="color:var(--red);" onclick="deleteBranch(<?= $b['id'] ?>)" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="overflow:hidden;">
                <div style="padding:16px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);">
                    <div style="font-size:15px;font-weight:700;"><i class="fa-solid fa-map-location-dot" style="color:var(--blue);margin-right:8px;"></i>Zones</div>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addZoneModal').classList.add('add') || document.getElementById('addZoneModal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add Zone</button>
                </div>
                <table class="data-table">
                    <thead><tr><th>Zone</th><th>Code</th><th>Branch</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if(empty($zones)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text2);">No zones.</td></tr>
                        <?php else: foreach($zones as $z): ?>
                        <tr>
                            <td><div style="font-weight:600;"><?= htmlspecialchars($z['name']) ?></div><div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($z['description']??'') ?></div></td>
                            <td style="font-family:monospace;"><?= htmlspecialchars($z['code']??'—') ?></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($z['branch_name']??'—') ?></td>
                            <td><span class="badge <?= $z['is_active']?'badge-green':'badge-gray' ?>"><?= $z['is_active']?'Active':'Inactive' ?></span></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <button class="icon-btn btn-sm" onclick='openEditZoneModal(<?= json_encode($z) ?>)' title="Edit"><i class="fa-solid fa-edit"></i></button>
                                    <button class="icon-btn btn-sm" style="color:var(--red);" onclick="deleteZone(<?= $z['id'] ?>)" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div class="modal-overlay" id="addPackageModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-wifi" style="color:var(--green);margin-right:8px;"></i>Add Package</div>
            <button class="icon-btn" onclick="document.getElementById('addPackageModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/packages/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Package Name</label><input type="text" name="name" id="pkg_name" class="form-input" placeholder="e.g. Gold 30 Mbps" required></div>
                <div><label class="form-label">Download (Mbps)</label><input type="number" name="speed_download" id="pkg_down" class="form-input" placeholder="30"></div>
                <div><label class="form-label">Upload (Mbps)</label><input type="number" name="speed_upload" id="pkg_up" class="form-input" placeholder="15"></div>
                <div><label class="form-label">Monthly Price (৳)</label><input type="number" name="price" class="form-input" step="0.01" required></div>
                <div><label class="form-label">Type</label>
                    <select name="package_type" class="form-input"><option value="pppoe">PPPoE</option><option value="static">Static IP</option><option value="hotspot">Hotspot</option></select>
                </div>
                <div><label class="form-label">Radius/MikroTik Profile</label><input type="text" name="radius_profile" id="pkg_profile" class="form-input" placeholder="profile-name"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addPackageModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Package</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal-overlay" id="editPackageModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-edit" style="color:var(--blue);margin-right:8px;"></i>Edit Package</div>
            <button class="icon-btn" onclick="document.getElementById('editPackageModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/packages/update') ?>">
            <input type="hidden" name="id" id="edit_pkg_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Package Name</label><input type="text" name="name" id="edit_pkg_name" class="form-input" required></div>
                <div><label class="form-label">Download (Mbps)</label><input type="text" name="speed_download" id="edit_pkg_down" class="form-input"></div>
                <div><label class="form-label">Upload (Mbps)</label><input type="text" name="speed_upload" id="edit_pkg_up" class="form-input"></div>
                <div><label class="form-label">Monthly Price (৳)</label><input type="number" name="price" id="edit_pkg_price" class="form-input" step="0.01" required></div>
                <div><label class="form-label">Type</label>
                    <select name="package_type" id="edit_pkg_type" class="form-input"><option value="pppoe">PPPoE</option><option value="static">Static IP</option><option value="hotspot">Hotspot</option></select>
                </div>
                <div><label class="form-label">Radius/MikroTik Profile</label><input type="text" name="radius_profile" id="edit_pkg_profile" class="form-input"></div>
                <div><label class="form-label">Status</label>
                    <select name="is_active" id="edit_pkg_status" class="form-input"><option value="1">Active</option><option value="0">Inactive</option></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editPackageModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<form id="deletePackageForm" method="POST" action="<?= base_url('settings/packages/delete') ?>" style="display:none;"><input type="hidden" name="id" id="delete_pkg_id"></form>
<form id="deleteBranchForm" method="POST" action="<?= base_url('settings/branches/delete') ?>" style="display:none;"><input type="hidden" name="id" id="delete_branch_id"></form>
<form id="deleteZoneForm" method="POST" action="<?= base_url('settings/zones/delete') ?>" style="display:none;"><input type="hidden" name="id" id="delete_zone_id"></form>

<!-- Add Branch Modal -->
<div class="modal-overlay" id="addBranchModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-building" style="color:var(--yellow);margin-right:8px;"></i>Add Branch</div>
            <button class="icon-btn" onclick="document.getElementById('addBranchModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/branches/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Branch Name</label><input type="text" name="name" class="form-input" required></div>
                <div><label class="form-label">Branch Code</label><input type="text" name="code" class="form-input" required></div>
                <div><label class="form-label">Phone</label><input type="text" name="phone" class="form-input"></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input"></div>
                <div><label class="form-label">Manager Name</label><input type="text" name="manager_name" class="form-input"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Address</label><textarea name="address" class="form-input" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addBranchModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Branch</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Branch Modal -->
<div class="modal-overlay" id="editBranchModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-edit" style="color:var(--yellow);margin-right:8px;"></i>Edit Branch</div>
            <button class="icon-btn" onclick="document.getElementById('editBranchModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/branches/update') ?>">
            <input type="hidden" name="id" id="edit_branch_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Branch Name</label><input type="text" name="name" id="edit_branch_name" class="form-input" required></div>
                <div><label class="form-label">Branch Code</label><input type="text" name="code" id="edit_branch_code" class="form-input" required></div>
                <div><label class="form-label">Phone</label><input type="text" name="phone" id="edit_branch_phone" class="form-input"></div>
                <div><label class="form-label">Email</label><input type="email" name="email" id="edit_branch_email" class="form-input"></div>
                <div><label class="form-label">Manager Name</label><input type="text" name="manager_name" id="edit_branch_manager" class="form-input"></div>
                <div><label class="form-label">Status</label>
                    <select name="is_active" id="edit_branch_status" class="form-input"><option value="1">Active</option><option value="0">Inactive</option></select>
                </div>
                <div style="grid-column:1/-1;"><label class="form-label">Address</label><textarea name="address" id="edit_branch_address" class="form-input" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editBranchModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Zone Modal -->
<div class="modal-overlay" id="addZoneModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-map-location-dot" style="color:var(--blue);margin-right:8px;"></i>Add Zone</div>
            <button class="icon-btn" onclick="document.getElementById('addZoneModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/zones/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Select Branch</label>
                    <select name="branch_id" class="form-input" required>
                        <?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column:1/-1;"><label class="form-label">Zone Name</label><input type="text" name="name" class="form-input" required></div>
                <div><label class="form-label">Zone Code</label><input type="text" name="code" class="form-input"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Description</label><textarea name="description" class="form-input" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addZoneModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Zone</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Zone Modal -->
<div class="modal-overlay" id="editZoneModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-edit" style="color:var(--blue);margin-right:8px;"></i>Edit Zone</div>
            <button class="icon-btn" onclick="document.getElementById('editZoneModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/zones/update') ?>">
            <input type="hidden" name="id" id="edit_zone_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Select Branch</label>
                    <select name="branch_id" id="edit_zone_branch" class="form-input" required>
                        <?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column:1/-1;"><label class="form-label">Zone Name</label><input type="text" name="name" id="edit_zone_name" class="form-input" required></div>
                <div><label class="form-label">Zone Code</label><input type="text" name="code" id="edit_zone_code" class="form-input"></div>
                <div><label class="form-label">Status</label>
                    <select name="is_active" id="edit_zone_status" class="form-input"><option value="1">Active</option><option value="0">Inactive</option></select>
                </div>
                <div style="grid-column:1/-1;"><label class="form-label">Description</label><textarea name="description" id="edit_zone_desc" class="form-input" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editZoneModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteConfirmModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title text-red"><i class="fa-solid fa-triangle-exclamation" style="margin-right:8px;"></i>Confirm Deletion</div>
            <button class="icon-btn" onclick="document.getElementById('deleteConfirmModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p id="deleteConfirmText">Are you sure you want to delete this item? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('deleteConfirmModal').classList.remove('open')">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="executeDelete()">Delete Permanently</button>
        </div>
    </div>
</div>
</div>

<script>
function switchTab(id) {
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.settings-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-'+id).classList.add('active');
    document.getElementById('pane-'+id).classList.add('active');
    history.replaceState(null, '', '#'+id);
}
// Open to hash
if(location.hash) switchTab(location.hash.slice(1));

function togglePiprapay(cb) {
    const fields = document.getElementById('piprapay_fields');
    const card   = document.getElementById('piprapay-card');
    fields.style.display = cb.checked ? 'grid' : 'none';
    card.style.borderColor = cb.checked ? '#0ea5e9' : 'var(--border)';
}

function toggleSelfHostedPiprapay(cb) {
    const fields = document.getElementById('selfhosted_piprapay_fields');
    fields.style.display = cb.checked ? 'grid' : 'none';
}

async function openSyncModal() {
    document.getElementById('syncModal').classList.add('open');
    const tbody = document.getElementById('syncList');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Fetching profiles...</td></tr>';
    
    try {
        const res = await fetch('<?= base_url('settings/api/mikrotik-profiles') ?>');
        const profiles = await res.json();
        
        if (profiles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;">No profiles found on live NAS devices.</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        profiles.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-weight:600;">${p.name}</td>
                <td style="font-family:monospace;font-size:12px;">${p.rate_limit || 'No Limit'}</td>
                <td style="font-size:11px;color:var(--text2);">${p.nas_name}</td>
                <td><button class="btn btn-primary btn-xs" onclick="importAsPackage('${p.name}', ${p.download}, ${p.upload})">Import</button></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--red);">Failed to connect to MikroTik.</td></tr>';
    }
}

function importAsPackage(name, down, up) {
    document.getElementById('syncModal').classList.remove('open');
    document.getElementById('addPackageModal').classList.add('open');
    
    document.getElementById('pkg_name').value = name;
    document.getElementById('pkg_down').value = down || '';
    document.getElementById('pkg_up').value = up || '';
    document.getElementById('pkg_profile').value = name;
}

function openEditPackageModal(pkg) {
    document.getElementById('editPackageModal').classList.add('open');
    document.getElementById('edit_pkg_id').value = pkg.id;
    document.getElementById('edit_pkg_name').value = pkg.name;
    document.getElementById('edit_pkg_down').value = pkg.speed_download;
    document.getElementById('edit_pkg_up').value = pkg.speed_upload;
    document.getElementById('edit_pkg_price').value = pkg.price;
    document.getElementById('edit_pkg_type').value = pkg.type;
    document.getElementById('edit_pkg_profile').value = pkg.radius_profile;
    document.getElementById('edit_pkg_status').value = pkg.is_active;
}

let currentDeleteId = 0;
let currentDeleteType = '';

function deletePackage(id) {
    currentDeleteId = id;
    currentDeleteType = 'package';
    document.getElementById('deleteConfirmText').innerText = 'Are you sure you want to delete this package? This cannot be undone.';
    document.getElementById('deleteConfirmModal').classList.add('open');
}

function deleteBranch(id) {
    currentDeleteId = id;
    currentDeleteType = 'branch';
    document.getElementById('deleteConfirmText').innerText = 'Are you sure you want to delete this branch? All associated zones and data will be affected.';
    document.getElementById('deleteConfirmModal').classList.add('open');
}

function deleteZone(id) {
    currentDeleteId = id;
    currentDeleteType = 'zone';
    document.getElementById('deleteConfirmText').innerText = 'Are you sure you want to delete this zone?';
    document.getElementById('deleteConfirmModal').classList.add('open');
}

function deleteUser(id) {
    currentDeleteId = id;
    currentDeleteType = 'user';
    document.getElementById('deleteConfirmText').innerText = 'Are you sure you want to delete this staff user? This user will no longer be able to log in.';
    document.getElementById('deleteConfirmModal').classList.add('open');
}

function executeDelete() {
    let formId = '';
    let inputId = '';
    if (currentDeleteType === 'package') { formId = 'deletePackageForm'; inputId = 'delete_pkg_id'; }
    else if (currentDeleteType === 'branch') { formId = 'deleteBranchForm'; inputId = 'delete_branch_id'; }
    else if (currentDeleteType === 'zone') { formId = 'deleteZoneForm'; inputId = 'delete_zone_id'; }
    else if (currentDeleteType === 'user') { formId = 'deleteUserForm'; inputId = 'delete_user_id'; }
    
    if (formId && inputId) {
        document.getElementById(inputId).value = currentDeleteId;
        document.getElementById(formId).submit();
    }
}

function openEditBranchModal(b) {
    document.getElementById('editBranchModal').classList.add('open');
    document.getElementById('edit_branch_id').value = b.id;
    document.getElementById('edit_branch_name').value = b.name;
    document.getElementById('edit_branch_code').value = b.code;
    document.getElementById('edit_branch_phone').value = b.phone || '';
    document.getElementById('edit_branch_email').value = b.email || '';
    document.getElementById('edit_branch_manager').value = b.manager_name || '';
    document.getElementById('edit_branch_address').value = b.address || '';
    document.getElementById('edit_branch_status').value = b.is_active;
}

function openEditZoneModal(z) {
    document.getElementById('editZoneModal').classList.add('open');
    document.getElementById('edit_zone_id').value = z.id;
    document.getElementById('edit_zone_branch').value = z.branch_id;
    document.getElementById('edit_zone_name').value = z.name;
    document.getElementById('edit_zone_code').value = z.code || '';
    document.getElementById('edit_zone_desc').value = z.description || '';
    document.getElementById('edit_zone_status').value = z.is_active;
}

function openEditUserModal(u) {
    document.getElementById('editUserModal').classList.add('open');
    document.getElementById('edit_user_id').value = u.id;
    document.getElementById('edit_user_name').value = u.full_name;
    document.getElementById('edit_user_username').value = u.username;
    document.getElementById('edit_user_email').value = u.email || '';
    document.getElementById('edit_user_phone').value = u.phone || '';
    document.getElementById('edit_user_role').value = u.role_id;
    document.getElementById('edit_user_branch').value = u.branch_id || '';
    document.getElementById('edit_user_status').value = u.is_active;
    document.getElementById('edit_user_password').value = ''; // Clear password field
}
</script>

<!-- Sync Profiles Modal -->
<div class="modal-overlay" id="syncModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-sync" style="color:var(--blue);margin-right:8px;"></i>Sync MikroTik Profiles</div>
            <button class="icon-btn" onclick="document.getElementById('syncModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="padding:0;">
            <div style="max-height:400px;overflow-y:auto;">
                <table class="data-table">
                    <thead><tr><th>Profile Name</th><th>Rate Limit</th><th>NAS</th><th>Action</th></tr></thead>
                    <tbody id="syncList"></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('syncModal').classList.remove('open')">Close</button>
        </div>
    </div>
</div>
<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--blue);margin-right:8px;"></i>Add Staff User</div>
            <button class="icon-btn" onclick="document.getElementById('addUserModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('users/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-input" required></div>
                <div><label class="form-label">Username</label><input type="text" name="username" class="form-input" required></div>
                <div><label class="form-label">Password</label><input type="password" name="password" class="form-input" placeholder="Min 6 chars" required></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input"></div>
                <div><label class="form-label">Phone</label><input type="text" name="phone" class="form-input"></div>
                <div><label class="form-label">Role</label>
                    <select name="role_id" class="form-input" required>
                        <?php foreach($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Branch</label>
                    <select name="branch_id" class="form-input">
                        <option value="">All Branches</option>
                        <?php foreach($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addUserModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-edit" style="color:var(--blue);margin-right:8px;"></i>Edit Staff User</div>
            <button class="icon-btn" onclick="document.getElementById('editUserModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('users/update') ?>">
            <input type="hidden" name="id" id="edit_user_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">Full Name</label><input type="text" name="full_name" id="edit_user_name" class="form-input" required></div>
                <div><label class="form-label">Username</label><input type="text" name="username" id="edit_user_username" class="form-input" required></div>
                <div><label class="form-label">Change Password</label><input type="password" name="password" id="edit_user_password" class="form-input" placeholder="Leave blank to keep current"></div>
                <div><label class="form-label">Email</label><input type="email" name="email" id="edit_user_email" class="form-input"></div>
                <div><label class="form-label">Phone</label><input type="text" name="phone" id="edit_user_phone" class="form-input"></div>
                <div><label class="form-label">Role</label>
                    <select name="role_id" id="edit_user_role" class="form-input" required>
                        <?php foreach($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Branch</label>
                    <select name="branch_id" id="edit_user_branch" class="form-input">
                        <option value="">All Branches</option>
                        <?php foreach($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Status</label>
                    <select name="is_active" id="edit_user_status" class="form-input">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editUserModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteUserForm" method="POST" action="<?= base_url('users/delete') ?>" style="display:none;"><input type="hidden" name="id" id="delete_user_id"></form>
