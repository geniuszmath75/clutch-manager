<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Session
{
    private static bool $started = false;

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

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    }
}