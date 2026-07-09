<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\DB;
use PDO;

final class AttendanceRepository
{
    public function findByUserAndDate(int $userId, string $date): ?array
    {
        $stmt = DB::connection()->prepare(
            'SELECT * FROM attendance WHERE user_id = :user_id AND date = :date LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'date' => $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = DB::connection()->prepare(
            'INSERT INTO attendance (user_id, date, entry_time) VALUES (:user_id, :date, :entry_time)'
        );
        $stmt->execute([
            'user_id'    => $data['user_id'],
            'date'       => $data['date'],
            'entry_time' => $data['entry_time'],
        ]);
        return (int) DB::connection()->lastInsertId();
    }

    public function updateExit(int $id, string $exitTime): void
    {
        $stmt = DB::connection()->prepare(
            'UPDATE attendance SET exit_time = :exit_time WHERE id = :id'
        );
        $stmt->execute(['exit_time' => $exitTime, 'id' => $id]);
    }

    public function findTodayByUser(int $userId): ?array
    {
        return $this->findByUserAndDate($userId, date('Y-m-d'));
    }
}
