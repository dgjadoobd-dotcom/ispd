<?php // views/dashboard/index.php ?>
<style>
.kpi-grid-4 { display:grid; gap:16px; margin-bottom:24px; }
.dash-card { display:flex; flex-direction:column; color:#fff; padding:12px 16px; border-radius:6px; box-shadow:0 2px 4px rgba(0,0,0,0.1); transition:transform 0.2s; position:relative; overflow:hidden; }
.dash-card:hover { transform:translateY(-2px); }
.dash-card .top { display:flex; align-items:flex-start; gap:12px; }
.dash-card .icon { font-size:32px; opacity:0.9; }
.dash-card .title { font-size:13px; font-weight:600; letter-spacing:0.5px; opacity:0.95; }
.dash-card .value { font-size:24px; font-weight:700; margin-top:2px; }
.dash-card .footer { border-top:1px solid rgba(255,255,255,0.2); font-size:10px; margin-top:12px; padding-top:6px; text-align:right; opacity:0.9; }
.bg-blue   { background:linear-gradient(135deg,#0ea5e9,#0284c7); }
.bg-teal   { background:linear-gradient(135deg,#14b8a6,#0d9488); }
.bg-violet { background:linear-gradient(135deg,#8b5cf6,#7c3aed); }
.bg-dark   { background:linear-gradient(135deg,#4b5563,#3f3f46); }
.bg-green  { background:linear-gradient(135deg,#22c55e,#16a34a); }
.bg-red    { background:linear-gradient(135deg,#ef4444,#dc2626); }
.bg-orange { background:linear-gradient(135deg,#f97316,#ea580c); }
.bg-indigo { background:linear-gradient(135deg,#6366f1,#4f46e5); }
/* Device status section */
.device-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:16px; }
.device-table th { background:linear-gradient(90deg,#1e3a8a,#1e40af); color:#fff; padding:9px 12px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; }
.device-table td { padding:10px 12px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
.device-table tr:last-child td { border-bottom:none; }
.device-table tbody tr:hover { background:var(--bg3); }
.status-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; }
.dot-online  { background:#22c55e; box-shadow:0 0 6px rgba(34,197,94,.6); }
.dot-offline { background:#ef4444; }
.dot-unknown { background:#f59e0b; }
</style>

<!-- Page Header -->
<div class="page-header fade-in" style="margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:8px;">
        <i class="fa-solid fa-gauge-high" style="font-size:22px;color:var(--blue);"></i>
        <div>
            <h1 class="page-title" style="margin:0;">Dashboard</h1>
            <div style="font-size:12px;color:var(--text2);">Admin Panel — <?= date('d F Y') ?></div>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= base_url('finance/cashbook') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-book"></i> Cashbook
        </a>
        <a href="<?= base_url('reports/income') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-chart-bar"></i> Reports
        </a>
    </div>
</div>

<!-- KPI Grid Row 1: Clients -->
<div class="kpi-grid-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-in fade-in-delay-1">
    <div class="dash-card bg-blue">
        <div class="top"><div class="icon"><i class="fa-solid fa-users"></i></div><div><div class="title">Total Clients</div><div class="value"><?= $stats['total'] ?? 0 ?></div></div></div>
        <div class="footer">All clients at present</div>
    </div>
    <div class="dash-card bg-teal">
        <div class="top"><div class="icon"><i class="fa-solid fa-user-check"></i></div><div><div class="title">Active Clients</div><div class="value"><?= $stats['active'] ?? 0 ?></div></div></div>
        <div class="footer">Currently running clients</div>
    </div>
    <div class="dash-card bg-violet">
        <div class="top"><div class="icon"><i class="fa-solid fa-user-shield"></i></div><div><div class="title">Suspended</div><div class="value"><?= $stats['suspended'] ?? 0 ?></div></div></div>
        <div class="footer">Suspended clients</div>
    </div>
    <div class="dash-card bg-dark">
        <div class="top"><div class="icon"><i class="fa-solid fa-user-plus"></i></div><div><div class="title">New This Month</div><div class="value"><?= $stats['newThisMonth'] ?? 0 ?></div></div></div>
        <div class="footer">New clients this month</div>
    </div>
</div>

<!-- KPI Grid Row 2: Billing -->
<div class="kpi-grid-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-in fade-in-delay-2">
    <div class="dash-card bg-green">
        <div class="top"><div class="icon"><i class="fa-solid fa-money-bill-wave"></i></div><div><div class="title">Today Collection</div><div class="value">৳<?= number_format($stats['todayCol'] ?? 0, 0) ?></div></div></div>
        <div class="footer">Total collected today</div>
    </div>
    <div class="dash-card bg-blue">
        <div class="top"><div class="icon"><i class="fa-solid fa-calendar-check"></i></div><div><div class="title">Month Collection</div><div class="value">৳<?= number_format($stats['monthCol'] ?? 0, 0) ?></div></div></div>
        <div class="footer">Total collected this month</div>
    </div>
    <div class="dash-card bg-orange">
        <div class="top"><div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="title">Total Due</div><div class="value">৳<?= number_format($stats['totalDue'] ?? 0, 0) ?></div></div></div>
        <div class="footer">Outstanding due amount</div>
    </div>
    <div class="dash-card bg-violet">
        <div class="top"><div class="icon"><i class="fa-solid fa-file-invoice"></i></div><div><div class="title">Unpaid Invoices</div><div class="value"><?= $stats['unpaidClients'] ?? 0 ?></div></div></div>
        <div class="footer">Clients with unpaid bills</div>
    </div>
</div>

<!-- KPI Grid Row 3: Network -->
<div class="kpi-grid-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-in fade-in-delay-3">
    <div class="dash-card bg-teal">
        <div class="top"><div class="icon"><i class="fa-solid fa-chart-simple"></i></div><div><div class="title">Online Clients</div><div class="value" id="liveSessionCount"><?= $stats['active'] ?? 0 ?></div></div></div>
        <div class="footer">MikroTik live sessions</div>
    </div>
    <div class="dash-card bg-indigo">
        <div class="top"><div class="icon"><i class="fa-solid fa-satellite-dish"></i></div><div><div class="title">RADIUS Online</div><div class="value" id="radiusSessionCount"><?= $stats['active'] ?? 0 ?></div></div></div>
        <div class="footer">MikroTik RADIUS sessions</div>
    </div>
    <div class="dash-card bg-blue">
        <div class="top"><div class="icon"><i class="fa-solid fa-users-viewfinder"></i></div><div><div class="title">Total RADIUS Users</div><div class="value"><?= $stats['totalRadiusUsers'] ?? 0 ?></div></div></div>
        <div class="footer">All PPPoE users in ERP</div>
    </div>
    <div class="dash-card bg-orange">
        <div class="top"><div class="icon"><i class="fa-solid fa-layer-group"></i></div><div><div class="title">Active Profiles</div><div class="value"><?= $stats['radiusProfiles'] ?? 0 ?></div></div></div>
        <div class="footer">Speed profiles configured</div>
    </div>
</div>

<!-- KPI Grid Row 4: GPON & Work Orders -->
<div class="kpi-grid-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-in fade-in-delay-3">
    <div class="dash-card bg-blue">
        <div class="top"><div class="icon"><i class="fa-solid fa-tower-broadcast"></i></div><div><div class="title">ONUs Online</div><div class="value"><?= $onuStats['online'] ?? 0 ?></div></div></div>
        <div class="footer">Online ONU devices</div>
    </div>
    <div class="dash-card bg-violet">
        <div class="top"><div class="icon"><i class="fa-solid fa-tower-cell"></i></div><div><div class="title">Total ONUs</div><div class="value"><?= $onuStats['total'] ?? 0 ?></div></div></div>
        <div class="footer">Registered ONUs in system</div>
    </div>
    <div class="dash-card bg-red">
        <div class="top"><div class="icon"><i class="fa-solid fa-clipboard-list"></i></div><div><div class="title">Pending Work Orders</div><div class="value"><?= $stats['pendingWO'] ?? 0 ?></div></div></div>
        <div class="footer">Open work orders</div>
    </div>
    <div class="dash-card bg-green">
        <div class="top"><div class="icon"><i class="fa-solid fa-clipboard-check"></i></div><div><div class="title">Completed This Month</div><div class="value"><?= $stats['completedWO'] ?? 0 ?></div></div></div>
        <div class="footer">Work orders completed</div>
    </div>
</div>

<!-- Real-time Activity Feed -->
<div class="card" style="margin-bottom:24px; display:none;" id="activityFeedCard">
    <div style="padding:12px 16px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:8px;">
            <div style="width:32px; height:32px; border-radius:6px; background:linear-gradient(135deg,var(--blue),#3b82f6); display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid fa-bolt" style="color:#fff; font-size:14px;"></i>
            </div>
            <div>
                <div style="font-weight:700; font-size:14px;">Real-time System Activity</div>
                <div style="font-size:11px; color:var(--text2);">Live events from across the system</div>
            </div>
        </div>
        <div style="font-size:10px; color:var(--green); font-weight:700; display:flex; align-items:center; gap:4px;">
            <span class="status-dot dot-online" style="width:8px; height:8px;"></span> LIVE
        </div>
    </div>
    <div id="activityFeedList" style="max-height:320px; overflow-y:auto; padding:8px 0;">
        <div id="noActivityPlaceholder" style="padding:40px; text-align:center; color:var(--text2); font-size:13px;">
            <i class="fa-solid fa-satellite-dish" style="font-size:24px; margin-bottom:8px; display:block; opacity:0.5;"></i>
            Waiting for live events...
        </div>
    </div>
</div>
<div class="device-grid fade-in fade-in-delay-4">

    <!-- MikroTik / NAS Servers -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:6px;background:#1e3a8a;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-server" style="color:#fff;font-size:14px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">MikroTik Servers</div>
                    <div style="font-size:11px;color:var(--text2);"><?= count($nasDevices) ?> configured</div>
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <?php
                $nasOnline  = count(array_filter($nasDevices, fn($n) => $n['connection_status'] == 1));
                $nasOffline = count($nasDevices) - $nasOnline;
                ?>
                <span class="badge badge-green"><?= $nasOnline ?> Online</span>
                <?php if ($nasOffline > 0): ?>
                <span class="badge badge-red"><?= $nasOffline ?> Offline</span>
                <?php endif; ?>
                <a href="<?= base_url('network/nas') ?>" class="btn btn-ghost btn-sm" style="font-size:11px;">View All</a>
            </div>
        </div>
        <table class="device-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Server Name</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Last Checked</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($nasDevices)): ?>
                <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text2);">
                    No MikroTik servers configured. <a href="<?= base_url('network/nas') ?>" style="color:var(--blue);">Add one</a>
                </td></tr>
                <?php else: foreach ($nasDevices as $nas):
                    $isOnline = $nas['connection_status'] == 1;
                    $dotClass = $isOnline ? 'dot-online' : ($nas['connection_status'] === null ? 'dot-unknown' : 'dot-offline');
                    $statusLabel = $isOnline ? 'Online' : ($nas['connection_status'] === null ? 'Unknown' : 'Offline');
                    $badgeClass  = $isOnline ? 'badge-green' : ($nas['connection_status'] === null ? 'badge-yellow' : 'badge-red');
                    $lastChecked = $nas['last_checked'] ? date('d M H:i', strtotime($nas['last_checked'])) : 'Never';
                ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($nas['name']) ?></td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($nas['ip_address']) ?></td>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <span class="status-dot <?= $dotClass ?>"></span><?= $statusLabel ?>
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--text2);"><?= $lastChecked ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- OLT Devices -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:6px;background:linear-gradient(135deg,#0ea5e9,#0284c7);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-tower-broadcast" style="color:#fff;font-size:14px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">OLT Devices</div>
                    <div style="font-size:11px;color:var(--text2);"><?= count($oltDevices) ?> configured · <?= $onuStats['total'] ?? 0 ?> ONUs total</div>
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <?php
                $oltOnline  = count(array_filter($oltDevices, fn($o) => $o['connection_status'] === 'online'));
                $oltOffline = count($oltDevices) - $oltOnline;
                ?>
                <span class="badge badge-green"><?= $oltOnline ?> Online</span>
                <?php if ($oltOffline > 0): ?>
                <span class="badge badge-red"><?= $oltOffline ?> Offline</span>
                <?php endif; ?>
                <a href="<?= base_url('gpon/olts') ?>" class="btn btn-ghost btn-sm" style="font-size:11px;">View All</a>
            </div>
        </div>
        <table class="device-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th>OLT Name</th>
                    <th>IP Address</th>
                    <th>ONUs</th>
                    <th>Status</th>
                    <th>Last Checked</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($oltDevices)): ?>
                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text2);">
                    No OLT devices configured. <a href="<?= base_url('gpon/olts') ?>" style="color:var(--blue);">Add one</a>
                </td></tr>
                <?php else: foreach ($oltDevices as $olt):
                    $isOnline    = $olt['connection_status'] === 'online';
                    $isUnknown   = !in_array($olt['connection_status'], ['online','offline']);
                    $dotClass    = $isOnline ? 'dot-online' : ($isUnknown ? 'dot-unknown' : 'dot-offline');
                    $statusLabel = $isOnline ? 'Online' : ($isUnknown ? 'Unknown' : 'Offline');
                    $badgeClass  = $isOnline ? 'badge-green' : ($isUnknown ? 'badge-yellow' : 'badge-red');
                    $lastChecked = $olt['last_checked_at'] ? date('d M H:i', strtotime($olt['last_checked_at'])) : 'Never';
                    $onuCount    = $olt['onu_total'];
                    $onuOnline   = $olt['onu_online'];
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($olt['name']) ?></div>
                        <div style="font-size:10px;color:var(--text2);"><?= htmlspecialchars($olt['model'] ?? 'EPON OLT') ?></div>
                    </td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($olt['ip_address'] ?? '—') ?></td>
                    <td>
                        <span style="font-weight:700;color:var(--green);"><?= $onuOnline ?></span>
                        <span style="color:var(--text2);font-size:11px;">/ <?= $onuCount ?></span>
                    </td>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <span class="status-dot <?= $dotClass ?>"></span><?= $statusLabel ?>
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--text2);"><?= $lastChecked ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <!-- ONU summary bar -->
        <?php if (!empty($oltDevices) && ($onuStats['total'] ?? 0) > 0): ?>
        <div style="padding:10px 16px;border-top:1px solid var(--border);display:flex;gap:16px;font-size:12px;">
            <span style="color:var(--green);font-weight:600;"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i><?= $onuStats['online'] ?? 0 ?> Online</span>
            <span style="color:var(--red);font-weight:600;"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;"></i><?= $onuStats['offline'] ?? 0 ?> Offline</span>
            <span style="color:var(--text2);">Total: <?= $onuStats['total'] ?? 0 ?> ONUs</span>
            <a href="<?= base_url('gpon/olts/onus') ?>" style="margin-left:auto;color:var(--blue);text-decoration:none;font-size:11px;">View ONU List →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function fetchLiveStats() {
    try {
        const r = await fetch('<?= base_url('api/dashboard/live-stats') ?>');
        const data = await r.json();
        if (data.success) {
            if (data.total_sessions !== undefined) document.getElementById('liveSessionCount').textContent = data.total_sessions.toLocaleString();
            if (data.radius_sessions !== undefined) document.getElementById('radiusSessionCount').textContent = data.radius_sessions.toLocaleString();
        }
    } catch(e) {}
}

// Supabase Real-time Activity Feed Logic
if (typeof supabase !== 'undefined' && supabase !== null) {
    document.getElementById('activityFeedCard').style.display = 'block';
    
    const activityFeedList = document.getElementById('activityFeedList');
    const placeholder = document.getElementById('noActivityPlaceholder');

    function addActivityItem(icon, color, title, time, meta) {
        if (placeholder) placeholder.remove();
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.style = 'display:flex; gap:12px; padding:12px 16px; border-bottom:1px solid rgba(0,0,0,0.03); transition:all 0.3s ease; animation: slideIn 0.4s ease-out;';
        item.innerHTML = `
            <div style="width:36px; height:36px; border-radius:10px; background:${color}15; color:${color}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fa-solid ${icon}"></i>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="display:flex; justify-content:space-between; align-items:start; gap:8px;">
                    <div style="font-size:13.5px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${title}</div>
                    <div style="font-size:10px; color:var(--text2); white-space:nowrap;">${time}</div>
                </div>
                <div style="font-size:12px; color:var(--text2); margin-top:2px;">${meta}</div>
            </div>
        `;
        activityFeedList.prepend(item);
        if (activityFeedList.children.length > 20) activityFeedList.lastElementChild.remove();
    }

    // Subscribe to multiple tables
    const tables = [
        { name: 'support_tickets', icon: 'fa-ticket', color: '#dc2626', label: 'Support Ticket' },
        { name: 'payments', icon: 'fa-money-bill-trend-up', color: '#16a34a', label: 'Payment' },
        { name: 'customers', icon: 'fa-user-plus', color: '#2563eb', label: 'New Customer' },
        { name: 'activity_logs', icon: 'fa-clock-rotate-left', color: '#7c3aed', label: 'Staff Action' }
    ];

    tables.forEach(table => {
        supabase
            .channel(`dashboard:${table.name}`)
            .on('postgres_changes', { event: 'INSERT', schema: 'public', table: table.name }, payload => {
                const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                let title = table.label;
                let meta = 'A new record was added.';

                if (table.name === 'support_tickets') {
                    title = `Ticket #${payload.new.id}: ${payload.new.subject}`;
                    meta = `Priority: ${payload.new.priority} | Customer ID: ${payload.new.customer_id}`;
                } else if (table.name === 'payments') {
                    title = `Payment: ${payload.new.amount} BDT`;
                    meta = `Receipt: ${payload.new.receipt_number} | Method: ${payload.new.payment_method}`;
                } else if (table.name === 'customers') {
                    title = `Customer Joined: ${payload.new.full_name}`;
                    meta = `Code: ${payload.new.customer_code} | Zone: ${payload.new.sub_zone || 'N/A'}`;
                } else if (table.name === 'activity_logs') {
                    title = `Action: ${payload.new.action}`;
                    meta = `Module: ${payload.new.module} | IP: ${payload.new.ip_address}`;
                }

                addActivityItem(table.icon, table.color, title, now, meta);
            })
            .subscribe();
    });
}

fetchLiveStats();
setInterval(fetchLiveStats, 30000);
</script>
