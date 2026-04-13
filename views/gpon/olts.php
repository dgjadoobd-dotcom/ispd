<?php // views/gpon/olts.php ?>
<div class="page-header fade-in">
    <div><h1 class="page-title">OLT Management</h1><div class="page-breadcrumb"><i class="fa-solid fa-diagram-project" style="color:var(--blue)"></i> GPON / Fiber</div></div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-green" onclick="checkAllConnections()"><i class="fa-solid fa-plug"></i> Check All Connections</button>
        <button class="btn btn-primary" onclick="document.getElementById('addOltModal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add OLT</button>
    </div>
</div>

<div class="card fade-in" style="padding:0;overflow:hidden;">
    <div style="padding:16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="color:var(--text2);font-size:13px;">Show</span>
            <select id="pageLength" class="form-input" style="width:70px;padding:6px 10px;" onchange="updatePagination()">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span style="color:var(--text2);font-size:13px;">entries</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="color:var(--text2);font-size:13px;">Search:</span>
            <input type="text" id="searchInput" class="form-input" style="width:200px;padding:6px 10px;" placeholder="Search OLT..." onkeyup="filterTable()">
        </div>
    </div>
    
    <div style="overflow-x:auto;">
        <table class="data-table" id="oltTable">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>OLT Name</th>
                    <th>IP Address</th>
                    <th>Protocol</th>
                    <th>Port</th>
                    <th>Connection</th>
                    <th>Last Checked</th>
                    <th>Model</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th style="width:140px;">Action</th>
                </tr>
            </thead>
            <tbody id="oltTableBody">
                <?php if(empty($olts)): ?>
                <tr><td colspan="11" style="text-align:center;padding:48px;color:var(--text2);">
                    <i class="fa-solid fa-diagram-project" style="font-size:32px;display:block;margin-bottom:12px;opacity:.4;"></i>
                    No OLTs configured yet. Add your first OLT device.
                </td></tr>
                <?php else: $i=1; foreach($olts as $olt): 
                    $connStatus = $olt['connection_status'] ?? 'unknown';
                    $lastChecked = $olt['last_checked_at'] ? date('d-M H:i', strtotime($olt['last_checked_at'])) : 'Never';
                ?>
                <tr data-search="<?= strtolower(htmlspecialchars($olt['name'].' '.$olt['ip_address'].' '.$olt['model'].' '.($olt['branch_name']??'').' '.$olt['username'].' '.$olt['snmp_community'] ?? '')) ?>" data-olt-id="<?= $olt['id'] ?>">
                    <td style="font-weight:600;"><?= $i++ ?></td>
                    <td style="font-weight:600;"><span style="color:var(--blue);"><?= htmlspecialchars($olt['name']) ?></span></td>
                    <td><span style="font-family:monospace;"><?= htmlspecialchars($olt['ip_address']??'—') ?></span></td>
                    <td><span class="badge badge-blue"><?= strtoupper($olt['protocol']??'SSH') ?></span></td>
                    <td><span style="font-family:monospace;"><?= $olt['access_port'] ?? 22 ?></span></td>
                    <td>
                        <span class="olt-status" data-id="<?= $olt['id'] ?>">
                            <?php if($connStatus === 'online'): ?>
                                <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i> Online</span>
                            <?php elseif($connStatus === 'offline'): ?>
                                <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i> Offline</span>
                            <?php else: ?>
                                <span class="badge badge-gray"><i class="fa-solid fa-circle-question" style="font-size:10px;margin-right:4px;"></i> Unknown</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--text2);"><?= $lastChecked ?></td>
                    <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($olt['model']??'—') ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($olt['branch_name']??'—') ?></td>
                    <td><span class="badge <?= $olt['is_active']?'badge-green':'badge-gray' ?>"><?= $olt['is_active']?'Active':'Inactive' ?></span></td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <button class="btn btn-ghost btn-sm" onclick="checkConnection(<?= $olt['id'] ?>)" title="Check Connection"><i class="fa-solid fa-plug"></i></button>
                            <button class="btn btn-blue btn-sm" onclick="snmpTestOlt(<?= $olt['id'] ?>)" title="SNMP Test"><i class="fa-solid fa-bolt"></i></button>
                            <button class="btn btn-green btn-sm" onclick="syncOnus(<?= $olt['id'] ?>, '<?= addslashes(htmlspecialchars($olt['name'])) ?>')" title="Sync ONUs"><i class="fa-solid fa-rotate"></i></button>
                            <button class="btn btn-purple btn-sm" onclick="fetchLiveOnus(<?= $olt['id'] ?>, '<?= addslashes(htmlspecialchars($olt['name'])) ?>')" title="Get All ONUs (Live)"><i class="fa-solid fa-list"></i></button>
                            <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($olt)) ?>)" title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <a href="<?= base_url('gpon/olts/onus?olt_id='.$olt['id']) ?>" class="btn btn-ghost btn-sm" title="View ONUs"><i class="fa-solid fa-users"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);font-size:13px;color:var(--text2);">
        <span id="showingInfo">Showing <?= count($olts) ?> of <?= count($olts) ?> entries</span>
        <div style="display:flex;gap:4px;" id="paginationControls"></div>
    </div>
</div>

<div class="modal-overlay" id="addOltModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-plus" style="color:var(--blue);margin-right:8px;"></i>Add OLT</div>
            <button class="icon-btn" onclick="document.getElementById('addOltModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('gpon/olts/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">OLT Name <span style="color:var(--red)">*</span></label><input type="text" name="name" class="form-input" placeholder="Core-OLT-01" required></div>
                <div><label class="form-label">Branch</label>
                    <select name="branch_id" class="form-input" required>
                        <?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Model</label><input type="text" name="model" class="form-input" placeholder="VSOL_GPON / Huawei MA5800"></div>
                <div><label class="form-label">IP Address <span style="color:var(--red)">*</span></label><input type="text" name="ip_address" class="form-input" placeholder="192.168.1.1" required></div>
                <div><label class="form-label">Protocol</label>
                    <select name="protocol" class="form-input">
                        <option value="ssh">SSH</option>
                        <option value="telnet">Telnet</option>
                        <option value="snmp">SNMP</option>
                        <option value="http">HTTP</option>
                        <option value="https">HTTPS</option>
                    </select>
                </div>
                <div><label class="form-label">Access Port</label><input type="number" name="access_port" class="form-input" value="22"></div>
                <div><label class="form-label">Username</label><input type="text" name="username" class="form-input" placeholder="admin"></div>
                <div><label class="form-label">Password</label><input type="password" name="password" class="form-input" placeholder="••••••••"></div>
                <div><label class="form-label">SNMP Community</label><input type="text" name="snmp_community" class="form-input" placeholder="public"></div>
                <div><label class="form-label">Total Ports</label><input type="number" name="total_ports" class="form-input" value="16"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Location</label><input type="text" name="location" class="form-input" placeholder="Exchange building / rack location"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addOltModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add OLT</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editOltModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen" style="color:var(--blue);margin-right:8px;"></i>Edit OLT</div>
            <button class="icon-btn" onclick="document.getElementById('editOltModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('gpon/olts/update') ?>">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;"><label class="form-label">OLT Name <span style="color:var(--red)">*</span></label><input type="text" name="name" id="edit_name" class="form-input" required></div>
                <div><label class="form-label">Branch</label>
                    <select name="branch_id" id="edit_branch_id" class="form-input" required>
                        <?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Model</label><input type="text" name="model" id="edit_model" class="form-input"></div>
                <div><label class="form-label">IP Address <span style="color:var(--red)">*</span></label><input type="text" name="ip_address" id="edit_ip_address" class="form-input" required></div>
                <div><label class="form-label">Protocol</label>
                    <select name="protocol" id="edit_protocol" class="form-input">
                        <option value="ssh">SSH</option>
                        <option value="telnet">Telnet</option>
                        <option value="snmp">SNMP</option>
                        <option value="http">HTTP</option>
                        <option value="https">HTTPS</option>
                    </select>
                </div>
                <div><label class="form-label">Access Port</label><input type="number" name="access_port" id="edit_access_port" class="form-input"></div>
                <div><label class="form-label">Username</label><input type="text" name="username" id="edit_username" class="form-input"></div>
                <div><label class="form-label">Password <span style="color:var(--text2);font-weight:400;">(leave blank to keep)</span></label><input type="password" name="password" class="form-input" placeholder="••••••••"></div>
                <div><label class="form-label">SNMP Community</label><input type="text" name="snmp_community" id="edit_snmp_community" class="form-input"></div>
                <div><label class="form-label">SNMP Version</label>
                    <select name="snmp_version" id="edit_snmp_version" class="form-input">
                        <option value="v2c">v2c (recommended)</option>
                        <option value="v1">v1</option>
                        <option value="v3">v3</option>
                    </select>
                </div>
                <div><label class="form-label">Total Ports</label><input type="number" name="total_ports" id="edit_total_ports" class="form-input"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Location</label><input type="text" name="location" id="edit_location" class="form-input"></div>
                <div><label class="form-label">Status</label>
                    <select name="is_active" id="edit_is_active" class="form-input">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editOltModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update OLT</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
let rowsPerPage = 10;

function openEditModal(olt) {
    document.getElementById('edit_id').value = olt.id;
    document.getElementById('edit_name').value = olt.name || '';
    document.getElementById('edit_branch_id').value = olt.branch_id || '';
    document.getElementById('edit_model').value = olt.model || '';
    document.getElementById('edit_ip_address').value = olt.ip_address || '';
    document.getElementById('edit_protocol').value = olt.protocol || 'ssh';
    document.getElementById('edit_access_port').value = olt.access_port || 22;
    document.getElementById('edit_username').value = olt.username || '';
    document.getElementById('edit_snmp_community').value = olt.snmp_community || '';
    if (document.getElementById('edit_snmp_version')) {
        document.getElementById('edit_snmp_version').value = olt.snmp_version || 'v2c';
    }
    document.getElementById('edit_total_ports').value = olt.total_ports || 16;
    document.getElementById('edit_location').value = olt.location || '';
    document.getElementById('edit_is_active').value = olt.is_active;
    document.getElementById('editOltModal').classList.add('open');
}

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#oltTableBody tr[data-search]');
    let visibleCount = 0;
    rows.forEach(row => {
        const match = row.getAttribute('data-search').includes(search);
        row.style.display = match ? '' : 'none';
        if(match) visibleCount++;
    });
    currentPage = 1;
    paginateTable(visibleCount);
    document.getElementById('showingInfo').textContent = `Showing ${visibleCount} of ${rows.length} entries`;
}

function updatePagination() {
    rowsPerPage = parseInt(document.getElementById('pageLength').value);
    currentPage = 1;
    const visibleRows = document.querySelectorAll('#oltTableBody tr[data-search]:not([style*="display: none"])');
    paginateTable(visibleRows.length);
}

function paginateTable(totalRows) {
    const rows = document.querySelectorAll('#oltTableBody tr');
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    rows.forEach((row, index) => {
        if(row.hasAttribute('data-search')) {
            if(row.style.display !== 'none') {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            }
        }
    });
    
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    let paginationHTML = '';
    if(totalPages > 1) {
        paginationHTML += `<button class="btn btn-ghost btn-sm" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
        for(let i = 1; i <= totalPages; i++) {
            paginationHTML += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-ghost'}" onclick="changePage(${i})">${i}</button>`;
        }
        paginationHTML += `<button class="btn btn-ghost btn-sm" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
    }
    document.getElementById('paginationControls').innerHTML = paginationHTML;
}

function changePage(page) {
    const totalRows = document.querySelectorAll('#oltTableBody tr[data-search]:not([style*="display: none"])').length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    if(page >= 1 && page <= totalPages) {
        currentPage = page;
        paginateTable(totalRows);
    }
}

function checkConnection(oltId) {
    const statusEl = document.querySelector(`.olt-status[data-id="${oltId}"]`);
    statusEl.innerHTML = '<span class="badge badge-yellow"><i class="fa-solid fa-spinner fa-spin" style="font-size:10px;margin-right:4px;"></i> Checking...</span>';
    
    fetch(`<?= base_url('gpon/api/olts/check') ?>/${oltId}`)
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                const methodLabel = data.method ? ` <span style="font-size:10px;opacity:.7;">[${data.method}]</span>` : '';
                statusEl.innerHTML = data.online 
                    ? `<span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i> Online${methodLabel}</span>`
                    : `<span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i> Offline${methodLabel}</span>`;
                const row = document.querySelector(`tr[data-olt-id="${oltId}"]`);
                if(row) row.cells[6].textContent = new Date().toLocaleString('en-GB', {day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
            }
        })
        .catch(err => {
            statusEl.innerHTML = '<span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i> Error</span>';
        });
}

function checkAllConnections() {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
    
    fetch(`<?= base_url('gpon/api/olts/check-all') ?>`)
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                data.results.forEach(r => {
                    const statusEl = document.querySelector(`.olt-status[data-id="${r.id}"]`);
                    if(statusEl) {
                        statusEl.innerHTML = r.online 
                            ? '<span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i> Online</span>'
                            : '<span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i> Offline</span>';
                    }
                });
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-plug"></i> Check All Connections';
            updatePagination();
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-plug"></i> Check All Connections';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    paginateTable(document.querySelectorAll('#oltTableBody tr[data-search]').length);
});
</script>

<!-- SNMP Result Panel -->
<div id="snmpResultPanel" style="display:none;position:fixed;bottom:24px;right:24px;z-index:9999;min-width:320px;max-width:420px;">
    <div class="card" style="padding:16px;border-left:4px solid var(--blue);box-shadow:0 4px 24px rgba(0,0,0,.18);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-weight:700;font-size:14px;" id="snmpPanelTitle">SNMP Result</span>
            <button class="icon-btn" onclick="document.getElementById('snmpResultPanel').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="snmpPanelBody" style="font-size:13px;"></div>
    </div>
</div>

<!-- SNMP Test Modal -->
<div class="modal-overlay" id="snmpTestModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-bolt" style="color:var(--blue);margin-right:8px;"></i>SNMP Test Result</div>
            <button class="icon-btn" onclick="document.getElementById('snmpTestModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="snmpTestModalBody" style="font-size:13px;">
            <div style="text-align:center;padding:24px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--blue);"></i><div style="margin-top:8px;color:var(--text2);">Testing SNMP connection...</div></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('snmpTestModal').classList.remove('open')">Close</button>
        </div>
    </div>
</div>

<script>
async function snmpTestOlt(id) {
    document.getElementById('snmpTestModalBody').innerHTML = '<div style="text-align:center;padding:24px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--blue);"></i><div style="margin-top:8px;color:var(--text2);">Testing SNMP connection...</div></div>';
    document.getElementById('snmpTestModal').classList.add('open');

    try {
        const res  = await fetch(`<?= base_url('gpon/api/snmp/test') ?>/${id}`);
        const data = await res.json();

        const statusBadge = data.success
            ? '<span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i>Online</span>'
            : '<span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i>Offline</span>';

        const methodBadge = data.method === 'snmp'
            ? '<span class="badge badge-blue">SNMP</span>'
            : data.method === 'tcp_ping'
            ? '<span class="badge badge-yellow">TCP Ping</span>'
            : '<span class="badge badge-gray">None</span>';

        const si = data.system_info || {};
        document.getElementById('snmpTestModalBody').innerHTML = `
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
                <span style="font-weight:700;">Status:</span> ${statusBadge}
                <span style="font-weight:700;">Method:</span> ${methodBadge}
                ${si.vendor ? `<span class="badge badge-gray">${si.vendor}</span>` : ''}
                ${!data.snmp_available ? '<span class="badge badge-red">SNMP ext missing</span>' : ''}
            </div>
            ${data.error ? `<div style="color:var(--yellow);margin-bottom:12px;padding:10px;background:rgba(245,158,11,.08);border-radius:6px;font-size:12px;"><i class="fa-solid fa-triangle-exclamation"></i> ${data.error}</div>` : ''}
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <tr style="border-bottom:1px solid var(--border);"><td style="padding:7px 0;color:var(--text2);width:42%;">Description</td><td style="padding:7px 0;font-weight:500;word-break:break-word;">${data.description || si.sysDescr || '—'}</td></tr>
                <tr style="border-bottom:1px solid var(--border);"><td style="padding:7px 0;color:var(--text2);">System Name</td><td style="padding:7px 0;">${si.sysName || '—'}</td></tr>
                <tr style="border-bottom:1px solid var(--border);"><td style="padding:7px 0;color:var(--text2);">Uptime</td><td style="padding:7px 0;">${si.sysUpTime || '—'}</td></tr>
                <tr style="border-bottom:1px solid var(--border);"><td style="padding:7px 0;color:var(--text2);">Vendor</td><td style="padding:7px 0;">${si.vendor || '—'}</td></tr>
                <tr><td style="padding:7px 0;color:var(--text2);">ONUs Detected</td><td style="padding:7px 0;font-weight:700;color:var(--blue);">${data.onu_count ?? 0}</td></tr>
            </table>
            ${data.method === 'tcp_ping' ? `
            <div style="margin-top:14px;padding:10px;background:rgba(59,130,246,.08);border-radius:6px;font-size:12px;color:var(--text2);">
                <i class="fa-solid fa-circle-info" style="color:var(--blue);"></i>
                <strong>SNMP blocked.</strong> Host is reachable via TCP but SNMP UDP port 161 is not responding.<br>
                Check: correct community string, SNMP v1/v2c setting, and firewall rules on the OLT.
            </div>` : ''}
        `;
    } catch(e) {
        document.getElementById('snmpTestModalBody').innerHTML = `<div style="color:var(--red);padding:16px;"><i class="fa-solid fa-triangle-exclamation"></i> Request failed: ${e.message}</div>`;
    }
}

async function syncOnus(id, name) {    if (!confirm(`Sync ONUs from "${name}" via SNMP?\n\nThis will fetch the live ONU list and update the database.`)) return;

    const panel = document.getElementById('snmpResultPanel');
    document.getElementById('snmpPanelTitle').textContent = `Syncing ${name}...`;
    document.getElementById('snmpPanelBody').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Fetching ONU list via SNMP...';
    panel.style.display = 'block';

    try {
        const res  = await fetch(`<?= base_url('gpon/api/snmp/sync') ?>/${id}`, { method: 'POST' });
        const data = await res.json();

        if (data.success) {
            document.getElementById('snmpPanelTitle').textContent = '✓ Sync Complete';
            document.getElementById('snmpPanelBody').innerHTML = `
                <div style="color:var(--green);font-weight:600;margin-bottom:6px;">Synced ${data.synced} ONUs</div>
                <div style="color:var(--text2);">🆕 New: <strong>${data.new_count}</strong> &nbsp; 🔄 Updated: <strong>${data.updated_count}</strong></div>
                ${data.errors && data.errors.length ? `<div style="color:var(--red);margin-top:6px;font-size:12px;">${data.errors.length} error(s)</div>` : ''}
            `;
            setTimeout(() => { panel.style.display = 'none'; location.reload(); }, 2500);
        } else {
            document.getElementById('snmpPanelTitle').textContent = '✗ Sync Failed';
            document.getElementById('snmpPanelBody').innerHTML = `<div style="color:var(--red);">${data.error || 'Unknown error'}</div>`;
        }
    } catch(e) {
        document.getElementById('snmpPanelTitle').textContent = '✗ Error';
        document.getElementById('snmpPanelBody').innerHTML = `<div style="color:var(--red);">${e.message}</div>`;
    }
}
</script>

<!-- Live ONU List Modal -->
<div class="modal-overlay" id="liveOnuModal">
    <div class="modal" style="max-width:860px;width:95vw;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-list" style="color:var(--green);margin-right:8px;"></i>Live ONU List — <span id="liveOnuOltName"></span></div>
            <button class="icon-btn" onclick="document.getElementById('liveOnuModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="padding:0;">
            <div id="liveOnuLoading" style="text-align:center;padding:32px;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size:28px;color:var(--blue);"></i>
                <div style="margin-top:10px;color:var(--text2);">Fetching live ONU data from OLT...</div>
            </div>
            <div id="liveOnuContent" style="display:none;">
                <div style="padding:12px 16px;display:flex;gap:16px;align-items:center;border-bottom:1px solid var(--border);flex-wrap:wrap;">
                    <span id="liveOnuStats" style="font-size:13px;color:var(--text2);"></span>
                    <span id="liveOnuMethod" style="font-size:12px;"></span>
                    <input type="text" id="liveOnuSearch" class="form-input" style="width:200px;padding:5px 10px;margin-left:auto;" placeholder="Search serial / MAC..." oninput="filterLiveOnus()">
                </div>
                <div style="overflow-x:auto;max-height:480px;overflow-y:auto;">
                    <table class="data-table" style="font-size:12px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Serial / ID</th>
                                <th>MAC Address</th>
                                <th>OLT Port</th>
                                <th>Status</th>
                                <th>Signal (dBm)</th>
                                <th>Description</th>
                                <th>Customer</th>
                            </tr>
                        </thead>
                        <tbody id="liveOnuTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div id="liveOnuError" style="display:none;padding:24px;color:var(--red);text-align:center;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('liveOnuModal').classList.remove('open')">Close</button>
        </div>
    </div>
</div>

<script>
let liveOnuData = [];

async function fetchLiveOnus(id, name) {
    document.getElementById('liveOnuOltName').textContent = name;
    document.getElementById('liveOnuLoading').style.display = 'block';
    document.getElementById('liveOnuContent').style.display = 'none';
    document.getElementById('liveOnuError').style.display   = 'none';
    document.getElementById('liveOnuModal').classList.add('open');

    try {
        const res  = await fetch(`<?= base_url('gpon/api/olts') ?>/${id}/onus/live`);
        const data = await res.json();

        document.getElementById('liveOnuLoading').style.display = 'none';

        if (!data.success) {
            document.getElementById('liveOnuError').style.display = 'block';
            document.getElementById('liveOnuError').innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${data.error || 'Failed to fetch ONU list'}`;
            return;
        }

        liveOnuData = data.onus || [];
        document.getElementById('liveOnuStats').innerHTML =
            `Total: <strong>${data.total}</strong> &nbsp;|&nbsp; ` +
            `<span style="color:var(--green);">Online: <strong>${data.online}</strong></span> &nbsp;|&nbsp; ` +
            `<span style="color:var(--red);">Offline: <strong>${data.offline}</strong></span>`;

        const methodColors = { snmp: 'badge-blue', telnet: 'badge-yellow', none: 'badge-gray' };
        document.getElementById('liveOnuMethod').innerHTML =
            `<span class="badge ${methodColors[data.method] || 'badge-gray'}">via ${(data.method || 'none').toUpperCase()}</span>`;

        document.getElementById('liveOnuSearch').value = '';
        renderLiveOnuTable(liveOnuData);
        document.getElementById('liveOnuContent').style.display = 'block';

    } catch (e) {
        document.getElementById('liveOnuLoading').style.display = 'none';
        document.getElementById('liveOnuError').style.display   = 'block';
        document.getElementById('liveOnuError').innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Request failed: ${e.message}`;
    }
}

function renderLiveOnuTable(onus) {
    const tbody = document.getElementById('liveOnuTableBody');
    if (!onus.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text2);">No ONUs found</td></tr>';
        return;
    }
    tbody.innerHTML = onus.map((o, i) => {
        const statusBadge = o.status === 'online'
            ? '<span class="badge badge-green" style="font-size:10px;">Online</span>'
            : '<span class="badge badge-red" style="font-size:10px;">Offline</span>';
        const signal = o.signal_dbm !== null && o.signal_dbm !== undefined
            ? `<span style="color:${o.signal_dbm < -27 ? 'var(--red)' : o.signal_dbm < -23 ? 'var(--yellow)' : 'var(--green)'}">${o.signal_dbm} dBm</span>`
            : '—';
        const customer = o.customer_name
            ? `<span style="color:var(--blue);">${o.customer_name}</span><br><span style="color:var(--text2);font-size:10px;">${o.customer_code || ''}</span>`
            : '<span style="color:var(--text2);">—</span>';
        return `<tr>
            <td>${i + 1}</td>
            <td style="font-family:monospace;font-size:11px;">${o.serial || '—'}</td>
            <td style="font-family:monospace;font-size:11px;">${o.mac_address || '—'}</td>
            <td style="font-family:monospace;">${o.olt_port || '—'}</td>
            <td>${statusBadge}</td>
            <td>${signal}</td>
            <td style="font-size:11px;color:var(--text2);">${o.description || '—'}</td>
            <td style="font-size:11px;">${customer}</td>
        </tr>`;
    }).join('');
}

function filterLiveOnus() {
    const q = document.getElementById('liveOnuSearch').value.toLowerCase();
    const filtered = q
        ? liveOnuData.filter(o =>
            (o.serial || '').toLowerCase().includes(q) ||
            (o.mac_address || '').toLowerCase().includes(q) ||
            (o.olt_port || '').toLowerCase().includes(q) ||
            (o.description || '').toLowerCase().includes(q) ||
            (o.customer_name || '').toLowerCase().includes(q)
          )
        : liveOnuData;
    renderLiveOnuTable(filtered);
}
</script>
