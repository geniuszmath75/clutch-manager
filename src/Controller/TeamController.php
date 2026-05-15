<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use RuntimeException;
use JsonException;
use Src\Enum\SystemRole;
use Src\Repository\TeamRepository;
use Src\Repository\UserRepository;
use Src\Service\TeamService;

final class TeamController
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
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Parses the request body as JSON.
     * Supports Content-Type: application/json and application/x-www-form-urlencoded.
     */
    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid JSON payload.', 400);
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('Payload must be a JSON object.', 400);
        }

        return $data;
    }

    /**
     * Returns error as JSON (for AJAX) or redirect (for HTML).
     */
    private function handleError(int $code, string $message): void
    {
        Response::json([
            'success'      => false,
            'statusCode'   => $code,
            'errorMessage' => $message,
        ], $code);
    }
}