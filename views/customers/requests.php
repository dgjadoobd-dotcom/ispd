<?php // views/customers/requests.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">New Requests</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('customers') ?>" style="color:var(--blue);text-decoration:none;">Clients</a> › Requests
        </div>
    </div>
    <a href="<?= base_url('signup') ?>" target="_blank" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Online Signup Page
    </a>
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

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;" class="fade-in">
    <div class="card" style="padding:16px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:10px;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-clock" style="color:var(--yellow);font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:26px;font-weight:800;color:var(--yellow);"><?= count($pendingCustomers) ?></div>
            <div style="font-size:12px;color:var(--text2);">Pending New Connections</div>
        </div>
    </div>
    <div class="card" style="padding:16px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:10px;background:rgba(124,58,237,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-arrows-rotate" style="color:var(--purple);font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:26px;font-weight:800;color:var(--purple);"><?= count($pkgRequests) ?></div>
            <div style="font-size:12px;color:var(--text2);">Package Change Requests</div>
        </div>
    </div>
    <div class="card" style="padding:16px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:10px;background:rgba(37,99,235,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-globe" style="color:var(--blue);font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:26px;font-weight:800;color:var(--blue);"><?= count($onlineSignups) ?></div>
            <div style="font-size:12px;color:var(--text2);">Online Signup Tickets</div>
        </div>
    </div>
</div>

<!-- ── Section 1: Pending New Connections ── -->
<div class="card fade-in" style="overflow:hidden;margin-bottom:20px;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:14px;font-weight:700;">
            <i class="fa-solid fa-clock" style="color:var(--yellow);margin-right:8px;"></i>
            Pending New Connections
            <span style="font-size:11px;background:rgba(245,158,11,0.15);color:var(--yellow);padding:2px 8px;border-radius:20px;margin-left:8px;"><?= count($pendingCustomers) ?></span>
        </div>
        <a href="<?= base_url('customers?status=pending') ?>" style="font-size:12px;color:var(--blue);text-decoration:none;">View all pending →</a>
    </div>
    <?php if (empty($pendingCustomers)): ?>
    <div style="padding:32px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-check-circle" style="font-size:28px;opacity:.2;display:block;margin-bottom:8px;"></i>
        No pending connection requests
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Client</th><th>Phone</th><th>Zone</th><th>Package</th><th>Address</th><th>Requested</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($pendingCustomers as $c): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($c['full_name']) ?></div>
                    <div style="font-size:11px;font-family:monospace;color:var(--text2);"><?= htmlspecialchars($c['customer_code']) ?></div>
                </td>
                <td style="font-size:13px;"><?= htmlspecialchars($c['phone']) ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($c['zone_name'] ?? '—') ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($c['package_name'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($c['address']) ?>"><?= htmlspecialchars($c['address']) ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="<?= base_url("customers/view/{$c['id']}") ?>" class="btn btn-ghost btn-sm" title="View"><i class="fa-solid fa-eye"></i></a>
                        <form method="POST" action="<?= base_url("customers/requests/approve/{$c['id']}") ?>" style="display:inline;">
                            <button type="submit" class="btn btn-success btn-sm" title="Approve & Activate"
                                    onclick="return confirm('Approve and activate <?= htmlspecialchars(addslashes($c['full_name'])) ?>?')">
                                <i class="fa-solid fa-check"></i> Approve
                            </button>
                        </form>
                        <button class="btn btn-danger btn-sm" title="Reject"
                                onclick="openRejectModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')">
                            <i class="fa-solid fa-xmark"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Section 2: Package Change Requests ── -->
<div class="card fade-in" style="overflow:hidden;margin-bottom:20px;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
        <div style="font-size:14px;font-weight:700;">
            <i class="fa-solid fa-arrows-rotate" style="color:var(--purple);margin-right:8px;"></i>
            Package Change Requests
            <span style="font-size:11px;background:rgba(124,58,237,0.15);color:var(--purple);padding:2px 8px;border-radius:20px;margin-left:8px;"><?= count($pkgRequests) ?></span>
        </div>
    </div>
    <?php if (empty($pkgRequests)): ?>
    <div style="padding:32px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-check-circle" style="font-size:28px;opacity:.2;display:block;margin-bottom:8px;"></i>
        No pending package change requests
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Client</th><th>Current Package</th><th>Request Details</th><th>Priority</th><th>Submitted</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($pkgRequests as $t): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($t['customer_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($t['phone']) ?></div>
                </td>
                <td style="font-size:12px;"><?= htmlspecialchars($t['current_package'] ?? '—') ?></td>
                <td style="font-size:12px;max-width:200px;">
                    <div style="font-weight:600;"><?= htmlspecialchars($t['subject']) ?></div>
                    <div style="color:var(--text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($t['description']) ?>"><?= htmlspecialchars($t['description']) ?></div>
                </td>
                <td>
                    <?php $pc = ['urgent'=>'badge-red','high'=>'badge-yellow','normal'=>'badge-blue','low'=>'badge-gray'][$t['priority']] ?? 'badge-gray'; ?>
                    <span class="badge <?= $pc ?>"><?= ucfirst($t['priority']) ?></span>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="<?= base_url("support/tickets/view/{$t['id']}") ?>" class="btn btn-ghost btn-sm" title="View Ticket"><i class="fa-solid fa-eye"></i></a>
                        <button class="btn btn-success btn-sm" onclick="openPkgApproveModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['customer_name'])) ?>')">
                            <i class="fa-solid fa-check"></i> Approve
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Section 3: Online Signup Tickets ── -->
<?php if (!empty($onlineSignups)): ?>
<div class="card fade-in" style="overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
        <div style="font-size:14px;font-weight:700;">
            <i class="fa-solid fa-globe" style="color:var(--blue);margin-right:8px;"></i>
            Online Signup Tickets
            <span style="font-size:11px;background:rgba(37,99,235,0.15);color:var(--blue);padding:2px 8px;border-radius:20px;margin-left:8px;"><?= count($onlineSignups) ?></span>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Client</th><th>Phone</th><th>Subject</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($onlineSignups as $t): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($t['customer_name']) ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($t['phone']) ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($t['subject']) ?></td>
                <td><span class="badge badge-blue"><?= ucfirst($t['status']) ?></span></td>
                <td style="font-size:12px;color:var(--text2);"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                <td>
                    <a href="<?= base_url("support/tickets/view/{$t['id']}") ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-eye"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-xmark" style="color:var(--red);margin-right:8px;"></i>Reject Request</div>
            <button class="icon-btn" onclick="document.getElementById('rejectModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="rejectForm">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                <div style="font-size:13px;color:var(--text2);">Rejecting request for: <strong id="rejectName"></strong></div>
                <div>
                    <label class="form-label">Reason for rejection</label>
                    <textarea name="reason" class="form-input" rows="3" placeholder="Explain why this request is being rejected..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('rejectModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Reject Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Package Approve Modal -->
<div class="modal-overlay" id="pkgApproveModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-arrows-rotate" style="color:var(--purple);margin-right:8px;"></i>Approve Package Change</div>
            <button class="icon-btn" onclick="document.getElementById('pkgApproveModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="pkgApproveForm">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                <div style="font-size:13px;color:var(--text2);">Approving package change for: <strong id="pkgClientName"></strong></div>
                <div>
                    <label class="form-label">Select New Package</label>
                    <select name="package_id" class="form-input" required>
                        <option value="">— Select Package —</option>
                        <?php
                        $allPkgs = Database::getInstance()->fetchAll("SELECT id, name, price FROM packages WHERE is_active=1 ORDER BY price ASC");
                        foreach ($allPkgs as $pkg):
                        ?>
                        <option value="<?= $pkg['id'] ?>"><?= htmlspecialchars($pkg['name']) ?> — ৳<?= number_format($pkg['price'],0) ?>/mo</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('pkgApproveModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Approve & Apply</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, name) {
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectForm').action = '<?= base_url("customers/requests/reject/") ?>' + id;
    document.getElementById('rejectModal').classList.add('open');
}
function openPkgApproveModal(id, name) {
    document.getElementById('pkgClientName').textContent = name;
    document.getElementById('pkgApproveForm').action = '<?= base_url("customers/requests/pkg-approve/") ?>' + id;
    document.getElementById('pkgApproveModal').classList.add('open');
}
</script>
