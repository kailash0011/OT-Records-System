<?php
/**
 * API: Check OT Room Scheduling Conflict
 * POST: ot_room_id, scheduled_date, scheduled_time, estimated_duration, exclude_id (optional)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$otRoomId          = (int)   ($_POST['ot_room_id']          ?? 0);
$scheduledDate     = trim(    $_POST['scheduled_date']       ?? '');
$scheduledTime     = trim(    $_POST['scheduled_time']       ?? '');
$estimatedDuration = (int)   ($_POST['estimated_duration']   ?? 60);
$excludeId         = (int)   ($_POST['exclude_id']           ?? 0);

if (!$otRoomId || !$scheduledDate || !$scheduledTime) {
    echo json_encode(['conflict' => false, 'message' => 'Insufficient data to check conflict.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Calculate new booking's start and end time as full datetime strings for comparison
    $startDT = $scheduledDate . ' ' . $scheduledTime;
    $endDT   = date('Y-m-d H:i:s', strtotime($startDT) + $estimatedDuration * 60);

    $excludeSql = $excludeId > 0 ? 'AND cb.id != :excl' : '';

    $stmt = $pdo->prepare(
        "SELECT cb.id, cb.booking_number, cb.scheduled_date, cb.scheduled_time,
                cb.estimated_duration,
                p.full_name AS patient_name,
                s.full_name AS surgeon_name
         FROM case_bookings cb
         JOIN patients p ON cb.patient_id = p.id
         JOIN surgeons s ON cb.surgeon_id = s.id
         WHERE cb.ot_room_id = :room
           AND cb.scheduled_date = :date
           AND cb.status NOT IN ('Cancelled','Postponed')
           $excludeSql
           AND (
               ADDTIME(cb.scheduled_time, SEC_TO_TIME(cb.estimated_duration * 60)) > :start_time
               AND cb.scheduled_time < TIME(:end_time)
           )
         LIMIT 1"
    );

    $params = [
        ':room'       => $otRoomId,
        ':date'       => $scheduledDate,
        ':start_time' => $scheduledTime,
        ':end_time'   => $endDT,
    ];
    if ($excludeId > 0) {
        $params[':excl'] = $excludeId;
    }

    $stmt->execute($params);
    $conflict = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conflict) {
        $endMin  = strtotime($conflict['scheduled_date'] . ' ' . $conflict['scheduled_time'])
                   + (int)$conflict['estimated_duration'] * 60;
        echo json_encode([
            'conflict'            => true,
            'message'             => 'This OT room is already booked during the selected time.',
            'conflicting_booking' => [
                'id'             => (int) $conflict['id'],
                'booking_number' => $conflict['booking_number'],
                'patient'        => $conflict['patient_name'],
                'surgeon'        => $conflict['surgeon_name'],
                'start'          => $conflict['scheduled_date'] . ' ' . $conflict['scheduled_time'],
                'end'            => date('Y-m-d H:i', $endMin),
            ],
        ]);
    } else {
        echo json_encode(['conflict' => false, 'message' => 'No conflict – room is available.']);
    }
} catch (PDOException $e) {
    error_log('check_conflict error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
