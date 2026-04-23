<?php // views/roles/form.php
$isEdit      = isset($role) && $role !== null;
$formAction  = $isEdit ? base_url("roles/update/{$role['id']}") : base_url('roles/store');
$isProtected = $isEdit && in_array($role['name'] ?? '', ['superadmin', 'comadmin']);
$pageHeading = $isEdit ? "Edit Role: {$role['display_name']}" : 'Create New Role';

// Module display names
$moduleLabels = [
    'branches'        => ['Branches',          'fa-building',            '#d97706'],
    'hr'              => ['HR & Payroll',       'fa-users-gear',          '#7c3aed'],
    'support'         => ['Support & Tickets',  'fa-headset',             '#2563eb'],
    'tasks'           => ['Task Management',    'fa-clipboard-list',      '#16a34a'],
    'sales'           => ['Sales & Invoicing',  'fa-file-invoice-dollar', '#0891b2'],
    'purchases'       => ['Purchase Mgmt',      'fa-cart-shopping',       '#d97706'],
    'inventory'       => ['Inventory',          'fa-boxes-stacked',       '#16a34a'],
    'network'         => ['Network',            'fa-server',              '#2563eb'],
    'accounts'        => ['Accounts',           'fa-coins',               '#d97706'],
    'assets'          => ['Asset Management',   'fa-laptop',              '#7c3aed'],
    'bandwidth'       => ['Bandwidth',          'fa-wifi',                '#0891b2'],
    'reseller_portal' => ['Reseller Portal',    'fa-handshake',           '#16a34a'],
    'mac_reseller'    => ['MAC Resellers',      'fa-network-wired',       '#2563eb'],
    'employee_portal' => ['Employee Portal',    'fa-id-badge',            '#7c3aed'],
    'btrc_reports'    => ['BTRC Reports',       'fa-file-chart-column',   '#d97706'],
    'ott'             => ['OTT Subscriptions',  'fa-tv',                  '#0891b2'],
    'roles'           => ['Roles & Permissions','fa-shield-halved',       '#dc2626'],
    'campaigns'       => ['Campaigns / SMS',    'fa-bullhorn',            '#16a34a'],
    'api'             => ['Android API',        'fa-mobile-screen',       '#2563eb'],
    'configuration'   => ['Configuration',      'fa-sliders',             '#7c3aed'],
];

// Action display names
$actionLabels = [
    'view'          => ['View',           'fa-eye',              '#2563eb'],
    'create'        => ['Create',         'fa-plus',             '#16a34a'],
    'edit'          => ['Edit',           'fa-pen',              '#d97706'],
    'delete'        => ['Delete',         'fa-trash',            '#dc2626'],
    'reports'       => ['Reports',        'fa-chart-bar',        '#7c3aed'],
    'payroll'       => ['Payroll',        'fa-money-bill',       '#16a34a'],
    'attendance'    => ['Attendance',     'fa-calendar-check',   '#0891b2'],
    'appraisal'     => ['Appraisal',      'fa-star',             '#d97706'],
    'leave'         => ['Leave',          'fa-calendar-xmark',   '#dc2626'],
    'assign'        => ['Assign',         'fa-user-check',       '#2563eb'],
    'resolve'       => ['Resolve',        'fa-circle-check',     '#16a34a'],
    'payments'      => ['Payments',       'fa-credit-card',      '#16a34a'],
    'cancel'        => ['Cancel',         'fa-ban',              '#dc2626'],
    'approve'       => ['Approve',        'fa-thumbs-up',        '#16a34a'],
    'issue'         => ['Issue Stock',    'fa-arrow-right',      '#d97706'],
    'transfer'      => ['Transfer',       'fa-arrows-rotate',    '#0891b2'],
    'export'        => ['Export',         'fa-download',         '#7c3aed'],
    'dispose'       => ['Dispose',        'fa-trash-can',        '#dc2626'],
    'invoices'      => ['Invoices',       'fa-file-invoice',     '#2563eb'],
    'suspend'       => ['Suspend',        'fa-pause',            '#dc2626'],
    'billing'       => ['Billing',        'fa-file-invoice-dollar','#d97706'],
    'collections'   => ['Collections',   'fa-hand-holding-dollar','#16a34a'],
    'generate'      => ['Generate',       'fa-bolt',             '#d97706'],
    'activate'      => ['Activate',       'fa-play',             '#16a34a'],
    'deactivate'    => ['Deactivate',     'fa-stop',             '#dc2626'],
    'send'          => ['Send',           'fa-paper-plane',      '#2563eb'],
    'tokens'        => ['API Tokens',     'fa-key',              '#7c3aed'],
    'manage'        => ['Manage',         'fa-gear',             '#d97706'],
    'zones'         => ['Zones',          'fa-map',              '#0891b2'],
    'packages'      => ['Packages',       'fa-wifi',             '#16a34a'],
    'billing_rules' => ['Billing Rules',  'fa-calendar',         '#d97706'],
    'templates'     => ['Templates',      'fa-file-lines',       '#7c3aed'],
];
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= $pageHeading ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('roles') ?>" style="color:var(--blue);text-decoration:none;">Roles</a> ›
            <?= $isEdit ? 'Edit' : 'New' ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if ($isEdit): ?>
        <a href="<?= base_url("roles/users/{$role['id']}") ?>" class="btn btn-ghost">
            <i class="fa-solid fa-users"></i> View Users
        </a>
        <?php endif; ?>
        <a href="<?= base_url('roles') ?>" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:10px;color:var(--green);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['success']) ?>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;color:var(--red);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['error']) ?>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $formAction ?>" id="roleForm">
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;">

    <!-- LEFT: Permissions -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Quick select toolbar -->
        <div class="card fade-in" style="padding:14px 18px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span style="font-size:12px;font-weight:700;color:var(--text2);">Quick Select:</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="selectAll()"><i class="fa-solid fa-check-double"></i> All</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="selectNone()"><i class="fa-solid fa-xmark"></i> None</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="selectViewOnly()"><i class="fa-solid fa-eye"></i> View Only</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="selectNoDelete()"><i class="fa-solid fa-shield"></i> No Delete</button>
                <div style="margin-left:auto;font-size:12px;color:var(--text2);">
                    <span id="selectedCount">0</span> permissions selected
                </div>
            </div>
        </div>

        <!-- Permission modules -->
        <?php foreach ($modules as $moduleName => $permissions):
            [$label, $icon, $color] = $moduleLabels[$moduleName] ?? [$moduleName, 'fa-circle', '#64748b'];
        ?>
        <div class="card fade-in" style="overflow:hidden;">
            <div style="padding:12px 16px;background:var(--bg3);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:8px;background:<?= $color ?>22;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid <?= $icon ?>" style="color:<?= $color ?>;font-size:13px;"></i>
                    </div>
                    <span style="font-size:13px;font-weight:700;"><?= $label ?></span>
                </div>
                <div style="display:flex;gap:6px;">
                    <button type="button" class="btn btn-ghost btn-xs" onclick="toggleModule('<?= $moduleName ?>', true)">All</button>
                    <button type="button" class="btn btn-ghost btn-xs" onclick="toggleModule('<?= $moduleName ?>', false)">None</button>
                </div>
            </div>
            <div style="padding:14px 16px;display:flex;flex-wrap:wrap;gap:10px;">
                <?php foreach ($permissions as $perm):
                    $action = explode('.', $perm)[1] ?? $perm;
                    [$aLabel, $aIcon, $aColor] = $actionLabels[$action] ?? [ucfirst($action), 'fa-circle', '#64748b'];
                    $checked = in_array($perm, $rolePermissions ?? []);
                    $disabled = $isProtected && $role['name'] === 'superadmin';
                ?>
                <label class="perm-chip <?= $moduleName ?>" style="display:flex;align-items:center;gap:7px;padding:7px 12px;border-radius:8px;cursor:pointer;border:1.5px solid <?= $checked ? $aColor : 'var(--border)' ?>;background:<?= $checked ? $aColor.'18' : 'var(--bg3)' ?>;transition:all 0.15s;user-select:none;"
                       data-module="<?= $moduleName ?>" data-action="<?= $action ?>">
                    <input type="checkbox" name="permissions[]" value="<?= $perm ?>"
                           <?= $checked ? 'checked' : '' ?>
                           <?= $disabled ? 'disabled checked' : '' ?>
                           onchange="updateChip(this)"
                           style="display:none;">
                    <i class="fa-solid <?= $aIcon ?>" style="font-size:11px;color:<?= $aColor ?>;"></i>
                    <span style="font-size:12px;font-weight:600;color:var(--text);"><?= $aLabel ?></span>
                    <i class="fa-solid fa-check perm-check" style="font-size:10px;color:<?= $aColor ?>;<?= $checked ? '' : 'display:none;' ?>"></i>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- RIGHT: Role info + save -->
    <div style="display:flex;flex-direction:column;gap:16px;align-self:start;position:sticky;top:80px;">
        <div class="card fade-in" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;">
                <i class="fa-solid fa-shield-halved" style="color:var(--blue);margin-right:8px;"></i>Role Details
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Display Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="display_name" class="form-input" required
                           value="<?= htmlspecialchars($role['display_name'] ?? '') ?>"
                           placeholder="e.g. Branch Manager">
                </div>
                <div>
                    <label class="form-label">Role Key <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-input" required
                           value="<?= htmlspecialchars($role['name'] ?? '') ?>"
                           placeholder="e.g. branch_manager"
                           <?= $isProtected ? 'readonly style="background:var(--bg3);color:var(--text2);"' : '' ?>
                           oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,'_')">
                    <div style="font-size:11px;color:var(--text2);margin-top:4px;">Lowercase letters, numbers, underscores only</div>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3"
                              placeholder="What can this role do?"><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Permission summary -->
        <div class="card fade-in" style="padding:16px;">
            <div style="font-size:12px;font-weight:700;margin-bottom:10px;color:var(--text2);">Permission Summary</div>
            <div id="permSummary" style="display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto;font-size:12px;">
                <!-- Filled by JS -->
            </div>
        </div>

        <?php if ($isProtected && $role['name'] === 'superadmin'): ?>
        <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:8px;padding:12px;font-size:12px;color:var(--red);">
            <i class="fa-solid fa-lock" style="margin-right:6px;"></i>
            Superadmin always has all permissions. Checkboxes are read-only.
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;">
            <i class="fa-solid fa-save"></i> <?= $isEdit ? 'Save Changes' : 'Create Role' ?>
        </button>
        <a href="<?= base_url('roles') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;">Cancel</a>
    </div>
</div>
</form>

<style>
.perm-chip:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.perm-chip input:checked ~ span { font-weight: 700; }
</style>

<script>
function updateChip(input) {
    const label = input.closest('label');
    const action = label.dataset.action;
    const actionColors = <?= json_encode(array_map(fn($v) => $v[2], $actionLabels)) ?>;
    const color = actionColors[action] || '#64748b';
    const checked = input.checked;
    label.style.borderColor = checked ? color : 'var(--border)';
    label.style.background   = checked ? color + '18' : 'var(--bg3)';
    label.querySelector('.perm-check').style.display = checked ? '' : 'none';
    updateCount();
    updateSummary();
}

function updateCount() {
    const n = document.querySelectorAll('input[name="permissions[]"]:checked').length;
    document.getElementById('selectedCount').textContent = n;
}

function updateSummary() {
    const summary = {};
    document.querySelectorAll('input[name="permissions[]"]:checked').forEach(cb => {
        const [mod, action] = cb.value.split('.');
        if (!summary[mod]) summary[mod] = [];
        summary[mod].push(action);
    });
    const el = document.getElementById('permSummary');
    if (Object.keys(summary).length === 0) {
        el.innerHTML = '<div style="color:var(--text2);font-style:italic;">No permissions selected</div>';
        return;
    }
    el.innerHTML = Object.entries(summary).map(([mod, actions]) =>
        `<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border);">
            <span style="font-weight:600;text-transform:capitalize;">${mod.replace('_',' ')}</span>
            <span style="color:var(--blue);font-weight:700;">${actions.length}</span>
        </div>`
    ).join('');
}

function selectAll() {
    document.querySelectorAll('input[name="permissions[]"]:not([disabled])').forEach(cb => {
        cb.checked = true; updateChip(cb);
    });
}
function selectNone() {
    document.querySelectorAll('input[name="permissions[]"]:not([disabled])').forEach(cb => {
        cb.checked = false; updateChip(cb);
    });
}
function selectViewOnly() {
    document.querySelectorAll('input[name="permissions[]"]:not([disabled])').forEach(cb => {
        cb.checked = cb.value.endsWith('.view'); updateChip(cb);
    });
}
function selectNoDelete() {
    document.querySelectorAll('input[name="permissions[]"]:not([disabled])').forEach(cb => {
        cb.checked = !cb.value.endsWith('.delete'); updateChip(cb);
    });
}
function toggleModule(mod, state) {
    document.querySelectorAll(`.perm-chip.${mod} input[name="permissions[]"]:not([disabled])`).forEach(cb => {
        cb.checked = state; updateChip(cb);
    });
}

// Init
updateCount();
updateSummary();
</script>
