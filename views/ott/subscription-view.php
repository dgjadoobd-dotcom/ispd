<?php // views/ott/subscription-view.php
// Variables: $subscription (array), $logs (array)
$sub = $subscription;
$statusColors = [
    'active'    => 'badge-green',
    'expired'   => 'badge-red',
    'cancelled' => 'badge-gray',
    'suspended' => 'badge-yellow',
];
$badgeClass = $statusColors[$sub['status']] ?? 'badge-gray';
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">OTT Subscription #<?= $sub['id'] ?></h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i>
            <a href="<?= base_url('ott') ?>" style="color:var(--blue);">OTT</a> &rsaquo;
            <a href="<?= base_url('ott/subscriptions') ?>" style="color:var(--blue);">Subscriptions</a> &rsaquo;
            #<?= $sub['id'] ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= base_url('ott/subscriptions') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
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

<div style="display:grid;grid-template-columns:1fr 380px;gap:18px;" class="fade-in">

    <!-- Main Details -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Subscription Info -->
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h3 style="margin:0;font-size:15px;font-weight:700;">Subscription Details</h3>
                <span class="badge <?= $badgeClass ?>"><?= ucfirst($sub['status']) ?></span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Customer</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($sub['customer_name']) ?></div>
                    <div style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($sub['customer_code']) ?></div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Phone</div>
                    <div><?= htmlspecialchars($sub['customer_phone'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Provider</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($sub['provider_name']) ?></div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">OTT Package</div>
                    <div><?= htmlspecialchars($sub['ott_package_name']) ?></div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Start Date</div>
                    <div><?= date('d M Y', strtotime($sub['start_date'])) ?></div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Expiry Date</div>
                    <div style="font-weight:600;<?= $sub['status'] === 'active' && strtotime($sub['expiry_date']) <= strtotime('+3 days') ? 'color:var(--yellow);' : '' ?>">
                        <?= date('d M Y', strtotime($sub['expiry_date'])) ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Auto-Renewal</div>
                    <div>
                        <?php if ($sub['auto_renewal']): ?>
                        <span class="badge badge-blue" style="font-size:11px;"><i class="fa-solid fa-rotate"></i> Enabled</span>
                        <?php else: ?>
                        <span class="badge badge-gray" style="font-size:11px;">Disabled</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Last Renewed</div>
                    <div style="font-size:13px;">
                        <?= $sub['last_renewed_at'] ? date('d M Y H:i', strtotime($sub['last_renewed_at'])) : '—' ?>
                    </div>
                </div>
                <?php if ($sub['activated_by_name']): ?>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Activated By</div>
                    <div style="font-size:13px;"><?= htmlspecialchars($sub['activated_by_name']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($sub['deactivated_at']): ?>
                <div>
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Deactivated At</div>
                    <div style="font-size:13px;color:var(--red);">
                        <?= date('d M Y H:i', strtotime($sub['deactivated_at'])) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($sub['deactivation_reason']): ?>
                <div style="grid-column:1/-1;">
                    <div style="font-size:12px;color:var(--text2);margin-bottom:3px;">Deactivation Reason</div>
                    <div style="font-size:13px;color:var(--red);"><?= htmlspecialchars($sub['deactivation_reason']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Renewal Log -->
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <h3 style="margin:0;font-size:14px;font-weight:700;">
                    <i class="fa-solid fa-clock-rotate-left" style="color:var(--purple);margin-right:6px;"></i>Activity Log
                </h3>
            </div>
            <?php if (empty($logs)): ?>
            <div style="padding:24px;text-align:center;color:var(--text2);">No activity recorded yet.</div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Old Status</th>
                        <th>New Status</th>
                        <th>Old Expiry</th>
                        <th>New Expiry</th>
                        <th>By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
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
                    foreach ($logs as $log):
                        $color = $actionColors[$log['action']] ?? 'var(--text2)';
                    ?>
                    <tr>
                        <td>
                            <span style="color:<?= $color ?>;font-size:12px;font-weight:600;">
                                <?= ucwords(str_replace('_', ' ', $log['action'])) ?>
                            </span>
                            <?php if ($log['error_message']): ?>
                            <div style="font-size:11px;color:var(--red);margin-top:2px;">
                                <?= htmlspecialchars(substr($log['error_message'], 0, 80)) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:var(--text2);"><?= $log['old_status'] ?? '—' ?></td>
                        <td style="font-size:12px;"><?= $log['new_status'] ?? '—' ?></td>
                        <td style="font-size:12px;color:var(--text2);">
                            <?= $log['old_expiry'] ? date('d M Y', strtotime($log['old_expiry'])) : '—' ?>
                        </td>
                        <td style="font-size:12px;">
                            <?= $log['new_expiry'] ? date('d M Y', strtotime($log['new_expiry'])) : '—' ?>
                        </td>
                        <td style="font-size:12px;"><?= htmlspecialchars($log['performed_by_name'] ?? 'System') ?></td>
                        <td style="font-size:12px;color:var(--text2);">
                            <?= date('d M Y H:i', strtotime($log['performed_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions Sidebar -->
    <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
    <div style="display:flex;flex-direction:column;gap:14px;">

        <!-- Activate -->
        <?php if (in_array($sub['status'], ['expired', 'cancelled', 'suspended'])): ?>
        <div class="card" style="padding:16px;">
            <h4 style="margin:0 0 10px;font-size:13px;font-weight:700;color:var(--green);">
                <i class="fa-solid fa-circle-check"></i> Activate Subscription
            </h4>
            <p style="font-size:12px;color:var(--text2);margin-bottom:12px;">
                Reactivate this subscription. A new expiry date will be calculated from today.
            </p>
            <form method="POST" action="<?= base_url('ott/subscriptions/activate/' . $sub['id']) ?>"
                  onsubmit="return confirm('Activate this subscription?')">
                <button type="submit" class="btn btn-success" style="width:100%;">
                    <i class="fa-solid fa-circle-check"></i> Activate
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Manual Renew -->
        <?php if ($sub['status'] === 'active'): ?>
        <div class="card" style="padding:16px;">
            <h4 style="margin:0 0 10px;font-size:13px;font-weight:700;color:var(--blue);">
                <i class="fa-solid fa-rotate"></i> Manual Renewal
            </h4>
            <p style="font-size:12px;color:var(--text2);margin-bottom:12px;">
                Extend the subscription by <?= (int)$sub['validity_days'] ?> days from the current expiry date.
            </p>
            <form method="POST" action="<?= base_url('ott/subscriptions/renew/' . $sub['id']) ?>"
                  onsubmit="return confirm('Renew this subscription now?')">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fa-solid fa-rotate"></i> Renew Now
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Deactivate -->
        <?php if (in_array($sub['status'], ['active', 'suspended'])): ?>
        <div class="card" style="padding:16px;">
            <h4 style="margin:0 0 10px;font-size:13px;font-weight:700;color:var(--red);">
                <i class="fa-solid fa-ban"></i> Deactivate Subscription
            </h4>
            <form method="POST" action="<?= base_url('ott/subscriptions/deactivate/' . $sub['id']) ?>"
                  onsubmit="return confirm('Deactivate this subscription?')">
                <div style="margin-bottom:10px;">
                    <label class="form-label" style="font-size:12px;">Reason (optional)</label>
                    <textarea name="reason" class="form-input" rows="2"
                              placeholder="Enter reason for deactivation..."
                              style="font-size:13px;resize:none;"></textarea>
                </div>
                <button type="submit" class="btn btn-danger" style="width:100%;">
                    <i class="fa-solid fa-ban"></i> Deactivate
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

</div>
