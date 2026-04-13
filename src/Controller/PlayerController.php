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
     * GET /players?page=1&pageSize=5&filters
     */
    public function getPlayers(): void
    {
        Auth::requireRole([SystemRole::Coach->value, SystemRole::Admin->value, SystemRole::Player->value]);

        $sessionRole = Auth::systemRole();

        $filters = [];

        try {
            if ($sessionRole === SystemRole::Player->value || $sessionRole === SystemRole::Coach->value) {
                $teamId = Auth::teamId();

                if ($teamId === null) {
                    Response::json(['success' => true, 'data' => [], 'meta' => []]);
                    return;
                }

                $filters['team_id'] = $teamId;
                $filters['is_active'] = true;
            }

            $roleFilter = isset($_GET['role']) ? strtoupper(trim($_GET['role'])) : null;
            $statusFilter = $_GET['is_active'] ?? null;

            if (!empty($roleFilter)) {
                $filters['team_role_ident'] = $roleFilter;
            }
            if (!empty($statusFilter)) {
                $filters['is_active'] = $statusFilter;
            }

            $page = max(1, intval($_GET['page']));
            $pageSize = min(50, intval($_GET['pageSize']));

            $result = $this->playerService->getAll($filters, $page, $pageSize);

            Response::json([
                'success' => true,
                'data' => $result['players'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'pageSize' => $result['pageSize'],
                    'totalPages' => $result['totalPages'],
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * PUT /players/{id}
     */

    public function updatePlayer(string $id): void
    {
        Auth::requireRole(SystemRole::Admin->value);
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
     * PATCH /players/{id}/deactivate
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
     * PATCH /players/{id}/activate
     */
    public function activatePlayer(string $id): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);
        $id = intval($id);

        try {
            $this->playerService->activate($id);

            Response::json([
                'success' => true,
                'message' => 'Player activated successfully.'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * DELETE /players/{id}
     */
    public function deletePlayer(string $id): void
    {
        Auth::requireRole(SystemRole::Admin->value);

        try {
            $this->playerService->delete($id);
            Response::json([
                'success' => true,
                'message' => 'Player deleted successfully.'
            ]);
        } catch (RuntimeException $e) {
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