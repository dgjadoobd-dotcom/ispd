<?php // views/ott/dashboard.php
// Variables: $stats (array), $expiringSoon (array), $recentLogs (array)
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">OTT Subscriptions</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i> OTT Management &rsaquo; Dashboard
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
        <a href="<?= base_url('ott/subscriptions/create') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add Subscription
        </a>
        <form method="POST" action="<?= base_url('ott/process-renewals') ?>" style="display:inline;"
              onsubmit="return confirm('Process all pending auto-renewals now?')">
            <button type="submit" class="btn btn-ghost">
                <i class="fa-solid fa-rotate"></i> Process Renewals
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.08);">
    <span style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></span>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px;" class="fade-in">
    <div class="card stat-card">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div class="stat-label">Total</div>
                <div class="stat-value"><?= number_format($stats['total']) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(37,99,235,.12);color:var(--blue);">
                <i class="fa-solid fa-tv"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div class="stat-label">Active</div>
                <div class="stat-value" style="color:var(--green);"><?= number_format($stats['active_count']) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(22,163,74,.12);color:var(--green);">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div class="stat-label">Expired</div>
                <div class="stat-value" style="color:var(--red);"><?= number_format($stats['expired_count']) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(220,38,38,.12);color:var(--red);">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div class="stat-label">Cancelled</div>
                <div class="stat-value" style="color:var(--text2);"><?= number_format($stats['cancelled_count']) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(100,116,139,.12);color:var(--text2);">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div class="stat-label">Renewing Soon</div>
                <div class="stat-value" style="color:var(--yellow);"><?= number_format($stats['pending_renewal_count']) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(217,119,6,.12);color:var(--yellow);">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Nav -->
<div style="display:flex;gap:10px;margin-bottom:20px;" class="fade-in">
    <a href="<?= base_url('ott/subscriptions') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-list"></i> All Subscriptions
    </a>
    <a href="<?= base_url('ott/subscriptions?status=active') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-circle-check" style="color:var(--green)"></i> Active
    </a>
    <a href="<?= base_url('ott/subscriptions?status=expired') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-circle-xmark" style="color:var(--red)"></i> Expired
    </a>
    <a href="<?= base_url('ott/providers') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-building"></i> Providers
    </a>
    <a href="<?= base_url('ott/packages') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-box"></i> Packages
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;" class="fade-in">

    <!-- Expiring Soon -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:14px;font-weight:700;">
                <i class="fa-solid fa-clock" style="color:var(--yellow);margin-right:6px;"></i>Expiring in 3 Days
            </h3>
            <span style="font-size:12px;color:var(--text2);"><?= count($expiringSoon) ?> subscription(s)</span>
        </div>
        <?php if (empty($expiringSoon)): ?>
        <div style="padding:28px;text-align:center;color:var(--text2);">
            <i class="fa-solid fa-circle-check" style="font-size:24px;color:var(--green);margin-bottom:8px;display:block;"></i>
            No subscriptions expiring soon.
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Expires</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expiringSoon as $sub): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($sub['customer_name']) ?></div>
                        <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($sub['customer_code']) ?></div>
                    </td>
                    <td style="font-size:12px;">
                        <?= htmlspecialchars($sub['provider_name']) ?><br>
                        <span style="color:var(--text2);"><?= htmlspecialchars($sub['ott_package_name']) ?></span>
                    </td>
                    <td>
                        <span style="color:var(--yellow);font-weight:600;font-size:12px;">
                            <?= date('d M Y', strtotime($sub['expiry_date'])) ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= base_url('ott/subscriptions/view/' . $sub['id']) ?>"
                           class="btn btn-ghost" style="padding:3px 8px;font-size:11px;">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Recent Activity Log -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:14px;font-weight:700;">
                <i class="fa-solid fa-clock-rotate-left" style="color:var(--purple);margin-right:6px;"></i>Recent Activity
            </h3>
        </div>
        <?php if (empty($recentLogs)): ?>
        <div style="padding:28px;text-align:center;color:var(--text2);">No activity yet.</div>
        <?php else: ?>
        <div style="max-height:340px;overflow-y:auto;">
            <?php
            $actionColors = [
                'renewal_success'   => 'var(--green)',
                'renewal_failed'    => 'var(--red)',
                'renewal_attempt'   => 'var(--blue)',
                'manual_activate'   => 'var(--green)',
                'manual_deactivate' => 'var(--red)',
                'sms_sent'          => 'var(--purple)',
                'sms_failed'        => 'var(--yellow)',
            ];
            $actionIcons = [
                'renewal_success'   => 'fa-rotate',
                'renewal_failed'    => 'fa-circle-xmark',
                'renewal_attempt'   => 'fa-rotate',
                'manual_activate'   => 'fa-circle-check',
                'manual_deactivate' => 'fa-ban',
                'sms_sent'          => 'fa-message',
                'sms_failed'        => 'fa-message-slash',
            ];
            foreach ($recentLogs as $log):
                $color = $actionColors[$log['action']] ?? 'var(--text2)';
                $icon  = $actionIcons[$log['action']] ?? 'fa-circle';
            ?>
            <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;">
                <div style="width:30px;height:30px;border-radius:8px;background:rgba(0,0,0,.05);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $color ?>;">
                    <i class="fa-solid <?= $icon ?>" style="font-size:13px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;font-weight:600;color:<?= $color ?>;">
                        <?= ucwords(str_replace('_', ' ', $log['action'])) ?>
                    </div>
                    <div style="font-size:11px;color:var(--text2);">
                        <?= htmlspecialchars($log['customer_name'] ?? '') ?>
                        (<?= htmlspecialchars($log['customer_code'] ?? '') ?>)
                    </div>
                </div>
                <div style="font-size:11px;color:var(--text2);white-space:nowrap;">
                    <?= date('d M H:i', strtotime($log['performed_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
