<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;

final class RateLimitMiddleware
{
    private static string $storageDir = '';

    public static function init(string $storageDir): void
    {
        self::$storageDir = rtrim($storageDir, '/\\');
        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0755, true);
        }
    }

    public static function limit(int $maxRequests = 60, int $windowSeconds = 60): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'ratelimit_' . md5($ip);
        $file = (self::$storageDir ?: __DIR__ . '/../../storage/ratelimit') . "/{$key}.json";

        $now = time();
        $data = ['window_start' => $now, 'count' => 0];

        if (is_file($file)) {
            $stored = json_decode(file_get_contents($file), true);
            if (is_array($stored) && ($now - ($stored['window_start'] ?? 0)) < $windowSeconds) {
                $data = $stored;
            }
        }

        $data['count'] = ($data['count'] ?? 0) + 1;

        if ($data['count'] > $maxRequests) {
            $retryAfter = $windowSeconds - ($now - ($data['window_start'] ?? $now));
            header('Retry-After: ' . max(1, $retryAfter));
            Response::error('Too many requests. Please try again later.', 429);
        }

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
}
