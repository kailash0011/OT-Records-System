<?php
/**
 * API: Get Schedule Events for FullCalendar
 * GET: start, end (ISO date strings from FullCalendar)
 * OT Records Management System
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d', strtotime('first day of this month'));
$end   = $_GET['end']   ?? date('Y-m-d', strtotime('last day of this month'));

// Trim time portion if present (FullCalendar sends ISO 8601)
$start = substr($start, 0, 10);
$end   = substr($end,   0, 10);

// Colour map by OT room id (cycling through a palette)
$roomColours = [
    1 => '#3788d8',
    2 => '#2e7d32',
    3 => '#e65100',
    4 => '#6a1b9a',
    5 => '#c62828',
];

$statusColours = [
    'Scheduled'   => '#3788d8',
    'In Progress' => '#f9a825',
    'Completed'   => '#388e3c',
    'Cancelled'   => '#d32f2f',
    'Postponed'   => '#757575',
];

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT cb.id, cb.booking_number, cb.scheduled_date, cb.scheduled_time,
                cb.estimated_duration, cb.status, cb.priority, cb.procedure_name,
                cb.ot_room_id,
                p.full_name  AS patient_name,
                s.full_name  AS surgeon_name,
                r.room_name
         FROM case_bookings cb
         JOIN patients    p ON cb.patient_id    = p.id
         JOIN surgeons    s ON cb.surgeon_id    = s.id
         LEFT JOIN ot_rooms r ON cb.ot_room_id  = r.id
         WHERE cb.scheduled_date BETWEEN :start AND :end
         ORDER BY cb.scheduled_date, cb.scheduled_time"
    );
    $stmt->execute([':start' => $start, ':end' => $end]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($rows as $row) {
        $startDT = $row['scheduled_date'] . 'T' . $row['scheduled_time'];
        $endTs   = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time'])
                   + (int)$row['estimated_duration'] * 60;
        $endDT   = date('Y-m-d\TH:i:s', $endTs);

        $colour = $statusColours[$row['status']] ?? '#3788d8';

        $events[] = [
            'id'    => (int) $row['id'],
            'title' => $row['procedure_name'] . ' – ' . $row['patient_name'],
            'start' => $startDT,
            'end'   => $endDT,
            'color' => $colour,
            'extendedProps' => [
                'booking_number' => $row['booking_number'],
                'patient'        => $row['patient_name'],
                'surgeon'        => $row['surgeon_name'],
                'room'           => $row['room_name'] ?? 'TBD',
                'status'         => $row['status'],
                'priority'       => $row['priority'],
                'duration'       => (int) $row['estimated_duration'],
            ],
        ];
    }

    echo json_encode($events);
} catch (PDOException $e) {
    error_log('get_schedule error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
