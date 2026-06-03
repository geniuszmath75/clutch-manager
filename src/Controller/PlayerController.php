<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Core\ServiceContainer;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;

final class PlayerController extends BaseController
{
    public function __construct()
    {
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
            }

            $roleFilter = isset($_GET['role']) ? strtoupper(trim($_GET['role'])) : null;
            $statusFilter = $_GET['is_active'] ?? null;
            $teamIdFilter = $_GET['team_id'] ?? null;

            if (!empty($roleFilter)) {
                $filters['team_role_ident'] = $roleFilter;
            }
            if (!empty($statusFilter)) {
                $filters['is_active'] = $statusFilter;
            }
            if (!empty($teamIdFilter)) {
                $filters['team_id'] = $teamIdFilter;
            }

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, intval($_GET['pageSize'] ?? 5));

            $result = ServiceContainer::getPlayerService()->getAll($filters, $page, $pageSize);

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
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    public function getAvailablePlayers(): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);

        try {
            $players = ServiceContainer::getPlayerService()->getAvailable();

            Response::json([
                'success' => true,
                'data' => $players,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
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

        // Whitelist fields - only those that can be changed via the API
        $allowed = ['nickname', 'team_role_ident'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        try {
            $updated = ServiceContainer::getPlayerService()->update($id, $filtered);

            Response::json([
                'success' => true,
                'message' => 'Player updated successfully.',
                'data' => $updated,
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
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
            ServiceContainer::getPlayerService()->deactivate($id);

            Response::json([
                'success' => true,
                'message' => 'Player deactivated successfully.'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
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
            ServiceContainer::getPlayerService()->activate($id);

            Response::json([
                'success' => true,
                'message' => 'Player activated successfully.'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * DELETE /players/{id}
     */
    public function deletePlayer(string $id): void
    {
        Auth::requireRole(SystemRole::Admin->value);

        try {
            ServiceContainer::getPlayerService()->delete($id);
            Response::json([
                'success' => true,
                'message' => 'Player deleted successfully.'
            ]);
        } catch (RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * POST /players/{id}/team
     */
    public function addPlayerToTeam(string $id): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);
        $id = intval($id);

        $data = $this->parseJsonBody();

        if (!isset($data['team_id'])) {
            $this->handleError(400, 'Missing required field: team_id');
            return;
        }

        $teamId = intval($data['team_id']);

        if ($teamId <= 0) {
            $this->handleError(400, 'Invalid team_id');
            return;
        }

        try {
            ServiceContainer::getPlayerService()->assignToTeam($id, $teamId, Auth::systemRole(), Auth::teamId());

            Response::json([
                'success' => true,
                'message' => 'Player assigned to team successfully.'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * DELETE /players/{id}/team
     */
    public function removePlayerFromTeam(string $id): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);

        $id = intval($id);

        try {
            ServiceContainer::getPlayerService()->removeFromTeam($id, Auth::systemRole(), Auth::teamId());

            Response::json([
                'success' => true,
                'message' => 'Player removed from team successfully.'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }
}