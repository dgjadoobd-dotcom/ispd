<?php // views/reports/btrc/generate.php
$months = [
    1=>'January', 2=>'February', 3=>'March',    4=>'April',
    5=>'May',     6=>'June',     7=>'July',      8=>'August',
    9=>'September',10=>'October',11=>'November', 12=>'December',
];
$currentYear  = (int)date('Y');
$currentMonth = (int)date('n');
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Generate BTRC Report</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-file-contract" style="color:var(--blue)"></i>
            Reports &rsaquo; <a href="<?= base_url('reports/btrc') ?>" style="color:var(--blue);">BTRC DIS</a> &rsaquo; Generate
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:380px 1fr;gap:20px;" class="fade-in">

    <!-- Generation Form -->
    <div class="card" style="padding:24px;align-self:start;">
        <h3 style="margin:0 0 6px;font-size:15px;font-weight:700;">
            <i class="fa-solid fa-file-circle-plus" style="color:var(--blue);margin-right:6px;"></i>New Report
        </h3>
        <p style="font-size:12px;color:var(--text2);margin:0 0 20px;">
            Select the reporting period. The system will aggregate subscriber and revenue data
            for the selected month and generate a BTRC DIS-format report.
        </p>

        <form method="POST" action="<?= base_url('reports/btrc/generate') ?>">
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="form-label">Reporting Month <span style="color:var(--red);">*</span></label>
                    <select name="month" class="form-input" required>
                        <?php foreach ($months as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $currentMonth === $num ? 'selected' : '' ?>>
                            <?= $name ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Year <span style="color:var(--red);">*</span></label>
                    <input type="number" name="year" class="form-input"
                           value="<?= $currentYear ?>" min="2000" max="2099" required>
                </div>

                <div style="background:rgba(37,99,235,.06);border:1px solid rgba(37,99,235,.2);border-radius:6px;padding:12px;font-size:12px;color:var(--text2);">
                    <i class="fa-solid fa-circle-info" style="color:var(--blue);margin-right:4px;"></i>
                    If a report already exists for the selected period, it will be <strong>regenerated</strong> with the latest data.
                </div>

                <div style="display:flex;gap:10px;">
                    <a href="<?= base_url('reports/btrc') ?>" class="btn btn-secondary" style="flex:1;text-align:center;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" style="flex:2;"
                            onclick="return confirm('Generate BTRC report for the selected period?')">
                        <i class="fa-solid fa-file-circle-plus"></i> Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Info Panel -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card" style="padding:20px;">
            <h4 style="margin:0 0 12px;font-size:13px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
                What's Included
            </h4>
            <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;">
                <?php
                $items = [
                    ['fa-users',          'var(--blue)',   'Total subscribers at end of period'],
                    ['fa-user-plus',      'var(--green)',  'New connections during the period'],
                    ['fa-user-minus',     'var(--red)',    'Disconnections during the period'],
                    ['fa-circle-check',   'var(--green)',  'Active subscribers count'],
                    ['fa-money-bill-wave','var(--blue)',   'Total revenue collected'],
                    ['fa-map-location',   'var(--purple)', 'Breakdown by division and district'],
                ];
                foreach ($items as [$icon, $color, $label]):
                ?>
                <li style="display:flex;align-items:center;gap:10px;font-size:13px;">
                    <i class="fa-solid <?= $icon ?>" style="color:<?= $color ?>;width:16px;text-align:center;"></i>
                    <?= $label ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card" style="padding:20px;">
            <h4 style="margin:0 0 12px;font-size:13px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
                Export Formats
            </h4>
            <div style="display:flex;gap:12px;">
                <div style="flex:1;border:1px solid var(--border);border-radius:6px;padding:14px;text-align:center;">
                    <i class="fa-solid fa-file-csv" style="font-size:28px;color:var(--green);margin-bottom:8px;display:block;"></i>
                    <div style="font-weight:600;font-size:13px;">CSV</div>
                    <div style="font-size:11px;color:var(--text2);margin-top:4px;">BTRC-specified column headers and order</div>
                </div>
                <div style="flex:1;border:1px solid var(--border);border-radius:6px;padding:14px;text-align:center;">
                    <i class="fa-solid fa-file-pdf" style="font-size:28px;color:var(--red);margin-bottom:8px;display:block;"></i>
                    <div style="font-weight:600;font-size:13px;">PDF</div>
                    <div style="font-size:11px;color:var(--text2);margin-top:4px;">Company letterhead with authorised signatory</div>
                </div>
            </div>
        </div>

        <div class="card" style="padding:20px;">
            <h4 style="margin:0 0 12px;font-size:13px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">
                Quick Preview
            </h4>
            <p style="font-size:12px;color:var(--text2);margin:0 0 12px;">
                Preview data for a period without saving a report.
            </p>
            <form method="GET" action="<?= base_url('reports/btrc/preview') ?>" style="display:flex;gap:8px;align-items:flex-end;">
                <div style="flex:1;">
                    <label class="form-label" style="font-size:11px;">Month</label>
                    <select name="month" class="form-input" style="padding:6px 10px;font-size:12px;">
                        <?php foreach ($months as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $currentMonth === $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1;">
                    <label class="form-label" style="font-size:11px;">Year</label>
                    <input type="number" name="year" class="form-input" value="<?= $currentYear ?>"
                           min="2000" max="2099" style="padding:6px 10px;font-size:12px;">
                </div>
                <button type="submit" class="btn btn-secondary" style="padding:7px 14px;font-size:12px;">
                    <i class="fa-solid fa-eye"></i> Preview
                </button>
            </form>
        </div>
    </div>
</div>
