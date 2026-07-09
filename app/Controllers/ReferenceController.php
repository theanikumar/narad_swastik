<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class ReferenceController
{
    public function vehicles(array $params): void
    {
        AuthMiddleware::authenticate();

        $pdo = \App\Helpers\DB::connection();
        $stmt = $pdo->query(
            'SELECT id, vehicle_number, vehicle_type, status FROM vehicles WHERE status = \'active\' ORDER BY vehicle_number'
        );
        Response::success($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function materials(array $params): void
    {
        AuthMiddleware::authenticate();

        $pdo = \App\Helpers\DB::connection();
        $stmt = $pdo->query('SELECT id, name, description FROM materials ORDER BY name');
        Response::success($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function shifts(array $params): void
    {
        AuthMiddleware::authenticate();

        $pdo = \App\Helpers\DB::connection();
        $stmt = $pdo->query('SELECT id, name, start_time, end_time FROM shifts ORDER BY id');
        Response::success($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
}
