<?php

namespace Src\Model;

final class Player
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $nickname,
        public readonly string  $email,
        public readonly ?string $teamRoleIdent,
        public readonly bool $isActive,
        public readonly ?int $teamId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    )
    {
    }

    /**
     * Creates an instance from a database row (array from PDO::FETCH_ASSOC).
     * Centralizes column mapping -> properties.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            nickname: $row['nickname'],
            email: $row['email'],
            teamRoleIdent: $row['team_role_ident'] ?? null,
            isActive: (bool)$row['is_active'] ?? true,
            teamId: isset($row['team_id']) ? (int)$row['team_id'] : null,
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }
}