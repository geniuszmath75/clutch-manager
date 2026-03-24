<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Session
{
    private static bool $started = false;

    /**
     * Starts a session with secure cookie options.
     * Idempotent - multiple calls are safe.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0, // session cookie (deleted when you close the browser)
            'path' => '/',
            'domain' => '',
            'secure' => self::isHttps(),
            'httponly' => true, // no access via JS
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;
    }

    /**
     * Regenerates the session ID - call after every login.
     * Deletes the old session on the server side.
     */
    public static function regenerate(): void
    {
        self::assertStarted();
        session_regenerate_id(true);
    }

    public static function set(string $key, mixed $value): void
    {
        self::assertStarted();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::assertStarted();
        return $_SESSION[$key] ?? $default;
    }

    public static function destroy(): void
    {
        self::assertStarted();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Lax'
                ]
            );
        }

        session_destroy();
        self::$started = false;
    }

    private static function assertStarted(): void
    {
        if (!self::$started && session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session has not been started. Call Session::start() first.');
        }
    }
    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    }
}