<?php // views/branches/view.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <?= htmlspecialchars($branch['name']) ?>
            <?php if ((int)$branch['is_active'] === 1): ?>
                <span class="badge badge-green" style="font-size:13px;vertical-align:middle;margin-left:8px;"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Active</span>
            <?php else: ?>
                <span class="badge badge-red" style="font-size:13px;vertical-align:middle;margin-left:8px;"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Inactive</span>
            <?php endif; ?>
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('branches') ?>" style="color:var(--blue);text-decoration:none;"><i class="fa-solid fa-code-branch"></i> Branches</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <?= htmlspecialchars($branch['name']) ?>
            <span style="font-family:monospace;background:var(--bg3);padding:2px 6px;border-radius:4px;font-size:11px;"><?= htmlspecialchars($branch['code'] ?? '') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if (PermissionHelper::hasPermission('branches.edit')): ?>
        <a href="<?= base_url('branches/edit/' . $branch['id']) ?>" class="btn btn-ghost"><i class="fa-solid fa-pen"></i> Edit</a>
        <?php if ((int)$branch['is_active'] === 1): ?>
        <form method="POST" action="<?= base_url('branches/deactivate/' . $branch['id']) ?>" style="display:inline;" onsubmit="return confirm('Deactivate this branch?')">
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Deactivate</button>
        </form>
        <?php else: ?>
        <form method="POST" action="<?= base_url('branches/activate/' . $branch['id']) ?>" style="display:inline;" onsubmit="return confirm('Activate this branch?')">
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-circle-check"></i> Activate</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <a href="<?= base_url('branches') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
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

<!-- Branch Info + KPI Cards -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:18px;" class="fade-in">
    <!-- Branch Details -->
    <div class="card" style="padding:20px;">
        <div style="font-size:14px;font-weight:700;margin-bottom:14px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;">Branch Details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <div style="font-size:11px;color:var(--text2);margin-bottom:2px;">Code</div>
                <div style="font-weight:600;font-family:monospace;"><?= htmlspecialchars($branch['code'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text2);margin-bottom:2px;">Manager</div>
                <div style="font-weight:600;"><?= htmlspecialchars($branch['manager'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text2);margin-bottom:2px;">Phone</div>
                <div><?= htmlspecialchars($branch['phone'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text2);margin-bottom:2px;">Email</div>
                <div><?= htmlspecialchars($branch['email'] ?? '—') ?></div>
            </div>
            <div style="grid-column:1/-1;">
                <div style="font-size:11px;color:var(--text2);margin-bottom:2px;">Address</div>
                <div><?= htmlspecialchars($branch['address'] ?? '—') ?></div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div style="display:flex;flex-direction:column;gap:10px;">
        <div class="card stat-card" style="padding:14px;">
            <div class="stat-label">Customers</div>
            <div class="stat-value" style="font-size:22px;"><?= (int)($branch['customer_count'] ?? 0) ?></div>
        </div>
        <?php if (!empty($reports)): $latest = $reports[0]; ?>
        <div class="card stat-card" style="padding:14px;">
            <div class="stat-label">Monthly Revenue</div>
            <div class="stat-value" style="font-size:18px;color:var(--green);">৳<?= number_format((float)($latest['monthly_revenue'] ?? 0), 0) ?></div>
        </div>
        <div class="card stat-card" style="padding:14px;">
            <div class="stat-label">Outstanding Dues</div>
            <div class="stat-value" style="font-size:18px;color:var(--red);">৳<?= number_format((float)($latest['outstanding_dues'] ?? 0), 0) ?></div>
        </div>
        <div class="card stat-card" style="padding:14px;">
            <div class="stat-label">Active Tickets</div>
            <div class="stat-value" style="font-size:18px;color:var(--yellow);"><?= (int)($latest['active_tickets'] ?? 0) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Report + Export -->
<?php if (PermissionHelper::hasPermission('branches.reports')): ?>
<div class="card fade-in" style="padding:20px;margin-bottom:18px;">
    <div style="font-size:15px;font-weight:700;margin-bottom:14px;color:var(--text);border-bottom:1px solid var(--border);padding-bottom:10px;">
        <i class="fa-solid fa-chart-bar" style="color:var(--blue);margin-right:8px;"></i>Generate Summary Report
    </div>
    <form method="POST" action="<?= base_url('branches/report/' . $branch['id']) ?>" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div>
            <label class="form-label" style="font-size:12px;">From Date</label>
            <input type="date" name="date_from" class="form-input" style="width:160px;" value="<?= date('Y-m-01') ?>">
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">To Date</label>
            <input type="date" name="date_to" class="form-input" style="width:160px;" value="<?= date('Y-m-d') ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-chart-line"></i> Generate Report</button>
        <a href="<?= base_url('branches/export/' . $branch['id']) ?>" class="btn btn-ghost"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
    </form>
</div>
<?php endif; ?>

<!-- Assign Credential -->
<?php if (PermissionHelper::hasPermission('branches.edit') && !empty($users)): ?>
<div class="card fade-in" style="padding:20px;margin-bottom:18px;">
    <div style="font-size:15px;font-weight:700;margin-bottom:14px;color:var(--text);border-bottom:1px solid var(--border);padding-bottom:10px;">
        <i class="fa-solid fa-key" style="color:var(--yellow);margin-right:8px;"></i>Login Credential
    </div>
    <?php if ($credential): ?>
    <div style="margin-bottom:14px;padding:12px;background:var(--bg3);border-radius:8px;border:1px solid var(--border);">
        <div style="font-size:12px;color:var(--text2);">Currently assigned to:</div>
        <div style="font-weight:600;margin-top:4px;">
            <?= htmlspecialchars($credential['user_full_name'] ?? $credential['username'] ?? '—') ?>
            <span style="font-size:12px;color:var(--text2);font-weight:400;">(<?= htmlspecialchars($credential['username'] ?? '') ?>)</span>
        </div>
        <?php if (!empty($credential['user_email'])): ?>
        <div style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($credential['user_email']) ?></div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="margin-bottom:14px;color:var(--text2);font-size:13px;"><i class="fa-solid fa-circle-info"></i> No credential assigned yet.</div>
    <?php endif; ?>
    <form method="POST" action="<?= base_url('branches/credential/' . $branch['id']) ?>" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div>
            <label class="form-label" style="font-size:12px;"><?= $credential ? 'Change Assigned User' : 'Assign User' ?></label>
            <select name="user_id" class="form-input" style="width:280px;">
                <option value="">— Select user —</option>
                <?php foreach ($users as $user): ?>
                <option value="<?= (int)$user['id'] ?>" <?= isset($credential['user_id']) && (int)$credential['user_id'] === (int)$user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
                    <?= $user['email'] ? '(' . htmlspecialchars($user['email']) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> <?= $credential ? 'Update' : 'Assign' ?></button>
    </form>
</div>
<?php endif; ?>

<!-- Report History -->
<div class="card fade-in" style="overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:15px;font-weight:700;color:var(--text);">
            <i class="fa-solid fa-history" style="color:var(--blue);margin-right:8px;"></i>Report History
        </div>
        <span style="font-size:12px;color:var(--text2);"><?= count($reports) ?> report(s)</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Period</th>
                <th>Customers</th>
                <th>Revenue</th>
                <th>Outstanding Dues</th>
                <th>Active Tickets</th>
                <th>Generated By</th>
                <th>Generated At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text2);">No reports generated yet. Use the form above to generate a report.</td></tr>
            <?php else: foreach ($reports as $report): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:12px;"><?= htmlspecialchars($report['period_start']) ?></div>
                    <div style="font-size:11px;color:var(--text2);">to <?= htmlspecialchars($report['period_end']) ?></div>
                </td>
                <td style="font-weight:600;"><?= (int)$report['customer_count'] ?></td>
                <td style="font-weight:600;color:var(--green);">৳<?= number_format((float)$report['monthly_revenue'], 2) ?></td>
                <td style="font-weight:600;color:var(--red);">৳<?= number_format((float)$report['outstanding_dues'], 2) ?></td>
                <td style="font-weight:600;color:var(--yellow);"><?= (int)$report['active_tickets'] ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($report['generated_by_name'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($report['generated_at'] ?? '—') ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
