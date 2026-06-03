<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Core\ServiceContainer;

final class GameMapController
{
    public function __construct()
    {
    }

    /**
     * GET /game-maps
     */
    public function getGameMaps(): void
    {
        Auth::requireLogin();
        $maps = ServiceContainer::getGameMapService()->getAll();

        Response::json([
            'success' => true,
            'data' => $maps
        ]);
    }
}