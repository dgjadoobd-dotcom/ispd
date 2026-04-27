<?php
// views/billing/receipt.php — Printable Money Receipt (Bangla)
?>
<style>
@media print { #main > *:not(#receiptArea) { display:none; } #printBtns { display:none; } }
.receipt-box { max-width:680px; margin:0 auto; background:white; color:#111; border-radius:16px; overflow:hidden; box-shadow:0 10px 60px rgba(0,0,0,0.4); }
.receipt-header { background: linear-gradient(135deg, #1e3a8a, #7c3aed); color: white; padding: 24px 28px; }
.receipt-body { padding: 24px 28px; }
.receipt-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed #e5e7eb; font-size:14px; }
.receipt-row:last-child { border-bottom:none; }
.amount-box { background:#f0fdf4; border:2px solid #22c55e; border-radius:12px; padding:16px; margin-top:16px; text-align:center; }
.bangla { font-family:'Noto Serif Bengali', serif; }
.watermark { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) rotate(-30deg); font-size:80px; color:rgba(34,197,94,0.08); font-weight:900; pointer-events:none; }
</style>

<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;700&display=swap" rel="stylesheet">

<div class="page-header fade-in" id="printBtns">
    <div>
        <h1 class="page-title">Money Receipt</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-receipt" style="color:var(--blue)"></i> Receipt #<?= htmlspecialchars($payment['receipt_number']) ?></div>
    </div>
    <div style="display:flex;gap:10px;">
        <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print</button>
        <a href="<?= base_url('billing/invoices') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<div id="receiptArea" class="fade-in" style="max-width:680px;margin:0 auto;position:relative;">
    <div class="receipt-box">
        <!-- Header -->
        <div class="receipt-header">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <div style="font-size:22px;font-weight:800;letter-spacing:-0.5px;"><?= htmlspecialchars($payment['branch_name'] ?? 'FCNCHBD ISP ERP') ?></div>
                    <div style="font-size:12px;opacity:0.8;margin-top:2px;"><?= htmlspecialchars($payment['branch_address'] ?? 'Bangladesh') ?></div>
                    <div style="font-size:12px;opacity:0.8;"><?= htmlspecialchars($payment['branch_phone'] ?? '') ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px;opacity:0.7;">MONEY RECEIPT</div>
                    <div style="font-size:20px;font-weight:700;font-family:monospace;"><?= htmlspecialchars($payment['receipt_number']) ?></div>
                    <div style="font-size:11px;opacity:0.7;"><?= date('d M Y, h:i A', strtotime($payment['payment_date'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="receipt-body" style="position:relative;">
            <div class="watermark">PAID</div>

            <!-- Bangla Header -->
            <div class="bangla" style="text-align:center;margin-bottom:20px;padding-bottom:16px;border-bottom:2px solid #f3f4f6;">
                <div style="font-size:14px;color:#6b7280;">পেমেন্ট রসিদ</div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">অনুগ্রহ করে এই রসিদটি সংরক্ষণ করুন</div>
            </div>

            <!-- Customer Info -->
            <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Customer Details / গ্রাহকের তথ্য</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
                    <div><span style="color:#6b7280;">Name:</span> <strong><?= htmlspecialchars($payment['full_name']) ?></strong></div>
                    <div><span style="color:#6b7280;">ID:</span> <strong><?= htmlspecialchars($payment['customer_code']) ?></strong></div>
                    <div><span style="color:#6b7280;">Phone:</span> <?= htmlspecialchars($payment['phone']) ?></div>
                    <div><span style="color:#6b7280;">Username:</span> <?= htmlspecialchars($payment['pppoe_username'] ?? '—') ?></div>
                </div>
            </div>

            <!-- Payment Details -->
            <div style="margin-bottom:8px;">
                <div class="receipt-row">
                    <span style="color:#6b7280;">Invoice # / চালান নং</span>
                    <strong><?= htmlspecialchars($payment['invoice_number'] ?? 'Advance Payment') ?></strong>
                </div>
                <div class="receipt-row">
                    <span style="color:#6b7280;">Billing Month / বিলিং মাস</span>
                    <strong><?= $payment['billing_month'] ? date('F Y', strtotime($payment['billing_month'])) : '—' ?></strong>
                </div>
                <div class="receipt-row">
                    <span style="color:#6b7280;">Package / প্যাকেজ</span>
                    <strong><?= htmlspecialchars($payment['package_name'] ?? '—') ?></strong>
                </div>
                <div class="receipt-row">
                    <span style="color:#6b7280;">Payment Method / পেমেন্ট পদ্ধতি</span>
                    <strong><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></strong>
                </div>
                <div class="receipt-row">
                    <span style="color:#6b7280;">Received By / গ্রহণকারী</span>
                    <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong>
                </div>
            </div>

            <!-- Amount Box -->
            <div class="amount-box">
                <div style="font-size:12px;color:#6b7280;margin-bottom:4px;" class="bangla">পরিশোধিত মোট পরিমাণ</div>
                <div style="font-size:36px;font-weight:900;color:#16a34a;">৳<?= number_format($payment['amount'], 2) ?></div>
                <div style="font-size:11px;color:#16a34a;margin-top:4px;">BDT <?= number_format($payment['amount'], 0) ?> Taka Only</div>
            </div>

            <!-- Signature -->
            <div style="display:flex;justify-content:space-between;margin-top:28px;padding-top:16px;border-top:1px dashed #e5e7eb;">
                <div style="text-align:center;">
                    <div style="width:100px;border-top:1px solid #111;padding-top:6px;font-size:11px;color:#6b7280;">Customer Signature</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:11px;color:#6b7280;margin-bottom:6px;">Authorized Signatory</div>
                    <div style="width:120px;border-top:1px solid #111;padding-top:6px;font-size:11px;color:#6b7280;"><?= htmlspecialchars($payment['branch_name'] ?? 'FCNCHBD ISP ERP') ?></div>
                </div>
            </div>

            <div style="margin-top:16px;padding:10px;background:#fffbeb;border-radius:8px;font-size:11px;color:#92400e;text-align:center;" class="bangla">
                ⚠️ এই রসিদটি একটি বৈধ পেমেন্ট প্রমাণ। অনুগ্রহ করে সংরক্ষণ করুন।
            </div>
        </div>
    </div>
</div>
