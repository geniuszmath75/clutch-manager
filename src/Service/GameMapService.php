<?php

declare(strict_types=1);

namespace Src\Service;

use Src\Repository\GameMapRepository;

final class GameMapService
{
    public function __construct(
        private readonly GameMapRepository $mapRepository
    )
    {
    }

    /**
     * Returns all game maps
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->mapRepository->findAll();
    }
}