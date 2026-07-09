<?php

declare(strict_types=1);

use App\Helpers\Response;

// Simple router — matches HTTP method + URI pattern
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip the script's base directory path (handles subdirectory deployment)
$basePath = dirname($_SERVER['SCRIPT_NAME']);
// Only strip base path if it's an actual directory, not the root slash
if ($basePath !== '/' && $basePath !== '\\') {
    $path = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $uri);
} else {
    $path = $uri;
}

// Strip /api/v1 prefix to match route definitions (made the leading slash optional with /?)
$path = preg_replace('#^/?api/v1#', '', $path);
$path = '/' . trim($path, '/');

// Route map: [method, path, handler]
// ... (keep all your routes exactly as they are below this line)

// Route map: [method, path, handler]
$routes = [
    // Auth
    ['POST', '/auth/login',         [\App\Controllers\AuthController::class, 'login']],
    ['POST', '/auth/register',      [\App\Controllers\AuthController::class, 'register']],
    ['GET',  '/auth/me',            [\App\Controllers\AuthController::class, 'me']],
    ['POST', '/auth/logout',        [\App\Controllers\AuthController::class, 'logout']],

    // Attendance
    ['GET',  '/attendance/today',   [\App\Controllers\AttendanceController::class, 'today']],
    ['POST', '/attendance/checkout',[\App\Controllers\AttendanceController::class, 'checkout']],

    // Trips
    ['GET',  '/trips',              [\App\Controllers\TripController::class, 'index']],
    ['POST', '/trips',              [\App\Controllers\TripController::class, 'store']],
    ['GET',  '/trips/{id}',         [\App\Controllers\TripController::class, 'show']],

    // Breakdowns
    ['GET',  '/breakdowns',         [\App\Controllers\BreakdownController::class, 'index']],
    ['POST', '/breakdowns',         [\App\Controllers\BreakdownController::class, 'store']],
    ['PUT',  '/breakdowns/{id}',    [\App\Controllers\BreakdownController::class, 'update']],

    // Locations
    ['POST', '/locations/batch',    [\App\Controllers\LocationController::class, 'batch']],
    ['GET',  '/locations/latest',   [\App\Controllers\LocationController::class, 'latest']],

    // Dashboard
    ['GET',  '/dashboard/owner',    [\App\Controllers\DashboardController::class, 'owner']],
    ['GET',  '/dashboard/supervisor', [\App\Controllers\DashboardController::class, 'supervisor']],
    ['GET',  '/dashboard/mechanic', [\App\Controllers\DashboardController::class, 'mechanic']],

    // Owner: User Management
    ['GET',    '/users',             [\App\Controllers\UserManagementController::class, 'index']],
    ['GET',    '/users/roles',       [\App\Controllers\UserManagementController::class, 'roles']],
    ['POST',   '/users',             [\App\Controllers\UserManagementController::class, 'store']],
    ['GET',    '/users/{id}',        [\App\Controllers\UserManagementController::class, 'show']],
    ['PUT',    '/users/{id}',        [\App\Controllers\UserManagementController::class, 'update']],
    ['DELETE', '/users/{id}',        [\App\Controllers\UserManagementController::class, 'destroy']],

    // Owner: Vehicle Management
    ['GET',    '/vehicles/manage',   [\App\Controllers\VehicleController::class, 'index']],
    ['POST',   '/vehicles/manage',   [\App\Controllers\VehicleController::class, 'store']],
    ['GET',    '/vehicles/manage/{id}', [\App\Controllers\VehicleController::class, 'show']],
    ['PUT',    '/vehicles/manage/{id}', [\App\Controllers\VehicleController::class, 'update']],
    ['DELETE', '/vehicles/manage/{id}', [\App\Controllers\VehicleController::class, 'destroy']],

    // Reference data (all authenticated users)
    ['GET',  '/vehicles',           [\App\Controllers\ReferenceController::class, 'vehicles']],
    ['GET',  '/materials',          [\App\Controllers\ReferenceController::class, 'materials']],
    ['GET',  '/shifts',             [\App\Controllers\ReferenceController::class, 'shifts']],
];

$matched = false;

foreach ($routes as [$routeMethod, $routePath, $handler]) {
    if ($method !== $routeMethod) {
        continue;
    }

    // Convert route pattern to regex (e.g., {id} -> (?P<id>[^/]+))
    $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
    $pattern = '#^' . $pattern . '$#';

    if (preg_match($pattern, $path, $matches)) {
        $matched = true;
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        [$class, $action] = $handler;
        $controller = new $class();
        $controller->$action($params);
        break;
    }
}

if (!$matched) {
    Response::notFound('Route not found');
}
