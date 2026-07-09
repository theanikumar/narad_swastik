<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Logger;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\BreakdownRepository;

final class BreakdownController
{
    private readonly BreakdownRepository $breakdownRepo;

    public function __construct()
    {
        $this->breakdownRepo = new BreakdownRepository();
    }

    public function index(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor', 'mechanic', 'owner');

        $status = $_GET['status'] ?? null;
        $breakdowns = $this->breakdownRepo->findAll($status);
        Response::success($breakdowns);
    }

    public function store(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        $validator->required('vehicle_id')->integer('vehicle_id');
        $validator->required('issue_description')->minLength('issue_description', 10);

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        $breakdownId = $this->breakdownRepo->create([
            'vehicle_id'        => (int) $data['vehicle_id'],
            'reported_by'       => $user['id'],
            'issue_description' => $data['issue_description'],
        ]);

        $breakdown = $this->breakdownRepo->findById($breakdownId);

        Logger::audit($user['id'], 'BREAKDOWN_REPORTED', 'breakdowns', $breakdownId, null, $data);

        Response::created($breakdown, 'Breakdown reported successfully');
    }

    public function update(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('mechanic');

        $breakdown = $this->breakdownRepo->findById((int) $params['id']);
        if (!$breakdown) {
            Response::notFound('Breakdown ticket not found');
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        $validator->required('status')->inArray('status', ['in_progress', 'completed']);

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        $oldStatus = $breakdown['status'];
        $this->breakdownRepo->updateStatus(
            (int) $params['id'],
            $data['status'],
            $data['mechanic_remarks'] ?? null,
            $user['id'],
        );

        Logger::audit(
            $user['id'],
            'BREAKDOWN_STATUS_CHANGED',
            'breakdowns',
            (int) $params['id'],
            ['status' => $oldStatus],
            ['status' => $data['status'], 'remarks' => $data['mechanic_remarks'] ?? null],
        );

        $breakdown = $this->breakdownRepo->findById((int) $params['id']);
        Response::success($breakdown, 'Breakdown updated successfully');
    }
}
