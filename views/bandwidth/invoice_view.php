<?php $invoice = $invoice ?? []; ?>
<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-file-invoice" style="color:var(--blue);margin-right:10px;"></i>Invoice <?= htmlspecialchars($invoice['invoice_number'] ?? '') ?></h1></div>
    <a href="<?= base_url('bandwidth/invoices') ?>" class="btn btn-ghost">Back</a>
</div>

<div class="card fade-in" style="max-width:600px;">
    <div style="padding:20px;"><h2 style="font-size:17px;font-weight:700;margin-bottom:16px;"><?= htmlspecialchars($invoice['reseller_name'] ?? '') ?></h2>
        <table style="width:100%;">
            <tr><td style="padding:8px 0;border-bottom:1px solid var(--border);">Invoice Number</td><td style="text-align:right;font-weight:600;"><?= htmlspecialchars($invoice['invoice_number'] ?? '') ?></td></tr>
            <tr><td style="padding:8px 0;border-bottom:1px solid var(--border);">Amount</td><td style="text-align:right;font-weight:600;font-size:18px;"><?= number_format($invoice['total_amount'] ?? 0, 2) ?></td></tr>
            <tr><td style="padding:8px 0;border-bottom:1px solid var(--border);">Status</td><td style="text-align:right;"><span class="badge <?= $invoice['payment_status'] === 'paid' ? 'badge-green' : 'badge-red' ?>"><?= ucfirst($invoice['payment_status'] ?? '') ?></span></td></tr>
            <tr><td style="padding:8px 0;">Date</td><td style="text-align:right;"><?= date('d M Y', strtotime($invoice['created_at'] ?? date('Y-m-d'))) ?></td></tr>
        </table>
    </div>
</div>