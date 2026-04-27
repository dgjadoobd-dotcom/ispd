<?php // views/billing/invoice-detail.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Invoice Detail: <?= htmlspecialchars($invoice['invoice_number']) ?></h1>
        <div class="page-breadcrumb" style="display:flex;align-items:center;gap:8px;">
            <a href="<?= base_url('billing/invoices') ?>" style="color:var(--blue);text-decoration:none;">Invoices</a>
            <i class="fa-solid fa-chevron-right" style="font-size:9px;"></i>
            <?php $ist=['unpaid'=>'badge-red','paid'=>'badge-green','partial'=>'badge-blue','cancelled'=>'badge-gray'];
            echo '<span class="badge '.($ist[$invoice['status']]??'badge-gray').'">'.ucfirst($invoice['status']).'</span>'; ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-ghost" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        <?php if($invoice['status'] !== 'paid'): ?>
        <a href="<?= base_url("billing/pay/{$invoice['id']}") ?>" class="btn btn-success"><i class="fa-solid fa-credit-card"></i> Pay Now</a>
        <?php
        // Show PipraPay button if enabled
        $ppEnabled = false;
        try {
            $ppRow = Database::getInstance()->fetchOne("SELECT `value` FROM settings WHERE `key`='piprapay_enabled'");
            $ppEnabled = ($ppRow['value'] ?? '') === '1';
        } catch(Exception $e) {}
        if ($ppEnabled):
        ?>
        <form method="POST" action="<?= base_url("payment/piprapay/initiate/{$invoice['id']}") ?>" style="display:inline;">
            <button type="submit" class="btn" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;gap:8px;">
                <i class="fa-solid fa-p" style="font-weight:900;"></i> Pay via PipraPay
            </button>
        </form>
        <?php endif; ?>
        <?php
        // Show Self-Hosted PipraPay button if enabled
        $shppEnabled = false;
        try {
            $shppRow = Database::getInstance()->fetchOne("SELECT `value` FROM settings WHERE `key`='selfhosted_piprapay_enabled'");
            $shppEnabled = ($shppRow['value'] ?? '') === '1';
        } catch(Exception $e) {}
        if ($shppEnabled):
        ?>
        <form method="POST" action="<?= base_url("payment/selfhosted/initiate/{$invoice['id']}") ?>" style="display:inline;">
            <button type="submit" class="btn" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;gap:8px;">
                <i class="fa-solid fa-mobile-screen-button"></i> Pay via Self-Hosted
            </button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;" class="fade-in">
    <div>
        <!-- Main Invoice Card -->
        <div class="card" style="padding:40px;position:relative;overflow:hidden;background:#fff;color:#1e293b;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.1);">
            <div style="display:flex;justify-content:space-between;margin-bottom:40px;">
                <div>
                    <div style="font-size:24px;font-weight:900;color:var(--blue);letter-spacing:-1px;">FCNCHBD ISP ERP</div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Enterprise ISP & Billing Mgmt</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:18px;font-weight:800;text-transform:uppercase;">Invoice</div>
                    <div style="font-family:monospace;font-size:14px;color:#64748b;">#<?= htmlspecialchars($invoice['invoice_number']) ?></div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:40px;font-size:13px;">
                <div>
                    <div style="color:#94a3b8;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:8px;">Billed To</div>
                    <div style="font-size:16px;font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($invoice['full_name']) ?></div>
                    <div style="color:#64748b;">Cust Code: <?= htmlspecialchars($invoice['customer_code']) ?></div>
                    <div style="color:#64748b;">Phone: <?= htmlspecialchars($invoice['phone']) ?></div>
                    <div style="color:#64748b;width:80%;"><?= htmlspecialchars($invoice['address']??'') ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="color:#94a3b8;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:8px;">Invoice Info</div>
                    <div style="margin-bottom:4px;">Billing Month: <strong><?= date('F Y', strtotime($invoice['billing_month'])) ?></strong></div>
                    <div style="margin-bottom:4px;">Date Generated: <?= date('d M Y', strtotime($invoice['generated_at'])) ?></div>
                    <div style="color:#ef4444;font-weight:700;">Due Date: <?= date('d M Y', strtotime($invoice['due_date'])) ?></div>
                </div>
            </div>

            <!-- Items -->
            <table style="width:100%;border-collapse:collapse;margin-bottom:40px;">
                <thead>
                    <tr style="border-bottom:2px solid #f1f5f9;text-align:left;font-size:11px;text-transform:uppercase;color:#94a3b8;">
                        <th style="padding:12px 0;">Description</th>
                        <th style="padding:12px 0;text-align:right;">Price</th>
                        <th style="padding:12px 0;text-align:right;">Discount</th>
                        <th style="padding:12px 0;text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody style="font-size:14px;">
                    <tr style="border-bottom:1px solid #f8fafc;">
                        <td style="padding:20px 0;">
                            <div style="font-weight:700;color:#1e293b;">Internet Service Subscription</div>
                            <div style="font-size:12px;color:#64748b;">Package: <?= htmlspecialchars($invoice['package_name']) ?> (<?= $invoice['speed_download'] ?> Mbps)</div>
                            <?php if($invoice['is_prorata']): ?>
                            <div style="font-size:11px;color:var(--blue);margin-top:4px;">Pro-rata billing based on <?= $invoice['prorata_days'] ?> days</div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:20px 0;text-align:right;">৳<?= number_format($invoice['amount'],2) ?></td>
                        <td style="padding:20px 0;text-align:right;">৳<?= number_format($invoice['discount'],2) ?></td>
                        <td style="padding:20px 0;text-align:right;font-weight:700;">৳<?= number_format($invoice['total'],2) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary Table -->
            <div style="display:flex;justify-content:flex-end;">
                <div style="width:300px;background:#f8fafc;border-radius:12px;padding:24px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;color:#64748b;">
                        <span>Subtotal</span><span>৳<?= number_format($invoice['total'],2) ?></span>
                    </div>
                    <?php if($invoice['vat']>0): ?>
                    <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;color:#64748b;">
                        <span>VAT</span><span>৳<?= number_format($invoice['vat'],2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#64748b;">
                        <span>Paid Amount</span><span>৳<?= number_format($invoice['paid_amount'],2) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:900;color:var(--blue);">
                        <span>Total Due</span><span>৳<?= number_format($invoice['due_amount'],2) ?></span>
                    </div>
                </div>
            </div>

            <div style="margin-top:60px;border-top:1px solid #f1f5f9;padding-top:20px;text-align:center;font-size:11px;color:#94a3b8;">
                Thank you for choosing FCNCHBD ISP ERP. This is a computer generated invoice.
            </div>
        </div>

        <!-- Payment History -->
        <div class="card" style="margin-top:20px;overflow:hidden;">
            <div style="padding:14px 18px;font-size:13px;font-weight:700;border-bottom:1px solid var(--border);">Related Payments</div>
            <table class="data-table">
                <thead><tr><th>Date</th><th>Receipt#</th><th>Method</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php if(empty($payments)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text2);">No payments recorded yet.</td></tr>
                    <?php else: foreach($payments as $p): ?>
                    <tr>
                        <td style="font-size:12px;"><?= date('d M Y H:i', strtotime($p['payment_date'])) ?></td>
                        <td style="font-family:monospace;font-size:11px;"><?= htmlspecialchars($p['receipt_number']) ?></td>
                        <td><?= ucfirst(str_replace('_',' ',$p['payment_method'])) ?></td>
                        <td style="font-weight:700;color:var(--green);">৳<?= number_format($p['amount'],2) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card" style="padding:20px;">
            <div style="font-size:12px;color:var(--text2);margin-bottom:10px;font-weight:700;text-transform:uppercase;">Customer Status</div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:10px;height:10px;border-radius:50%;background:var(--green);"></div>
                <div style="font-weight:700;"><?= strtoupper($invoice['status']) ?></div>
            </div>
            <div style="font-size:12px;color:var(--text2);margin-top:6px;">PPPoE: <?= htmlspecialchars($invoice['pppoe_username']??'None') ?></div>
            <a href="<?= base_url("customers/view/{$invoice['customer_id']}") ?>" class="btn btn-ghost" style="margin-top:12px;width:100%;justify-content:center;">View Customer Profile</a>
        </div>
    </div>
</div>
