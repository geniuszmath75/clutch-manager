<?php

namespace Src\Controller;

use Core\Response;
use InvalidArgumentException;
use JsonException;

abstract class BaseController
{
    /**
     * Parses the request body as JSON.
     * Returns empty array if no body provided.
     * Throws InvalidArgumentException on JSON parse errors.
     *
     * @return array The parsed JSON data
     * @throws InvalidArgumentException
     */
    protected function parseJsonBody(): array
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
     * Returns error as JSON response.
     * Always returns JSON regardless of request type.
     *
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return void
     */
    protected function handleError(int $code, string $message): void
    {
        Response::json([
            'success' => false,
            'statusCode' => $code,
            'errorMessage' => $message,
        ], $code);
    }
}

