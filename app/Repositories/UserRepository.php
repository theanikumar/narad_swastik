<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\DB;
use PDO;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = DB::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = DB::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = DB::connection()->prepare(
            'INSERT INTO users (name, email, phone, password_hash, role_id)
             VALUES (:name, :email, :phone, :password_hash, :role_id)'
        );
        $stmt->execute([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => $data['password_hash'],
            'role_id'       => $data['role_id'],
        ]);
        return (int) DB::connection()->lastInsertId();
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = DB::connection()->prepare(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
    }

    public function findAllByRole(string $roleSlug): array
    {
        $stmt = DB::connection()->prepare(
            'SELECT u.id, u.name, u.email, u.phone, u.status
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.slug = :role_slug AND u.status = :status'
        );
        $stmt->execute(['role_slug' => $roleSlug, 'status' => 'active']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        $stmt = DB::connection()->query(
            'SELECT u.id, u.name, u.email, u.phone, u.status, r.name AS role_name, r.slug AS role_slug, u.created_at
             FROM users u
             JOIN roles r ON r.id = u.role_id
             ORDER BY u.created_at DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        $allowed = ['name', 'email', 'phone', 'role_id', 'status'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (array_key_exists('password', $data) && !empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = DB::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = DB::connection()->prepare(
            'UPDATE users SET status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => 'inactive']);
    }
}
