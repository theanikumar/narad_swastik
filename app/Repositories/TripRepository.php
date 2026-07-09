<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\DB;
use PDO;

final class TripRepository
{
    public function create(array $data): int
    {
        $stmt = DB::connection()->prepare(
            'INSERT INTO trips (supervisor_id, vehicle_id, shift_id, material_id, trip_count, remarks, trip_date, recorded_at, sync_status)
             VALUES (:supervisor_id, :vehicle_id, :shift_id, :material_id, :trip_count, :remarks, :trip_date, :recorded_at, :sync_status)'
        );
        $stmt->execute([
            'supervisor_id' => $data['supervisor_id'],
            'vehicle_id'    => $data['vehicle_id'],
            'shift_id'      => $data['shift_id'],
            'material_id'   => $data['material_id'],
            'trip_count'    => $data['trip_count'],
            'remarks'       => $data['remarks'] ?? null,
            'trip_date'     => $data['trip_date'],
            'recorded_at'   => $data['recorded_at'],
            'sync_status'   => $data['sync_status'] ?? 'synced',
        ]);
        return (int) DB::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = DB::connection()->prepare(
            'SELECT t.*, v.vehicle_number, s.name AS shift_name, m.name AS material_name
             FROM trips t
             JOIN vehicles v ON v.id = t.vehicle_id
             JOIN shifts s ON s.id = t.shift_id
             JOIN materials m ON m.id = t.material_id
             WHERE t.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findBySupervisorAndDate(int $supervisorId, string $date): array
    {
        $stmt = DB::connection()->prepare(
            'SELECT t.*, v.vehicle_number, s.name AS shift_name, m.name AS material_name
             FROM trips t
             JOIN vehicles v ON v.id = t.vehicle_id
             JOIN shifts s ON s.id = t.shift_id
             JOIN materials m ON m.id = t.material_id
             WHERE t.supervisor_id = :supervisor_id AND t.trip_date = :trip_date
             ORDER BY t.recorded_at DESC'
        );
        $stmt->execute(['supervisor_id' => $supervisorId, 'trip_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTodayStats(int $supervisorId): array
    {
        $today = date('Y-m-d');
        $stmt = DB::connection()->prepare(
            'SELECT COALESCE(SUM(trip_count), 0) AS total_trips,
                    COUNT(*) AS total_entries
             FROM trips
             WHERE supervisor_id = :supervisor_id AND trip_date = :trip_date'
        );
        $stmt->execute(['supervisor_id' => $supervisorId, 'trip_date' => $today]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
