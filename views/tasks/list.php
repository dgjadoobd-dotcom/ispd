<?php
/**
 * Task List View
 * Requirement 4.1: paginated task list with filters
 */
$tasks     = $result['data']       ?? [];
$total     = $result['total']      ?? 0;
$page      = $result['page']        ?? 1;
$totalPages = $result['totalPages'] ?? 1;

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
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-list-check" style="color:var(--blue);margin-right:10px;"></i>Tasks</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Task List</span>
        </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <?php if (PermissionHelper::hasPermission('task.reports')): ?>
        <a href="<?= base_url('tasks/reports') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-chart-bar"></i> Reports
        </a>
        <?php endif; ?>
        <?php if (PermissionHelper::hasPermission('task.view')): ?>
        <a href="<?= base_url('tasks/calendar') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-regular fa-calendar"></i> Calendar
        </a>
        <?php endif; ?>
        <?php if (PermissionHelper::hasPermission('task.create')): ?>
        <a href="<?= base_url('tasks/create') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Task
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']) ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- Filters -->
<div class="card fade-in fade-in-delay-1" style="padding:16px;margin-bottom:20px;">
    <form method="GET" action="<?= base_url('tasks/list') ?>" style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:12px;align-items:end;">
        <div>
            <label class="form-label" style="font-size:12px;">Status</label>
            <select name="status" class="form-input" style="padding:8px 12px;font-size:13px;">
                <option value="">All Statuses</option>
                <?php foreach (['pending','in_progress','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Priority</label>
            <select name="priority" class="form-input" style="padding:8px 12px;font-size:13px;">
                <option value="">All Priorities</option>
                <?php foreach (['urgent','high','medium','low'] as $p): ?>
                <option value="<?= $p ?>" <?= ($_GET['priority'] ?? '') === $p ? 'selected' : '' ?>>
                    <?= ucfirst($p) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Assigned To</label>
            <select name="assigned_to" class="form-input" style="padding:8px 12px;font-size:13px;">
                <option value="">All Employees</option>
                <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" <?= (int)($_GET['assigned_to'] ?? 0) === (int)$emp['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($emp['full_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Search</label>
            <input type="text" name="search" class="form-input" style="padding:8px 12px;font-size:13px;"
                   placeholder="Title, description..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="<?= base_url('tasks/list') ?>" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-xmark"></i> Clear
            </a>
        </div>
    </form>
</div>

<!-- Task Table -->
<div class="card fade-in fade-in-delay-2">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-weight:700;font-size:15px;">
            <?= number_format($total) ?> task<?= $total !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($tasks)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);">
        <i class="fa-solid fa-list-check" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
        <p style="font-size:15px;">No tasks found.</p>
        <?php if (PermissionHelper::hasPermission('task.create')): ?>
        <a href="<?= base_url('tasks/create') ?>" class="btn btn-primary btn-sm" style="margin-top:12px;">
            <i class="fa-solid fa-plus"></i> Create First Task
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $t): ?>
                <?php
                    $dueDate = !empty($t['due_date']) ? new DateTime($t['due_date']) : null;
                    $now    = new DateTime();
                    $isOverdue = $dueDate && $now > $dueDate && !in_array($t['status'], ['completed','cancelled']);
                ?>
                <tr>
                    <td style="font-weight:700;color:var(--blue);">#<?= $t['id'] ?></td>
                    <td style="max-width:250px;">
                        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($t['title']) ?>">
                            <?= htmlspecialchars($t['title']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $priorityColors[$t['priority']] ?? 'badge-gray' ?>">
                            <?= ucfirst($t['priority']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $statusColors[$t['status']] ?? 'badge-gray' ?>">
                            <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                        </span>
                    </td>
                    <td style="font-size:13px;"><?= htmlspecialchars($t['assigned_to_name'] ?? '—') ?></td>
                    <td style="font-size:12px;<?= $isOverdue ? 'color:#dc2626;font-weight:700;' : '' ?>">
                        <?php if ($dueDate): ?>
                        <?= $dueDate->format('d M Y') ?>
                        <?php if ($isOverdue): ?>
                        <div style="font-size:10px;color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i> Overdue</div>
                        <?php endif; ?>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text2);">
                        <?= date('d M Y', strtotime($t['created_at'])) ?>
                    </td>
                    <td>
                        <a href="<?= base_url('tasks/view/' . $t['id']) ?>" class="btn btn-ghost btn-xs">
                            <i class="fa-solid fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:13px;color:var(--text2);">
            Page <?= $page ?> of <?= $totalPages ?>
        </span>
        <div style="display:flex;gap:6px;">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-chevron-left"></i> Prev
            </a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-ghost btn-sm">
                Next <i class="fa-solid fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>