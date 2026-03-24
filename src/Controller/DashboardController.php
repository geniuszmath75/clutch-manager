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
    public function showDashboardView(array $params): void
    {
        Auth::requireLogin();

        Response::view('dashboard.html');
    }
}