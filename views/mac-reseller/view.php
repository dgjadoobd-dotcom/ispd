<?php // views/mac-reseller/view.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($reseller['business_name']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('mac-resellers') ?>" style="color:var(--blue);text-decoration:none;">MAC Resellers</a> › Details
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= base_url("mac-resellers/{$reseller['id']}/clients") ?>" class="btn btn-ghost">
            <i class="fa-solid fa-users"></i> Clients
        </a>
        <a href="<?= base_url("mac-resellers/{$reseller['id']}/tariffs") ?>" class="btn btn-ghost">
            <i class="fa-solid fa-tags"></i> Tariffs
        </a>
        <a href="<?= base_url("mac-resellers/{$reseller['id']}/billing") ?>" class="btn btn-ghost">
            <i class="fa-solid fa-file-invoice-dollar"></i> Billing
        </a>
        <a href="<?= base_url("mac-resellers/edit/{$reseller['id']}") ?>" class="btn btn-ghost">
            <i class="fa-solid fa-pen"></i> Edit
        </a>
        <a href="<?= base_url('mac-resellers') ?>" class="btn btn-ghost">
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

<!-- Stats row -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;" class="fade-in">
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Total Clients</div>
        <div style="font-size:28px;font-weight:900;color:var(--blue);"><?= $reseller['client_count'] ?></div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Active Clients</div>
        <div style="font-size:28px;font-weight:900;color:var(--green);"><?= $reseller['active_clients'] ?></div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Today Billed</div>
        <div style="font-size:22px;font-weight:900;color:var(--yellow);">৳<?= number_format($todayBilling['total_billed'] ?? 0, 0) ?></div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Today Collected</div>
        <div style="font-size:22px;font-weight:900;color:var(--green);">৳<?= number_format($todayBilling['total_collected'] ?? 0, 0) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="fade-in">
    <!-- Left column -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Info card -->
        <div class="card" style="padding:20px;">
            <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:14px;">
                <i class="fa-solid fa-network-wired" style="color:var(--blue);margin-right:8px;"></i>Business Info
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;">
                <div><div style="font-size:10px;color:var(--text2);">Contact Person</div><div style="font-weight:600;"><?= htmlspecialchars($reseller['contact_person']) ?></div></div>
                <div><div style="font-size:10px;color:var(--text2);">Phone</div><div style="font-weight:600;"><?= htmlspecialchars($reseller['phone']) ?></div></div>
                <div><div style="font-size:10px;color:var(--text2);">Email</div><div><?= htmlspecialchars($reseller['email'] ?? '—') ?></div></div>
                <div><div style="font-size:10px;color:var(--text2);">Branch</div><div><?= htmlspecialchars($reseller['branch_name'] ?? '—') ?></div></div>
                <div><div style="font-size:10px;color:var(--text2);">Commission Rate</div><div style="font-weight:700;color:var(--green);"><?= $reseller['commission_rate'] ?>%</div></div>
                <div><div style="font-size:10px;color:var(--text2);">Credit Limit</div><div>৳<?= number_format($reseller['credit_limit'], 0) ?></div></div>
                <div><div style="font-size:10px;color:var(--text2);">Status</div>
                    <?php $bc = match($reseller['status']) { 'active' => 'badge-green', 'suspended' => 'badge-yellow', default => 'badge-gray' }; ?>
                    <span class="badge <?= $bc ?>"><?= ucfirst($reseller['status']) ?></span>
                </div>
                <div><div style="font-size:10px;color:var(--text2);">Joined</div><div><?= date('d M Y', strtotime($reseller['joined_date'] ?? 'now')) ?></div></div>
                <?php if ($reseller['notes']): ?>
                <div style="grid-column:1/-1;"><div style="font-size:10px;color:var(--text2);">Notes</div><div style="font-size:12px;"><?= htmlspecialchars($reseller['notes']) ?></div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Balance + Top-up -->
        <div class="card" style="padding:20px;">
            <div style="text-align:center;margin-bottom:16px;">
                <div style="font-size:12px;color:var(--text2);">Current Balance</div>
                <div style="font-size:40px;font-weight:900;<?= $reseller['balance'] >= 0 ? 'color:var(--green)' : 'color:var(--red)' ?>">
                    ৳<?= number_format($reseller['balance'], 2) ?>
                </div>
            </div>
            <form method="POST" action="<?= base_url("mac-resellers/topup/{$reseller['id']}") ?>" style="display:flex;gap:8px;">
                <input type="number" name="amount" class="form-input" placeholder="Top-up amount" step="0.01" min="1" style="flex:1;" required>
                <input type="text" name="notes" class="form-input" placeholder="Notes (optional)" style="flex:1;">
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-plus"></i> Top-up</button>
            </form>
        </div>

        <!-- Recent clients -->
        <div class="card" style="overflow:hidden;">
            <div style="padding:14px 18px;font-size:13px;font-weight:700;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                Recent Clients
                <a href="<?= base_url("mac-resellers/{$reseller['id']}/clients") ?>" style="font-size:11px;color:var(--blue);">View all →</a>
            </div>
            <?php if (empty($clients)): ?>
            <div style="padding:20px;text-align:center;color:var(--text2);font-size:12px;">No clients yet</div>
            <?php else: foreach ($clients as $c): ?>
            <div style="padding:10px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($c['full_name']) ?></div>
                    <div style="font-size:11px;font-family:monospace;color:var(--text2);"><?= htmlspecialchars($c['mac_address']) ?></div>
                    <div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($c['tariff_name'] ?? '—') ?></div>
                </div>
                <span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-red' ?>"><?= ucfirst($c['status']) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Right column: transactions -->
    <div class="card" style="overflow:hidden;">
        <div style="padding:14px 18px;font-size:13px;font-weight:700;border-bottom:1px solid var(--border);">Transaction History</div>
        <div style="max-height:620px;overflow-y:auto;">
            <?php if (empty($transactions)): ?>
            <div style="padding:32px;text-align:center;color:var(--text2);">No transactions yet</div>
            <?php else: foreach ($transactions as $tx): ?>
            <div style="padding:12px 18px;border-bottom:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="font-size:12px;font-weight:700;text-transform:capitalize;"><?= str_replace('_', ' ', $tx['transaction_type']) ?></div>
                        <div style="font-size:11px;color:var(--text2);"><?= date('d M Y H:i', strtotime($tx['transaction_date'])) ?></div>
                        <?php if ($tx['notes']): ?><div style="font-size:11px;color:var(--text2);"><?= htmlspecialchars($tx['notes']) ?></div><?php endif; ?>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700;<?= in_array($tx['transaction_type'], ['topup', 'commission']) ? 'color:var(--green)' : 'color:var(--red)' ?>">
                            <?= in_array($tx['transaction_type'], ['topup', 'commission']) ? '+' : '-' ?>৳<?= number_format($tx['amount'], 2) ?>
                        </div>
                        <div style="font-size:10px;color:var(--text2);">Bal: ৳<?= number_format($tx['balance_after'] ?? 0, 0) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
