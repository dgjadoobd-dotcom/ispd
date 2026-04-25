<?php // views/superadmin/users/index.php ?>

<!-- Page Header -->
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">User Management</h1>
        <div class="page-breadcrumb">Manage all system users and their roles</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('createUserModal')">
        <i class="fa-solid fa-user-plus"></i> Add User
    </button>
</div>

<!-- Filters -->
<div class="card fade-in" style="padding:16px 20px;margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label class="form-label" style="font-size:11px;">Search</label>
            <input type="text" name="search" class="form-input" placeholder="Name, username, email…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div style="min-width:150px;">
            <label class="form-label" style="font-size:11px;">Role</label>
            <select name="role" class="form-input">
                <option value="">All Roles</option>
                <?php foreach ($roles as $r): ?>
                <option value="<?= htmlspecialchars($r['name']) ?>" <?= ($_GET['role'] ?? '') === $r['name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['display_name'] ?? $r['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:130px;">
            <label class="form-label" style="font-size:11px;">Status</label>
            <select name="status" class="form-input">
                <option value="">All</option>
                <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-magnifying-glass"></i> Filter
        </button>
        <a href="<?= base_url('superadmin/users') ?>" class="btn btn-ghost btn-sm">Reset</a>
    </form>
</div>

<!-- Users Table -->
<div class="card fade-in" style="overflow:hidden;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:14px;font-weight:700;"><?= count($users) ?> Users Found</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text2);">
                    <i class="fa-solid fa-users" style="font-size:24px;margin-bottom:8px;display:block;opacity:0.3;"></i>
                    No users found
                </td></tr>
                <?php else: foreach ($users as $i => $u):
                    $initials = strtoupper(substr($u['full_name'] ?? $u['username'], 0, 2));
                    $roleColors = ['superadmin'=>'badge-purple','comadmin'=>'badge-blue','branch_admin'=>'badge-yellow','staff'=>'badge-gray'];
                    $roleBadge = $roleColors[$u['role_name'] ?? ''] ?? 'badge-gray';
                ?>
                <tr>
                    <td style="color:var(--text2);font-size:12px;"><?= $i + 1 ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></div>
                                <div style="font-size:11px;color:var(--text2);">@<?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u['email'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge <?= $roleBadge ?>"><?= htmlspecialchars($u['role_name'] ?? '—') ?></span></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($u['branch_name'] ?? '<span style="color:var(--text2);">All Branches</span>') ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                        <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px;"></i> Active</span>
                        <?php else: ?>
                        <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:7px;"></i> Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text2);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-ghost btn-xs" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-ghost btn-xs" onclick="resetPassword(<?= $u['id'] ?>)" title="Reset Password">
                                <i class="fa-solid fa-key"></i>
                            </button>
                            <button class="btn btn-ghost btn-xs" onclick="toggleUser(<?= $u['id'] ?>, <?= $u['is_active'] ?>)" title="<?= $u['is_active'] ? 'Disable' : 'Enable' ?>">
                                <i class="fa-solid fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                            </button>
                            <?php if ((int)($_SESSION['user_id'] ?? 0) !== (int)$u['id']): ?>
                            <button class="btn btn-danger btn-xs" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus" style="margin-right:8px;color:var(--purple);"></i>Create New User</div>
            <button class="btn btn-ghost btn-xs" onclick="closeModal('createUserModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="createUserForm">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="grid-column:1/-1;">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-input" required placeholder="John Doe">
                </div>
                <div>
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-input" required placeholder="johndoe">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="john@example.com">
                </div>
                <div>
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-input" required placeholder="Min 6 characters">
                </div>
                <div>
                    <label class="form-label">Role *</label>
                    <select name="role_id" class="form-input" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name'] ?? $r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-input">
                        <option value="">All Branches</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('createUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen" style="margin-right:8px;color:var(--purple);"></i>Edit User</div>
            <button class="btn btn-ghost btn-xs" onclick="closeModal('editUserModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="editUserForm">
            <input type="hidden" id="editUserId">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="grid-column:1/-1;">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" id="editFullName" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="editEmail" class="form-input">
                </div>
                <div>
                    <label class="form-label">New Password <span style="color:var(--text2);font-weight:400;">(leave blank to keep)</span></label>
                    <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current">
                </div>
                <div>
                    <label class="form-label">Role *</label>
                    <select name="role_id" id="editRoleId" class="form-input" required>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name'] ?? $r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Branch</label>
                    <select name="branch_id" id="editBranchId" class="form-input">
                        <option value="">All Branches</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetPwModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-key" style="margin-right:8px;color:var(--yellow);"></i>Reset Password</div>
            <button class="btn btn-ghost btn-xs" onclick="closeModal('resetPwModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="resetPwForm">
            <input type="hidden" id="resetPwUserId">
            <div class="modal-body">
                <label class="form-label">New Password *</label>
                <input type="password" name="password" id="resetPwInput" class="form-input" required placeholder="Min 6 characters">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('resetPwModal')">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-key"></i> Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
// Create user
document.getElementById('createUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const r = await fetch('<?= base_url('superadmin/users/store') ?>', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) { showToast(d.message, 'success'); closeModal('createUserModal'); setTimeout(() => location.reload(), 800); }
    else showToast(d.message, 'error');
});

// Edit user
function editUser(u) {
    document.getElementById('editUserId').value = u.id;
    document.getElementById('editFullName').value = u.full_name || '';
    document.getElementById('editEmail').value = u.email || '';
    document.getElementById('editRoleId').value = u.role_id || '';
    document.getElementById('editBranchId').value = u.branch_id || '';
    openModal('editUserModal');
}
document.getElementById('editUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('editUserId').value;
    const fd = new FormData(this);
    const r = await fetch(`<?= base_url('superadmin/users/update/') ?>${id}`, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) { showToast(d.message, 'success'); closeModal('editUserModal'); setTimeout(() => location.reload(), 800); }
    else showToast(d.message, 'error');
});

// Reset password
function resetPassword(id) {
    document.getElementById('resetPwUserId').value = id;
    document.getElementById('resetPwInput').value = '';
    openModal('resetPwModal');
}
document.getElementById('resetPwForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('resetPwUserId').value;
    const fd = new FormData(this);
    const r = await fetch(`<?= base_url('superadmin/users/reset-password/') ?>${id}`, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) { showToast(d.message, 'success'); closeModal('resetPwModal'); }
    else showToast(d.message, 'error');
});

// Toggle user
async function toggleUser(id, isActive) {
    if (!confirm(isActive ? 'Disable this user?' : 'Enable this user?')) return;
    const r = await fetch(`<?= base_url('superadmin/users/toggle/') ?>${id}`, { method: 'POST' });
    const d = await r.json();
    if (d.success) { showToast(d.message, 'success'); setTimeout(() => location.reload(), 600); }
    else showToast(d.message, 'error');
}

// Delete user
async function deleteUser(id, username) {
    if (!confirm(`Permanently delete user "${username}"? This cannot be undone.`)) return;
    const r = await fetch(`<?= base_url('superadmin/users/delete/') ?>${id}`, { method: 'POST' });
    const d = await r.json();
    if (d.success) { showToast(d.message, 'success'); setTimeout(() => location.reload(), 600); }
    else showToast(d.message, 'error');
}
</script>
