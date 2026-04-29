<?php

declare(strict_types=1);

namespace Src\Repository;

class GameModeRepository extends AbstractDictionaryRepository
{

    /**
     * @inheritDoc
     */
    protected function tableName(): string
    {
        return 'game_modes';
    }
}