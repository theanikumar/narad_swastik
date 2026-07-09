<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\DB;
use PDO;

final class VehicleRepository
{
    public function findAll(): array
    {
        $stmt = DB::connection()->query(
            'SELECT * FROM vehicles ORDER BY vehicle_number'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = DB::connection()->prepare(
            'SELECT * FROM vehicles WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByNumber(string $vehicleNumber): ?array
    {
        $stmt = DB::connection()->prepare(
            'SELECT * FROM vehicles WHERE vehicle_number = :vehicle_number LIMIT 1'
        );
        $stmt->execute(['vehicle_number' => $vehicleNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = DB::connection()->prepare(
            'INSERT INTO vehicles (vehicle_number, vehicle_type, status)
             VALUES (:vehicle_number, :vehicle_type, :status)'
        );
        $stmt->execute([
            'vehicle_number' => $data['vehicle_number'],
            'vehicle_type'   => $data['vehicle_type'] ?? null,
            'status'         => $data['status'] ?? 'active',
        ]);
        return (int) DB::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        $allowed = ['vehicle_number', 'vehicle_type', 'status'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return;
        }

        $sql = 'UPDATE vehicles SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = DB::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = DB::connection()->prepare(
            'UPDATE vehicles SET status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => 'retired']);
    }
}
