<?php
/**
 * Bill View
 */
$statusColors = ['pending' => 'badge-red', 'partial' => 'badge-yellow', 'paid' => 'badge-green', 'cancelled' => 'badge-gray'];
$canPay = PermissionHelper::hasPermission('purchase.payment');
$isPending = $bill['payment_status'] !== 'paid' && $bill['payment_status'] !== 'cancelled';
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--blue);margin-right:10px;"></i>Bill <?= htmlspecialchars($bill['bill_number']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('purchases/bills') ?>">Purchase Bills</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span><?= htmlspecialchars($bill['bill_number']) ?></span>
        </div>
    </div>
    <a href="<?= base_url('purchases/bills') ?>" class="btn btn-ghost btn-sm">Back</a>
</div>

<?php foreach (['success','error'] as $msg): ?>
<?php if (!empty($_SESSION[$msg])): ?>
<div style="background:#<?= $msg=='success'?'dcfce7':'fee2e2' ?>;border:1px solid #<?= $msg=='success'?'86efac':'fecaca' ?>;color:#<?= $msg=='success'?'15803d':'b91c1c' ?>;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-<?= $msg=='success' ? 'circle-check' : 'circle-xmark' ?>"></i> <?= htmlspecialchars($_SESSION[$msg]) ?>
</div>
<?php unset($_SESSION[$msg]); endif; ?>
<?php endforeach; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
    <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card">
            <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;">
                <div><h2 style="font-size:17px;font-weight:700;"><?= htmlspecialchars($bill['vendor_name']) ?></h2><?= htmlspecialchars($bill['vendor_phone'] ?? '') ?></div>
                <span class="badge <?= $statusColors[$bill['payment_status']] ?? 'badge-gray' ?>"><?= ucfirst($bill['payment_status']) ?></span>
            </div>
            <div style="padding:20px 24px;">
                <table style="width:100%;"><tr><td style="padding:8px 0;border-bottom:1px solid var(--border);">Subtotal</td><td style="text-align:right;"><?= number_format($bill['subtotal'], 2) ?></td></tr>
                <tr><td style="padding:8px 0;border-bottom:1px solid var(--border);">Discount</td><td style="text-align:right;">-<?= number_format($bill['discount'], 2) ?></td></tr>
                <tr style="font-weight:700;font-size:18px;"><td style="padding:12px 0;">Total</td><td style="text-align:right;"><?= number_format($bill['total'], 2) ?></td></tr>
                <tr style="color:#15803d;"><td style="padding:8px 0;border-top:1px solid var(--border);">Paid</td><td style="text-align:right;"><?= number_format($bill['paid_amount'], 2) ?></td></tr>
                <tr style="color:#dc2626;font-weight:700;"><td style="padding:8px 0;">Due</td><td style="text-align:right;"><?= number_format($bill['due_amount'], 2) ?></td></tr></table>
            </div>
        </div>
        <div class="card">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><h3 style="font-size:14px;font-weight:700;">Line Items</h3></div>
            <div style="padding:16px 20px;">
                <table class="data-table"><thead><tr><th>Description</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody><?php $items = json_decode($bill['items'], true); if($items): foreach($items as $it): ?>
                <tr><td><?= htmlspecialchars($it['description']) ?></td><td><?= $it['quantity'] ?></td><td><?= number_format($it['unit_price'], 2) ?></td><td><?= number_format($it['line_total'], 2) ?></td></tr>
                <?php endforeach; endif; ?></tbody></table>
            </div>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:20px;">
        <?php if ($canPay && $isPending && $bill['due_amount'] > 0): ?>
        <div class="card">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><h3 style="font-size:14px;font-weight:700;">Record Payment</h3></div>
            <form method="POST" action="<?= base_url('purchases/bills/payment/' . $bill['id']) ?>">
                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                    <div><label class="form-label">Amount</label><input type="number" name="amount" class="form-input" value="<?= $bill['due_amount'] ?>" min="0.01" step="0.01" required></div>
                    <div><label class="form-label">Method</label><select name="payment_method" class="form-input"><?php foreach(['cash'=>'Cash','mobile_banking'=>'Mobile Banking','bank_transfer'=>'Bank Transfer','online'=>'Online'] as $m=>$l): ?><option value="<?= $m ?>"><?= $l ?></option><?php endforeach; ?></select></div>
                    <div><label class="form-label">Reference</label><input type="text" name="reference" class="form-input"></div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Record Payment</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <div class="card">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);"><h3 style="font-size:14px;font-weight:700;">Payment History</h3></div>
            <?php if(empty($payments)): ?><div style="padding:20px;text-align:center;color:var(--text2);font-size:13px;">No payments.</div>
            <?php else: ?><div style="padding:16px 20px;"><?php foreach($payments as $p): ?>
                <div style="padding:12px 0;border-bottom:1px solid var(--border);">
                    <div style="font-weight:600;font-size:13px;"><?= number_format($p['amount'], 2) ?></div>
                    <div style="font-size:12px;color:var(--text2);"><?= ucfirst($p['payment_method']) ?> • <?= date('d M Y H:i', strtotime($p['created_at'])) ?></div>
                </div>
                <?php endforeach; ?></div><?php endif; ?>
        </div>
    </div>
</div>