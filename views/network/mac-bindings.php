<?php // views/network/mac-bindings.php ?>
<style>
.kpi-row { display:flex; gap:12px; margin-bottom:16px; }
.kpi-card { flex:1; background:var(--bg2); padding:16px; border-radius:8px; border:1px solid var(--border); }
.kpi-card .label { font-size:11px; color:var(--text2); text-transform:uppercase; letter-spacing:0.5px; }
.kpi-card .value { font-size:28px; font-weight:700; color:var(--text); margin-top:4px; }
.filter-tabs { display:flex; gap:4px; margin-bottom:16px; }
.filter-tab { padding:6px 16px; border-radius:6px; border:1px solid var(--border); background:var(--bg2); color:var(--text2); cursor:pointer; font-size:13px; font-weight:500; transition:all 0.15s; }
.filter-tab.active { background:var(--blue); border-color:var(--blue); color:#fff; }
.filter-tab:hover:not(.active) { background:var(--bg3); color:var(--text); }
.device-type-row { display:flex; gap:16px; align-items:center; }
.device-type-row label { display:flex; align-items:center; gap:6px; cursor:pointer; font-size:13px; font-weight:500; }
</style>

<div class="page-header fade-in" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1 class="page-title">MAC Bindings &amp; CallerID</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-network-wired" style="color:var(--blue)"></i> Network <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:0.5;"></i> MAC Bindings</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fa-solid fa-plus"></i> Add Binding</button>
</div>

<?php if(isset($_SESSION['success'])): ?>
<div style="background:rgba(16,185,129,0.1); border:1px solid var(--green); color:var(--green); padding:12px; border-radius:8px; margin-bottom:16px; font-weight:600;">
    <i class="fa-solid fa-check-circle" style="margin-right:8px;"></i> <?= $_SESSION['success'] ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if(isset($_SESSION['error'])): ?>
<div style="background:rgba(239,68,68,0.1); border:1px solid var(--red); color:var(--red); padding:12px; border-radius:8px; margin-bottom:16px; font-weight:600;">
    <i class="fa-solid fa-triangle-exclamation" style="margin-right:8px;"></i> <?= $_SESSION['error'] ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<?php
$totalBindings  = count($bindings);
$activeBindings = count(array_filter($bindings, fn($b) => $b['is_active']));
$onuBindings    = count(array_filter($bindings, fn($b) => strtolower($b['device_type']) === 'onu'));
$routerBindings = count(array_filter($bindings, fn($b) => strtolower($b['device_type']) === 'router'));
?>

<div class="kpi-row fade-in">
    <div class="kpi-card">
        <div class="label">Total Bindings</div>
        <div class="value"><?= $totalBindings ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Active</div>
        <div class="value"><?= $activeBindings ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">ONU Bindings</div>
        <div class="value"><?= $onuBindings ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Router Bindings</div>
        <div class="value"><?= $routerBindings ?></div>
    </div>
</div>

<div class="filter-tabs fade-in">
    <button class="filter-tab active" data-filter="all">All</button>
    <button class="filter-tab" data-filter="onu">ONU MAC</button>
    <button class="filter-tab" data-filter="router">Router MAC</button>
</div>

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table" id="bindingsTable">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Device Type</th>
                <th>MAC Address</th>
                <th>Caller ID / ONU Serial</th>
                <th>Brand / Model</th>
                <th>Binding Type</th>
                <th>NAS</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($bindings)): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2);">No MAC bindings found</td></tr>
            <?php else: foreach($bindings as $b): ?>
            <?php $dtype = strtolower($b['device_type']); ?>
            <tr data-device="<?= htmlspecialchars($dtype) ?>">
                <td>
                    <?php if(!empty($b['customer_id'])): ?>
                    <a href="<?= base_url("customers/view/{$b['customer_id']}") ?>" style="font-weight:600;color:var(--blue);text-decoration:none;"><?= htmlspecialchars($b['customer_name']) ?></a>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($b['customer_code']) ?></div>
                    <div style="font-size:11px;color:var(--text2);font-family:monospace;"><?= htmlspecialchars($b['username']) ?></div>
                    <?php else: ?>
                    <span style="color:var(--text2);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($dtype === 'onu'): ?>
                    <span class="badge badge-blue"><i class="fa-solid fa-tower-broadcast"></i> ONU</span>
                    <?php elseif($dtype === 'router'): ?>
                    <span class="badge badge-purple"><i class="fa-solid fa-router"></i> Router</span>
                    <?php else: ?>
                    <span class="badge badge-gray"><i class="fa-solid fa-microchip"></i> <?= htmlspecialchars($b['device_type']) ?></span>
                    <?php endif; ?>
                </td>
                <td><code style="background:var(--bg3);padding:4px 8px;border-radius:4px;font-size:12px;"><?= htmlspecialchars($b['mac_address']) ?></code></td>
                <td>
                    <?php if($dtype === 'onu' && !empty($b['onu_serial'])): ?>
                    <span style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($b['onu_serial']) ?></span>
                    <?php elseif(!empty($b['caller_id'])): ?>
                    <span style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($b['caller_id']) ?></span>
                    <?php else: ?>
                    <span style="color:var(--text2);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!empty($b['router_brand']) || !empty($b['router_model'])): ?>
                    <span style="font-size:13px;"><?= htmlspecialchars(trim($b['router_brand'] . ' ' . $b['router_model'])) ?></span>
                    <?php else: ?>
                    <span style="color:var(--text2);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $btLabel = match($b['binding_type'] ?? '') {
                        'pppoe_callerid' => ['PPPoE CallerID', 'badge-blue'],
                        'mac_auth'       => ['MAC Auth',       'badge-purple'],
                        'static_ip'      => ['Static IP',      'badge-gray'],
                        default          => [htmlspecialchars($b['binding_type'] ?? '—'), 'badge-gray'],
                    };
                    ?>
                    <span class="badge <?= $btLabel[1] ?>"><?= $btLabel[0] ?></span>
                </td>
                <td><?= htmlspecialchars($b['nas_name'] ?? '—') ?></td>
                <td>
                    <?php if($b['is_active']): ?>
                    <span class="badge badge-green">Active</span>
                    <?php else: ?>
                    <span class="badge badge-gray">Inactive</span>
                    <?php endif; ?>
                    <?php if(!$b['is_allowed']): ?>
                    <span class="badge badge-red" style="margin-top:2px;">Blocked</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <button class="btn btn-ghost btn-sm" title="Edit"
                        onclick="editBinding(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form action="<?= base_url('network/mac-bindings/toggle/' . $b['id']) ?>" method="POST" style="display:inline;">
                        <button type="submit" class="btn btn-ghost btn-sm" title="<?= $b['is_active'] ? 'Disable' : 'Enable' ?>">
                            <i class="fa-solid fa-toggle-<?= $b['is_active'] ? 'on' : 'off' ?>"></i>
                        </button>
                    </form>
                    <form action="<?= base_url('network/mac-bindings/delete/' . $b['id']) ?>" method="POST" style="display:inline;" onsubmit="return confirm('Delete this MAC binding?');">
                        <button type="submit" class="btn btn-ghost btn-sm" title="Delete" style="color:var(--red);"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- ===================== ADD MODAL ===================== -->
<div id="addModal" class="modal-overlay">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title">Add MAC Binding</div>
            <button class="icon-btn" onclick="closeModal('addModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('network/mac-bindings/store') ?>">
            <div class="modal-body" style="display:grid;gap:14px;">

                <div>
                    <label class="form-label">Customer *</label>
                    <select name="customer_id" id="add_customer_id" class="form-input" onchange="fillCustomerAdd(this)">
                        <option value="">— Select Customer —</option>
                        <?php foreach($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            data-username="<?= htmlspecialchars($c['pppoe_username'] ?? '') ?>"
                            data-onu-serial="<?= htmlspecialchars($c['onu_serial'] ?? '') ?>"
                            data-onu-mac="<?= htmlspecialchars($c['onu_mac'] ?? '') ?>">
                            <?= htmlspecialchars($c['full_name'] . ' (' . $c['customer_code'] . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Device Type *</label>
                    <div class="device-type-row">
                        <label><input type="radio" name="device_type" value="ONU" onchange="toggleDeviceFields('add','ONU')" checked> ONU</label>
                        <label><input type="radio" name="device_type" value="Router" onchange="toggleDeviceFields('add','Router')"> Router</label>
                        <label><input type="radio" name="device_type" value="Other" onchange="toggleDeviceFields('add','Other')"> Other</label>
                    </div>
                </div>

                <div>
                    <label class="form-label">PPPoE Username</label>
                    <input type="text" name="username" id="add_username" class="form-input" placeholder="pppoe-username">
                </div>

                <div>
                    <label class="form-label">MAC Address *</label>
                    <input type="text" name="mac_address" id="add_mac_address" class="form-input" required placeholder="AA:BB:CC:DD:EE:FF" style="font-family:monospace;">
                </div>

                <div>
                    <label class="form-label">Binding Type *</label>
                    <select name="binding_type" class="form-input">
                        <option value="pppoe_callerid">PPPoE CallerID</option>
                        <option value="mac_auth">MAC Auth</option>
                        <option value="static_ip">Static IP</option>
                    </select>
                </div>

                <div id="add_caller_id_row" style="display:none;">
                    <label class="form-label">Caller ID</label>
                    <input type="text" name="caller_id" id="add_caller_id" class="form-input" placeholder="Caller ID" style="font-family:monospace;">
                </div>

                <div id="add_onu_serial_row">
                    <label class="form-label">ONU Serial</label>
                    <input type="text" name="onu_serial" id="add_onu_serial" class="form-input" placeholder="ONU Serial Number" style="font-family:monospace;">
                </div>

                <div id="add_router_brand_row" style="display:none;">
                    <label class="form-label">Router Brand</label>
                    <input type="text" name="router_brand" id="add_router_brand" class="form-input" placeholder="e.g. MikroTik, TP-Link">
                </div>

                <div id="add_router_model_row" style="display:none;">
                    <label class="form-label">Router Model</label>
                    <input type="text" name="router_model" id="add_router_model" class="form-input" placeholder="e.g. hAP ac2">
                </div>

                <div>
                    <label class="form-label">IP Address <span style="color:var(--text2);font-weight:400;">(optional)</span></label>
                    <input type="text" name="ip_address" class="form-input" placeholder="192.168.1.x">
                </div>

                <div>
                    <label class="form-label">NAS Server</label>
                    <select name="nas_id" class="form-input">
                        <option value="">— Any NAS —</option>
                        <?php foreach($nasDevices as $n): ?>
                        <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['name'] . ' (' . $n['ip_address'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" name="is_allowed" id="add_is_allowed" value="1" checked style="width:16px;height:16px;cursor:pointer;">
                    <label for="add_is_allowed" class="form-label" style="margin:0;cursor:pointer;">Allow this binding (uncheck to block)</label>
                </div>

                <div>
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-input" placeholder="Optional notes">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Binding</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== EDIT MODAL ===================== -->
<div id="editModal" class="modal-overlay">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title">Edit MAC Binding</div>
            <button class="icon-btn" onclick="closeModal('editModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="editForm" action="">
            <input type="hidden" name="_method" value="PUT">
            <div class="modal-body" style="display:grid;gap:14px;">

                <div>
                    <label class="form-label">Customer *</label>
                    <select name="customer_id" id="edit_customer_id" class="form-input" onchange="fillCustomerEdit(this)">
                        <option value="">— Select Customer —</option>
                        <?php foreach($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            data-username="<?= htmlspecialchars($c['pppoe_username'] ?? '') ?>"
                            data-onu-serial="<?= htmlspecialchars($c['onu_serial'] ?? '') ?>"
                            data-onu-mac="<?= htmlspecialchars($c['onu_mac'] ?? '') ?>">
                            <?= htmlspecialchars($c['full_name'] . ' (' . $c['customer_code'] . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Device Type *</label>
                    <div class="device-type-row">
                        <label><input type="radio" name="device_type" value="ONU" onchange="toggleDeviceFields('edit','ONU')"> ONU</label>
                        <label><input type="radio" name="device_type" value="Router" onchange="toggleDeviceFields('edit','Router')"> Router</label>
                        <label><input type="radio" name="device_type" value="Other" onchange="toggleDeviceFields('edit','Other')"> Other</label>
                    </div>
                </div>

                <div>
                    <label class="form-label">PPPoE Username</label>
                    <input type="text" name="username" id="edit_username" class="form-input" placeholder="pppoe-username">
                </div>

                <div>
                    <label class="form-label">MAC Address *</label>
                    <input type="text" name="mac_address" id="edit_mac_address" class="form-input" required placeholder="AA:BB:CC:DD:EE:FF" style="font-family:monospace;">
                </div>

                <div>
                    <label class="form-label">Binding Type *</label>
                    <select name="binding_type" id="edit_binding_type" class="form-input">
                        <option value="pppoe_callerid">PPPoE CallerID</option>
                        <option value="mac_auth">MAC Auth</option>
                        <option value="static_ip">Static IP</option>
                    </select>
                </div>

                <div id="edit_caller_id_row" style="display:none;">
                    <label class="form-label">Caller ID</label>
                    <input type="text" name="caller_id" id="edit_caller_id" class="form-input" placeholder="Caller ID" style="font-family:monospace;">
                </div>

                <div id="edit_onu_serial_row">
                    <label class="form-label">ONU Serial</label>
                    <input type="text" name="onu_serial" id="edit_onu_serial" class="form-input" placeholder="ONU Serial Number" style="font-family:monospace;">
                </div>

                <div id="edit_router_brand_row" style="display:none;">
                    <label class="form-label">Router Brand</label>
                    <input type="text" name="router_brand" id="edit_router_brand" class="form-input" placeholder="e.g. MikroTik, TP-Link">
                </div>

                <div id="edit_router_model_row" style="display:none;">
                    <label class="form-label">Router Model</label>
                    <input type="text" name="router_model" id="edit_router_model" class="form-input" placeholder="e.g. hAP ac2">
                </div>

                <div>
                    <label class="form-label">IP Address <span style="color:var(--text2);font-weight:400;">(optional)</span></label>
                    <input type="text" name="ip_address" id="edit_ip_address" class="form-input" placeholder="192.168.1.x">
                </div>

                <div>
                    <label class="form-label">NAS Server</label>
                    <select name="nas_id" id="edit_nas_id" class="form-input">
                        <option value="">— Any NAS —</option>
                        <?php foreach($nasDevices as $n): ?>
                        <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['name'] . ' (' . $n['ip_address'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" name="is_allowed" id="edit_is_allowed" value="1" style="width:16px;height:16px;cursor:pointer;">
                    <label for="edit_is_allowed" class="form-label" style="margin:0;cursor:pointer;">Allow this binding (uncheck to block)</label>
                </div>

                <div>
                    <label class="form-label">Description</label>
                    <input type="text" name="description" id="edit_description" class="form-input" placeholder="Optional notes">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Binding</button>
            </div>
        </form>
    </div>
</div>

<script>
/* ---- Modal helpers ---- */
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) { if (e.target === o) o.classList.remove('open'); });
});

/* ---- Customer auto-fill (Add) ---- */
function fillCustomerAdd(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('add_username').value    = opt.dataset.username  || '';
    document.getElementById('add_onu_serial').value  = opt.dataset.onuSerial || '';
    document.getElementById('add_mac_address').value = opt.dataset.onuMac    || '';
}

/* ---- Customer auto-fill (Edit) ---- */
function fillCustomerEdit(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('edit_username').value    = opt.dataset.username  || '';
    document.getElementById('edit_onu_serial').value  = opt.dataset.onuSerial || '';
}

/* ---- Toggle ONU / Router specific fields ---- */
function toggleDeviceFields(prefix, type) {
    var show = function(id, visible) {
        var el = document.getElementById(prefix + '_' + id);
        if (el) el.style.display = visible ? '' : 'none';
    };
    if (type === 'ONU') {
        show('onu_serial_row',    true);
        show('caller_id_row',     false);
        show('router_brand_row',  false);
        show('router_model_row',  false);
    } else if (type === 'Router') {
        show('onu_serial_row',    false);
        show('caller_id_row',     true);
        show('router_brand_row',  true);
        show('router_model_row',  true);
    } else {
        show('onu_serial_row',    false);
        show('caller_id_row',     false);
        show('router_brand_row',  false);
        show('router_model_row',  false);
    }
}

/* ---- Filter tabs ---- */
document.querySelectorAll('.filter-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(function(t) { t.classList.remove('active'); });
        tab.classList.add('active');
        var filter = tab.dataset.filter;
        document.querySelectorAll('#bindingsTable tbody tr').forEach(function(row) {
            if (filter === 'all') {
                row.style.display = '';
            } else {
                row.style.display = (row.dataset.device === filter) ? '' : 'none';
            }
        });
    });
});

/* ---- Edit binding: populate form ---- */
function editBinding(b) {
    var baseUrl = '<?= base_url('network/mac-bindings/update') ?>';
    document.getElementById('editForm').action = baseUrl + '/' + b.id;

    /* Customer */
    var custSel = document.getElementById('edit_customer_id');
    for (var i = 0; i < custSel.options.length; i++) {
        if (custSel.options[i].value == b.customer_id) { custSel.selectedIndex = i; break; }
    }

    /* Device type radio */
    var dtype = (b.device_type || 'ONU');
    document.querySelectorAll('#editModal input[name="device_type"]').forEach(function(r) {
        r.checked = (r.value === dtype);
    });
    toggleDeviceFields('edit', dtype);

    /* Text fields */
    document.getElementById('edit_username').value    = b.username      || '';
    document.getElementById('edit_mac_address').value = b.mac_address   || '';
    document.getElementById('edit_caller_id').value   = b.caller_id     || '';
    document.getElementById('edit_onu_serial').value  = b.onu_serial    || '';
    document.getElementById('edit_router_brand').value= b.router_brand  || '';
    document.getElementById('edit_router_model').value= b.router_model  || '';
    document.getElementById('edit_ip_address').value  = b.ip_address    || '';
    document.getElementById('edit_description').value = b.description   || '';

    /* Binding type select */
    var btSel = document.getElementById('edit_binding_type');
    for (var j = 0; j < btSel.options.length; j++) {
        if (btSel.options[j].value === b.binding_type) { btSel.selectedIndex = j; break; }
    }

    /* NAS select */
    var nasSel = document.getElementById('edit_nas_id');
    for (var k = 0; k < nasSel.options.length; k++) {
        /* match by nas_name since we only have name in binding row */
        if (nasSel.options[k].text.indexOf(b.nas_name) !== -1 && b.nas_name) {
            nasSel.selectedIndex = k; break;
        }
    }

    /* Checkboxes */
    document.getElementById('edit_is_allowed').checked = (b.is_allowed == 1 || b.is_allowed === true);

    openModal('editModal');
}
</script>
