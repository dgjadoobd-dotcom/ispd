<?php
/**
 * Support Ticket Create / Edit Form
 * Requirements 3.2, 3.10
 */
$isEdit  = isset($ticket);
$t       = $ticket ?? [];
$formUrl = $isEdit
    ? base_url('support/tickets/update/' . $t['id'])
    : base_url('support/tickets/store');
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-ticket" style="color:var(--blue);margin-right:10px;"></i>
            <?= $isEdit ? 'Edit Ticket #' . $t['id'] : 'New Support Ticket' ?>
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('support/tickets') ?>">Support Tickets</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span><?= $isEdit ? 'Edit' : 'New' ?></span>
        </div>
    </div>
    <a href="<?= base_url('support/tickets') ?>" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in fade-in-delay-1" style="max-width:800px;">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:15px;font-weight:700;">Ticket Details</h3>
    </div>
    <form method="POST" action="<?= $formUrl ?>">
        <div style="padding:24px;display:grid;gap:20px;">

            <?php if (!$isEdit): ?>
            <!-- Customer -->
            <div>
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-input">
                    <option value="">— Select Customer —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)($t['customer_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['customer_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Subject -->
            <div>
                <label class="form-label">Subject <span style="color:var(--red);">*</span></label>
                <input type="text" name="subject" class="form-input" required
                       placeholder="Brief description of the issue"
                       value="<?= htmlspecialchars($t['subject'] ?? '') ?>">
            </div>

            <!-- Description -->
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="5"
                          placeholder="Detailed description of the issue..."><?= htmlspecialchars($t['description'] ?? '') ?></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <!-- Priority -->
                <div>
                    <label class="form-label">Priority <span style="color:var(--red);">*</span></label>
                    <select name="priority" class="form-input" required>
                        <?php foreach (['urgent' => 'Urgent (SLA: 2h)', 'high' => 'High (SLA: 8h)', 'medium' => 'Medium (SLA: 24h)', 'low' => 'Low (SLA: 72h)'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($t['priority'] ?? 'medium') === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="font-size:11px;color:var(--text2);margin-top:4px;">
                        <i class="fa-solid fa-clock"></i> SLA deadline is calculated automatically on creation.
                    </p>
                </div>

                <!-- Category -->
                <div>
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input">
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)($t['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($isEdit): ?>
            <!-- Status (edit only) -->
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <?php foreach (['open','in_progress','resolved','closed','sla_breached'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($t['status'] ?? 'open') === $s ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $s)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!$isEdit): ?>
            <!-- Assign To (optional on create) -->
            <div>
                <label class="form-label">Assign To (optional)</label>
                <select name="assigned_to" class="form-input">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>">
                        <?= htmlspecialchars($emp['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p style="font-size:11px;color:var(--text2);margin-top:4px;">
                    <i class="fa-solid fa-sms"></i> An SMS notification will be sent to the assigned employee.
                </p>
            </div>
            <?php endif; ?>

        </div>

        <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;">
            <a href="<?= base_url('support/tickets') ?>" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> <?= $isEdit ? 'Save Changes' : 'Create Ticket' ?>
            </button>
        </div>
    </form>
</div>
