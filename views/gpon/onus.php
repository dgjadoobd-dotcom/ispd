<?php // views/gpon/onus.php
$totalOnus   = count($onus);
$onlineCount = count(array_filter($onus, fn($o) => in_array($o['status'], ['online','installed','active'])));
$stockCount  = count(array_filter($onus, fn($o) => $o['status'] === 'stock'));
$faultyCount = count(array_filter($onus, fn($o) => $o['status'] === 'faulty'));
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">ONU / CPE Devices</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-router" style="color:var(--green)"></i> GPON</div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-ghost" onclick="exportOnuCsv()"><i class="fa-solid fa-download"></i> Export CSV</button>
        <button class="btn btn-primary" onclick="document.getElementById('addOnuModal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add ONU</button>
    </div>
</div>

<!-- Stats bar -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;" class="fade-in">
    <?php foreach([
        ['Total ONUs',    $totalOnus,   'fa-router',        'var(--blue)'],
        ['Active',        $onlineCount, 'fa-circle-check',  'var(--green)'],
        ['In Stock',      $stockCount,  'fa-box',           'var(--yellow)'],
        ['Faulty',        $faultyCount, 'fa-triangle-exclamation', 'var(--red)'],
    ] as [$label, $val, $icon, $color]): ?>
    <div class="card" style="padding:16px;display:flex;align-items:center;gap:14px;">
        <div style="width:42px;height:42px;border-radius:10px;background:<?= $color ?>22;display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid <?= $icon ?>" style="color:<?= $color ?>;font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:22px;font-weight:700;"><?= $val ?></div>
            <div style="font-size:12px;color:var(--text2);"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Search & filter bar -->
<div class="card fade-in" style="padding:12px 16px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <input type="text" id="onuSearch" class="form-input" style="width:220px;padding:6px 10px;" placeholder="Search serial / MAC / customer..." oninput="filterOnuTable()">
    <select id="onuStatusFilter" class="form-input" style="width:140px;padding:6px 10px;" onchange="filterOnuTable()">
        <option value="">All Status</option>
        <option value="online">Online</option>
        <option value="installed">Installed</option>
        <option value="stock">Stock</option>
        <option value="faulty">Faulty</option>
        <option value="returned">Returned</option>
    </select>
    <select id="onuOltFilter" class="form-input" style="width:180px;padding:6px 10px;" onchange="filterOnuTable()">
        <option value="">All OLTs</option>
        <?php foreach($olts ?? [] as $olt): ?>
        <option value="<?= htmlspecialchars($olt['name']) ?>"><?= htmlspecialchars($olt['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <span id="onuFilterCount" style="font-size:12px;color:var(--text2);margin-left:auto;"></span>
</div>

<div class="card fade-in" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table" id="onuTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Serial Number</th>
                    <th>Brand / Model</th>
                    <th>MAC Address</th>
                    <th>OLT</th>
                    <th>OLT Port</th>
                    <th>Splitter</th>
                    <th>Signal</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Last Synced</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="onuTableBody">
                <?php if(empty($onus)): ?>
                <tr><td colspan="12" style="text-align:center;padding:48px;color:var(--text2);">
                    <i class="fa-solid fa-router" style="font-size:36px;display:block;margin-bottom:12px;opacity:.3;"></i>
                    No ONU devices found. Add one manually or sync from an OLT.
                </td></tr>
                <?php else: $i=1; foreach($onus as $o):
                    $statusMap = ['online'=>'badge-green','installed'=>'badge-green','active'=>'badge-green','offline'=>'badge-red','stock'=>'badge-yellow','faulty'=>'badge-red','returned'=>'badge-gray'];
                    $sig = $o['signal_level'];
                    $sigClass = $sig !== null ? ($sig >= -25 ? 'badge-green' : ($sig >= -27 ? 'badge-yellow' : 'badge-red')) : '';
                ?>
                <tr data-search="<?= strtolower(htmlspecialchars(($o['serial_number']??'').' '.($o['mac_address']??'').' '.($o['customer_name']??'').' '.($o['customer_code']??'').' '.($o['brand']??'').' '.($o['model']??''))) ?>"
                    data-status="<?= htmlspecialchars($o['status']??'') ?>"
                    data-olt="<?= htmlspecialchars($o['olt_name']??'') ?>">
                    <td style="font-weight:600;color:var(--text2);"><?= $i++ ?></td>
                    <td>
                        <span style="font-family:monospace;font-weight:700;font-size:12px;color:var(--blue);"><?= htmlspecialchars($o['serial_number']) ?></span>
                        <?php if($o['onu_type']): ?>
                        <div style="font-size:10px;color:var(--text2);margin-top:2px;"><?= ucfirst($o['onu_type']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars(trim(($o['brand']??'').' '.($o['model']??''))) ?: '—' ?></div>
                    </td>
                    <td><span style="font-family:monospace;font-size:11px;"><?= htmlspecialchars($o['mac_address']??'—') ?></span></td>
                    <td style="font-size:12px;font-weight:500;color:var(--blue);"><?= htmlspecialchars($o['olt_name']??'—') ?></td>
                    <td><span style="font-family:monospace;font-size:11px;"><?= htmlspecialchars($o['olt_port']??'—') ?></span></td>
                    <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($o['splitter_name']??'—') ?></td>
                    <td>
                        <?php if($sig !== null): ?>
                        <span class="badge <?= $sigClass ?>"><?= $sig ?> dBm</span>
                        <?php else: ?><span style="color:var(--text2);">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if($o['customer_id']): ?>
                        <a href="<?= base_url("customers/view/{$o['customer_id']}") ?>" style="color:var(--blue);text-decoration:none;font-weight:500;font-size:13px;"><?= htmlspecialchars($o['customer_name']) ?></a>
                        <div style="font-size:10px;color:var(--text2);"><?= htmlspecialchars($o['customer_code']??'') ?></div>
                        <?php else: ?><span class="badge badge-gray" style="font-size:10px;">Unassigned</span><?php endif; ?>
                    </td>
                    <td><span class="badge <?= $statusMap[$o['status']] ?? 'badge-gray' ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td style="font-size:11px;color:var(--text2);"><?= $o['last_synced_at'] ? date('d-M H:i', strtotime($o['last_synced_at'])) : '—' ?></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <?php if($o['customer_id']): ?>
                            <a href="<?= base_url("customers/view/{$o['customer_id']}") ?>" class="btn btn-ghost btn-sm" title="View Customer"><i class="fa-solid fa-user"></i></a>
                            <?php endif; ?>
                            <button class="btn btn-ghost btn-sm" onclick='openEditOnu(<?= htmlspecialchars(json_encode($o)) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" action="<?= base_url("gpon/onus/delete/{$o['id']}") ?>" onsubmit="return confirm('Delete ONU <?= addslashes(htmlspecialchars($o['serial_number'])) ?>?');" style="display:inline;">
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:10px 16px;border-top:1px solid var(--border);font-size:12px;color:var(--text2);" id="onuTableFooter">
        Showing <?= min($offset+1, $total) ?>–<?= min($offset+count($onus), $total) ?> of <?= number_format($total) ?> devices
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;gap:12px;flex-wrap:wrap;">
    <div style="font-size:13px;color:var(--text2);">
        Page <?= $page ?> of <?= $totalPages ?>
    </div>
    <div style="display:flex;gap:6px;margin-left:auto;">
        <?php for ($i=1;$i<=$totalPages;$i++): ?>
        <?php if ($i==1 || $i==$totalPages || abs($i-$page)<=2): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"
           style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;
                  <?= $i===$page ? 'background:var(--blue);color:#fff;' : 'background:var(--bg3);color:var(--text2);' ?>">
            <?= $i ?>
        </a>
        <?php elseif (abs($i-$page)==3): ?>
        <span style="color:var(--text2);display:inline-flex;align-items:center;padding:0 4px;">…</span>
        <?php endif; ?>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- Add ONU Modal -->
<div class="modal-overlay" id="addOnuModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-router" style="color:var(--green);margin-right:8px;"></i>Add ONU / CPE Device</div>
            <button class="icon-btn" onclick="document.getElementById('addOnuModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('gpon/onus/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;">
                    <label class="form-label">Serial Number <span style="color:var(--red)">*</span></label>
                    <input type="text" name="serial_number" class="form-input" style="font-family:monospace;text-transform:uppercase;" placeholder="HWTC1234ABCD" required>
                </div>
                <div><label class="form-label">Brand</label><input type="text" name="brand" class="form-input" placeholder="Huawei / ZTE / TP-Link"></div>
                <div><label class="form-label">Model</label><input type="text" name="model" class="form-input" placeholder="HG8145V5"></div>
                <div><label class="form-label">MAC Address</label><input type="text" name="mac_address" class="form-input" style="font-family:monospace;" placeholder="AA:BB:CC:DD:EE:FF"></div>
                <div><label class="form-label">Type</label>
                    <select name="onu_type" class="form-input">
                        <option value="indoor">Indoor</option>
                        <option value="outdoor">Outdoor</option>
                        <option value="router">Router</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                <div><label class="form-label">OLT</label>
                    <select name="olt_id" class="form-input">
                        <option value="">— None —</option>
                        <?php foreach($olts ?? [] as $olt): ?>
                        <option value="<?= $olt['id'] ?>"><?= htmlspecialchars($olt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Splitter</label>
                    <select name="splitter_id" class="form-input">
                        <option value="">— None —</option>
                        <?php foreach($splitters as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Installed Date</label><input type="date" name="installed_date" class="form-input"></div>
                <div><label class="form-label">Purchase Price (৳)</label><input type="number" name="purchase_price" class="form-input" step="0.01" placeholder="0.00"></div>
                <div><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry" class="form-input"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Notes</label><textarea name="notes" class="form-input" rows="2" placeholder="Optional notes..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addOnuModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add ONU</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit ONU Modal -->
<div class="modal-overlay" id="editOnuModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen" style="color:var(--blue);margin-right:8px;"></i>Edit ONU Device</div>
            <button class="icon-btn" onclick="document.getElementById('editOnuModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('gpon/onus/update') ?>">
            <input type="hidden" name="id" id="edit_onu_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;">
                    <label class="form-label">Serial Number <span style="color:var(--red)">*</span></label>
                    <input type="text" name="serial_number" id="edit_onu_serial" class="form-input" style="font-family:monospace;" required>
                </div>
                <div><label class="form-label">Brand</label><input type="text" name="brand" id="edit_onu_brand" class="form-input"></div>
                <div><label class="form-label">Model</label><input type="text" name="model" id="edit_onu_model" class="form-input"></div>
                <div><label class="form-label">MAC Address</label><input type="text" name="mac_address" id="edit_onu_mac" class="form-input" style="font-family:monospace;"></div>
                <div><label class="form-label">Type</label>
                    <select name="onu_type" id="edit_onu_type" class="form-input">
                        <option value="indoor">Indoor</option>
                        <option value="outdoor">Outdoor</option>
                        <option value="router">Router</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                <div><label class="form-label">Splitter</label>
                    <select name="splitter_id" id="edit_onu_splitter" class="form-input">
                        <option value="">— None —</option>
                        <?php foreach($splitters as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Status</label>
                    <select name="status" id="edit_onu_status" class="form-input">
                        <option value="stock">Stock</option>
                        <option value="installed">Installed</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="faulty">Faulty</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                <div><label class="form-label">Signal Level (dBm)</label><input type="number" name="signal_level" id="edit_onu_signal" class="form-input" step="0.01" placeholder="-25.00"></div>
                <div><label class="form-label">Purchase Price (৳)</label><input type="number" name="purchase_price" id="edit_onu_price" class="form-input" step="0.01"></div>
                <div><label class="form-label">Installed Date</label><input type="date" name="installed_date" id="edit_onu_installed" class="form-input"></div>
                <div><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry" id="edit_onu_warranty" class="form-input"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Notes</label><textarea name="notes" id="edit_onu_notes" class="form-input" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editOnuModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditOnu(o) {
    document.getElementById('edit_onu_id').value       = o.id;
    document.getElementById('edit_onu_serial').value   = o.serial_number || '';
    document.getElementById('edit_onu_brand').value    = o.brand || '';
    document.getElementById('edit_onu_model').value    = o.model || '';
    document.getElementById('edit_onu_mac').value      = o.mac_address || '';
    document.getElementById('edit_onu_type').value     = o.onu_type || 'indoor';
    document.getElementById('edit_onu_splitter').value = o.splitter_id || '';
    document.getElementById('edit_onu_status').value   = o.status || 'stock';
    document.getElementById('edit_onu_signal').value   = o.signal_level || '';
    document.getElementById('edit_onu_price').value    = o.purchase_price || '';
    document.getElementById('edit_onu_installed').value= o.installed_date || '';
    document.getElementById('edit_onu_warranty').value = o.warranty_expiry || '';
    document.getElementById('edit_onu_notes').value    = o.notes || '';
    document.getElementById('editOnuModal').classList.add('open');
}

function filterOnuTable() {
    const q      = document.getElementById('onuSearch').value.toLowerCase();
    const status = document.getElementById('onuStatusFilter').value.toLowerCase();
    const olt    = document.getElementById('onuOltFilter').value.toLowerCase();
    const rows   = document.querySelectorAll('#onuTableBody tr[data-search]');
    let visible  = 0;

    rows.forEach(row => {
        const matchQ      = !q      || row.dataset.search.includes(q);
        const matchStatus = !status || row.dataset.status === status;
        const matchOlt    = !olt    || row.dataset.olt.toLowerCase() === olt;
        const show = matchQ && matchStatus && matchOlt;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('onuFilterCount').textContent =
        (q || status || olt) ? `Showing ${visible} of ${rows.length}` : `${rows.length} devices`;
    document.getElementById('onuTableFooter').textContent =
        (q || status || olt) ? `Showing ${visible} of ${rows.length} devices` : `Showing ${rows.length} devices`;
}

function exportOnuCsv() {
    const headers = ['#','Serial Number','Brand/Model','MAC Address','OLT','OLT Port','Splitter','Signal (dBm)','Customer','Status','Last Synced'];
    const rows    = document.querySelectorAll('#onuTableBody tr[data-search]:not([style*="display: none"])');
    let csv = [headers.map(h => `"${h}"`).join(',')];

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = Array.from(cells).slice(0, 11).map(td => `"${td.innerText.trim().replace(/"/g,'""')}"`);
        csv.push(rowData.join(','));
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'onus-<?= date('Y-m-d') ?>.csv'; a.click();
    URL.revokeObjectURL(url);
}

// Init count
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('#onuTableBody tr[data-search]');
    document.getElementById('onuFilterCount').textContent = rows.length + ' devices';
});
</script>
