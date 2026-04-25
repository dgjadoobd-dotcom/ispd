<?php // views/ott/subscriptions.php
// Variables: $result (paginated array), $providers (array), $filters (array)
$subscriptions = $result['data'] ?? [];
$total         = $result['total'] ?? 0;
$page          = $result['page'] ?? 1;
$totalPages    = $result['totalPages'] ?? 1;
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">OTT Subscriptions</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i>
            <a href="<?= base_url('ott') ?>" style="color:var(--blue);">OTT</a> &rsaquo; Subscriptions
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
        <a href="<?= base_url('ott/subscriptions/create') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add Subscription
        </a>
        <?php endif; ?>
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

<!-- Filters -->
<div class="card fade-in" style="padding:14px 18px;margin-bottom:16px;">
    <form method="GET" action="<?= base_url('ott/subscriptions') ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="form-label" style="font-size:12px;">Status</label>
            <select name="status" class="form-input" style="width:140px;padding:7px 10px;">
                <option value="">All Statuses</option>
                <?php foreach (['active','expired','cancelled','suspended'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size:12px;">Provider</label>
            <select name="provider_id" class="form-input" style="width:160px;padding:7px 10px;">
                <option value="">All Providers</option>
                <?php foreach ($providers as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (int)($filters['provider_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="<?= base_url('ott/subscriptions') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-xmark"></i> Clear
        </a>
    </form>
</div>

<!-- Table -->
<div class="card fade-in" style="overflow:hidden;">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:13px;font-weight:600;"><?= number_format($total) ?> subscription(s)</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Provider / Package</th>
                <th>Start Date</th>
                <th>Expiry Date</th>
                <th>Auto-Renew</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subscriptions)): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:36px;color:var(--text2);">
                    <i class="fa-solid fa-tv" style="font-size:24px;margin-bottom:8px;display:block;"></i>
                    No subscriptions found.
                    <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
                    <a href="<?= base_url('ott/subscriptions/create') ?>" style="color:var(--blue);">Add one</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: foreach ($subscriptions as $sub): ?>
            <?php
            $statusColors = [
                'active'    => 'badge-green',
                'expired'   => 'badge-red',
                'cancelled' => 'badge-gray',
                'suspended' => 'badge-yellow',
            ];
            $badgeClass = $statusColors[$sub['status']] ?? 'badge-gray';
            $isExpiringSoon = $sub['status'] === 'active'
                && strtotime($sub['expiry_date']) <= strtotime('+3 days');
            ?>
            <tr>
                <td style="font-size:12px;color:var(--text2);"><?= $sub['id'] ?></td>
                <td>
                    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($sub['customer_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($sub['customer_code']) ?></div>
                </td>
                <td>
                    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($sub['provider_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($sub['ott_package_name']) ?></div>
                </td>
                <td style="font-size:12px;"><?= date('d M Y', strtotime($sub['start_date'])) ?></td>
                <td style="font-size:12px;">
                    <span style="<?= $isExpiringSoon ? 'color:var(--yellow);font-weight:600;' : '' ?>">
                        <?= date('d M Y', strtotime($sub['expiry_date'])) ?>
                    </span>
                    <?php if ($isExpiringSoon): ?>
                    <span style="font-size:10px;color:var(--yellow);display:block;">Expiring soon</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($sub['auto_renewal']): ?>
                    <span class="badge badge-blue" style="font-size:11px;"><i class="fa-solid fa-rotate"></i> Yes</span>
                    <?php else: ?>
                    <span class="badge badge-gray" style="font-size:11px;">No</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($sub['status']) ?></span></td>
                <td>
                    <a href="<?= base_url('ott/subscriptions/view/' . $sub['id']) ?>"
                       class="btn btn-ghost" style="padding:4px 10px;font-size:12px;" title="View">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="padding:14px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:13px;color:var(--text2);">Page <?= $page ?> of <?= $totalPages ?></span>
        <div style="display:flex;gap:6px;">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
               class="btn btn-ghost btn-sm"><i class="fa-solid fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
               class="btn btn-ghost btn-sm"><i class="fa-solid fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
