<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;

final class DB
{
    private static ?PDO $instance = null;

    public static function setInstance(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database connection not initialized');
        }
        return self::$instance;
    }
}
