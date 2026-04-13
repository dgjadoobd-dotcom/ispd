<?php // views/radius/audit.php — RADIUS Audit Log ?>

<div class="page-header fade-in" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1 class="page-title">RADIUS Audit Log</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-satellite-dish" style="color:var(--blue)"></i> Network
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> RADIUS
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> Audit Log
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card fade-in" style="padding:14px 16px;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="admin_user" class="form-input" style="width:150px;" placeholder="Admin user" value="<?= htmlspecialchars($_GET['admin_user'] ?? '') ?>">
        <input type="text" name="action" class="form-input" style="width:150px;" placeholder="Action" value="<?= htmlspecialchars($_GET['action'] ?? '') ?>">
        <input type="text" name="target_username" class="form-input" style="width:150px;" placeholder="Target username" value="<?= htmlspecialchars($_GET['target_username'] ?? '') ?>">
        <input type="date" name="date_from" class="form-input" style="width:140px;" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        <input type="date" name="date_to" class="form-input" style="width:140px;" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        <button type="submit" class="btn btn-primary" style="padding:6px 14px;"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="<?= base_url('network/radius/audit') ?>" class="btn btn-ghost" style="padding:6px 14px;">Clear</a>
    </form>
</div>

<!-- Audit Table -->
<div class="card fade-in">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h2 style="font-size:15px;font-weight:700;margin:0;"><i class="fa-solid fa-shield-halved" style="color:var(--blue);margin-right:8px;"></i>Audit Entries</h2>
        <span style="font-size:12px;color:var(--text2);"><?= count($logs) ?> record(s)</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr>
                <th>Time</th><th>Admin</th><th>Action</th><th>Target User</th><th>IP Address</th><th>Details</th>
            </tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text2);padding:32px;">No audit log entries found</td></tr>
            <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td style="font-size:12px;color:var(--text2);white-space:nowrap;"><?= htmlspecialchars($log['created_at']) ?></td>
                    <td><strong><?= htmlspecialchars($log['admin_user']) ?></strong></td>
                    <td>
                        <span style="background:var(--bg);border:1px solid var(--border);padding:2px 8px;border-radius:4px;font-size:12px;font-family:monospace;">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['target_username'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text2);max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?php
                        $details = json_decode($log['details'] ?? '{}', true);
                        echo htmlspecialchars($details ? json_encode($details) : '—');
                        ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
