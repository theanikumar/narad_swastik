<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Logger;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\VehicleRepository;

final class VehicleController
{
    private readonly VehicleRepository $vehicleRepo;

    public function __construct()
    {
        $this->vehicleRepo = new VehicleRepository();
    }

    public function index(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $vehicles = $this->vehicleRepo->findAll();
        Response::success($vehicles);
    }

    public function show(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $vehicle = $this->vehicleRepo->findById((int) $params['id']);
        if (!$vehicle) {
            Response::notFound('Vehicle not found');
        }
        Response::success($vehicle);
    }

    public function store(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        $validator->required('vehicle_number')->minLength('vehicle_number', 2)->maxLength('vehicle_number', 50);

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        $existing = $this->vehicleRepo->findByNumber($data['vehicle_number']);
        if ($existing) {
            Response::error('Vehicle number already exists', 409);
        }

        $vehicleId = $this->vehicleRepo->create([
            'vehicle_number' => strtoupper($data['vehicle_number']),
            'vehicle_type'   => $data['vehicle_type'] ?? null,
            'status'         => 'active',
        ]);

        $vehicle = $this->vehicleRepo->findById($vehicleId);

        Logger::audit($user['id'], 'VEHICLE_CREATED', 'vehicles', $vehicleId, null, $data);

        Response::created($vehicle, 'Vehicle added successfully');
    }

    public function update(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $vehicle = $this->vehicleRepo->findById((int) $params['id']);
        if (!$vehicle) {
            Response::notFound('Vehicle not found');
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        if (isset($data['vehicle_number'])) {
            $validator->minLength('vehicle_number', 2)->maxLength('vehicle_number', 50);
        }
        if (isset($data['status'])) {
            $validator->inArray('status', ['active', 'breakdown', 'maintenance', 'retired']);
        }

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        if (isset($data['vehicle_number'])) {
            $existing = $this->vehicleRepo->findByNumber($data['vehicle_number']);
            if ($existing && $existing['id'] !== (int) $params['id']) {
                Response::error('Vehicle number already in use', 409);
            }
            $data['vehicle_number'] = strtoupper($data['vehicle_number']);
        }

        $this->vehicleRepo->update((int) $params['id'], $data);
        $vehicle = $this->vehicleRepo->findById((int) $params['id']);

        Logger::audit($user['id'], 'VEHICLE_UPDATED', 'vehicles', (int) $params['id'], null, $data);

        Response::success($vehicle, 'Vehicle updated successfully');
    }

    public function destroy(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $vehicle = $this->vehicleRepo->findById((int) $params['id']);
        if (!$vehicle) {
            Response::notFound('Vehicle not found');
        }

        $this->vehicleRepo->delete((int) $params['id']);

        Logger::audit($user['id'], 'VEHICLE_DELETED', 'vehicles', (int) $params['id']);

        Response::success(null, 'Vehicle retired successfully');
    }
}
