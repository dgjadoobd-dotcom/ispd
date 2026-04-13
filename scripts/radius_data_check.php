<?php
$env = parse_ini_file(dirname(__DIR__) . '/.env');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $env['RADIUS_DB_HOST'], $env['RADIUS_DB_PORT'], $env['RADIUS_DB_DATABASE']),
        $env['RADIUS_DB_USERNAME'], $env['RADIUS_DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $tables = [
        'radcheck'            => 'SELECT COUNT(*) FROM radcheck',
        'radusergroup'        => 'SELECT COUNT(*) FROM radusergroup',
        'radacct'             => 'SELECT COUNT(*) FROM radacct',
        'radpostauth'         => 'SELECT COUNT(*) FROM radpostauth',
        'radius_sessions'     => 'SELECT COUNT(*) FROM radius_sessions',
        'radius_alerts'       => 'SELECT COUNT(*) FROM radius_alerts',
        'radius_audit_logs'   => 'SELECT COUNT(*) FROM radius_audit_logs',
        'radius_usage_daily'  => 'SELECT COUNT(*) FROM radius_usage_daily',
        'radius_user_profiles'=> 'SELECT COUNT(*) FROM radius_user_profiles',
    ];

    echo "=== Row Counts ===" . PHP_EOL;
    foreach ($tables as $name => $sql) {
        $count = $pdo->query($sql)->fetchColumn();
        echo sprintf("  %-25s %d rows\n", $name, $count);
    }

    echo PHP_EOL . "=== Sample radcheck users (first 5) ===" . PHP_EOL;
    $rows = $pdo->query("SELECT username, attribute, value FROM radcheck LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  (no users in radcheck)" . PHP_EOL;
    } else {
        foreach ($rows as $r) {
            echo "  user={$r['username']}  attr={$r['attribute']}  val={$r['value']}" . PHP_EOL;
        }
    }

    echo PHP_EOL . "=== Sample radacct sessions (last 5) ===" . PHP_EOL;
    $rows = $pdo->query("SELECT username, nasipaddress, acctstarttime, acctstoptime, acctinputoctets, acctoutputoctets FROM radacct ORDER BY radacctid DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  (no accounting records in radacct)" . PHP_EOL;
    } else {
        foreach ($rows as $r) {
            echo "  user={$r['username']}  nas={$r['nasipaddress']}  start={$r['acctstarttime']}  stop=" . ($r['acctstoptime'] ?: 'ACTIVE') . PHP_EOL;
        }
    }

    echo PHP_EOL . "=== radius_sessions table ===" . PHP_EOL;
    $rows = $pdo->query("SELECT username, nas_ip, status, start_time FROM radius_sessions LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  (empty — needs sync from radacct)" . PHP_EOL;
    } else {
        foreach ($rows as $r) {
            echo "  user={$r['username']}  nas={$r['nas_ip']}  status={$r['status']}  start={$r['start_time']}" . PHP_EOL;
        }
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
