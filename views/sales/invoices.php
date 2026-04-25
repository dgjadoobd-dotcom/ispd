<?php
/**
 * Sales Invoices List View
 * Requirement 5.1: paginated invoice list
 */
$invoices = $result['data'] ?? [];
$total     = $result['total'] ?? 0;
$page      = $result['page'] ?? 1;
$totalPages = $result['totalPages'] ?? 1;

$statusColors = [
    'unpaid'    => 'badge-red',
    'partial'  => 'badge-yellow',
    'paid'     => 'badge-green',
    'cancelled' => 'badge-gray',
];
$typeColors = [
    'installation' => 'badge-purple',
    'product'     => 'badge-blue',
    'service'    => 'badge-green',
];
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-file-invoice" style="color:var(--blue);margin-right:10px;"></i>Sales Invoices</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Sales Invoices</span>
        </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <?php if (PermissionHelper::hasPermission('sales.reports')): ?>
        <a href="<?= base_url('sales/reports') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-chart-bar"></i> Reports
        </a>
        <?php endif; ?>
        <?php if (PermissionHelper::hasPermission('sales.payment')): ?>
        <a href="<?= base_url('sales/payments') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-money-bill"></i> Payments
        </a>
        <?php endif; ?>
        <?php if (PermissionHelper::hasPermission('sales.create')): ?>
        <a href="<?= base_url('sales/create') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Invoice
        </a>
        <?php endif; ?>
    </div>
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

<div class="card fade-in fade-in-delay_1" style="padding:16px;margin-bottom:20px;">
    <form method="GET" action="<?= base_url('sales/invoices') ?>" style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:12px;align-items:end;">
        <div>
            <label class="form-label" style="font-size:12px;">Status</label>
            <select name="status" class="form-input" style="padding:8px 12px;font-size:13px;">
                <option value="">All Statuses</option>
                <?php foreach (['unpaid','partial','paid','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Type</label>
            <select name="type" class="form-input" style="padding:8px 12px;font-size:13px;">
                <option value="">All Types</option>
                <?php foreach (['installation','product','service'] as $t): ?>
                <option value="<?= $t ?>" <?= ($_GET['type'] ?? '') === $t ? 'selected' : '' ?>>
                    <?= ucfirst($t) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Search</label>
            <input type="text" name="search" class="form-input" style="padding:8px 12px;font-size:13px;"
                   placeholder="Invoice #, customer..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="<?= base_url('sales/invoices') ?>" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-xmark"></i> Clear
            </a>
        </div>
    </form>
</div>

<div class="card fade-in fade-in-delay_2">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-weight:700;font-size:15px;">
            <?= number_format($total) ?> invoice<?= $total !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($invoices)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-file-invoice" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
        <p style="font-size:15px;">No invoices found.</p>
        <?php if (PermissionHelper::hasPermission('sales.create')): ?>
        <a href="<?= base_url('sales/create') ?>" class="btn btn-primary btn-sm" style="margin-top:12px;">
            <i class="fa-solid fa-plus"></i> Create First Invoice
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td style="font-weight:700;color:var(--blue);"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                    <td>
                        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($inv['customer_name'] ?? '—') ?></div>
                        <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($inv['customer_code'] ?? '') ?></div>
                    </td>
                    <td>
                        <span class="badge <?= $typeColors[$inv['invoice_type']] ?? 'badge-gray' ?>">
                            <?= ucfirst($inv['invoice_type']) ?>
                        </span>
                    </td>
                    <td style="font-weight:600;"><?= number_format($inv['total'], 2) ?></td>
                    <td style="color:#15803d;"><?= number_format($inv['paid_amount'], 2) ?></td>
                    <td style="<?= $inv['due_amount'] > 0 ? 'color:#dc2626;font-weight:700;' : '' ?>">
                        <?= number_format($inv['due_amount'], 2) ?>
                    </td>
                    <td>
                        <span class="badge <?= $statusColors[$inv['payment_status']] ?? 'badge-gray' ?>">
                            <?= ucfirst($inv['payment_status']) ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text2);">
                        <?= date('d M Y', strtotime($inv['created_at'])) ?>
                    </td>
                    <td>
                        <a href="<?= base_url('sales/view/' . $inv['id']) ?>" class="btn btn-ghost btn-xs">
                            <i class="fa-solid fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:13px;color:var(--text2);">Page <?= $page ?> of <?= $totalPages ?></span>
        <div style="display:flex;gap:6px;">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-chevron-left"></i> Prev
            </a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-ghost btn-sm">
                Next <i class="fa-solid fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>