<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Response;
use Core\ServiceContainer;

final class StrategyTypeController
{
    public function __construct()
    {
    }

    /**
     * GET /strategy-types
     */
    public function getStrategyTypes(): void
    {
        $strategyTypes = ServiceContainer::getStrategyTypeService()->getAll();
        Response::json([
            'success' => true,
            'data' => $strategyTypes
        ]);
    }
}