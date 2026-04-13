# RADIUS Service Rollback Procedures

## When to Rollback

Trigger a rollback if any of the following occur after a deployment:

- Health check endpoint (`/health`) returns non-200 for > 2 minutes
- RADIUS authentication failure rate exceeds baseline by > 10%
- Database migration caused data loss or corruption
- Critical errors in application logs (`storage/logs/`)
- Monitoring alerts firing in Grafana/Prometheus

---

## Quick Rollback (< 5 minutes)

1. SSH into the production server.
2. Navigate to the project root.
3. Run the rollback script with the last known-good image tag:

   ```bash
   bash scripts/rollback.sh --image-tag=<previous-tag>
   ```

4. Confirm the health check passes (script reports success).
5. Verify in Grafana that error rates return to normal.

---

## Database Rollback

### Option A — Drop enhanced schema tables (no backup needed)

Reverts the RADIUS enhancement migrations by dropping the five extended tables:

```bash
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
    < database/migrations/rollback_radius_enhanced.sql
```

Tables dropped (in dependency order):
`radius_alerts` → `radius_usage_daily` → `radius_audit_logs` → `radius_sessions` → `radius_user_profiles`

### Option B — Restore from backup file

```bash
bash scripts/rollback.sh --db-backup=backups/radius_<TIMESTAMP>.sql.gz
```

Or call the restore script directly (prompts for confirmation):

```bash
bash scripts/restore_radius.sh backups/radius_<TIMESTAMP>.sql.gz
```

---

## Docker Image Rollback

To roll back to a specific image tag without touching the database:

```bash
bash scripts/rollback.sh --image-tag=<previous-tag>
```

This updates `docker-compose.prod.yml` and restarts `app1`, `app2`, `app3`.

To find the previous tag, check your CI/CD pipeline history or the container registry.

---

## Full Recovery from Backup

Use this when both the application and database need to be restored:

```bash
bash scripts/rollback.sh \
    --image-tag=<previous-tag> \
    --db-backup=backups/radius_<TIMESTAMP>.sql.gz
```

Steps performed automatically:
1. Updates image tag in `docker-compose.prod.yml` and restarts app containers.
2. Restores the database from the `.sql.gz` backup via `scripts/restore_radius.sh`.
3. Runs a health check against `/health`.

---

## Post-Rollback Verification Checklist

- [ ] `/health` endpoint returns HTTP 200
- [ ] RADIUS authentication succeeds for a test user
- [ ] No new errors in `storage/logs/` since rollback
- [ ] Grafana dashboards show normal request rate and error rate
- [ ] Database tables are present and row counts look correct
- [ ] Redis connectivity confirmed (check app logs for cache errors)
- [ ] Notify the team that rollback is complete and incident is under investigation
