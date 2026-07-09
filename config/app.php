<?php

return [
    'name'       => 'NARAD-SWASTIK API',
    'version'    => '1.0.0',
    'debug'      => (bool)($_ENV['APP_DEBUG'] ?? false),
    'env'        => $_ENV['APP_ENV'] ?? 'production',
    'log_path'   => __DIR__ . '/../storage/logs',
    'timezone'   => 'Asia/Kolkata',
    'cors'       => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    ],
];
