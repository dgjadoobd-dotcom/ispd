<?php // views/superadmin/noc/index.php ?>

<!-- Page Header -->
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Network Operations Center</h1>
        <div class="page-breadcrumb">System health & infrastructure monitoring · <?= date('d F Y, H:i:s') ?></div>
    </div>
    <button class="btn btn-primary btn-sm" onclick="location.reload()">
        <i class="fa-solid fa-rotate"></i> Refresh
    </button>
</div>

<!-- System Health Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;" class="fade-in">
    <?php
    $diskColor = $systemInfo['disk_pct'] > 80 ? '#dc2626' : ($systemInfo['disk_pct'] > 60 ? '#d97706' : '#16a34a');
    $items = [
        ['PHP Version', $systemInfo['php_version'], 'fa-php', '#7c3aed', 'rgba(124,58,237,0.1)'],
        ['Memory Limit', $systemInfo['memory_limit'], 'fa-memory', '#2563eb', 'rgba(37,99,235,0.1)'],
        ['Max Exec Time', $systemInfo['max_execution'].'s', 'fa-clock', '#d97706', 'rgba(217,119,6,0.1)'],
        ['Upload Max', $systemInfo['upload_max'], 'fa-upload', '#16a34a', 'rgba(22,163,74,0.1)'],
    ];
    foreach ($items as [$lbl, $val, $ico, $color, $bg]):
    ?>
    <div class="card" style="padding:16px 18px;display:flex;align-items:center;gap:12px;">
        <div style="width:40px;height:40px;border-radius:10px;background:<?= $bg ?>;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
            <i class="fa-brands <?= $ico ?>"></i>
        </div>
        <div>
            <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($val) ?></div>
            <div style="font-size:11px;color:var(--text2);"><?= $lbl ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Disk + OS Info -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;" class="fade-in">
    <!-- Disk Usage -->
    <div class="card" style="padding:22px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-hard-drive" style="color:var(--purple);"></i> Disk Usage
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <span style="font-size:13px;color:var(--text2);">Used Space</span>
            <span style="font-size:20px;font-weight:800;color:<?= $diskColor ?>;"><?= $systemInfo['disk_pct'] ?>%</span>
        </div>
        <div class="progress-bar" style="height:10px;margin-bottom:10px;">
            <div class="progress-fill" style="width:<?= $systemInfo['disk_pct'] ?>%;background:<?= $diskColor ?>;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text2);">
            <span><?= $systemInfo['disk_free_gb'] ?> GB free</span>
            <span><?= $systemInfo['disk_total_gb'] ?> GB total</span>
        </div>
        <div style="margin-top:14px;padding:10px 14px;background:var(--bg3);border-radius:8px;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:13px;color:var(--text2);">Log Files Size</span>
            <span style="font-size:13px;font-weight:700;"><?= $systemInfo['log_size_mb'] ?> MB</span>
        </div>
    </div>

    <!-- Server Info -->
    <div class="card" style="padding:22px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-server" style="color:var(--purple);"></i> Server Info
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php $serverItems = [
                ['Operating System', $systemInfo['os']],
                ['Server Uptime', $systemInfo['uptime'] ?? 'N/A'],
                ['Load Average', $systemInfo['load_avg'] ?? 'N/A'],
                ['Server Time', date('d M Y H:i:s')],
            ]; foreach ($serverItems as [$lbl, $val]): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;background:var(--bg3);border-radius:8px;">
                <span style="font-size:12px;color:var(--text2);"><?= $lbl ?></span>
                <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- NAS Devices -->
<div class="card fade-in" style="overflow:hidden;margin-bottom:24px;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-network-wired" style="color:var(--purple);"></i> NAS / MikroTik Devices
        </div>
        <?php
        $online = count(array_filter($nasDevices, fn($n) => $n['connection_status'] === 'online' || $n['connection_status'] == 1));
        $total  = count($nasDevices);
        ?>
        <div style="display:flex;gap:8px;">
            <span class="badge badge-green"><?= $online ?> Online</span>
            <?php if ($total - $online > 0): ?>
            <span class="badge badge-red"><?= $total - $online ?> Offline</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($nasDevices)): ?>
    <div style="text-align:center;padding:32px;color:var(--text2);">No NAS devices configured</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Device Name</th><th>IP Address</th><th>Status</th><th>Last Checked</th></tr></thead>
            <tbody>
                <?php foreach ($nasDevices as $nas):
                    $isOnline = $nas['connection_status'] === 'online' || $nas['connection_status'] == 1;
                    $badgeClass = $isOnline ? 'badge-green' : 'badge-red';
                    $statusLabel = $isOnline ? 'Online' : 'Offline';
                    $lastChecked = !empty($nas['last_checked']) ? date('d M H:i', strtotime($nas['last_checked'])) : 'Never';
                ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($nas['name']) ?></td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($nas['ip_address'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <i class="fa-solid fa-circle" style="font-size:7px;"></i> <?= $statusLabel ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text2);"><?= $lastChecked ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Alerts -->
<div class="card fade-in" style="overflow:hidden;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
        <div style="font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-bell" style="color:var(--yellow);"></i> Recent Alerts
        </div>
    </div>
    <?php if (empty($recentAlerts)): ?>
    <div style="text-align:center;padding:32px;color:var(--text2);">
        <i class="fa-solid fa-circle-check" style="font-size:28px;color:var(--green);margin-bottom:8px;display:block;"></i>
        No recent alerts — system is healthy
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Time</th><th>Type</th><th>Message</th><th>Severity</th></tr></thead>
            <tbody>
                <?php foreach ($recentAlerts as $alert):
                    $sevColors = ['critical'=>'badge-red','warning'=>'badge-yellow','info'=>'badge-blue'];
                    $sevBadge = $sevColors[$alert['severity'] ?? 'info'] ?? 'badge-gray';
                ?>
                <tr>
                    <td style="font-size:12px;color:var(--text2);white-space:nowrap;"><?= date('d M H:i', strtotime($alert['created_at'])) ?></td>
                    <td style="font-size:13px;font-weight:600;"><?= htmlspecialchars($alert['alert_type'] ?? '—') ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($alert['message'] ?? '—') ?></td>
                    <td><span class="badge <?= $sevBadge ?>"><?= htmlspecialchars($alert['severity'] ?? 'info') ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
