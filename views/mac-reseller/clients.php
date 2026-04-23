<?php // views/mac-reseller/clients.php
$totalClients     = count($clients);
$activeClients    = count(array_filter($clients, fn($c) => $c['status'] === 'active'));
$suspendedClients = count(array_filter($clients, fn($c) => $c['status'] === 'suspended'));
?>
<style>
.client-search-bar { display:flex; gap:10px; align-items:center; padding:14px 18px; border-bottom:1px solid var(--border); }
.client-search-bar input { flex:1; max-width:320px; }
.filter-btn { padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1px solid var(--border); background:var(--bg2); color:var(--text2); transition:.15s; }
.filter-btn.active { background:var(--blue); color:#fff; border-color:var(--blue); }
.mac-badge { font-family:monospace; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px; background:rgba(99,102,241,0.12); color:#6366f1; letter-spacing:.04em; }
</style>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Clients — <?= htmlspecialchars($reseller['business_name']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('mac-resellers') ?>" style="color:var(--blue);text-decoration:none;">MAC Resellers</a> ›
            <a href="<?= base_url("mac-resellers/view/{$reseller['id']}") ?>" style="color:var(--blue);text-decoration:none;"><?= htmlspecialchars($reseller['business_name']) ?></a>
            › Clients
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="openAddClientModal()">
            <i class="fa-solid fa-plus"></i> Add Client
        </button>
        <a href="<?= base_url("mac-resellers/view/{$reseller['id']}") ?>" class="btn btn-ghost">
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

<!-- Stats row -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px;" class="fade-in">
    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-users" style="color:var(--blue);font-size:16px;"></i>
        </div>
        <div>
            <div style="font-size:22px;font-weight:900;color:var(--blue);"><?= $totalClients ?></div>
            <div style="font-size:11px;color:var(--text2);">Total Clients</div>
        </div>
    </div>
    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(34,197,94,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-circle-check" style="color:var(--green);font-size:16px;"></i>
        </div>
        <div>
            <div style="font-size:22px;font-weight:900;color:var(--green);"><?= $activeClients ?></div>
            <div style="font-size:11px;color:var(--text2);">Active</div>
        </div>
    </div>
    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(234,179,8,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-pause" style="color:var(--yellow);font-size:16px;"></i>
        </div>
        <div>
            <div style="font-size:22px;font-weight:900;color:var(--yellow);"><?= $suspendedClients ?></div>
            <div style="font-size:11px;color:var(--text2);">Suspended</div>
        </div>
    </div>
</div>

<!-- Table card -->
<div class="card fade-in" style="overflow:hidden;">
    <!-- Search + filter bar -->
    <div class="client-search-bar">
        <input type="text" id="clientSearch" class="form-input" placeholder="Search by name, phone, MAC or IP…" oninput="filterClients()" style="max-width:320px;">
        <div style="display:flex;gap:6px;margin-left:auto;">
            <button class="filter-btn active" id="filter-all"       onclick="setFilter('all')">All (<?= $totalClients ?>)</button>
            <button class="filter-btn"        id="filter-active"    onclick="setFilter('active')">Active (<?= $activeClients ?>)</button>
            <button class="filter-btn"        id="filter-suspended" onclick="setFilter('suspended')">Suspended (<?= $suspendedClients ?>)</button>
        </div>
    </div>

    <table class="data-table" id="clientsTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>MAC Address</th>
                <th>IP Address</th>
                <th>Tariff Plan</th>
                <th>Daily Rate</th>
                <th>Balance</th>
                <th>Joined</th>
                <th>Status</th>
                <th style="width:120px;">Actions</th>
            </tr>
        </thead>
        <tbody id="clientsBody">
            <?php if (empty($clients)): ?>
            <tr id="emptyRow">
                <td colspan="10" style="text-align:center;padding:48px;color:var(--text2);">
                    <i class="fa-solid fa-users" style="font-size:36px;display:block;margin-bottom:12px;opacity:.3;"></i>
                    No clients yet. <button onclick="openAddClientModal()" style="background:none;border:none;color:var(--blue);cursor:pointer;font-size:inherit;padding:0;">Add your first client</button>.
                </td>
            </tr>
            <?php else: foreach ($clients as $i => $c): ?>
            <tr data-status="<?= $c['status'] ?>"
                data-search="<?= strtolower(htmlspecialchars($c['full_name'].' '.$c['phone'].' '.$c['mac_address'].' '.($c['ip_address']??''))) ?>">
                <td style="color:var(--text2);font-size:12px;"><?= $i + 1 ?></td>
                <td>
                    <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($c['full_name']) ?></div>
                    <?php if ($c['phone']): ?>
                    <div style="font-size:11px;color:var(--text2);margin-top:2px;">
                        <i class="fa-solid fa-phone" style="font-size:9px;margin-right:3px;"></i><?= htmlspecialchars($c['phone']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($c['notes']): ?>
                    <div style="font-size:10px;color:var(--text2);margin-top:2px;font-style:italic;" title="<?= htmlspecialchars($c['notes']) ?>">
                        <?= htmlspecialchars(mb_strimwidth($c['notes'], 0, 30, '…')) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="mac-badge"><?= htmlspecialchars($c['mac_address']) ?></span>
                </td>
                <td style="font-family:monospace;font-size:12px;color:var(--text2);">
                    <?= $c['ip_address'] ? htmlspecialchars($c['ip_address']) : '<span style="opacity:.4;">—</span>' ?>
                </td>
                <td>
                    <?php if ($c['tariff_name']): ?>
                    <div style="font-size:12px;font-weight:600;"><?= htmlspecialchars($c['tariff_name']) ?></div>
                    <div style="font-size:10px;color:var(--text2);"><?= $c['speed_download'] ?>↓ / <?= $c['speed_upload'] ?>↑ Mbps</div>
                    <?php else: ?>
                    <span style="font-size:12px;color:var(--text2);opacity:.5;">No tariff</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:700;color:var(--green);font-size:13px;">
                    <?= $c['daily_rate'] > 0 ? '৳'.number_format($c['daily_rate'], 2) : '<span style="opacity:.4;">—</span>' ?>
                </td>
                <td style="font-weight:700;font-size:13px;<?= $c['balance'] >= 0 ? 'color:var(--green)' : 'color:var(--red)' ?>">
                    ৳<?= number_format($c['balance'], 2) ?>
                </td>
                <td style="font-size:11px;color:var(--text2);">
                    <?= $c['joined_date'] ? date('d M Y', strtotime($c['joined_date'])) : '—' ?>
                </td>
                <td>
                    <?php $bc = match($c['status']) { 'active' => 'badge-green', 'suspended' => 'badge-yellow', default => 'badge-gray' }; ?>
                    <span class="badge <?= $bc ?>"><?= ucfirst($c['status']) ?></span>
                </td>
                <td>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <button class="btn btn-ghost btn-sm" onclick='openEditClient(<?= json_encode($c) ?>)' title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" action="<?= base_url("mac-resellers/clients/suspend/{$c['id']}") ?>" style="display:inline;">
                            <button type="submit" class="btn btn-ghost btn-sm"
                                    title="<?= $c['status'] === 'active' ? 'Suspend' : 'Reactivate' ?>"
                                    style="<?= $c['status'] === 'active' ? 'color:var(--yellow)' : 'color:var(--green)' ?>">
                                <i class="fa-solid <?= $c['status'] === 'active' ? 'fa-pause' : 'fa-play' ?>"></i>
                            </button>
                        </form>
                        <form method="POST" action="<?= base_url("mac-resellers/clients/delete/{$c['id']}") ?>"
                              onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($c['full_name'])) ?>? This cannot be undone.');"
                              style="display:inline;">
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- No results row (hidden by default) -->
    <div id="noResults" style="display:none;padding:32px;text-align:center;color:var(--text2);font-size:13px;">
        <i class="fa-solid fa-magnifying-glass" style="font-size:24px;display:block;margin-bottom:8px;opacity:.3;"></i>
        No clients match your search.
    </div>
</div>

<!-- ── Add Client Modal ─────────────────────────────────────────── -->
<div class="modal-overlay" id="addClientModal">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--blue);margin-right:8px;"></i>Add New Client</div>
            <button class="icon-btn" onclick="closeModal('addClientModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url("mac-resellers/{$reseller['id']}/clients/store") ?>" id="addClientForm" novalidate>
            <div class="modal-body">
                <!-- Section: Personal Info -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2);margin-bottom:10px;">
                    <i class="fa-solid fa-user" style="margin-right:5px;"></i>Personal Information
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                    <div>
                        <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="full_name" id="add_name" class="form-input" required
                               placeholder="e.g. Rahim Uddin">
                    </div>
                    <div>
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" id="add_phone" class="form-input"
                               placeholder="01XXXXXXXXX">
                    </div>
                </div>

                <!-- Section: Network Info -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2);margin-bottom:10px;">
                    <i class="fa-solid fa-network-wired" style="margin-right:5px;"></i>Network Information
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                    <div>
                        <label class="form-label">MAC Address <span style="color:var(--red)">*</span></label>
                        <input type="text" name="mac_address" id="add_mac" class="form-input" required
                               placeholder="AA:BB:CC:DD:EE:FF" maxlength="17"
                               oninput="formatMac(this)"
                               style="font-family:monospace;letter-spacing:.05em;">
                        <div style="font-size:10px;color:var(--text2);margin-top:4px;">Format: AA:BB:CC:DD:EE:FF</div>
                    </div>
                    <div>
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" id="add_ip" class="form-input"
                               placeholder="192.168.1.100"
                               style="font-family:monospace;">
                    </div>
                </div>

                <!-- Section: Billing -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2);margin-bottom:10px;">
                    <i class="fa-solid fa-tags" style="margin-right:5px;"></i>Billing & Tariff
                </div>
                <div style="display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:18px;">
                    <div>
                        <label class="form-label">Tariff Plan</label>
                        <select name="tariff_id" id="add_tariff" class="form-input" onchange="updateTariffPreview(this)">
                            <option value="">— No Tariff Assigned —</option>
                            <?php foreach ($tariffs as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                    data-daily="<?= $t['daily_rate'] ?>"
                                    data-monthly="<?= $t['monthly_rate'] ?>"
                                    data-speed="<?= $t['speed_download'] ?>↓ / <?= $t['speed_upload'] ?>↑ Mbps">
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Tariff preview -->
                        <div id="tariffPreview" style="display:none;margin-top:8px;padding:10px 14px;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:8px;font-size:12px;">
                            <div style="display:flex;gap:20px;">
                                <div><span style="color:var(--text2);">Speed:</span> <strong id="tp_speed">—</strong></div>
                                <div><span style="color:var(--text2);">Daily:</span> <strong id="tp_daily" style="color:var(--green);">—</strong></div>
                                <div><span style="color:var(--text2);">Monthly:</span> <strong id="tp_monthly">—</strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Notes -->
                <div>
                    <label class="form-label">Notes <span style="color:var(--text2);font-weight:400;">(optional)</span></label>
                    <textarea name="notes" id="add_notes" class="form-input" rows="2"
                              placeholder="Any additional information about this client…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addClientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="addClientSubmit">
                    <i class="fa-solid fa-plus"></i> Add Client
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit Client Modal ────────────────────────────────────────── -->
<div class="modal-overlay" id="editClientModal">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen" style="color:var(--blue);margin-right:8px;"></i>Edit Client</div>
            <button class="icon-btn" onclick="closeModal('editClientModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="editClientForm" novalidate>
            <div class="modal-body">
                <!-- Section: Personal Info -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2);margin-bottom:10px;">
                    <i class="fa-solid fa-user" style="margin-right:5px;"></i>Personal Information
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                    <div>
                        <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="full_name" id="ec_name" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" id="ec_phone" class="form-input">
                    </div>
                </div>

                <!-- Section: Network Info -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2);margin-bottom:10px;">
                    <i class="fa-solid fa-network-wired" style="margin-right:5px;"></i>Network Information
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                    <div>
                        <label class="form-label">MAC Address <span style="color:var(--red)">*</span></label>
                        <input type="text" name="mac_address" id="ec_mac" class="form-input" required
                               maxlength="17" oninput="formatMac(this)"
                               style="font-family:monospace;letter-spacing:.05em;">
                    </div>
                    <div>
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" id="ec_ip" class="form-input"
                               style="font-family:monospace;">
                    </div>
                </div>

                <!-- Section: Billing & Status -->
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2);margin-bottom:10px;">
                    <i class="fa-solid fa-tags" style="margin-right:5px;"></i>Billing & Status
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                    <div>
                        <label class="form-label">Tariff Plan</label>
                        <select name="tariff_id" id="ec_tariff" class="form-input">
                            <option value="">— No Tariff —</option>
                            <?php foreach ($tariffs as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> — ৳<?= number_format($t['daily_rate'], 2) ?>/day</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" id="ec_status" class="form-input">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="ec_notes" class="form-input" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editClientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Modal helpers ──────────────────────────────────────────────────
function openAddClientModal() {
    document.getElementById('addClientForm').reset();
    document.getElementById('tariffPreview').style.display = 'none';
    document.getElementById('addClientModal').classList.add('open');
    setTimeout(() => document.getElementById('add_name').focus(), 100);
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// ── MAC address auto-formatter ─────────────────────────────────────
function formatMac(input) {
    let v = input.value.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();
    let formatted = '';
    for (let i = 0; i < v.length && i < 12; i++) {
        if (i > 0 && i % 2 === 0) formatted += ':';
        formatted += v[i];
    }
    input.value = formatted;
}

// ── Tariff preview ─────────────────────────────────────────────────
function updateTariffPreview(sel) {
    const opt = sel.options[sel.selectedIndex];
    const preview = document.getElementById('tariffPreview');
    if (!opt.value) { preview.style.display = 'none'; return; }
    document.getElementById('tp_speed').textContent   = opt.dataset.speed   || '—';
    document.getElementById('tp_daily').textContent   = '৳' + parseFloat(opt.dataset.daily || 0).toFixed(2);
    document.getElementById('tp_monthly').textContent = '৳' + parseFloat(opt.dataset.monthly || 0).toFixed(2);
    preview.style.display = 'block';
}

// ── Open edit modal ────────────────────────────────────────────────
function openEditClient(c) {
    document.getElementById('ec_name').value   = c.full_name   || '';
    document.getElementById('ec_phone').value  = c.phone       || '';
    document.getElementById('ec_mac').value    = c.mac_address || '';
    document.getElementById('ec_ip').value     = c.ip_address  || '';
    document.getElementById('ec_notes').value  = c.notes       || '';
    document.getElementById('ec_status').value = c.status      || 'active';
    const sel = document.getElementById('ec_tariff');
    for (let o of sel.options) o.selected = (o.value == c.tariff_id);
    document.getElementById('editClientForm').action =
        '<?= base_url("mac-resellers/clients/update/") ?>' + c.id;
    document.getElementById('editClientModal').classList.add('open');
    setTimeout(() => document.getElementById('ec_name').focus(), 100);
}

// ── Search & filter ────────────────────────────────────────────────
let currentFilter = 'all';

function setFilter(f) {
    currentFilter = f;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('filter-' + f).classList.add('active');
    filterClients();
}

function filterClients() {
    const q     = document.getElementById('clientSearch').value.toLowerCase().trim();
    const rows  = document.querySelectorAll('#clientsBody tr[data-status]');
    let visible = 0;

    rows.forEach(row => {
        const matchFilter = currentFilter === 'all' || row.dataset.status === currentFilter;
        const matchSearch = !q || row.dataset.search.includes(q);
        const show = matchFilter && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});
</script>
