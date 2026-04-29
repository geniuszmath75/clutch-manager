<?php

namespace Src\Model;

final class GameMatch
{
    public function __construct(
        public readonly int    $id,
        public readonly string $opponentName,
        public readonly int    $opponentScore,
        public readonly int    $teamScore,
        public readonly int    $duration,
        public readonly string $mapIdent,
        public readonly int    $mapId,
        public readonly int    $teamId,
        public readonly int    $gameModeId,
        public readonly string $playedAt,
    )
    {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            opponentName: $row['opponent_name'],
            opponentScore: (int)$row['opponent_score'],
            teamScore: (int)$row['team_score'],
            duration: (int)$row['duration'],
            mapIdent: $row['map_ident'],
            mapId: (int)$row['map_id'],
            teamId: (int)$row['team_id'],
            gameModeId: (int)$row['game_mode_id'],
            playedAt: (string)$row['played_at']
        );
    }

    public function result(): string
    {
        if ($this->teamScore > $this->opponentScore) {
            return 'WIN';
        }
        if ($this->teamScore < $this->opponentScore) {
            return 'LOSS';
        }
        return 'DRAW';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'opponentName' => $this->opponentName,
            'opponentScore' => $this->opponentScore,
            'teamScore' => $this->teamScore,
            'duration' => $this->duration,
            'mapIdent' => $this->mapIdent,
            'mapId' => $this->mapId,
            'teamId' => $this->teamId,
            'gameModeId' => $this->gameModeId,
            'playedAt' => $this->playedAt,
            'result' => $this->result()
        ];
    }
}