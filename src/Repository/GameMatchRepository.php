<?php

declare(strict_types=1);

namespace Src\Repository;

use Core\Database;
use PDO;
use PDOStatement;
use Src\Model\GameMatch;
use Src\Model\PlayerMatchStats;

final class GameMatchRepository
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
     * Returns a paginated list of matches joined with map + game_mode + team names.
     *
     * Supported filters:
     *   team_id   (int)    - filter by team
     *   map_ident (string) - filter by map
     *   result    (string) - 'WIN' | 'LOSS' | 'DRAW' (derived, filtered in SQL)
     */
    public function findAll(array $filters = [], int $page = 1, int $pageSize = 5): array
    {
        [$conditions, $params] = $this->buildConditions($filters);

        $stmt = $this->pdo->prepare("
            SELECT
                gmatch.id,
                gmatch.team_id,
                t.name AS team_name,
                gmatch.opponent_name,
                gmatch.team_score,
                gmatch.opponent_score,
                gmatch.map_id,
                gmap.ident  AS map_ident,
                gmatch.game_mode_id,
                gmode.ident  AS game_mode_ident,
                gmatch.duration,
                gmatch.played_at
            FROM game_matches gmatch
            JOIN game_maps gmap ON gmatch.map_id = gmap.id
            JOIN game_modes gmode ON gmatch.game_mode_id = gmode.id
            LEFT JOIN teams t ON gmatch.team_id = t.id
            WHERE 
                gmatch.deleted_at IS NULL
                " . $conditions . "
            ORDER BY gmatch.played_at DESC
            LIMIT :pageSize OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $offset = ($page - 1) * $pageSize;
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return array_map(
            fn(array $row) => GameMatch::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Returns the total number of matches (for pagination).
     */
    public function countAll(array $filters = []): int
    {
        [$conditions, $params] = $this->buildConditions($filters);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(gmatch.id)
            FROM game_matches gmatch
            JOIN game_maps gmap ON gmatch.map_id = gmap.id
            JOIN game_modes gmode ON gmatch.game_mode_id = gmode.id
            WHERE gmatch.deleted_at IS NULL
            " . $conditions . "
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Fetch a single match by ID (with all joins). Returns null if not found or soft-deleted.
     */
    public function findById(int $id): ?GameMatch
    {
        $stmt = $this->pdo->prepare("
            SELECT
                gmatch.id,
                gmatch.team_id,
                t.name AS team_name,
                gmatch.opponent_name,
                gmatch.team_score,
                gmatch.opponent_score,
                gmatch.map_id,
                gmap.ident      AS map_ident,
                gmatch.game_mode_id,
                gmode.ident     AS game_mode_ident,
                gmatch.duration,
                gmatch.played_at
            FROM game_matches gmatch
            JOIN game_maps  gmap  ON gmatch.map_id       = gmap.id
            JOIN game_modes gmode ON gmatch.game_mode_id = gmode.id
            LEFT JOIN teams t ON gmatch.team_id = t.id
            WHERE gmatch.id = :id
              AND gmatch.deleted_at IS NULL
        ");

        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? GameMatch::fromRow($row) : null;
    }

    /**
     * Returns all player_match_stats rows for a given match, ordered by kills desc.
     *
     * @return PlayerMatchStats[]
     */
    public function findStatsByMatchId(int $matchId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                pms.id,
                pms.match_id,
                pms.player_id,
                u.nickname          AS player_nickname,
                pms.kills_number,
                pms.deaths_number,
                pms.assists_number,
                pms.flash_assists_number,
                pms.total_damage,
                pms.hs_percent,
                pms.rkast_number
            FROM player_match_stats pms
            JOIN users u ON u.id = pms.player_id
            WHERE pms.match_id = :matchId
              AND pms.deleted_at IS NULL
            ORDER BY pms.kills_number DESC,
                     pms.deaths_number ASC
        ");

        $stmt->execute([':matchId' => $matchId]);

        return array_map(
            static fn(array $row) => PlayerMatchStats::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * WRITE
     */

    /**
     * Insert a new match and its player stats atomically.
     *
     * @param array $matchData Keys: team_id, opponent_name, team_score,
     *                          opponent_score, map_id, game_mode_id,
     *                          duration, played_at
     * @param array $statsRows Each row: player_id, kills_number, deaths_number,
     *                          assists_number, flash_assists_number,
     *                          total_damage, hs_percent, rkast_number
     */
    public function create(array $matchData, array $statsRows): GameMatch
    {
        return Database::getInstance()->transaction(function () use ($matchData, $statsRows): GameMatch {
            $stmt = $this->pdo->prepare("
                INSERT INTO game_matches
                    (team_id, opponent_name, team_score, opponent_score,
                     map_id, game_mode_id, duration, played_at, updated_by_user_id)
                VALUES
                    (:team_id, :opponent_name, :team_score, :opponent_score,
                     :map_id, :game_mode_id, :duration, :played_at, :updated_by_user_id)
                RETURNING id");

            $stmt->execute([
                ':team_id' => $matchData['team_id'],
                ':opponent_name' => $matchData['opponent_name'],
                ':team_score' => $matchData['team_score'],
                ':opponent_score' => $matchData['opponent_score'],
                ':map_id' => $matchData['map_id'],
                ':game_mode_id' => $matchData['game_mode_id'],
                ':duration' => $matchData['duration'],
                ':played_at' => $matchData['played_at'],
                ':updated_by_user_id' => $matchData['updated_by_user_id'],
            ]);

            $matchId = (int)$stmt->fetchColumn();

            $this->insertPlayerStats($matchId, $statsRows);

            return $this->findById($matchId);
        });
    }

    /**
     * Update match info and replace all player stats atomically.
     */
    public function update(int $id, array $matchData, array $statsRows): GameMatch
    {
        return Database::getInstance()->transaction(function () use ($id, $matchData, $statsRows): GameMatch {
            $stmtOne = $this->pdo->prepare("
                UPDATE game_matches SET
                    opponent_name  = :opponent_name,
                    team_score     = :team_score,
                    opponent_score = :opponent_score,
                    map_id         = :map_id,
                    game_mode_id   = :game_mode_id,
                    duration       = :duration,
                    played_at      = :played_at,
                    updated_at     = NOW(),
                    updated_by_user_id = :updated_by_user_id
                WHERE id = :id
                  AND deleted_at IS NULL
            ");

            $stmtOne->execute([
                ':opponent_name' => $matchData['opponent_name'],
                ':team_score' => $matchData['team_score'],
                ':opponent_score' => $matchData['opponent_score'],
                ':map_id' => $matchData['map_id'],
                ':game_mode_id' => $matchData['game_mode_id'],
                ':duration' => $matchData['duration'],
                ':played_at' => $matchData['played_at'],
                ':id' => $id,
                ':updated_by_user_id' => $matchData['updated_by_user_id'],
            ]);

            $this->updatePlayerStats($id, $statsRows);

            return $this->findById($id);
        });
    }

    /**
     * Soft-delete a match (cascades not needed; stats are scoped via match_id + deleted_at).
     */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE game_matches
            SET deleted_at = NOW(),
                updated_at = NOW(),
                updated_by_user_id = :updated_by_user_id
            WHERE id = :id
              AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id, 'updated_by_user_id' => $userId]);

        return $stmt->rowCount() === 1;
    }

    /**
     * HELPERS
     */

    /**
     * Builds a WHERE fragment and a parameter array based on filters.
     * Filter keys come exclusively from trusted service code—not from user input.
     *
     * @param array{team_id?: int, map_ident?: string, result?: string} $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildConditions(array $filters): array
    {
        $conditions = '';
        $params = [];

        if (isset($filters['team_id'])) {
            $conditions .= ' AND gmatch.team_id = :team_id';
            $params[':team_id'] = (int)$filters['team_id'];
        }

        if (isset($filters['map_ident']) && $filters['map_ident'] !== '') {
            $conditions .= ' AND gmap.ident = :map_ident';
            $params[':map_ident'] = strtoupper($filters['map_ident']);
        }

        if (isset($filters['result']) && in_array($filters['result'], ['WIN', 'LOSS', 'DRAW'], true)) {
            $conditions .= match ($filters['result']) {
                'WIN' => ' AND gmatch.team_score > gmatch.opponent_score',
                'LOSS' => ' AND gmatch.team_score < gmatch.opponent_score',
                'DRAW' => ' AND gmatch.team_score = gmatch.opponent_score',
            };
        }

        return [$conditions, $params];
    }

    /**
     * Bulk-insert player stats rows for a given match.
     */
    private function insertPlayerStats(int $matchId, array $statsRows): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO player_match_stats
                (match_id, player_id, kills_number, deaths_number, assists_number,
                 flash_assists_number, total_damage, hs_percent, rkast_number)
            VALUES
                (:match_id, :player_id, :kills_number, :deaths_number, :assists_number,
                 :flash_assists_number, :total_damage, :hs_percent, :rkast_number)
        ");

        $this->executePlayerStatsStatement($stmt, $matchId, $statsRows);
    }

    /**
     * Bulk-update player stats rows for a given match.
     */
    private function updatePlayerStats(int $matchId, array $statsRows): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE player_match_stats
            SET kills_number = :kills_number,
                deaths_number = :deaths_number,
                assists_number = :assists_number,
                flash_assists_number = :flash_assists_number,
                total_damage = :total_damage,
                hs_percent = :hs_percent,
                rkast_number = :rkast_number,
                updated_at = NOW()
            WHERE match_id = :match_id
                AND player_id = :player_id
                AND deleted_at IS NULL
        ");

        $this->executePlayerStatsStatement($stmt, $matchId, $statsRows);
    }

    private function executePlayerStatsStatement(PDOStatement $stmt, int $matchId, array $statsRows): void
    {
        if (empty($statsRows)) {
            return;
        }

        foreach ($statsRows as $row) {
            $stmt->execute([
                ':match_id' => $matchId,
                ':player_id' => (int)$row['player_id'],
                ':kills_number' => (int)$row['kills_number'],
                ':deaths_number' => (int)$row['deaths_number'],
                ':assists_number' => (int)$row['assists_number'],
                ':flash_assists_number' => (int)$row['flash_assists_number'],
                ':total_damage' => (int)$row['total_damage'],
                ':hs_percent' => (float)$row['hs_percent'],
                ':rkast_number' => (int)$row['rkast_number'],
            ]);
        }
    }
}