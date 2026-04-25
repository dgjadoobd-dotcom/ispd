<?php
/**
 * Purchase Bills List View
 */
$bills = $result['data'] ?? [];
$total = $result['total'] ?? 0;

$statusColors = [
    'pending' => 'badge-red',
    'partial' => 'badge-yellow',
    'paid' => 'badge-green',
    'cancelled' => 'badge-gray',
];
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--blue);margin-right:10px;"></i>Purchase Bills</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Purchase Bills</span>
        </div>
    </div>
    <?php if (PermissionHelper::hasPermission('purchase.create')): ?>
    <a href="<?= base_url('purchases/bills/create') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> New Bill
    </a>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']) ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in" style="padding:16px;margin-bottom:20px;">
    <form method="GET" action="<?= base_url('purchases/bills') ?>" style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:12px;align-items:end;">
        <div>
            <label class="form-label" style="font-size:12px;">Vendor</label>
            <select name="vendor_id" class="form-input" style="padding:8px 12px;font-size:13px;">
                <option value="">All Vendors</option>
                <?php foreach ($vendors as $v): ?>
                <option value="<?= $v['id'] ?>" <?= (int)($_GET['vendor_id'] ?? 0) === $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Status</label>
            <select name="status" class="form-input" style="padding:8px 12px;font-size:13px;">
                <option value="">All</option>
                <?php foreach (['pending','partial','paid','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Search</label>
            <input type="text" name="search" class="form-input" placeholder="Bill #, vendor..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="<?= base_url('purchases/bills') ?>" class="btn btn-ghost btn-sm">Clear</a>
        </div>
    </form>
</div>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <span style="font-weight:700;"><?= number_format($total) ?> bill<?= $total !== 1 ? 's' : '' ?></span>
        <a href="<?= base_url('purchases/ledger') ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-book"></i> Ledger</a>
    </div>

    <?php if (empty($bills)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-file-invoice-dollar" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
        <p>No bills found.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bill #</th>
                    <th>Vendor</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $b): ?>
                <tr>
                    <td style="font-weight:700;color:var(--blue);"><?= htmlspecialchars($b['bill_number']) ?></td>
                    <td><?= htmlspecialchars($b['vendor_name'] ?? '—') ?></td>
                    <td style="font-weight:600;"><?= number_format($b['total'], 2) ?></td>
                    <td style="color:#15803d;"><?= number_format($b['paid_amount'], 2) ?></td>
                    <td style="<?= $b['due_amount'] > 0 ? 'color:#dc2626;font-weight:700;' : '' ?>"><?= number_format($b['due_amount'], 2) ?></td>
                    <td><span class="badge <?= $statusColors[$b['payment_status']] ?? 'badge-gray' ?>"><?= ucfirst($b['payment_status']) ?></span></td>
                    <td style="font-size:12px;color:var(--text2);"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                    <td><a href="<?= base_url('purchases/bills/view/' . $b['id']) ?>" class="btn btn-ghost btn-xs">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>