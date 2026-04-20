<?php // views/workorders/view.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Work Order: <?= htmlspecialchars($wo['wo_number']) ?></h1>
        <div class="page-breadcrumb" style="display:flex;align-items:center;gap:8px;">
            <a href="<?= base_url('workorders') ?>" style="color:var(--blue);text-decoration:none;">Work Orders</a>
            <i class="fa-solid fa-chevron-right" style="font-size:9px;"></i>
            <?php $wsc=['pending'=>'badge-yellow','assigned'=>'badge-blue','in_progress'=>'badge-purple','completed'=>'badge-green','cancelled'=>'badge-gray'];
            echo '<span class="badge '.($wsc[$wo['status']]??'badge-gray').'">'.ucfirst(str_replace('_',' ',$wo['status'])).'</span>'; ?>
        </div>
    </div>
    <a href="<?= base_url('workorders') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;" class="fade-in">
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Details -->
        <div class="card" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;"><i class="fa-solid fa-file-lines" style="color:var(--blue);margin-right:8px;"></i>Work Order Details</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div><div style="font-size:11px;color:var(--text2);">Type</div><div class="badge badge-gray"><?= ucfirst(str_replace('_',' ',$wo['type'])) ?></div></div>
                <div><div style="font-size:11px;color:var(--text2);">Priority</div>
                    <?php $pc=['urgent'=>'badge-red','high'=>'badge-yellow','normal'=>'badge-blue','low'=>'badge-gray'];
                    echo '<span class="badge '.($pc[$wo['priority']]??'badge-gray').'">'.ucfirst($wo['priority']).'</span>'; ?>
                </div>
                <div><div style="font-size:11px;color:var(--text2);">Branch</div><div style="font-weight:500;"><?= htmlspecialchars($wo['branch_name']??'—') ?></div></div>
                <div><div style="font-size:11px;color:var(--text2);">Created</div><div><?= date('d M Y H:i',strtotime($wo['created_at'])) ?></div></div>
                <?php if($wo['scheduled_date']): ?>
                <div><div style="font-size:11px;color:var(--text2);">Scheduled</div><div><?= date('d M Y',strtotime($wo['scheduled_date'])) ?></div></div>
                <?php endif; ?>
                <?php if($wo['completed_at']): ?>
                <div><div style="font-size:11px;color:var(--text2);">Completed</div><div style="color:var(--green);"><?= date('d M Y H:i',strtotime($wo['completed_at'])) ?></div></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;"><div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Title</div><div style="font-size:15px;font-weight:700;"><?= htmlspecialchars($wo['title']) ?></div></div>
                <?php if($wo['description']): ?>
                <div style="grid-column:1/-1;"><div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Description</div><div style="line-height:1.6;color:var(--text2);font-size:13px;"><?= nl2br(htmlspecialchars($wo['description'])) ?></div></div>
                <?php endif; ?>
                <?php if($wo['address']): ?>
                <div style="grid-column:1/-1;"><div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Address</div><div><?= htmlspecialchars($wo['address']) ?></div></div>
                <?php endif; ?>
                <?php if($wo['completion_notes']): ?>
                <div style="grid-column:1/-1;background:rgba(34,197,94,0.08);border-radius:10px;padding:12px;">
                    <div style="font-size:11px;color:var(--text2);margin-bottom:4px;">Completion Notes</div>
                    <div style="font-size:13px;"><?= nl2br(htmlspecialchars($wo['completion_notes'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- AI Technician Assistant -->
        <?php if (env('AI_ENABLED') && !empty($aiSuggestion)): ?>
        <div class="card" style="padding:20px; border-left: 4px solid var(--blue); background: linear-gradient(to right, rgba(37, 99, 235, 0.05), transparent);">
            <div style="font-size:14px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-robot" style="color:var(--blue);"></i>
                Technician's AI Assistant
            </div>
            <div style="font-size:13px;line-height:1.6;color:var(--text);background:var(--bg2);padding:14px;border-radius:8px;border:1px dashed var(--border);">
                <?= nl2br(sanitize($aiSuggestion)) ?>
            </div>
            <div style="font-size:11px;color:var(--text2);margin-top:10px;font-style:italic;">
                <i class="fa-solid fa-info-circle"></i> AI-generated suggestions based on work order details.
            </div>
        </div>
        <?php endif; ?>

        <!-- Update Status -->
        <?php if($wo['status'] !== 'completed' && $wo['status'] !== 'cancelled'): ?>
        <div class="card" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:14px;"><i class="fa-solid fa-rotate" style="color:var(--purple);margin-right:8px;"></i>Update Status</div>
            <form method="POST" action="<?= base_url("workorders/status/{$wo['id']}") ?>" style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-input" required>
                            <?php foreach(['pending','assigned','in_progress','completed','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $wo['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Assign Technician</label>
                        <select name="technician_id" class="form-input">
                            <option value="">Unassigned</option>
                            <?php foreach($technicians as $t): ?><option value="<?= $t['id'] ?>" <?= $wo['technician_id']==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label">Completion Notes</label>
                    <textarea name="completion_notes" class="form-input" rows="2" placeholder="What was done?"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Customer -->
        <?php if($wo['customer_name']): ?>
        <div class="card" style="padding:18px;">
            <div style="font-size:12px;color:var(--text2);margin-bottom:10px;font-weight:700;">CUSTOMER</div>
            <div style="font-size:15px;font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($wo['customer_name']) ?></div>
            <div style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($wo['customer_phone']??'') ?></div>
            <div style="font-size:12px;color:var(--text2);margin-top:4px;"><?= htmlspecialchars($wo['customer_address']??'') ?></div>
            <a href="<?= base_url("customers/view/{$wo['customer_id']}") ?>" class="btn btn-ghost" style="margin-top:12px;width:100%;justify-content:center;">View Customer</a>
        </div>
        <?php endif; ?>

        <!-- Technician -->
        <div class="card" style="padding:18px;">
            <div style="font-size:12px;color:var(--text2);margin-bottom:10px;font-weight:700;">TECHNICIAN</div>
            <?php if($wo['technician_name']): ?>
            <div style="font-size:15px;font-weight:700;"><?= htmlspecialchars($wo['technician_name']) ?></div>
            <div style="font-size:12px;color:var(--text2);"><?= htmlspecialchars($wo['technician_phone']??'') ?></div>
            <?php else: ?>
            <div style="color:var(--text2);font-size:13px;">Not assigned yet</div>
            <?php endif; ?>
        </div>
    </div>
</div>
