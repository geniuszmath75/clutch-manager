<?php

declare(strict_types=1);

namespace Src\Repository;

use Core\Database;
use PDO;
use Src\Model\AdminDashboardView;
use Src\Model\AdminLogsView;

final class DashboardRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPDO();
    }

    /**
     * READ
     */

    /**
     * Returns aggregated stats for a single PLAYER from v_player_dashboard.
     * Filter is applied on player_id — value comes from session, never from user input.
     */
    public function getPlayerStats(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM v_player_dashboard
            WHERE player_id = :player_id
        ");

        $stmt->execute([':player_id' => $userId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Returns per-player stats for all players in a team from v_coach_dashboard.
     * Filter is applied on team_id — value comes from session, never from user input.
     */
    public function getCoachStats(int $teamId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM v_coach_dashboard
            WHERE team_id = :team_id
            ORDER BY player_kd DESC
        ");

        $stmt->execute([':team_id' => $teamId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns paginated aggregate stats per team from v_admin_dashboard.
     */
    public function getAdminTeamStats(int $page = 1, int $pageSize = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM v_admin_dashboard
            ORDER BY team_win_rate DESC
            LIMIT :pageSize OFFSET :offset
        ");

        $offset = ($page - 1) * $pageSize;
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn(array $row) => AdminDashboardView::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Returns total number of rows in v_admin_dashboard (one per team).
     */
    public function countAdminTeamStats(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(team_id) FROM v_admin_dashboard");

        return (int) $stmt->fetchColumn();
    }

    /**
     * Returns paginated audit log entries from v_admin_audit_log.
     */
    public function getAdminAuditLog(int $page, int $pageSize): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM v_admin_audit_log
            ORDER BY created_at DESC
            LIMIT :pageSize OFFSET :offset
        ");

        $offset = ($page - 1) * $pageSize;
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn (array $row) => AdminLogsView::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Returns total number of rows in v_admin_audit_log.
     */
    public function countAdminAuditLog(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(log_id) FROM v_admin_audit_log");

        return (int) $stmt->fetchColumn();
    }
}