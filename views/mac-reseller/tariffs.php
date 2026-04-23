<?php // views/mac-reseller/tariffs.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Tariff Plans — <?= htmlspecialchars($reseller['business_name']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('mac-resellers') ?>" style="color:var(--blue);text-decoration:none;">MAC Resellers</a> ›
            <a href="<?= base_url("mac-resellers/view/{$reseller['id']}") ?>" style="color:var(--blue);text-decoration:none;"><?= htmlspecialchars($reseller['business_name']) ?></a>
            › Tariffs
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="document.getElementById('addTariffModal').classList.add('open')">
            <i class="fa-solid fa-plus"></i> Add Tariff
        </button>
        <a href="<?= base_url("mac-resellers/view/{$reseller['id']}") ?>" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> Back
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

<div class="card fade-in" style="overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr><th>Tariff Name</th><th>Speed</th><th>Daily Rate</th><th>Monthly Rate</th><th>Clients</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($tariffs)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text2);">
                <i class="fa-solid fa-tags" style="font-size:32px;display:block;margin-bottom:10px;opacity:.3;"></i>
                No tariff plans yet. Add one to get started.
            </td></tr>
            <?php else: foreach ($tariffs as $t): ?>
            <tr>
                <td style="font-weight:700;"><?= htmlspecialchars($t['name']) ?></td>
                <td style="font-family:monospace;font-size:12px;"><?= $t['speed_download'] ?>↓ / <?= $t['speed_upload'] ?>↑ Mbps</td>
                <td style="font-weight:700;color:var(--green);">৳<?= number_format($t['daily_rate'], 2) ?></td>
                <td style="font-weight:600;">৳<?= number_format($t['monthly_rate'], 2) ?></td>
                <td style="color:var(--blue);font-weight:600;"><?= $t['client_count'] ?></td>
                <td><span class="badge <?= $t['is_active'] ? 'badge-green' : 'badge-gray' ?>"><?= $t['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <div style="display:flex;gap:5px;">
                        <button class="btn btn-ghost btn-sm" onclick='openEditTariff(<?= json_encode($t) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <form method="POST" action="<?= base_url("mac-resellers/tariffs/delete/{$t['id']}") ?>"
                              onsubmit="return confirm('Delete tariff <?= htmlspecialchars(addslashes($t['name'])) ?>?');" style="display:inline;">
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Tariff Modal -->
<div class="modal-overlay" id="addTariffModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title">Add Tariff Plan</div>
            <button class="icon-btn" onclick="document.getElementById('addTariffModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url("mac-resellers/{$reseller['id']}/tariffs/store") ?>">
            <div class="modal-body" style="display:grid;gap:12px;">
                <div><label class="form-label">Tariff Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. 5 Mbps Basic"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div><label class="form-label">Download (Mbps)</label>
                        <input type="number" name="speed_download" class="form-input" value="0" min="0"></div>
                    <div><label class="form-label">Upload (Mbps)</label>
                        <input type="number" name="speed_upload" class="form-input" value="0" min="0"></div>
                    <div><label class="form-label">Daily Rate (৳)</label>
                        <input type="number" name="daily_rate" class="form-input" value="0" step="0.01" min="0"></div>
                    <div><label class="form-label">Monthly Rate (৳)</label>
                        <input type="number" name="monthly_rate" class="form-input" value="0" step="0.01" min="0"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addTariffModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Tariff</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Tariff Modal -->
<div class="modal-overlay" id="editTariffModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title">Edit Tariff Plan</div>
            <button class="icon-btn" onclick="document.getElementById('editTariffModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="editTariffForm">
            <div class="modal-body" style="display:grid;gap:12px;">
                <div><label class="form-label">Tariff Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="name" id="et_name" class="form-input" required></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div><label class="form-label">Download (Mbps)</label>
                        <input type="number" name="speed_download" id="et_dl" class="form-input" min="0"></div>
                    <div><label class="form-label">Upload (Mbps)</label>
                        <input type="number" name="speed_upload" id="et_ul" class="form-input" min="0"></div>
                    <div><label class="form-label">Daily Rate (৳)</label>
                        <input type="number" name="daily_rate" id="et_daily" class="form-input" step="0.01" min="0"></div>
                    <div><label class="form-label">Monthly Rate (৳)</label>
                        <input type="number" name="monthly_rate" id="et_monthly" class="form-input" step="0.01" min="0"></div>
                </div>
                <div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_active" id="et_active" value="1" style="width:16px;height:16px;">
                        <span style="font-size:13px;font-weight:600;">Active</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editTariffModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditTariff(t) {
    document.getElementById('et_name').value    = t.name;
    document.getElementById('et_dl').value      = t.speed_download;
    document.getElementById('et_ul').value      = t.speed_upload;
    document.getElementById('et_daily').value   = t.daily_rate;
    document.getElementById('et_monthly').value = t.monthly_rate;
    document.getElementById('et_active').checked = t.is_active == 1;
    document.getElementById('editTariffForm').action = '<?= base_url("mac-resellers/tariffs/update/") ?>' + t.id;
    document.getElementById('editTariffModal').classList.add('open');
}
</script>
