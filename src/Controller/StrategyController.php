<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Repository\PlayerRepository;
use Src\Repository\StrategyRepository;
use Src\Service\StrategyService;

final class StrategyController extends BaseController
{
    private StrategyService $strategyService;

    public function __construct()
    {
        $playerRepository = new PlayerRepository();
        $strategyRepository = new StrategyRepository($playerRepository);
        $this->strategyService = new StrategyService(
            $strategyRepository,
        );
    }

    /**
     * GET /strategies?page=1&pageSize=5&filters
     */
    public function getStrategies(): void
    {
        Auth::requireLogin();

        try {
            $page = max(1, intval($_GET['page']) ?? 1);
            $pageSize = max(1, min(50, intval($_GET['pageSize']) ?? 5));

            $result = $this->strategyService->getAll($_GET, $page, $pageSize);

            Response::json([
                'success' => true,
                'data' => $result['strategies'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'pageSize' => $result['pageSize'],
                    'totalPages' => $result['totalPages'],
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * GET /strategies/{id}
     */
    public function getStrategyDetails(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        try {
            $result = $this->strategyService->getById($id);
            Response::json([
                'success' => true,
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * POST /strategies
     */
    public function createStrategy(): void
    {
        Auth::requireLogin();

        try {
            $body = $this->parseJsonBody();
            $strategy = $this->strategyService->create($body);

            Response::json([
                'success' => true,
                'strategy' => $strategy
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * PUT /strategies/{id}
     */
    public function updateStrategy(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        try {
            $body = $this->parseJsonBody();
            $strategy = $this->strategyService->update($id, $body);

            Response::json([
                'success' => true,
                'strategy' => $strategy
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * DELETE /strategies/{id}
     */
    public function deleteStrategy(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        try {
            $this->strategyService->delete($id);
            Response::json([
                'success' => true,
                'message' => 'Strategy deleted successfully'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }
}