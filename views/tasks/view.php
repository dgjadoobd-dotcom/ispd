<?php
/**
 * Task Detail View
 * Requirements 4.3, 4.4, 4.5, 4.6, 4.7
 */
$statusColors = [
    'pending'     => 'badge-yellow',
    'in_progress' => 'badge-blue',
    'completed'  => 'badge-green',
    'cancelled'   => 'badge-gray',
];
$priorityColors = [
    'urgent' => 'badge-red',
    'high'  => 'badge-yellow',
    'medium' => 'badge-blue',
    'low'   => 'badge-gray',
];

$dueDate  = !empty($task['due_date']) ? new DateTime($task['due_date']) : null;
$now     = new DateTime();
$isOverdue = $dueDate && $now > $dueDate && !in_array($task['status'], ['completed','cancelled']);
$canEdit    = PermissionHelper::hasPermission('task.edit');
$canAssign  = PermissionHelper::hasPermission('task.assign');
$canDelete  = PermissionHelper::hasPermission('task.delete');
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-list-check" style="color:var(--blue);margin-right:10px;"></i>
            Task #<?= $task['id'] ?>
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('tasks/list') ?>">Tasks</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>#<?= $task['id'] ?></span>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($canEdit && !in_array($task['status'], ['completed','cancelled'])): ?>
        <a href="<?= base_url('tasks/edit/' . $task['id']) ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-pen"></i> Edit
        </a>
        <?php endif; ?>
        <a href="<?= base_url('tasks/list') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php foreach (['success','error'] as $msgType): ?>
<?php if (!empty($_SESSION[$msgType])): ?>
<?php
$msgStyles = [
    'success' => 'background:#dcfce7;border-color:#86efac;color:#15803d;',
    'error'   => 'background:#fee2e2;border-color:#fecaca;color:#b91c1c;',
];
$msgIcons = ['success' => 'circle-check', 'error' => 'circle-xmark'];
?>
<div style="<?= $msgStyles[$msgType] ?>border:1px solid;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-<?= $msgIcons[$msgType] ?>"></i> <?= htmlspecialchars($_SESSION[$msgType]) ?>
</div>
<?php unset($_SESSION[$msgType]); endif; endforeach; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;">

    <!-- Left: Task details -->
    <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Task Info Card -->
        <div class="card fade-in">
            <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h2 style="font-size:17px;font-weight:700;"><?= htmlspecialchars($task['title']) ?></h2>
                    <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
                        <span class="badge <?= $statusColors[$task['status']] ?? 'badge-gray' ?>">
                            <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                        </span>
                        <span class="badge <?= $priorityColors[$task['priority']] ?? 'badge-gray' ?>">
                            <?= ucfirst($task['priority']) ?> Priority
                        </span>
                    </div>
                </div>
                <?php if ($dueDate): ?>
                <div style="text-align:right;">
                    <div style="font-size:11px;color:var(--text2);margin-bottom:2px;">Due Date</div>
                    <div style="font-size:13px;font-weight:700;<?= $isOverdue ? 'color:#dc2626;' : 'color:var(--text);' ?>">
                        <?= $dueDate->format('d M Y H:i') ?>
                    </div>
                    <?php if ($isOverdue): ?>
                    <div style="font-size:11px;color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i> Overdue</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="padding:20px 24px;">
                <?php if (!empty($task['description'])): ?>
                <div style="margin-bottom:20px;">
                    <div style="font-size:12px;color:var(--text2);margin-bottom:6px;">Description</div>
                    <div style="font-size:14px;line-height:1.6;white-space:pre-wrap;"><?= htmlspecialchars($task['description']) ?></div>
                </div>
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                    <div>
                        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Created By</div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($task['created_by_name'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Assigned To</div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($task['assigned_to_name'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Branch</div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($task['branch_name'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Update -->
        <?php if ($canEdit && !in_array($task['status'], ['completed','cancelled'])): ?>
        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Update Status</h3>
            </div>
            <div style="padding:16px 20px;">
                <form method="POST" action="<?= base_url('tasks/status/' . $task['id']) ?>" style="display:flex;gap:12px;align-items:center;">
                    <select name="status" class="form-input" style="flex:1;">
                        <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $task['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-check"></i> Update
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Task History -->
        <?php if (!empty($task['history'])): ?>
        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Activity History</h3>
            </div>
            <div style="padding:16px 20px;">
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($task['history'] as $h): ?>
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div style="width:28px;height:28px;border-radius:50%;background:var(--bg2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-clock" style="font-size:12px;color:var(--text2);"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px;">
                                <strong><?= htmlspecialchars($h['user_name'] ?? 'System') ?></strong>
                                <?= htmlspecialchars($h['action']) ?>
                                <?php if (!empty($h['old_value']) || !empty($h['new_value'])): ?>
                                <span style="color:var(--text2);">
                                    (<?= htmlspecialchars($h['old_value'] ?? '') ?> → <?= htmlspecialchars($h['new_value'] ?? '') ?>)
                                </span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:11px;color:var(--text2);">
                                <?= date('d M Y H:i', strtotime($h['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: Sidebar -->
    <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Quick Actions -->
        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Quick Actions</h3>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
                <?php if ($canAssign && !in_array($task['status'], ['completed','cancelled'])): ?>
                <button type="button" class="btn btn-ghost btn-sm" style="width:100%;justify-content:flex-start;" onclick="document.getElementById('assignModal').showModal();">
                    <i class="fa-solid fa-user-plus"></i> Assign To
                </button>
                <?php endif; ?>

                <?php if ($canEdit && !in_array($task['status'], ['completed','cancelled'])): ?>
                <a href="<?= base_url('tasks/edit/' . $task['id']) ?>" class="btn btn-ghost btn-sm" style="width:100%;justify-content:flex-start;">
                    <i class="fa-solid fa-pen"></i> Edit Task
                </a>
                <?php endif; ?>

                <?php if ($canDelete): ?>
                <form method="POST" action="<?= base_url('tasks/delete/' . $task['id']) ?>" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                    <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;justify-content:flex-start;color:#dc2626;">
                        <i class="fa-solid fa-trash"></i> Delete Task
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info -->
        <div class="card fade-in">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">Details</h3>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                <div>
                    <div style="font-size:11px;color:var(--text2);">ID</div>
                    <div style="font-size:13px;font-weight:600;">#<?= $task['id'] ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text2);">Created</div>
                    <div style="font-size:13px;"><?= date('d M Y H:i', strtotime($task['created_at'])) ?></div>
                </div>
                <?php if (!empty($task['updated_at'])): ?>
                <div>
                    <div style="font-size:11px;color:var(--text2);">Updated</div>
                    <div style="font-size:13px;"><?= date('d M Y H:i', strtotime($task['updated_at'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($task['completed_at'])): ?>
                <div>
                    <div style="font-size:11px;color:var(--text2);">Completed</div>
                    <div style="font-size:13px;color:#15803d;"><?= date('d M Y H:i', strtotime($task['completed_at'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Assign Modal -->
<?php if ($canAssign): ?>
<dialog id="assignModal" style="border:none;border-radius:12px;padding:0;max-width:400px;width:90%;">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:16px;font-weight:700;">Assign Task</h3>
    </div>
    <form method="POST" action="<?= base_url('tasks/assign/' . $task['id']) ?>">
        <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="form-label">Select Employee</label>
                <select name="employee_id" class="form-input" required>
                    <option value="">— Select —</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3" placeholder="Optional notes..."></textarea>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('assignModal').close();">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign</button>
            </div>
        </div>
    </form>
</dialog>
<?php endif; ?>

<style>
dialog::backdrop { background: rgba(0,0,0,0.5); }
</style>