<?php
/**
 * Digital ISP ERP — Auto Backup Script
 *
 * Backs up:
 *   - SQLite database file
 *   - MySQL databases (radius, piprapay) via mysqldump
 *   - .env configuration
 *   - Uploaded media (public/assets/uploads)
 *
 * Usage:
 *   php scripts/backup.php              — full backup
 *   php scripts/backup.php db           — database only
 *   php scripts/backup.php files        — files only
 *   php scripts/backup.php clean        — remove old backups only
 *
 * Output: storage/backups/backup_YYYY-MM-DD_HH-MM-SS.zip
 */

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/app.php';

// ── Config ────────────────────────────────────────────────────────────────────
$backupDir   = BASE_PATH . '/storage/backups';
$keepDays    = (int)(getenv('BACKUP_KEEP_DAYS') ?: 30);   // days to retain
$maxBackups  = (int)(getenv('BACKUP_MAX_COUNT') ?: 60);   // max backup files
$job         = $argv[1] ?? 'full';
$timestamp   = date('Y-m-d_H-i-s');
$backupName  = "backup_{$timestamp}";
$backupPath  = "{$backupDir}/{$backupName}";
$zipFile     = "{$backupPath}.zip";

// ── Helpers ───────────────────────────────────────────────────────────────────
function log_msg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    $logFile = BASE_PATH . '/storage/logs/backup.log';
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function ensure_dir(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function human_size(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function add_to_zip(ZipArchive $zip, string $src, string $zipBase): void {
    if (!file_exists($src)) return;
    if (is_file($src)) {
        $zip->addFile($src, $zipBase . '/' . basename($src));
        return;
    }
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $file) {
        $rel = $zipBase . '/' . str_replace($src . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $rel = str_replace('\\', '/', $rel);
        if ($file->isDir()) {
            $zip->addEmptyDir($rel);
        } else {
            $zip->addFile($file->getPathname(), $rel);
        }
    }
}

// ── Load env for DB credentials ───────────────────────────────────────────────
$env = file_exists(BASE_PATH . '/.env') ? parse_ini_file(BASE_PATH . '/.env') : [];

// ── Main ──────────────────────────────────────────────────────────────────────
ensure_dir($backupDir);
ensure_dir(BASE_PATH . '/storage/logs');
ensure_dir($backupPath);

log_msg("=== Backup started: {$backupName} (job: {$job}) ===");

$errors = [];

// ── 1. SQLite backup ──────────────────────────────────────────────────────────
if (in_array($job, ['full', 'db'])) {
    $sqliteFile = BASE_PATH . '/database/digital-isp.sqlite';
    if (file_exists($sqliteFile)) {
        $dest = "{$backupPath}/digital-isp.sqlite";
        if (copy($sqliteFile, $dest)) {
            log_msg("SQLite: " . human_size(filesize($dest)));
        } else {
            $errors[] = 'SQLite copy failed';
            log_msg("ERROR: SQLite copy failed");
        }
    } else {
        log_msg("SQLite: file not found (skipped)");
    }
}

// ── 2. MySQL dumps ────────────────────────────────────────────────────────────
if (in_array($job, ['full', 'db'])) {
    $mysqldump = PHP_OS_FAMILY === 'Windows'
        ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe'
        : (shell_exec('which mysqldump') ? trim(shell_exec('which mysqldump')) : 'mysqldump');

    $mysqlDbs = [
        'radius'   => [
            'host' => $env['RADIUS_DB_HOST']     ?? 'localhost',
            'port' => $env['RADIUS_DB_PORT']     ?? '3306',
            'user' => $env['RADIUS_DB_USERNAME'] ?? 'root',
            'pass' => $env['RADIUS_DB_PASSWORD'] ?? '',
            'db'   => $env['RADIUS_DB_DATABASE'] ?? 'radius',
        ],
        'piprapay' => [
            'host' => $env['PAYBILL_DB_HOST']     ?? 'localhost',
            'port' => $env['PAYBILL_DB_PORT']     ?? '3306',
            'user' => $env['PAYBILL_DB_USERNAME'] ?? 'root',
            'pass' => $env['PAYBILL_DB_PASSWORD'] ?? '',
            'db'   => $env['PAYBILL_DB_NAME']     ?? 'piprapay',
        ],
    ];

    foreach ($mysqlDbs as $label => $cfg) {
        $dumpFile = "{$backupPath}/{$label}.sql";
        $passArg  = $cfg['pass'] !== '' ? '-p' . escapeshellarg($cfg['pass']) : '';
        $cmd = sprintf(
            '%s -h %s -P %s -u %s %s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellcmd($mysqldump),
            escapeshellarg($cfg['host']),
            escapeshellarg($cfg['port']),
            escapeshellarg($cfg['user']),
            $passArg,
            escapeshellarg($cfg['db']),
            escapeshellarg($dumpFile)
        );
        exec($cmd, $out, $code);
        if ($code === 0 && file_exists($dumpFile) && filesize($dumpFile) > 100) {
            log_msg("MySQL [{$label}]: " . human_size(filesize($dumpFile)));
        } else {
            log_msg("MySQL [{$label}]: skipped or failed (DB may not exist)");
            @unlink($dumpFile);
        }
    }
}

// ── 3. .env backup ────────────────────────────────────────────────────────────
if (in_array($job, ['full', 'files'])) {
    $envFile = BASE_PATH . '/.env';
    if (file_exists($envFile)) {
        copy($envFile, "{$backupPath}/.env.bak");
        log_msg(".env: backed up");
    }
}

// ── 4. Uploads backup ─────────────────────────────────────────────────────────
if (in_array($job, ['full', 'files'])) {
    $uploadsDir = BASE_PATH . '/public/assets/uploads';
    if (is_dir($uploadsDir)) {
        // Copy uploads folder
        $destUploads = "{$backupPath}/uploads";
        ensure_dir($destUploads);
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $count = 0;
        foreach ($iter as $file) {
            if ($file->isFile()) {
                $rel  = str_replace($uploadsDir, '', $file->getPathname());
                $dest = $destUploads . $rel;
                ensure_dir(dirname($dest));
                copy($file->getPathname(), $dest);
                $count++;
            }
        }
        log_msg("Uploads: {$count} files");
    } else {
        log_msg("Uploads: directory not found (skipped)");
    }
}

// ── 5. Zip everything ─────────────────────────────────────────────────────────
if ($job !== 'clean') {
    $zipped = false;

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            add_to_zip($zip, $backupPath, $backupName);
            $zip->close();
            $zipped = true;
        }
    }

    if (!$zipped) {
        // Try system zip command
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'powershell -Command "Compress-Archive -Path ' .
                   escapeshellarg($backupPath) . ' -DestinationPath ' .
                   escapeshellarg($zipFile) . ' -Force" 2>&1';
        } else {
            $cmd = 'zip -r ' . escapeshellarg($zipFile) . ' ' . escapeshellarg($backupPath) . ' 2>&1';
        }
        exec($cmd, $out, $code);
        if ($code === 0 && file_exists($zipFile)) {
            $zipped = true;
        }
    }

    if (!$zipped) {
        // Last resort: keep the folder as-is, rename to .bak
        $bakPath = $backupDir . '/' . $backupName . '.bak';
        rename($backupPath, $bakPath);
        log_msg("Zip unavailable — backup saved as folder: " . basename($bakPath));
        // Update zipFile reference for size reporting
        $zipFile = $bakPath;
    } else {
        // Remove temp folder
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backupPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($backupPath);
    }

    if (file_exists($zipFile)) {
        log_msg("Backup saved: " . basename($zipFile) . " (" . human_size(filesize($zipFile)) . ")");
    }
}

// ── 6. Cleanup old backups ────────────────────────────────────────────────────
$allBackups = glob($backupDir . '/backup_*');
if ($allBackups) {
    usort($allBackups, fn($a, $b) => filemtime($a) - filemtime($b));

    $cutoff  = time() - ($keepDays * 86400);
    $removed = 0;

    foreach ($allBackups as $f) {
        if (filemtime($f) < $cutoff) {
            is_dir($f) ? (function($d){ $i=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($i as $x){$x->isDir()?rmdir($x):unlink($x);} rmdir($d); })($f) : unlink($f);
            $removed++;
        }
    }

    $remaining = glob($backupDir . '/backup_*');
    if ($remaining && count($remaining) > $maxBackups) {
        usort($remaining, fn($a, $b) => filemtime($a) - filemtime($b));
        $toDelete = array_slice($remaining, 0, count($remaining) - $maxBackups);
        foreach ($toDelete as $f) {
            is_dir($f) ? rmdir($f) : unlink($f);
            $removed++;
        }
    }

    if ($removed > 0) log_msg("Cleanup: removed {$removed} old backup(s)");
}

// ── Summary ───────────────────────────────────────────────────────────────────
$remaining = count(glob($backupDir . '/backup_*.zip') ?: []);
if (empty($errors)) {
    log_msg("=== Backup complete. Total backups stored: {$remaining} ===");
    exit(0);
} else {
    log_msg("=== Backup finished with errors: " . implode(', ', $errors) . " ===");
    exit(1);
}
