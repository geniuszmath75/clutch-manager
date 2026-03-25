<?php

declare(strict_types=1);

namespace Src\Repository;

final class TeamRoleRepository extends AbstractDictionaryRepository
{

    /**
     * @inheritDoc
     */
    protected function tableName(): string
    {
        return 'team_roles';
    }
}