<?php

/**
 * DatabasePool — lightweight PDO connection pool using PHP's built-in persistent connections.
 *
 * Reuses connections across requests instead of creating a new PDO instance on every call.
 * Backed by PDO::ATTR_PERSISTENT so the underlying connection is kept alive by the SAPI.
 */
class DatabasePool
{
    /** @var array<string, self> Singleton instances keyed by connection name */
    private static array $instances = [];

    /** @var array<string, PDO> Live PDO connections keyed by connection name */
    private static array $connections = [];

    /** @var array<string, array{dsn: string, username: string, password: string, options: array}> */
    private static array $configs = [];

    private function __construct() {}

    /**
     * Get the singleton pool instance for the given name.
     */
    public static function getInstance(string $name = 'default'): self
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self();
        }
        return self::$instances[$name];
    }

    /**
     * Register a named connection configuration.
     *
     * @param array{dsn: string, username: string, password: string, options?: array} $config
     */
    public static function configure(string $name, array $config): void
    {
        self::$configs[$name] = [
            'dsn'      => $config['dsn'],
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'options'  => $config['options'] ?? [],
        ];
    }

    /**
     * Return an existing PDO connection or create a new persistent one.
     */
    public function getConnection(string $name = 'default'): PDO
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        if (!isset(self::$configs[$name])) {
            throw new \RuntimeException("DatabasePool: no configuration registered for connection '{$name}'.");
        }

        $cfg = self::$configs[$name];

        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => true,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Caller-supplied options override defaults
        $options = array_replace($defaultOptions, $cfg['options']);

        self::$connections[$name] = new PDO(
            $cfg['dsn'],
            $cfg['username'],
            $cfg['password'],
            $options
        );

        return self::$connections[$name];
    }

    /**
     * Close all managed connections.
     */
    public static function closeAll(): void
    {
        foreach (array_keys(self::$connections) as $name) {
            self::$connections[$name] = null;
        }
        self::$connections = [];
        self::$instances   = [];
    }
}
