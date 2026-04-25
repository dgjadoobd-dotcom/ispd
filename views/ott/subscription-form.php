<?php // views/ott/subscription-form.php
// Variables: $customers (array), $packages (array)
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Add OTT Subscription</h1>
        <div class="page-breadcrumb">
            <i class="fa-solid fa-tv" style="color:var(--blue)"></i>
            <a href="<?= base_url('ott') ?>" style="color:var(--blue);">OTT</a> &rsaquo;
            <a href="<?= base_url('ott/subscriptions') ?>" style="color:var(--blue);">Subscriptions</a> &rsaquo;
            New
        </div>
    </div>
    <a href="<?= base_url('ott/subscriptions') ?>" class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:12px 18px;margin-bottom:14px;border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<div class="card fade-in" style="max-width:600px;padding:24px;">
    <form method="POST" action="<?= base_url('ott/subscriptions/store') ?>">

        <div style="margin-bottom:16px;">
            <label class="form-label">Customer <span style="color:var(--red);">*</span></label>
            <select name="customer_id" class="form-input" required>
                <option value="">— Select Customer —</option>
                <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>">
                    <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['customer_code']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom:16px;">
            <label class="form-label">OTT Package <span style="color:var(--red);">*</span></label>
            <select name="ott_package_id" class="form-input" required>
                <option value="">— Select OTT Package —</option>
                <?php foreach ($packages as $pkg): ?>
                <option value="<?= $pkg['id'] ?>">
                    <?= htmlspecialchars($pkg['provider_name']) ?> — <?= htmlspecialchars($pkg['name']) ?>
                    (<?= $pkg['validity_days'] ?> days<?= $pkg['price'] > 0 ? ', ৳' . number_format($pkg['price'], 0) : '' ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom:20px;">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-input"
                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
            <div style="font-size:12px;color:var(--text2);margin-top:4px;">
                Expiry date will be calculated automatically based on the package validity.
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Create Subscription
            </button>
            <a href="<?= base_url('ott/subscriptions') ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
