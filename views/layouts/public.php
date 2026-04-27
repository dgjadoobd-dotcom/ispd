<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'FCNCHBD ISP ERP') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f0f4f8; min-height: 100vh; display: flex; flex-direction: column; }
        .pub-header {
            background: #1a2942; color: #fff; padding: 16px 24px;
            display: flex; align-items: center; gap: 12px;
        }
        .pub-header .logo { width: 36px; height: 36px; border-radius: 8px; background: #2563eb; display: flex; align-items: center; justify-content: center; }
        .pub-header .brand { font-size: 16px; font-weight: 700; }
        .pub-header .tagline { font-size: 11px; color: rgba(255,255,255,0.5); }
        .pub-main { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 32px 16px; }
        .pub-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); width: 100%; max-width: 600px; overflow: hidden; }
        .pub-card-header { background: linear-gradient(135deg, #1a2942, #2563eb); color: #fff; padding: 28px 32px; }
        .pub-card-header h1 { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
        .pub-card-header p { font-size: 13px; opacity: 0.8; }
        .pub-card-body { padding: 28px 32px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
            font-size: 14px; color: #111827; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .btn-submit {
            width: 100%; padding: 13px; background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-submit:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #15803d; }
        .pub-footer { text-align: center; padding: 16px; font-size: 12px; color: #9ca3af; }
        .required { color: #dc2626; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .pub-card-body { padding: 20px; } }
    </style>
</head>
<body>
<header class="pub-header">
    <div class="logo"><i class="fa-solid fa-network-wired" style="color:#fff;font-size:16px;"></i></div>
    <div>
        <div class="brand"><?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'FCNCHBD ISP ERP' ?></div>
        <div class="tagline">Internet Service Provider</div>
    </div>
</header>

<main class="pub-main">
    <?php require_once $viewFile; ?>
</main>

<footer class="pub-footer">
    &copy; <?= date('Y') ?> <?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'FCNCHBD ISP ERP' ?>. All rights reserved.
</footer>
</body>
</html>
