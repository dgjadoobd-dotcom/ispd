<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — FCNCHBD ISP ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; } body { background: #0d1117; min-height: 100vh; display: flex; align-items: center; justify-content: center; }</style>
</head>
<body>
    <div class="text-center px-6">
        <div style="font-size:100px;font-weight:900;background:linear-gradient(135deg,#f59e0b,#d97706);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;">500</div>
        <h1 style="color:#e6edf3;font-size:22px;font-weight:700;margin-top:16px;">Internal Server Error</h1>
        <p style="color:#8b949e;margin-top:8px;font-size:14px;">
            <?= isset($errorMessage) && $errorMessage !== ''
                ? htmlspecialchars($errorMessage, ENT_QUOTES)
                : 'Something went wrong on our end. Please try again later.' ?>
        </p>
        <?php if (function_exists('base_url')): ?>
        <a href="<?= base_url('dashboard') ?>" style="display:inline-flex;align-items:center;gap:8px;margin-top:24px;padding:11px 22px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px;">
            ← Back to Dashboard
        </a>
        <?php else: ?>
        <a href="/dashboard" style="display:inline-flex;align-items:center;gap:8px;margin-top:24px;padding:11px 22px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px;">
            ← Back to Dashboard
        </a>
        <?php endif; ?>
    </div>
</body>
</html>
