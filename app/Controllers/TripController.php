<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Logger;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\TripRepository;

final class TripController
{
    private readonly TripRepository $tripRepo;

    public function __construct()
    {
        $this->tripRepo = new TripRepository();
    }

    public function index(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor', 'owner');

        $date = $_GET['date'] ?? date('Y-m-d');
        $trips = $this->tripRepo->findBySupervisorAndDate($user['id'], $date);
        Response::success($trips);
    }

    public function store(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        $validator->required('vehicle_id')->integer('vehicle_id');
        $validator->required('shift_id')->integer('shift_id');
        $validator->required('material_id')->integer('material_id');
        $validator->required('trip_count')->integer('trip_count');

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        $tripData = [
            'supervisor_id' => $user['id'],
            'vehicle_id'    => (int) $data['vehicle_id'],
            'shift_id'      => (int) $data['shift_id'],
            'material_id'   => (int) $data['material_id'],
            'trip_count'    => (int) $data['trip_count'],
            'remarks'       => $data['remarks'] ?? null,
            'trip_date'     => date('Y-m-d'),
            'recorded_at'   => date('Y-m-d H:i:s'),
            'sync_status'   => $data['sync_status'] ?? 'synced',
        ];

        $tripId = $this->tripRepo->create($tripData);
        $trip = $this->tripRepo->findById($tripId);

        Logger::audit($user['id'], 'TRIP_CREATED', 'trips', $tripId, null, $tripData);

        Response::created($trip, 'Trip recorded successfully');
    }

    public function show(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor', 'owner');

        $trip = $this->tripRepo->findById((int) $params['id']);
        if (!$trip) {
            Response::notFound('Trip not found');
        }
        Response::success($trip);
    }
}
