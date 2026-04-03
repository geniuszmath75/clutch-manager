<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;
use Src\Repository\PlayerRepository;
use Src\Repository\SystemRoleRepository;
use Src\Repository\TeamRoleRepository;
use Src\Service\PlayerService;

final class PlayerController
{
    private PlayerService $playerService;

    public function __construct()
    {
        $playerRepository = new PlayerRepository();
        $teamRoleRepository = new TeamRoleRepository();
        $systemRoleRepository = new SystemRoleRepository();
        $this->playerService = new PlayerService(
            $playerRepository,
            $teamRoleRepository,
            $systemRoleRepository
        );
    }

    /**
     * GET /players
     */
    public function getPlayers(): void
    {
        Auth::requireLogin();

        $filterRole = isset($_GET['role']) ? strtoupper(trim($_GET['role'])) : null;

        try {
            $players = !empty($filterRole)
                ? $this->playerService->getByTeamRole($filterRole)
                : $this->playerService->getAll();

            if (Auth::isAjaxRequest()) {
                Response::json([
                    'success' => true,
                    'data' => $players
                ]);
                return;
            }

            // First page load
            Response::view("players.html");
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * PUT /players/{id}
     */

    public function updatePlayer(string $id): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);
        $id = intval($id);

        $data = $this->parseJsonBody();

        if ($data === null) {
            $this->handleError(400, 'Invalid data format (JSON expected)');
            return;
        }

        // Whitelist fields - only those that can be changed via the API
        $allowed = ['nickname', 'team_role_ident'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        try {
            $updated = $this->playerService->update($id, $filtered);

            Response::json([
                'success' => true,
                'message' => 'Player updated successfully.',
                'data' => $updated,
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * PATCH /player/{id}
     */
    public function deactivatePlayer(string $id): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);
        $id = intval($id);

        try {
            $this->playerService->deactivate($id);

            Response::json([
                'success' => true,
                'message' => 'Player deactivated successfully.'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Helpers
     */

    /**
     * Parses the request body as JSON.
     * Supports Content-Type: application/json and application/x-www-form-urlencoded.
     */
    private function parseJsonBody(): ?array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw === false || $raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        }

        return $_POST ?: [];
    }

    /**
     * Returns error as JSON (for AJAX) or redirect (for HTML).
     */
    private function handleError(int $code, string $message): void
    {
        if (Auth::isAjaxRequest()) {
            Response::error($code, $message);
            return;
        }

        Response::redirect('/players?error=' . urlencode($message));
    }
}