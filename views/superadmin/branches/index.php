<?php // views/superadmin/branches/index.php ?>

<!-- Page Header -->
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Branches Overview</h1>
        <div class="page-breadcrumb">Platform-wide branch performance</div>
    </div>
    <a href="<?= base_url('settings/branches') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-pen-to-square"></i> Manage Branches
    </a>
</div>

<!-- Summary KPIs -->
<?php
$totalCust = array_sum(array_column($branches, 'total_customers'));
$activeCust = array_sum(array_column($branches, 'active_customers'));
$totalRev = array_sum(array_column($branches, 'total_revenue'));
$monthRev = array_sum(array_column($branches, 'month_revenue'));
?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;" class="fade-in">
    <?php $kpis = [
        ['Total Branches', count($branches), 'fa-code-branch', '#7c3aed', 'rgba(124,58,237,0.1)'],
        ['Total Customers', number_format($totalCust), 'fa-users', '#2563eb', 'rgba(37,99,235,0.1)'],
        ['Active Customers', number_format($activeCust), 'fa-user-check', '#16a34a', 'rgba(22,163,74,0.1)'],
        ['Month Revenue', '৳'.number_format($monthRev, 0), 'fa-money-bill-wave', '#d97706', 'rgba(217,119,6,0.1)'],
    ]; foreach ($kpis as [$lbl, $val, $ico, $color, $bg]): ?>
    <div class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $bg ?>;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
            <i class="fa-solid <?= $ico ?>"></i>
        </div>
        <div>
            <div style="font-size:24px;font-weight:800;color:var(--text);"><?= $val ?></div>
            <div style="font-size:12px;color:var(--text2);"><?= $lbl ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Branches Table -->
<div class="card fade-in" style="overflow:hidden;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
        <div style="font-size:14px;font-weight:700;"><?= count($branches) ?> Branches</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Total Customers</th>
                    <th>Active</th>
                    <th>Staff Users</th>
                    <th>Total Revenue</th>
                    <th>This Month</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($branches)): ?>
                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text2);">No branches found</td></tr>
                <?php else: foreach ($branches as $b):
                    $pct = $b['total_customers'] > 0 ? round(($b['active_customers'] / $b['total_customers']) * 100) : 0;
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">
                                <?= strtoupper(substr($b['name'], 0, 2)) ?>
                            </div>
                            <div>
                                <div style="font-weight:700;"><?= htmlspecialchars($b['name']) ?></div>
                                <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($b['code'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight:600;"><?= number_format($b['total_customers']) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-weight:600;color:var(--green);"><?= number_format($b['active_customers']) ?></span>
                            <div style="flex:1;min-width:60px;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:<?= $pct ?>%;background:var(--green);"></div>
                                </div>
                            </div>
                            <span style="font-size:11px;color:var(--text2);"><?= $pct ?>%</span>
                        </div>
                    </td>
                    <td><?= number_format($b['total_users']) ?></td>
                    <td style="font-weight:600;">৳<?= number_format($b['total_revenue'], 0) ?></td>
                    <td style="font-weight:700;color:var(--purple);">৳<?= number_format($b['month_revenue'], 0) ?></td>
                    <td>
                        <?php if ($b['is_active']): ?>
                        <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px;"></i> Active</span>
                        <?php else: ?>
                        <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:7px;"></i> Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
