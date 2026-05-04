<?php
/**
 * API: Dashboard Statistics
 * Returns live quick-stat counts as JSON for AJAX refresh.
 * OT Records Management System
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Must be authenticated
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

try {
    $pdo   = getDBConnection();
    $today = date('Y-m-d');
    $month = date('Y-m');

    $q = static function (PDO $pdo, string $sql, array $p = []): int {
        $s = $pdo->prepare($sql);
        $s->execute($p);
        return (int) $s->fetchColumn();
    };

    echo json_encode([
        'today_cases'      => $q($pdo, "SELECT COUNT(*) FROM case_bookings WHERE scheduled_date=:d",                      [':d' => $today]),
        'month_cases'      => $q($pdo, "SELECT COUNT(*) FROM case_bookings WHERE DATE_FORMAT(scheduled_date,'%Y-%m')=:m", [':m' => $month]),
        'total_patients'   => $q($pdo, "SELECT COUNT(*) FROM patients"),
        'pending_bookings' => $q($pdo, "SELECT COUNT(*) FROM case_bookings WHERE status='Scheduled'"),
        'completed_today'  => $q($pdo, "SELECT COUNT(*) FROM case_bookings WHERE scheduled_date=:d AND status='Completed'", [':d' => $today]),
        'emergencies_today'=> $q($pdo, "SELECT COUNT(*) FROM case_bookings WHERE scheduled_date=:d AND priority='Emergency'", [':d' => $today]),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('get_stats error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}
