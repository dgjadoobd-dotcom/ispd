# Performance Optimization Guide

Practical tuning recommendations for the RADIUS service PHP/MySQL stack.

---

## 1. PHP OPcache Configuration

Add to `/etc/php/8.x/fpm/php.ini` (or `docker/php/php.ini`):

```ini
; Enable OPcache
opcache.enable=1
opcache.enable_cli=0

; Memory for compiled bytecode (MB) — 128 MB covers most ISP apps
opcache.memory_consumption=128

; Maximum number of files to cache — set above your total PHP file count
opcache.max_accelerated_files=10000

; Seconds between revalidation checks (0 = always check, bad for prod)
; Set to 60 in staging, 0 only in development
opcache.revalidate_freq=60

; Validate file timestamps — set to 0 after deployment for max speed
opcache.validate_timestamps=1

; Interned strings buffer (MB)
opcache.interned_strings_buffer=16

; Enable fast shutdown sequence
opcache.fast_shutdown=1
```

> After deploying new code, run `opcache_reset()` or restart PHP-FPM to clear stale bytecode.

---

## 2. Database Connection Pooling

`DatabasePool` (`app/Core/DatabasePool.php`) wraps PDO persistent connections so the underlying MySQL socket is reused across PHP-FPM worker requests instead of being torn down and re-established each time.

**How PDO persistent connections work:** When `PDO::ATTR_PERSISTENT => true` is set, PHP-FPM keeps the connection open in the worker process after the request ends. The next request handled by the same worker reuses it, skipping the TCP handshake and authentication round-trip.

### Setup

Register your connection once (e.g. in `config/database.php`):

```php
DatabasePool::configure('radius', [
    'dsn'      => 'mysql:host=127.0.0.1;dbname=radius;charset=utf8mb4',
    'username' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASS'],
]);
```

### Usage in services

```php
// Anywhere you need a PDO instance:
$pdo = DatabasePool::getInstance('radius')->getConnection();

// Example — fetch active sessions for a user
$stmt = $pdo->prepare(
    'SELECT * FROM radius_sessions WHERE username = ? AND status = ?'
);
$stmt->execute([$username, 'active']);
$sessions = $stmt->fetchAll();
```

> Use a single named connection per database. Avoid calling `DatabasePool::closeAll()` inside request handlers — reserve it for CLI scripts and graceful shutdown routines.

---

## 3. Query Optimization Checklist

### Indexes from migration 009

Migration `2024_01_02_009_query_optimizations.sql` adds these composite indexes:

| Index | Table | Columns | Use case |
|---|---|---|---|
| `idx_sessions_username_status` | `radius_sessions` | `(username, status)` | Concurrent session limit checks |
| `idx_sessions_status_start_time` | `radius_sessions` | `(status, start_time)` | Dashboard time-range queries |
| `idx_audit_action_created_at` | `radius_audit_logs` | `(action, created_at)` | Admin event filtering |
| `idx_alerts_severity_resolved_at` | `radius_alerts` | `(severity, resolved_at)` | Open alert surfacing |

`radius_usage_daily` already has a `UNIQUE KEY uq_username_date (username, date)` from migration 004 — no additional index needed.

### EXPLAIN usage

Prefix any slow query with `EXPLAIN` to verify index usage:

```sql
EXPLAIN SELECT * FROM radius_sessions
WHERE username = 'alice' AND status = 'active';
```

Key columns to check:
- `key` — should show the composite index name (e.g. `idx_sessions_username_status`)
- `type` — `ref` or `range` is good; `ALL` means a full table scan
- `rows` — lower is better; a high value with `type=ALL` signals a missing index

### Keep statistics fresh

Run weekly (add to cron or maintenance window):

```sql
ANALYZE TABLE radius_sessions;
ANALYZE TABLE radius_audit_logs;
ANALYZE TABLE radius_alerts;
ANALYZE TABLE radius_usage_daily;
```

### Slow query log

Enable in `my.cnf` to catch regressions early:

```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1
log_queries_not_using_indexes = 1
```

Review with `mysqldumpslow -s t /var/log/mysql/slow.log | head -20`.

---

## 4. File-Based Cache Usage

`Cache` (`app/Core/Cache.php`) stores serialized data in `storage/cache/` with TTL-based expiry. Use `remember()` to wrap expensive queries.

### Basic pattern

```php
$cache = Cache::getInstance();

$stats = $cache->remember('radius:dashboard:stats', 300, function () use ($pdo) {
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM radius_sessions WHERE status = "active"');
    return $stmt->fetch();
});
```

### Recommended TTL values

| Data type | TTL | Rationale |
|---|---|---|
| Active session count (dashboard) | 60 s | Near-real-time feel without hammering DB |
| Daily usage rollup totals | 300 s (5 min) | Updated by cron, stale for short periods is fine |
| User profile / plan details | 600 s (10 min) | Changes infrequently |
| Alert summaries | 120 s | Operators expect reasonably fresh data |
| Analytics aggregates (weekly/monthly) | 3600 s (1 hr) | Heavy queries, data rarely changes mid-day |

### Cache invalidation patterns

**On write — delete the affected key:**

```php
// After updating a user profile:
Cache::getInstance()->delete("radius:user:profile:{$username}");
```

**Versioned keys — bump a version prefix when bulk data changes:**

```php
$version = Cache::getInstance()->remember('radius:cache:version', 86400, fn() => 1);
$key = "radius:sessions:active:v{$version}";
```

**Flush on deployment** (add to deploy script):

```php
Cache::getInstance()->flush();
```

---

## 5. Cron Job Scheduling

### Daily rollup — `cron_radius_rollup.php`

Aggregates `radius_sessions` into `radius_usage_daily` for the previous day.

```cron
# Run at 00:05 daily — after midnight so the previous day's sessions are closed
5 0 * * * php /var/www/html/cron_radius_rollup.php >> /var/log/radius_rollup.log 2>&1
```

Run at 00:05 (not 00:00) to allow any sessions that ended just before midnight to be committed.

### Session sync cron

Syncs active session state from the NAS/FreeRADIUS accounting records.

```cron
# Every 5 minutes during business hours, every 15 minutes overnight
*/5 6-23 * * * php /var/www/html/cron_session_sync.php >> /var/log/radius_session_sync.log 2>&1
*/15 0-5  * * * php /var/www/html/cron_session_sync.php >> /var/log/radius_session_sync.log 2>&1
```

### Backup cron

```cron
# Full DB dump at 02:00 daily, keep 7 days
0 2 * * * mysqldump -u$DB_USER -p$DB_PASS radius | gzip > /backups/radius_$(date +\%F).sql.gz
# Prune backups older than 7 days
10 2 * * * find /backups -name 'radius_*.sql.gz' -mtime +7 -delete
```

> Log all cron output to dedicated files and monitor them with your alerting stack (Prometheus/Alertmanager config is in `docker/monitoring/`).

---

## 6. PHP-FPM Tuning

Edit `/etc/php/8.x/fpm/pool.d/www.conf`:

```ini
pm = dynamic
```

### `pm.max_children` formula

```
pm.max_children = floor(available_RAM_MB / avg_worker_RSS_MB)
```

Check average worker RSS:

```bash
ps --no-headers -o rss -C php-fpm | awk '{sum+=$1} END {print sum/NR/1024 " MB avg"}'
```

Example: 4 GB server, 60 MB avg worker → `pm.max_children = 68` (leave ~500 MB headroom for MySQL/OS).

### Recommended values (4 GB server, ~60 MB/worker)

```ini
pm.max_children      = 60
pm.start_servers     = 10   ; ~16% of max_children
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests      = 500  ; recycle workers to prevent memory leaks
```

**General rules:**
- `start_servers` ≈ 15–20% of `max_children`
- `min_spare_servers` ≈ `start_servers / 2`
- `max_spare_servers` ≈ `start_servers * 2`
- Set `pm.max_requests = 500` to recycle workers and prevent slow memory leaks from long-running processes

After changing FPM config: `systemctl reload php8.x-fpm` (or `docker compose restart php` in the dev stack).
