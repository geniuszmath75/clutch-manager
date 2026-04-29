<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Src\Repository\GameMatchRepository;
use Src\Repository\PlayerRepository;
use Src\Service\GameMatchService;

final class GameMatchController
{
    private GameMatchService $matchService;

    public function __construct()
    {
        $matchRepository = new GameMatchRepository();
        $playerRepository = new PlayerRepository();
        $this->matchService = new GameMatchService(
            $matchRepository,
            $playerRepository
        );
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

            $result = $this->matchService->getAll($_GET, $page, $pageSize);
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
            $this->handleError($e->getCode(), $e->getMessage());
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
            $result = $this->matchService->getById($id);

            Response::json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
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
            $match = $this->matchService->create($body);

            Response::json([
                'success' => true,
                'match' => $match
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
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
            $match = $this->matchService->update($id, $body);

            Response::json([
                'success' => true,
                'match' => $match
            ]);
        } catch (InvalidArgumentException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
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
            $this->matchService->delete($id);
            Response::json([
                'success' => true,
                'message' => 'Match deleted successfully'
            ]);
        } catch (RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * HELPERS
     */

    /**
     * Parses the request body as JSON.
     * Supports Content-Type: application/json and application/x-www-form-urlencoded.
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
        if (Auth::isAjaxRequest()) {
            Response::error($code, $message);
            return;
        }

        Response::redirect('/matches?error=' . urlencode($message));
    }
}