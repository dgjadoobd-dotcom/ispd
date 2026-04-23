<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGITAL ISP ERP — Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0e1a; }
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .glass-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .login-btn {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            transition: all 0.3s ease;
        }
        .login-btn:hover { filter: brightness(1.15); transform: translateY(-1px); }
        .input-field {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            color: #e2e8f0;
            transition: all 0.3s;
        }
        .input-field:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(59,130,246,0.1);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .input-field::placeholder { color: rgba(255,255,255,0.3); }
        .bg-grid {
            background-image: radial-gradient(rgba(59,130,246,0.15) 1px, transparent 1px);
            background-size: 30px 30px;
        }
        .orb1 { background: radial-gradient(circle, rgba(59,130,246,0.4), transparent 70%); }
        .orb2 { background: radial-gradient(circle, rgba(139,92,246,0.4), transparent 70%); }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        .float-anim { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="h-full bg-grid">
    <!-- Background Orbs -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="orb1 absolute w-96 h-96 rounded-full -top-20 -left-20 opacity-40 float-anim"></div>
        <div class="orb2 absolute w-80 h-80 rounded-full -bottom-20 -right-20 opacity-30 float-anim" style="animation-delay:3s"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center px-4 relative z-10">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                    <i class="fa-solid fa-network-wired text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold gradient-text">DIGITAL ISP ERP</h1>
                <p class="text-gray-400 text-sm mt-1">Bangladesh ISP Management System</p>
            </div>

            <!-- Login Card -->
            <div class="glass-card rounded-2xl p-8">
                <h2 class="text-white text-xl font-semibold mb-6">Sign in to your account</h2>

                <?php if (!empty($error)): ?>
                <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 mb-5 text-sm">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('login') ?>" id="loginForm">
                    <div class="mb-5">
                        <label class="text-gray-300 text-sm font-medium mb-2 block">Username</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
                                <i class="fa-solid fa-user text-sm"></i>
                            </span>
                            <input type="text" name="username" id="username"
                                   placeholder="Enter your username"
                                   value="<?= sanitize($_POST['username'] ?? '') ?>"
                                   class="input-field w-full rounded-xl px-4 py-3 pl-10 text-sm"
                                   required autofocus>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="text-gray-300 text-sm font-medium mb-2 block">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </span>
                            <input type="password" name="password" id="password"
                                   placeholder="Enter your password"
                                   class="input-field w-full rounded-xl px-4 py-3 pl-10 pr-10 text-sm"
                                   required>
                            <button type="button" onclick="togglePassword()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition-colors">
                                <i class="fa-solid fa-eye text-sm" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="login-btn w-full text-white font-semibold py-3 rounded-xl text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Sign In
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-white/10">
                    <p class="text-gray-500 text-xs text-center">For access credentials, contact your system administrator.</p>
                </div>
            </div>

            <!-- Footer info -->
            <div class="text-center mt-6 space-y-1">
                <p class="text-gray-600 text-xs">DIGITAL ISP ERP v2.0.0 &mdash; Made for Bangladesh ISPs</p>
                <div class="flex items-center justify-center gap-4 text-xs text-gray-700">
                    <span><i class="fa-solid fa-shield-halved mr-1"></i>Secure</span>
                    <span><i class="fa-solid fa-bolt mr-1"></i>Fast</span>
                    <span><i class="fa-solid fa-globe mr-1"></i>Multi-Branch</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pw = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pw.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing in...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
