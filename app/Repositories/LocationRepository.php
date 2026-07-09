<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\DB;
use PDO;

final class LocationRepository
{
    public function batchInsert(array $locations): int
    {
        $pdo = DB::connection();
        $count = 0;

        $stmt = $pdo->prepare(
            'INSERT INTO locations (user_id, vehicle_id, latitude, longitude, speed, heading, accuracy, recorded_at)
             VALUES (:user_id, :vehicle_id, :latitude, :longitude, :speed, :heading, :accuracy, :recorded_at)'
        );

        foreach ($locations as $loc) {
            $stmt->execute([
                'user_id'     => $loc['user_id'],
                'vehicle_id'  => $loc['vehicle_id'],
                'latitude'    => $loc['latitude'],
                'longitude'   => $loc['longitude'],
                'speed'       => $loc['speed'] ?? null,
                'heading'     => $loc['heading'] ?? null,
                'accuracy'    => $loc['accuracy'] ?? null,
                'recorded_at' => $loc['recorded_at'],
            ]);
            $count++;
        }

        return $count;
    }

    public function getLatestForAllVehicles(): array
    {
        $stmt = DB::connection()->query(
            'SELECT l.*, u.name AS user_name, v.vehicle_number
             FROM locations l
             JOIN (
                 SELECT vehicle_id, MAX(recorded_at) AS max_time
                 FROM locations
                 GROUP BY vehicle_id
             ) latest ON latest.vehicle_id = l.vehicle_id AND latest.max_time = l.recorded_at
             JOIN users u ON u.id = l.user_id
             JOIN vehicles v ON v.id = l.vehicle_id'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
