<?php // views/automation/index.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Billing Automation</h1>
        <div class="page-breadcrumb">Automated billing jobs & scheduling</div>
    </div>
    <a href="<?= base_url('automation/logs') ?>" class="btn btn-ghost">
        <i class="fa-solid fa-list-ul"></i> View Logs
    </a>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;" class="fade-in">
    <?php foreach ([
        ['Active Customers',   $stats['active'],          'fa-users',              'var(--green)'],
        ['Suspended',          $stats['suspended'],        'fa-ban',                'var(--red)'],
        ['Overdue Customers',  $stats['overdue'],          'fa-triangle-exclamation','var(--yellow)'],
        ['Due Today',          $stats['due_today'],        'fa-calendar-day',       'var(--blue)'],
        ['Unpaid Invoices',    $stats['unpaid_invoices'],  'fa-file-invoice',       'var(--purple)'],
        ['Total Due (৳)',      number_format($stats['total_due_amount'],0), 'fa-coins', 'var(--red)'],
    ] as [$label,$val,$icon,$color]): ?>
    <div class="card stat-card">
        <div class="stat-icon" style="background:<?= $color ?>1a;color:<?= $color ?>;"><i class="fa-solid <?= $icon ?>"></i></div>
        <div class="stat-value" style="font-size:22px;"><?= $val ?></div>
        <div class="stat-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="fade-in">

    <!-- Jobs -->
    <div style="display:flex;flex-direction:column;gap:14px;">

        <!-- Invoice Generation -->
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(37,99,235,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-file-invoice-dollar" style="color:var(--blue);font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">Monthly Invoice Generation</div>
                    <div style="font-size:12px;color:var(--text2);">Generate invoices for all active customers</div>
                </div>
            </div>
            <form method="POST" action="<?= base_url('automation/run/invoices') ?>">
                <div style="display:flex;gap:10px;align-items:center;">
                    <input type="month" name="billing_month_display" class="form-input" style="max-width:180px;"
                           value="<?= date('Y-m') ?>" onchange="this.form.querySelector('[name=billing_month]').value=this.value+'-01'">
                    <input type="hidden" name="billing_month" value="<?= date('Y-m-01') ?>">
                    <button type="submit" class="btn btn-primary btn-sm"
                            onclick="return confirm('Generate invoices for <?= date('F Y') ?>?')">
                        <i class="fa-solid fa-play"></i> Run
                    </button>
                </div>
            </form>
        </div>

        <!-- Auto Suspension -->
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(220,38,38,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-ban" style="color:var(--red);font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">Auto Suspend Overdue</div>
                    <div style="font-size:12px;color:var(--text2);">Suspend customers with overdue unpaid invoices</div>
                </div>
            </div>
            <form method="POST" action="<?= base_url('automation/run/suspend') ?>">
                <div style="display:flex;gap:10px;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);">
                        Grace period:
                        <input type="number" name="grace_days" class="form-input" style="width:70px;" value="0" min="0" max="30">
                        days
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Suspend all overdue customers?')">
                        <i class="fa-solid fa-play"></i> Run
                    </button>
                </div>
            </form>
        </div>

        <!-- Auto Reconnect -->
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(22,163,74,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-rotate" style="color:var(--green);font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">Auto Reconnect on Payment</div>
                    <div style="font-size:12px;color:var(--text2);">Reconnect suspended customers with zero balance</div>
                </div>
            </div>
            <form method="POST" action="<?= base_url('automation/run/reconnect') ?>">
                <button type="submit" class="btn btn-success btn-sm"
                        onclick="return confirm('Reconnect all fully-paid suspended customers?')">
                    <i class="fa-solid fa-play"></i> Run Now
                </button>
            </form>
        </div>

        <!-- Manual Bulk Unlock -->
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-unlock" style="color:var(--blue);font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">Manual Bulk Unlock</div>
                    <div style="font-size:12px;color:var(--text2);">Reconnect ALL suspended customers (no payment check)</div>
                </div>
            </div>
            <form method="POST" action="<?= base_url('automation/run/unlock-all') ?>">
                <button type="submit" class="btn btn-primary btn-sm"
                        onclick="return confirm('UNLOCK ALL suspended customers?\n\nThis will reconnect everyone including those with unpaid invoices.\n\nAre you sure?')">
                    <i class="fa-solid fa-unlock"></i> Unlock All (<?= $stats['suspended'] ?>)
                </button>
            </form>
        </div>

        <!-- Due Reminders -->
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(217,119,6,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-bell" style="color:var(--yellow);font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">Due Reminder SMS</div>
                    <div style="font-size:12px;color:var(--text2);">Send SMS to customers with upcoming due invoices</div>
                </div>
            </div>
            <form method="POST" action="<?= base_url('automation/run/due-reminders') ?>">
                <div style="display:flex;gap:10px;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);">
                        Due within:
                        <input type="number" name="days_ahead" class="form-input" style="width:70px;" value="3" min="1" max="30">
                        days
                    </div>
                    <button type="submit" class="btn btn-sm" style="background:var(--yellow);color:#fff;">
                        <i class="fa-solid fa-play"></i> Send
                    </button>
                </div>
            </form>
        </div>

        <!-- Expiry Reminders -->
        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(124,58,237,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-clock" style="color:var(--purple);font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">Expiry Reminder SMS</div>
                    <div style="font-size:12px;color:var(--text2);">Notify customers whose service is about to expire</div>
                </div>
            </div>
            <form method="POST" action="<?= base_url('automation/run/expiry-reminders') ?>">
                <div style="display:flex;gap:10px;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);">
                        Expiring within:
                        <input type="number" name="days_ahead" class="form-input" style="width:70px;" value="5" min="1" max="30">
                        days
                    </div>
                    <button type="submit" class="btn btn-sm" style="background:var(--purple);color:#fff;">
                        <i class="fa-solid fa-play"></i> Send
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Settings + Recent Logs -->
    <div style="display:flex;flex-direction:column;gap:14px;">

        <!-- Cron Setup -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
                <i class="fa-solid fa-terminal" style="margin-right:6px;"></i>Cron Setup
            </div>
            <div style="font-size:12px;color:var(--text2);margin-bottom:10px;">Add these to your server crontab:</div>
            <?php foreach ([
                ['Daily at midnight — invoices + reconnect', '0 0 * * * php ' . BASE_PATH . '/cron_automation.php'],
                ['Daily at 8am — due reminders',             '0 8 * * * php ' . BASE_PATH . '/cron_automation.php due-reminders'],
                ['Every 6h — auto suspend',                  '0 */6 * * * php ' . BASE_PATH . '/cron_automation.php suspend'],
            ] as [$label, $cmd]): ?>
            <div style="margin-bottom:10px;">
                <div style="font-size:11px;color:var(--text2);margin-bottom:4px;"><?= $label ?></div>
                <code style="display:block;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:11px;word-break:break-all;cursor:copy;"
                      onclick="navigator.clipboard.writeText(this.textContent);this.style.borderColor='var(--green)'"
                      title="Click to copy"><?= htmlspecialchars($cmd) ?></code>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Logs -->
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:14px;font-weight:700;">Recent Runs</div>
                <a href="<?= base_url('automation/logs') ?>" style="font-size:12px;color:var(--blue);text-decoration:none;">View all</a>
            </div>
            <?php if (empty($logs)): ?>
            <div style="padding:24px;text-align:center;color:var(--text2);font-size:13px;">No automation runs yet.</div>
            <?php else: foreach ($logs as $log): ?>
            <div style="padding:10px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:10px;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;font-weight:600;"><?= htmlspecialchars(ucwords(str_replace('_',' ',$log['job_type']))) ?></div>
                    <div style="font-size:11px;color:var(--text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($log['message']) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <span class="badge <?= $log['status']==='success'?'badge-green':($log['status']==='error'?'badge-red':'badge-gray') ?>" style="font-size:10px;"><?= $log['affected'] ?> affected</span>
                    <span style="font-size:10px;color:var(--text2);"><?= date('d M H:i', strtotime($log['run_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
