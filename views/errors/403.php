<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Digital ISP ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; } body { background: #0d1117; min-height: 100vh; display: flex; align-items: center; justify-content: center; }</style>
</head>
<body>
    <div class="text-center px-6">
        <div style="font-size:100px;font-weight:900;background:linear-gradient(135deg,#ef4444,#b91c1c);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;">403</div>
        <h1 style="color:#e6edf3;font-size:22px;font-weight:700;margin-top:16px;">Access Denied</h1>
        <p style="color:#8b949e;margin-top:8px;font-size:14px;">
            <?= isset($errorMessage) && $errorMessage !== ''
                ? htmlspecialchars($errorMessage, ENT_QUOTES)
                : "You don't have permission to access this resource." ?>
        </p>
        <?php if (function_exists('base_url')): ?>
        <a href="<?= base_url('dashboard') ?>" style="display:inline-flex;align-items:center;gap:8px;margin-top:24px;padding:11px 22px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px;">
            ← Back to Dashboard
        </a>
        <?php else: ?>
        <a href="/dashboard" style="display:inline-flex;align-items:center;gap:8px;margin-top:24px;padding:11px 22px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px;">
            ← Back to Dashboard
        </a>
        <?php endif; ?>
    </div>
</body>
</html>
