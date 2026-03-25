<?php

declare(strict_types = 1);

namespace Src\Enum;

enum TeamRole: string
{
    case Igl = 'IGL';
    case Awp = 'AWP';
    case Entry = 'ENTRY';
    case Support = 'SUPPORT';
    case Lurker = 'LURKER';
}
