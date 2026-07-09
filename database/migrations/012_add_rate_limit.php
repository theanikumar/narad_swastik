<?php

declare(strict_types=1);

/**
 * Creates the rate_limit storage directory.
 * Run: php database/migrations/012_add_rate_limit.php
 */

$dir = __DIR__ . '/../storage/ratelimit';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
    echo "Created rate-limit storage directory: {$dir}" . PHP_EOL;
} else {
    echo "Rate-limit storage directory already exists: {$dir}" . PHP_EOL;
}
