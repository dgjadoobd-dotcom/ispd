# RADIUS Service Enhancement — System Administration Guide

Practical reference for deploying, operating, and maintaining the RADIUS service enhancement stack.

---

## 1. System Requirements

### Software

| Component | Minimum version |
|---|---|
| PHP | 8.1 |
| MySQL | 8.0 |
| Redis | 6.0 |
| Nginx | 1.20 |
| Docker + Compose | 20.10 / 2.0 (optional) |

### Required PHP extensions

```
pdo pdo_mysql mysqli json mbstring openssl curl redis opcache
```

Verify installed extensions:

```bash
php -m | grep -E 'pdo|mysql|json|mbstring|openssl|curl|redis|opcache'
```

### Minimum server specs

| Resource | Minimum | Recommended |
|---|---|---|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Disk | 20 GB SSD | 50 GB SSD |
| MySQL buffer pool | 512 MB | 1–2 GB |

---

## 2. Installation & Initial Setup

### Database migrations

Run migrations in order against your MySQL `radius` database:

```bash
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_01_portal_schema.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_001_create_radius_user_profiles.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_002_create_radius_sessions.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_003_create_radius_audit_logs.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_004_create_radius_usage_daily.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_005_create_radius_alerts.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_006_radius_indexes_optimizations.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_007_create_ip_access_rules.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_008_create_rate_limit_hits.sql

mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/2024_01_02_009_query_optimizations.sql
```

To roll back all RADIUS enhancement tables:

```bash
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE \
    < database/migrations/rollback_radius_enhanced.sql
```

### Environment variables

Copy `.env.example` to `.env` and set the following key variables:

| Variable | Description |
|---|---|
| `APP_ENV` | `production` / `staging` / `local` |
| `APP_KEY` | Base64-encoded application key |
| `DB_HOST` / `DB_PORT` | Main application database host |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Main DB credentials |
| `RADIUS_HOST` / `RADIUS_PORT` | FreeRADIUS server address (default port 1812) |
| `RADIUS_DB_HOST` / `RADIUS_DB_DATABASE` | RADIUS MySQL database connection |
| `RADIUS_DB_USERNAME` / `RADIUS_DB_PASSWORD` | RADIUS DB credentials |
| `JWT_SECRET` | Secret for signing JWT tokens — use a long random string |
| `BACKUP_KEEP_DAYS` | Days to retain backup files (default: 30) |

> Never commit `.env` to version control. Restrict file permissions to `600`.

### Cron jobs

Add to the web server user's crontab (`crontab -e -u www-data`):

```cron
# Daily usage rollup — aggregates radius_sessions into radius_usage_daily
5 0 * * * php /var/www/html/cron_radius_rollup.php >> /var/log/radius_rollup.log 2>&1

# Daily database backup at 02:00
0 2 * * * bash /var/www/html/scripts/backup_radius.sh >> /var/log/radius_backup.log 2>&1
```

Run the rollup at `00:05` (not `00:00`) to allow sessions that ended just before midnight to be committed. Pass `--date=YYYY-MM-DD` to reprocess a specific day:

```bash
php /var/www/html/cron_radius_rollup.php --date=2024-01-15
```

---

## 3. Service Management

### Docker Compose (development)

```bash
# Start all services
docker compose -f docker-compose.dev.yml up -d

# Stop all services
docker compose -f docker-compose.dev.yml down

# Restart a single service
docker compose -f docker-compose.dev.yml restart app

# Rebuild after code changes
docker compose -f docker-compose.dev.yml up -d --build app
```

Services exposed locally:

| Service | URL |
|---|---|
| Application | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | localhost:3306 |
| Redis | localhost:6379 |

### Health check

The `/health` endpoint returns HTTP 200 when the application and its dependencies are reachable:

```bash
curl -s http://localhost:8080/health
```

A non-200 response for more than 2 minutes should trigger a rollback (see `docs/rollback-procedures.md`).

### Viewing logs

```bash
# Application logs
tail -f storage/logs/app.log

# Docker container logs
docker logs -f radius-app-dev

# Cron output
tail -f /var/log/radius_rollup.log
tail -f /var/log/radius_backup.log

# Nginx access/error logs (Docker)
docker logs -f radius-nginx-dev
```

Log rotation is configured in `docker/logrotate/radius.conf`.

---

## 4. Database Maintenance

### Weekly ANALYZE TABLE

Keep query planner statistics fresh. Add to a weekly maintenance window or cron:

```sql
ANALYZE TABLE radius_sessions;
ANALYZE TABLE radius_audit_logs;
ANALYZE TABLE radius_alerts;
ANALYZE TABLE radius_usage_daily;
```

### Backup schedule

`scripts/backup_radius.sh` performs a `mysqldump --single-transaction` and compresses the output with gzip. It reads credentials from environment variables and prunes files older than `BACKUP_KEEP_DAYS` days automatically.

```bash
# Manual backup
bash scripts/backup_radius.sh

# Restore from a backup
bash scripts/restore_radius.sh backups/radius_<TIMESTAMP>.sql.gz
```

Backups are written to `backups/radius_<YYYYMMDD_HHMMSS>.sql.gz`.

### Checking table sizes

```sql
SELECT
    table_name,
    ROUND(data_length / 1024 / 1024, 2)  AS data_mb,
    ROUND(index_length / 1024 / 1024, 2) AS index_mb,
    table_rows
FROM information_schema.tables
WHERE table_schema = 'radius'
ORDER BY data_length DESC;
```

---

## 5. Monitoring Setup

### Prometheus + Grafana

Monitoring configuration lives in `docker/monitoring/`:

| File | Purpose |
|---|---|
| `prometheus.yml` | Scrape targets (app `:9090`, MySQL exporter `:9104`, Redis exporter `:9121`) |
| `alert_rules.yml` | Alert definitions |
| `alertmanager.yml` | Routing and notification receivers |

Prometheus scrapes the app every 15 seconds. The app exposes metrics at `/metrics`.

### Alert rules (`docker/monitoring/alert_rules.yml`)

| Alert | Condition | Severity |
|---|---|---|
| `HighErrorRate` | Error rate > 5% over 5 min | critical |
| `HighResponseLatency` | p95 latency > 500 ms | warning |
| `ServiceDown` | Target unreachable for 1 min | critical |
| `HighMemoryUsage` | Memory > 85% of limit for 5 min | warning |
| `MySQLDown` | MySQL exporter reports DB unreachable | critical |
| `RedisDown` | Redis exporter reports cache unreachable | critical |

### Alertmanager (`docker/monitoring/alertmanager.yml`)

- Critical alerts repeat every 1 hour; warnings repeat every 4 hours.
- Default receiver posts to a webhook at `http://localhost:5001/webhook`.
- To enable email notifications, uncomment and fill in the `email_configs` block in `alertmanager.yml` under the `critical` receiver.
- Critical alerts suppress duplicate warning-level alerts for the same `alertname` via the `inhibit_rules` block.

---

## 6. Troubleshooting Common Issues

### RADIUS authentication failures

1. Confirm FreeRADIUS is reachable: `RADIUS_HOST` and `RADIUS_PORT` in `.env`.
2. Check `storage/logs/app.log` for `RadiusService` errors.
3. Verify the user exists in `radius_user_profiles` and their plan is active.
4. Confirm the RADIUS DB credentials (`RADIUS_DB_*`) are correct and the `radius` database is accessible.
5. Check for IP access control blocks in `radius_ip_access_rules` (managed by `IpAccessControlService`).

### High session count alerts

1. Query for users with abnormally high concurrent sessions:
   ```sql
   SELECT username, COUNT(*) AS sessions
   FROM radius_sessions
   WHERE status = 'active'
   GROUP BY username
   ORDER BY sessions DESC
   LIMIT 20;
   ```
2. Check `RadiusAlertService` logs — alerts are stored in `radius_alerts`.
3. If stale sessions are accumulating, verify the session timeout cron (`RadiusSessionTimeoutService`) is running.

### Slow queries

1. Enable the MySQL slow query log in `my.cnf`:
   ```ini
   slow_query_log = 1
   slow_query_log_file = /var/log/mysql/slow.log
   long_query_time = 1
   log_queries_not_using_indexes = 1
   ```
2. Review with: `mysqldumpslow -s t /var/log/mysql/slow.log | head -20`
3. Run `EXPLAIN` on suspect queries and verify composite indexes from migration `009` are being used (see `docs/performance-optimization.md` §3).
4. Run `ANALYZE TABLE` on the affected table to refresh statistics.

### PHP-FPM worker exhaustion

Symptoms: 502 errors, `connect() to unix:/run/php/php-fpm.sock failed`.

1. Check current worker count: `ps aux | grep php-fpm | wc -l`
2. Calculate safe `pm.max_children`:
   ```bash
   ps --no-headers -o rss -C php-fpm | awk '{sum+=$1} END {print sum/NR/1024 " MB avg"}'
   # max_children = floor(available_RAM_MB / avg_worker_MB)
   ```
3. Update `/etc/php/8.x/fpm/pool.d/www.conf` and reload: `systemctl reload php8.x-fpm`
4. See `docs/performance-optimization.md` §6 for recommended values.

---

## 7. Security Checklist

### Environment variable security

- [ ] `.env` file permissions set to `600`, owned by the web server user
- [ ] `APP_KEY` is a unique base64-encoded random key (never reuse across environments)
- [ ] `JWT_SECRET` is a long random string, rotated if compromised
- [ ] `APP_DEBUG=false` in production — debug mode exposes stack traces
- [ ] Database passwords are strong and unique per environment

### File permissions

```bash
# Application root — web server user owns files, not world-writable
chown -R www-data:www-data /var/www/html
find /var/www/html -type f -exec chmod 644 {} \;
find /var/www/html -type d -exec chmod 755 {} \;

# Writable directories only
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/backups

# Protect .env
chmod 600 /var/www/html/.env
```

### Rate limiting configuration

Rate limiting is handled by `ApiRateLimitMiddleware` and `RateLimiterService`, with hit counts stored in `rate_limit_hits` (migration `008`).

Key tuning points in `.env` or `config/app.php`:
- Adjust per-route limits to match expected traffic patterns.
- Review `rate_limit_hits` periodically to identify abusive clients.
- Nginx-level rate limiting can be added in `docker/nginx/default.conf` as a first line of defence:
  ```nginx
  limit_req_zone $binary_remote_addr zone=api:10m rate=30r/m;
  limit_req zone=api burst=10 nodelay;
  ```

### IP access control

IP allowlists and blocklists are managed via `IpAccessControlService` and stored in `radius_ip_access_rules` (migration `007`).

- Add trusted NAS/management IPs to the allowlist via the admin UI or directly in `radius_ip_access_rules`.
- Block known abusive IPs by inserting `deny` rules.
- Review the table regularly and remove stale entries.
