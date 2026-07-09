<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;
use App\Repositories\AttendanceRepository;
use App\Services\AuthService;

final class AuthController
{
    private readonly AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService(
            new UserRepository(),
            new AttendanceRepository(),
        );
    }

    public function login(array $params): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($data);
        $validator->required('email')->email('email');
        $validator->required('password')->minLength('password', 6);

        if (!$validator->passes()) {
            Response::validationError($validator->errors());
        }

        try {
            $result = $this->authService->login($data['email'], $data['password']);
            Response::success($result, 'Login successful');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 401);
        }
    }

    public function register(array $params): void
    {
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
            $user = $this->authService->register($data);
            Response::created($user, 'Registration successful');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function me(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        $userData = $this->authService->getUserById($user['id']);
        Response::success($userData);
    }

    public function logout(array $params): void
    {
        AuthMiddleware::authenticate();
        // JWT is stateless — client discards the token
        Response::success(null, 'Logged out successfully');
    }
}
