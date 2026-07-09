<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Logger;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\LocationRepository;

final class LocationController
{
    private readonly LocationRepository $locationRepo;

    public function __construct()
    {
        $this->locationRepo = new LocationRepository();
    }

    public function batch(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('operator');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $locations = $data['locations'] ?? [];

        if (empty($locations) || !is_array($locations)) {
            Response::validationError(['locations' => ['At least one location point is required']]);
        }

        $count = $this->locationRepo->batchInsert($locations);

        Logger::audit($user['id'], 'LOCATIONS_UPLOADED', 'locations', null, null, ['count' => $count]);

        Response::created(['uploaded' => $count], "{$count} location points uploaded");
    }

    public function latest(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $locations = $this->locationRepo->getLatestForAllVehicles();
        Response::success($locations);
    }
}
