<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Src\Enum\SystemRole;
use Src\Repository\TeamRepository;
use Src\Service\TeamService;

final class DashboardController
{
    private TeamService $teamService;

    public function __construct()
    {
        $teamRepository = new TeamRepository();
        $this->teamService = new TeamService($teamRepository);
    }
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

        $teams = [];
        $sessionRole = Auth::systemRole();

        if ($sessionRole === SystemRole::Admin->value) {
            $teams = $this->teamService->getAll();
        }

        Response::view('players.php', [
            'teams' => $teams
        ]);
    }
}