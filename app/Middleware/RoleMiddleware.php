<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;

final class RoleMiddleware
{
    public static function allow(string ...$roles): void
    {
        $userRole = AuthMiddleware::userRole();

        if ($userRole === null) {
            Response::unauthorized('Authentication required');
        }

        if (!in_array($userRole, $roles, true)) {
            Response::forbidden('Insufficient permissions for this action');
        }
    }
}
