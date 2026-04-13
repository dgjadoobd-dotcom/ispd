<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Digital ISP ERP' ?> — <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --sidebar-w: 240px;
            --header-h: 60px;
            --sidebar-bg: #1a2942;
            --sidebar-text: #94a3b8;
            --sidebar-active: #2563eb;
            --sidebar-line: rgba(148,163,184,0.18);
            --sidebar-hover: rgba(255,255,255,0.08);
            --sidebar-active-bg: rgba(37,99,235,0.2);
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
            --shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            --radius-sm: 6px; --radius-md: 6px; --radius-lg: 8px; --radius-xl: 10px;
        }
        [data-theme="dark"] {
            --bg: #09090b; --bg2: #141417; --bg3: #27272a;
            --border: rgba(255,255,255,0.08);
            --text: #fafafa; --text2: #a1a1aa;
            --card-bg: rgba(20,20,23,0.6);
            --shadow: 0 1px 3px rgba(0,0,0,0.4);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.4), 0 2px 4px -1px rgba(0,0,0,0.2);
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.5), 0 4px 6px -2px rgba(0,0,0,0.3);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.6), 0 10px 10px -5px rgba(0,0,0,0.4);
            --sidebar-bg: #141417;
            --sidebar-line: rgba(255,255,255,0.1);
            --sidebar-hover: rgba(255,255,255,0.06);
            --sidebar-active-bg: rgba(37,99,235,0.18);
        }
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); transition: background 0.3s, color 0.3s; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        /* ── SIDEBAR ── */
        #sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w); background: #1a2942;
            box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            z-index: 100; display: flex; flex-direction: column;
            transition: transform 0.3s ease; overflow-x: hidden;
        }
        #sidebarBackdrop {
            position: fixed; inset: 0;
            background: rgba(2, 6, 23, 0.55);
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 98;
        }
        #sidebar.open + #sidebarBackdrop { opacity: 1; pointer-events: auto; }
        .sidebar-logo {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 18px; border-bottom: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0; background: #152236;
        }
        .logo-icon {
            width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
            background: #2563eb; display: flex; align-items: center;
            justify-content: center; color: #fff; font-size: 15px;
        }
        .logo-text { font-weight: 700; font-size: 14px; line-height: 1.2; color: #fff; }
        .logo-sub  { font-size: 10px; color: rgba(255,255,255,0.5); font-weight: 400; }

        #sidebarNav { overflow-y: auto; flex: 1; padding: 6px 0 20px; }
        #sidebarNav::-webkit-scrollbar { width: 4px; }
        #sidebarNav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

        .nav-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px; margin: 2px 8px; border-radius: 8px;
            color: var(--sidebar-text); font-size: 13.5px; font-weight: 600;
            transition: all 0.2s ease; cursor: pointer; text-decoration: none; white-space: nowrap;
        }
        .nav-item:hover { background: var(--sidebar-hover); color: #fff; }
        .nav-item.active, .nav-item.open { background: var(--sidebar-active-bg); color: #dbeafe; }
        .nav-item .left-part { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .nav-item .icon { width: 18px; text-align: center; flex-shrink: 0; color: inherit; }
        .nav-item .chevron {
            font-size: 11px; color: inherit; opacity: 0.8;
            transition: transform 0.2s ease;
        }
        .nav-item.open .chevron { transform: rotate(90deg); opacity: 1; }

        .submenu {
            padding-left: 14px; margin: 2px 12px 6px 24px;
            border-left: 1px solid var(--sidebar-line); display: none;
        }
        .submenu.open { display: block; }
        .sub-item {
            display: flex; align-items: center; gap: 10px; padding: 7px 12px;
            color: var(--sidebar-text); font-size: 13px; font-weight: 500;
            text-decoration: none; border-radius: 8px; margin: 1px 0; transition: all 0.2s ease;
        }
        .sub-item:hover { color: #fff; background: var(--sidebar-hover); }
        .sub-item.active { background: rgba(37,99,235,0.16); color: #bfdbfe; }
        .sub-item i { width: 12px; font-size: 10px; text-align: center; flex-shrink: 0; }

        /* ── HEADER ── */
        #header {
            position: fixed; top: 0; right: 0; left: var(--sidebar-w); height: var(--header-h);
            background: var(--bg2); border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            z-index: 99; display: flex; align-items: center; padding: 0 20px; gap: 12px;
        }
        .header-search {
            flex: 1; max-width: 380px; background: var(--bg3); border: 1px solid var(--border);
            border-radius: 6px; display: flex; align-items: center;
            padding: 0 12px; gap: 10px; color: var(--text2); font-size: 13px; position: relative;
        }
        .header-search input {
            background: none; border: none; outline: none; color: var(--text);
            font-size: 13px; width: 100%; padding: 7px 0;
        }
        .header-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .icon-btn {
            width: 36px; height: 36px; border-radius: 6px; border: 1px solid var(--border);
            cursor: pointer; background: var(--bg2); color: var(--text2); font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; position: relative;
        }
        .icon-btn:hover { background: var(--bg3); color: var(--text); border-color: #cbd5e1; }
        .notif-dot {
            position: absolute; top: 6px; right: 6px; width: 8px; height: 8px;
            border-radius: 50%; background: var(--red); border: 2px solid var(--bg2);
            display: none;
        }
        .user-btn {
            display: flex; align-items: center; gap: 10px; padding: 5px 10px;
            border-radius: 6px; cursor: pointer; background: var(--bg3);
            border: 1px solid var(--border); transition: all 0.2s;
        }
        .user-btn:hover { border-color: var(--blue); }
        .user-avatar {
            width: 28px; height: 28px; border-radius: 6px; background: #2563eb;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700;
        }
        .user-info .name { font-size: 13px; font-weight: 600; color: var(--text); }
        .user-info .role { font-size: 11px; color: var(--text2); }

        /* ── MAIN ── */
        #main {
            margin-left: var(--sidebar-w); margin-top: var(--header-h);
            min-height: calc(100vh - var(--header-h));
            padding: 20px 24px; background: var(--bg);
            overflow-x: auto;
        }
        #main > * { max-width: 100%; }
        #main table { max-width: 100%; }
        #main img, #main canvas, #main video { max-width: 100%; height: auto; }
        #main [style*="display:grid"] { min-width: 0; }
        .quick-action { display: inline-flex; }
        .quick-action.quick-ticket { display: inline-flex; }
        .quick-action.quick-online { display: inline-flex; }
        .quick-action.quick-sms { display: inline-flex; }
        .user-chevron { font-size: 10px; color: var(--text2); margin-left: 4px; }
        body.sidebar-open { overflow: hidden; }

        /* Common fixed grids used across modules */
        .kpi-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .device-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kanban { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .ocm-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .filter-grid-1 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .filter-grid-2 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .form-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }

        /* Generic inline grid fallbacks used by several pages */
        #main [style*="grid-template-columns:repeat(6"] { grid-template-columns: repeat(6, minmax(0, 1fr)) !important; }
        #main [style*="grid-template-columns:repeat(5"] { grid-template-columns: repeat(5, minmax(0, 1fr)) !important; }
        #main [style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; }
        #main [style*="grid-template-columns:repeat(3"] { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
        #main [style*="grid-template-columns:repeat(2"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        #main [style*="grid-template-columns:1fr 1fr 1fr"] { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
        #main [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        #main [style*="grid-template-columns:2fr 1fr"] { grid-template-columns: 2fr 1fr !important; }
        #main [style*="grid-template-columns:1fr 380px"] { grid-template-columns: 1fr 380px !important; }
        #main [style*="grid-template-columns:220px minmax(0,1fr)"] { grid-template-columns: 220px minmax(0, 1fr) !important; }

        #main .modal {
            width: min(600px, calc(100vw - 20px));
            max-height: calc(100vh - 20px);
        }

        /* ── CARDS ── */
        .card {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transition: box-shadow 0.3s ease;
        }
        .card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .stat-card { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .stat-value { font-size: 30px; font-weight: 800; line-height: 1; color: var(--text); }
        .stat-label { font-size: 14px; color: var(--text2); font-weight: 500; }
        .stat-change { font-size: 13px; display: flex; align-items: center; gap: 4px; }
        .stat-change.up { color: var(--green); }
        .stat-change.down { color: var(--red); }

        /* ── TABLE ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .data-table th {
            text-align: left; padding: 12px 16px; font-size: 12px;
            font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;
            background: linear-gradient(90deg, #1e3a8a, #1e40af);
            color: #ffffff; border-bottom: 2px solid var(--border);
        }
        .data-table th:first-child { border-radius: 8px 0 0 0; }
        .data-table th:last-child  { border-radius: 0 8px 0 0; }
        .data-table td { padding: 13px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: var(--bg3); }

        /* ── BADGES ── */
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-green  { background: linear-gradient(135deg,#dcfce7,#bbf7d0); color: #15803d; }
        .badge-yellow { background: linear-gradient(135deg,#fef3c7,#fcd34d); color: #b45309; }
        .badge-red    { background: linear-gradient(135deg,#fee2e2,#fecaca); color: #b91c1c; }
        .badge-blue   { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color: #1e40af; }
        .badge-gray   { background: linear-gradient(135deg,#f1f5f9,#e2e8f0); color: #475569; }
        .badge-purple { background: linear-gradient(135deg,#ede9fe,#ddd6fe); color: #6d28d9; }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 600;
            cursor: pointer; border: none; transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
            text-decoration: none; white-space: nowrap;
        }
        .btn-primary { background: linear-gradient(135deg,#2563eb,#1d4ed8); color:#fff; box-shadow:0 8px 20px rgba(37,99,235,0.25); }
        .btn-primary:hover { background: linear-gradient(135deg,#1d4ed8,#1e40af); box-shadow:0 12px 30px rgba(37,99,235,0.35); transform:translateY(-1px); }
        .btn-success { background: linear-gradient(135deg,#16a34a,#15803d); color:#fff; box-shadow:0 8px 20px rgba(22,163,74,0.25); }
        .btn-success:hover { background: linear-gradient(135deg,#15803d,#166534); box-shadow:0 12px 30px rgba(22,163,74,0.35); }
        .btn-danger  { background: linear-gradient(135deg,#dc2626,#b91c1c); color:#fff; box-shadow:0 8px 20px rgba(220,38,38,0.25); }
        .btn-danger:hover  { background: linear-gradient(135deg,#b91c1c,#991b1b); box-shadow:0 12px 30px rgba(220,38,38,0.35); }
        .btn-ghost { background: transparent; color: var(--text); border: 1.5px solid var(--border); }
        .btn-ghost:hover { background: var(--bg3); border-color: var(--blue); color: var(--blue); }
        .btn-sm { padding: 7px 14px; font-size: 13px; }
        .btn-xs { padding: 4px 10px; font-size: 12px; }

        /* ── PAGE HEADER ── */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 16px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--text); letter-spacing: -0.5px; }
        .page-breadcrumb { font-size: 13px; color: var(--text2); margin-top: 4px; display: flex; align-items: center; gap: 8px; }

        /* ── FORMS ── */
        .form-input {
            background: var(--bg2); border: 1.5px solid var(--border); color: var(--text);
            border-radius: 8px; padding: 10px 14px; font-size: 14px; width: 100%;
            transition: all 0.25s; font-family: 'Inter', sans-serif;
        }
        .form-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .form-label { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 8px; display: block; }

        /* ── TOGGLE SWITCH ── */
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0;
            background: #cbd5e1; border-radius: 24px; transition: 0.3s;
        }
        .slider:before {
            content: ''; position: absolute; height: 18px; width: 18px;
            left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s;
        }
        .switch input:checked + .slider { background: #2563eb; }
        .switch input:checked + .slider:before { transform: translateX(20px); }

        /* ── DROPDOWN ── */
        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; right: 0; top: calc(100% + 8px); min-width: 200px;
            background: var(--bg2); border: 1px solid var(--border); border-radius: 10px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12); z-index: 999; overflow: hidden; display: none;
        }
        .dropdown-menu.open { display: block; }
        .dropdown-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            font-size: 14px; color: var(--text); cursor: pointer; transition: all 0.2s; text-decoration: none;
        }
        .dropdown-item:hover { background: var(--bg3); color: var(--blue); }
        .dropdown-divider { border-top: 1px solid var(--border); margin: 4px 0; }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(6px);
            z-index: 500; display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.25s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal {
            background: var(--bg2); border: 1px solid var(--border); border-radius: 14px;
            width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;
            transform: scale(0.95); transition: transform 0.25s; box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        .modal-overlay.open .modal { transform: scale(1); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--border); }
        .modal-title  { font-size: 16px; font-weight: 700; color: var(--text); }
        .modal-body   { padding: 22px; }
        .modal-footer { padding: 14px 22px; border-top: 1px solid var(--border); display: flex; gap: 10px; justify-content: flex-end; }

        /* ── MISC ── */
        .progress-bar  { height: 6px; border-radius: 3px; background: var(--bg3); overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 3px; transition: width 1s ease; }

        /* ── SEARCH RESULTS ── */
        .search-results-panel {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0;
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            z-index: 1000; overflow: hidden; display: none; max-height: 400px; overflow-y: auto;
        }
        .search-results-panel.open { display: block; }
        .search-result-item {
            padding: 10px 14px; display: flex; align-items: center; gap: 12px;
            cursor: pointer; border-bottom: 1px solid var(--border);
            transition: all 0.2s; text-decoration: none; color: inherit;
        }
        .search-result-item:last-child { border-bottom: none; }
        .search-result-item:hover, .search-result-item.selected { background: var(--bg3); }
        .sr-icon { width: 30px; height: 30px; border-radius: 6px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 13px; }
        .sr-info { flex: 1; }
        .sr-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .sr-meta  { font-size: 11px; color: var(--text2); }
        .sr-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: auto; }

        /* ── ANIMATIONS ── */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeInUp 0.4s ease forwards; }
        .fade-in-delay-1 { animation-delay: 0.05s; opacity: 0; }
        .fade-in-delay-2 { animation-delay: 0.10s; opacity: 0; }
        .fade-in-delay-3 { animation-delay: 0.15s; opacity: 0; }
        .fade-in-delay-4 { animation-delay: 0.20s; opacity: 0; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1200px) {
            #header { padding: 0 14px; }
            #main { padding: 18px; }
        }

        @media (max-width: 992px) {
            .header-search { display: none; }
            #main { padding: 14px; }
            #main table { min-width: 680px; }
            #main [style*="grid-template-columns:repeat(6"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            #main [style*="grid-template-columns:repeat(5"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            #main [style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            #main [style*="grid-template-columns:repeat(3"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            #main [style*="grid-template-columns:1fr 1fr 1fr"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            #main [style*="grid-template-columns:2fr 1fr"],
            #main [style*="grid-template-columns:1fr 380px"],
            #main [style*="grid-template-columns:220px minmax(0,1fr)"] { grid-template-columns: 1fr !important; }
            .kpi-grid-4,
            .kanban,
            .ocm-stats,
            .filter-grid-1,
            .filter-grid-2,
            .form-row { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            .device-grid { grid-template-columns: 1fr !important; }
            .page-title { font-size: 22px; }
        }

        @media (max-width: 768px) {
            :root { --header-h: 56px; }
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #sidebarBackdrop { display: block; }
            #header { left: 0; }
            #main {
                margin-left: 0;
                padding: 12px;
                min-height: calc(100vh - var(--header-h));
            }
            #header { padding: 0 10px; gap: 8px; }
            .header-actions { gap: 6px; }
            .quick-action.quick-online,
            .quick-action.quick-sms { display: none; }
            .user-info,
            .user-chevron { display: none; }
            .user-btn { padding: 4px 6px; gap: 6px; }
            .icon-btn { width: 34px; height: 34px; }
            .dropdown-menu {
                position: fixed;
                left: 10px;
                right: 10px;
                top: calc(var(--header-h) + 8px);
                min-width: 0;
            }
            #notifMenu { width: auto !important; }
            #main table { min-width: 620px; }
            #main [style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
            #main [style*="grid-template-columns:repeat(4"],
            #main [style*="grid-template-columns:repeat(3"],
            #main [style*="grid-template-columns:repeat(2"],
            #main [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
            .kpi-grid-4,
            .device-grid,
            .kanban,
            .ocm-stats,
            .filter-grid-1,
            .filter-grid-2,
            .form-row { grid-template-columns: 1fr !important; }
            .page-header { margin-bottom: 18px; gap: 10px; }
            .page-title { font-size: 20px; }
            .header-actions .btn { display: none; }
        }

        /* ── SIDEBAR COLLAPSED (desktop) ── */
        @media (min-width: 769px) {
            body.sidebar-collapsed #sidebar { width: 60px; }
            body.sidebar-collapsed .logo-text,
            body.sidebar-collapsed .logo-sub,
            body.sidebar-collapsed .nav-item .left-part span:not(.icon),
            body.sidebar-collapsed .nav-item .chevron,
            body.sidebar-collapsed .submenu,
            body.sidebar-collapsed #sidebarNav > div[style*="padding:12px"] { display: none; }
            body.sidebar-collapsed .nav-item { justify-content: center; padding: 10px; margin: 2px 4px; }
            body.sidebar-collapsed .nav-item .left-part { gap: 0; }
            body.sidebar-collapsed .nav-item .icon { width: auto; font-size: 16px; }
            body.sidebar-collapsed .sidebar-logo { justify-content: center; padding: 16px 10px; }
            body.sidebar-collapsed #header { left: 60px; }
            body.sidebar-collapsed #main { margin-left: 60px; }
        }
    </style>
</head>
<body>

<!-- ─────────────────── SIDEBAR ─────────────────── -->
<nav id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-network-wired"></i></div>
        <div>
            <div class="logo-text"><?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Digital ISP ERP' ?></div>
            <div class="logo-sub">ERP v2.0</div>
        </div>
    </div>

    <div id="sidebarNav">
        <?php
        $cp  = $currentPage    ?? '';
        $csp = $currentSubPage ?? '';
        ?>

        <a href="<?= base_url('dashboard') ?>" class="nav-item <?= $cp==='dashboard'?'active':'' ?>">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-gauge-high"></i></span>Dashboard</div>
        </a>

        <!-- Clients -->
        <?php $clientsOpen = in_array($cp, ['clients','customers']); ?>
        <div class="nav-item <?= $clientsOpen?'open':'' ?>" onclick="toggleNav('clientsMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-users"></i></span>Clients</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $clientsOpen?'open':'' ?>" id="clientsMenu">
            <a href="<?= base_url('customers') ?>" class="sub-item <?= $csp==='client-list'?'active':'' ?>">
                <i class="fa-solid fa-list"></i> All Customers
            </a>
            <a href="<?= base_url('customers/create') ?>" class="sub-item <?= $csp==='client-create'?'active':'' ?>">
                <i class="fa-solid fa-user-plus"></i> New Customer
            </a>
        </div>

        <!-- Billing -->
        <?php $billingOpen = $cp === 'billing'; ?>
        <div class="nav-item <?= $billingOpen?'open':'' ?>" onclick="toggleNav('billingMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>Billing</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $billingOpen?'open':'' ?>" id="billingMenu">
            <a href="<?= base_url('billing/invoices') ?>" class="sub-item <?= $csp==='invoices'?'active':'' ?>">
                <i class="fa-solid fa-file-invoice"></i> Invoices
            </a>
            <a href="<?= base_url('billing/cashbook') ?>" class="sub-item <?= $csp==='billing-cashbook'?'active':'' ?>">
                <i class="fa-solid fa-book"></i> Cashbook
            </a>
        </div>

        <!-- Reports -->
        <?php $reportsOpen = $cp === 'reports'; ?>
        <div class="nav-item <?= $reportsOpen?'open':'' ?>" onclick="toggleNav('reportsMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-chart-bar"></i></span>Reports</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $reportsOpen?'open':'' ?>" id="reportsMenu">
            <a href="<?= base_url('reports/income') ?>" class="sub-item <?= $csp==='income'?'active':'' ?>">
                <i class="fa-solid fa-chart-line"></i> Income
            </a>
            <a href="<?= base_url('reports/due') ?>" class="sub-item <?= $csp==='due'?'active':'' ?>">
                <i class="fa-solid fa-calendar-xmark"></i> Due
            </a>
            <a href="<?= base_url('reports/collection') ?>" class="sub-item <?= $csp==='collection'?'active':'' ?>">
                <i class="fa-solid fa-hand-holding-dollar"></i> Collection
            </a>
            <a href="<?= base_url('reports/customers') ?>" class="sub-item <?= $csp==='customers-report'?'active':'' ?>">
                <i class="fa-solid fa-chart-area"></i> Customer Growth
            </a>
        </div>

        <!-- Finance -->
        <?php $financeOpen = $cp === 'finance'; ?>
        <div class="nav-item <?= $financeOpen?'open':'' ?>" onclick="toggleNav('financeMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-coins"></i></span>Finance</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $financeOpen?'open':'' ?>" id="financeMenu">
            <a href="<?= base_url('finance/cashbook') ?>" class="sub-item <?= $csp==='cashbook'?'active':'' ?>">
                <i class="fa-solid fa-book"></i> Cashbook
            </a>
            <a href="<?= base_url('finance/expenses') ?>" class="sub-item <?= $csp==='expenses'?'active':'' ?>">
                <i class="fa-solid fa-receipt"></i> Expenses
            </a>
        </div>

        <!-- Network -->
        <?php $netOpen = $cp === 'network' || $cp === 'monitoring'; ?>
        <div class="nav-item <?= $netOpen?'open':'' ?>" onclick="toggleNav('netMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-server"></i></span>Network</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $netOpen?'open':'' ?>" id="netMenu">
            <a href="<?= base_url('network/nas') ?>" class="sub-item <?= $csp==='nas'?'active':'' ?>">
                <i class="fa-solid fa-server"></i> MikroTik / NAS
            </a>
            <a href="<?= base_url('network/ip-pools') ?>" class="sub-item <?= $csp==='ip-pools'?'active':'' ?>">
                <i class="fa-solid fa-sitemap"></i> IP Pools
            </a>
            <a href="<?= base_url('network/pppoe-users') ?>" class="sub-item <?= $csp==='pppoe-users'?'active':'' ?>">
                <i class="fa-solid fa-users-gear"></i> PPPoE Users
            </a>
            <a href="<?= base_url('network/pppoe-profiles') ?>" class="sub-item <?= $csp==='pppoe-profiles'?'active':'' ?>">
                <i class="fa-solid fa-layer-group"></i> PPPoE Profiles
            </a>
            <a href="<?= base_url('network/pppoe-active') ?>" class="sub-item <?= $csp==='pppoe-active'?'active':'' ?>">
                <i class="fa-solid fa-circle-play"></i> Active Sessions
            </a>
            <a href="<?= base_url('network/radius') ?>" class="sub-item <?= $csp==='radius'?'active':'' ?>">
                <i class="fa-solid fa-satellite-dish"></i> RADIUS Users
            </a>
            <a href="<?= base_url('network/radius/profiles') ?>" class="sub-item <?= $csp==='radius_profiles'?'active':'' ?>">
                <i class="fa-solid fa-folder-tree"></i> RADIUS Profiles
            </a>
            <a href="<?= base_url('network/radius/dashboard') ?>" class="sub-item <?= $csp==='radius_dashboard'?'active':'' ?>">
                <i class="fa-solid fa-gauge"></i> RADIUS Dashboard
            </a>
            <a href="<?= base_url('network/radius/sessions') ?>" class="sub-item <?= $csp==='radius_sessions'?'active':'' ?>">
                <i class="fa-solid fa-plug-circle-check"></i> RADIUS Sessions
            </a>
            <a href="<?= base_url('network/radius/analytics') ?>" class="sub-item <?= $csp==='radius_analytics'?'active':'' ?>">
                <i class="fa-solid fa-chart-column"></i> RADIUS Analytics
            </a>
            <a href="<?= base_url('network/radius/audit') ?>" class="sub-item <?= $csp==='radius_audit'?'active':'' ?>">
                <i class="fa-solid fa-shield-halved"></i> RADIUS Audit Log
            </a>
            <a href="<?= base_url('network/mac-bindings') ?>" class="sub-item <?= $csp==='mac-bindings'?'active':'' ?>">
                <i class="fa-solid fa-link"></i> MAC Bindings
            </a>
            <a href="<?= base_url('network/mac-filters') ?>" class="sub-item <?= $csp==='mac-filters'?'active':'' ?>">
                <i class="fa-solid fa-ban"></i> MAC Filters
            </a>
            <a href="<?= base_url('network/online-clients') ?>" class="sub-item <?= $csp==='online-clients'?'active':'' ?>">
                <i class="fa-solid fa-desktop"></i> Online Clients
            </a>
        </div>

        <!-- OLT / GPON -->
        <?php $gponOpen = $cp === 'gpon'; ?>
        <div class="nav-item <?= $gponOpen?'open':'' ?>" onclick="toggleNav('gponMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-tower-broadcast"></i></span>OLT / GPON</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $gponOpen?'open':'' ?>" id="gponMenu">
            <a href="<?= base_url('gpon/olts') ?>" class="sub-item <?= $csp==='olts'?'active':'' ?>">
                <i class="fa-solid fa-tower-broadcast"></i> OLT Devices
            </a>
            <a href="<?= base_url('gpon/onus') ?>" class="sub-item <?= $csp==='onus'?'active':'' ?>">
                <i class="fa-solid fa-microchip"></i> ONUs / CPEs
            </a>
            <a href="<?= base_url('gpon/splitters') ?>" class="sub-item <?= $csp==='splitters'?'active':'' ?>">
                <i class="fa-solid fa-code-branch"></i> Splitters
            </a>
            <a href="<?= base_url('gpon/incidents') ?>" class="sub-item <?= $csp==='incidents'?'active':'' ?>">
                <i class="fa-solid fa-triangle-exclamation"></i> Incidents
            </a>
        </div>

        <!-- Inventory -->
        <?php $invOpen = $cp === 'inventory'; ?>
        <div class="nav-item <?= $invOpen?'open':'' ?>" onclick="toggleNav('invMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-boxes-stacked"></i></span>Inventory</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $invOpen?'open':'' ?>" id="invMenu">
            <a href="<?= base_url('inventory/stock') ?>" class="sub-item <?= $csp==='stock'?'active':'' ?>">
                <i class="fa-solid fa-boxes-stacked"></i> Stock Items
            </a>
            <a href="<?= base_url('inventory/purchases') ?>" class="sub-item <?= $csp==='purchases'?'active':'' ?>">
                <i class="fa-solid fa-cart-shopping"></i> Purchase Orders
            </a>
        </div>

        <a href="<?= base_url('workorders') ?>" class="nav-item <?= $cp==='workorders'?'active':'' ?>">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-clipboard-list"></i></span>Work Orders</div>
        </a>

        <a href="<?= base_url('resellers') ?>" class="nav-item <?= $cp==='resellers'?'active':'' ?>">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-handshake"></i></span>Resellers</div>
        </a>

        <!-- Automation -->
        <?php $autoOpen = $cp === 'automation'; ?>
        <div class="nav-item <?= $autoOpen?'open':'' ?>" onclick="toggleNav('autoMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-robot"></i></span>Automation</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $autoOpen?'open':'' ?>" id="autoMenu">
            <a href="<?= base_url('automation') ?>" class="sub-item <?= ($cp==='automation'&&$csp==='dashboard')?'active':'' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="<?= base_url('automation/logs') ?>" class="sub-item <?= $csp==='logs'?'active':'' ?>">
                <i class="fa-solid fa-list-ul"></i> Logs
            </a>
        </div>

        <!-- Communication Hub -->
        <?php $commsOpen = $cp === 'comms'; ?>
        <div class="nav-item <?= $commsOpen?'open':'' ?>" onclick="toggleNav('commsMenu',this)">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-comments"></i></span>Communication</div>
            <i class="fa-solid fa-chevron-right chevron"></i>
        </div>
        <div class="submenu <?= $commsOpen?'open':'' ?>" id="commsMenu">
            <a href="<?= base_url('comms') ?>" class="sub-item <?= ($cp==='comms'&&$csp==='dashboard')?'active':'' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="<?= base_url('comms/bulk') ?>" class="sub-item <?= $csp==='bulk'?'active':'' ?>">
                <i class="fa-solid fa-paper-plane"></i> Bulk SMS
            </a>
            <a href="<?= base_url('comms/campaigns') ?>" class="sub-item <?= $csp==='campaigns'?'active':'' ?>">
                <i class="fa-solid fa-bullhorn"></i> Campaigns
            </a>
            <a href="<?= base_url('comms/templates') ?>" class="sub-item <?= $csp==='templates'?'active':'' ?>">
                <i class="fa-solid fa-file-lines"></i> Templates
            </a>
            <a href="<?= base_url('comms/logs') ?>" class="sub-item <?= $csp==='logs'?'active':'' ?>">
                <i class="fa-solid fa-list-ul"></i> SMS Logs
            </a>
        </div>

        <!-- Settings -->
        <a href="<?= base_url('settings') ?>" class="nav-item <?= $cp==='settings'?'active':'' ?>">
            <div class="left-part"><span class="icon"><i class="fa-solid fa-sliders"></i></span>Settings</div>
        </a>

    </div>

    <!-- Branch footer -->
    <div style="padding:12px 16px;border-top:1px solid rgba(255,255,255,0.08);flex-shrink:0;background:#152236;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0;"></div>
            <div>
                <div style="font-size:12px;font-weight:600;color:#fff;"><?= htmlspecialchars($_SESSION['branch_name'] ?? 'Head Office') ?></div>
                <div style="font-size:10px;color:#94a3b8;">Active Branch</div>
            </div>
        </div>
    </div>
</nav>
<div id="sidebarBackdrop" onclick="closeSidebarMobile()"></div>

<!-- ─────────────────── HEADER ─────────────────── -->
<header id="header">
    <button id="sidebarToggle" class="icon-btn" onclick="toggleSidebar()" title="Toggle sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="header-search">
        <i class="fa-solid fa-magnifying-glass" style="font-size:12px;flex-shrink:0;"></i>
        <input type="text" placeholder="Search customers, phone, code…" id="globalSearch" autocomplete="off">
        <span style="font-size:11px;background:var(--bg);padding:2px 6px;border-radius:4px;border:1px solid var(--border);flex-shrink:0;">⌘K</span>
        <div class="search-results-panel" id="searchPanel"></div>
    </div>

    <div class="header-actions">
        <a href="<?= base_url('network/online-clients') ?>" class="icon-btn quick-action quick-online" title="Online Clients"
           style="<?= $csp==='online-clients'?'border-color:var(--blue);color:var(--blue);':'' ?>">
            <i class="fa-solid fa-desktop"></i>
        </a>
        <a href="<?= base_url('workorders/create') ?>" class="icon-btn quick-action quick-ticket" title="New Support Ticket">
            <i class="fa-solid fa-ticket"></i>
        </a>
        <a href="<?= base_url('comms/bulk') ?>" class="icon-btn quick-action quick-sms" title="Send Bulk SMS">
            <i class="fa-solid fa-paper-plane"></i>
        </a>

        <button class="icon-btn" onclick="toggleTheme()" title="Toggle theme">
            <i class="fa-solid fa-moon" id="themeIcon"></i>
        </button>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="icon-btn" onclick="toggleDropdown('notifMenu')">
                <i class="fa-solid fa-bell"></i>
                <span class="notif-dot" id="notifDot"></span>
            </button>
            <div class="dropdown-menu" id="notifMenu" style="width:300px;">
                <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;">Notifications</div>
                <div id="notifList" style="max-height:300px;overflow-y:auto;">
                    <div style="padding:20px;text-align:center;color:var(--text2);font-size:13px;">
                        <i class="fa-solid fa-bell-slash" style="font-size:24px;margin-bottom:8px;display:block;"></i>
                        No new notifications
                    </div>
                </div>
            </div>
        </div>

        <!-- User menu -->
        <div class="dropdown">
            <div class="user-btn" onclick="toggleDropdown('userMenu')">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></div>
                    <div class="role"><?= htmlspecialchars($_SESSION['role_display'] ?? '') ?></div>
                </div>
                <i class="fa-solid fa-chevron-down user-chevron"></i>
            </div>
            <div class="dropdown-menu" id="userMenu">
                <a href="#" class="dropdown-item"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="#" class="dropdown-item"><i class="fa-solid fa-key"></i> Change Password</a>
                <a href="<?= base_url('settings') ?>" class="dropdown-item"><i class="fa-solid fa-gear"></i> Settings</a>
                <div class="dropdown-divider"></div>
                <a href="<?= base_url('logout') ?>" class="dropdown-item" style="color:var(--red);">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<!-- ─────────────────── MAIN ─────────────────── -->
<?php
// Section nav only for pages with many sub-pages where context panel adds value.
// All pages use the sidebar submenu for navigation — no section nav panels.
$sectionMenuData = $sectionMenu ?? null;
?>
<main id="main">
    <?php require_once $viewFile; ?>
</main>

<script>
// ── THEME ──
const savedTheme = localStorage.getItem('ispTheme') || 'light';
document.getElementById('htmlRoot').setAttribute('data-theme', savedTheme);
document.getElementById('themeIcon').className = savedTheme === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
function toggleTheme() {
    const html = document.getElementById('htmlRoot');
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('ispTheme', next);
    document.getElementById('themeIcon').className = next === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
}

// ── SIDEBAR ──
// Restore collapsed state
if (localStorage.getItem('sidebarCollapsed') === '1') document.body.classList.add('sidebar-collapsed');

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth <= 768) {
        const isOpen = sidebar.classList.toggle('open');
        document.body.classList.toggle('sidebar-open', isOpen);
    } else {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-open');
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
    }
}

function closeSidebarMobile() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.remove('open');
    document.body.classList.remove('sidebar-open');
}

function toggleNav(menuId, el) {
    const submenu = document.getElementById(menuId);
    const opening = !submenu.classList.contains('open');
    document.querySelectorAll('#sidebarNav .submenu').forEach(m => m.classList.remove('open'));
    document.querySelectorAll('#sidebarNav .nav-item').forEach(i => i.classList.remove('open'));
    if (opening) { submenu.classList.add('open'); el.classList.add('open'); }
}

document.addEventListener('click', e => {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !e.target.closest('#sidebarToggle')) {
        closeSidebarMobile();
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeSidebarMobile();
});

// ── DROPDOWN ──
function toggleDropdown(id) {
    document.querySelectorAll('.dropdown-menu').forEach(m => { if (m.id !== id) m.classList.remove('open'); });
    document.getElementById(id).classList.toggle('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.dropdown')) document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('open'));
});

// ── SEARCH ──
let searchTimer, selectedIdx = -1;
const searchInput = document.getElementById('globalSearch');
const searchPanel = document.getElementById('searchPanel');

searchInput.addEventListener('input', e => {
    clearTimeout(searchTimer);
    const q = e.target.value.trim();
    if (q.length < 2) { searchPanel.classList.remove('open'); return; }
    searchTimer = setTimeout(() => globalSearch(q), 300);
});

searchInput.addEventListener('keydown', e => {
    const items = searchPanel.querySelectorAll('.search-result-item');
    if (!searchPanel.classList.contains('open') || !items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); selectedIdx = Math.min(selectedIdx+1, items.length-1); updateSel(items); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); selectedIdx = Math.max(selectedIdx-1, 0); updateSel(items); }
    else if (e.key === 'Enter' && selectedIdx > -1) { e.preventDefault(); items[selectedIdx].click(); }
});

function updateSel(items) {
    items.forEach((item, i) => { item.classList.toggle('selected', i === selectedIdx); if (i === selectedIdx) item.scrollIntoView({block:'nearest'}); });
}

async function globalSearch(q) {
    try {
        const r = await fetch('<?= base_url('customers/search') ?>?q=' + encodeURIComponent(q));
        const data = await r.json();
        searchPanel.innerHTML = data.length
            ? data.map(c => `<a href="<?= base_url('customers/view/') ?>${c.id}" class="search-result-item">
                <div class="sr-icon"><i class="fa-solid fa-user"></i></div>
                <div class="sr-info"><div class="sr-title">${esc(c.full_name)}</div><div class="sr-meta">${esc(c.customer_code)} · ${esc(c.phone)}</div></div>
                <span class="badge badge-${c.status==='active'?'green':'red'}" style="font-size:10px;">${esc(c.status)}</span>
              </a>`).join('')
            : '<div style="padding:16px;text-align:center;font-size:13px;color:var(--text2);">No results found</div>';
        searchPanel.classList.add('open');
        selectedIdx = -1;
    } catch(e) {}
}

function esc(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }
document.addEventListener('click', e => { if (!e.target.closest('.header-search')) searchPanel.classList.remove('open'); });

document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); searchInput.focus(); }
});
</script>
</body>
</html>
