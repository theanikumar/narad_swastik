<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\UserRepository;
use App\Services\UserManagementService;

final class UserManagementController
{
    private readonly UserManagementService $service;

    public function __construct()
    {
        $this->service = new UserManagementService(
            new UserRepository(),
        );
    }

    public function index(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $users = $this->service->list();
        Response::success($users);
    }

    public function show(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        try {
            $user = $this->service->get((int) $params['id']);
            Response::success($user);
        } catch (\RuntimeException $e) {
            Response::notFound($e->getMessage());
        }
    }

    public function store(array $params): void
    {
        $authUser = AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        $validator->required('name')->minLength('name', 2)->maxLength('name', 100);
        $validator->required('email')->email('email');
        $validator->required('password')->minLength('password', 6);
        $validator->required('role_id')->integer('role_id');

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        try {
            $user = $this->service->create($data, $authUser['id']);
            Response::created($user, 'User created successfully');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function update(array $params): void
    {
        $authUser = AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        if (isset($data['name'])) {
            $validator->minLength('name', 2)->maxLength('name', 100);
        }
        if (isset($data['email'])) {
            $validator->email('email');
        }
        if (isset($data['password'])) {
            $validator->minLength('password', 6);
        }
        if (isset($data['role_id'])) {
            $validator->integer('role_id');
        }
        if (isset($data['status'])) {
            $validator->inArray('status', ['active', 'inactive', 'suspended']);
        }

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        try {
            $user = $this->service->update((int) $params['id'], $data, $authUser['id']);
            Response::success($user, 'User updated successfully');
        } catch (\RuntimeException $e) {
            Response::notFound($e->getMessage());
        }
    }

    public function destroy(array $params): void
    {
        $authUser = AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        try {
            $this->service->delete((int) $params['id'], $authUser['id']);
            Response::success(null, 'User deactivated successfully');
        } catch (\RuntimeException $e) {
            Response::notFound($e->getMessage());
        }
    }

    public function roles(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $roles = $this->service->listRoles();
        Response::success($roles);
    }
}
