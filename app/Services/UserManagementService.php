<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Repositories\UserRepository;

final class UserManagementService
{
    public function __construct(
        private readonly UserRepository $userRepo,
    ) {}

    public function list(): array
    {
        $users = $this->userRepo->findAll();
        return array_map(function ($user) {
            unset($user['password_hash']);
            return $user;
        }, $users);
    }

    public function get(int $id): array
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        unset($user['password_hash']);
        return $user;
    }

    public function create(array $data, int $createdBy): array
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

        Logger::audit($createdBy, 'USER_CREATED', 'users', $userId, null, $data);

        return $user;
    }

    public function update(int $id, array $data, int $updatedBy): array
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $oldValues = $user;
        $this->userRepo->update($id, $data);

        $updated = $this->userRepo->findById($id);
        unset($updated['password_hash']);

        Logger::audit($updatedBy, 'USER_UPDATED', 'users', $id, $oldValues, $updated);

        return $updated;
    }

    public function delete(int $id, int $deletedBy): void
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $this->userRepo->delete($id);

        Logger::audit($deletedBy, 'USER_DEACTIVATED', 'users', $id);
    }

    public function listRoles(): array
    {
        $stmt = \App\Helpers\DB::connection()->query(
            'SELECT id, name, slug, description FROM roles ORDER BY id'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
