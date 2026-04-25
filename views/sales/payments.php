<?php
/**
 * Sales Payments List View
 * Requirement 5.5: payment tracking dashboard
 */
$payments = $result['data'] ?? [];
$total    = $result['total'] ?? 0;
$page     = $result['page'] ?? 1;
$totalPages = $result['totalPages'] ?? 1;
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-money-bill" style="color:var(--blue);margin-right:10px;"></i>Payment Records</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Payments</span>
        </div>
    </div>
    <a href="<?= base_url('sales/invoices') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Invoices
    </a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']) ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<div class="card fade-in" style="padding:16px;margin-bottom:20px;">
    <form method="GET" action="<?= base_url('sales/payments') ?>" style="display:grid;grid-template-columns:1fr 1fr auto auto;gap:12px;align-items:end;">
        <div>
            <label class="form-label" style="font-size:12px;">Date From</label>
            <input type="date" name="date_from" class="form-input" value="<?= $_GET['date_from'] ?? '' ?>">
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Date To</label>
            <input type="date" name="date_to" class="form-input" value="<?= $_GET['date_to'] ?? '' ?>">
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
        </div>
        <div>
            <a href="<?= base_url('sales/payments') ?>" class="btn btn-ghost btn-sm">Clear</a>
        </div>
    </form>
</div>

<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <span style="font-weight:700;font-size:15px;"><?= number_format($total) ?> payment<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($payments)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-money-bill" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
        <p style="font-size:15px;">No payments found.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Collected By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pmt): ?>
                <tr>
                    <td style="font-weight:600;color:var(--blue);"><?= htmlspecialchars($pmt['invoice_number']) ?></td>
                    <td><?= htmlspecialchars($pmt['customer_name']) ?></td>
                    <td style="font-weight:600;color:#15803d;"><?= number_format($pmt['amount'], 2) ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $pmt['method'])) ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($pmt['reference'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($pmt['collected_by_name'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text2);"><?= date('d M Y', strtotime($pmt['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>