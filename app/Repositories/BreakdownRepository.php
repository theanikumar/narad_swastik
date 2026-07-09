<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\DB;
use PDO;

final class BreakdownRepository
{
    public function create(array $data): int
    {
        $stmt = DB::connection()->prepare(
            'INSERT INTO breakdowns (vehicle_id, reported_by, issue_description, status)
             VALUES (:vehicle_id, :reported_by, :issue_description, :status)'
        );
        $stmt->execute([
            'vehicle_id'        => $data['vehicle_id'],
            'reported_by'       => $data['reported_by'],
            'issue_description' => $data['issue_description'],
            'status'            => 'open',
        ]);
        return (int) DB::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = DB::connection()->prepare(
            'SELECT b.*, v.vehicle_number,
                    reporter.name AS reported_by_name,
                    mechanic.name AS assigned_to_name
             FROM breakdowns b
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN users reporter ON reporter.id = b.reported_by
             LEFT JOIN users mechanic ON mechanic.id = b.assigned_to
             WHERE b.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findAll(?string $status = null): array
    {
        $sql = 'SELECT b.*, v.vehicle_number,
                       reporter.name AS reported_by_name,
                       mechanic.name AS assigned_to_name
                FROM breakdowns b
                JOIN vehicles v ON v.id = b.vehicle_id
                JOIN users reporter ON reporter.id = b.reported_by
                LEFT JOIN users mechanic ON mechanic.id = b.assigned_to';

        $params = [];

        if ($status) {
            $sql .= ' WHERE b.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY b.created_at DESC';

        $stmt = DB::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status, ?string $remarks = null, ?int $assignedTo = null): void
    {
        $sql = 'UPDATE breakdowns SET status = :status';
        $params = ['status' => $status, 'id' => $id];

        if ($remarks !== null) {
            $sql .= ', mechanic_remarks = :remarks';
            $params['remarks'] = $remarks;
        }

        if ($assignedTo !== null) {
            $sql .= ', assigned_to = :assigned_to';
            $params['assigned_to'] = $assignedTo;
        }

        if ($status === 'completed') {
            $sql .= ', resolved_at = NOW()';
        }

        $sql .= ' WHERE id = :id';

        $stmt = DB::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function getOpenCount(): int
    {
        $stmt = DB::connection()->query('SELECT COUNT(*) FROM breakdowns WHERE status != \'completed\'');
        return (int) $stmt->fetchColumn();
    }
}
