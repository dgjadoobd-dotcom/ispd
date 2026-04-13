# Load Testing Guide — RADIUS Service

Practical load testing for the RADIUS API endpoints in a production-like environment.

---

## 1. Load Testing Tools

### Apache Bench (`ab`)
Built into most Linux systems (package: `apache2-utils`). Good for quick single-endpoint tests.

```bash
# Install if missing
apt-get install apache2-utils   # Debian/Ubuntu
yum install httpd-tools          # RHEL/CentOS
```

### k6
Modern, scriptable tool with detailed percentile metrics and ramp-up support. Recommended for realistic multi-endpoint scenarios.

```bash
# Install
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg \
  --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" \
  | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update && sudo apt-get install k6
```

**Recommendation:** use k6 for any scenario beyond a single endpoint — it gives p95/p99 breakdowns, error rates, and supports staged load profiles out of the box.

---

## 2. Test Scenarios

Set your base URL before running:

```bash
export BASE_URL="https://your-production-host.example.com"
export API_KEY="your-api-key"
```

---

### Scenario 1: API Health Check Baseline

Establishes a baseline for the health endpoint with no auth overhead.

```bash
ab -n 1000 -c 100 "${BASE_URL}/health"
```

| Parameter | Value |
|---|---|
| Concurrent users | 100 |
| Total requests | 1000 |
| Target | `/health` |

---

### Scenario 2: Active Sessions API

Tests the stats endpoint under moderate concurrency.

```bash
ab -n 500 -c 50 \
   -H "X-API-Key: ${API_KEY}" \
   "${BASE_URL}/api/radius/usage.php?action=stats"
```

| Parameter | Value |
|---|---|
| Concurrent users | 50 |
| Total requests | 500 |
| Target | `/api/radius/usage.php?action=stats` |

---

### Scenario 3: User Search Load Test

```javascript
// k6-user-search.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 20,
  duration: '30s',
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const API_KEY  = __ENV.API_KEY  || '';

export default function () {
  const res = http.get(
    `${BASE_URL}/api/v1/radius/users?username=test`,
    { headers: { 'X-API-Key': API_KEY } }
  );

  check(res, {
    'status 200': (r) => r.status === 200,
    'response time < 100ms': (r) => r.timings.duration < 100,
  });

  sleep(1);
}
```

```bash
k6 run -e BASE_URL="${BASE_URL}" -e API_KEY="${API_KEY}" k6-user-search.js
```

| Parameter | Value |
|---|---|
| Virtual users | 20 |
| Duration | 30 s |
| Target | `/api/v1/radius/users?username=test` |

---

### Scenario 4: Sustained Load Test (Mixed Endpoints)

Ramps up to 100 users, sustains for 5 minutes, then ramps down. Distributes traffic across endpoints in a realistic ratio.

```javascript
// k6-sustained.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '1m',  target: 100 }, // ramp up
    { duration: '5m',  target: 100 }, // sustain
    { duration: '30s', target: 0   }, // ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<100'],  // p95 < 100 ms
    http_req_failed:   ['rate<0.001'], // error rate < 0.1%
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const API_KEY  = __ENV.API_KEY  || '';
const HEADERS  = { 'X-API-Key': API_KEY };

export default function () {
  const roll = Math.random();

  if (roll < 0.50) {
    // 50% — stats
    const res = http.get(`${BASE_URL}/api/radius/usage.php?action=stats`, { headers: HEADERS });
    check(res, { 'stats 200': (r) => r.status === 200 });

  } else if (roll < 0.80) {
    // 30% — active sessions
    const res = http.get(`${BASE_URL}/api/v1/radius/sessions`, { headers: HEADERS });
    check(res, { 'sessions 200': (r) => r.status === 200 });

  } else {
    // 20% — user search
    const res = http.get(`${BASE_URL}/api/v1/radius/users?username=test`, { headers: HEADERS });
    check(res, { 'users 200': (r) => r.status === 200 });
  }

  sleep(1);
}
```

```bash
k6 run -e BASE_URL="${BASE_URL}" -e API_KEY="${API_KEY}" k6-sustained.js
```

| Stage | Duration | Target VUs |
|---|---|---|
| Ramp up | 1 min | 0 → 100 |
| Sustain | 5 min | 100 |
| Ramp down | 30 s | 100 → 0 |

Traffic mix: stats 50% · active sessions 30% · user search 20%

---

## 3. Acceptance Criteria

| Metric | Threshold |
|---|---|
| p95 response time | < 100 ms for all endpoints |
| Error rate | < 0.1% |
| PHP-FPM worker RSS | Stable over time (no upward drift = no memory leak) |

The k6 `thresholds` block in Scenario 4 enforces the first two automatically — the run exits non-zero if either is breached.

### Check for memory leaks

Sample PHP-FPM worker RSS before and after the sustained test:

```bash
# Before test
ps --no-headers -o rss -C php-fpm | awk '{sum+=$1} END {print sum/NR/1024 " MB avg"}'

# Run Scenario 4 ...

# After test — compare; significant growth indicates a leak
ps --no-headers -o rss -C php-fpm | awk '{sum+=$1} END {print sum/NR/1024 " MB avg"}'
```

---

## 4. Monitoring During Load Tests

### Prometheus metrics

Open `http://your-host:9090` and query:

```promql
# Request rate per endpoint
rate(http_requests_total[1m])

# p95 latency
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[1m]))

# PHP-FPM active workers
phpfpm_active_processes
```

Alertmanager rules are in `docker/monitoring/alert_rules.yml`.

### MySQL slow query log

Enable in `my.cnf` if not already active (see `docs/performance-optimization.md` §3):

```ini
slow_query_log = 1
long_query_time = 1
log_queries_not_using_indexes = 1
```

Tail during the test:

```bash
tail -f /var/log/mysql/slow.log
```

Or summarise after:

```bash
mysqldumpslow -s t /var/log/mysql/slow.log | head -20
```

### PHP-FPM status page

Enable in `docker/nginx/default.conf` (already configured in the dev stack):

```nginx
location ~ ^/fpm-status$ {
    fastcgi_pass php:9000;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Then poll during the test:

```bash
watch -n2 'curl -s http://localhost/fpm-status'
```

Key fields to watch: `active processes`, `max active processes`, `slow requests`.
