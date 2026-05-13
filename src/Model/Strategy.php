<?php

declare(strict_types=1);

namespace Src\Model;

final class Strategy
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly string $description,
        public readonly array  $stepsToDo,
        public readonly string $mapIdent,
        public readonly string $strategyTypeIdent,
        public readonly int    $teamId,
        public readonly int    $mapId,
        public readonly int    $strategyTypeId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        /** @var array<array{id: int, nickname: string}> */
        public readonly array $players = [],
    )
    {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            name: (string)$row['name'],
            description: (string)$row['description'],
            stepsToDo: is_array($row['steps_to_do'])
                ? $row['steps_to_do']
                : (json_decode((string)($row['steps_to_do'] ?? '[]'), true) ?? []),
            mapIdent: (string)($row['map_ident'] ?? ''),
            strategyTypeIdent: (string)($row['strategy_type_ident'] ?? ''),
            teamId: (int)$row['team_id'],
            mapId: (int)$row['map_id'],
            strategyTypeId: (int)$row['strategy_type_id'],
            createdAt: (string)$row['created_at'],
            updatedAt: (string)$row['updated_at'],
            players: []
        );
    }
}