<?php

/**
 * Cache — simple file-based cache with TTL support.
 *
 * Stores serialized data in storage/cache/ using md5-hashed filenames.
 * No external dependencies required.
 */
class Cache
{
    private static ?self $instance = null;
    private string $cacheDir;

    private function __construct()
    {
        $this->cacheDir = defined('BASE_PATH')
            ? BASE_PATH . '/storage/cache'
            : dirname(__DIR__, 2) . '/storage/cache';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return the file path for a given key.
     */
    private function filePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Read and deserialize a cache file, returning null if missing or expired.
     *
     * @return array{expires_at: int, data: mixed}|null
     */
    private function readFile(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $entry = @unserialize($raw);
        if (!is_array($entry) || !isset($entry['expires_at'], $entry['data'])) {
            return null;
        }

        return $entry;
    }

    /**
     * Get a cached value. Returns null if not found or expired.
     */
    public function get(string $key): mixed
    {
        $entry = $this->readFile($this->filePath($key));

        if ($entry === null) {
            return null;
        }

        if (time() > $entry['expires_at']) {
            $this->delete($key);
            return null;
        }

        return $entry['data'];
    }

    /**
     * Store a value in the cache with a TTL (default 300 seconds).
     */
    public function set(string $key, mixed $value, int $ttlSeconds = 300): void
    {
        $entry = [
            'expires_at' => time() + $ttlSeconds,
            'data'       => $value,
        ];

        file_put_contents($this->filePath($key), serialize($entry), LOCK_EX);
    }

    /**
     * Remove a cache entry.
     */
    public function delete(string $key): void
    {
        $path = $this->filePath($key);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Check whether a key exists and has not expired.
     */
    public function has(string $key): bool
    {
        $entry = $this->readFile($this->filePath($key));

        if ($entry === null) {
            return false;
        }

        if (time() > $entry['expires_at']) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Delete all cache files.
     */
    public function flush(): void
    {
        $files = glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /**
     * Return cached value if present; otherwise execute $callback, cache the
     * result, and return it.
     */
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttlSeconds);
        return $value;
    }
}
