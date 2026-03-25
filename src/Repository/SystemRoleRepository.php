<?php

declare(strict_types=1);

namespace Src\Repository;

final class SystemRoleRepository extends AbstractDictionaryRepository
{

    /**
     * @inheritDoc
     */
    protected function tableName(): string
    {
        return 'system_roles';
    }
}