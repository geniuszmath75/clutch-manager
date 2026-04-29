<?php

declare(strict_types=1);

namespace Src\Repository;

final class GameMapRepository extends AbstractDictionaryRepository
{

    /**
     * @inheritDoc
     */
    protected function tableName(): string
    {
        return 'game_maps';
    }
}