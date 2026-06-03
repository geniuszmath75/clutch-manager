<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Response;
use Core\ServiceContainer;

final class GameModeController
{
    public function __construct()
    {
    }

    /**
     * GET /game-modes
     */
    public function getGameModes(): void
    {
        $modes = ServiceContainer::getGameModeService()->getAll();
        Response::json([
            'success' => true,
            'data' => $modes
        ]);
    }
}