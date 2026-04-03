<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;

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
        Auth::requireLogin();

        Response::view('players.html');
    }
}