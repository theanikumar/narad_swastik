<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\AttendanceRepository;

final class AttendanceController
{
    private readonly AttendanceRepository $attendanceRepo;

    public function __construct()
    {
        $this->attendanceRepo = new AttendanceRepository();
    }

    public function today(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor', 'owner');

        $attendance = $this->attendanceRepo->findTodayByUser($user['id']);
        Response::success($attendance);
    }

    public function checkout(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor');

        $attendance = $this->attendanceRepo->findTodayByUser($user['id']);
        if (!$attendance) {
            Response::error('No attendance record found for today', 404);
        }

        if ($attendance['exit_time'] !== null) {
            Response::error('Already checked out today', 400);
        }

        $this->attendanceRepo->updateExit($attendance['id'], date('Y-m-d H:i:s'));
        Response::success(null, 'Checkout recorded successfully');
    }
}
