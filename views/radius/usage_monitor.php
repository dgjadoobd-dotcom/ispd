<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RADIUS Usage Monitor</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; color: #333; }
        h1 { margin-bottom: 4px; }
        .cards { display: flex; gap: 16px; margin: 16px 0; flex-wrap: wrap; }
        .card { background: #fff; border-radius: 6px; padding: 16px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.1); min-width: 160px; }
        .card .label { font-size: 12px; color: #888; text-transform: uppercase; }
        .card .value { font-size: 28px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        th { background: #4a6fa5; color: #fff; padding: 10px 12px; text-align: left; font-size: 13px; }
        td { padding: 9px 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9f9f9; }
        .updated { font-size: 12px; color: #888; margin-top: 8px; }
        h2 { margin: 20px 0 8px; }
    </style>
</head>
<body>

<h1>RADIUS Usage Monitor</h1>
<p class="updated">Last updated: <span id="last-updated">—</span></p>

<div class="cards">
    <div class="card">
        <div class="label">Active Sessions</div>
        <div class="value" id="stat-active">—</div>
    </div>
    <div class="card">
        <div class="label">Bandwidth In</div>
        <div class="value" id="stat-bytes-in">—</div>
    </div>
    <div class="card">
        <div class="label">Bandwidth Out</div>
        <div class="value" id="stat-bytes-out">—</div>
    </div>
    <div class="card">
        <div class="label">Unique NAS</div>
        <div class="value" id="stat-nas">—</div>
    </div>
</div>

<h2>Top Users Today</h2>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Username</th>
            <th>Download (In)</th>
            <th>Upload (Out)</th>
            <th>Total</th>
            <th>Sessions</th>
        </tr>
    </thead>
    <tbody id="top-users-body">
        <tr><td colspan="6" style="text-align:center;color:#aaa;">Loading…</td></tr>
    </tbody>
</table>

<script>
    const API = 'api/radius/usage.php';

    function fmtBytes(bytes) {
        bytes = parseInt(bytes) || 0;
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576)    return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024)       return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    async function fetchStats() {
        const res = await fetch(API + '?action=stats');
        const json = await res.json();
        if (!json.success) return;
        const d = json.data;
        document.getElementById('stat-active').textContent   = d.total_active;
        document.getElementById('stat-bytes-in').textContent  = fmtBytes(d.total_bytes_in);
        document.getElementById('stat-bytes-out').textContent = fmtBytes(d.total_bytes_out);
        document.getElementById('stat-nas').textContent       = d.unique_nas_count;
    }

    async function fetchTopUsers() {
        const res = await fetch(API + '?action=top_users');
        const json = await res.json();
        const tbody = document.getElementById('top-users-body');
        if (!json.success || !json.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#aaa;">No data</td></tr>';
            return;
        }
        tbody.innerHTML = json.data.map((u, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${u.username}</td>
                <td>${fmtBytes(u.total_bytes_in)}</td>
                <td>${fmtBytes(u.total_bytes_out)}</td>
                <td>${fmtBytes(u.total_bytes)}</td>
                <td>${u.session_count}</td>
            </tr>`).join('');
    }

    function refresh() {
        Promise.all([fetchStats(), fetchTopUsers()])
            .then(() => {
                document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
            })
            .catch(() => {
                document.getElementById('last-updated').textContent = 'Error fetching data';
            });
    }

    refresh();
    setInterval(refresh, 30000);
</script>

</body>
</html>
