<?php
// views/customers/list.php
?>
<style>
.filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:20px; }
.status-dot { display:inline-block; width:7px; height:7px; border-radius:50%; margin-right:5px; }
.client-avatar { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:12px; flex-shrink:0; }
.action-btn { width:28px; height:28px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; border:none; cursor:pointer; transition:all 0.2s; }
.action-btn:hover { transform:scale(1.1); }
.btn-suspend { background:rgba(239,68,68,0.1); color:var(--text2); }
.btn-suspend:hover { background:var(--red); color:#fff; }
.btn-reconnect { background:rgba(34,197,94,0.1); color:var(--text2); }
.btn-reconnect:hover { background:var(--green); color:#fff; }
</style>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Client List</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-users" style="color:var(--blue)"></i> Client <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:0.5;"></i> Client List</div>
    </div>
    <div style="display:flex;gap:10px;">
        <button class="btn btn-ghost" onclick="openModal('importModal')"><i class="fa-solid fa-upload"></i> Import</button>
        <div class="dropdown">
            <button class="btn btn-ghost" onclick="toggleDropdown('exportMenu')"><i class="fa-solid fa-download"></i> Export <i class="fa-solid fa-chevron-down" style="font-size:10px;margin-left:4px;"></i></button>
            <div class="dropdown-menu" id="exportMenu">
                <a href="javascript:void(0)" onclick="exportCSV()" class="dropdown-item"><i class="fa-solid fa-file-csv"></i> Excel / CSV</a>
                <a href="javascript:void(0)" onclick="exportPDF()" class="dropdown-item"><i class="fa-solid fa-file-pdf"></i> PDF Report</a>
            </div>
        </div>
        <a href="<?= base_url('customers/create') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Customer</a>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar card fade-in" style="padding:14px 16px;">
    <div style="flex:1;min-width:200px;">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by name, phone, ID, MAC..." class="form-input">
    </div>
    <select name="status" class="form-input" style="width:140px;">
        <option value="">All Status</option>
        <?php foreach(['active','suspended','pending','cancelled','deleted'] as $s): ?>
        <option value="<?= $s ?>" <?= $status===$s?'selected':''; ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="package_id" class="form-input" style="width:180px;">
        <option value="">All Packages</option>
        <?php foreach($packages as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $package_id===$p['id']?'selected':''; ?>><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="zone_id" class="form-input" style="width:160px;">
        <option value="">All Zones</option>
        <?php foreach($zones as $z): ?>
        <option value="<?= $z['id'] ?>" <?= $zone_id===$z['id']?'selected':''; ?>><?= htmlspecialchars($z['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <a href="<?= base_url('customers') ?>" class="btn btn-ghost"><i class="fa-solid fa-xmark"></i> Clear</a>
</form>

<!-- Table -->
<div class="card fade-in" style="overflow-x:auto;">
    <table class="data-table" id="customerTable" style="min-width:1600px;font-size:12px;">
        <thead>
            <tr>
                <th style="width:36px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                <th>SrNo</th>
                <th>ID / IP</th>
                <th>C.Code</th>
                <th>Client Name</th>
                <th>Mobile</th>
                <th>Zone</th>
                <th>Sub Zone</th>
                <th>Box</th>
                <th>Package</th>
                <th>Server</th>
                <th>Speed</th>
                <th>Ex.Date</th>
                <th>MACAddress</th>
                <th>Protocol</th>
                <th>B.Status</th>
                <th>Joining Date</th>
                <th>Thana</th>
                <th>District</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
            <tr>
                <td colspan="20" style="text-align:center;padding:48px;color:var(--text2);">
                    <i class="fa-solid fa-users-slash" style="font-size:32px;display:block;margin-bottom:12px;"></i>
                    No customers found. <a href="<?= base_url('customers/create') ?>" style="color:var(--blue);">Add the first customer</a>
                </td>
            </tr>
            <?php else:
            $srNo = ($page - 1) * $limit;
            foreach ($customers as $c):
            $srNo++;
            ?>
            <tr>
                <td><input type="checkbox" class="rowCheck" value="<?= $c['id'] ?>"></td>
                <td style="font-size:12px;color:var(--text2);text-align:center;"><?= $srNo ?></td>
                <td>
                    <?php if ($c['pppoe_username']): ?>
                    <code style="font-size:11px;background:var(--bg3);padding:2px 5px;border-radius:4px;"><?= htmlspecialchars($c['pppoe_username']) ?></code>
                    <?php elseif ($c['static_ip']): ?>
                    <code style="font-size:11px;background:var(--bg3);padding:2px 5px;border-radius:4px;"><?= htmlspecialchars($c['static_ip']) ?></code>
                    <?php else: ?>
                    <span style="color:var(--text2);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;font-family:monospace;font-weight:600;color:var(--blue);"><?= htmlspecialchars($c['customer_code']) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="client-avatar" style="background:linear-gradient(135deg,var(--blue),var(--purple));flex-shrink:0;">
                            <?= strtoupper(substr($c['full_name'],0,1)) ?>
                        </div>
                        <div>
                            <a href="<?= base_url("customers/view/{$c['id']}") ?>" style="font-weight:600;font-size:13px;color:var(--text);text-decoration:none;"><?= htmlspecialchars($c['full_name']) ?></a>
                            <?php if (!empty($c['client_type'])): ?>
                            <div style="font-size:10px;color:var(--text2);"><?= ucfirst($c['client_type']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-size:13px;"><?= htmlspecialchars($c['phone']) ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($c['zone_name'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($c['sub_zone'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($c['box_no'] ?? '—') ?></td>
                <td>
                    <?php if ($c['package_name']): ?>
                    <div style="font-size:12px;font-weight:500;"><?= htmlspecialchars($c['package_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);">৳<?= number_format($c['monthly_charge'],0) ?>/mo</div>
                    <?php else: ?>
                    <span style="color:var(--text2);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($c['nas_name'] ?? '—') ?></td>
                <td style="font-size:12px;font-family:monospace;">
                    <?php if ($c['speed_download']): ?>
                    <?= $c['speed_download'] ?>↓/<?= $c['speed_upload'] ?? $c['speed_download'] ?>↑
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($c['expiration'])): ?>
                    <div style="font-size:12px;<?= strtotime($c['expiration']) < time() ? 'color:var(--red);font-weight:600;' : '' ?>">
                        <?= date('d/m/Y', strtotime($c['expiration'])) ?>
                    </div>
                    <?php else: ?>
                    <span style="color:var(--text2);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $mac = $c['mac_address'] ?: ($c['mac_from_binding'] ?? ''); ?>
                    <?php if ($mac): ?>
                    <code style="background:var(--bg3);padding:2px 5px;border-radius:4px;font-size:10px;"><?= htmlspecialchars($mac) ?></code>
                    <?php else: ?>
                    <span style="color:var(--text2);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;">
                    <span class="badge badge-blue" style="font-size:10px;"><?= strtoupper($c['connection_type'] ?? 'PPPOE') ?></span>
                </td>
                <td>
                    <?php
                    $sc = ['active'=>'badge-green','suspended'=>'badge-red','pending'=>'badge-yellow','cancelled'=>'badge-gray','deleted'=>'badge-gray'];
                    $statusColors = ['active'=>'#22c55e','suspended'=>'#ef4444','pending'=>'#f59e0b','cancelled'=>'#6b7280','deleted'=>'#6b7280'];
                    echo '<span class="badge '.($sc[$c['status']]?:'badge-gray').'">';
                    echo '<span class="status-dot" style="background:'.($statusColors[$c['status']]??'#6b7280').'"></span>';
                    echo ucfirst($c['status']).'</span>';
                    ?>
                </td>
                <td style="font-size:12px;color:var(--text2);">
                    <?= !empty($c['connection_date']) ? date('d/m/Y', strtotime($c['connection_date'])) : '—' ?>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($c['thana'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($c['district'] ?? '—') ?></td>
                <td>
                    <div style="display:flex;gap:5px;justify-content:flex-end;">
                        <a href="<?= base_url("customers/view/{$c['id']}") ?>" class="btn btn-ghost btn-sm" title="View"><i class="fa-solid fa-eye"></i></a>
                        <a href="<?= base_url("customers/edit/{$c['id']}") ?>" class="btn btn-ghost btn-sm" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php if ($c['status'] === 'active'): ?>
                        <button onclick="confirmSuspend(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')" class="action-btn btn-suspend" title="Suspend"><i class="fa-solid fa-ban"></i></button>
                        <?php elseif ($c['status'] === 'suspended'): ?>
                        <button onclick="confirmReconnect(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')" class="action-btn btn-reconnect" title="Reconnect"><i class="fa-solid fa-rotate"></i></button>
                        <?php endif; ?>
                        <?php if ($c['status'] !== 'deleted'): ?>
                        <button onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')" class="action-btn" style="color:#dc2626;" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;justify-content:between;align-items:center;margin-top:16px;gap:12px;flex-wrap:wrap;">
    <div style="font-size:13px;color:var(--text2);">
        Showing <?= min($offset+1, $total) ?>–<?= min($offset+$limit, $total) ?> of <?= number_format($total) ?>
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

<!-- Suspend Modal -->
<div class="modal-overlay" id="suspendModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-ban" style="color:var(--red);margin-right:8px;"></i>Suspend Customer</div>
            <button class="icon-btn" onclick="closeModal('suspendModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="suspendForm">
            <div class="modal-body">
                <p style="color:var(--text2);font-size:13px;margin-bottom:16px;">Are you sure you want to suspend <strong id="suspendName"></strong>?</p>
                <label class="form-label">Reason for suspension</label>
                <select name="reason" class="form-input">
                    <option>Non-payment</option>
                    <option>Customer request</option>
                    <option>Network abuse</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('suspendModal')">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Suspend</button>
            </div>
        </form>
    </div>
</div>

<!-- Reconnect Modal -->
<div class="modal-overlay" id="reconnectModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-rotate" style="color:var(--green);margin-right:8px;"></i>Reconnect Customer</div>
            <button class="icon-btn" onclick="closeModal('reconnectModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="reconnectForm">
            <div class="modal-body">
                <p style="color:var(--text2);font-size:13px;">Reconnect <strong id="reconnectName"></strong>? Ensure payment is cleared before reconnecting.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('reconnectModal')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-rotate"></i> Reconnect</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div class="modal-overlay" id="importModal">
    <div class="modal" style="max-width:680px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-upload" style="color:var(--blue);margin-right:8px;"></i>Import Clients</div>
            <button class="icon-btn" onclick="closeModal('importModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('customers/import') ?>" enctype="multipart/form-data">
            <div class="modal-body" style="max-height:70vh;overflow-y:auto;">

                <!-- Download template strip -->
                <div style="background:linear-gradient(135deg,rgba(59,130,246,0.08),rgba(139,92,246,0.08));border:1px solid rgba(59,130,246,0.25);border-radius:10px;padding:14px 16px;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--blue);margin-bottom:3px;">
                                <i class="fa-solid fa-file-csv" style="margin-right:6px;"></i>Download Demo Template
                            </div>
                            <div style="font-size:11px;color:var(--text2);">30 columns · rules row · 3 sample rows · UTF-8 CSV</div>
                        </div>
                        <a href="<?= base_url('customers/download-template?type=csv') ?>" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-download"></i> Download Template
                        </a>
                    </div>
                </div>

                <!-- Column rules table -->
                <div style="margin-bottom:16px;">
                    <div style="font-size:12px;font-weight:700;margin-bottom:8px;color:var(--text);">
                        <i class="fa-solid fa-table" style="color:var(--purple);margin-right:6px;"></i>Column Rules
                    </div>
                    <div style="overflow-x:auto;border:1px solid var(--border);border-radius:8px;">
                        <table style="width:100%;border-collapse:collapse;font-size:11px;">
                            <thead>
                                <tr style="background:var(--bg3);">
                                    <th style="padding:7px 10px;text-align:left;border-bottom:1px solid var(--border);font-weight:700;">Column</th>
                                    <th style="padding:7px 10px;text-align:left;border-bottom:1px solid var(--border);font-weight:700;">Required</th>
                                    <th style="padding:7px 10px;text-align:left;border-bottom:1px solid var(--border);font-weight:700;">Format / Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $colRules = [
                                    ['Client Name',       true,  'Full name of the client'],
                                    ['Mobile',            true,  '01XXXXXXXXX — must be unique'],
                                    ['ID/IP',             false, 'PPPoE username or IP — must be unique if provided'],
                                    ['MACAddress',        false, 'AA:BB:CC:DD:EE:FF — must be unique if provided'],
                                    ['Email',             false, 'Valid email address'],
                                    ['Address',           false, 'Installation address'],
                                    ['Road No',           false, 'Road number'],
                                    ['House No',          false, 'House number'],
                                    ['NID',               false, 'National ID number'],
                                    ['Zone',              false, 'Must match an existing zone name in the system'],
                                    ['Sub Zone',          false, 'Sub-zone or area name'],
                                    ['Box',               false, 'Distribution box number'],
                                    ['Package',           false, 'Must match an existing package name in the system'],
                                    ['Server',            false, 'Must match an existing NAS/server name in the system'],
                                    ['Password',          false, 'PPPoE password'],
                                    ['Conn. Type',        false, 'pppoe / hotspot / static (default: pppoe)'],
                                    ['Client Type',       false, 'home / business / corporate / other (default: home)'],
                                    ['M.Bill',            false, 'Monthly bill amount — numbers only (e.g. 515.00)'],
                                    ['B.Status',          false, 'Active / Inactive (default: Active)'],
                                    ['ClientJoiningDate', false, 'YYYY-MM-DD or MM/DD/YYYY'],
                                    ['Ex.Date',           false, 'Expiry date — YYYY-MM-DD'],
                                    ['Thana',             false, 'Thana / Upazila'],
                                    ['District',          false, 'District name'],
                                    ['Device',            false, 'Device model name'],
                                    ['PurchaseDate',      false, 'Device purchase date — YYYY-MM-DD'],
                                    ['AssignedEmployee',  false, 'Employee name'],
                                    ['Remarks',           false, 'Optional notes'],
                                    ['C.Code',            false, 'Leave blank — auto-generated'],
                                    ['Speed',             false, 'Informational only — ignored on import'],
                                    ['Protocol',          false, 'Informational only — ignored on import'],
                                ];
                                foreach ($colRules as [$col, $req, $note]):
                                ?>
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:6px 10px;font-family:monospace;font-weight:600;white-space:nowrap;"><?= $col ?></td>
                                    <td style="padding:6px 10px;text-align:center;">
                                        <?php if ($req): ?>
                                        <span style="background:rgba(239,68,68,0.12);color:var(--red);padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700;">Required</span>
                                        <?php else: ?>
                                        <span style="background:var(--bg3);color:var(--text2);padding:2px 7px;border-radius:20px;font-size:10px;">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:6px 10px;color:var(--text2);"><?= $note ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Import rules summary -->
                <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:12px;">
                    <div style="font-weight:700;color:#d97706;margin-bottom:6px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>Import Rules</div>
                    <ul style="margin:0;padding-left:18px;color:var(--text2);line-height:1.8;">
                        <li>Column headers must <strong>exactly match</strong> the template (case-sensitive)</li>
                        <li>Rows with duplicate <strong>Mobile</strong>, <strong>ID/IP</strong>, or <strong>MACAddress</strong> will be <strong>rejected</strong></li>
                        <li>Rows missing <strong>Client Name</strong> or <strong>Mobile</strong> will be rejected</li>
                        <li>Package, Zone, and Server names must match existing records in the system</li>
                        <li>Rows 2 in the template is a rules/guide row — it will be skipped automatically if it contains non-numeric phone</li>
                        <li>Accepted: CSV (.csv), Excel (.xlsx, .xls)</li>
                    </ul>
                </div>

                <!-- File upload -->
                <div>
                    <label class="form-label">Select File (CSV or Excel)</label>
                    <input type="file" name="csv_file" accept=".csv,.xlsx,.xls" class="form-input" required
                           style="padding:10px;">
                    <div style="font-size:11px;color:var(--text2);margin-top:4px;">
                        <i class="fa-solid fa-info-circle"></i> Max recommended: 5,000 rows per import
                    </div>
                </div>

                <?php if (!empty($_SESSION['import_errors'])): ?>
                <div style="margin-top:14px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.25);border-radius:8px;padding:12px 14px;">
                    <div style="font-size:12px;font-weight:700;color:var(--red);margin-bottom:8px;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                        <?= count($_SESSION['import_errors']) ?> rows were rejected in the last import:
                    </div>
                    <div style="max-height:160px;overflow-y:auto;">
                        <?php foreach ($_SESSION['import_errors'] as $err): ?>
                        <div style="font-size:11px;color:var(--red);padding:2px 0;border-bottom:1px solid rgba(239,68,68,0.1);"><?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php unset($_SESSION['import_errors']); endif; ?>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('importModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Start Import</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmSuspend(id, name) {
    document.getElementById('suspendName').textContent = name;
    document.getElementById('suspendForm').action = '<?= base_url('customers/suspend/') ?>' + id;
    document.getElementById('suspendModal').classList.add('open');
}
function confirmReconnect(id, name) {
    document.getElementById('reconnectName').textContent = name;
    document.getElementById('reconnectForm').action = '<?= base_url('customers/reconnect/') ?>' + id;
    document.getElementById('reconnectModal').classList.add('open');
}
function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete customer "${name}"?\n\nThis will mark the customer as deleted but preserve their data.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('customers/delete/') ?>' + id;
        document.body.appendChild(form);
        form.submit();
    }
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function toggleAll(el) { document.querySelectorAll('.rowCheck').forEach(c => c.checked = el.checked); }
function exportCSV() {
    window.location = '<?= base_url('customers') ?>?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>';
}
function exportPDF() {
    window.location = '<?= base_url('customers') ?>?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>';
}
function openModal(id) { document.getElementById(id).classList.add('open'); }

// Auto-open import modal if there were import errors from last run
<?php if (!empty($_SESSION['import_errors'])): ?>
window.addEventListener('DOMContentLoaded', () => openModal('importModal'));
<?php endif; ?>
</script>
