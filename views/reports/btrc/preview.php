<?php // views/reports/btrc/preview.php
// Variables: $report (array — may have is_preview=true)
$period      = date('F Y', strtotime($report['report_period']));
$isPreview   = !empty($report['is_preview']);
$isSaved     = !empty($report['id']);
$divData     = $report['division_district_data'] ?? [];
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <?= $isPreview ? 'Preview' : 'Report' ?>: <?= htmlspecialchars($period) ?>
        </h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-file-contract" style="color:var(--blue)"></i>
            Reports &rsaquo; <a href="<?= base_url('reports/btrc') ?>" style="color:var(--blue);">BTRC DIS</a>
            &rsaquo; <?= $isPreview ? 'Preview' : 'View' ?>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($isPreview && PermissionHelper::hasPermission('btrc_reports.generate')): ?>
        <form method="POST" action="<?= base_url('reports/btrc/generate') ?>" style="display:inline;">
            <input type="hidden" name="month" value="<?= $report['report_month'] ?>">
            <input type="hidden" name="year"  value="<?= $report['report_year'] ?>">
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Save this report?')">
                <i class="fa-solid fa-floppy-disk"></i> Save Report
            </button>
        </form>
        <?php endif; ?>
        <?php if ($isSaved && PermissionHelper::hasPermission('btrc_reports.export')): ?>
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

<?php if ($isPreview): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(234,179,8,.4);background:rgba(234,179,8,.06);">
    <i class="fa-solid fa-triangle-exclamation" style="color:var(--yellow);"></i>
    <strong style="color:var(--yellow);">Preview Mode</strong> — This data has not been saved. Click "Save Report" to persist it.
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
                    No division/district data available. Ensure customers have zone assignments with division and district fields.
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
            <!-- Totals row -->
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

<!-- Report Metadata -->
<?php if (!$isPreview): ?>
<div class="card fade-in" style="padding:16px 20px;">
    <h3 style="margin:0 0 12px;font-size:13px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
        Report Metadata
    </h3>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;font-size:13px;">
        <div>
            <span style="color:var(--text2);">Status:</span>
            <strong style="margin-left:6px;">
                <?= $report['status'] === 'final'
                    ? '<span style="color:var(--green);">Final</span>'
                    : '<span style="color:var(--yellow);">Draft</span>' ?>
            </strong>
        </div>
        <div>
            <span style="color:var(--text2);">Generated By:</span>
            <strong style="margin-left:6px;"><?= htmlspecialchars($report['generated_by_name'] ?? '—') ?></strong>
        </div>
        <div>
            <span style="color:var(--text2);">Generated At:</span>
            <strong style="margin-left:6px;"><?= date('d M Y H:i', strtotime($report['generated_at'])) ?></strong>
        </div>
    </div>
</div>
<?php endif; ?>
