<?php

declare(strict_types=1);

namespace App\Helpers;

final class Response
{
    public static function json(mixed $data, string $message = 'Success', int $statusCode = 200, array $errors = []): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'data'    => $data,
            'message' => $message,
            'errors'  => $errors,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data, string $message = 'Success', int $statusCode = 200): void
    {
        self::json($data, $message, $statusCode);
    }

    public static function created(mixed $data, string $message = 'Created successfully'): void
    {
        self::json($data, $message, 201);
    }

    public static function error(string $message = 'Error', int $statusCode = 400, array $errors = []): void
    {
        self::json(null, $message, $statusCode, $errors);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::json(null, $message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::json(null, $message, 403);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::json(null, $message, 404);
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::json(null, $message, 422, $errors);
    }
}
