<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Src\Repository\PlayerRepository;
use Src\Repository\StrategyRepository;
use Src\Service\StrategyService;

final class StrategyController
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

    /**
     * HELPERS
     */
    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid JSON payload.', 400);
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('Payload must be a JSON object.', 400);
        }

        return $data;
    }

    /**
     * Returns error as JSON (for AJAX) or redirect (for HTML).
     */
    private function handleError(int $code, string $message): void
    {
        Response::json([
            'success' => false,
            'statusCode' => $code,
            'errorMessage' => $message,
        ], $code);
    }
}