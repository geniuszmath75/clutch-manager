<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Src\Enum\SystemRole;
use Src\Repository\GameMapRepository;
use Src\Repository\GameModeRepository;
use Src\Repository\TeamRepository;
use Src\Service\TeamService;

final class DashboardController
{
    private TeamService $teamService;
    private GameMapRepository $mapRepository;
    private GameModeRepository $gameModeRepository;

    public function __construct()
    {
        $teamRepository = new TeamRepository();
        $this->teamService = new TeamService($teamRepository);
        $this->mapRepository = new GameMapRepository();
        $this->gameModeRepository = new GameModeRepository();
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
        Auth::requireLogin();

        $teams = [];
        $sessionRole = Auth::systemRole();

        if ($sessionRole === SystemRole::Admin->value) {
            $teams = $this->teamService->getAll();
        }

        Response::view('players.php', [
            'teams' => $teams
        ]);
    }

    /**
     * GET /dashboard/matches
     */
    public function showMatchesView(): void
    {
        Auth::requireLogin();

        $teams = [];
        $maps = $this->mapRepository->findAll();
        $gameModes = $this->gameModeRepository->findAll();
        $sessionRole = Auth::systemRole();

        if ($sessionRole === SystemRole::Admin->value) {
            $teams = $this->teamService->getAll();
        }

        Response::view('matches.php', [
            'teams' => $teams,
            'maps' => $maps,
            'gameModes' => $gameModes
        ]);
    }

    /**
     * GET /dashboard/matches/{id}
     */
    public function showMatchDetailsView(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        Response::view("match-details.php", [
            'matchId' => $id
        ]);
    }
}