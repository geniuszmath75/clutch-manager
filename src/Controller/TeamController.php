<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Src\Enum\SystemRole;
use Src\Repository\TeamRepository;
use Src\Service\TeamService;

final class TeamController
{
    private TeamService $teamService;

    public function __construct()
    {
        $teamRepository = new TeamRepository();
        $this->teamService = new TeamService($teamRepository);
    }

    /**
     * GET /teams
     */
    public function getTeams(): void
    {
        Auth::requireRole([SystemRole::Admin->value]);
        $teams = $this->teamService->getAll();

        Response::json([
            'success' => true,
            'data' => $teams
        ]);
    }
}