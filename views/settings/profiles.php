<?php // views/settings/profiles.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">PPPoE Profiles</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-gear" style="color:var(--blue)"></i>
            Configuration
            <i class="fa-solid fa-chevron-right" style="font-size:9px;"></i>
            PPPoE Profiles
        </div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">
        <i class="fa-solid fa-plus"></i> Add Profile
    </button>
</div>

<?php if ($successMsg): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.08);">
    <span style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?></span>
</div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?></span>
</div>
<?php endif; ?>

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:60px;">#</th>
                <th>Profile Name</th>
                <th>Download Speed</th>
                <th>Upload Speed</th>
                <th>NAS / Server</th>
                <th>Description</th>
                <th>Status</th>
                <th style="width:120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($profiles)): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:40px;color:var(--text2);">
                    <i class="fa-solid fa-gauge-high" style="font-size:28px;display:block;margin-bottom:10px;opacity:.4;"></i>
                    No PPPoE profiles yet.
                    <br><button class="btn btn-primary btn-sm" style="margin-top:10px;" onclick="document.getElementById('addModal').classList.add('open')">
                        <i class="fa-solid fa-plus"></i> Add First Profile
                    </button>
                </td>
            </tr>
            <?php else: $sl = 1; foreach ($profiles as $p): ?>
            <tr>
                <td style="text-align:center;font-weight:600;color:var(--text2);"><?= $sl++ ?></td>
                <td style="font-weight:700;font-family:monospace;color:var(--blue);"><?= htmlspecialchars($p['name']) ?></td>
                <td><span class="badge badge-green"><?= htmlspecialchars($p['speed_download']) ?> Mbps ↓</span></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($p['speed_upload']) ?> Mbps ↑</span></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($p['nas_name'] ?? 'All Servers') ?></td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($p['description'] ?? '—') ?></td>
                <td><span class="badge <?= $p['is_active'] ? 'badge-green' : 'badge-gray' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td style="text-align:center;">
                    <div style="display:inline-flex;gap:6px;">
                        <button class="btn btn-ghost btn-sm" onclick='openEdit(<?= json_encode($p) ?>)' title="Edit">
                            <i class="fa-solid fa-pen-to-square" style="color:var(--green);"></i>
                        </button>
                        <form method="POST" action="<?= base_url("settings/profiles/delete/{$p['id']}") ?>"
                              onsubmit="return confirm('Delete profile \'<?= addslashes($p['name']) ?>\'?');"
                              style="display:inline;">
                            <button type="submit" class="btn btn-ghost btn-sm" title="Delete">
                                <i class="fa-solid fa-trash" style="color:var(--red);"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-plus" style="color:var(--blue);margin-right:8px;"></i>Add PPPoE Profile</div>
            <button class="icon-btn" onclick="document.getElementById('addModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/profiles/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;">
                    <label class="form-label">Profile Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. 10Mbps, 50Mbps, Fiber-100M" required autofocus>
                </div>
                <div>
                    <label class="form-label">Download Speed (Mbps) <span style="color:var(--red)">*</span></label>
                    <input type="number" name="speed_download" class="form-input" placeholder="10" min="1" required>
                </div>
                <div>
                    <label class="form-label">Upload Speed (Mbps) <span style="color:var(--red)">*</span></label>
                    <input type="number" name="speed_upload" class="form-input" placeholder="10" min="1" required>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">NAS / Server <span style="font-size:11px;color:var(--text2);">(optional — leave blank for all)</span></label>
                    <select name="nas_id" class="form-input">
                        <option value="">All Servers</option>
                        <?php foreach ($nasDevices as $nas): ?>
                        <option value="<?= $nas['id'] ?>"><?= htmlspecialchars($nas['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-input" placeholder="Optional notes...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen" style="color:var(--blue);margin-right:8px;"></i>Edit PPPoE Profile</div>
            <button class="icon-btn" onclick="document.getElementById('editModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('settings/profiles/update') ?>">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="grid-column:1/-1;">
                    <label class="form-label">Profile Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" id="edit_name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Download Speed (Mbps)</label>
                    <input type="number" name="speed_download" id="edit_download" class="form-input" min="1" required>
                </div>
                <div>
                    <label class="form-label">Upload Speed (Mbps)</label>
                    <input type="number" name="speed_upload" id="edit_upload" class="form-input" min="1" required>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">NAS / Server</label>
                    <select name="nas_id" id="edit_nas" class="form-input">
                        <option value="">All Servers</option>
                        <?php foreach ($nasDevices as $nas): ?>
                        <option value="<?= $nas['id'] ?>"><?= htmlspecialchars($nas['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" id="edit_desc" class="form-input">
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="is_active" id="edit_active" class="form-input">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(p) {
    document.getElementById('edit_id').value       = p.id;
    document.getElementById('edit_name').value     = p.name;
    document.getElementById('edit_download').value = p.speed_download;
    document.getElementById('edit_upload').value   = p.speed_upload;
    document.getElementById('edit_nas').value      = p.nas_id || '';
    document.getElementById('edit_desc').value     = p.description || '';
    document.getElementById('edit_active').value   = p.is_active;
    document.getElementById('editModal').classList.add('open');
}
</script>
