<?php // views/reports/btrc/view.php
// Variables: $report (array), $logs (array)
$period  = date('F Y', strtotime($report['report_period']));
$divData = $report['division_district_data'] ?? [];
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">BTRC Report: <?= htmlspecialchars($period) ?></h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-file-contract" style="color:var(--blue)"></i>
            Reports &rsaquo; <a href="<?= base_url('reports/btrc') ?>" style="color:var(--blue);">BTRC DIS</a>
            &rsaquo; <?= htmlspecialchars($period) ?>
        </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php if ($report['status'] === 'draft' && PermissionHelper::hasPermission('btrc_reports.generate')): ?>
        <form method="POST" action="<?= base_url('reports/btrc/finalise/' . $report['id']) ?>" style="display:inline;">
            <button type="submit" class="btn btn-secondary"
                    onclick="return confirm('Mark this report as final? This cannot be undone.')">
                <i class="fa-solid fa-lock"></i> Mark as Final
            </button>
        </form>
        <?php if (PermissionHelper::hasPermission('btrc_reports.generate')): ?>
        <form method="POST" action="<?= base_url('reports/btrc/generate') ?>" style="display:inline;">
            <input type="hidden" name="month" value="<?= $report['report_month'] ?>">
            <input type="hidden" name="year"  value="<?= $report['report_year'] ?>">
            <button type="submit" class="btn btn-secondary"
                    onclick="return confirm('Regenerate this report with the latest data?')">
                <i class="fa-solid fa-rotate"></i> Regenerate
            </button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (PermissionHelper::hasPermission('btrc_reports.export')): ?>
        <a href="<?= base_url('reports/btrc/export/csv/' . $report['id']) ?>"
           class="btn btn-secondary" style="color:var(--green);">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <a href="<?= base_url('reports/btrc/export/pdf/' . $report['id']) ?>"
           target="_blank" class="btn btn-secondary" style="color:var(--red);">
            <i class="fa-solid fa-file-pdf"></i> Export PDF
        </a>
        <?php endif; ?>
        <a href="<?= base_url('reports/btrc') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.08);">
    <span style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></span>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<?php if ($report['status'] === 'final'): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.06);">
    <i class="fa-solid fa-lock" style="color:var(--green);"></i>
    <strong style="color:var(--green);">Final Report</strong> — This report has been finalised and is ready for BTRC submission.
</div>
<?php else: ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(234,179,8,.4);background:rgba(234,179,8,.06);">
    <i class="fa-solid fa-pen-to-square" style="color:var(--yellow);"></i>
    <strong style="color:var(--yellow);">Draft</strong> — This report is still in draft. Regenerate to refresh data or mark as final when ready.
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;" class="fade-in">
    <?php
    $cards = [
        ['Total Subscribers',  $report['total_subscribers'],  'fa-users',       'var(--blue)'],
        ['New Connections',    $report['new_connections'],     'fa-user-plus',   'var(--green)'],
        ['Disconnections',     $report['disconnections'],      'fa-user-minus',  'var(--red)'],
        ['Active Subscribers', $report['active_subscribers'],  'fa-circle-check','var(--green)'],
    ];
    foreach ($cards as [$label, $value, $icon, $color]):
    ?>
    <div class="card" style="padding:18px;text-align:center;">
        <i class="fa-solid <?= $icon ?>" style="font-size:22px;color:<?= $color ?>;margin-bottom:8px;display:block;"></i>
        <div style="font-size:26px;font-weight:800;color:<?= $color ?>;"><?= number_format((int)$value) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px;"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Revenue Summary -->
<div class="card fade-in" style="padding:20px;margin-bottom:20px;">
    <h3 style="margin:0 0 16px;font-size:14px;font-weight:700;">
        <i class="fa-solid fa-money-bill-wave" style="color:var(--blue);margin-right:6px;"></i>Revenue Summary
    </h3>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
        <?php
        $revCards = [
            ['Total Revenue',          $report['total_revenue'],          'var(--blue)'],
            ['New Connection Revenue', $report['new_connection_revenue'],  'var(--green)'],
            ['Monthly Bill Revenue',   $report['monthly_bill_revenue'],    'var(--purple)'],
        ];
        foreach ($revCards as [$label, $value, $color]):
        ?>
        <div style="border:1px solid var(--border);border-radius:8px;padding:16px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:<?= $color ?>;">
                ৳ <?= number_format((float)$value, 2) ?>
            </div>
            <div style="font-size:12px;color:var(--text2);margin-top:4px;"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Division/District Breakdown -->
<div class="card fade-in" style="overflow:hidden;margin-bottom:20px;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h3 style="margin:0;font-size:14px;font-weight:700;">
            <i class="fa-solid fa-map-location-dot" style="color:var(--purple);margin-right:6px;"></i>
            Subscriber Breakdown by Division &amp; District
        </h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Division</th>
                <th>District</th>
                <th>Active Subscribers</th>
                <th>New Connections</th>
                <th>Disconnections</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($divData)): ?>
            <tr>
                <td colspan="5" style="text-align:center;padding:24px;color:var(--text2);">
                    No division/district data available.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($divData as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($row['division'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($row['district'] ?? 'Unknown') ?></td>
                <td><?= number_format((int)($row['active'] ?? 0)) ?></td>
                <td style="color:var(--green);">+<?= number_format((int)($row['new'] ?? 0)) ?></td>
                <td style="color:var(--red);">-<?= number_format((int)($row['disconnected'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight:700;background:rgba(37,99,235,.04);">
                <td colspan="2">Total</td>
                <td><?= number_format(array_sum(array_column($divData, 'active'))) ?></td>
                <td style="color:var(--green);">+<?= number_format(array_sum(array_column($divData, 'new'))) ?></td>
                <td style="color:var(--red);">-<?= number_format(array_sum(array_column($divData, 'disconnected'))) ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Report Metadata & Audit Log -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="fade-in">

    <!-- Metadata -->
    <div class="card" style="padding:20px;">
        <h3 style="margin:0 0 14px;font-size:13px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
            Report Details
        </h3>
        <table style="width:100%;font-size:13px;border-collapse:collapse;">
            <tr>
                <td style="padding:6px 0;color:var(--text2);width:140px;">Report Period</td>
                <td style="padding:6px 0;font-weight:600;"><?= htmlspecialchars($period) ?></td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:var(--text2);">Status</td>
                <td style="padding:6px 0;">
                    <?= $report['status'] === 'final'
                        ? '<span style="color:var(--green);font-weight:600;">Final</span>'
                        : '<span style="color:var(--yellow);font-weight:600;">Draft</span>' ?>
                </td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:var(--text2);">Generated By</td>
                <td style="padding:6px 0;"><?= htmlspecialchars($report['generated_by_name'] ?? '—') ?></td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:var(--text2);">Generated At</td>
                <td style="padding:6px 0;"><?= date('d M Y H:i', strtotime($report['generated_at'])) ?></td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:var(--text2);">Last Updated</td>
                <td style="padding:6px 0;"><?= date('d M Y H:i', strtotime($report['updated_at'] ?? $report['generated_at'])) ?></td>
            </tr>
        </table>
    </div>

    <!-- Audit Log -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;font-size:13px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
                Activity Log
            </h3>
        </div>
        <div style="max-height:220px;overflow-y:auto;">
            <?php if (empty($logs)): ?>
            <div style="padding:20px;text-align:center;color:var(--text2);font-size:13px;">No activity logged.</div>
            <?php else: ?>
            <?php foreach ($logs as $log): ?>
            <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;font-size:12px;">
                <?php
                $actionColors = [
                    'generated'    => 'var(--blue)',
                    'exported_csv' => 'var(--green)',
                    'exported_pdf' => 'var(--red)',
                    'previewed'    => 'var(--purple)',
                    'deleted'      => 'var(--red)',
                ];
                $actionIcons = [
                    'generated'    => 'fa-file-circle-plus',
                    'exported_csv' => 'fa-file-csv',
                    'exported_pdf' => 'fa-file-pdf',
                    'previewed'    => 'fa-eye',
                    'deleted'      => 'fa-trash',
                ];
                $color = $actionColors[$log['action']] ?? 'var(--text2)';
                $icon  = $actionIcons[$log['action']] ?? 'fa-circle';
                ?>
                <i class="fa-solid <?= $icon ?>" style="color:<?= $color ?>;width:14px;text-align:center;"></i>
                <div style="flex:1;">
                    <span style="font-weight:600;color:<?= $color ?>;">
                        <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                    </span>
                    <?php if (!empty($log['export_format'])): ?>
                    <span style="color:var(--text2);"> (<?= strtoupper($log['export_format']) ?>)</span>
                    <?php endif; ?>
                </div>
                <div style="color:var(--text2);text-align:right;">
                    <div><?= htmlspecialchars($log['performed_by_name'] ?? '—') ?></div>
                    <div><?= date('d M H:i', strtotime($log['performed_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
