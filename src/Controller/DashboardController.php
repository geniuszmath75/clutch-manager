<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;
use Src\Repository\DashboardRepository;
use Src\Repository\GameMapRepository;
use Src\Repository\GameModeRepository;
use Src\Repository\StrategyTypeRepository;
use Src\Repository\TeamRepository;
use Src\Repository\TeamRoleRepository;
use Src\Repository\UserRepository;
use Src\Service\DashboardService;
use Src\Service\TeamService;

final class DashboardController extends BaseController
{
    private TeamService $teamService;
    private GameMapRepository $mapRepository;
    private GameModeRepository $gameModeRepository;

    private StrategyTypeRepository $strategyTypeRepository;
    private TeamRoleRepository $teamRoleRepository;
    private DashboardService $dashboardService;

    public function __construct()
    {
        $teamRepository = new TeamRepository();
        $dashboardRepository = new DashboardRepository();
        $userRepository = new UserRepository();

        $this->teamService = new TeamService($teamRepository, $userRepository);
        $this->mapRepository = new GameMapRepository();
        $this->gameModeRepository = new GameModeRepository();
        $this->strategyTypeRepository = new StrategyTypeRepository();
        $this->dashboardService = new DashboardService($dashboardRepository);
        $this->teamRoleRepository = new TeamRoleRepository();
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
            $stats = $this->dashboardService->getStats();

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

            $result = $this->dashboardService->getAdminTeamStats($page, $pageSize);

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

            $result = $this->dashboardService->getAdminAuditLog($page, $pageSize);

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

    /**
     * GET /dashboard/strategies
     */
    public function showStrategiesView(): void
    {
        Auth::requireLogin();

        $maps = $this->mapRepository->findAll();
        $strategyTypes = $this->strategyTypeRepository->findAll();

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
            $teamRoles = $this->teamRoleRepository->findAll();
        }

        Response::view("user-settings.php", [
            'teamRoles' => $teamRoles
        ]);
    }
}