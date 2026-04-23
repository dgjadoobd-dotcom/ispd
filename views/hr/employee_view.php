<?php // views/hr/employee_view.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($employee['full_name']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('hr/employees') ?>" style="color:var(--blue);text-decoration:none;"><i class="fa-solid fa-users"></i> Employees</a>
            &rsaquo; <?= htmlspecialchars($employee['employee_code']) ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= base_url('hr/employees/edit/' . $employee['id']) ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
        <a href="<?= base_url('hr/attendance?employee_id=' . $employee['id']) ?>" class="btn btn-ghost"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
        <a href="<?= base_url('hr/employees') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="fade-in">

    <!-- Employee Details -->
    <div class="card" style="padding:20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;"><i class="fa-solid fa-id-card" style="color:var(--blue);margin-right:8px;"></i>Employee Details</div>
        <table style="width:100%;border-collapse:collapse;">
            <?php $rows = [
                'Employee Code' => $employee['employee_code'],
                'Department'    => $employee['department_name'] ?? '—',
                'Designation'   => $employee['designation_title'] ?? '—',
                'Branch'        => $employee['branch_name'] ?? '—',
                'Phone'         => $employee['phone'] ?? '—',
                'Email'         => $employee['email'] ?? '—',
                'NID'           => $employee['nid_number'] ?? '—',
                'Joining Date'  => $employee['joining_date'] ? date('d M Y', strtotime($employee['joining_date'])) : '—',
                'Bank Account'  => $employee['bank_account'] ?? '—',
                'Bank Name'     => $employee['bank_name'] ?? '—',
                'Emergency Contact' => ($employee['emergency_contact_name'] ?? '') . ($employee['emergency_contact'] ? ' — ' . $employee['emergency_contact'] : ''),
            ]; foreach ($rows as $label => $val): ?>
            <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:8px 0;font-size:12px;color:var(--text2);width:40%;"><?= $label ?></td>
                <td style="padding:8px 0;font-size:13px;font-weight:500;"><?= htmlspecialchars((string)$val) ?: '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td style="padding:8px 0;font-size:12px;color:var(--text2);">Status</td>
                <td style="padding:8px 0;">
                    <?php if ($employee['status'] === 'active'): ?>
                        <span class="badge badge-green">Active</span>
                    <?php elseif ($employee['status'] === 'inactive'): ?>
                        <span class="badge badge-yellow">Inactive</span>
                    <?php else: ?>
                        <span class="badge badge-red">Terminated</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Salary & Leave -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Salary Summary -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;"><i class="fa-solid fa-money-bill-wave" style="color:var(--green);margin-right:8px;"></i>Salary</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div style="text-align:center;padding:12px;background:rgba(37,99,235,.06);border-radius:8px;">
                    <div style="font-size:11px;color:var(--text2);">Basic</div>
                    <div style="font-size:18px;font-weight:700;">৳<?= number_format((float)$employee['basic_salary'], 0) ?></div>
                </div>
                <div style="text-align:center;padding:12px;background:rgba(22,163,74,.06);border-radius:8px;">
                    <div style="font-size:11px;color:var(--text2);">Allowances</div>
                    <div style="font-size:18px;font-weight:700;color:var(--green);">৳<?= number_format((float)$employee['allowances'], 0) ?></div>
                </div>
                <div style="text-align:center;padding:12px;background:rgba(37,99,235,.06);border-radius:8px;">
                    <div style="font-size:11px;color:var(--text2);">Gross</div>
                    <div style="font-size:18px;font-weight:700;color:var(--blue);">৳<?= number_format((float)$employee['basic_salary'] + (float)$employee['allowances'], 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Leave Balances (Req 2.10) -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;"><i class="fa-solid fa-calendar-xmark" style="color:var(--yellow);margin-right:8px;"></i>Leave Balances (<?= date('Y') ?>)</div>
            <?php if (empty($leaveBalances)): ?>
            <p style="color:var(--text2);font-size:13px;">No leave balances recorded.</p>
            <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
                <?php foreach ($leaveBalances as $lb):
                    $used      = (int)$lb['used_days'];
                    $total     = (int)$lb['total_days'];
                    $remaining = (int)$lb['remaining_days'];
                    $pct       = $total > 0 ? min(100, round($used / $total * 100)) : 0;
                ?>
                <div style="text-align:center;padding:12px;background:var(--bg3);border-radius:8px;border:1px solid var(--border);">
                    <div style="font-size:11px;color:var(--text2);text-transform:capitalize;margin-bottom:6px;"><?= htmlspecialchars($lb['leave_type']) ?></div>
                    <div style="font-size:20px;font-weight:700;color:var(--blue);"><?= $remaining ?></div>
                    <div style="font-size:10px;color:var(--text2);">remaining</div>
                    <div class="progress-bar" style="margin-top:6px;">
                        <div class="progress-fill" style="width:<?= $pct ?>%;background:var(--blue);"></div>
                    </div>
                    <div style="font-size:10px;color:var(--text2);margin-top:4px;"><?= $used ?> used / <?= $total ?> total</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Update Leave Balance Form -->
            <details style="margin-top:4px;">
                <summary style="font-size:12px;color:var(--blue);cursor:pointer;font-weight:600;">Update Leave Balance</summary>
                <form method="POST" action="<?= base_url('hr/employees/leave/' . $employee['id']) ?>" style="margin-top:12px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end;">
                        <div>
                            <label class="form-label" style="font-size:11px;">Type</label>
                            <select name="leave_type" class="form-input" style="padding:7px 10px;font-size:12px;">
                                <option value="annual">Annual</option>
                                <option value="sick">Sick</option>
                                <option value="casual">Casual</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:11px;">Total Days</label>
                            <input type="number" name="days" class="form-input" min="0" max="365" value="0" style="padding:7px 10px;font-size:12px;" required>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:11px;">Used Days</label>
                            <input type="number" name="used_days" class="form-input" min="0" max="365" value="0" style="padding:7px 10px;font-size:12px;">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save</button>
                    </div>
                </form>
            </details>
        </div>
    </div>
</div>

<!-- Salary Slips -->
<?php if (!empty($salarySlips)): ?>
<div class="card fade-in" style="margin-top:16px;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:13px;font-weight:700;"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--blue);margin-right:8px;"></i>Salary Slips</div>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Month</th><th>Basic</th><th>Allowances</th><th>Deductions</th><th>Gross Pay</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($salarySlips as $slip): ?>
            <tr>
                <td style="font-weight:600;"><?= date('F Y', strtotime($slip['salary_month'])) ?></td>
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
                    <a href="<?= base_url('hr/payroll/slip/' . $slip['id']) ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Performance Appraisals (Req 2.8) -->
<div class="card fade-in" style="margin-top:16px;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:13px;font-weight:700;"><i class="fa-solid fa-star" style="color:var(--yellow);margin-right:8px;"></i>Performance Appraisals</div>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('addAppraisalModal').classList.add('open')">
            <i class="fa-solid fa-plus"></i> Add Appraisal
        </button>
    </div>
    <?php
    // Fetch appraisals for this employee
    $appraisals = [];
    try {
        $appraisals = Database::getInstance()->fetchAll(
            "SELECT pa.*, u.full_name AS reviewer_name
             FROM performance_appraisals pa
             LEFT JOIN users u ON u.id = pa.reviewer_id
             WHERE pa.employee_id = ?
             ORDER BY pa.review_period DESC",
            [$employee['id']]
        );
    } catch (\Throwable $e) { /* table may not exist yet */ }
    ?>
    <?php if (empty($appraisals)): ?>
    <div style="padding:32px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-star" style="font-size:28px;opacity:.2;display:block;margin-bottom:10px;"></i>
        No appraisals recorded yet.
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Review Period</th><th>Rating</th><th>Reviewer</th><th>Comments</th></tr>
        </thead>
        <tbody>
            <?php foreach ($appraisals as $ap): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($ap['review_period']) ?></td>
                <td>
                    <?php
                    $rating = (int)$ap['rating'];
                    $colors = [1=>'var(--red)',2=>'var(--red)',3=>'var(--yellow)',4=>'var(--green)',5=>'var(--green)'];
                    ?>
                    <span style="font-size:16px;color:<?= $colors[$rating] ?? 'var(--text2)' ?>;">
                        <?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?>
                    </span>
                    <span style="font-size:12px;color:var(--text2);margin-left:4px;"><?= $rating ?>/5</span>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($ap['reviewer_name'] ?? $ap['reviewer'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($ap['comments'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Add Appraisal Modal (Req 2.8) -->
<div class="modal-overlay" id="addAppraisalModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-star" style="color:var(--yellow);margin-right:8px;"></i>Add Performance Appraisal</h3>
            <button onclick="document.getElementById('addAppraisalModal').classList.remove('open')" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text2);">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('hr/employees/appraisal/' . $employee['id']) ?>">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Review Period <span style="color:var(--red)">*</span></label>
                    <input type="text" name="review_period" class="form-input" placeholder="e.g. Q1 2025 or Jan–Jun 2025" required>
                </div>
                <div>
                    <label class="form-label">Rating (1–5) <span style="color:var(--red)">*</span></label>
                    <select name="rating" class="form-input" required>
                        <option value="">— Select Rating —</option>
                        <option value="5">5 — Excellent</option>
                        <option value="4">4 — Good</option>
                        <option value="3">3 — Satisfactory</option>
                        <option value="2">2 — Needs Improvement</option>
                        <option value="1">1 — Unsatisfactory</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Comments</label>
                    <textarea name="comments" class="form-input" rows="3" style="resize:vertical;" placeholder="Reviewer comments…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addAppraisalModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Appraisal</button>
            </div>
        </form>
    </div>
</div>
