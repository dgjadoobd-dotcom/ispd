<?php
// views/customers/view.php
?>
<style>
.info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; }
.info-item .label { font-size:11px; color:var(--text2); font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px; }
.info-item .value { font-size:14px; font-weight:500; }
.timeline-item { display:flex; gap:14px; padding-bottom:16px; position:relative; }
.timeline-item::before { content:''; position:absolute; left:15px; top:32px; bottom:0; width:2px; background:var(--border); }
.timeline-item:last-child::before { display:none; }
.timeline-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; z-index:1; }
</style>

<div class="page-header fade-in">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--blue),var(--purple));display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;">
            <?= strtoupper(substr($customer['full_name'],0,1)) ?>
        </div>
        <div>
            <h1 class="page-title"><?= htmlspecialchars($customer['full_name']) ?></h1>
            <div class="page-breadcrumb">
                <a href="<?= base_url('customers') ?>" style="color:var(--blue);text-decoration:none;">Customers</a>
                <i class="fa-solid fa-chevron-right" style="font-size:9px;"></i>
                <span style="font-family:monospace;"><?= htmlspecialchars($customer['customer_code']) ?></span>
                &nbsp;
                <?php
                $sc=['active'=>'badge-green','suspended'=>'badge-red','pending'=>'badge-yellow','cancelled'=>'badge-gray','deleted'=>'badge-gray'];
                echo '<span class="badge '.($sc[$customer['status']]??'badge-gray').'">'.ucfirst($customer['status']).'</span>';
                ?>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= base_url("customers/edit/{$customer['id']}") ?>" class="btn btn-ghost"><i class="fa-solid fa-pen"></i> Edit</a>
        <?php if ($customer['status']==='active'): ?>
        <button class="btn btn-danger" onclick="document.getElementById('suspendModal').classList.add('open')"><i class="fa-solid fa-ban"></i> Suspend</button>
        <?php elseif ($customer['status']==='suspended'): ?>
        <form method="POST" action="<?= base_url("customers/reconnect/{$customer['id']}") ?>" style="display:inline;">
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-rotate"></i> Reconnect</button>
        </form>
        <?php endif; ?>
        <?php if ($customer['status'] !== 'deleted'): ?>
        <button class="btn btn-danger" onclick="confirmDelete(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['full_name']) ?>')" style="background:#dc2626;border-color:#dc2626;"><i class="fa-solid fa-trash"></i> Delete</button>
        <?php endif; ?>
        <a href="<?= base_url('customers') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<!-- AI Account Health Summary -->
<?php if (env('AI_ENABLED') && !empty($aiSummary)): ?>
<div class="card fade-in" style="padding:16px 20px; margin-bottom:20px; border-left: 4px solid var(--purple); background: linear-gradient(to right, rgba(124, 58, 237, 0.05), transparent);">
    <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:36px; height:36px; border-radius:10px; background:rgba(124, 58, 237, 0.1); color:var(--purple); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </div>
        <div>
            <div style="font-size:11px; font-weight:700; color:var(--text2); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;">AI Account Insight</div>
            <div style="font-size:14px; font-weight:500; color:var(--text); line-height:1.4;">
                <?= sanitize($aiSummary) ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;" class="fade-in">
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:var(--green);">৳<?= number_format($customer['monthly_charge'],0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Monthly Charge</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:22px;font-weight:800;<?= $customer['due_amount']>0?'color:var(--red)':'' ?>">৳<?= number_format($customer['due_amount'],0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Due Amount</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:var(--blue);"><?= number_format(count($invoices)) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Total Invoices</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:var(--purple);"><?= number_format(count($payments)) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Payments Made</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="fade-in">

    <!-- Left: Customer Info -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Personal Info -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-user" style="color:var(--blue)"></i> Personal Information
            </div>
            <div class="info-grid">
                <div class="info-item"><div class="label">Full Name</div><div class="value"><?= htmlspecialchars($customer['full_name']) ?></div></div>
                <div class="info-item"><div class="label">Phone</div><div class="value"><?= htmlspecialchars($customer['phone']) ?></div></div>
                <?php if($customer['phone_alt']): ?>
                <div class="info-item"><div class="label">Alt Phone</div><div class="value"><?= htmlspecialchars($customer['phone_alt']) ?></div></div>
                <?php endif; ?>
                <?php if($customer['email']): ?>
                <div class="info-item"><div class="label">Email</div><div class="value"><?= htmlspecialchars($customer['email']) ?></div></div>
                <?php endif; ?>
                <?php if($customer['nid_number']): ?>
                <div class="info-item"><div class="label">NID</div><div class="value"><?= htmlspecialchars($customer['nid_number']) ?></div></div>
                <?php endif; ?>
                <div class="info-item"><div class="label">Father's Name</div><div class="value"><?= htmlspecialchars($customer['father_name']??'—') ?></div></div>
                <div class="info-item" style="grid-column:1/-1;"><div class="label">Address</div><div class="value"><?= htmlspecialchars($customer['address']) ?></div></div>
                <div class="info-item"><div class="label">Branch</div><div class="value"><?= htmlspecialchars($customer['branch_name']??'—') ?></div></div>
                <div class="info-item"><div class="label">Zone</div><div class="value"><?= htmlspecialchars($customer['zone_name']??'—') ?></div></div>
                <div class="info-item"><div class="label">Connection Date</div><div class="value"><?= $customer['connection_date'] ? date('d M Y', strtotime($customer['connection_date'])) : '—' ?></div></div>
                <div class="info-item"><div class="label">Billing Day</div><div class="value"><?= $customer['billing_day'] ?>th of month</div></div>
            </div>
        </div>

        <!-- Connection Info -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-wifi" style="color:var(--purple)"></i> Connection Details
            </div>
            <div class="info-grid">
                <div class="info-item"><div class="label">Type</div><div class="value"><span class="badge badge-blue"><?= strtoupper($customer['connection_type']) ?></span></div></div>
                <div class="info-item"><div class="label">Package</div><div class="value"><?= htmlspecialchars($customer['package_name']??'—') ?></div></div>
                <?php if($customer['speed_download']): ?>
                <div class="info-item"><div class="label">Speed</div><div class="value"><?= $customer['speed_download'] ?> ↓ / <?= $customer['speed_upload'] ?> ↑</div></div>
                <?php endif; ?>
                <?php if($customer['pppoe_username']): ?>
                <div class="info-item"><div class="label">PPPoE Username</div><div class="value" style="font-family:monospace;"><?= htmlspecialchars($customer['pppoe_username']) ?></div></div>
                <?php endif; ?>
                <?php if($customer['static_ip']): ?>
                <div class="info-item"><div class="label">Static IP</div><div class="value" style="font-family:monospace;"><?= htmlspecialchars($customer['static_ip']) ?></div></div>
                <?php endif; ?>
                <?php if($onu): ?>
                <div class="info-item"><div class="label">ONU Serial</div><div class="value" style="font-family:monospace;"><?= htmlspecialchars($onu['serial_number']) ?></div></div>
                <div class="info-item"><div class="label">ONU Model</div><div class="value"><?= htmlspecialchars($onu['model']??'—') ?></div></div>
                <?php endif; ?>
                <?php if($customer['mikrotik_profile']): ?>
                <div class="info-item"><div class="label">MikroTik Profile</div><div class="value" style="font-family:monospace;"><?= htmlspecialchars($customer['mikrotik_profile']) ?></div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Billing + Work Orders -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Invoices -->
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:14px;font-weight:700;">Invoice History</div>
                <a href="<?= base_url("billing/invoices?search={$customer['customer_code']}") ?>" style="font-size:12px;color:var(--blue);text-decoration:none;">View all</a>
            </div>
            <table class="data-table">
                <thead><tr><th>Invoice</th><th>Month</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php if(empty($invoices)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text2);">No invoices yet</td></tr>
                    <?php else: foreach(array_slice($invoices,0,5) as $inv): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:11px;"><?= $inv['invoice_number'] ?></td>
                        <td style="font-size:12px;"><?= date('M Y',strtotime($inv['billing_month'])) ?></td>
                        <td style="font-weight:600;">৳<?= number_format($inv['total'],0) ?></td>
                        <td><?php
                            $sc=['paid'=>'badge-green','unpaid'=>'badge-red','partial'=>'badge-yellow','waived'=>'badge-gray'];
                            echo '<span class="badge '.($sc[$inv['status']]??'badge-gray').'" style="font-size:10px;">'.ucfirst($inv['status']).'</span>';
                        ?></td>
                        <td>
                            <?php if(in_array($inv['status'],['unpaid','partial'])): ?>
                            <a href="<?= base_url("billing/pay/{$inv['id']}") ?>" class="btn btn-success btn-sm">Pay</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Payments -->
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <div style="font-size:14px;font-weight:700;">Payment History</div>
            </div>
            <div style="max-height:240px;overflow-y:auto;">
                <?php if(empty($payments)): ?>
                <div style="text-align:center;padding:24px;color:var(--text2);font-size:13px;">No payments recorded</div>
                <?php else: foreach($payments as $p): ?>
                <div class="timeline-item" style="padding:12px 18px;border-bottom:1px solid var(--border);">
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:600;color:var(--green);">৳<?= number_format($p['amount'],2) ?></div>
                        <div style="font-size:11px;color:var(--text2);"><?= date('d M Y h:i A',strtotime($p['payment_date'])) ?> &middot; <?= ucfirst(str_replace('_',' ',$p['payment_method'])) ?></div>
                    </div>
                    <a href="<?= base_url("billing/receipt/{$p['id']}") ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-print"></i></a>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Work Orders -->
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:14px;font-weight:700;">Work Orders</div>
                <a href="<?= base_url('workorders/create?customer_id='.$customer['id']) ?>" style="font-size:12px;color:var(--blue);text-decoration:none;">+ New</a>
            </div>
            <?php if(empty($workOrders)): ?>
            <div style="text-align:center;padding:24px;color:var(--text2);font-size:13px;">No work orders</div>
            <?php else: foreach($workOrders as $wo): ?>
            <div style="padding:12px 18px;border-bottom:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($wo['title']) ?></div>
                        <div style="font-size:11px;color:var(--text2);"><?= $wo['technician']??'Unassigned' ?> &middot; <?= date('d M',strtotime($wo['created_at'])) ?></div>
                    </div>
                    <?php $wsc=['pending'=>'badge-yellow','in_progress'=>'badge-blue','completed'=>'badge-green','cancelled'=>'badge-gray'];
                    echo '<span class="badge '.($wsc[$wo['status']]??'badge-gray').'" style="font-size:10px;">'.ucfirst(str_replace('_',' ',$wo['status'])).'</span>'; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Auto-Payment Subscription -->
        <?php
        $shppEnabled = false;
        try {
            $shppRow = Database::getInstance()->fetchOne("SELECT `value` FROM settings WHERE `key`='selfhosted_piprapay_enabled'");
            $shppEnabled = ($shppRow['value'] ?? '') === '1';
        } catch(Exception $e) {}
        if ($shppEnabled):
            $subscription = Database::getInstance()->fetchOne(
                "SELECT * FROM piprapay_subscriptions WHERE customer_id=?", [$customer['id']]
            );
        ?>
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-rotate" style="color:var(--green);"></i> Auto-Payment
                </div>
                <span class="badge <?= $customer['auto_payment_enabled'] ? 'badge-green' : 'badge-gray' ?>">
                    <?= $customer['auto_payment_enabled'] ? 'Enabled' : 'Disabled' ?>
                </span>
            </div>
            <div style="padding:16px;">
                <?php if ($subscription): ?>
                <div style="display:grid;gap:8px;font-size:13px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--text2);">Preferred Method</span>
                        <span style="font-weight:600;"><?= ucfirst(str_replace('_',' ',$subscription['payment_method']??'—')) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--text2);">Account</span>
                        <span style="font-family:monospace;"><?= htmlspecialchars($subscription['payment_account']??'—') ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--text2);">Max Amount</span>
                        <span style="font-weight:600;">৳<?= number_format($subscription['max_amount']??0,0) ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div style="color:var(--text2);font-size:13px;margin-bottom:14px;">No auto-payment subscription configured.</div>
                <?php endif; ?>
                <button class="btn btn-ghost btn-sm" onclick="loadAutoBillingStatus(<?= $customer['id'] ?>)">
                    <i class="fa-solid fa-clock-rotate-left"></i> View Queue
                </button>
            </div>
            <div id="auto-billing-queue" style="display:none;border-top:1px solid var(--border);padding:14px;font-size:12px;"></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal-overlay" id="suspendModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-ban" style="color:var(--red);margin-right:8px;"></i>Suspend Customer</div>
            <button class="icon-btn" onclick="document.getElementById('suspendModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url("customers/suspend/{$customer['id']}") ?>">
            <div class="modal-body">
                <p style="color:var(--text2);font-size:13px;margin-bottom:14px;">Suspend <strong><?= htmlspecialchars($customer['full_name']) ?></strong>?</p>
                <select name="reason" class="form-input">
                    <option>Non-payment</option><option>Customer request</option><option>Network abuse</option><option>Other</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('suspendModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Suspend</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete customer "${name}"?\n\nThis will mark the customer as deleted but preserve their data.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('customers/delete/') ?>' + id;
        document.body.appendChild(form);
        form.submit();
    }
}

function loadAutoBillingStatus(customerId) {
    const el = document.getElementById('auto-billing-queue');
    el.style.display = 'block';
    el.innerHTML = '<span style="color:var(--text2);">Loading...</span>';
    fetch('<?= base_url('payment/selfhosted/status/') ?>' + customerId)
        .then(r => r.json())
        .then(data => {
            const q = data.queue || [];
            if (!q.length) { el.innerHTML = '<span style="color:var(--text2);">No billing queue entries.</span>'; return; }
            el.innerHTML = q.map(item => `
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text2);">Invoice #${item.invoice_id}</span>
                    <span>৳${parseFloat(item.amount).toFixed(2)}</span>
                    <span class="badge ${item.status==='completed'?'badge-green':item.status==='failed'?'badge-red':'badge-yellow'}">${item.status}</span>
                </div>`).join('');
        })
        .catch(() => { el.innerHTML = '<span style="color:var(--red);">Failed to load queue.</span>'; });
}
</script>
