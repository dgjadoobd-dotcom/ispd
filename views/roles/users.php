<?php // views/roles/users.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Users — <?= htmlspecialchars($role['display_name']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('roles') ?>" style="color:var(--blue);text-decoration:none;">Roles</a> ›
            <a href="<?= base_url("roles/edit/{$role['id']}") ?>" style="color:var(--blue);text-decoration:none;"><?= htmlspecialchars($role['display_name']) ?></a>
            › Users
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="document.getElementById('assignModal').classList.add('open')">
            <i class="fa-solid fa-user-plus"></i> Assign User
        </button>
        <a href="<?= base_url("roles/edit/{$role['id']}") ?>" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> Back to Role
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:10px;color:var(--green);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['success']) ?>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;color:var(--red);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['error']) ?>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr><th>User</th><th>Username</th><th>Branch</th><th>Status</th><th>Last Login</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text2);">
                <i class="fa-solid fa-users" style="font-size:32px;display:block;margin-bottom:10px;opacity:.3;"></i>
                No users assigned to this role yet.
            </td></tr>
            <?php else: foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,var(--blue),var(--purple));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                            <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($u['full_name']) ?></div>
                            <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($u['username']) ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($u['branch_name'] ?? '—') ?></td>
                <td><span class="badge <?= $u['is_active'] ? 'badge-green' : 'badge-gray' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td style="font-size:12px;color:var(--text2);">
                    <?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : 'Never' ?>
                </td>
                <td>
                    <!-- Change role for this user -->
                    <form method="POST" action="<?= base_url('roles/assign-user') ?>" style="display:flex;gap:6px;align-items:center;">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="redirect" value="<?= base_url("roles/users/{$role['id']}") ?>">
                        <select name="role_id" class="form-input" style="width:160px;padding:6px 10px;font-size:12px;">
                            <?php foreach ($allRoles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $r['id'] == $u['role_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['display_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-ghost btn-sm" title="Change role">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Assign User Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus" style="color:var(--blue);margin-right:8px;"></i>Assign User to Role</div>
            <button class="icon-btn" onclick="document.getElementById('assignModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('roles/assign-user') ?>">
            <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
            <input type="hidden" name="redirect" value="<?= base_url("roles/users/{$role['id']}") ?>">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="form-label">Search User</label>
                    <input type="text" id="userSearch" class="form-input" placeholder="Type name or username..."
                           oninput="filterUsers(this.value)">
                </div>
                <div>
                    <label class="form-label">Select User <span style="color:var(--red)">*</span></label>
                    <select name="user_id" id="userSelect" class="form-input" required size="8" style="height:auto;">
                        <?php
                        $allUsers = Database::getInstance()->fetchAll(
                            "SELECT u.id, u.full_name, u.username, r.display_name as role_name
                             FROM users u LEFT JOIN roles r ON r.id=u.role_id
                             WHERE u.is_active=1 ORDER BY u.full_name"
                        );
                        foreach ($allUsers as $u):
                        ?>
                        <option value="<?= $u['id'] ?>" data-search="<?= strtolower($u['full_name'].' '.$u['username']) ?>">
                            <?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['username']) ?>) — <?= htmlspecialchars($u['role_name'] ?? '—') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="font-size:12px;color:var(--text2);">
                    <i class="fa-solid fa-info-circle" style="margin-right:4px;"></i>
                    This will change the selected user's role to <strong><?= htmlspecialchars($role['display_name']) ?></strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('assignModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-check"></i> Assign Role</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterUsers(q) {
    const sel = document.getElementById('userSelect');
    q = q.toLowerCase();
    Array.from(sel.options).forEach(opt => {
        opt.style.display = opt.dataset.search.includes(q) ? '' : 'none';
    });
}
</script>
