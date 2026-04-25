<?php // views/roles/history.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Role Change History</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('roles') ?>" style="color:var(--blue);text-decoration:none;">Roles</a> ›
            <span><?= htmlspecialchars($user['full_name']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= base_url('roles') ?>" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> Back to Roles
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

<!-- User info card -->
<div class="card fade-in" style="padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
    <div style="width:44px;height:44px;border-radius:10px;background:var(--bg3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fa-solid fa-user" style="color:var(--blue);font-size:18px;"></i>
    </div>
    <div>
        <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($user['full_name']) ?></div>
        <div style="font-size:12px;color:var(--text2);">@<?= htmlspecialchars($user['username']) ?></div>
    </div>
    <div style="margin-left:auto;font-size:12px;color:var(--text2);">
        <?= count($history) ?> change<?= count($history) !== 1 ? 's' : '' ?> recorded
    </div>
</div>

<!-- History table -->
<div class="card fade-in" style="overflow:hidden;">
    <?php if (empty($history)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-clock-rotate-left" style="font-size:32px;margin-bottom:12px;display:block;opacity:0.4;"></i>
        <div style="font-size:14px;">No role change history found for this user.</div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%;">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Old Role</th>
                    <th>New Role</th>
                    <th>Changed By</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $entry): ?>
                <tr>
                    <td>
                        <span style="font-weight:600;"><?= htmlspecialchars($entry['user_name'] ?? '—') ?></span>
                    </td>
                    <td>
                        <?php if (!empty($entry['old_role_name'])): ?>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:rgba(239,68,68,0.1);color:var(--red);font-size:12px;font-weight:600;">
                            <i class="fa-solid fa-shield-halved" style="font-size:10px;"></i>
                            <?= htmlspecialchars($entry['old_role_name']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:var(--text2);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:rgba(37,99,235,0.1);color:var(--blue);font-size:12px;font-weight:600;">
                            <i class="fa-solid fa-shield-halved" style="font-size:10px;"></i>
                            <?= htmlspecialchars($entry['new_role_name'] ?? '—') ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-size:13px;"><?= htmlspecialchars($entry['changed_by_name'] ?? '—') ?></span>
                    </td>
                    <td>
                        <?php if (!empty($entry['reason'])): ?>
                        <span style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($entry['reason']) ?></span>
                        <?php else: ?>
                        <span style="color:var(--text2);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size:12px;color:var(--text2);">
                            <?= htmlspecialchars(date('d M Y, H:i', strtotime($entry['changed_at']))) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
