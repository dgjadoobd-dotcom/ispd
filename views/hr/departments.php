<?php // views/hr/departments.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Departments</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-sitemap" style="color:var(--blue)"></i> HR &amp; Payroll</div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="document.getElementById('addDeptModal').classList.add('open')">
            <i class="fa-solid fa-plus"></i> Add Department
        </button>
        <a href="<?= base_url('hr/employees') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Employees</a>
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

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr><th>Department</th><th>Branch</th><th>Head of Department</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($departments)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text2);">No departments found. Add your first department.</td></tr>
            <?php else: foreach ($departments as $dept): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($dept['name']) ?></div>
                    <?php if (!empty($dept['description'])): ?>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($dept['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($dept['branch_name'] ?? '—') ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($dept['head_name'] ?? '—') ?></td>
                <td>
                    <?php if ($dept['is_active']): ?>
                        <span class="badge badge-green">Active</span>
                    <?php else: ?>
                        <span class="badge badge-yellow">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;"
                            onclick="editDept(<?= htmlspecialchars(json_encode($dept)) ?>)">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" action="<?= base_url('hr/departments/delete/' . $dept['id']) ?>" style="display:inline;"
                            onsubmit="return confirm('Delete this department?')">
                            <button type="submit" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;color:var(--red);">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Department Modal -->
<div class="modal-overlay" id="addDeptModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title">Add Department</h3>
            <button class="modal-close" onclick="document.getElementById('addDeptModal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('hr/departments/store') ?>">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Branch <span style="color:var(--red)">*</span></label>
                    <select name="branch_id" class="form-input" required>
                        <option value="">— Select Branch —</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Head of Department</label>
                    <select name="head_of_department" class="form-input">
                        <option value="">— None —</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2" style="resize:vertical;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addDeptModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal-overlay" id="editDeptModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Department</h3>
            <button class="modal-close" onclick="document.getElementById('editDeptModal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" id="editDeptForm" action="">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" id="editDeptName" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Branch</label>
                    <select name="branch_id" id="editDeptBranch" class="form-input">
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Head of Department</label>
                    <select name="head_of_department" id="editDeptHead" class="form-input">
                        <option value="">— None —</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" id="editDeptDesc" class="form-input" rows="2" style="resize:vertical;"></textarea>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="is_active" id="editDeptActive" class="form-input">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editDeptModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDept(dept) {
    document.getElementById('editDeptForm').action = '<?= base_url('hr/departments/update/') ?>' + dept.id;
    document.getElementById('editDeptName').value   = dept.name || '';
    document.getElementById('editDeptBranch').value = dept.branch_id || '';
    document.getElementById('editDeptHead').value   = dept.head_of_department || '';
    document.getElementById('editDeptDesc').value   = dept.description || '';
    document.getElementById('editDeptActive').value = dept.is_active;
    document.getElementById('editDeptModal').classList.add('open');
}
</script>
