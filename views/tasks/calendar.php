<?php
/**
 * Task Calendar View
 * Requirement 4.4: daily task calendar interface
 */
$currentDate = new DateTime($date);
$prevDate = (clone $currentDate)->modify('-1 day');
$nextDate = (clone $currentDate)->modify('+1 day');

$priorityColors = [
    'urgent' => 'badge-red',
    'high'  => 'badge-yellow',
    'medium' => 'badge-blue',
    'low'   => 'badge-gray',
];
$statusColors = [
    'pending'     => 'badge-yellow',
    'in_progress' => 'badge-blue',
    'completed'  => 'badge-green',
    'cancelled'   => 'badge-gray',
];
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-regular fa-calendar" style="color:var(--blue);margin-right:10px;"></i>Task Calendar</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Task Calendar</span>
        </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="<?= base_url('tasks/list') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-list"></i> List
        </a>
        <?php if (PermissionHelper::hasPermission('task.create')): ?>
        <a href="<?= base_url('tasks/create') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> New Task
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Date Navigation -->
<div class="card fade-in" style="padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:center;gap:20px;">
    <a href="?date=<?= $prevDate->format('Y-m-d') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <div style="text-align:center;">
        <div style="font-size:20px;font-weight:700;"><?= $currentDate->format('l') ?></div>
        <div style="font-size:14px;color:var(--text2);"><?= $currentDate->format('d F Y') ?></div>
    </div>
    <a href="?date=<?= $nextDate->format('Y-m-d') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-chevron-right"></i>
    </a>
</div>

<!-- Tasks for Date -->
<div class="card fade-in">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-weight:700;font-size:15px;">
            <?= count($tasks) ?> task<?= count($tasks) !== 1 ? 's' : '' ?> on this day
        </span>
    </div>

    <?php if (empty($tasks)): ?>
    <div style="padding:48px;text-align:center;color:var(--text2);">
        <i class="fa-regular fa-calendar-check" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
        <p style="font-size:15px;">No tasks scheduled for this day.</p>
    </div>
    <?php else: ?>
    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($tasks as $t): ?>
        <a href="<?= base_url('tasks/view/' . $t['id']) ?>" style="display:flex;align-items:center;gap:16px;padding:12px 16px;background:var(--bg2);border-radius:8px;text-decoration:none;color:inherit;transition:background 0.2s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='var(--bg2)'">
            <div style="flex:1;">
                <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($t['title']) ?></div>
                <div style="font-size:12px;color:var(--text2);margin-top:4px;">
                    <?= htmlspecialchars($t['assigned_to_name'] ?? 'Unassigned') ?>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <span class="badge <?= $priorityColors[$t['priority']] ?? 'badge-gray' ?>">
                    <?= ucfirst($t['priority']) ?>
                </span>
                <span class="badge <?= $statusColors[$t['status']] ?? 'badge-gray' ?>">
                    <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                </span>
            </div>
            <i class="fa-solid fa-chevron-right" style="color:var(--text2);"></i>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>