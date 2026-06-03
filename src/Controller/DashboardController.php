<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Core\ServiceContainer;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;

final class DashboardController extends BaseController
{
    public function __construct()
    {
    }

    /**
     * GET /
     */
    public function showRedirectedDashboardView(): void
    {
        Auth::requireLogin();
        Response::redirect('/dashboard');
    }

    /**
     * GET /
     * GET /dashboard
     */
    public function showDashboardView(): void
    {
        Auth::requireLogin();

        $systemRole = Auth::systemRole();

        $viewPath = match ($systemRole) {
            SystemRole::Admin->value => 'dashboard-admin.php',
            default => 'dashboard-user.php',
        };

        Response::view($viewPath);
    }

    /**
     * GET /dashboard/stats
     */
    public function getDashboardStats(): void
    {
        Auth::requireLogin();

        try {
            $stats = ServiceContainer::getDashboardService()->getStats();

            Response::json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * GET /dashboard/admin/teams?page=1&pageSize=5
     */
    public function getAdminTeamStats(): void
    {
        Auth::requireLogin();

        try {
            $page = max(1, intval($_GET['page']) ?? 1);
            $pageSize = max(1, min(50, intval($_GET['pageSize']) ?? 5));

            $result = ServiceContainer::getDashboardService()->getAdminTeamStats($page, $pageSize);

            Response::json([
                'success' => true,
                'data'    => $result['teams'],
                'meta'    => [
                    'total'      => $result['total'],
                    'page'       => $result['page'],
                    'pageSize'   => $result['pageSize'],
                    'totalPages' => $result['totalPages'],
                ],
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * GET /dashboard/admin/logs?page=1&pageSize=10
     */
    public function getAdminAuditLog(): void
    {
        Auth::requireLogin();

        try {
            $page     = max(1, intval($_GET['page']) ?? 1);
            $pageSize = max(1, min(50, intval($_GET['pageSize']) ?? 10));

            $result = ServiceContainer::getDashboardService()->getAdminAuditLog($page, $pageSize);

            Response::json([
                'success' => true,
                'data'    => $result['entries'],
                'meta'    => [
                    'total'      => $result['total'],
                    'page'       => $result['page'],
                    'pageSize'   => $result['pageSize'],
                    'totalPages' => $result['totalPages'],
                ],
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
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
            $teams = ServiceContainer::getTeamService()->getAll();
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
        $maps = ServiceContainer::getGameMapService()->getAll();
        $gameModes = ServiceContainer::getGameModeService()->getAll();
        $sessionRole = Auth::systemRole();

        if ($sessionRole === SystemRole::Admin->value) {
            $teams = ServiceContainer::getTeamService()->getAll();
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

    /**
     * GET /dashboard/strategies
     */
    public function showStrategiesView(): void
    {
        Auth::requireLogin();

        $maps = ServiceContainer::getGameMapService()->getAll();
        $strategyTypes = ServiceContainer::getStrategyTypeService()->getAll();

        Response::view('strategies.php', [
            'maps' => $maps,
            'strategyTypes' => $strategyTypes
        ]);
    }

    /**
     * GET /dashboard/strategies/{id}
     */
    public function showStrategyDetailsView(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        Response::view("strategy-details.php", [
            'strategyId' => $id
        ]);
    }

    /**
     * GET /dashboard/settings
     */
    public function showSettingsView(): void
    {
        Auth::requireLogin();

        $systemRole = Auth::systemRole();
        $teamRoles = [];

        if ($systemRole === SystemRole::Player->value) {
            $teamRoles = ServiceContainer::getTeamRoleRepository()->findAll();
        }

        Response::view("user-settings.php", [
            'teamRoles' => $teamRoles
        ]);
    }
}