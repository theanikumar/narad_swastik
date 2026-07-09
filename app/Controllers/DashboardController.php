<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DB;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\TripRepository;
use App\Repositories\BreakdownRepository;
use App\Repositories\LocationRepository;
use App\Repositories\UserRepository;

final class DashboardController
{
    public function owner(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('owner');

        $userRepo = new UserRepository();
        $breakdownRepo = new BreakdownRepository();
        $locationRepo = new LocationRepository();

        $todayTotalTrips = $this->getTodayTotalTrips();
        $todayActiveUsers = $this->getTodayActiveUsers();
        $breakdownByStatus = $this->getBreakdownByStatus();
        $recentActivity = $this->getRecentActivity();

        $dashboard = [
            'kpis' => [
                'active_operators'   => count($userRepo->findAllByRole('operator')),
                'active_supervisors' => count($userRepo->findAllByRole('supervisor')),
                'open_breakdowns'    => $breakdownRepo->getOpenCount(),
                'today_total_trips'  => $todayTotalTrips,
            ],
            'today_summary' => [
                'total_trips'  => $todayTotalTrips,
                'active_users' => $todayActiveUsers,
            ],
            'breakdown_by_status' => $breakdownByStatus,
            'recent_activity'     => $recentActivity,
            'latest_locations'    => $locationRepo->getLatestForAllVehicles(),
        ];

        Response::success($dashboard);
    }

    private function getTodayTotalTrips(): int
    {
        $stmt = DB::connection()->prepare(
            'SELECT COALESCE(SUM(trip_count), 0) FROM trips WHERE trip_date = CURDATE()'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function getTodayActiveUsers(): int
    {
        $stmt = DB::connection()->prepare(
            'SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date = CURDATE()'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function getBreakdownByStatus(): array
    {
        $stmt = DB::connection()->query(
            "SELECT status, COUNT(*) AS count FROM breakdowns GROUP BY status"
        );
        $result = ['open' => 0, 'in_progress' => 0, 'completed' => 0];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[$row['status']] = (int) $row['count'];
        }
        return $result;
    }

    private function getRecentActivity(): array
    {
        // Recent trips
        $tripStmt = DB::connection()->query(
            'SELECT t.id, t.trip_count, t.recorded_at, t.trip_date,
                    v.vehicle_number, s.name AS shift_name, m.name AS material_name,
                    u.name AS user_name
             FROM trips t
             JOIN vehicles v ON v.id = t.vehicle_id
             JOIN shifts s ON s.id = t.shift_id
             JOIN materials m ON m.id = t.material_id
             JOIN users u ON u.id = t.supervisor_id
             ORDER BY t.created_at DESC
             LIMIT 10'
        );
        $trips = $tripStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Recent breakdowns
        $bdStmt = DB::connection()->query(
            'SELECT b.id, b.issue_description, b.status, b.created_at, b.resolved_at,
                    v.vehicle_number, reporter.name AS reported_by_name
             FROM breakdowns b
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN users reporter ON reporter.id = b.reported_by
             ORDER BY b.created_at DESC
             LIMIT 10'
        );
        $breakdowns = $bdStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Merge and sort by timestamp descending
        $activity = [];

        foreach ($trips as $t) {
            $activity[] = [
                'type'       => 'trip',
                'message'    => "{$t['user_name']} logged {$t['trip_count']} trips on {$t['vehicle_number']} ({$t['material_name']}, {$t['shift_name']})",
                'timestamp'  => $t['recorded_at'],
            ];
        }

        foreach ($breakdowns as $b) {
            $activity[] = [
                'type'       => 'breakdown',
                'message'    => "{$b['reported_by_name']} reported {$b['vehicle_number']}: {$b['issue_description']}",
                'timestamp'  => $b['created_at'],
            ];
        }

        usort($activity, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

        return array_slice($activity, 0, 20);
    }

    public function supervisor(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        RoleMiddleware::allow('supervisor');

        $tripRepo = new TripRepository();
        $attendanceRepo = new AttendanceRepository();

        $today = date('Y-m-d');
        $attendance = $attendanceRepo->findTodayByUser($user['id']);

        $dashboard = [
            'today_attendance' => $attendance,
            'today_trips'      => $tripRepo->getTodayStats($user['id']),
        ];

        Response::success($dashboard);
    }

    public function mechanic(array $params): void
    {
        AuthMiddleware::authenticate();
        RoleMiddleware::allow('mechanic');

        $breakdownRepo = new BreakdownRepository();

        $dashboard = [
            'open'        => $breakdownRepo->findAll('open'),
            'in_progress' => $breakdownRepo->findAll('in_progress'),
            'completed'   => $breakdownRepo->findAll('completed'),
        ];

        Response::success($dashboard);
    }
}
