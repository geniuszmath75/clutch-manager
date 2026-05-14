<?php

declare(strict_types=1);

namespace Src\Model;

final class AdminDashboardView
{
    public function __construct(
        public readonly int $teamId,
        public readonly string $teamName,
        public readonly string $teamTag,
        public readonly int $totalMatches,
        public readonly float $teamWinRate,
        public readonly float $teamKd,
        public readonly int $avgKillsPerMatch,
        public readonly int $avgDamagePerMatch,
    )
    {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            teamId: (int)$row['team_id'],
            teamName: $row['team_name'],
            teamTag: $row['team_tag'],
            totalMatches: (int)$row['total_matches'],
            teamWinRate: (float)$row['team_win_rate'],
            teamKd: (float)$row['team_kd'],
            avgKillsPerMatch: (int)$row['avg_kills_per_match'],
            avgDamagePerMatch: (int)$row['avg_damage_per_match'],
        );
    }
}