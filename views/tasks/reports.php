<?php
/**
 * Task Reports View
 * Requirement 4.8: completion rate reports
 */
$summary = $report['summary'] ?? [];
$completionRate = $report['completion_rate'] ?? 0;
$byEmployee = $report['by_employee'] ?? [];

$statusColors = [
    'pending'     => 'badge-yellow',
    'in_progress' => 'badge-blue',
    'completed'  => 'badge-green',
    'cancelled'   => 'badge-gray',
];
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-chart-pie" style="color:var(--blue);margin-right:10px;"></i>Task Reports</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>Task Reports</span>
        </div>
    </div>
    <a href="<?= base_url('tasks/list') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to List
    </a>
</div>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px;">
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:var(--blue);"><?= number_format($summary['total'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Total Tasks</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:#eab308;"><?= number_format($summary['pending'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Pending</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:#2563eb;"><?= number_format($summary['in_progress'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">In Progress</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:#15803d;"><?= number_format($summary['completed'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Completed</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;">
        <div style="font-size:24px;font-weight:700;color:#dc2626;"><?= number_format($summary['overdue'] ?? 0) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;">Overdue</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- Completion Rate -->
    <div class="card fade-in">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:700;">Completion Rate</h3>
        </div>
        <div style="padding:24px;text-align:center;">
            <div style="width:150px;height:150px;border-radius:50%;background:conic-gradient(var(--green) <?= $completionRate ?>%,var(--bg2) 0);margin:0 auto 20px;position:relative;">
                <div style="width:130px;height:130px;border-radius:50%;background:var(--bg);position:absolute;top:10px;left:10px;display:flex;align-items:center;justify-content:center;">
                    <div style="font-size:28px;font-weight:700;"><?= $completionRate ?>%</div>
                </div>
            </div>
            <div style="font-size:14px;color:var(--text2);"><?= number_format($summary['completed'] ?? 0) ?> of <?= number_format($summary['total'] ?? 0) ?> tasks completed</div>
        </div>
    </div>

    <!-- By Employee -->
    <div class="card fade-in">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:700;">By Employee</h3>
        </div>
        <?php if (empty($byEmployee)): ?>
        <div style="padding:32px;text-align:center;color:var(--text2);">
            No assigned tasks found.
        </div>
        <?php else: ?>
        <div style="padding:16px 20px;">
            <div style="display:flex;flex-direction:column;gap:16px;">
                <?php foreach ($byEmployee as $emp): ?>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($emp['employee_name']) ?></span>
                        <span style="font-size:13px;"><?= $emp['completion_rate'] ?>%</span>
                    </div>
                    <div style="height:8px;background:var(--bg2);border-radius:4px;overflow:hidden;">
                        <div style="height:100%;width:<?= $emp['completion_rate'] ?>%;background:var(--green);border-radius:4px;"></div>
                    </div>
                    <div style="font-size:11px;color:var(--text2);margin-top:4px;">
                        <?= $emp['completed'] ?> / <?= $emp['total_tasks'] ?> tasks
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>