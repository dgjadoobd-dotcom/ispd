<?php // views/hr/employee_form.php
$isEdit = isset($employee);
$emp    = $employee ?? [];
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Edit Employee' : 'Add Employee' ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('hr/employees') ?>" style="color:var(--blue);text-decoration:none;"><i class="fa-solid fa-users"></i> Employees</a>
            &rsaquo; <?= $isEdit ? 'Edit' : 'New' ?>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? base_url('hr/employees/update/' . $emp['id']) : base_url('hr/employees/store') ?>">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;" class="fade-in">

    <!-- Left column -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card" style="padding:20px;">
            <h3 style="margin:0 0 16px;font-size:14px;font-weight:700;color:var(--text1);">Personal Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="full_name" class="form-input" required value="<?= htmlspecialchars($emp['full_name'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($emp['phone'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($emp['email'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">NID Number</label>
                    <input type="text" name="nid_number" class="form-input" value="<?= htmlspecialchars($emp['nid_number'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-input" value="<?= htmlspecialchars($emp['joining_date'] ?? '') ?>">
                </div>
                <?php if ($isEdit): ?>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active"     <?= ($emp['status'] ?? '') === 'active'     ? 'selected' : '' ?>>Active</option>
                        <option value="inactive"   <?= ($emp['status'] ?? '') === 'inactive'   ? 'selected' : '' ?>>Inactive</option>
                        <option value="terminated" <?= ($emp['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="padding:20px;">
            <h3 style="margin:0 0 16px;font-size:14px;font-weight:700;color:var(--text1);">Salary Information</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Basic Salary (৳)</label>
                    <input type="number" name="basic_salary" class="form-input" step="0.01" min="0" value="<?= htmlspecialchars($emp['basic_salary'] ?? '0') ?>">
                </div>
                <div>
                    <label class="form-label">Allowances (৳)</label>
                    <input type="number" name="allowances" class="form-input" step="0.01" min="0" value="<?= htmlspecialchars($emp['allowances'] ?? '0') ?>">
                </div>
                <div>
                    <label class="form-label">Bank Account</label>
                    <input type="text" name="bank_account" class="form-input" value="<?= htmlspecialchars($emp['bank_account'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-input" value="<?= htmlspecialchars($emp['bank_name'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="card" style="padding:20px;">
            <h3 style="margin:0 0 16px;font-size:14px;font-weight:700;color:var(--text1);">Emergency Contact</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-input" value="<?= htmlspecialchars($emp['emergency_contact_name'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="emergency_contact" class="form-input" value="<?= htmlspecialchars($emp['emergency_contact'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card" style="padding:20px;">
            <h3 style="margin:0 0 16px;font-size:14px;font-weight:700;color:var(--text1);">Organisation</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Branch <span style="color:var(--red)">*</span></label>
                    <select name="branch_id" class="form-input" required>
                        <option value="">— Select Branch —</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($emp['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-input">
                        <option value="">— Select Department —</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($emp['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Designation</label>
                    <select name="designation_id" class="form-input">
                        <option value="">— Select Designation —</option>
                        <?php foreach ($designations as $dg): ?>
                        <option value="<?= $dg['id'] ?>" <?= ($emp['designation_id'] ?? '') == $dg['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dg['title']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card" style="padding:20px;">
            <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="fa-solid fa-save"></i> <?= $isEdit ? 'Update Employee' : 'Create Employee' ?>
            </button>
            <a href="<?= base_url('hr/employees') ?>" class="btn btn-ghost" style="width:100%;margin-top:8px;text-align:center;">
                Cancel
            </a>
        </div>
    </div>

</div>
</form>
