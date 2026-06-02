<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;
use Src\Repository\TeamRepository;
use Src\Repository\UserRepository;
use Src\Service\TeamService;

final class TeamController extends BaseController
{
    private TeamService $teamService;

    public function __construct()
    {
        $teamRepository = new TeamRepository();
        $userRepository = new UserRepository();
        $this->teamService = new TeamService($teamRepository, $userRepository);
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

    /**
     * POST /teams
     */
    public function createTeam(): void
    {
        Auth::requireLogin();

        try {
            $data = $this->parseJsonBody();
            $team = $this->teamService->createTeam($data);

            Response::json([
                'success' => true,
                'data' => $team
            ], 201);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }
}