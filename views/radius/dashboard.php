<?php // views/radius/dashboard.php — RADIUS Monitoring Dashboard (partial, loaded via main layout) ?>

<style>
.radius-cards { display:flex; gap:16px; margin:16px 0; flex-wrap:wrap; }
.radius-card  { background:var(--bg2); border:1px solid var(--border); border-radius:8px; padding:16px 24px; min-width:160px; flex:1; }
.radius-card .label { font-size:12px; color:var(--text2); text-transform:uppercase; letter-spacing:.5px; }
.radius-card .value { font-size:28px; font-weight:700; margin-top:4px; color:var(--blue); }
.badge-critical { background:rgba(239,68,68,.15); color:#ef4444; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; text-transform:uppercase; }
.badge-warning  { background:rgba(245,158,11,.15); color:#f59e0b; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; text-transform:uppercase; }
.badge-info     { background:rgba(59,130,246,.15);  color:#3b82f6; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; text-transform:uppercase; }
</style>

<div class="page-header fade-in" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1 class="page-title">RADIUS Dashboard</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-satellite-dish" style="color:var(--blue)"></i> Network
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> RADIUS
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> Dashboard
        </div>
    </div>
    <span id="last-updated" style="font-size:12px;color:var(--text2);">Auto-refreshes every 30s</span>
</div>

<!-- Summary Cards -->
<div class="radius-cards fade-in">
    <div class="radius-card">
        <div class="label">Registered Users</div>
        <div class="value"><?= number_format($totalUsers ?? 0) ?></div>
    </div>
    <div class="radius-card">
        <div class="label">Active Sessions</div>
        <div class="value"><?= number_format($stats['total_active'] ?? 0) ?></div>
    </div>
    <div class="radius-card">
        <div class="label">Bandwidth In</div>
        <div class="value" style="font-size:20px;"><?= formatBytes((int)($stats['total_bytes_in'] ?? 0)) ?></div>
    </div>
    <div class="radius-card">
        <div class="label">Bandwidth Out</div>
        <div class="value" style="font-size:20px;"><?= formatBytes((int)($stats['total_bytes_out'] ?? 0)) ?></div>
    </div>
    <div class="radius-card">
        <div class="label">Unique NAS</div>
        <div class="value"><?= number_format($stats['unique_nas_count'] ?? 0) ?></div>
    </div>
    <div class="radius-card">
        <div class="label">Unresolved Alerts</div>
        <div class="value" style="color:<?= count($unresolvedAlerts) > 0 ? '#ef4444' : 'var(--green)' ?>">
            <?= count($unresolvedAlerts) ?>
        </div>
    </div>
</div>

<!-- Active Sessions Table -->
<div class="card fade-in" style="margin-bottom:24px;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h2 style="font-size:15px;font-weight:700;margin:0;"><i class="fa-solid fa-plug-circle-check" style="color:var(--blue);margin-right:8px;"></i>Active Sessions</h2>
        <a href="<?= base_url('network/radius/sessions') ?>" class="btn btn-ghost" style="font-size:12px;">View All</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr>
                <th>Username</th><th>NAS IP</th><th>Framed IP</th><th>Duration</th><th>Bytes In</th><th>Bytes Out</th>
            </tr></thead>
            <tbody>
            <?php if (empty($activeSessions)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text2);padding:24px;">No active sessions</td></tr>
            <?php else: foreach ($activeSessions as $s): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['username']) ?></strong></td>
                    <td><?= htmlspecialchars($s['nas_ip'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['framed_ip'] ?? '—') ?></td>
                    <td><?= formatDuration($s['duration_seconds'] ?? 0) ?></td>
                    <td><?= formatBytes($s['bytes_in'] ?? 0) ?></td>
                    <td><?= formatBytes($s['bytes_out'] ?? 0) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Alerts -->
<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h2 style="font-size:15px;font-weight:700;margin:0;"><i class="fa-solid fa-bell" style="color:#f59e0b;margin-right:8px;"></i>Unresolved Alerts</h2>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Severity</th><th>Type</th><th>Message</th><th>Time</th></tr></thead>
            <tbody>
            <?php if (empty($unresolvedAlerts)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--text2);padding:24px;"><i class="fa-solid fa-check-circle" style="color:var(--green);margin-right:6px;"></i>No unresolved alerts</td></tr>
            <?php else: foreach ($unresolvedAlerts as $a): ?>
                <tr>
                    <td><span class="badge-<?= htmlspecialchars($a['severity']) ?>"><?= htmlspecialchars($a['severity']) ?></span></td>
                    <td><?= htmlspecialchars($a['alert_type']) ?></td>
                    <td><?= htmlspecialchars($a['message']) ?></td>
                    <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($a['created_at']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
function formatDuration(int $seconds): string {
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    if ($h > 0) return "{$h}h {$m}m";
    if ($m > 0) return "{$m}m {$s}s";
    return "{$s}s";
}
?>
