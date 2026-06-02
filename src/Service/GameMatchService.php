<?php

declare(strict_types=1);

namespace Src\Service;

use Core\Auth;
use http\Exception\RuntimeException;
use InvalidArgumentException;
use Src\Enum\SystemRole;
use Src\Model\GameMatch;
use Src\Repository\GameMatchRepository;
use Src\Repository\PlayerRepository;

final class GameMatchService
{
    public function __construct(
        private readonly GameMatchRepository $matchRepository,
        private readonly PlayerRepository    $playerRepository,
    )
    {
    }

    /**
     * Returns list of matches.
     *
     * PLAYER / COACH -> team_id forced from session.
     * ADMIN          -> optional team_id filter from raw params.
     */
    public function getAll(array $rawFilters = [], int $page = 1, int $pageSize = 5): array
    {
        $filters = $this->buildFilters($rawFilters);
        $page = max(1, $page);
        $pageSize = max(1, min(50, $pageSize));

        $matches = $this->matchRepository->findAll($filters, $page, $pageSize);
        $total = $this->matchRepository->countAll($filters);
        $totalPages = (int)ceil($total / $pageSize);

        return [
            'matches' => array_map(fn(GameMatch $gm) => $gm->toArray(), $matches),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Returns a single match with its player stats.
     * PLAYER / COACH scoped to own team.
     */
    public function getById(int $id): array
    {
        $match = $this->matchRepository->findById($id);

        if ($match === null) {
            throw new InvalidArgumentException('Match not found.', 404);
        }

        $this->assertTeamAccess($match->teamId);

        $stats = $this->matchRepository->findStatsByMatchId($id);

        return [
            'match' => $match->toArray(),
            'stats' => array_map(static fn($s) => $s->toArray(), $stats),
        ];
    }

    /**
     * Create a match + player stats.
     *
     * COACH  -> team_id locked to session; team must have >=5 active players.
     * ADMIN  -> team_id from payload; chosen team must have >=5 active players.
     */
    public function create(array $data): array
    {
        Auth::requireRole([SystemRole::Coach->value, SystemRole::Admin->value]);

        $teamId = $this->resolveTeamId($data);
        $this->assertTeamHasFullRoster($teamId);

        $matchData = $this->validateMatchData($data, $teamId);
        $statsRows = $this->validateStatsRows($data['stats'] ?? []);

        $match = $this->matchRepository->create($matchData, $statsRows);

        return $match->toArray();
    }

    /**
     * Update match info + replace all player stats.
     * team_id is immutable after creation.
     */
    public function update(int $id, array $data): array
    {
        Auth::requireRole([SystemRole::Coach->value, SystemRole::Admin->value]);

        $match = $this->matchRepository->findById($id);
        if ($match === null) {
            throw new InvalidArgumentException('Match not found.', 404);
        }

        $this->assertTeamAccess($match->teamId);

        $matchData = $this->validateMatchData($data, $match->teamId);
        $statsRows = $this->validateStatsRows($data['stats'] ?? []);

        $updated = $this->matchRepository->update($id, $matchData, $statsRows);

        return $updated->toArray();
    }

    /**
     * Permanently deletes a match - soft delete via deleted_at
     */
    public function delete(int $id): void
    {
        Auth::requireRole([SystemRole::Coach->value, SystemRole::Admin->value]);

        $match = $this->matchRepository->findById($id);
        if ($match === null) {
            throw new InvalidArgumentException('Match not found.', 404);
        }

        $this->assertTeamAccess($match->teamId);
        $userId = Auth::userId();

        $success = $this->matchRepository->delete($id, $userId);

        if (!$success) {
            throw new RuntimeException('Failed to delete match.', 500);
        }
    }

    /**
     * HELPERS
     */

    /**
     * Build repository-level filters from raw query params.
     * Non-admin roles have their team_id forced from session.
     */
    private function buildFilters(array $raw): array
    {
        $filters = [];

        $systemRole = Auth::systemRole();

        if ($systemRole !== SystemRole::Admin->value) {
            // PLAYER and COACH always scoped to own team
            $filters['team_id'] = Auth::teamId();
        } elseif (!empty($raw['team_id'])) {
            $filters['team_id'] = (int)$raw['team_id'];
        }

        if (!empty($raw['map_ident'])) {
            $filters['map_ident'] = (string)$raw['map_ident'];
        }

        if (!empty($raw['result']) && in_array($raw['result'], ['WIN', 'LOSS', 'DRAW'], true)) {
            $filters['result'] = $raw['result'];
        }

        return $filters;
    }

    /**
     * COACH and PLAYER may only access their own team's matches.
     */
    private function assertTeamAccess(int $matchTeamId): void
    {
        if (Auth::systemRole() === SystemRole::Admin->value) {
            return;
        }

        if (Auth::teamId() !== $matchTeamId) {
            throw new InvalidArgumentException('Access denied.', 403);
        }
    }

    /**
     * Resolve team_id for a new match:
     * - COACH -> always session team_id (payload ignored for security)
     * - ADMIN -> from payload, required
     */
    private function resolveTeamId(array $data): int
    {
        if (Auth::systemRole() !== SystemRole::Admin->value) {
            $teamId = Auth::teamId();
            if ($teamId === null) {
                throw new InvalidArgumentException('You are not assigned to a team.', 403);
            }
            return $teamId;
        }

        if (empty($data['team_id']) || !is_numeric($data['team_id'])) {
            throw new InvalidArgumentException('Field team_id is required.', 400);
        }

        return (int)$data['team_id'];
    }

    /**
     * Team must have exactly 5 active, non-deleted players before a match can be recorded.
     */
    private function assertTeamHasFullRoster(int $teamId): void
    {
        $count = $this->playerRepository->countAll(['team_id' => $teamId, 'is_active' => true]);

        if ($count < 5) {
            throw new InvalidArgumentException(
                "Team does not have a full roster ($count/5 active players). Add players before recording a match.",
                422
            );
        }
    }

    /**
     * Validate and sanitize match info payload.
     */
    private function validateMatchData(array $data, int $teamId): array
    {
        $required = ['opponent_name', 'team_score', 'opponent_score', 'map_id', 'game_mode_id', 'duration', 'played_at'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new InvalidArgumentException("Field '$field' is required.", 400);
            }
        }

        $opponentName = trim((string)$data['opponent_name']);
        $teamScore = (int)$data['team_score'];
        $opponentScore = (int)$data['opponent_score'];
        $mapId = (int)$data['map_id'];
        $gameModeId = (int)$data['game_mode_id'];
        $duration = (int)$data['duration'];
        $playedAt = (string)$data['played_at'];

        if ($opponentName === '' || strlen($opponentName) > 255) {
            throw new InvalidArgumentException("Field 'opponent_name' must be 1–255 characters.", 400);
        }
        if ($teamScore < 0 || $opponentScore < 0) {
            throw new InvalidArgumentException('Fields team_score and opponent_score must be non-negative.', 400);
        }
        if ($mapId <= 0 || $gameModeId <= 0) {
            throw new InvalidArgumentException('Invalid fields map_id or game_mode_id.', 400);
        }
        if ($duration <= 0) {
            throw new InvalidArgumentException("Field 'duration' must be positive.", 400);
        }
        if (!strtotime($playedAt)) {
            throw new InvalidArgumentException("Field 'played_at' must be a valid datetime.", 400);
        }

        return [
            'team_id' => $teamId,
            'opponent_name' => $opponentName,
            'team_score' => $teamScore,
            'opponent_score' => $opponentScore,
            'map_id' => $mapId,
            'game_mode_id' => $gameModeId,
            'duration' => $duration,
            'played_at' => $playedAt,
            'updated_by_user_id' => Auth::userId(),
        ];
    }

    /**
     * Validate player stats rows - numeric ranges, required player_id.
     */
    private function validateStatsRows(array $rows): array
    {
        if (empty($rows)) {
            throw new InvalidArgumentException('Player stats are required.', 400);
        }

        $cleaned = [];

        foreach ($rows as $index => $row) {
            $label = "stats[$index]";

            $playerId = (int)($row['player_id'] ?? 0);
            $killsNumber = (int)($row['kills_number'] ?? 0);
            $deathsNumber = (int)($row['deaths_number'] ?? 0);
            $assistsNumber = (int)($row['assists_number'] ?? 0);
            $flashAssistsNumber = (int)($row['flash_assists_number'] ?? 0);
            $totalDamage = (int)($row['total_damage'] ?? 0);
            $hsPercent = (float)($row['hs_percent'] ?? 0.0);
            $rkastNumber = (int)($row['rkast_number'] ?? 0);

            if ($playerId <= 0) {
                throw new InvalidArgumentException("$label: invalid 'player_id' field.", 400);
            }
            if ($killsNumber < 0 || $deathsNumber < 0 || $assistsNumber < 0
                || $flashAssistsNumber < 0 || $totalDamage < 0 || $rkastNumber < 0) {
                throw new InvalidArgumentException("$label: numeric values must be non-negative.", 400);
            }
            if ($hsPercent < 0.0 || $hsPercent > 100.0) {
                throw new InvalidArgumentException("$label: field 'hs_percent' must be 0-100.", 400);
            }

            $cleaned[] = [
                'player_id' => $playerId,
                'kills_number' => $killsNumber,
                'deaths_number' => $deathsNumber,
                'assists_number' => $assistsNumber,
                'flash_assists_number' => $flashAssistsNumber,
                'total_damage' => $totalDamage,
                'hs_percent' => $hsPercent,
                'rkast_number' => $rkastNumber,
            ];
        }

        return $cleaned;
    }
}