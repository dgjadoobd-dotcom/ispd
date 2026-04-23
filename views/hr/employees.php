<?php // views/hr/employees.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Employees</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-users" style="color:var(--blue)"></i> HR &amp; Payroll</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= base_url('hr/employees/create') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Employee</a>
        <a href="<?= base_url('hr/departments') ?>" class="btn btn-ghost"><i class="fa-solid fa-sitemap"></i> Departments</a>
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

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;" class="fade-in">
    <?php
    $total    = count($employees);
    $active   = count(array_filter($employees, fn($e) => $e['status'] === 'active'));
    $inactive = count(array_filter($employees, fn($e) => $e['status'] === 'inactive'));
    $depts    = count(array_unique(array_column($employees, 'department_id')));
    ?>
    <div class="card stat-card" style="padding:14px;"><div class="stat-label">Total Employees</div><div class="stat-value" style="font-size:22px;"><?= $total ?></div></div>
    <div class="card stat-card" style="padding:14px;"><div class="stat-label">Active</div><div class="stat-value" style="font-size:22px;color:var(--green);"><?= $active ?></div></div>
    <div class="card stat-card" style="padding:14px;"><div class="stat-label">Inactive</div><div class="stat-value" style="font-size:22px;color:var(--yellow);"><?= $inactive ?></div></div>
    <div class="card stat-card" style="padding:14px;"><div class="stat-label">Departments</div><div class="stat-value" style="font-size:22px;"><?= count($departments) ?></div></div>
</div>

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Designation</th>
                <th>Branch</th>
                <th>Phone</th>
                <th>Basic Salary</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($employees)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2);">No employees found. Add your first employee.</td></tr>
            <?php else: foreach ($employees as $emp): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($emp['full_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);font-family:monospace;"><?= htmlspecialchars($emp['employee_code']) ?></div>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($emp['department_name'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($emp['designation_title'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($emp['branch_name'] ?? '—') ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($emp['phone'] ?? '—') ?></td>
                <td style="font-weight:600;">৳<?= number_format((float)$emp['basic_salary'], 0) ?></td>
                <td>
                    <?php if ($emp['status'] === 'active'): ?>
                        <span class="badge badge-green">Active</span>
                    <?php elseif ($emp['status'] === 'inactive'): ?>
                        <span class="badge badge-yellow">Inactive</span>
                    <?php else: ?>
                        <span class="badge badge-red">Terminated</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="<?= base_url('hr/employees/view/' . $emp['id']) ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;"><i class="fa-solid fa-eye"></i></a>
                        <a href="<?= base_url('hr/employees/edit/' . $emp['id']) ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;"><i class="fa-solid fa-pen"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
