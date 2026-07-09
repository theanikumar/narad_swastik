<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Load configs
$appConfig = require __DIR__ . '/../config/app.php';
$dbConfig  = require __DIR__ . '/../config/database.php';

// Error handling
if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $appConfig['log_path'] . '/error.log');
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Rate limiting (skip for auth endpoints to avoid lockout)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isAuth = preg_match('#/auth/(login|register)$#', $uri) === 1;
if (!$isAuth) {
    \App\Middleware\RateLimitMiddleware::init(__DIR__ . '/../storage/ratelimit');
    \App\Middleware\RateLimitMiddleware::limit(120, 60);
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    \App\Helpers\Response::error('Database connection failed', 500);
    exit;
}

// Register PDO for dependency injection
\App\Helpers\DB::setInstance($pdo);

// Global exception handler
set_exception_handler(function (\Throwable $e) use ($appConfig) {
    if ($appConfig['debug']) {
        error_log('Uncaught ' . $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data'    => null,
        'message' => $appConfig['debug'] ? $e->getMessage() : 'Internal server error',
        'errors'  => [],
    ]);
    exit;
});

// Load routes
require __DIR__ . '/../routes/api.php';
