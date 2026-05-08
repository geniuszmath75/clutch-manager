<?php

declare(strict_types=1);

namespace Src\Repository;

class StrategyTypeRepository extends AbstractDictionaryRepository
{

    /**
     * @inheritDoc
     */
    protected function tableName(): string
    {
        return "strategy_types";
    }
}