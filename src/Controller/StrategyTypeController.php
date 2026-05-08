<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Response;
use Src\Repository\StrategyTypeRepository;
use Src\Service\StrategyTypeService;

final class StrategyTypeController
{
    private StrategyTypeService $strategyTypeService;

    public function __construct()
    {
        $strategyTypeRepository = new StrategyTypeRepository();
        $this->strategyTypeService = new StrategyTypeService($strategyTypeRepository);
    }

    /**
     * GET /strategy-types
     */
    public function getStrategyTypes(): void
    {
        $strategyTypes = $this->strategyTypeService->getAll();
        Response::json([
            'success' => true,
            'data' => $strategyTypes
        ]);
    }
}