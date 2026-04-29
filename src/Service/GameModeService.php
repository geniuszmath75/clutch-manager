<?php

declare(strict_types=1);

namespace Src\Service;

use Src\Repository\GameModeRepository;

final class GameModeService
{
    public function __construct(
        private readonly GameModeRepository $modeRepository
    )
    {
    }

    /**
     * Returns all game modes
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->modeRepository->findAll();
    }
}