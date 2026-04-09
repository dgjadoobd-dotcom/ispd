<?php
/**
 * Digital ISP ERP — Web Installer
 * Supports: XAMPP (Windows) · Shared Hosting · Ubuntu Server
 *
 * Access: http://your-domain/install.php
 * DELETE this file after installation is complete.
 */

define('BASE_PATH', dirname(__DIR__));
define('INSTALLER_VERSION', '1.0.0');

// ── Security: block if already installed ─────────────────────────────────────
if (file_exists(BASE_PATH . '/.installed') && !isset($_GET['force'])) {
    die('<h2 style="font-family:sans-serif;color:#dc2626;padding:40px">
        Already installed. Delete <code>.installed</code> to re-run.
        <br><a href="/">Go to Login</a></h2>');
}

session_start();

// ── Step logic ────────────────────────────────────────────────────────────────
$step  = (int)($_GET['step'] ?? $_SESSION['install_step'] ?? 1);
$error = '';
$info  = '';

// ── Helper functions ──────────────────────────────────────────────────────────
function checkReq(): array {
    $checks = [];
    $checks[] = ['PHP Version ≥ 8.1',   version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION];
    $checks[] = ['PDO',                  extension_loaded('pdo'),                     extension_loaded('pdo') ? 'Enabled' : 'Missing'];
    $checks[] = ['PDO SQLite',           extension_loaded('pdo_sqlite'),              extension_loaded('pdo_sqlite') ? 'Enabled' : 'Missing'];
    $checks[] = ['PDO MySQL',            extension_loaded('pdo_mysql'),               extension_loaded('pdo_mysql') ? 'Enabled' : 'Missing'];
    $checks[] = ['cURL',                 function_exists('curl_init'),                function_exists('curl_init') ? 'Enabled' : 'Missing'];
    $checks[] = ['OpenSSL',              extension_loaded('openssl'),                 extension_loaded('openssl') ? 'Enabled' : 'Missing'];
    $checks[] = ['Mbstring',             extension_loaded('mbstring'),                extension_loaded('mbstring') ? 'Enabled' : 'Missing'];
    $checks[] = ['JSON',                 extension_loaded('json'),                    extension_loaded('json') ? 'Enabled' : 'Missing'];
    $checks[] = ['BCMath',               extension_loaded('bcmath'),                  extension_loaded('bcmath') ? 'Enabled' : 'Missing'];
    $checks[] = ['SNMP (optional)',      extension_loaded('snmp'),                    extension_loaded('snmp') ? 'Enabled' : 'Optional'];
    $checks[] = ['.env writable',        is_writable(BASE_PATH),                      is_writable(BASE_PATH) ? 'OK' : 'Not writable'];
    $checks[] = ['database/ writable',   is_writable(BASE_PATH . '/database'),        is_writable(BASE_PATH . '/database') ? 'OK' : 'Not writable'];
    $checks[] = ['storage/ writable',    is_writable(BASE_PATH . '/storage') || @mkdir(BASE_PATH . '/storage/logs', 0755, true), 'OK'];
    return $checks;
}

function allReqPassed(array $checks): bool {
    foreach ($checks as $c) {
        // SNMP is optional — skip
        if (str_contains($c[0], 'optional')) continue;
        if (!$c[1]) return false;
    }
    return true;
}

function testMysql(string $host, string $port, string $db, string $user, string $pass): array {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        return ['ok' => true, 'msg' => 'Connected to MySQL successfully.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}

function writeEnv(array $d): bool {
    $lines = [];
    foreach ($d as $k => $v) {
        // Quote values with spaces
        $lines[] = $k . '=' . (str_contains((string)$v, ' ') ? '"' . $v . '"' : $v);
    }
    return file_put_contents(BASE_PATH . '/.env', implode("\n", $lines) . "\n") !== false;
}

function initSqlite(string $dbPath, string $schemaPath): void {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $sql = preg_replace('/^--[^\n]*\n/m', '', file_get_contents($schemaPath));
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') $pdo->exec($stmt);
    }
}

function createAdmin(string $dbPath, string $username, string $email, string $password, string $fullName): void {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    // Get superadmin role id
    $roleId = $pdo->query("SELECT id FROM roles WHERE name='superadmin' LIMIT 1")->fetchColumn() ?: 1;
    // Get branch id
    $branchId = $pdo->query("SELECT id FROM branches LIMIT 1")->fetchColumn() ?: 1;
    // Remove existing admin
    $pdo->prepare("DELETE FROM users WHERE username=?")->execute([$username]);
    $pdo->prepare("INSERT INTO users (branch_id, role_id, username, email, phone, password_hash, full_name, is_active, created_at, updated_at)
                   VALUES (?, ?, ?, ?, '', ?, ?, 1, datetime('now'), datetime('now'))")
        ->execute([$branchId, $roleId, $username, $email, $hash, $fullName]);
}

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 2 — save config
    if ($step === 2) {
        $dbType = $_POST['db_type'] ?? 'sqlite';
        $env = [
            'APP_NAME'     => trim($_POST['app_name'] ?? 'Digital ISP ERP'),
            'APP_URL'      => rtrim(trim($_POST['app_url'] ?? 'http://localhost:8088'), '/'),
            'APP_ENV'      => 'production',
            'APP_DEBUG'    => 'false',
            'APP_TIMEZONE' => $_POST['timezone'] ?? 'Asia/Dhaka',
            'APP_KEY'      => 'base64:' . base64_encode(random_bytes(32)),
            'DB_CONNECTION'=> $dbType,
            'DB_HOST'      => trim($_POST['db_host'] ?? 'localhost'),
            'DB_PORT'      => trim($_POST['db_port'] ?? '3306'),
            'DB_DATABASE'  => $dbType === 'sqlite' ? 'database/digital-isp.sqlite' : trim($_POST['db_name'] ?? 'digital_isp'),
            'DB_USERNAME'  => trim($_POST['db_user'] ?? 'root'),
            'DB_PASSWORD'  => $_POST['db_pass'] ?? '',
            'JWT_SECRET'   => bin2hex(random_bytes(32)),
            'JWT_EXPIRY'   => '86400',
            'SMS_GATEWAY'  => 'sslwireless',
            'SMS_API_KEY'  => '',
            'SMS_SENDER_ID'=> 'DIGITALISP',
            'MIKROTIK_TIMEOUT' => '10',
            'RADIUS_HOST'  => trim($_POST['radius_host'] ?? '127.0.0.1'),
            'RADIUS_PORT'  => '1812',
            'RADIUS_DB_CONNECTION' => 'mysql',
            'RADIUS_DB_HOST'     => trim($_POST['radius_db_host'] ?? 'localhost'),
            'RADIUS_DB_PORT'     => '3306',
            'RADIUS_DB_DATABASE' => trim($_POST['radius_db_name'] ?? 'radius'),
            'RADIUS_DB_USERNAME' => trim($_POST['radius_db_user'] ?? 'root'),
            'RADIUS_DB_PASSWORD' => $_POST['radius_db_pass'] ?? '',
            'PAYBILL_DB_HOST'    => 'localhost',
            'PAYBILL_DB_PORT'    => '3306',
            'PAYBILL_DB_NAME'    => 'piprapay',
            'PAYBILL_DB_USERNAME'=> trim($_POST['db_user'] ?? 'root'),
            'PAYBILL_DB_PASSWORD'=> $_POST['db_pass'] ?? '',
            'PAYBILL_DB_PREFIX'  => 'pp_',
        ];

        // Test MySQL if selected
        if ($dbType === 'mysql') {
            $test = testMysql($env['DB_HOST'], $env['DB_PORT'], $env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD']);
            if (!$test['ok']) {
                $error = 'MySQL connection failed: ' . $test['msg'];
                goto render;
            }
        }

        if (!writeEnv($env)) {
            $error = 'Cannot write .env file. Check folder permissions.';
            goto render;
        }
        $_SESSION['install_env'] = $env;
        $_SESSION['install_step'] = 3;
        header('Location: install.php?step=3'); exit;
    }

    // Step 3 — init database
    if ($step === 3) {
        $env    = $_SESSION['install_env'] ?? [];
        $dbType = $env['DB_CONNECTION'] ?? 'sqlite';
        try {
            if ($dbType === 'sqlite') {
                $dbPath     = BASE_PATH . '/database/digital-isp.sqlite';
                $schemaPath = BASE_PATH . '/database/sqlite_schema.sql';
                if (!file_exists($schemaPath)) throw new Exception("Schema file not found: $schemaPath");
                if (file_exists($dbPath)) unlink($dbPath);
                initSqlite($dbPath, $schemaPath);
            } else {
                // MySQL — run schema.sql
                $pdo = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
                    $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $sql = file_get_contents(BASE_PATH . '/database/schema.sql');
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if ($stmt !== '' && !preg_match('/^--/', $stmt)) $pdo->exec($stmt);
                }
            }
            $_SESSION['install_step'] = 4;
            header('Location: install.php?step=4'); exit;
        } catch (Exception $e) {
            $error = 'Database setup failed: ' . $e->getMessage();
        }
    }

    // Step 4 — create admin
    if ($step === 4) {
        $username  = trim($_POST['username'] ?? 'admin');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $fullName  = trim($_POST['full_name'] ?? 'System Admin');
        $env       = $_SESSION['install_env'] ?? [];
        $dbType    = $env['DB_CONNECTION'] ?? 'sqlite';

        if (strlen($username) < 3)          { $error = 'Username must be at least 3 characters.'; goto render; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email address.'; goto render; }
        if (strlen($password) < 8)          { $error = 'Password must be at least 8 characters.'; goto render; }
        if ($password !== $password2)        { $error = 'Passwords do not match.'; goto render; }

        try {
            if ($dbType === 'sqlite') {
                createAdmin(BASE_PATH . '/database/digital-isp.sqlite', $username, $email, $password, $fullName);
            } else {
                $pdo  = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
                    $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $roleId   = $pdo->query("SELECT id FROM roles WHERE name='superadmin' LIMIT 1")->fetchColumn() ?: 1;
                $branchId = $pdo->query("SELECT id FROM branches LIMIT 1")->fetchColumn() ?: 1;
                $pdo->prepare("DELETE FROM users WHERE username=?")->execute([$username]);
                $pdo->prepare("INSERT INTO users (branch_id,role_id,username,email,phone,password_hash,full_name,is_active,created_at,updated_at)
                               VALUES (?,?,?,?,'',?,?,1,NOW(),NOW())")
                    ->execute([$branchId, $roleId, $username, $email, $hash, $fullName]);
            }
            $_SESSION['install_admin'] = ['username' => $username, 'password' => $password, 'email' => $email];
            // Mark installed
            file_put_contents(BASE_PATH . '/.installed', date('Y-m-d H:i:s'));
            $_SESSION['install_step'] = 5;
            header('Location: install.php?step=5'); exit;
        } catch (Exception $e) {
            $error = 'Failed to create admin: ' . $e->getMessage();
        }
    }
}

render:

$checks  = checkReq();
$allPass = allReqPassed($checks);
$appUrl  = ($_SESSION['install_env']['APP_URL'] ?? '') ?: (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$admin   = $_SESSION['install_admin'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install — Digital ISP ERP</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.wrap{width:100%;max-width:680px}
.logo{text-align:center;margin-bottom:28px}
.logo-icon{width:60px;height:60px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:12px}
.logo h1{font-size:26px;font-weight:800;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo p{color:#64748b;font-size:13px;margin-top:4px}
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:36px}
.steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:32px}
.step-dot{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;transition:.3s}
.step-dot.done{background:#22c55e;color:#fff}
.step-dot.active{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;box-shadow:0 0 0 4px rgba(99,102,241,.25)}
.step-dot.pending{background:rgba(255,255,255,.08);color:#64748b}
.step-line{flex:1;height:2px;max-width:48px;background:rgba(255,255,255,.08)}
.step-line.done{background:#22c55e}
h2{font-size:20px;font-weight:700;margin-bottom:20px;color:#f1f5f9}
.form-group{margin-bottom:16px}
label{display:block;font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
input,select{width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;padding:11px 14px;color:#f1f5f9;font-size:14px;outline:none;transition:.2s}
input:focus,select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.2s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-secondary{background:rgba(255,255,255,.07);color:#94a3b8}
.btn-secondary:hover{background:rgba(255,255,255,.12)}
.btn-group{display:flex;gap:10px;margin-top:24px}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.alert-info{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);color:#93c5fd}
.req-row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:8px;margin-bottom:6px;font-size:13px}
.req-row:nth-child(odd){background:rgba(255,255,255,.03)}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-ok{background:rgba(34,197,94,.15);color:#4ade80}
.badge-fail{background:rgba(239,68,68,.15);color:#f87171}
.badge-opt{background:rgba(234,179,8,.12);color:#fbbf24}
.section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#475569;margin:20px 0 10px}
.info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:13px}
.info-row:last-child{border:none}
.info-val{font-family:monospace;color:#60a5fa;font-size:13px}
.success-icon{width:72px;height:72px;background:rgba(34,197,94,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 20px}
.copy-btn{background:rgba(255,255,255,.07);border:none;color:#94a3b8;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;margin-left:8px}
.copy-btn:hover{background:rgba(255,255,255,.12);color:#e2e8f0}
.toggle-pass{position:relative}
.toggle-pass input{padding-right:44px}
.toggle-pass .eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#64748b;font-size:16px;user-select:none}
.db-tabs{display:flex;gap:8px;margin-bottom:20px}
.db-tab{padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid rgba(255,255,255,.1);color:#64748b;background:transparent;transition:.2s}
.db-tab.active{background:rgba(59,130,246,.15);border-color:#3b82f6;color:#60a5fa}
.mysql-fields{display:none}
.mysql-fields.show{display:block}
footer{text-align:center;margin-top:20px;color:#334155;font-size:12px}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div class="logo-icon">🌐</div>
    <h1>Digital ISP ERP</h1>
    <p>Installation Wizard v<?= INSTALLER_VERSION ?></p>
  </div>

  <div class="card">
    <!-- Steps -->
    <div class="steps">
      <?php
      $labels = ['Req', 'Config', 'DB', 'Admin', 'Done'];
      for ($i = 1; $i <= 5; $i++):
        $cls = $i < $step ? 'done' : ($i === $step ? 'active' : 'pending');
        echo '<div class="step-dot '.$cls.'">'.($i < $step ? '✓' : $i).'</div>';
        if ($i < 5) {
            echo '<div class="step-line '.($i < $step ? 'done' : '').'"></div>';
        }
      endfor;
      ?>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($info): ?>
    <div class="alert alert-info">ℹ️ <?= htmlspecialchars($info) ?></div>
    <?php endif; ?>

    <!-- ── STEP 1: Requirements ── -->
    <?php if ($step === 1): ?>
    <h2>System Requirements</h2>
    <?php foreach ($checks as $c): ?>
    <?php
      $isOpt = str_contains($c[0], 'optional');
      $badge = $c[1] ? 'badge-ok' : ($isOpt ? 'badge-opt' : 'badge-fail');
      $label = $c[1] ? 'OK' : ($isOpt ? 'Optional' : 'FAIL');
    ?>
    <div class="req-row">
      <span><?= $c[0] ?></span>
      <div>
        <span style="color:#64748b;font-size:12px;margin-right:8px"><?= $c[2] ?></span>
        <span class="badge <?= $badge ?>"><?= $label ?></span>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="btn-group">
      <?php if ($allPass): ?>
      <a href="install.php?step=2" class="btn btn-primary">Continue →</a>
      <?php else: ?>
      <div class="alert alert-error" style="margin:0">Fix the failed requirements before continuing.</div>
      <a href="install.php?step=1" class="btn btn-secondary">Re-check</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── STEP 2: Configuration ── -->
    <?php if ($step === 2): ?>
    <h2>Application Configuration</h2>
    <form method="POST" action="install.php?step=2">

      <div class="section-title">Application</div>
      <div class="row">
        <div class="form-group">
          <label>App Name</label>
          <input name="app_name" value="Digital ISP ERP" required>
        </div>
        <div class="form-group">
          <label>Timezone</label>
          <select name="timezone">
            <option value="Asia/Dhaka" selected>Asia/Dhaka (GMT+6)</option>
            <option value="Asia/Kolkata">Asia/Kolkata (GMT+5:30)</option>
            <option value="Asia/Karachi">Asia/Karachi (GMT+5)</option>
            <option value="UTC">UTC</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Application URL</label>
        <input name="app_url" value="<?= htmlspecialchars($appUrl) ?>" placeholder="http://billing.yourdomain.com:8088" required>
      </div>

      <div class="section-title">Database</div>
      <div class="db-tabs">
        <button type="button" class="db-tab active" onclick="setDb('sqlite')">SQLite (Recommended)</button>
        <button type="button" class="db-tab" onclick="setDb('mysql')">MySQL / MariaDB</button>
      </div>
      <input type="hidden" name="db_type" id="db_type" value="sqlite">

      <div class="mysql-fields" id="mysql_fields">
        <div class="row">
          <div class="form-group">
            <label>DB Host</label>
            <input name="db_host" value="localhost">
          </div>
          <div class="form-group">
            <label>DB Port</label>
            <input name="db_port" value="3306">
          </div>
        </div>
        <div class="form-group">
          <label>Database Name</label>
          <input name="db_name" value="digital_isp" placeholder="Create this DB first in phpMyAdmin">
        </div>
        <div class="row">
          <div class="form-group">
            <label>DB Username</label>
            <input name="db_user" value="root">
          </div>
          <div class="form-group">
            <label>DB Password</label>
            <input type="password" name="db_pass" value="">
          </div>
        </div>
      </div>

      <div class="section-title">RADIUS Server (Optional)</div>
      <div class="row">
        <div class="form-group">
          <label>RADIUS Host IP</label>
          <input name="radius_host" value="127.0.0.1" placeholder="e.g. 172.17.50.10">
        </div>
        <div class="form-group">
          <label>RADIUS DB Host</label>
          <input name="radius_db_host" value="localhost">
        </div>
      </div>
      <div class="row">
        <div class="form-group">
          <label>RADIUS DB Name</label>
          <input name="radius_db_name" value="radius">
        </div>
        <div class="form-group">
          <label>RADIUS DB User</label>
          <input name="radius_db_user" value="root">
        </div>
      </div>
      <div class="form-group">
        <label>RADIUS DB Password</label>
        <input type="password" name="radius_db_pass" value="">
      </div>

      <div class="btn-group">
        <a href="install.php?step=1" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-primary">Save & Continue →</button>
      </div>
    </form>
    <?php endif; ?>

    <!-- ── STEP 3: Database Init ── -->
    <?php if ($step === 3): ?>
    <h2>Initialize Database</h2>
    <?php $env = $_SESSION['install_env'] ?? []; ?>
    <div class="info-row"><span>Database Type</span><span class="info-val"><?= strtoupper($env['DB_CONNECTION'] ?? 'sqlite') ?></span></div>
    <div class="info-row"><span>Database</span><span class="info-val"><?= htmlspecialchars($env['DB_DATABASE'] ?? '') ?></span></div>
    <div class="info-row"><span>Schema File</span>
      <span class="badge <?= file_exists(BASE_PATH.'/database/sqlite_schema.sql') ? 'badge-ok' : 'badge-fail' ?>">
        <?= file_exists(BASE_PATH.'/database/sqlite_schema.sql') ? 'Found' : 'Missing' ?>
      </span>
    </div>
    <div class="alert alert-info" style="margin-top:16px">
      This will create all required tables. Existing data will be erased if re-running.
    </div>
    <form method="POST" action="install.php?step=3">
      <div class="btn-group">
        <a href="install.php?step=2" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-primary">Initialize Database →</button>
      </div>
    </form>
    <?php endif; ?>

    <!-- ── STEP 4: Admin Account ── -->
    <?php if ($step === 4): ?>
    <h2>Create Admin Account</h2>
    <form method="POST" action="install.php?step=4">
      <div class="form-group">
        <label>Full Name</label>
        <input name="full_name" value="System Admin" required>
      </div>
      <div class="row">
        <div class="form-group">
          <label>Username</label>
          <input name="username" value="admin" required minlength="3">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="" placeholder="admin@yourdomain.com" required>
        </div>
      </div>
      <div class="row">
        <div class="form-group">
          <label>Password</label>
          <div class="toggle-pass">
            <input type="password" name="password" id="pw1" placeholder="Min 8 characters" required minlength="8">
            <span class="eye" onclick="togglePw('pw1')">👁</span>
          </div>
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <div class="toggle-pass">
            <input type="password" name="password2" id="pw2" placeholder="Repeat password" required>
            <span class="eye" onclick="togglePw('pw2')">👁</span>
          </div>
        </div>
      </div>
      <div class="btn-group">
        <a href="install.php?step=3" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-primary">Create Admin →</button>
      </div>
    </form>
    <?php endif; ?>

    <!-- ── STEP 5: Complete ── -->
    <?php if ($step === 5): ?>
    <div style="text-align:center">
      <div class="success-icon">✅</div>
      <h2 style="text-align:center;margin-bottom:8px">Installation Complete!</h2>
      <p style="color:#64748b;font-size:13px;margin-bottom:24px">Digital ISP ERP is ready to use.</p>
    </div>

    <div class="section-title">Login Credentials</div>
    <div class="info-row">
      <span>URL</span>
      <span>
        <a href="<?= htmlspecialchars($appUrl) ?>" class="info-val" target="_blank"><?= htmlspecialchars($appUrl) ?></a>
        <button class="copy-btn" onclick="copy('<?= htmlspecialchars($appUrl) ?>')">Copy</button>
      </span>
    </div>
    <div class="info-row">
      <span>Username</span>
      <span class="info-val"><?= htmlspecialchars($admin['username'] ?? 'admin') ?>
        <button class="copy-btn" onclick="copy('<?= htmlspecialchars($admin['username'] ?? 'admin') ?>')">Copy</button>
      </span>
    </div>
    <div class="info-row">
      <span>Password</span>
      <span class="info-val" id="pw-show">••••••••
        <button class="copy-btn" onclick="revealPw()">Show</button>
        <button class="copy-btn" onclick="copy('<?= htmlspecialchars($admin['password'] ?? '') ?>')">Copy</button>
      </span>
    </div>
    <div class="info-row">
      <span>Email</span>
      <span class="info-val"><?= htmlspecialchars($admin['email'] ?? '') ?></span>
    </div>

    <div class="alert alert-error" style="margin-top:20px">
      ⚠️ <strong>Security:</strong> Delete <code>public/install.php</code> from your server immediately after logging in.
    </div>

    <div class="btn-group" style="justify-content:center;margin-top:20px">
      <a href="<?= htmlspecialchars($appUrl) ?>/login" class="btn btn-primary" target="_blank">Go to Login →</a>
    </div>
    <?php endif; ?>

  </div><!-- /card -->
  <footer>Digital ISP ERP &copy; <?= date('Y') ?> &nbsp;·&nbsp; Installer v<?= INSTALLER_VERSION ?></footer>
</div>

<script>
function setDb(type) {
  document.getElementById('db_type').value = type;
  document.querySelectorAll('.db-tab').forEach(t => t.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('mysql_fields').classList.toggle('show', type === 'mysql');
}
function togglePw(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
function copy(text) {
  navigator.clipboard.writeText(text).then(() => {
    const btn = event.target;
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 1500);
  });
}
function revealPw() {
  const el = document.getElementById('pw-show');
  el.childNodes[0].textContent = '<?= addslashes($admin['password'] ?? '') ?>';
  event.target.textContent = 'Hide';
  event.target.onclick = () => {
    el.childNodes[0].textContent = '••••••••';
    event.target.textContent = 'Show';
    event.target.onclick = revealPw;
  };
}
</script>
</body>
</html>
