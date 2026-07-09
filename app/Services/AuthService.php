<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Repositories\UserRepository;
use App\Repositories\AttendanceRepository;
use Firebase\JWT\JWT;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly AttendanceRepository $attendanceRepo,
    ) {}

    public function login(string $email, string $password): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Logger::audit(null, 'LOGIN_FAILED', 'users', null, null, ['email' => $email]);
            throw new \RuntimeException('Invalid email or password');
        }

        if ($user['status'] !== 'active') {
            Logger::audit($user['id'], 'LOGIN_BLOCKED', 'users', $user['id'], null, ['status' => $user['status']]);
            throw new \RuntimeException('Account is ' . $user['status'] . '. Contact administrator.');
        }

        $this->userRepo->updateLastLogin($user['id']);

        // Auto-mark attendance for supervisors
        if ($user['role_slug'] === 'supervisor') {
            $this->autoMarkAttendance($user['id']);
        }

        // Generate JWT
        $jwtConfig = require __DIR__ . '/../../config/jwt.php';
        $now = time();
        $payload = [
            'iss' => $jwtConfig['issuer'],
            'iat' => $now,
            'exp' => $now + $jwtConfig['expiry'],
            'data' => [
                'id'   => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role_slug'],
                'role_name' => $user['role_name'],
            ],
        ];

        $token = JWT::encode($payload, $jwtConfig['secret'], $jwtConfig['algorithm']);

        Logger::audit($user['id'], 'LOGIN_SUCCESS', 'users', $user['id']);

        return [
            'token' => $token,
            'user'  => $payload['data'],
        ];
    }

    public function register(array $data): array
    {
        $existing = $this->userRepo->findByEmail($data['email']);
        if ($existing) {
            throw new \RuntimeException('Email already registered');
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);

        $userId = $this->userRepo->create($data);
        $user = $this->userRepo->findById($userId);
        unset($user['password_hash']);

        Logger::audit($userId, 'USER_REGISTERED', 'users', $userId);

        return $user;
    }

    public function getUserById(int $id): array
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        unset($user['password_hash']);
        return $user;
    }

    private function autoMarkAttendance(int $userId): void
    {
        $today = date('Y-m-d');
        $existing = $this->attendanceRepo->findByUserAndDate($userId, $today);

        if (!$existing) {
            $this->attendanceRepo->create([
                'user_id'    => $userId,
                'date'       => $today,
                'entry_time' => date('Y-m-d H:i:s'),
            ]);
            Logger::audit($userId, 'ATTENDANCE_AUTO_MARKED', 'attendance', null, null, ['date' => $today]);
        }
    }
}
