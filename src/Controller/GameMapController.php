<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Src\Repository\GameMapRepository;
use Src\Service\GameMapService;

final class GameMapController
{
    private GameMapService $mapService;

    public function __construct()
    {
        $mapRepository = new GameMapRepository();
        $this->mapService = new GameMapService($mapRepository);
    }

    /**
     * GET /game-maps
     */
    public function getGameMaps(): void
    {
        Auth::requireLogin();
        $maps = $this->mapService->getAll();

        Response::json([
            'success' => true,
            'data' => $maps
        ]);
    }
}