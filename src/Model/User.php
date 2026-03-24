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
}