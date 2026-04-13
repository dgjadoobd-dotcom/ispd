<?php

/**
 * PerformanceMetricsService — collects and exposes runtime performance metrics.
 *
 * Metrics collected:
 *   - active_sessions   : count of radius_sessions WHERE status='active'
 *   - db_query_time_ms  : latency of a simple SELECT 1 (ms)
 *   - memory_usage_mb   : current PHP memory usage (MB)
 *   - memory_peak_mb    : peak PHP memory usage (MB)
 *   - uptime_seconds    : seconds since PHP process started
 *   - timestamp         : current Unix timestamp
 */
class PerformanceMetricsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Collect current metrics and return them as an associative array.
     */
    public function collect(): array
    {
        // Active sessions
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM radius_sessions WHERE status = 'active'");
        $stmt->execute();
        $activeSessions = (int) $stmt->fetchColumn();

        // DB query latency
        $start = microtime(true);
        $this->pdo->query('SELECT 1');
        $dbQueryTimeMs = round((microtime(true) - $start) * 1000, 3);

        // Memory
        $memoryUsageMb = round(memory_get_usage(true) / 1048576, 2);
        $memoryPeakMb  = round(memory_get_peak_usage(true) / 1048576, 2);

        // Uptime
        $requestTime   = $_SERVER['REQUEST_TIME'] ?? time();
        $uptimeSeconds = time() - (int) $requestTime;

        return [
            'active_sessions'  => $activeSessions,
            'db_query_time_ms' => $dbQueryTimeMs,
            'memory_usage_mb'  => $memoryUsageMb,
            'memory_peak_mb'   => $memoryPeakMb,
            'uptime_seconds'   => $uptimeSeconds,
            'timestamp'        => time(),
        ];
    }

    /**
     * Return metrics formatted as Prometheus text exposition.
     * Each line: radius_<metric_name> <value>
     */
    public function getPrometheusMetrics(): string
    {
        $metrics = $this->collect();
        $output  = '';

        foreach ($metrics as $name => $value) {
            $output .= "radius_{$name} {$value}\n";
        }

        return $output;
    }

    /**
     * Return metrics from cache, refreshing when the TTL has expired.
     */
    public function getCachedMetrics(int $ttlSeconds = 30): array
    {
        return Cache::getInstance()->remember(
            'performance_metrics',
            $ttlSeconds,
            fn () => $this->collect()
        );
    }
}
