<?php // views/superadmin/dashboard/index.php ?>
<style>
.sa-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.sa-kpi { background: var(--bg2); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; transition: box-shadow 0.2s; }
.sa-kpi:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.sa-kpi-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.sa-kpi-val { font-size: 26px; font-weight: 800; color: var(--text); line-height: 1; }
.sa-kpi-lbl { font-size: 12px; color: var(--text2); font-weight: 500; margin-top: 3px; }
.sa-section-grid { display: grid; grid-template-columns: 1fr 380px; gap: 20px; margin-bottom: 24px; }
.sa-alert-row { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid var(--border); font-size: 13px; }
.sa-alert-row:last-child { border-bottom: none; }
@media (max-width: 1100px) { .sa-kpi-grid { grid-template-columns: repeat(2, 1fr); } .sa-section-grid { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .sa-kpi-grid { grid-template-columns: 1fr; } }
</style>

<!-- Page Header -->
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Owner Dashboard</h1>
        <div class="page-breadcrumb">Platform-wide overview · <?= date('d F Y, H:i') ?></div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-ghost btn-sm" onclick="refreshStats()">
            <i class="fa-solid fa-rotate"></i> Refresh
        </button>
        <a href="<?= base_url('superadmin/noc') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-tower-broadcast"></i> NOC
        </a>
    </div>
</div>

<!-- Pending Alerts Bar -->
<?php if ($pendingSignups > 0 || $pendingTickets > 0 || $pendingPayments > 0): ?>
<div style="background:linear-gradient(135deg,rgba(124,58,237,0.08),rgba(37,99,235,0.06));border:1px solid rgba(124,58,237,0.2);border-radius:10px;padding:12px 18px;margin-bottom:20px;display:flex;gap:20px;flex-wrap:wrap;align-items:center;" class="fade-in">
    <span style="font-size:12px;font-weight:700;color:var(--purple);"><i class="fa-solid fa-bell" style="margin-right:6px;"></i>PENDING ACTIONS</span>
    <?php if ($pendingSignups > 0): ?>
    <a href="<?= base_url('customers?status=pending') ?>" style="font-size:13px;color:var(--text);text-decoration:none;display:flex;align-items:center;gap:6px;">
        <span class="badge badge-yellow"><?= $pendingSignups ?></span> New Signups
    </a>
    <?php endif; ?>
    <?php if ($pendingTickets > 0): ?>
    <a href="<?= base_url('support') ?>" style="font-size:13px;color:var(--text);text-decoration:none;display:flex;align-items:center;gap:6px;">
        <span class="badge badge-red"><?= $pendingTickets ?></span> Open Tickets
    </a>
    <?php endif; ?>
    <?php if ($pendingPayments > 0): ?>
    <a href="<?= base_url('billing') ?>" style="font-size:13px;color:var(--text);text-decoration:none;display:flex;align-items:center;gap:6px;">
        <span class="badge badge-blue"><?= $pendingPayments ?></span> Unpaid Invoices
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- KPI Row 1: Customers & Revenue -->
<div class="sa-kpi-grid fade-in">
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(37,99,235,0.1);color:#2563eb;"><i class="fa-solid fa-users"></i></div>
        <div>
            <div class="sa-kpi-val" id="kpi-total-customers"><?= number_format($stats['totalCustomers']) ?></div>
            <div class="sa-kpi-lbl">Total Customers</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(22,163,74,0.1);color:#16a34a;"><i class="fa-solid fa-user-check"></i></div>
        <div>
            <div class="sa-kpi-val" id="kpi-active-customers"><?= number_format($stats['activeCustomers']) ?></div>
            <div class="sa-kpi-lbl">Active Customers</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(124,58,237,0.1);color:#7c3aed;"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div>
            <div class="sa-kpi-val" id="kpi-month-revenue">৳<?= number_format($stats['monthRevenue'], 0) ?></div>
            <div class="sa-kpi-lbl">This Month Revenue</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(220,38,38,0.1);color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <div class="sa-kpi-val" id="kpi-total-due">৳<?= number_format($stats['totalDue'], 0) ?></div>
            <div class="sa-kpi-lbl">Total Outstanding Due</div>
        </div>
    </div>
</div>

<!-- KPI Row 2: Platform -->
<div class="sa-kpi-grid fade-in">
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(217,119,6,0.1);color:#d97706;"><i class="fa-solid fa-code-branch"></i></div>
        <div>
            <div class="sa-kpi-val"><?= $stats['totalBranches'] ?></div>
            <div class="sa-kpi-lbl">Active Branches</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(37,99,235,0.1);color:#2563eb;"><i class="fa-solid fa-users-gear"></i></div>
        <div>
            <div class="sa-kpi-val"><?= $stats['totalUsers'] ?></div>
            <div class="sa-kpi-lbl">System Users</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(22,163,74,0.1);color:#16a34a;"><i class="fa-solid fa-server"></i></div>
        <div>
            <div class="sa-kpi-val" id="kpi-online-nas"><?= $stats['onlineNas'] ?> / <?= $stats['totalNas'] ?></div>
            <div class="sa-kpi-lbl">NAS Devices Online</div>
        </div>
    </div>
    <div class="sa-kpi">
        <div class="sa-kpi-icon" style="background:rgba(124,58,237,0.1);color:#7c3aed;"><i class="fa-solid fa-headset"></i></div>
        <div>
            <div class="sa-kpi-val" id="kpi-open-tickets"><?= $stats['openTickets'] ?></div>
            <div class="sa-kpi-lbl">Open Support Tickets</div>
        </div>
    </div>
</div>

<!-- Revenue Chart + System Health -->
<div class="sa-section-grid fade-in">
    <!-- Revenue Chart -->
    <div class="card" style="padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div>
                <div style="font-size:15px;font-weight:700;">Revenue Trend</div>
                <div style="font-size:12px;color:var(--text2);">Last 12 months</div>
            </div>
            <div style="font-size:20px;font-weight:800;color:var(--purple);">৳<?= number_format($stats['totalRevenue'], 0) ?></div>
        </div>
        <canvas id="revenueChart" height="200"></canvas>
    </div>

    <!-- System Health -->
    <div class="card" style="padding:20px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-heart-pulse" style="color:var(--green);"></i> System Health
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div>
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
                    <span style="color:var(--text2);">Disk Usage</span>
                    <span style="font-weight:700;color:<?= $systemHealth['disk_pct'] > 80 ? 'var(--red)' : 'var(--text)' ?>;"><?= $systemHealth['disk_pct'] ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $systemHealth['disk_pct'] ?>%;background:<?= $systemHealth['disk_pct'] > 80 ? '#dc2626' : '#7c3aed' ?>;"></div>
                </div>
                <div style="font-size:11px;color:var(--text2);margin-top:3px;"><?= $systemHealth['disk_free_gb'] ?> GB free of <?= $systemHealth['disk_total_gb'] ?> GB</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <?php $items = [
                    ['PHP Version', $systemHealth['php_version'], 'fa-php'],
                    ['Memory Limit', $systemHealth['memory_limit'], 'fa-memory'],
                    ['Log Size', $systemHealth['log_size_mb'].' MB', 'fa-file-lines'],
                    ['Uptime', $systemHealth['uptime'] ?? 'N/A', 'fa-clock'],
                ]; foreach ($items as [$lbl, $val, $ico]): ?>
                <div style="background:var(--bg3);border-radius:8px;padding:10px 12px;">
                    <div style="font-size:11px;color:var(--text2);margin-bottom:3px;"><i class="fa-brands <?= $ico ?>" style="margin-right:4px;"></i><?= $lbl ?></div>
                    <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($val) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:rgba(22,163,74,0.08);border-radius:8px;border:1px solid rgba(22,163,74,0.2);">
                <i class="fa-solid fa-circle-check" style="color:var(--green);"></i>
                <span style="font-size:13px;font-weight:600;color:var(--green);">Database connection OK</span>
            </div>
        </div>
    </div>
</div>

<!-- Top Branches + Recent Activity -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="fade-in">
    <!-- Top Branches -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:700;">Top Branches</div>
            <a href="<?= base_url('superadmin/branches') ?>" class="btn btn-ghost btn-xs">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Branch</th><th>Customers</th><th>Revenue (30d)</th></tr></thead>
            <tbody>
                <?php if (empty($topBranches)): ?>
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--text2);">No branch data</td></tr>
                <?php else: foreach ($topBranches as $b): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($b['name']) ?></div>
                        <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($b['code'] ?? '') ?></div>
                    </td>
                    <td><?= number_format($b['customers']) ?></td>
                    <td style="font-weight:700;color:var(--purple);">৳<?= number_format($b['revenue'], 0) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Activity -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:700;">Recent Activity</div>
            <a href="<?= base_url('superadmin/logs') ?>" class="btn btn-ghost btn-xs">View All</a>
        </div>
        <div style="max-height:320px;overflow-y:auto;">
            <?php if (empty($recentLogs)): ?>
            <div style="text-align:center;padding:24px;color:var(--text2);">No activity yet</div>
            <?php else: foreach ($recentLogs as $log): ?>
            <div class="sa-alert-row">
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(124,58,237,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-bolt" style="font-size:12px;color:var(--purple);"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($log['description'] ?? $log['action']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($log['username'] ?? 'System') ?> · <?= date('d M H:i', strtotime($log['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
// Revenue Chart
(function() {
    const data = <?= json_encode($revenueChart) ?>;
    const labels = data.map(r => r.month);
    const values = data.map(r => parseFloat(r.revenue));
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue',
                data: values,
                backgroundColor: 'rgba(124,58,237,0.7)',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => '৳'+v.toLocaleString() } },
                x: { grid: { display: false } }
            }
        }
    });
})();

// Live stats refresh
async function refreshStats() {
    try {
        const r = await fetch('<?= base_url('superadmin/api/live-stats') ?>');
        const d = await r.json();
        if (!d.success) return;
        const s = d.stats;
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('kpi-total-customers', s.totalCustomers?.toLocaleString());
        set('kpi-active-customers', s.activeCustomers?.toLocaleString());
        set('kpi-month-revenue', '৳' + (s.monthRevenue || 0).toLocaleString());
        set('kpi-total-due', '৳' + (s.totalDue || 0).toLocaleString());
        set('kpi-online-nas', s.onlineNas + ' / ' + s.totalNas);
        set('kpi-open-tickets', s.openTickets);
        showToast('Stats refreshed', 'success');
    } catch(e) { showToast('Refresh failed', 'error'); }
}
</script>
