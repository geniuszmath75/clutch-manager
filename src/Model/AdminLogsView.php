<?php

declare(strict_types=1);

namespace Src\Model;

final class AdminLogsView
{
    public function __construct(
        public readonly int $logId,
        public readonly string $actorNickname,
        public readonly string $actorRole,
        public readonly ?string $teamTag,
        public readonly string $actionIdent,
        public readonly ?string $entityType,
        public readonly ?int $entityId,
        public readonly string $createdAt,
    )
    {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            logId: (int)$row['log_id'],
            actorNickname: $row['actor_nickname'],
            actorRole: $row['actor_role'],
            teamTag: $row['team_tag'] ?? null,
            actionIdent: $row['action_ident'],
            entityType: $row['entity_type'] ?? null,
            entityId: (int)$row['entity_id'] ?? null,
            createdAt: (string)$row['created_at'],
        );
    }
}