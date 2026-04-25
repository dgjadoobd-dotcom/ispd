<?php // views/portal/support/package_change_content.php ?>
<div style="max-width:600px;margin:0 auto;">

    <div style="margin-bottom:20px;">
        <h2 style="font-size:20px;font-weight:800;margin-bottom:4px;">Package Upgrade / Downgrade</h2>
        <p style="font-size:13px;color:var(--text2);">Request a change to your internet package. Our team will process it within 24 hours.</p>
    </div>

    <?php if (!empty($_SESSION['portal_error'])): ?>
    <div style="padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;color:var(--red);font-size:13px;margin-bottom:16px;">
        <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i><?= htmlspecialchars($_SESSION['portal_error']) ?>
        <?php unset($_SESSION['portal_error']); ?>
    </div>
    <?php endif; ?>

    <!-- Current package -->
    <?php if ($currentPkg): ?>
    <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:20px;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Current Package</div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:16px;font-weight:700;"><?= htmlspecialchars($currentPkg['name']) ?></div>
                <div style="font-size:12px;color:var(--text2);"><?= $currentPkg['speed_download'] ?>↓ / <?= $currentPkg['speed_upload'] ?>↑ Mbps</div>
            </div>
            <div style="font-size:20px;font-weight:800;color:var(--blue);">৳<?= number_format($currentPkg['price'], 0) ?><span style="font-size:12px;font-weight:400;color:var(--text2);">/mo</span></div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('portal/package-change') ?>">
        <div style="display:flex;flex-direction:column;gap:16px;">

            <div>
                <label class="form-label">Request Type <span style="color:var(--red)">*</span></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid var(--border);border-radius:8px;cursor:pointer;transition:all .15s;" id="upgradeLabel">
                        <input type="radio" name="change_type" value="upgrade" checked onchange="updateLabel()" style="width:16px;height:16px;">
                        <div>
                            <div style="font-weight:700;font-size:13px;"><i class="fa-solid fa-arrow-up" style="color:var(--green);margin-right:6px;"></i>Upgrade</div>
                            <div style="font-size:11px;color:var(--text2);">Move to a faster/higher plan</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid var(--border);border-radius:8px;cursor:pointer;transition:all .15s;" id="downgradeLabel">
                        <input type="radio" name="change_type" value="downgrade" onchange="updateLabel()" style="width:16px;height:16px;">
                        <div>
                            <div style="font-weight:700;font-size:13px;"><i class="fa-solid fa-arrow-down" style="color:var(--yellow);margin-right:6px;"></i>Downgrade</div>
                            <div style="font-size:11px;color:var(--text2);">Move to a lower/cheaper plan</div>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="form-label">Select New Package <span style="color:var(--red)">*</span></label>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($packages as $p):
                        $isCurrent = isset($currentPkg) && $currentPkg['id'] == $p['id'];
                    ?>
                    <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:2px solid <?= $isCurrent ? 'var(--blue)' : 'var(--border)' ?>;border-radius:8px;cursor:<?= $isCurrent ? 'not-allowed' : 'pointer' ?>;opacity:<?= $isCurrent ? '.5' : '1' ?>;">
                        <input type="radio" name="new_package_id" value="<?= $p['id'] ?>" <?= $isCurrent ? 'disabled' : '' ?> style="width:16px;height:16px;flex-shrink:0;">
                        <div style="flex:1;">
                            <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($p['name']) ?> <?= $isCurrent ? '<span style="font-size:10px;color:var(--blue);">(Current)</span>' : '' ?></div>
                            <div style="font-size:11px;color:var(--text2);"><?= $p['speed_download'] ?>↓ / <?= $p['speed_upload'] ?>↑ Mbps</div>
                        </div>
                        <div style="font-size:16px;font-weight:800;color:var(--blue);">৳<?= number_format($p['price'], 0) ?><span style="font-size:11px;font-weight:400;color:var(--text2);">/mo</span></div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="form-label">Reason (optional)</label>
                <textarea name="reason" class="form-input" rows="3" placeholder="Tell us why you want to change your package..."></textarea>
            </div>

            <div style="background:rgba(37,99,235,0.06);border:1px solid rgba(37,99,235,0.2);border-radius:8px;padding:12px 14px;font-size:12px;color:var(--text2);">
                <i class="fa-solid fa-info-circle" style="color:var(--blue);margin-right:6px;"></i>
                Package changes are processed within 24 hours. Your current package remains active until the change is applied.
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;">
                <i class="fa-solid fa-paper-plane"></i> Submit Package Change Request
            </button>
            <a href="<?= base_url('portal/support') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateLabel() {
    const upgrade   = document.getElementById('upgradeLabel');
    const downgrade = document.getElementById('downgradeLabel');
    const isUpgrade = document.querySelector('input[name="change_type"][value="upgrade"]').checked;
    upgrade.style.borderColor   = isUpgrade ? 'var(--green)' : 'var(--border)';
    downgrade.style.borderColor = !isUpgrade ? 'var(--yellow)' : 'var(--border)';
}
updateLabel();
</script>
