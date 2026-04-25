<?php // views/superadmin/logs/index.php ?>

<!-- Page Header -->
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Activity Logs</h1>
        <div class="page-breadcrumb">Audit trail of all system actions · <?= number_format($total) ?> total records</div>
    </div>
    <button class="btn btn-danger btn-sm" onclick="openModal('clearLogsModal')">
        <i class="fa-solid fa-trash-can"></i> Clear Old Logs
    </button>
</div>

<!-- Filters -->
<div class="card fade-in" style="padding:16px 20px;margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label class="form-label" style="font-size:11px;">Search</label>
            <input type="text" name="search" class="form-input" placeholder="Description or username…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="font-size:11px;">Action Type</label>
            <select name="action" class="form-input">
                <option value="">All Actions</option>
                <?php foreach ($actions as $a): ?>
                <option value="<?= htmlspecialchars($a['action']) ?>" <?= ($_GET['action'] ?? '') === $a['action'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['action']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="font-size:11px;">User</label>
            <select name="user_id" class="form-input">
                <option value="">All Users</option>
                <?php foreach ($allUsers as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (int)($_GET['user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['full_name'] ?? $u['username']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
        <a href="<?= base_url('superadmin/logs') ?>" class="btn btn-ghost btn-sm">Reset</a>
    </form>
</div>

<!-- Logs Table -->
<div class="card fade-in" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text2);">
                    <i class="fa-solid fa-scroll" style="font-size:24px;margin-bottom:8px;display:block;opacity:0.3;"></i>
                    No logs found
                </td></tr>
                <?php else: foreach ($logs as $log):
                    $actionColors = [
                        'sa_login' => 'badge-green', 'sa_logout' => 'badge-gray',
                        'sa_login_fail' => 'badge-red', 'sa_user_create' => 'badge-blue',
                        'sa_user_update' => 'badge-yellow', 'sa_user_delete' => 'badge-red',
                        'sa_settings_save' => 'badge-purple', 'sa_logs_clear' => 'badge-red',
                    ];
                    $badgeClass = $actionColors[$log['action']] ?? 'badge-gray';
                ?>
                <tr>
                    <td style="font-size:12px;color:var(--text2);white-space:nowrap;"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                    <td>
                        <?php if ($log['username']): ?>
                        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($log['full_name'] ?? $log['username']) ?></div>
                        <div style="font-size:11px;color:var(--text2);">@<?= htmlspecialchars($log['username']) ?></div>
                        <?php else: ?>
                        <span style="color:var(--text2);font-size:12px;">System</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td style="font-size:13px;max-width:400px;"><?= htmlspecialchars($log['description'] ?? '—') ?></td>
                    <td style="font-family:monospace;font-size:12px;color:var(--text2);"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:13px;color:var(--text2);">Page <?= $page ?> of <?= $pages ?> · <?= number_format($total) ?> records</div>
        <div style="display:flex;gap:6px;">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-ghost btn-xs">
                <i class="fa-solid fa-chevron-left"></i> Prev
            </a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
               class="btn btn-xs <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-ghost btn-xs">
                Next <i class="fa-solid fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Clear Logs Modal -->
<div class="modal-overlay" id="clearLogsModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-trash-can" style="margin-right:8px;color:var(--red);"></i>Clear Old Logs</div>
            <button class="btn btn-ghost btn-xs" onclick="closeModal('clearLogsModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="clearLogsForm">
            <div class="modal-body">
                <p style="font-size:13px;color:var(--text2);margin-bottom:14px;">Delete activity logs older than the specified number of days. This action cannot be undone.</p>
                <label class="form-label">Delete logs older than (days)</label>
                <input type="number" name="days" class="form-input" value="30" min="1" max="365" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('clearLogsModal')">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i> Clear Logs</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('clearLogsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const r = await fetch('<?= base_url('superadmin/logs/clear') ?>', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) { showToast(d.message, 'success'); closeModal('clearLogsModal'); setTimeout(() => location.reload(), 800); }
    else showToast(d.message, 'error');
});
</script>
