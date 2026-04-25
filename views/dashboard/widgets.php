<?php
/**
 * Cross-module dashboard widgets partial.
 *
 * Displays stat cards for key metrics across new modules.
 * Expects $crossModuleWidgets array from DashboardController::getCrossModuleWidgets().
 *
 * @var array $crossModuleWidgets {
 *   open_tickets: int,
 *   overdue_tasks: int,
 *   low_stock_items: int,
 *   pending_requisitions: int,
 *   warranty_expiring: int,
 *   active_ott_subs: int,
 * }
 */

$widgets = $crossModuleWidgets ?? [];

$openTickets         = (int)($widgets['open_tickets']         ?? 0);
$overdueTasks        = (int)($widgets['overdue_tasks']        ?? 0);
$lowStockItems       = (int)($widgets['low_stock_items']      ?? 0);
$pendingRequisitions = (int)($widgets['pending_requisitions'] ?? 0);
$warrantyExpiring    = (int)($widgets['warranty_expiring']    ?? 0);
$activeOttSubs       = (int)($widgets['active_ott_subs']      ?? 0);
?>

<div style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:16px;margin-bottom:24px;">

    <!-- Open Tickets -->
    <a href="<?= base_url('support/tickets') ?>" style="text-decoration:none;">
        <div class="card stat-card" style="cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-icon" style="background:rgba(220,38,38,0.1);color:#dc2626;">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <?php if ($openTickets > 0): ?>
                <span class="badge badge-red" style="font-size:11px;"><?= $openTickets > 99 ? '99+' : $openTickets ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div class="stat-value" style="font-size:22px;"><?= number_format($openTickets) ?></div>
                <div class="stat-label">Open Tickets</div>
            </div>
        </div>
    </a>

    <!-- Overdue Tasks -->
    <a href="<?= base_url('tasks?filter=overdue') ?>" style="text-decoration:none;">
        <div class="card stat-card" style="cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-icon" style="background:rgba(217,119,6,0.1);color:#d97706;">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <?php if ($overdueTasks > 0): ?>
                <span class="badge badge-yellow" style="font-size:11px;"><?= $overdueTasks > 99 ? '99+' : $overdueTasks ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div class="stat-value" style="font-size:22px;"><?= number_format($overdueTasks) ?></div>
                <div class="stat-label">Overdue Tasks</div>
            </div>
        </div>
    </a>

    <!-- Low Stock Items -->
    <a href="<?= base_url('inventory/stock?filter=low') ?>" style="text-decoration:none;">
        <div class="card stat-card" style="cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-icon" style="background:rgba(124,58,237,0.1);color:#7c3aed;">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <?php if ($lowStockItems > 0): ?>
                <span class="badge badge-purple" style="font-size:11px;"><?= $lowStockItems > 99 ? '99+' : $lowStockItems ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div class="stat-value" style="font-size:22px;"><?= number_format($lowStockItems) ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </div>
    </a>

    <!-- Pending Requisitions -->
    <a href="<?= base_url('purchases/requisitions') ?>" style="text-decoration:none;">
        <div class="card stat-card" style="cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-icon" style="background:rgba(37,99,235,0.1);color:#2563eb;">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <?php if ($pendingRequisitions > 0): ?>
                <span class="badge badge-blue" style="font-size:11px;"><?= $pendingRequisitions > 99 ? '99+' : $pendingRequisitions ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div class="stat-value" style="font-size:22px;"><?= number_format($pendingRequisitions) ?></div>
                <div class="stat-label">Pending Requisitions</div>
            </div>
        </div>
    </a>

    <!-- Warranty Expiring -->
    <a href="<?= base_url('assets?filter=expiring') ?>" style="text-decoration:none;">
        <div class="card stat-card" style="cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-icon" style="background:rgba(217,119,6,0.1);color:#d97706;">
                    <i class="fa-solid fa-building"></i>
                </div>
                <?php if ($warrantyExpiring > 0): ?>
                <span class="badge badge-yellow" style="font-size:11px;"><?= $warrantyExpiring > 99 ? '99+' : $warrantyExpiring ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div class="stat-value" style="font-size:22px;"><?= number_format($warrantyExpiring) ?></div>
                <div class="stat-label">Warranty Expiring</div>
            </div>
        </div>
    </a>

    <!-- Active OTT Subs -->
    <a href="<?= base_url('ott/subscriptions') ?>" style="text-decoration:none;">
        <div class="card stat-card" style="cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-icon" style="background:rgba(22,163,74,0.1);color:#16a34a;">
                    <i class="fa-solid fa-tv"></i>
                </div>
            </div>
            <div>
                <div class="stat-value" style="font-size:22px;"><?= number_format($activeOttSubs) ?></div>
                <div class="stat-label">Active OTT Subs</div>
            </div>
        </div>
    </a>

</div>
