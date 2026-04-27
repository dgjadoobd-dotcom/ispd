<?php // views/hr/salary_slip.php ?>
<style>
@media print {
    #sidebar, #header, .page-header, .no-print { display: none !important; }
    #main { margin: 0 !important; padding: 0 !important; }
    #printArea { box-shadow: none !important; border: none !important; max-width: 100% !important; }
    body { background: #fff !important; }
}
</style>

<div class="page-header fade-in no-print">
    <div>
        <h1 class="page-title">Salary Slip</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('hr/payroll') ?>" style="color:var(--blue);text-decoration:none;"><i class="fa-solid fa-money-bill-wave"></i> Payroll</a>
            &rsaquo; Slip #<?= $slip['id'] ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
        <a href="<?= base_url('hr/payroll') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card fade-in" style="padding:36px;max-width:720px;margin:0 auto;" id="printArea">

    <!-- Company Header -->
    <div style="text-align:center;margin-bottom:28px;padding-bottom:20px;border-bottom:2px solid var(--border);">
        <div style="width:48px;height:48px;border-radius:12px;background:var(--blue);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
            <i class="fa-solid fa-network-wired" style="color:#fff;font-size:20px;"></i>
        </div>
        <h2 style="margin:0 0 4px;font-size:22px;font-weight:800;color:var(--text);"><?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'FCNCHBD ISP ERP' ?></h2>
        <p style="margin:0;font-size:13px;color:var(--text2);">Salary Slip</p>
        <div style="display:inline-block;margin-top:8px;padding:4px 16px;background:rgba(37,99,235,.1);border-radius:20px;font-size:13px;font-weight:700;color:var(--blue);">
            <?= date('F Y', strtotime($slip['salary_month'])) ?>
        </div>
    </div>

    <!-- Employee Info Grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;padding:16px;background:var(--bg3);border-radius:8px;border:1px solid var(--border);">
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Employee</div>
            <table style="width:100%;border-collapse:collapse;">
                <?php $empRows = [
                    'Name'        => $slip['employee_name'],
                    'Code'        => $slip['employee_code'],
                    'Department'  => $slip['department_name'] ?? '—',
                    'Designation' => $slip['designation_title'] ?? '—',
                ]; foreach ($empRows as $label => $val): ?>
                <tr>
                    <td style="padding:4px 0;font-size:12px;color:var(--text2);width:40%;"><?= $label ?></td>
                    <td style="padding:4px 0;font-size:13px;font-weight:600;"><?= htmlspecialchars((string)$val) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Details</div>
            <table style="width:100%;border-collapse:collapse;">
                <?php
                $generatedDate = !empty($slip['generated_at']) ? date('d M Y', strtotime($slip['generated_at'])) :
                                 (!empty($slip['created_at'])  ? date('d M Y', strtotime($slip['created_at']))  : '—');
                $slipRows = [
                    'Branch'         => $slip['branch_name'] ?? '—',
                    'Salary Month'   => date('F Y', strtotime($slip['salary_month'])),
                    'Payment Status' => ucfirst($slip['payment_status']),
                    'Generated'      => $generatedDate,
                ]; foreach ($slipRows as $label => $val): ?>
                <tr>
                    <td style="padding:4px 0;font-size:12px;color:var(--text2);width:40%;"><?= $label ?></td>
                    <td style="padding:4px 0;font-size:13px;font-weight:600;"><?= htmlspecialchars((string)$val) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Attendance Summary -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:28px;">
        <div style="text-align:center;padding:14px;background:rgba(22,163,74,.08);border-radius:8px;border:1px solid rgba(22,163,74,.2);">
            <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Present Days</div>
            <div style="font-size:26px;font-weight:800;color:var(--green);"><?= (int)$slip['present_days'] ?></div>
        </div>
        <div style="text-align:center;padding:14px;background:rgba(220,38,38,.08);border-radius:8px;border:1px solid rgba(220,38,38,.2);">
            <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Absent Days</div>
            <div style="font-size:26px;font-weight:800;color:var(--red);"><?= (int)$slip['absent_days'] ?></div>
        </div>
        <div style="text-align:center;padding:14px;background:rgba(37,99,235,.08);border-radius:8px;border:1px solid rgba(37,99,235,.2);">
            <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Leave Days</div>
            <div style="font-size:26px;font-weight:800;color:var(--blue);"><?= (int)$slip['leave_days'] ?></div>
        </div>
    </div>

    <!-- Earnings & Deductions Table -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <!-- Earnings -->
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Earnings</div>
            <table style="width:100%;border-collapse:collapse;border:1px solid var(--border);border-radius:8px;overflow:hidden;">
                <thead>
                    <tr style="background:rgba(22,163,74,.08);">
                        <th style="padding:10px 12px;text-align:left;font-size:12px;color:var(--green);">Description</th>
                        <th style="padding:10px 12px;text-align:right;font-size:12px;color:var(--green);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top:1px solid var(--border);">
                        <td style="padding:10px 12px;font-size:13px;">Basic Salary</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:600;">৳<?= number_format((float)$slip['basic_salary'], 2) ?></td>
                    </tr>
                    <tr style="border-top:1px solid var(--border);">
                        <td style="padding:10px 12px;font-size:13px;">Allowances</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:600;color:var(--green);">৳<?= number_format((float)$slip['allowances'], 2) ?></td>
                    </tr>
                    <tr style="border-top:2px solid var(--border);background:rgba(22,163,74,.06);">
                        <td style="padding:10px 12px;font-size:13px;font-weight:700;">Gross Pay</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:800;font-size:15px;color:var(--green);">৳<?= number_format((float)$slip['gross_pay'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Deductions -->
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Deductions</div>
            <table style="width:100%;border-collapse:collapse;border:1px solid var(--border);border-radius:8px;overflow:hidden;">
                <thead>
                    <tr style="background:rgba(220,38,38,.08);">
                        <th style="padding:10px 12px;text-align:left;font-size:12px;color:var(--red);">Description</th>
                        <th style="padding:10px 12px;text-align:right;font-size:12px;color:var(--red);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top:1px solid var(--border);">
                        <td style="padding:10px 12px;font-size:13px;">Absent Deduction</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:600;color:var(--red);">৳<?= number_format((float)$slip['deductions'], 2) ?></td>
                    </tr>
                    <tr style="border-top:2px solid var(--border);background:rgba(220,38,38,.06);">
                        <td style="padding:10px 12px;font-size:13px;font-weight:700;">Total Deductions</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:800;font-size:15px;color:var(--red);">৳<?= number_format((float)$slip['deductions'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Net Pay Banner -->
    <div style="text-align:center;padding:20px;background:linear-gradient(135deg,rgba(37,99,235,.1),rgba(22,163,74,.1));border-radius:10px;border:1px solid rgba(37,99,235,.2);">
        <div style="font-size:13px;color:var(--text2);margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Net Pay</div>
        <div style="font-size:36px;font-weight:900;color:var(--blue);">৳<?= number_format((float)$slip['net_pay'], 2) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">
            <?php
            $statusBadge = $slip['payment_status'] === 'paid'
                ? '<span style="background:rgba(22,163,74,.15);color:var(--green);padding:3px 10px;border-radius:12px;font-weight:700;">Paid</span>'
                : '<span style="background:rgba(217,119,6,.15);color:var(--yellow);padding:3px 10px;border-radius:12px;font-weight:700;">Pending</span>';
            echo $statusBadge;
            ?>
        </div>
    </div>

    <?php if (!empty($slip['notes'])): ?>
    <p style="font-size:12px;color:var(--text2);margin-top:16px;padding:10px 14px;background:var(--bg3);border-radius:6px;border:1px solid var(--border);">
        <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i><?= htmlspecialchars($slip['notes']) ?>
    </p>
    <?php endif; ?>

    <!-- Signature area (print only) -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:40px;padding-top:20px;border-top:1px solid var(--border);">
        <div style="text-align:center;">
            <div style="border-top:1px solid var(--border);padding-top:8px;font-size:12px;color:var(--text2);">Employee Signature</div>
        </div>
        <div style="text-align:center;">
            <div style="border-top:1px solid var(--border);padding-top:8px;font-size:12px;color:var(--text2);">Authorized Signature</div>
        </div>
    </div>
</div>
