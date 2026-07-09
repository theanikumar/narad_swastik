<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class AuthMiddleware
{
    private static ?array $user = null;

    public static function authenticate(): array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            // Try getallheaders() for some server configs
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            Response::unauthorized('Missing or malformed authorization header');
        }

        $token = substr($authHeader, 7);
        $jwtConfig = require __DIR__ . '/../../config/jwt.php';

        try {
            $decoded = JWT::decode($token, new Key($jwtConfig['secret'], $jwtConfig['algorithm']));
            self::$user = (array) $decoded->data;
            return self::$user;
        } catch (\Throwable $e) {
            Response::unauthorized('Invalid or expired token');
        }
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function userId(): ?int
    {
        return self::$user['id'] ?? null;
    }

    public static function userRole(): ?string
    {
        return self::$user['role'] ?? null;
    }
}
