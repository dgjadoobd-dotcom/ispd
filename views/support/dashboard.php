<?php
/**
 * SLA Compliance Dashboard
 * Requirement 3.9: % resolved within SLA per category and per employee.
 */
$byCategory = $dashboard['by_category']    ?? [];
$byEmployee = $dashboard['by_employee']    ?? [];
$summary    = $dashboard['summary']        ?? [];
$overall    = $dashboard['overall_compliance'] ?? 0.0;

function complianceColor(float $pct): string {
    if ($pct >= 90) return '#16a34a';
    if ($pct >= 70) return '#d97706';
    return '#dc2626';
}
function complianceBg(float $pct): string {
    if ($pct >= 90) return '#dcfce7';
    if ($pct >= 70) return '#fef3c7';
    return '#fee2e2';
}
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-chart-bar" style="color:var(--blue);margin-right:10px;"></i>
            SLA Compliance Dashboard
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('support/tickets') ?>">Support</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>SLA Dashboard</span>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if (PermissionHelper::hasPermission('support.edit')): ?>
        <form method="POST" action="<?= base_url('support/check-sla') ?>">
            <button type="submit" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-clock-rotate-left"></i> Run SLA Check
            </button>
        </form>
        <?php endif; ?>
        <a href="<?= base_url('support/tickets') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-ticket"></i> All Tickets
        </a>
    </div>
</div>

<!-- Summary KPI Cards -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px;" class="fade-in">

    <div class="card stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fa-solid fa-ticket"></i></div>
        <div class="stat-value"><?= number_format((int)($summary['total'] ?? 0)) ?></div>
        <div class="stat-label">Total Tickets</div>
    </div>

    <div class="card stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa-solid fa-circle-dot"></i></div>
        <div class="stat-value"><?= number_format((int)($summary['open_count'] ?? 0) + (int)($summary['in_progress_count'] ?? 0)) ?></div>
        <div class="stat-label">Open / In Progress</div>
    </div>

    <div class="card stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-value"><?= number_format((int)($summary['resolved_count'] ?? 0) + (int)($summary['closed_count'] ?? 0)) ?></div>
        <div class="stat-label">Resolved / Closed</div>
    </div>

    <div class="card stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-value"><?= number_format((int)($summary['breached_count'] ?? 0)) ?></div>
        <div class="stat-label">SLA Breached</div>
    </div>

    <div class="card stat-card" style="border:2px solid <?= complianceColor($overall) ?>;">
        <div class="stat-icon" style="background:<?= complianceBg($overall) ?>;color:<?= complianceColor($overall) ?>;"><i class="fa-solid fa-gauge-high"></i></div>
        <div class="stat-value" style="color:<?= complianceColor($overall) ?>;"><?= $overall ?>%</div>
        <div class="stat-label">Overall SLA Compliance</div>
    </div>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- SLA Compliance by Category -->
    <div class="card fade-in fade-in-delay-1">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:700;">
                <i class="fa-solid fa-tags" style="color:var(--blue);margin-right:6px;"></i>
                Compliance by Category
            </h3>
        </div>
        <?php if (empty($byCategory)): ?>
        <div style="padding:32px;text-align:center;color:var(--text2);font-size:13px;">No data available.</div>
        <?php else: ?>
        <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($byCategory as $row): ?>
            <?php $pct = (float)$row['compliance_pct']; ?>
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($row['category_name']) ?></span>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:11px;color:var(--text2);"><?= $row['within_sla'] ?>/<?= $row['total_tickets'] ?> tickets</span>
                        <span style="font-size:13px;font-weight:700;color:<?= complianceColor($pct) ?>;"><?= $pct ?>%</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= complianceColor($pct) ?>;"></div>
                </div>
                <?php if ($row['breached'] > 0): ?>
                <div style="font-size:11px;color:#dc2626;margin-top:3px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= $row['breached'] ?> breached
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- SLA Compliance by Employee -->
    <div class="card fade-in fade-in-delay-2">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:700;">
                <i class="fa-solid fa-users" style="color:var(--blue);margin-right:6px;"></i>
                Compliance by Employee
            </h3>
        </div>
        <?php if (empty($byEmployee)): ?>
        <div style="padding:32px;text-align:center;color:var(--text2);font-size:13px;">No data available.</div>
        <?php else: ?>
        <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($byEmployee as $row): ?>
            <?php $pct = (float)$row['compliance_pct']; ?>
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:6px;background:#dbeafe;color:#2563eb;
                                    display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">
                            <?= strtoupper(substr($row['employee_name'], 0, 1)) ?>
                        </div>
                        <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($row['employee_name']) ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:11px;color:var(--text2);"><?= $row['within_sla'] ?>/<?= $row['total_tickets'] ?></span>
                        <span style="font-size:13px;font-weight:700;color:<?= complianceColor($pct) ?>;"><?= $pct ?>%</span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= complianceColor($pct) ?>;"></div>
                </div>
                <?php if ($row['breached'] > 0): ?>
                <div style="font-size:11px;color:#dc2626;margin-top:3px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= $row['breached'] ?> breached
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- SLA Reference Table -->
<div class="card fade-in fade-in-delay-3" style="margin-top:20px;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:14px;font-weight:700;">
            <i class="fa-solid fa-clock" style="color:var(--blue);margin-right:6px;"></i>
            SLA Reference — Deadline by Priority
        </h3>
    </div>
    <div style="padding:16px 20px;display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
        <?php foreach (['urgent' => ['2 hours', '#dc2626', '#fee2e2'], 'high' => ['8 hours', '#d97706', '#fef3c7'], 'medium' => ['24 hours', '#2563eb', '#dbeafe'], 'low' => ['72 hours', '#64748b', '#f1f5f9']] as $priority => [$label, $color, $bg]): ?>
        <div style="background:<?= $bg ?>;border-radius:8px;padding:14px;text-align:center;">
            <div style="font-size:11px;font-weight:700;color:<?= $color ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                <?= ucfirst($priority) ?>
            </div>
            <div style="font-size:20px;font-weight:800;color:<?= $color ?>;"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
