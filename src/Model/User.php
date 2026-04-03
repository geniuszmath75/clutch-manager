<?php

declare(strict_types=1);

namespace Src\Model;
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $nickname,
        public readonly string $email,
        public readonly string $password,
        public readonly string $systemRole,
        public readonly ?string $teamRole,
        public readonly ?int $teamId,
        public readonly bool $isActive,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            nickname: $row['nickname'],
            email: $row['email'],
            password: $row['password'],
            systemRole: $row['system_role'],
            teamRole: $row['team_role'] ?? null,
            teamId: (int)$row['team_id'] ?? null,
            isActive: $row['is_active'],
        );
    }
}