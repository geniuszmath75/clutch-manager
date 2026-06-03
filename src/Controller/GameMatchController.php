<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use Core\ServiceContainer;
use InvalidArgumentException;
use RuntimeException;

final class GameMatchController extends BaseController
{
    public function __construct()
    {
    }

    /**
     * GET /matches?page=1&pageSize=5&filters
     */
    public function getMatches(): void
    {
        Auth::requireLogin();

        try {
            $page = max(1, intval($_GET['page']));
            $pageSize = min(50, intval($_GET['pageSize']));

            $result = ServiceContainer::getGameMatchService()->getAll($_GET, $page, $pageSize);
            Response::json([
                'success' => true,
                'data' => $result['matches'],
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
     * GET /matches/{id}
     */
    public function getMatchDetails(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        try {
            $result = ServiceContainer::getGameMatchService()->getById($id);

            Response::json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * POST /matches
     */
    public function createMatch(): void
    {
        Auth::requireLogin();

        try {
            $body = $this->parseJsonBody();
            $match = ServiceContainer::getGameMatchService()->create($body);

            Response::json([
                'success' => true,
                'match' => $match
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * PUT /matches/{id}
     */
    public function updateMatch(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        try {
            $body = $this->parseJsonBody();
            $match = ServiceContainer::getGameMatchService()->update($id, $body);

            Response::json([
                'success' => true,
                'match' => $match
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }

    /**
     * DELETE /matches/{id}
     */
    public function deleteMatch(string $id): void
    {
        Auth::requireLogin();
        $id = intval($id);

        try {
            ServiceContainer::getGameMatchService()->delete($id);
            Response::json([
                'success' => true,
                'message' => 'Match deleted successfully'
            ]);
        } catch (RuntimeException $e) {
            $this->handleError((int)$e->getCode(), $e->getMessage());
        }
    }
}