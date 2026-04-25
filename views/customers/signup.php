<?php // views/customers/signup.php — Public online signup form
$success = isset($_GET['success']) && $_SESSION['signup_success'] ?? false;
$name    = $_SESSION['signup_name'] ?? '';
if ($success) { unset($_SESSION['signup_success'], $_SESSION['signup_name']); }
?>

<div class="pub-card">
    <div class="pub-card-header">
        <h1><i class="fa-solid fa-wifi" style="margin-right:10px;"></i>New Connection Request</h1>
        <p>Fill in your details and we'll contact you to set up your internet connection.</p>
    </div>
    <div class="pub-card-body">

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i>
            <strong>Request submitted successfully!</strong><br>
            Thank you, <strong><?= htmlspecialchars($name) ?></strong>. Our team will contact you within 24 hours to confirm your connection.
        </div>
        <div style="text-align:center;margin-top:16px;">
            <a href="<?= base_url('signup') ?>" style="color:#2563eb;font-size:13px;text-decoration:none;">
                <i class="fa-solid fa-plus" style="margin-right:4px;"></i>Submit another request
            </a>
        </div>

        <?php else: ?>

        <?php if (!empty($_SESSION['signup_error'])): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i>
            <?= htmlspecialchars($_SESSION['signup_error']) ?>
            <?php unset($_SESSION['signup_error']); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('signup') ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" placeholder="Your full name" required
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone Number <span class="required">*</span></label>
                    <input type="tel" name="phone" placeholder="01XXXXXXXXX" required
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Installation Address <span class="required">*</span></label>
                <textarea name="address" rows="2" placeholder="House/Road/Area where you need the connection" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Preferred Package</label>
                    <select name="package_id">
                        <option value="">— Select a package —</option>
                        <?php foreach ($packages as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($_POST['package_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                            (<?= $p['speed_download'] ?>↓/<?= $p['speed_upload'] ?>↑ Mbps)
                            — ৳<?= number_format($p['price'], 0) ?>/mo
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Area / Zone</label>
                    <select name="zone_id">
                        <option value="">— Select your area —</option>
                        <?php foreach ($zones as $z): ?>
                        <option value="<?= $z['id'] ?>" <?= ($_POST['zone_id'] ?? '') == $z['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($z['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Additional Notes</label>
                <textarea name="notes" rows="2" placeholder="Any special requirements or notes..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>

            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:12px;color:#0369a1;">
                <i class="fa-solid fa-info-circle" style="margin-right:6px;"></i>
                By submitting this form, you agree to be contacted by our team. Your information will only be used to process your connection request.
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-paper-plane" style="margin-right:8px;"></i>Submit Connection Request
            </button>

        </form>
        <?php endif; ?>

    </div>
</div>
