<?php // views/ott/providers.php
// Variables: $providers (array)
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">OTT Providers</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i>
            <a href="<?= base_url('ott') ?>" style="color:var(--blue);">OTT</a> &rsaquo; Providers
        </div>
    </div>
    <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
    <a href="<?= base_url('ott/providers/create') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add Provider
    </a>
    <?php endif; ?>
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

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Provider</th>
                <th>Plan Types</th>
                <th>Packages</th>
                <th>Active Subscriptions</th>
                <th>API Endpoint</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($providers)): ?>
            <tr>
                <td colspan="7" style="text-align:center;padding:36px;color:var(--text2);">
                    <i class="fa-solid fa-building" style="font-size:24px;margin-bottom:8px;display:block;"></i>
                    No OTT providers configured.
                    <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
                    <a href="<?= base_url('ott/providers/create') ?>" style="color:var(--blue);">Add one</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: foreach ($providers as $p): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php if ($p['logo_url']): ?>
                        <img src="<?= htmlspecialchars($p['logo_url']) ?>" alt=""
                             style="width:32px;height:32px;object-fit:contain;border-radius:6px;border:1px solid var(--border);">
                        <?php else: ?>
                        <div style="width:32px;height:32px;border-radius:6px;background:rgba(37,99,235,.1);display:flex;align-items:center;justify-content:center;color:var(--blue);">
                            <i class="fa-solid fa-tv" style="font-size:14px;"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:12px;color:var(--text2);">
                    <?= $p['plan_types'] ? htmlspecialchars($p['plan_types']) : '—' ?>
                </td>
                <td style="font-weight:600;"><?= (int)$p['package_count'] ?></td>
                <td style="font-weight:600;color:var(--green);"><?= (int)$p['subscription_count'] ?></td>
                <td style="font-size:12px;color:var(--text2);">
                    <?= $p['api_endpoint'] ? htmlspecialchars(substr($p['api_endpoint'], 0, 40)) . '...' : '—' ?>
                </td>
                <td>
                    <?php if ($p['is_active']): ?>
                    <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Active</span>
                    <?php else: ?>
                    <span class="badge badge-gray"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <?php if (PermissionHelper::hasPermission('ott.manage')): ?>
                        <a href="<?= base_url('ott/providers/edit/' . $p['id']) ?>"
                           class="btn btn-ghost" style="padding:4px 10px;font-size:12px;" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form method="POST" action="<?= base_url('ott/providers/toggle/' . $p['id']) ?>"
                              style="display:inline;"
                              onsubmit="return confirm('Toggle provider status?')">
                            <button type="submit" class="btn btn-ghost"
                                    style="padding:4px 10px;font-size:12px;color:<?= $p['is_active'] ? 'var(--red)' : 'var(--green)' ?>;"
                                    title="<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <i class="fa-solid <?= $p['is_active'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
