<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Core\ServiceContainer;
use InvalidArgumentException;
use RuntimeException;

final class StrategyController extends BaseController
{
    public function __construct()
    {
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

            $result = ServiceContainer::getStrategyService()->getAll($_GET, $page, $pageSize);

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
            $this->handleError((int)$e->getCode(), $e->getMessage());
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
            $result = ServiceContainer::getStrategyService()->getById($id);
            Response::json([
                'success' => true,
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
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
            $strategy = ServiceContainer::getStrategyService()->create($body);

            Response::json([
                'success' => true,
                'strategy' => $strategy
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
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
            $strategy = ServiceContainer::getStrategyService()->update($id, $body);

            Response::json([
                'success' => true,
                'strategy' => $strategy
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
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
            ServiceContainer::getStrategyService()->delete($id);
            Response::json([
                'success' => true,
                'message' => 'Strategy deleted successfully'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }
}