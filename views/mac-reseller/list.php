<?php // views/mac-reseller/list.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">MAC Resellers</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-network-wired" style="color:var(--blue)"></i>
            <?= count($resellers) ?> reseller<?= count($resellers) !== 1 ? 's' : '' ?>
        </div>
    </div>
    <a href="<?= base_url('mac-resellers/create') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add MAC Reseller
    </a>
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
            <tr>
                <th>Business</th>
                <th>Contact</th>
                <th>Branch</th>
                <th>Clients</th>
                <th>Active</th>
                <th>Balance</th>
                <th>Commission</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($resellers)): ?>
            <tr>
                <td colspan="9" style="text-align:center;padding:48px;color:var(--text2);">
                    <i class="fa-solid fa-network-wired" style="font-size:36px;display:block;margin-bottom:12px;opacity:.3;"></i>
                    No MAC resellers yet. <a href="<?= base_url('mac-resellers/create') ?>" style="color:var(--blue);">Add your first MAC reseller</a>.
                </td>
            </tr>
            <?php else: foreach ($resellers as $r): ?>
            <tr>
                <td>
                    <div style="font-weight:700;"><?= htmlspecialchars($r['business_name']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($r['contact_person']) ?></div>
                </td>
                <td>
                    <div style="font-size:13px;"><?= htmlspecialchars($r['phone']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($r['email'] ?? '') ?></div>
                </td>
                <td style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($r['branch_name'] ?? '—') ?></td>
                <td style="font-size:15px;font-weight:700;color:var(--blue);"><?= number_format($r['client_count']) ?></td>
                <td style="font-size:13px;font-weight:600;color:var(--green);"><?= number_format($r['active_clients']) ?></td>
                <td style="font-weight:700;<?= $r['balance'] > 0 ? 'color:var(--green)' : ($r['balance'] < 0 ? 'color:var(--red)' : '') ?>">
                    ৳<?= number_format($r['balance'], 2) ?>
                </td>
                <td style="font-weight:600;"><?= $r['commission_rate'] ?>%</td>
                <td>
                    <?php $bc = match($r['status']) { 'active' => 'badge-green', 'suspended' => 'badge-yellow', default => 'badge-gray' }; ?>
                    <span class="badge <?= $bc ?>"><?= ucfirst($r['status']) ?></span>
                </td>
                <td>
                    <div style="display:flex;gap:5px;">
                        <a href="<?= base_url("mac-resellers/view/{$r['id']}") ?>" class="btn btn-ghost btn-sm" title="View"><i class="fa-solid fa-eye"></i></a>
                        <a href="<?= base_url("mac-resellers/{$r['id']}/clients") ?>" class="btn btn-ghost btn-sm" title="Clients"><i class="fa-solid fa-users"></i></a>
                        <a href="<?= base_url("mac-resellers/{$r['id']}/tariffs") ?>" class="btn btn-ghost btn-sm" title="Tariffs"><i class="fa-solid fa-tags"></i></a>
                        <a href="<?= base_url("mac-resellers/edit/{$r['id']}") ?>" class="btn btn-ghost btn-sm" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php if ($r['status'] !== 'inactive'): ?>
                        <form method="POST" action="<?= base_url("mac-resellers/delete/{$r['id']}") ?>"
                              onsubmit="return confirm('Deactivate <?= htmlspecialchars(addslashes($r['business_name'])) ?>?');" style="display:inline;">
                            <button type="submit" class="btn btn-danger btn-sm" title="Deactivate"><i class="fa-solid fa-ban"></i></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
