<?php // views/hr/payroll.php
$months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
           7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Payroll</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-money-bill-wave" style="color:var(--blue)"></i> HR &amp; Payroll</div>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.08);">
    <span style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></span>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;" class="fade-in">

    <!-- Generate Payroll Panel -->
    <div class="card" style="padding:20px;align-self:start;">
        <h3 style="margin:0 0 14px;font-size:14px;font-weight:700;">Generate Salary Slips</h3>
        <p style="font-size:12px;color:var(--text2);margin:0 0 14px;">
            Gross pay = Basic + Allowances − Deductions (based on attendance).
        </p>
        <form method="POST" action="<?= base_url('hr/payroll/generate') ?>">
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div>
                    <label class="form-label">Month</label>
                    <select name="month" class="form-input">
                        <?php foreach ($months as $num => $name): ?>
                        <option value="<?= $num ?>" <?= date('n') == $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-input" value="<?= date('Y') ?>" min="2020" max="2099">
                </div>
                <div>
                    <label class="form-label">Employee (optional)</label>
                    <select name="employee_id" class="form-input">
                        <option value="">— All Employees —</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"
                    onclick="return confirm('Generate salary slips for selected period?')">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Generate
                </button>
            </div>
        </form>
    </div>

    <!-- Salary Slips List -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.06);">
            <h3 style="margin:0;font-size:14px;font-weight:700;">Salary Slips</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Month</th>
                    <th>Basic</th>
                    <th>Allowances</th>
                    <th>Deductions</th>
                    <th>Gross Pay</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($salarySlips)): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2);">No salary slips generated yet.</td></tr>
                <?php else: foreach ($salarySlips as $slip): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($slip['employee_name']) ?></div>
                        <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($slip['employee_code']) ?></div>
                    </td>
                    <td><?= date('M Y', strtotime($slip['salary_month'])) ?></td>
                    <td>৳<?= number_format((float)$slip['basic_salary'], 0) ?></td>
                    <td style="color:var(--green);">৳<?= number_format((float)$slip['allowances'], 0) ?></td>
                    <td style="color:var(--red);">৳<?= number_format((float)$slip['deductions'], 0) ?></td>
                    <td style="font-weight:700;">৳<?= number_format((float)$slip['gross_pay'], 0) ?></td>
                    <td>
                        <?php if ($slip['payment_status'] === 'paid'): ?>
                            <span class="badge badge-green">Paid</span>
                        <?php else: ?>
                            <span class="badge badge-yellow">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('hr/payroll/slip/' . $slip['id']) ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;">
                            <i class="fa-solid fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
