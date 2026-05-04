<?php
/**
 * API: Search Patients
 * GET ?q=search_term
 * Returns JSON array of matching patients (max 10).
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
        "SELECT id, patient_id, full_name, date_of_birth, gender, blood_group, phone
         FROM patients
         WHERE full_name LIKE :q1
            OR patient_id LIKE :q2
            OR phone LIKE :q3
         ORDER BY full_name
         LIMIT 10"
    );
    $stmt->execute([':q1' => $term, ':q2' => $term, ':q3' => $term]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $row) {
        $results[] = [
            'id'          => (int) $row['id'],
            'patient_id'  => $row['patient_id'],
            'full_name'   => $row['full_name'],
            'dob'         => $row['date_of_birth'],
            'gender'      => $row['gender'],
            'blood_group' => $row['blood_group'],
            'phone'       => $row['phone'],
        ];
    }
    echo json_encode($results);
} catch (PDOException $e) {
    error_log('search_patient error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
