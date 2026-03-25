<?php

declare(strict_types=1);

namespace Src\Enum;

enum SystemRole: string
{
    case Admin = 'ADMIN';
    case Coach = 'COACH';
    case Player = 'PLAYER';
}
