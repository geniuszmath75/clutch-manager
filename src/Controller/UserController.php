<?php

declare(strict_types=1);

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Src\Repository\UserRepository;
use Src\Service\UserService;

final class UserController
{
    private UserService $userService;
    public function __construct()
    {
        $userRepository = new UserRepository();
        $this->userService = new UserService($userRepository);
    }

    /**
     * PATCH /users/me
     */
    public function updateUserProfile(): void
    {
        Auth::requireLogin();

        try {
            $data   = $this->parseJsonBody();
            $result = $this->userService->updateProfile($data);

            Response::json(['success' => true, 'data' => $result]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * PATCH /users/me/password
     */
    public function updateUserPassword(): void
    {
        Auth::requireLogin();

        try {
            $data = $this->parseJsonBody();
            $this->userService->updatePassword($data);

            Response::json(['success' => true, 'message' => 'Password updated successfully']);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

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
        Response::json([
            'success'      => false,
            'statusCode'   => $code,
            'errorMessage' => $message,
        ], $code);
    }
}