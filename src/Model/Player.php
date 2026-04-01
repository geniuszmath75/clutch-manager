<?php

namespace Src\Model;

final class Player
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $nickname,
        public readonly string  $email,
        public readonly ?string $teamRoleIdent,
        public readonly bool    $isActive
    )
    {
    }


}