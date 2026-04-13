<?php // views/radius/sessions.php — RADIUS Session Monitoring ?>

<div class="page-header fade-in" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1 class="page-title">RADIUS Sessions</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-satellite-dish" style="color:var(--blue)"></i> Network
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> RADIUS
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> Sessions
        </div>
    </div>
</div>

<!-- Stats -->
<div style="display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap;" class="fade-in">
    <?php foreach ([
        ['Active Sessions', $stats['total_active'] ?? 0, 'fa-plug-circle-check', 'var(--blue)'],
        ['Bandwidth In',    formatSessionBytes($stats['total_bytes_in'] ?? 0), 'fa-arrow-down', 'var(--green)'],
        ['Bandwidth Out',   formatSessionBytes($stats['total_bytes_out'] ?? 0), 'fa-arrow-up', '#f59e0b'],
        ['Unique NAS',      $stats['unique_nas_count'] ?? 0, 'fa-server', 'var(--purple)'],
    ] as [$label, $val, $icon, $color]): ?>
    <div class="card" style="flex:1;min-width:140px;padding:16px 20px;">
        <div style="font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;"><?= $label ?></div>
        <div style="font-size:22px;font-weight:700;color:<?= $color ?>;margin-top:4px;">
            <i class="fa-solid <?= $icon ?>" style="font-size:14px;margin-right:6px;"></i><?= $val ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card fade-in" style="padding:12px 16px;margin-bottom:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="username" class="form-input" style="width:180px;" placeholder="Filter by username" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
        <input type="text" name="nas_ip" class="form-input" style="width:150px;" placeholder="Filter by NAS IP" value="<?= htmlspecialchars($_GET['nas_ip'] ?? '') ?>">
        <button type="submit" class="btn btn-primary" style="padding:6px 14px;"><i class="fa-solid fa-filter"></i> Filter</button>
        <?php if (!empty($_GET['username']) || !empty($_GET['nas_ip'])): ?>
        <a href="<?= base_url('network/radius/sessions') ?>" class="btn btn-ghost" style="padding:6px 14px;">Clear</a>
        <?php endif; ?>
    </form>
    <span style="margin-left:auto;font-size:12px;color:var(--text2);"><?= count($activeSessions) ?> session(s)</span>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div style="background:rgba(16,185,129,.1);border:1px solid var(--green);color:var(--green);padding:12px;border-radius:8px;margin-bottom:16px;font-weight:600;">
    <i class="fa-solid fa-check-circle" style="margin-right:8px;"></i><?= $_SESSION['success'] ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<!-- Sessions Table -->
<div class="card fade-in">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr>
                <th>Username</th><th>NAS IP</th><th>Framed IP</th>
                <th>Started</th><th>Duration</th><th>Bytes In</th><th>Bytes Out</th><th>Action</th>
            </tr></thead>
            <tbody>
            <?php if (empty($activeSessions)): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--text2);padding:32px;">No active sessions found</td></tr>
            <?php else: foreach ($activeSessions as $s): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['username']) ?></strong></td>
                    <td><?= htmlspecialchars($s['nas_ip'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['framed_ip'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($s['start_time'] ?? '—') ?></td>
                    <td><?= formatSessionDuration($s['duration_seconds'] ?? 0) ?></td>
                    <td><?= formatSessionBytes($s['bytes_in'] ?? 0) ?></td>
                    <td><?= formatSessionBytes($s['bytes_out'] ?? 0) ?></td>
                    <td>
                        <form method="POST" action="<?= base_url('network/radius/sessions/terminate/' . urlencode($s['session_id'])) ?>"
                              onsubmit="return confirm('Terminate session for <?= htmlspecialchars($s['username']) ?>?')">
                            <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:12px;">
                                <i class="fa-solid fa-plug-circle-xmark"></i> Kick
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
function formatSessionBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
function formatSessionDuration(int $seconds): string {
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    if ($h > 0) return "{$h}h {$m}m";
    if ($m > 0) return "{$m}m {$s}s";
    return "{$s}s";
}
?>
