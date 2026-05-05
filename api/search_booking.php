<?php
/**
 * API: Search Case Bookings
 * GET ?q=search_term  (searches booking_number, patient name, procedure)
 * Returns JSON array of matching booked cases (max 15).
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

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

try {
    $pdo  = getDBConnection();
    $term = '%' . $q . '%';
    $stmt = $pdo->prepare(
        "SELECT cb.id, cb.booking_number, cb.procedure_name, cb.scheduled_date,
                cb.scheduled_time, cb.anaesthesia_type, cb.pre_op_diagnosis,
                cb.surgeon_id, cb.department_id, cb.patient_id,
                p.full_name AS patient_name, p.patient_id AS patient_code,
                s.full_name AS surgeon_name
         FROM case_bookings cb
         JOIN patients p ON p.id = cb.patient_id
         JOIN surgeons s ON s.id = cb.surgeon_id
         WHERE cb.status IN ('Scheduled','In Progress')
           AND (cb.booking_number LIKE :q1
                OR p.full_name     LIKE :q2
                OR cb.procedure_name LIKE :q3
                OR p.patient_id    LIKE :q4)
         ORDER BY cb.scheduled_date DESC, cb.scheduled_time DESC
         LIMIT 15"
    );
    $stmt->execute([':q1' => $term, ':q2' => $term, ':q3' => $term, ':q4' => $term]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $row) {
        $results[] = [
            'id'              => (int) $row['id'],
            'booking_number'  => $row['booking_number'],
            'patient_id'      => (int) $row['patient_id'],
            'patient_code'    => $row['patient_code'],
            'patient_name'    => $row['patient_name'],
            'procedure_name'  => $row['procedure_name'],
            'surgeon_id'      => (int) $row['surgeon_id'],
            'surgeon_name'    => $row['surgeon_name'],
            'department_id'   => (int) $row['department_id'],
            'scheduled_date'  => $row['scheduled_date'],
            'scheduled_time'  => $row['scheduled_time'],
            'anaesthesia_type'=> $row['anaesthesia_type'],
            'pre_op_diagnosis'=> $row['pre_op_diagnosis'] ?? '',
        ];
    }
    echo json_encode($results);
} catch (PDOException $e) {
    error_log('search_booking error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
