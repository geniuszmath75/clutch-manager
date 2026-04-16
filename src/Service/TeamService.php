<?php

namespace Src\Service;

use Src\Model\Player;
use Src\Repository\TeamRepository;

final class TeamService
{
    public function __construct(private readonly TeamRepository $teamRepository)
    {

    }

    /**
     * Returns all teams
     *
     * @return Player[]
     */
    public function getAll(): array
    {
        return $this->teamRepository->findAll();
    }
}