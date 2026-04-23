<?php // views/hr/attendance.php
$months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
           7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];

// Build a lookup: day => record for calendar rendering
$attendanceByDay = [];
foreach ($attendanceRecords as $rec) {
    $day = (int)date('j', strtotime($rec['attendance_date']));
    $attendanceByDay[$day] = $rec;
}

// Calendar helpers
$daysInMonth  = $selectedEmployee ? cal_days_in_month(CAL_GREGORIAN, $month, $year) : 0;
$firstDayOfWeek = $selectedEmployee ? (int)date('w', mktime(0,0,0,$month,1,$year)) : 0; // 0=Sun

$statusColors = [
    'present'  => ['bg'=>'rgba(22,163,74,.15)',  'border'=>'rgba(22,163,74,.4)',  'text'=>'var(--green)',  'label'=>'Present'],
    'absent'   => ['bg'=>'rgba(220,38,38,.15)',   'border'=>'rgba(220,38,38,.4)',  'text'=>'var(--red)',    'label'=>'Absent'],
    'late'     => ['bg'=>'rgba(217,119,6,.15)',   'border'=>'rgba(217,119,6,.4)',  'text'=>'var(--yellow)', 'label'=>'Late'],
    'half_day' => ['bg'=>'rgba(234,88,12,.15)',   'border'=>'rgba(234,88,12,.4)',  'text'=>'#ea580c',       'label'=>'Half Day'],
    'leave'    => ['bg'=>'rgba(37,99,235,.15)',   'border'=>'rgba(37,99,235,.4)',  'text'=>'var(--blue)',   'label'=>'Leave'],
];
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Attendance</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-calendar-check" style="color:var(--blue)"></i> HR &amp; Payroll</div>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.08);">
    <span style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></span>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;" class="fade-in">

    <!-- Left Panel: Filter + Record Form -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Filter -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;"><i class="fa-solid fa-filter" style="color:var(--blue);margin-right:8px;"></i>Filter</div>
            <form method="GET" action="<?= base_url('hr/attendance') ?>">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div>
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-input">
                            <option value="">— Select Employee —</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $selectedEmployee == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Month</label>
                        <select name="month" class="form-input">
                            <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-input" value="<?= $year ?>" min="2020" max="2099">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="fa-solid fa-filter"></i> View
                    </button>
                </div>
            </form>
        </div>

        <!-- Record Attendance Form -->
        <?php if ($selectedEmployee): ?>
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;"><i class="fa-solid fa-pen-to-square" style="color:var(--green);margin-right:8px;"></i>Record Attendance</div>
            <form method="POST" action="<?= base_url('hr/attendance/store') ?>">
                <input type="hidden" name="employee_id" value="<?= $selectedEmployee ?>">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div>
                        <label class="form-label">Date <span style="color:var(--red)">*</span></label>
                        <input type="date" name="attendance_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Status <span style="color:var(--red)">*</span></label>
                        <select name="status" class="form-input" required>
                            <option value="present">✅ Present</option>
                            <option value="absent">❌ Absent</option>
                            <option value="late">⏰ Late</option>
                            <option value="half_day">🌓 Half Day</option>
                            <option value="leave">📋 Leave</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="2" style="resize:vertical;" placeholder="Optional notes…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="fa-solid fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>

        <!-- Legend -->
        <div class="card" style="padding:14px;">
            <div style="font-size:11px;font-weight:700;color:var(--text2);margin-bottom:8px;">LEGEND</div>
            <?php foreach ($statusColors as $st => $c): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                <div style="width:14px;height:14px;border-radius:3px;background:<?= $c['bg'] ?>;border:1px solid <?= $c['border'] ?>;flex-shrink:0;"></div>
                <span style="font-size:12px;color:var(--text2);"><?= $c['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Panel: Calendar + Summary -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <?php if ($selectedEmployee && !empty($summary)): ?>
        <!-- Monthly Summary Cards -->
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;">
            <?php foreach ($summary as $st => $cnt):
                $c = $statusColors[$st] ?? ['bg'=>'var(--bg3)','border'=>'var(--border)','text'=>'var(--text2)','label'=>ucfirst($st)];
            ?>
            <div class="card" style="padding:12px;text-align:center;border-color:<?= $c['border'] ?>;background:<?= $c['bg'] ?>;">
                <div style="font-size:10px;color:var(--text2);text-transform:capitalize;margin-bottom:4px;"><?= $c['label'] ?></div>
                <div style="font-size:24px;font-weight:800;color:<?= $c['text'] ?>;"><?= $cnt ?></div>
                <div style="font-size:10px;color:var(--text2);">days</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($selectedEmployee && $daysInMonth > 0): ?>
        <!-- Calendar Grid -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;margin-bottom:16px;">
                <i class="fa-solid fa-calendar" style="color:var(--blue);margin-right:8px;"></i>
                <?= $months[$month] ?> <?= $year ?>
            </div>

            <!-- Day headers -->
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:4px;">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayName): ?>
                <div style="text-align:center;font-size:11px;font-weight:700;color:var(--text2);padding:4px 0;"><?= $dayName ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Calendar cells -->
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
                <?php
                // Empty cells before first day
                for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                <div></div>
                <?php endfor;

                for ($day = 1; $day <= $daysInMonth; $day++):
                    $rec = $attendanceByDay[$day] ?? null;
                    $st  = $rec ? $rec['status'] : null;
                    $c   = $st ? $statusColors[$st] : null;
                    $isToday = ($day == date('j') && $month == date('n') && $year == date('Y'));
                ?>
                <div style="
                    text-align:center;padding:8px 4px;border-radius:6px;
                    background:<?= $c ? $c['bg'] : 'var(--bg3)' ?>;
                    border:1px solid <?= $c ? $c['border'] : 'var(--border)' ?>;
                    <?= $isToday ? 'outline:2px solid var(--blue);outline-offset:1px;' : '' ?>
                    cursor:default;
                " title="<?= $st ? ucfirst(str_replace('_',' ',$st)) . ($rec['notes'] ? ': ' . htmlspecialchars($rec['notes']) : '') : 'No record' ?>">
                    <div style="font-size:12px;font-weight:<?= $isToday ? '800' : '600' ?>;color:<?= $c ? $c['text'] : 'var(--text2)' ?>;"><?= $day ?></div>
                    <?php if ($st): ?>
                    <div style="font-size:9px;color:<?= $c['text'] ?>;margin-top:2px;line-height:1;"><?= strtoupper(substr(str_replace('_',' ',$st),0,3)) ?></div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Attendance Records Table -->
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;">
                <i class="fa-solid fa-list" style="color:var(--blue);margin-right:8px;"></i>Records
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Date</th><th>Day</th><th>Status</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceRecords)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text2);">
                        <?= $selectedEmployee ? 'No attendance records for this period.' : 'Select an employee to view attendance.' ?>
                    </td></tr>
                    <?php else: foreach ($attendanceRecords as $rec):
                        $st = $rec['status'];
                        $c  = $statusColors[$st] ?? null;
                    ?>
                    <tr>
                        <td style="font-weight:600;"><?= date('d M Y', strtotime($rec['attendance_date'])) ?></td>
                        <td style="color:var(--text2);font-size:12px;"><?= date('l', strtotime($rec['attendance_date'])) ?></td>
                        <td>
                            <?php if ($c): ?>
                            <span class="badge" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>;border:1px solid <?= $c['border'] ?>;">
                                <?= $c['label'] ?>
                            </span>
                            <?php else: ?>
                            <span class="badge badge-gray"><?= htmlspecialchars($st) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($rec['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
