<?php

declare(strict_types=1);

namespace Core;

final class Auth
{
    /**
     * Returns true if the user is logged in (a valid session exists).
     */
    public static function isLoggedIn(): bool
    {
        $user = Session::get('user');
        return is_array($user) && !empty($user['id']);
    }

    /**
     * Requires login - redirects to /login or returns 401 for AJAX.
     */
    public static function requireLogin(): void
    {
        if (self::isLoggedIn()) {
            return;
        }

        if (self::isAjaxRequest()) {
            Response::unauthorized();
        }

        Response::redirect('/login');
    }

    /**
     * Checks if the request is from the Fetch API (AJAX).
     * Used to distinguish between redirects and JSON 401/403 requests.
     */
    private static function isAjaxRequest(): bool
    {
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        return strtolower($requestedWith) === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }
}