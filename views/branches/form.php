<?php // views/branches/form.php — used for both create and edit ?>
<?php $isEdit = isset($branch) && !empty($branch['id']); ?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Edit Branch' : 'Add Branch' ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('branches') ?>" style="color:var(--blue);text-decoration:none;"><i class="fa-solid fa-code-branch"></i> Branches</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <?= $isEdit ? 'Edit' : 'Add' ?>
        </div>
    </div>
    <a href="<?= base_url('branches') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['success'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.08);">
    <span style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></span>
    <?php unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? base_url('branches/update/' . $branch['id']) : base_url('branches/store') ?>">
    <div class="card fade-in" style="padding:24px;margin-bottom:18px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:18px;color:var(--text);border-bottom:1px solid var(--border);padding-bottom:12px;">
            <i class="fa-solid fa-code-branch" style="color:var(--blue);margin-right:8px;"></i>Branch Information
        </div>

        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;">
            <!-- Name -->
            <div>
                <label class="form-label">Branch Name <span style="color:var(--red);">*</span></label>
                <input type="text" name="name" class="form-input"
                       value="<?= htmlspecialchars($branch['name'] ?? '') ?>"
                       placeholder="e.g. Dhaka Main Branch" required>
            </div>

            <!-- Code -->
            <div>
                <label class="form-label">Branch Code <span style="color:var(--red);">*</span></label>
                <input type="text" name="code" class="form-input"
                       value="<?= htmlspecialchars($branch['code'] ?? '') ?>"
                       placeholder="e.g. DHK01"
                       style="text-transform:uppercase;"
                       oninput="this.value=this.value.toUpperCase()"
                       required>
                <div style="font-size:11px;color:var(--text2);margin-top:4px;">Must be unique. Will be stored in uppercase.</div>
            </div>

            <!-- Phone -->
            <div>
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input"
                       value="<?= htmlspecialchars($branch['phone'] ?? '') ?>"
                       placeholder="e.g. +880 1700-000000">
            </div>

            <!-- Email -->
            <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input"
                       value="<?= htmlspecialchars($branch['email'] ?? '') ?>"
                       placeholder="e.g. dhaka@example.com">
            </div>

            <!-- Manager -->
            <div>
                <label class="form-label">Manager Name</label>
                <input type="text" name="manager" class="form-input"
                       value="<?= htmlspecialchars($branch['manager'] ?? '') ?>"
                       placeholder="e.g. Md. Karim">
            </div>

            <!-- Address -->
            <div>
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-input"
                       value="<?= htmlspecialchars($branch['address'] ?? '') ?>"
                       placeholder="e.g. 123 Main Road, Dhaka">
            </div>
        </div>
    </div>

    <?php if (!$isEdit && !empty($users)): ?>
    <!-- Assign Login Credential (create only) -->
    <div class="card fade-in" style="padding:24px;margin-bottom:18px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:18px;color:var(--text);border-bottom:1px solid var(--border);padding-bottom:12px;">
            <i class="fa-solid fa-key" style="color:var(--yellow);margin-right:8px;"></i>Assign Login Credential <span style="font-size:12px;font-weight:400;color:var(--text2);">(optional)</span>
        </div>
        <div style="max-width:400px;">
            <label class="form-label">Assign User Account</label>
            <select name="credential_user_id" class="form-input">
                <option value="">— No credential assigned —</option>
                <?php foreach ($users as $user): ?>
                <option value="<?= (int)$user['id'] ?>">
                    <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
                    <?= $user['email'] ? '(' . htmlspecialchars($user['email']) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div style="font-size:11px;color:var(--text2);margin-top:4px;">This user will be the designated login for this branch.</div>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-<?= $isEdit ? 'floppy-disk' : 'plus' ?>"></i>
            <?= $isEdit ? 'Save Changes' : 'Create Branch' ?>
        </button>
        <a href="<?= base_url('branches') ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>
