<?php // views/radius/analytics.php — RADIUS Analytics (partial, loaded via main layout) ?>

<div class="page-header fade-in" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1 class="page-title">RADIUS Analytics</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-satellite-dish" style="color:var(--blue)"></i> Network
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> RADIUS
            <i class="fa-solid fa-angle-right" style="margin:0 8px;font-size:10px;opacity:.5;"></i> Analytics
        </div>
    </div>
</div>

<!-- Period Selector -->
<div class="card fade-in" style="padding:12px 16px;margin-bottom:20px;display:flex;gap:8px;align-items:center;">
    <span style="font-size:13px;color:var(--text2);margin-right:4px;">Period:</span>
    <?php foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $key => $label): ?>
    <a href="?period=<?= $key ?>" class="btn <?= $period === $key ? 'btn-primary' : 'btn-ghost' ?>" style="padding:5px 14px;font-size:13px;">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Top Users -->
<div class="card fade-in" style="margin-bottom:24px;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h2 style="font-size:15px;font-weight:700;margin:0;"><i class="fa-solid fa-trophy" style="color:#f59e0b;margin-right:8px;"></i>Top Users by Usage</h2>
        <span style="font-size:12px;color:var(--text2);">Total registered: <strong><?= number_format($totalUsers ?? 0) ?></strong></span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>#</th><th>Username</th><th>Download</th><th>Upload</th><th>Total</th><th>Sessions</th></tr></thead>
            <tbody>
            <?php if (empty($topUsers)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text2);padding:24px;">No data for this period</td></tr>
            <?php else: foreach ($topUsers as $i => $u): ?>
                <tr>
                    <td style="color:var(--text2);"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong>
                        <?php if (!empty($u['profile'])): ?>
                        <span style="font-size:11px;color:var(--text2);margin-left:6px;"><?= htmlspecialchars($u['profile']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= analyticsFormatBytes((int)($u['total_bytes_in'] ?? 0)) ?></td>
                    <td><?= analyticsFormatBytes((int)($u['total_bytes_out'] ?? 0)) ?></td>
                    <td><strong><?= analyticsFormatBytes((int)($u['total_bytes'] ?? 0)) ?></strong></td>
                    <td><?= number_format((int)($u['session_count'] ?? 0)) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Hourly Sessions Chart -->
<div class="card fade-in" style="margin-bottom:24px;padding:20px;">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 16px;"><i class="fa-solid fa-clock" style="color:var(--blue);margin-right:8px;"></i>Hourly Sessions — <?= date('Y-m-d') ?></h2>
    <div style="display:flex;align-items:flex-end;gap:3px;height:100px;">
        <?php
        $maxCount = max(array_column($hourlyCounts, 'session_count') ?: [1]);
        foreach ($hourlyCounts as $h):
            $pct = $maxCount > 0 ? round(($h['session_count'] / $maxCount) * 100) : 0;
        ?>
        <div style="display:flex;flex-direction:column;align-items:center;flex:1;">
            <?php if ($h['session_count'] > 0): ?>
            <div style="font-size:9px;color:var(--text2);margin-bottom:2px;"><?= $h['session_count'] ?></div>
            <?php endif; ?>
            <div style="width:100%;background:var(--blue);border-radius:2px 2px 0 0;min-height:2px;height:<?= max($pct, 2) ?>%;opacity:.8;"></div>
            <div style="font-size:9px;color:var(--text2);margin-top:3px;"><?= $h['hour'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Daily Summary -->
<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h2 style="font-size:15px;font-weight:700;margin:0;"><i class="fa-solid fa-calendar-days" style="color:var(--purple);margin-right:8px;"></i>Daily Summary — Last 7 Days</h2>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Date</th><th>Download</th><th>Upload</th><th>Sessions</th><th>Unique Users</th></tr></thead>
            <tbody>
            <?php if (empty($dailySummary)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text2);padding:24px;">No data available</td></tr>
            <?php else: foreach ($dailySummary as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['date']) ?></td>
                    <td><?= analyticsFormatBytes($d['total_bytes_in']) ?></td>
                    <td><?= analyticsFormatBytes($d['total_bytes_out']) ?></td>
                    <td><?= number_format($d['total_sessions']) ?></td>
                    <td><?= number_format($d['unique_users']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
function analyticsFormatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>
