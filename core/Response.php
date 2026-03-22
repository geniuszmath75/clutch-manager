<?php

declare(strict_types=1);

namespace core;

final class Response
{
    public static function json(mixed $data, int $status = 200, bool $terminate = true): void
    {
        http_response_code($status);
        header('Content-Type: application/json');

        echo json_encode($data, JSON_UNESCAPED_UNICODE, JSON_THROW_ON_ERROR);

        if ($terminate) {
            exit;
        }
    }

    /**
     * JSON error response with a readable message.
     *
     * @param int $status HTTP code
     * @param string $message Message to the client
     */
    public static function error(int $status, string $message, bool $terminate = true): void
    {
        self::json(['statusCode' => $status, 'errorMessage' => $message], $status, $terminate);
    }

    /**
     * Response 401 Unauthorized.
     */
    public static function unauthorized(string $message = 'Unauthorized', bool $terminate = true): void
    {
        self::error(401, $message, $terminate);
    }

    /**
     * Response 403 Forbidden.
     */
    public static function forbidden(string $message = 'Forbidden', bool $terminate = true): void
    {
        self::error(403, $message, $terminate);
    }

    /**
     * Response 404 Not Found.
     */
    public static function notFound(string $message = 'Not Found', bool $terminate = true): void
    {
        self::error(404, $message, $terminate);
    }

    /**
     * Response 400 Bad Request.
     */
    public static function badRequest(string $message = 'Bad request', bool $terminate = true): void
    {
        self::error(400, $message, $terminate);
    }

    public static function serverError(string $message = 'Internal Server Error', bool $terminate = true): void
    {
        self::error(500, $message, $terminate);
    }
}