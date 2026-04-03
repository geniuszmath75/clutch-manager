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
     * Returns the logged-in user's session data or null.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return Session::get('user');
    }

    /**
     * Returns the system role of the logged-in user, or null.
     */
    public static function systemRole(): ?string
    {
        $user = self::user();
        return $user['system_role'] ?? null;
    }

    /**
     * Checks if the logged-in user has the specified role.
     *
     * @param string|string[] $roles A single role or an array of roles
     */
    public static function hasRole(string|array $roles): bool
    {
        $userRole = self::systemRole();

        if (empty($userRole)) {
            return false;
        }

        $allowed = is_array($roles) ? $roles : [$roles];
        return in_array($userRole, $allowed, strict: true);
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

        Response::redirect('/auth/login');
    }

    /**
     * Requires a specific role - returns 403 if permissions are denied.
     *
     * @param string|string[] $roles
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();

        if (!self::hasRole($roles)) {
            if (self::isAjaxRequest()) {
                Response::forbidden();
            }

            Response::redirect('/dashboard');
        }
    }

    /**
     * Checks if the request is from the Fetch API (AJAX).
     * Used to distinguish between redirects and JSON 401/403 requests.
     */
    public static function isAjaxRequest(): bool
    {
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        return strtolower($requestedWith) === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }
}