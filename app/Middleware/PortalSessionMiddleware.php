<?php

/**
 * PortalSessionMiddleware — session management for the three separate portals.
 *
 * Handles authentication and session timeout enforcement for:
 *  - Bandwidth Reseller Portal  (session key: reseller_id,        timeout: 30 min)
 *  - MAC Reseller Portal        (session key: mac_reseller_id,    timeout: 30 min)
 *  - Employee Portal            (session key: employee_portal_id, timeout: 30 min)
 *
 * Each portal uses its own session namespace to prevent cross-portal access.
 * Session activity timestamps are tracked per-portal so that a timeout in one
 * portal does not affect sessions in another.
 *
 * Usage (in portal controllers or router):
 *   (new PortalSessionMiddleware('reseller'))->handle();
 *   (new PortalSessionMiddleware('mac_reseller'))->handle();
 *   (new PortalSessionMiddleware('employee'))->handle();
 */
class PortalSessionMiddleware
{
    /**
     * Session inactivity timeout in seconds (30 minutes).
     */
    private const TIMEOUT_SECONDS = 1800;

    /**
     * Portal configuration: session key, last-activity key, and login route.
     *
     * @var array<string, array{session_key: string, activity_key: string, login_route: string, permission: string}>
     */
    private const PORTAL_CONFIG = [
        'reseller' => [
            'session_key'  => 'reseller_id',
            'activity_key' => 'reseller_last_activity',
            'login_route'  => 'reseller/login',
            'permission'   => 'reseller_portal.view',
        ],
        'mac_reseller' => [
            'session_key'  => 'mac_reseller_id',
            'activity_key' => 'mac_reseller_last_activity',
            'login_route'  => 'mac-reseller/login',
            'permission'   => 'mac_reseller.view',
        ],
        'employee' => [
            'session_key'  => 'employee_portal_id',
            'activity_key' => 'employee_portal_last_activity',
            'login_route'  => 'employee-portal/login',
            'permission'   => 'employee_portal.view',
        ],
    ];

    /**
     * The portal type this middleware instance is configured for.
     *
     * @var string
     */
    private string $portalType;

    /**
     * @param  string $portalType  One of: 'reseller', 'mac_reseller', 'employee'
     * @throws \InvalidArgumentException if an unknown portal type is given
     */
    public function __construct(string $portalType)
    {
        if (!array_key_exists($portalType, self::PORTAL_CONFIG)) {
            throw new \InvalidArgumentException(
                "Unknown portal type '{$portalType}'. Valid types: "
                . implode(', ', array_keys(self::PORTAL_CONFIG))
            );
        }

        $this->portalType = $portalType;
    }

    /**
     * Enforce portal authentication and session timeout.
     *
     * 1. Checks that the portal-specific session key is set.
     * 2. Enforces the 30-minute inactivity timeout.
     * 3. Updates the last-activity timestamp on every valid request.
     *
     * Redirects to the portal login page on failure.
     *
     * @return void
     */
    public function handle(): void
    {
        $config     = self::PORTAL_CONFIG[$this->portalType];
        $sessionKey = $config['session_key'];
        $activityKey = $config['activity_key'];
        $loginRoute  = $config['login_route'];

        // Check portal session exists
        if (!isset($_SESSION[$sessionKey])) {
            $this->redirectToLogin($loginRoute);
        }

        // Enforce inactivity timeout
        if (isset($_SESSION[$activityKey])) {
            $elapsed = time() - (int)$_SESSION[$activityKey];

            if ($elapsed > self::TIMEOUT_SECONDS) {
                $this->expireSession($sessionKey, $activityKey, $loginRoute);
            }
        }

        // Refresh last-activity timestamp
        $_SESSION[$activityKey] = time();
    }

    /**
     * Return the portal type this middleware is configured for.
     *
     * @return string
     */
    public function getPortalType(): string
    {
        return $this->portalType;
    }

    /**
     * Return the session key for the current portal.
     *
     * @return string
     */
    public function getSessionKey(): string
    {
        return self::PORTAL_CONFIG[$this->portalType]['session_key'];
    }

    /**
     * Return the authenticated portal user's ID from the session.
     *
     * @return int|null  Portal user ID, or null if not authenticated
     */
    public function getPortalUserId(): ?int
    {
        $sessionKey = self::PORTAL_CONFIG[$this->portalType]['session_key'];
        $value = $_SESSION[$sessionKey] ?? null;

        return $value !== null ? (int)$value : null;
    }

    /**
     * Initialise a portal session after successful login.
     *
     * Sets the portal session key and records the initial activity timestamp.
     * Call this from the portal's login controller after verifying credentials.
     *
     * @param  int $portalUserId  The authenticated portal user's ID
     * @return void
     */
    public function startSession(int $portalUserId): void
    {
        $config = self::PORTAL_CONFIG[$this->portalType];

        $_SESSION[$config['session_key']]  = $portalUserId;
        $_SESSION[$config['activity_key']] = time();
    }

    /**
     * Destroy the portal session and redirect to the login page.
     *
     * Only clears the keys belonging to this portal — other portal sessions
     * and the main admin session are left intact.
     *
     * @return void
     */
    public function destroySession(): void
    {
        $config = self::PORTAL_CONFIG[$this->portalType];

        unset(
            $_SESSION[$config['session_key']],
            $_SESSION[$config['activity_key']]
        );

        $this->redirectToLogin($config['login_route']);
    }

    /**
     * Return the remaining session lifetime in seconds.
     *
     * Returns 0 if the session has already expired or was never started.
     *
     * @return int  Seconds remaining before timeout
     */
    public function getRemainingTime(): int
    {
        $activityKey = self::PORTAL_CONFIG[$this->portalType]['activity_key'];

        if (!isset($_SESSION[$activityKey])) {
            return 0;
        }

        $elapsed   = time() - (int)$_SESSION[$activityKey];
        $remaining = self::TIMEOUT_SECONDS - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Check whether the portal session is currently valid (not expired).
     *
     * Does NOT redirect — use `handle()` for enforcement.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        $config = self::PORTAL_CONFIG[$this->portalType];

        if (!isset($_SESSION[$config['session_key']])) {
            return false;
        }

        if (isset($_SESSION[$config['activity_key']])) {
            $elapsed = time() - (int)$_SESSION[$config['activity_key']];
            if ($elapsed > self::TIMEOUT_SECONDS) {
                return false;
            }
        }

        return true;
    }

    // ── Private helpers ───────────────────────────────────────────

    /**
     * Expire the portal session and redirect to login.
     *
     * @param  string $sessionKey   Session key to unset
     * @param  string $activityKey  Activity timestamp key to unset
     * @param  string $loginRoute   Login route to redirect to
     * @return never
     */
    private function expireSession(
        string $sessionKey,
        string $activityKey,
        string $loginRoute
    ): never {
        unset($_SESSION[$sessionKey], $_SESSION[$activityKey]);

        // Store a flash message so the login page can show a timeout notice
        $_SESSION['portal_timeout_message'] = 'Your session has expired due to inactivity. Please log in again.';

        $this->redirectToLogin($loginRoute);
    }

    /**
     * Redirect to the portal login page and terminate.
     *
     * @param  string $loginRoute  Route path (without leading slash)
     * @return never
     */
    private function redirectToLogin(string $loginRoute): never
    {
        if (function_exists('base_url')) {
            header('Location: ' . base_url($loginRoute));
        } else {
            header('Location: /' . $loginRoute);
        }
        exit;
    }
}
