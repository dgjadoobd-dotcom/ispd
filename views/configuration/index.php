<?php
/**
 * Configuration Index View
 */
$zones = $zones ?? [];
$pops = $pops ?? [];
$packages = $packages ?? [];
$settings = $settings ?? [];
?>

<div class="page-header fade-in">
    <div><h1 class="page-title"><i class="fa-solid fa-gear" style="color:var(--blue);margin-right:10px;"></i>Business Configuration</h1></div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert-success"><?= $_SESSION['success'] ?></div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert-error"><?= $_SESSION['error'] ?></div>
<?php unset($_SESSION['error']); endif; ?>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="card fade-in" style="padding:20px;text-align:center;cursor:pointer;" onclick="toggleSection('zones')">
        <i class="fa-solid fa-map" style="font-size:32px;color:var(--blue);"></i>
        <div style="font-size:24px;font-weight:700;margin-top:8px;"><?= count($zones) ?></div>
        <div style="font-size:12px;color:var(--text2);">Zones</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;cursor:pointer;" onclick="toggleSection('pops')">
        <i class="fa-solid fa-tower-cell" style="font-size:32px;color:var(--blue);"></i>
        <div style="font-size:24px;font-weight:700;margin-top:8px;"><?= count($pops) ?></div>
        <div style="font-size:12px;color:var(--text2);">POPs</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;cursor:pointer;" onclick="toggleSection('packages')">
        <i class="fa-solid fa-box" style="font-size:32px;color:var(--blue);"></i>
        <div style="font-size:24px;font-weight:700;margin-top:8px;"><?= count($packages) ?></div>
        <div style="font-size:12px;color:var(--text2);">Packages</div>
    </div>
    <div class="card fade-in" style="padding:20px;text-align:center;cursor:pointer;" onclick="toggleSection('settings')">
        <i class="fa-solid fa-sliders" style="font-size:32px;color:var(--blue);"></i>
        <div style="font-size:24px;font-weight:700;margin-top:8px;">Settings</div>
        <div style="font-size:12px;color:var(--text2);">System</div>
    </div>
</div>

<!-- Zones Section -->
<div class="card fade-in" id="zonesSection">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-size:14px;font-weight:700;">Zones</h3>
        <?php if (PermissionHelper::hasPermission('configuration.create')): ?>
        <a href="<?= base_url('configuration/zones/create') ?>" class="btn btn-primary btn-sm">Add Zone</a>
        <?php endif; ?>
    </div>
    <?php if (empty($zones)): ?>
    <div style="padding:32px;text-align:center;color:var(--text2);">No zones found.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Code</th><th>Description</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($zones as $z): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($z['name']) ?></td>
                <td><?= htmlspecialchars($z['code']) ?></td>
                <td><?= htmlspecialchars($z['description'] ?? '') ?></td>
                <td>
                    <a href="<?= base_url('configuration/zones/edit/' . $z['id']) ?>" class="btn btn-ghost btn-xs">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- POPs Section -->
<div class="card fade-in" id="popsSection" style="display:none;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-size:14px;font-weight:700;">POPs</h3>
        <?php if (PermissionHelper::hasPermission('configuration.create')): ?>
        <a href="<?= base_url('configuration/pops/create') ?>" class="btn btn-primary btn-sm">Add POP</a>
        <?php endif; ?>
    </div>
    <?php if (empty($pops)): ?>
    <div style="padding:32px;text-align:center;color:var(--text2);">No POPs found.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Code</th><th>Zone</th><th>IP Address</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($pops as $p): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['code']) ?></td>
                <td><?= htmlspecialchars($p['zone_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['ip_address'] ?? '') ?></td>
                <td><span class="badge <?= $p['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= $p['status'] ?></span></td>
                <td><a href="<?= base_url('configuration/pops/edit/' . $p['id']) ?>" class="btn btn-ghost btn-xs">Edit</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Packages Section -->
<div class="card fade-in" id="packagesSection" style="display:none;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-size:14px;font-weight:700;">Packages</h3>
        <?php if (PermissionHelper::hasPermission('configuration.create')): ?>
        <a href="<?= base_url('configuration/packages/create') ?>" class="btn btn-primary btn-sm">Add Package</a>
        <?php endif; ?>
    </div>
    <?php if (empty($packages)): ?>
    <div style="padding:32px;text-align:center;color:var(--text2);">No packages found.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Code</th><th>Price</th><th>Download</th><th>Upload</th><th>Data Limit</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($packages as $p): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['code']) ?></td>
                <td><?= number_format($p['price'], 2) ?></td>
                <td><?= $p['download_speed'] ?> Kbps</td>
                <td><?= $p['upload_speed'] ?> Kbps</td>
                <td><?= $p['data_limit'] ?> GB</td>
                <td><span class="badge <?= $p['is_active'] ? 'badge-green' : 'badge-gray' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td><a href="<?= base_url('configuration/packages/edit/' . $p['id']) ?>" class="btn btn-ghost btn-xs">Edit</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
function toggleSection(id) {
    ['zones','pops','packages','settings'].forEach(s => {
        document.getElementById(s + 'Section').style.display = (s === id) ? 'block' : 'none';
    });
}
</script>