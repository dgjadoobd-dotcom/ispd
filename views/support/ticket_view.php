<?php
/**
 * Support Ticket Detail View
 * Requirements 3.4, 3.5, 3.7, 3.8
 */
$statusColors = [
    'open'         => 'badge-blue',
    'in_progress'  => 'badge-yellow',
    'resolved'     => 'badge-green',
    'closed'       => 'badge-gray',
    'sla_breached' => 'badge-red',
];
$priorityColors = [
    'urgent' => 'badge-red',
    'high'   => 'badge-yellow',
    'medium' => 'badge-blue',
    'low'    => 'badge-gray',
];

$slaDeadline = !empty($ticket['sla_deadline']) ? new DateTime($ticket['sla_deadline']) : null;
$now         = new DateTime();
$slaOverdue  = $slaDeadline && $now > $slaDeadline && !in_array($ticket['status'], ['resolved','closed']);
$canEdit     = PermissionHelper::hasPermission('support.edit');
$canAssign   = PermissionHelper::hasPermission('support.assign');
$canResolve  = PermissionHelper::hasPermission('support.resolve');
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-ticket" style="color:var(--blue);margin-right:10px;"></i>
            Ticket #<?= $ticket['id'] ?>
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('support/tickets') ?>">Support Tickets</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>#<?= $ticket['id'] ?></span>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($canEdit && !in_array($ticket['status'], ['resolved','closed'])): ?>
        <a href="<?= base_url('support/tickets/edit/' . $ticket['id']) ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-pen"></i> Edit
        </a>
        <?php endif; ?>
        <a href="<?= base_url('support/tickets') ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php foreach (['success','warning','error'] as $msgType): ?>
<?php if (!empty($_SESSION[$msgType])): ?>
<?php
$msgStyles = [
    'success' => 'background:#dcfce7;border-color:#86efac;color:#15803d;',
    'warning' => 'background:#fef3c7;border-color:#fcd34d;color:#b45309;',
    'error'   => 'background:#fee2e2;border-color:#fecaca;color:#b91c1c;',
];
$msgIcons = ['success' => 'circle-check', 'warning' => 'triangle-exclamation', 'error' => 'circle-xmark'];
?>
<div style="<?= $msgStyles[$msgType] ?>border:1px solid;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-<?= $msgIcons[$msgType] ?>"></i> <?= htmlspecialchars($_SESSION[$msgType]) ?>
</div>
<?php unset($_SESSION[$msgType]); endif; endforeach; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;">

    <!-- Left: Ticket details + comments -->
    <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Ticket Info Card -->
        <div class="card fade-in">
            <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h2 style="font-size:17px;font-weight:700;"><?= htmlspecialchars($ticket['subject']) ?></h2>
                    <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
                        <span class="badge <?= $statusColors[$ticket['status']] ?? 'badge-gray' ?>">
                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                        </span>
                        <span class="badge <?= $priorityColors[$ticket['priority']] ?? 'badge-gray' ?>">
                            <?= ucfirst($ticket['priority']) ?> Priority
                        </span>
                        <?php if (!empty($ticket['category_name'])): ?>
                        <span class="badge badge-purple"><?= htmlspecialchars($ticket['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($slaDeadline): ?>
                <div style="text-align:right;">
                    <div style="font-size:11px;color:var(--text2);margin-bottom:2px;">SLA Deadline</div>
                    <div style="font-size:13px;font-weight:700;<?= $slaOverdue ? 'color:#dc2626;' : 'color:var(--text);' ?>">
                        <?= $slaDeadline->format('d M Y H:i') ?>
                    </div>
                    <?php if ($slaOverdue): ?>
                    <div style="font-size:11px;color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i> SLA Breached</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="padding:20px 24px;">
                <p style="font-size:14px;color:var(--text);line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($ticket['description'] ?? '') ?></p>
            </div>
            <?php if (!empty($ticket['resolution_notes'])): ?>
            <div style="padding:16px 24px;border-top:1px solid var(--border);background:#f0fdf4;border-radius:0 0 10px 10px;">
                <div style="font-size:12px;font-weight:700;color:#15803d;margin-bottom:6px;">
                    <i class="fa-solid fa-circle-check"></i> Resolution Notes
                </div>
                <p style="font-size:13px;color:#166534;line-height:1.6;"><?= htmlspecialchars($ticket['resolution_notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Comments / Activity Thread -->
        <div class="card fade-in fade-in-delay-1">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:700;">
                    <i class="fa-solid fa-comments" style="color:var(--blue);margin-right:6px;"></i>
                    Activity Thread (<?= count($ticket['comments'] ?? []) ?>)
                </h3>
            </div>

            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;max-height:500px;overflow-y:auto;">
                <?php if (empty($ticket['comments'])): ?>
                <p style="color:var(--text2);font-size:13px;text-align:center;padding:20px 0;">No activity yet.</p>
                <?php else: ?>
                <?php foreach ($ticket['comments'] as $comment): ?>
                <div style="display:flex;gap:12px;<?= $comment['is_internal'] ? 'opacity:0.8;' : '' ?>">
                    <div style="width:32px;height:32px;border-radius:8px;background:<?= $comment['is_internal'] ? '#f1f5f9' : '#dbeafe' ?>;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                color:<?= $comment['is_internal'] ? '#64748b' : '#2563eb' ?>;font-size:13px;font-weight:700;">
                        <?= strtoupper(substr($comment['author_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($comment['author_name'] ?? 'Unknown') ?></span>
                            <?php if ($comment['is_internal']): ?>
                            <span style="font-size:10px;background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:4px;">Internal</span>
                            <?php endif; ?>
                            <span style="font-size:11px;color:var(--text2);margin-left:auto;">
                                <?= date('d M Y H:i', strtotime($comment['created_at'])) ?>
                            </span>
                        </div>
                        <div style="font-size:13px;color:var(--text);line-height:1.6;background:var(--bg3);
                                    padding:10px 14px;border-radius:8px;white-space:pre-wrap;">
                            <?= htmlspecialchars($comment['message']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Add Comment Form -->
            <?php if (!in_array($ticket['status'], ['closed'])): ?>
            <div style="padding:16px 20px;border-top:1px solid var(--border);">
                <form method="POST" action="<?= base_url('support/tickets/comment/' . $ticket['id']) ?>">
                    <textarea name="message" class="form-input" rows="3"
                              placeholder="Add a comment or note..." required
                              style="margin-bottom:10px;resize:vertical;"></textarea>
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="is_internal" value="1">
                            Internal note (not visible to customer)
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-paper-plane"></i> Add Comment
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Right: Sidebar panels -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Ticket Meta -->
        <div class="card fade-in">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <h4 style="font-size:13px;font-weight:700;">Ticket Info</h4>
            </div>
            <div style="padding:14px 18px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--text2);">Customer</span>
                    <span style="font-weight:600;"><?= htmlspecialchars($ticket['customer_name'] ?? '—') ?></span>
                </div>
                <?php if (!empty($ticket['customer_phone'])): ?>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--text2);">Phone</span>
                    <span><?= htmlspecialchars($ticket['customer_phone']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--text2);">Branch</span>
                    <span><?= htmlspecialchars($ticket['branch_name'] ?? '—') ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--text2);">Created</span>
                    <span><?= date('d M Y H:i', strtotime($ticket['created_at'])) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--text2);">Assigned To</span>
                    <span style="font-weight:600;"><?= htmlspecialchars($ticket['assigned_name'] ?? 'Unassigned') ?></span>
                </div>
            </div>
        </div>

        <!-- Assign Ticket -->
        <?php if ($canAssign && !in_array($ticket['status'], ['resolved','closed'])): ?>
        <div class="card fade-in fade-in-delay-1">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <h4 style="font-size:13px;font-weight:700;"><i class="fa-solid fa-user-check" style="color:var(--blue);margin-right:6px;"></i>Assign Ticket</h4>
            </div>
            <form method="POST" action="<?= base_url('support/tickets/assign/' . $ticket['id']) ?>">
                <div style="padding:14px 18px;display:flex;flex-direction:column;gap:10px;">
                    <div>
                        <label class="form-label" style="font-size:12px;">Assign To</label>
                        <select name="employee_id" class="form-input" style="font-size:13px;padding:8px 12px;" required>
                            <option value="">— Select Employee —</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= (int)$ticket['assigned_to'] === (int)$emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:12px;">Notes (optional)</label>
                        <input type="text" name="notes" class="form-input" style="font-size:13px;padding:8px 12px;"
                               placeholder="Assignment notes...">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">
                        <i class="fa-solid fa-user-check"></i> Assign &amp; Notify via SMS
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Resolve Ticket -->
        <?php if ($canResolve && in_array($ticket['status'], ['open','in_progress','sla_breached'])): ?>
        <div class="card fade-in fade-in-delay-2">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <h4 style="font-size:13px;font-weight:700;"><i class="fa-solid fa-circle-check" style="color:#16a34a;margin-right:6px;"></i>Resolve Ticket</h4>
            </div>
            <form method="POST" action="<?= base_url('support/tickets/resolve/' . $ticket['id']) ?>">
                <div style="padding:14px 18px;display:flex;flex-direction:column;gap:10px;">
                    <div>
                        <label class="form-label" style="font-size:12px;">Resolution Notes <span style="color:var(--red);">*</span></label>
                        <textarea name="resolution_notes" class="form-input" rows="3" required
                                  style="font-size:13px;resize:vertical;"
                                  placeholder="Describe how the issue was resolved..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm" style="width:100%;">
                        <i class="fa-solid fa-circle-check"></i> Mark as Resolved
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Close Ticket -->
        <?php if ($canEdit && $ticket['status'] === 'resolved'): ?>
        <div class="card fade-in fade-in-delay-2">
            <div style="padding:14px 18px;">
                <form method="POST" action="<?= base_url('support/tickets/close/' . $ticket['id']) ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;"
                            onclick="return confirm('Close this ticket?')">
                        <i class="fa-solid fa-lock"></i> Close Ticket
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Assignment History -->
        <?php if (!empty($ticket['assignments'])): ?>
        <div class="card fade-in fade-in-delay-3">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <h4 style="font-size:13px;font-weight:700;">Assignment History</h4>
            </div>
            <div style="padding:14px 18px;display:flex;flex-direction:column;gap:10px;">
                <?php foreach ($ticket['assignments'] as $a): ?>
                <div style="font-size:12px;border-left:3px solid var(--blue);padding-left:10px;">
                    <div style="font-weight:600;"><?= htmlspecialchars($a['assigned_to_name'] ?? '—') ?></div>
                    <div style="color:var(--text2);">
                        by <?= htmlspecialchars($a['assigned_by_name'] ?? '—') ?>
                        · <?= date('d M Y H:i', strtotime($a['assigned_at'])) ?>
                    </div>
                    <?php if (!empty($a['notes'])): ?>
                    <div style="color:var(--text2);margin-top:2px;"><?= htmlspecialchars($a['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
