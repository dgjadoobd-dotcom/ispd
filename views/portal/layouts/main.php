<?php
if (!isset($db)) { $db = Database::getInstance(); }
$settings = $db->fetchAll("SELECT setting_key, setting_value FROM portal_settings");
$portalSettings = [];
foreach ($settings as $s) { $portalSettings[$s['setting_key']] = $s['setting_value']; }
$portalName = $portalSettings['portal_name'] ?? 'SelfCare';
$isDark = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark';
$themeClass = $isDark ? 'dark' : 'light';
$customer = $portalCustomer ?? [];
$dueAmount = $customer['due_amount'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en" class="<?= $themeClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'SelfCare') ?> - <?= sanitize($portalName) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        primary: { 50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e' },
                        accent: { 50:'#fdf4ff',100:'#fae8ff',200:'#f5d0fe',300:'#f0abfc',400:'#e879f9',500:'#d946ef',600:'#c026d3',700:'#a21caf',800:'#86198f',900:'#701a75' },
                        dark: { 50:'#f8fafc',100:'#f1f5f9',200:'#e2e8f0',300:'#cbd5e1',400:'#94a3b8',500:'#64748b',600:'#475569',700:'#334155',800:'#1e293b',900:'#0f172a',950:'#020617' }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/portal.css') ?>">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .glass-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-card-dark { background: rgba(15,23,42,0.6); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }
        .dark .glass-card { background: rgba(30,41,59,0.6); border-color: rgba(255,255,255,0.08); }
        .gradient-text { background: linear-gradient(135deg, #0ea5e9, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .gradient-border { background: linear-gradient(135deg, #0ea5e9, #d946ef); padding: 2px; border-radius: 14px; }
        .glow-primary { box-shadow: 0 0 40px rgba(14,165,233,0.3); }
        .glow-accent { box-shadow: 0 0 40px rgba(217,70,239,0.3); }
        .status-dot { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .nav-active { background: linear-gradient(135deg, rgba(14,165,233,0.2), rgba(217,70,239,0.2)); border-left: 3px solid #0ea5e9; }
        .dark .nav-active { background: linear-gradient(135deg, rgba(14,165,233,0.3), rgba(217,70,239,0.3)); }
        main { min-width: 0; }
        .portal-page { overflow-x: auto; }
        .portal-page > * { max-width: 100%; }
        .portal-page table { max-width: 100%; }
        .portal-page img, .portal-page canvas, .portal-page video { max-width: 100%; height: auto; }
        .portal-pay-label { display: inline; }

        @media (max-width: 1024px) {
            .portal-page { padding: 1rem; }
            .portal-page table { min-width: 620px; }
            .portal-date { display: none; }
        }

        @media (max-width: 768px) {
            .portal-topbar {
                height: auto;
                min-height: 4rem;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                gap: 0.5rem;
            }
            .portal-topbar h2 { font-size: 1rem; line-height: 1.25rem; }
            .portal-actions { gap: 0.5rem; }
            .portal-pay-btn {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                font-size: 0.8125rem;
            }
            .portal-pay-label { display: none; }
            .portal-pay-btn i { margin-right: 0; }
            .portal-modal-card {
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-dark-900 via-dark-800 to-dark-900 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-72 bg-dark-900/95 dark:bg-dark-950/95 backdrop-blur-xl shadow-2xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 border-r border-dark-700/50">
            <div class="h-full flex flex-col">
                <!-- Logo -->
                <div class="h-20 flex items-center px-6 border-b border-dark-700/50 bg-dark-900/50">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-accent-500 flex items-center justify-center shadow-lg glow-primary">
                            <i class="fas fa-bolt text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white"><?= sanitize($portalName) ?></h1>
                            <p class="text-xs text-dark-400">SelfCare Portal</p>
                        </div>
                    </div>
                </div>

                <!-- Customer Info Card -->
                <div class="p-4 mx-3 mt-4 rounded-2xl glass-card">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-500 to-accent-500 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                            <?= strtoupper(substr($portalCustomer['full_name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate"><?= sanitize($portalCustomer['full_name'] ?? 'Customer') ?></p>
                            <p class="text-xs text-dark-400"><?= sanitize($portalCustomer['pppoe_username'] ?? '') ?></p>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-green-500 status-dot"></span>
                            <span class="text-xs text-green-400 font-medium">Active</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-dark-700/50">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-dark-400">Package</span>
                            <span class="text-sm font-semibold text-primary-400"><?= sanitize($portalCustomer['package_name'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto py-4 px-3">
                    <div class="px-3 mb-3 text-xs font-semibold text-dark-500 uppercase tracking-wider">Main Menu</div>
                    <a href="<?= base_url('portal/dashboard') ?>" class="nav-item flex items-center px-4 py-3 mx-2 rounded-xl <?= ($currentPage ?? '') === 'dashboard' ? 'nav-active text-white' : 'text-dark-300 hover:bg-dark-800 hover:text-white' ?>">
                        <div class="w-8 h-8 rounded-lg bg-dark-800 flex items-center justify-center mr-3 <?= ($currentPage ?? '') === 'dashboard' ? 'bg-primary-500/20 text-primary-400' : 'text-dark-400' ?>">
                            <i class="fas fa-th-large text-sm"></i>
                        </div> 
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a href="<?= base_url('portal/billing/invoices') ?>" class="nav-item flex items-center px-4 py-3 mx-2 rounded-xl <?= strpos($currentPage ?? '', 'billing') !== false ? 'nav-active text-white' : 'text-dark-300 hover:bg-dark-800 hover:text-white' ?>">
                        <div class="w-8 h-8 rounded-lg bg-dark-800 flex items-center justify-center mr-3 <?= strpos($currentPage ?? '', 'billing') !== false ? 'bg-primary-500/20 text-primary-400' : 'text-dark-400' ?>">
                            <i class="fas fa-file-invoice-dollar text-sm"></i>
                        </div>
                        <span class="font-medium">Billing</span>
                    </a>
                    <a href="<?= base_url('portal/usage') ?>" class="nav-item flex items-center px-4 py-3 mx-2 rounded-xl <?= ($currentPage ?? '') === 'usage' ? 'nav-active text-white' : 'text-dark-300 hover:bg-dark-800 hover:text-white' ?>">
                        <div class="w-8 h-8 rounded-lg bg-dark-800 flex items-center justify-center mr-3 <?= ($currentPage ?? '') === 'usage' ? 'bg-primary-500/20 text-primary-400' : 'text-dark-400' ?>">
                            <i class="fas fa-chart-line text-sm"></i>
                        </div>
                        <span class="font-medium">Usage</span>
                    </a>
                    <a href="<?= base_url('portal/support') ?>" class="nav-item flex items-center px-4 py-3 mx-2 rounded-xl <?= strpos($currentPage ?? '', 'support') !== false ? 'nav-active text-white' : 'text-dark-300 hover:bg-dark-800 hover:text-white' ?>">
                        <div class="w-8 h-8 rounded-lg bg-dark-800 flex items-center justify-center mr-3 <?= strpos($currentPage ?? '', 'support') !== false ? 'bg-primary-500/20 text-primary-400' : 'text-dark-400' ?>">
                            <i class="fas fa-headset text-sm"></i>
                        </div>
                        <span class="font-medium">Support</span>
                        <?php $openTickets = $db->fetchOne("SELECT COUNT(*) as c FROM support_tickets WHERE customer_id = ? AND status IN ('open','pending')", [$portalCustomer['id'] ?? 0])['c'] ?? 0; ?>
                        <?php if ($openTickets > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?= $openTickets ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="px-3 mt-6 mb-3 text-xs font-semibold text-dark-500 uppercase tracking-wider">Account</div>
                    <a href="<?= base_url('portal/profile') ?>" class="nav-item flex items-center px-4 py-3 mx-2 rounded-xl <?= ($currentPage ?? '') === 'profile' ? 'nav-active text-white' : 'text-dark-300 hover:bg-dark-800 hover:text-white' ?>">
                        <div class="w-8 h-8 rounded-lg bg-dark-800 flex items-center justify-center mr-3 <?= ($currentPage ?? '') === 'profile' ? 'bg-primary-500/20 text-primary-400' : 'text-dark-400' ?>">
                            <i class="fas fa-user-cog text-sm"></i>
                        </div>
                        <span class="font-medium">Profile</span>
                    </a>
                    <a href="<?= base_url('portal/profile/notifications') ?>" class="nav-item flex items-center px-4 py-3 mx-2 rounded-xl <?= ($currentPage ?? '') === 'notifications' ? 'nav-active text-white' : 'text-dark-300 hover:bg-dark-800 hover:text-white' ?>">
                        <div class="w-8 h-8 rounded-lg bg-dark-800 flex items-center justify-center mr-3 <?= ($currentPage ?? '') === 'notifications' ? 'bg-primary-500/20 text-primary-400' : 'text-dark-400' ?>">
                            <i class="fas fa-bell text-sm"></i>
                        </div>
                        <span class="font-medium">Notifications</span>
                        <?php $notifCount = $db->fetchOne("SELECT COUNT(*) as c FROM customer_notifications WHERE customer_id = ? AND is_read = 0", [$portalCustomer['id'] ?? 0])['c'] ?? 0; ?>
                        <?php if ($notifCount > 0): ?>
                        <span class="ml-auto bg-accent-500 text-white text-xs px-2 py-0.5 rounded-full"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </a>
                </nav>

                <!-- Logout & Version -->
                <div class="p-4 border-t border-dark-700/50">
                    <a href="<?= base_url('portal/logout') ?>" class="flex items-center px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-dark-800 flex items-center justify-center mr-3">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </div>
                        <span class="font-medium">Logout</span>
                    </a>
                    <p class="text-center text-xs text-dark-500 mt-3">v2.0 • Digital ISP ERP</p>
                </div>
            </div>
        </aside>

        <!-- Mobile menu overlay -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/70 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <main class="flex-1 min-h-screen">
            <!-- Top Bar -->
            <header class="portal-topbar h-16 bg-dark-900/80 backdrop-blur-xl border-b border-dark-700/50 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-30">
                <div class="portal-top-left flex items-center">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-dark-800 mr-3">
                        <i class="fas fa-bars text-dark-300"></i>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-white"><?= sanitize($pageTitle ?? '') ?></h2>
                        <p class="portal-date text-xs text-dark-400"><?= date('l, d M Y') ?></p>
                    </div>
                </div>
                <div class="portal-actions flex items-center space-x-3">
                    <!-- Pay Bill Button - Always Visible -->
                    <button onclick="openPayModal()" class="portal-pay-btn flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-medium text-sm hover:shadow-lg hover:shadow-green-500/30 transition-all">
                        <i class="fas fa-credit-card mr-2"></i><span class="portal-pay-label"> Pay Bill</span>
                    </button>
                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" class="p-2.5 rounded-xl bg-dark-800 hover:bg-dark-700 transition-colors">
                        <i class="fas <?= $isDark ? 'fa-sun' : 'fa-moon' ?> text-dark-300"></i>
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <div class="portal-page p-4 lg:p-6">
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['portal_success'])): ?>
                <div class="mb-4 p-4 bg-green-500/20 border border-green-500/30 text-green-400 rounded-xl flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?= sanitize($_SESSION['portal_success']) ?>
                </div>
                <?php unset($_SESSION['portal_success']); endif; ?>

                <?php if (isset($_SESSION['portal_error'])): ?>
                <div class="mb-4 p-4 bg-red-500/20 border border-red-500/30 text-red-400 rounded-xl flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <?= sanitize($_SESSION['portal_error']) ?>
                </div>
                <?php unset($_SESSION['portal_error']); endif; ?>

                <?php if (isset($content)): ?>
                    <?php require_once $content ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isHidden = sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden', isHidden);
            document.body.classList.toggle('overflow-hidden', !isHidden);
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) closeSidebar();
        });

        document.querySelectorAll('#sidebar a').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) closeSidebar();
            });
        });

        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            document.cookie = `theme=${isDark ? 'dark' : 'light'};path=/;max-age=31536000`;
            location.reload();
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }

        function openPayModal() {
            const modal = document.getElementById('payModal');
            if(modal) modal.classList.remove('hidden');
        }

        function closePayModal() {
            const modal = document.getElementById('payModal');
            if(modal) modal.classList.add('hidden');
        }

        function updatePaymentFields() {
            const method = document.getElementById('paymentMethod').value;
            const bkashFields = document.getElementById('bkashFields');
            const nagadFields = document.getElementById('nagadFields');
            const bankFields = document.getElementById('bankFields');
            const piprapayFields = document.getElementById('piprapayFields');
            
            bkashFields.classList.add('hidden');
            nagadFields.classList.add('hidden');
            bankFields.classList.add('hidden');
            piprapayFields.classList.add('hidden');
            
            if(method === 'bkash') bkashFields.classList.remove('hidden');
            else if(method === 'nagad') nagadFields.classList.remove('hidden');
            else if(method === 'bank') bankFields.classList.remove('hidden');
            else if(method === 'piprapay') piprapayFields.classList.remove('hidden');
        }
    </script>

    <!-- Payment Modal -->
    <div id="payModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="portal-modal-card glass-card-dark rounded-2xl w-full max-w-md border border-dark-700/50 p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-white">
                    <i class="fas fa-credit-card text-green-400 mr-2"></i> Pay Bill
                </h3>
                <button onclick="closePayModal()" class="text-dark-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="p-4 bg-dark-800/50 rounded-xl border border-dark-700/50">
                    <div class="flex justify-between items-center">
                        <span class="text-dark-400">Due Amount</span>
                        <span class="text-2xl font-bold text-white"><?= formatMoney($dueAmount) ?></span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-dark-300 mb-2">Payment Method</label>
                    <select id="paymentMethod" onchange="updatePaymentFields()" class="w-full px-4 py-3 bg-dark-800/50 border border-dark-700 rounded-xl text-white focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                        <option value="">Select Method</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="piprapay">PiPrapay</option>
                    </select>
                </div>

                <!-- bKash Fields -->
                <div id="bkashFields" class="hidden space-y-4">
                    <div class="p-3 bg-dark-800/50 rounded-xl border border-dark-700/50">
                        <p class="text-sm text-dark-400 mb-2">Send money to:</p>
                        <p class="text-lg font-mono text-primary-400">017XXXXXXXX</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark-300 mb-2">Your Number (Sender)</label>
                        <input type="text" value="<?= sanitize($customer['phone'] ?? '') ?>" class="w-full px-4 py-3 bg-dark-800/50 border border-dark-700 rounded-xl text-white">
                    </div>
                    <button class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                        <i class="fas fa-paper-plane mr-2"></i> Submit
                    </button>
                </div>

                <!-- Nagad Fields -->
                <div id="nagadFields" class="hidden space-y-4">
                    <div class="p-3 bg-dark-800/50 rounded-xl border border-dark-700/50">
                        <p class="text-sm text-dark-400 mb-2">Send money to:</p>
                        <p class="text-lg font-mono text-primary-400">017XXXXXXXX</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark-300 mb-2">Your Number (Sender)</label>
                        <input type="text" value="<?= sanitize($customer['phone'] ?? '') ?>" class="w-full px-4 py-3 bg-dark-800/50 border border-dark-700 rounded-xl text-white">
                    </div>
                    <button class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                        <i class="fas fa-paper-plane mr-2"></i> Submit
                    </button>
                </div>

                <!-- PiPrapay Fields -->
                <div id="piprapayFields" class="hidden space-y-4">
                    <div class="p-3 bg-dark-800/50 rounded-xl border border-dark-700/50">
                        <p class="text-sm text-dark-400 mb-2">Scan QR to Pay:</p>
                        <div class="flex justify-center py-2">
                            <div class="w-32 h-32 bg-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-qrcode text-6xl text-dark-900"></i>
                            </div>
                        </div>
                        <p class="text-sm text-dark-400 mb-2 mt-3">Or send money to:</p>
                        <p class="text-lg font-mono text-primary-400">017XXXXXXXX</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark-300 mb-2">Your Number (Sender)</label>
                        <input type="text" value="<?= sanitize($customer['phone'] ?? '') ?>" class="w-full px-4 py-3 bg-dark-800/50 border border-dark-700 rounded-xl text-white">
                    </div>
                    <button class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                        <i class="fas fa-paper-plane mr-2"></i> Submit
                    </button>
                </div>

                <!-- Bank Fields -->
                <div id="bankFields" class="hidden space-y-4">
                    <div class="p-3 bg-dark-800/50 rounded-xl border border-dark-700/50">
                        <p class="text-sm text-dark-400 mb-2">Account Name:</p>
                        <p class="text-lg font-mono text-white">YOUR ISP NAME</p>
                        <p class="text-sm text-dark-400 mb-1 mt-3">Account Number:</p>
                        <p class="text-lg font-mono text-primary-400">XXXXXXXXXXXXX</p>
                        <p class="text-sm text-dark-400 mb-1 mt-3">Bank:</p>
                        <p class="text-sm text-white">XYZ Bank Ltd.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark-300 mb-2">Your Account</label>
                        <input type="text" value="<?= sanitize($customer['phone'] ?? '') ?>" class="w-full px-4 py-3 bg-dark-800/50 border border-dark-700 rounded-xl text-white">
                    </div>
                    <button class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                        <i class="fas fa-paper-plane mr-2"></i> Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
