<?php // views/roles/index.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Roles & Permissions</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-shield-halved" style="color:var(--blue)"></i>
            <?= $totalUsers ?> users · <?= $totalPermissions ?> permissions defined
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <form method="POST" action="<?= base_url('roles/seed') ?>" style="display:inline;">
            <button type="submit" class="btn btn-ghost" onclick="return confirm('Seed all default roles and permissions?')">
                <i class="fa-solid fa-seedling"></i> Seed Defaults
            </button>
        </form>
        <a href="<?= base_url('roles/create') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Role
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:10px;color:var(--green);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['success']) ?>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;color:var(--red);font-size:13px;" class="fade-in">
    <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['error']) ?>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<!-- Role cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;" class="fade-in">
    <?php foreach ($roles as $r):
        $isProtected = in_array($r['name'], ['superadmin', 'comadmin']);
        $pct = $totalPermissions > 0 ? round(($r['perm_count'] / $totalPermissions) * 100) : 0;
        $colors = [
            'superadmin'   => ['#7c3aed','#ede9fe'],
            'comadmin'     => ['#2563eb','#dbeafe'],
            'branch_admin' => ['#d97706','#fef3c7'],
        ];
        [$clr, $bg] = $colors[$r['name']] ?? ['#16a34a','#dcfce7'];
    ?>
    <div class="card fade-in" style="padding:20px;position:relative;overflow:hidden;">
        <!-- Accent bar -->
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?= $clr ?>;"></div>

        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-shield-halved" style="color:<?= $clr ?>;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($r['display_name']) ?></div>
                    <div style="font-size:11px;font-family:monospace;color:var(--text2);"><?= htmlspecialchars($r['name']) ?></div>
                </div>
            </div>
            <?php if ($isProtected): ?>
            <span style="font-size:10px;background:rgba(239,68,68,0.1);color:var(--red);padding:3px 8px;border-radius:20px;font-weight:700;">Protected</span>
            <?php endif; ?>
        </div>

        <?php if ($r['description']): ?>
        <div style="font-size:12px;color:var(--text2);margin-bottom:14px;line-height:1.5;"><?= htmlspecialchars($r['description']) ?></div>
        <?php endif; ?>

        <!-- Permission progress -->
        <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text2);margin-bottom:5px;">
                <span>Permissions</span>
                <span style="font-weight:700;color:<?= $clr ?>;"><?= $r['perm_count'] ?> / <?= $totalPermissions ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>;"></div>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:flex;gap:12px;margin-bottom:16px;">
            <div style="flex:1;background:var(--bg3);border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:20px;font-weight:800;color:var(--blue);"><?= $r['user_count'] ?></div>
                <div style="font-size:10px;color:var(--text2);">Users</div>
            </div>
            <div style="flex:1;background:var(--bg3);border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:20px;font-weight:800;" style="color:<?= $clr ?>;"><?= $pct ?>%</div>
                <div style="font-size:10px;color:var(--text2);">Access</div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:8px;">
            <a href="<?= base_url("roles/edit/{$r['id']}") ?>" class="btn btn-primary btn-sm" style="flex:1;justify-content:center;">
                <i class="fa-solid fa-sliders"></i> Manage Permissions
            </a>
            <a href="<?= base_url("roles/users/{$r['id']}") ?>" class="btn btn-ghost btn-sm" title="View users">
                <i class="fa-solid fa-users"></i> <?= $r['user_count'] ?>
            </a>
            <?php if (!$isProtected): ?>
            <form method="POST" action="<?= base_url("roles/delete/{$r['id']}") ?>"
                  onsubmit="return confirm('Delete role \'<?= htmlspecialchars(addslashes($r['display_name'])) ?>\'? This cannot be undone.');"
                  style="display:inline;">
                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Add new role card -->
    <a href="<?= base_url('roles/create') ?>" class="card fade-in" style="padding:20px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;border:2px dashed var(--border);text-decoration:none;min-height:200px;transition:all 0.2s;">
        <div style="width:48px;height:48px;border-radius:12px;background:var(--bg3);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-plus" style="font-size:20px;color:var(--text2);"></i>
        </div>
        <div style="font-size:13px;font-weight:600;color:var(--text2);">Create New Role</div>
    </a>
</div>
