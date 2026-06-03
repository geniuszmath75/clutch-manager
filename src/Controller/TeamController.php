<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Core\ServiceContainer;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;

final class TeamController extends BaseController
{
    public function __construct()
    {
    }

    /**
     * GET /teams
     */
    public function getTeams(): void
    {
        Auth::requireRole([SystemRole::Admin->value]);
        $teams = ServiceContainer::getTeamService()->getAll();

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
            $team = ServiceContainer::getTeamService()->createTeam($data);

            Response::json([
                'success' => true,
                'data' => $team
            ], 201);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }
}