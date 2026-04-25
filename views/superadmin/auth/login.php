<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            position: relative; overflow: hidden;
        }
        body::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(124,58,237,0.15) 0%, transparent 60%),
                        radial-gradient(ellipse at 70% 20%, rgba(37,99,235,0.1) 0%, transparent 50%);
        }
        .login-card {
            background: rgba(255,255,255,0.04); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;
            padding: 40px; width: 100%; max-width: 420px; position: relative; z-index: 1;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .login-logo {
            width: 56px; height: 56px; border-radius: 14px; margin: 0 auto 20px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #fff; box-shadow: 0 8px 24px rgba(124,58,237,0.4);
        }
        h1 { font-size: 22px; font-weight: 800; color: #fff; text-align: center; margin-bottom: 4px; }
        .subtitle { font-size: 13px; color: rgba(255,255,255,0.45); text-align: center; margin-bottom: 28px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.6); margin-bottom: 6px; letter-spacing: 0.5px; text-transform: uppercase; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.3); font-size: 14px; }
        input[type="text"], input[type="password"] {
            width: 100%; background: rgba(255,255,255,0.07); border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 10px; padding: 12px 14px 12px 40px; font-size: 14px; color: #fff;
            transition: all 0.2s; font-family: 'Inter', sans-serif;
        }
        input:focus { outline: none; border-color: #7c3aed; background: rgba(124,58,237,0.1); box-shadow: 0 0 0 3px rgba(124,58,237,0.2); }
        input::placeholder { color: rgba(255,255,255,0.25); }
        .btn-login {
            width: 100%; padding: 13px; border-radius: 10px; border: none; cursor: pointer;
            background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff;
            font-size: 14px; font-weight: 700; margin-top: 8px;
            transition: all 0.2s; box-shadow: 0 8px 20px rgba(124,58,237,0.35);
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(124,58,237,0.45); }
        .btn-login:active { transform: translateY(0); }
        .error-box {
            background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.3);
            border-radius: 8px; padding: 10px 14px; margin-bottom: 16px;
            font-size: 13px; color: #fca5a5; display: flex; align-items: center; gap: 8px;
        }
        .back-link {
            display: block; text-align: center; margin-top: 20px;
            font-size: 12px; color: rgba(255,255,255,0.35); text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover { color: rgba(255,255,255,0.6); }
        .security-note {
            display: flex; align-items: center; gap: 8px; margin-top: 20px;
            padding: 10px 14px; background: rgba(124,58,237,0.1); border-radius: 8px;
            font-size: 11px; color: rgba(255,255,255,0.4);
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo"><i class="fa-solid fa-shield-halved"></i></div>
    <h1>Super Admin</h1>
    <p class="subtitle">Owner Control Panel — Restricted Access</p>

    <?php if (!empty($_SESSION['sa_login_error'])): ?>
    <div class="error-box">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($_SESSION['sa_login_error']) ?>
    </div>
    <?php unset($_SESSION['sa_login_error']); endif; ?>

    <form method="POST" action="<?= base_url('superadmin/login') ?>">
        <div class="form-group">
            <label>Username</label>
            <div class="input-wrap">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="username" placeholder="Enter username" autocomplete="username" required>
            </div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="fa-solid fa-right-to-bracket" style="margin-right:8px;"></i>Sign In
        </button>
    </form>

    <div class="security-note">
        <i class="fa-solid fa-shield-check"></i>
        This area is restricted to authorized super administrators only.
    </div>

    <a href="<?= base_url('login') ?>" class="back-link">
        <i class="fa-solid fa-arrow-left" style="margin-right:4px;"></i>Back to regular login
    </a>
</div>
</body>
</html>
