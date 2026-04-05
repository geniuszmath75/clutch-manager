<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Src\Enum\SystemRole;

final class DashboardController
{
    /**
     * GET /
     * GET /dashboard
     */
    public function showDashboardView(): void
    {
        Auth::requireLogin();

        Response::view('dashboard.html');
    }

    /**
     * GET /dashboard/players
     */
    public function showPlayersView(): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value, SystemRole::Player->value]);

        Response::view('players.php');
    }
}