<?php

declare(strict_types=1);

/**
 * Database migration runner
 * Usage: php database/migrate.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$dbConfig = require __DIR__ . '/../config/database.php';

try {
    // Connect without database to create it if needed
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbConfig['database']}`");

    echo "Database '{$dbConfig['database']}' ready." . PHP_EOL;

    // Run migration files in order
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/[0-9]*.sql');
    sort($files);

    foreach ($files as $file) {
        if (basename($file) === '000_run_all.sql') {
            continue;
        }
        $filename = basename($file);
        echo "Running migration: {$filename} ... ";

        $sql = file_get_contents($file);
        $pdo->exec($sql);

        echo "OK" . PHP_EOL;
    }

    echo PHP_EOL . "All migrations completed successfully." . PHP_EOL;

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
