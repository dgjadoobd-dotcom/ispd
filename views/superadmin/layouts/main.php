<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --sidebar-w: 230px;
            --header-h: 60px;
            --sidebar-bg: #0f172a;
            --sidebar-text: #94a3b8;
            --sidebar-active-bg: rgba(124,58,237,0.2);
            --sidebar-hover: rgba(255,255,255,0.07);
            --sidebar-line: rgba(148,163,184,0.15);
            --bg: #f0f2f5;
            --bg2: #ffffff;
            --bg3: #f8fafc;
            --border: #e2e8f0;
            --text: #1e293b;
            --text2: #64748b;
            --blue: #2563eb;
            --green: #16a34a;
            --red: #dc2626;
            --yellow: #d97706;
            --purple: #7c3aed;
            --card-bg: #ffffff;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius-sm: 6px; --radius-md: 8px; --radius-lg: 10px;
        }
        [data-theme="dark"] {
            --bg: #09090b; --bg2: #141417; --bg3: #27272a;
            --border: rgba(255,255,255,0.08);
            --text: #fafafa; --text2: #a1a1aa;
            --card-bg: rgba(20,20,23,0.6);
            --shadow: 0 1px 3px rgba(0,0,0,0.4);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.5);
            --sidebar-bg: #0a0a0f;
        }
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); transition: background 0.3s, color 0.3s; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        /* ── SIDEBAR ── */
        #sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w); background: var(--sidebar-bg);
            box-shadow: 2px 0 12px rgba(0,0,0,0.25);
            z-index: 100; display: flex; flex-direction: column;
            transition: transform 0.3s ease;
        }
        .sidebar-logo {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 18px; border-bottom: 1px solid rgba(255,255,255,0.07);
            flex-shrink: 0;
            background: linear-gradient(135deg, #1e1b4b, #312e81);
        }
        .logo-icon {
            width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px;
        }
        .logo-text { font-weight: 700; font-size: 13px; line-height: 1.2; color: #fff; }
        .logo-sub  { font-size: 10px; color: rgba(255,255,255,0.45); font-weight: 400; }
        .sa-badge {
            margin-left: auto; background: rgba(124,58,237,0.3); color: #c4b5fd;
            font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 4px;
            letter-spacing: 0.5px; text-transform: uppercase; flex-shrink: 0;
        }

        #sidebarNav { overflow-y: auto; flex: 1; padding: 8px 0 20px; }
        #sidebarNav::-webkit-scrollbar { width: 3px; }
        #sidebarNav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

        .nav-section {
            font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
            color: rgba(148,163,184,0.5); padding: 14px 18px 6px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px; margin: 1px 8px; border-radius: 8px;
            color: var(--sidebar-text); font-size: 13px; font-weight: 500;
            transition: all 0.2s; cursor: pointer; text-decoration: none;
        }
        .nav-item:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .nav-item.active { background: var(--sidebar-active-bg); color: #c4b5fd; }
        .nav-item .nav-icon { width: 18px; text-align: center; flex-shrink: 0; font-size: 14px; }
        .nav-item .nav-badge {
            margin-left: auto; background: rgba(220,38,38,0.2); color: #fca5a5;
            font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px;
        }

        /* ── HEADER ── */
        #header {
            position: fixed; top: 0; right: 0; left: var(--sidebar-w); height: var(--header-h);
            background: var(--bg2); border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            z-index: 99; display: flex; align-items: center; padding: 0 20px; gap: 12px;
        }
        .header-title { font-size: 15px; font-weight: 700; color: var(--text); }
        .header-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .icon-btn {
            width: 36px; height: 36px; border-radius: 6px; border: 1px solid var(--border);
            cursor: pointer; background: var(--bg2); color: var(--text2); font-size: 14px;
            display: flex; align-items: center; justify-content: center; transition: all 0.2s;
        }
        .icon-btn:hover { background: var(--bg3); color: var(--text); }
        .user-btn {
            display: flex; align-items: center; gap: 10px; padding: 5px 10px;
            border-radius: 6px; cursor: pointer; background: var(--bg3);
            border: 1px solid var(--border); transition: all 0.2s; text-decoration: none;
        }
        .user-btn:hover { border-color: var(--purple); }
        .user-avatar {
            width: 28px; height: 28px; border-radius: 6px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700;
        }
        .user-info .name { font-size: 13px; font-weight: 600; color: var(--text); }
        .user-info .role { font-size: 11px; color: var(--purple); font-weight: 600; }

        /* ── MAIN ── */
        #main {
            margin-left: var(--sidebar-w); margin-top: var(--header-h);
            min-height: calc(100vh - var(--header-h));
            padding: 24px; background: var(--bg);
        }

        /* ── CARDS ── */
        .card {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: 10px; box-shadow: var(--shadow);
        }
        .stat-card { padding: 20px; }

        /* ── TABLE ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .data-table th {
            text-align: left; padding: 11px 16px; font-size: 11px;
            font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;
            background: linear-gradient(90deg, #1e1b4b, #312e81);
            color: #c4b5fd; border-bottom: 2px solid var(--border);
        }
        .data-table th:first-child { border-radius: 8px 0 0 0; }
        .data-table th:last-child  { border-radius: 0 8px 0 0; }
        .data-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: var(--bg3); }

        /* ── BADGES ── */
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #b45309; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-purple { background: #ede9fe; color: #6d28d9; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; transition: all 0.2s; text-decoration: none;
        }
        .btn-primary { background: linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff; box-shadow:0 4px 12px rgba(124,58,237,0.3); }
        .btn-primary:hover { background: linear-gradient(135deg,#6d28d9,#5b21b6); transform:translateY(-1px); }
        .btn-success { background: linear-gradient(135deg,#16a34a,#15803d); color:#fff; }
        .btn-danger  { background: linear-gradient(135deg,#dc2626,#b91c1c); color:#fff; }
        .btn-ghost { background: transparent; color: var(--text); border: 1.5px solid var(--border); }
        .btn-ghost:hover { background: var(--bg3); border-color: var(--purple); color: var(--purple); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-xs { padding: 4px 9px; font-size: 11px; }

        /* ── PAGE HEADER ── */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .page-title { font-size: 24px; font-weight: 800; color: var(--text); letter-spacing: -0.5px; }
        .page-breadcrumb { font-size: 12px; color: var(--text2); margin-top: 3px; }

        /* ── FORMS ── */
        .form-input {
            background: var(--bg2); border: 1.5px solid var(--border); color: var(--text);
            border-radius: 8px; padding: 9px 13px; font-size: 13px; width: 100%;
            transition: all 0.2s; font-family: 'Inter', sans-serif;
        }
        .form-input:focus { outline: none; border-color: var(--purple); box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
        .form-label { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; display: block; }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);
            z-index: 500; display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.2s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal {
            background: var(--bg2); border: 1px solid var(--border); border-radius: 14px;
            width: 90%; max-width: 560px; max-height: 90vh; overflow-y: auto;
            transform: scale(0.95); transition: transform 0.2s; box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }
        .modal-overlay.open .modal { transform: scale(1); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .modal-title  { font-size: 15px; font-weight: 700; color: var(--text); }
        .modal-body   { padding: 20px; }
        .modal-footer { padding: 12px 20px; border-top: 1px solid var(--border); display: flex; gap: 8px; justify-content: flex-end; }

        /* ── PROGRESS ── */
        .progress-bar  { height: 6px; border-radius: 3px; background: var(--bg3); overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 3px; transition: width 0.8s ease; }

        /* ── ANIMATIONS ── */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeInUp 0.35s ease forwards; }

        /* ── TOAST ── */
        #toast {
            position: fixed; bottom: 24px; right: 24px; z-index: 9999;
            display: flex; flex-direction: column; gap: 8px; pointer-events: none;
        }
        .toast-item {
            padding: 12px 18px; border-radius: 8px; font-size: 13px; font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15); pointer-events: all;
            animation: fadeInUp 0.3s ease; display: flex; align-items: center; gap: 10px;
        }
        .toast-success { background: #16a34a; color: #fff; }
        .toast-error   { background: #dc2626; color: #fff; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #header { left: 0; }
            #main { margin-left: 0; padding: 14px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<nav id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
            <div class="logo-text">Super Admin</div>
            <div class="logo-sub">Owner Control Panel</div>
        </div>
        <span class="sa-badge">SA</span>
    </div>

    <div id="sidebarNav">
        <?php $cp = $currentPage ?? ''; ?>

        <div class="nav-section">Overview</div>
        <a href="<?= base_url('superadmin/dashboard') ?>" class="nav-item <?= $cp==='sa-dashboard'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span> Dashboard
        </a>

        <div class="nav-section">Management</div>
        <a href="<?= base_url('superadmin/users') ?>" class="nav-item <?= $cp==='sa-users'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span> Users
        </a>
        <a href="<?= base_url('superadmin/branches') ?>" class="nav-item <?= $cp==='sa-branches'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-code-branch"></i></span> Branches
        </a>

        <div class="nav-section">System</div>
        <a href="<?= base_url('superadmin/noc') ?>" class="nav-item <?= $cp==='sa-noc'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-tower-broadcast"></i></span> NOC / Health
        </a>
        <a href="<?= base_url('superadmin/logs') ?>" class="nav-item <?= $cp==='sa-logs'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-scroll"></i></span> Activity Logs
        </a>
        <a href="<?= base_url('superadmin/settings') ?>" class="nav-item <?= $cp==='sa-settings'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-sliders"></i></span> Settings
        </a>

        <div class="nav-section">Navigation</div>
        <a href="<?= base_url('dashboard') ?>" class="nav-item">
            <span class="nav-icon"><i class="fa-solid fa-arrow-left"></i></span> Back to ERP
        </a>
        <a href="<?= base_url('superadmin/logout') ?>" class="nav-item" style="color:#fca5a5;">
            <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span> Logout
        </a>
    </div>
</nav>

<!-- HEADER -->
<header id="header">
    <div style="display:flex;align-items:center;gap:10px;">
        <button class="icon-btn" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none;" id="menuBtn">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div>
            <div class="header-title"><?= htmlspecialchars($pageTitle ?? 'Super Admin') ?></div>
        </div>
    </div>
    <div class="header-actions">
        <button class="icon-btn" onclick="toggleTheme()" title="Toggle theme">
            <i class="fa-solid fa-circle-half-stroke"></i>
        </button>
        <div class="user-btn">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'SA', 0, 2)) ?></div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Super Admin') ?></div>
                <div class="role"><?= htmlspecialchars(strtoupper($_SESSION['user_role'] ?? 'superadmin')) ?></div>
            </div>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main id="main">
    <?php require_once $viewFile; ?>
</main>

<!-- TOAST CONTAINER -->
<div id="toast"></div>

<script>
// Theme toggle
function toggleTheme() {
    const html = document.getElementById('htmlRoot');
    const isDark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', isDark ? '' : 'dark');
    localStorage.setItem('sa_theme', isDark ? '' : 'dark');
}
(function() {
    const t = localStorage.getItem('sa_theme');
    if (t === 'dark') document.getElementById('htmlRoot').setAttribute('data-theme', 'dark');
})();

// Toast
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    const el = document.createElement('div');
    el.className = `toast-item toast-${type}`;
    el.innerHTML = `<i class="fa-solid fa-${type==='success'?'circle-check':'circle-xmark'}"></i> ${msg}`;
    t.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

// Generic AJAX helper
async function saPost(url, data = {}) {
    const fd = new FormData();
    Object.entries(data).forEach(([k,v]) => fd.append(k, v));
    const r = await fetch(url, { method: 'POST', body: fd });
    return r.json();
}

// Modal helpers
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});
</script>
</body>
</html>
