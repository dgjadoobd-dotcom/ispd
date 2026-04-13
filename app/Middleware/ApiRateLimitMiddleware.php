<?php

class ApiRateLimitMiddleware
{
    private RateLimiterService $rateLimiter;
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(RateLimiterService $rateLimiter, int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->rateLimiter  = $rateLimiter;
        $this->maxRequests  = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(): void
    {
        // Determine rate limit key: prefer API key header, fall back to client IP
        $key = isset($_SERVER['HTTP_X_API_KEY']) && $_SERVER['HTTP_X_API_KEY'] !== ''
            ? 'apikey:' . $_SERVER['HTTP_X_API_KEY']
            : 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        $currentHits = $this->rateLimiter->increment($key, $this->windowSeconds);
        $allowed     = $this->rateLimiter->check($key, $this->maxRequests, $this->windowSeconds);

        if (!$allowed) {
            http_response_code(429);
            header('Content-Type: application/json');
            header('Retry-After: ' . $this->windowSeconds);
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please retry after ' . $this->windowSeconds . ' seconds.',
                'code' => 429,
            ]);
            exit;
        }

        $remaining = max(0, $this->maxRequests - $currentHits);
        $reset     = time() + $this->windowSeconds;

        header('X-RateLimit-Limit: '     . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: '     . $reset);
    }

    public static function applyToApi(PDO $pdo, int $maxRequests = 60, int $windowSeconds = 60): void
    {
        $service    = new RateLimiterService($pdo);
        $middleware = new self($service, $maxRequests, $windowSeconds);
        $middleware->handle();
    }
}
