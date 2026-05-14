<?php

declare(strict_types=1);

namespace Src\Service;

use Core\Auth;
use RuntimeException;
use Src\Enum\SystemRole;
use Src\Repository\DashboardRepository;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository,
    ) {}

    /**
     * Returns dashboard stats for PLAYER or COACH.
     * ADMIN uses getAdminTeamStats() / getAdminAuditLog() via dedicated endpoints.
     *
     * @return array{role: string, stats: array}
     */
    public function getStats(): array
    {
        Auth::requireRole([SystemRole::Coach->value, SystemRole::Player->value]);
        $role = Auth::systemRole();

        return match ($role) {
            SystemRole::Player->value => $this->buildPlayerPayload(),
            SystemRole::Coach->value  => $this->buildCoachPayload(),
            default => throw new RuntimeException('Invalid system role', 500),
        };
    }

    /**
     * Returns paginated team stats for ADMIN from v_admin_dashboard.
     *
     * @return array{teams: array, total: int, page: int, pageSize: int, totalPages: int}
     */
    public function getAdminTeamStats(int $page = 1, int $pageSize = 5): array
    {
        Auth::requireRole([SystemRole::Admin->value]);

        $page = max(1, $page);
        $pageSize = max(1, min(50, $pageSize));

        $teams = $this->dashboardRepository->getAdminTeamStats($page, $pageSize);
        $total = $this->dashboardRepository->countAdminTeamStats();
        $totalPages = (int)ceil($total / $pageSize);

        return [
            'teams'      => $teams,
            'total'      => $total,
            'page'       => $page,
            'pageSize'   => $pageSize,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Returns paginated audit log entries for ADMIN from v_admin_audit_log.
     *
     * @return array{entries: array, total: int, page: int, pageSize: int, totalPages: int}
     */
    public function getAdminAuditLog(int $page = 1, int $pageSize = 5): array
    {
        Auth::requireRole([SystemRole::Admin->value]);

        $page = max(1, $page);
        $pageSize = max(1, min(50, $pageSize));

        $entries = $this->dashboardRepository->getAdminAuditLog($page, $pageSize);
        $total   = $this->dashboardRepository->countAdminAuditLog();
        $totalPages = (int)ceil($total / $pageSize);

        return [
            'entries'    => $entries,
            'total'      => $total,
            'page'       => $page,
            'pageSize'   => $pageSize,
            'totalPages' => $totalPages,
        ];
    }


    // -------------------------------------------------------------------------

    private function buildPlayerPayload(): array
    {
        $userId = Auth::userId();

        if ($userId === null) {
            throw new RuntimeException('Session user id missing.', 500);
        }

        $stats = $this->dashboardRepository->getPlayerStats($userId);

        return [
            'role'  => SystemRole::Player->value,
            'stats' => $stats ?? [],
        ];
    }

    private function buildCoachPayload(): array
    {
        $teamId = Auth::teamId();

        if ($teamId === null) {
            throw new RuntimeException('Session team id missing.', 500);
        }

        return [
            'role'  => SystemRole::Coach->value,
            'stats' => $this->dashboardRepository->getCoachStats($teamId),
        ];
    }
}