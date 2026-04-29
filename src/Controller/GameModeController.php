<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Response;
use Src\Repository\GameModeRepository;
use Src\Service\GameModeService;

final class GameModeController
{
    private GameModeService $modeService;

    public function __construct()
    {
        $modeRepository = new GameModeRepository();
        $this->modeService = new GameModeService($modeRepository);
    }

    /**
     * GET /game-modes
     */
    public function getGameModes(): void
    {
        $modes = $this->modeService->getAll();
        Response::json([
            'success' => true,
            'data' => $modes
        ]);
    }
}