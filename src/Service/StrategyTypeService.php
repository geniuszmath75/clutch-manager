<?php

declare(strict_types=1);

namespace Src\Service;

use Src\Repository\StrategyTypeRepository;

final class StrategyTypeService
{
    public function __construct(
        private readonly StrategyTypeRepository $strategyTypeRepository,
    )
    {
    }

    /**
     * Returns all strategy types
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->strategyTypeRepository->findAll();
    }
}