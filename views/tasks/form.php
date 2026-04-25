<?php
/**
 * Task Create / Edit Form
 * Requirements 4.3, 4.6
 */
$isEdit  = isset($task);
$t       = $task ?? [];
$formUrl = $isEdit
    ? base_url('tasks/update/' . $t['id'])
    : base_url('tasks/store');
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-list-check" style="color:var(--blue);margin-right:10px;"></i>
            <?= $isEdit ? 'Edit Task #' . $t['id'] : 'New Task' ?>
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('tasks/list') ?>">Tasks</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span><?= $isEdit ? 'Edit' : 'New' ?></span>
        </div>
    </div>
    <a href="<?= base_url('tasks/list') ?>" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in fade-in-delay-1" style="max-width:800px;">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:15px;font-weight:700;">Task Details</h3>
    </div>
    <form method="POST" action="<?= $formUrl ?>">
        <div style="padding:24px;display:grid;gap:20px;">

            <!-- Title -->
            <div>
                <label class="form-label">Title <span style="color:var(--red);">*</span></label>
                <input type="text" name="title" class="form-input" required
                       placeholder="Task title"
                       value="<?= htmlspecialchars($t['title'] ?? '') ?>">
            </div>

            <!-- Description -->
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="5"
                          placeholder="Detailed description..."><?= htmlspecialchars($t['description'] ?? '') ?></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <!-- Priority -->
                <div>
                    <label class="form-label">Priority <span style="color:var(--red);">*</span></label>
                    <select name="priority" class="form-input" required>
                        <?php foreach (['urgent' => 'Urgent', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($t['priority'] ?? 'medium') === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status (edit only) -->
                <?php if ($isEdit): ?>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <?php foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($t['status'] ?? 'pending') === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <!-- Due Date -->
            <div>
                <label class="form-label">Due Date</label>
                <input type="datetime-local" name="due_date" class="form-input"
                       value="<?= !empty($t['due_date']) ? date('Y-m-d\TH:i', strtotime($t['due_date'])) : '' ?>">
            </div>

            <!-- Assigned To (edit only) -->
            <?php if ($isEdit && PermissionHelper::hasPermission('task.assign')): ?>
            <div>
                <label class="form-label">Assign To</label>
                <select name="assigned_to_id" class="form-input">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= (int)($t['assigned_to'] ?? 0) === (int)$emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Buttons -->
            <div style="display:flex;gap:12px;padding-top:12px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> <?= $isEdit ? 'Update Task' : 'Create Task' ?>
                </button>
                <a href="<?= base_url('tasks/list') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>