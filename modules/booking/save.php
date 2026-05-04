<?php
/**
 * Booking Save Handler (New + Edit)
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRF($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page.']);
    exit;
}

// Determine if edit or new
$editId = (int)($_POST['booking_id'] ?? 0);
$action = $editId > 0 ? 'edit' : 'create';

if ($action === 'create' && !hasPermission('case_bookings', 'create')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}
if ($action === 'edit' && !hasPermission('case_bookings', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

// Collect and validate fields
$patientId          = (int)   ($_POST['patient_id']          ?? 0);
$surgeonId          = (int)   ($_POST['surgeon_id']           ?? 0);
$departmentId       = (int)   ($_POST['department_id']        ?? 0);
$otRoomId           = (int)   ($_POST['ot_room_id']           ?? 0) ?: null;
$procedureName      = trim(    $_POST['procedure_name']       ?? '');
$procedureType      = trim(    $_POST['procedure_type']       ?? 'Elective');
$scheduledDate      = trim(    $_POST['scheduled_date']       ?? '');
$scheduledTime      = trim(    $_POST['scheduled_time']       ?? '');
$estimatedDuration  = (int)   ($_POST['estimated_duration']   ?? 60);
$priority           = trim(    $_POST['priority']             ?? 'Routine');
$anaesthesiaType    = trim(    $_POST['anaesthesia_type']     ?? 'General');
$preOpDiagnosis     = trim(    $_POST['pre_op_diagnosis']     ?? '');
$specialRequirements= trim(    $_POST['special_requirements'] ?? '');

$errors = [];
if (!$patientId)     $errors[] = 'Patient is required.';
if (!$surgeonId)     $errors[] = 'Surgeon is required.';
if (!$departmentId)  $errors[] = 'Department is required.';
if (!$procedureName) $errors[] = 'Procedure name is required.';
if (!$scheduledDate) $errors[] = 'Scheduled date is required.';
if (!$scheduledTime) $errors[] = 'Scheduled time is required.';
if (!validateDate($scheduledDate)) $errors[] = 'Invalid scheduled date.';

$allowedTypes      = ['Elective','Emergency','Semi-Elective'];
$allowedPriorities = ['Routine','Urgent','Emergency'];
$allowedAnaes      = ['General','Spinal','Epidural','Local','Sedation'];
if (!in_array($procedureType,   $allowedTypes,      true)) $errors[] = 'Invalid procedure type.';
if (!in_array($priority,        $allowedPriorities, true)) $errors[] = 'Invalid priority.';
if (!in_array($anaesthesiaType, $allowedAnaes,      true)) $errors[] = 'Invalid anaesthesia type.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $pdo = getDBConnection();

    // Verify patient, surgeon, department exist
    $chk = function (string $table, int $id) use ($pdo): bool {
        $s = $pdo->prepare("SELECT 1 FROM `$table` WHERE id = :id LIMIT 1");
        $s->execute([':id' => $id]);
        return (bool)$s->fetchColumn();
    };
    if (!$chk('patients',    $patientId))    { echo json_encode(['success'=>false,'message'=>'Patient not found.']);    exit; }
    if (!$chk('surgeons',    $surgeonId))    { echo json_encode(['success'=>false,'message'=>'Surgeon not found.']);    exit; }
    if (!$chk('departments', $departmentId)) { echo json_encode(['success'=>false,'message'=>'Department not found.']); exit; }

    // Conflict check (only for the chosen room)
    if ($otRoomId) {
        $startDT = $scheduledDate . ' ' . $scheduledTime;
        $endDT   = date('Y-m-d H:i:s', strtotime($startDT) + $estimatedDuration * 60);
        $excl    = $editId > 0 ? 'AND id != :excl' : '';
        $cs = $pdo->prepare(
            "SELECT COUNT(*) FROM case_bookings
             WHERE ot_room_id = :room
               AND scheduled_date = :date
               AND status NOT IN ('Cancelled','Postponed')
               $excl
               AND ADDTIME(scheduled_time, SEC_TO_TIME(estimated_duration * 60)) > :stime
               AND scheduled_time < TIME(:etime)"
        );
        $cp = [':room'=>$otRoomId, ':date'=>$scheduledDate, ':stime'=>$scheduledTime, ':etime'=>$endDT];
        if ($editId > 0) $cp[':excl'] = $editId;
        $cs->execute($cp);
        if ((int)$cs->fetchColumn() > 0) {
            echo json_encode(['success'=>false,'message'=>'Scheduling conflict: OT room is already booked during this time.']);
            exit;
        }
    }

    if ($action === 'create') {
        $bookingNumber = generateBookingNumber($pdo);
        $stmt = $pdo->prepare(
            "INSERT INTO case_bookings
                (booking_number, patient_id, surgeon_id, department_id, ot_room_id,
                 procedure_name, procedure_type, scheduled_date, scheduled_time,
                 estimated_duration, priority, anaesthesia_type, pre_op_diagnosis,
                 special_requirements, status, booked_by, created_at)
             VALUES
                (:bn, :pid, :sid, :did, :rid,
                 :pname, :ptype, :date, :time,
                 :dur, :pri, :anaes, :prediag,
                 :specreq, 'Scheduled', :uid, NOW())"
        );
        $stmt->execute([
            ':bn'      => $bookingNumber,
            ':pid'     => $patientId,
            ':sid'     => $surgeonId,
            ':did'     => $departmentId,
            ':rid'     => $otRoomId,
            ':pname'   => $procedureName,
            ':ptype'   => $procedureType,
            ':date'    => $scheduledDate,
            ':time'    => $scheduledTime,
            ':dur'     => $estimatedDuration,
            ':pri'     => $priority,
            ':anaes'   => $anaesthesiaType,
            ':prediag' => $preOpDiagnosis,
            ':specreq' => $specialRequirements,
            ':uid'     => $_SESSION['user_id'],
        ]);
        $newId = (int)$pdo->lastInsertId();
        auditLog('CREATE', 'case_bookings', $newId, null, ['booking_number' => $bookingNumber]);
        echo json_encode(['success' => true, 'message' => "Booking {$bookingNumber} created successfully.", 'booking_id' => $newId]);

    } else {
        // Fetch existing for audit
        $old = $pdo->prepare("SELECT * FROM case_bookings WHERE id = :id LIMIT 1");
        $old->execute([':id' => $editId]);
        $oldData = $old->fetch();

        if (!$oldData) {
            echo json_encode(['success'=>false,'message'=>'Booking not found.']);
            exit;
        }

        $pdo->prepare(
            "UPDATE case_bookings SET
                patient_id=:pid, surgeon_id=:sid, department_id=:did, ot_room_id=:rid,
                procedure_name=:pname, procedure_type=:ptype,
                scheduled_date=:date, scheduled_time=:time, estimated_duration=:dur,
                priority=:pri, anaesthesia_type=:anaes,
                pre_op_diagnosis=:prediag, special_requirements=:specreq,
                updated_at=NOW()
             WHERE id=:id"
        )->execute([
            ':pid'     => $patientId,
            ':sid'     => $surgeonId,
            ':did'     => $departmentId,
            ':rid'     => $otRoomId,
            ':pname'   => $procedureName,
            ':ptype'   => $procedureType,
            ':date'    => $scheduledDate,
            ':time'    => $scheduledTime,
            ':dur'     => $estimatedDuration,
            ':pri'     => $priority,
            ':anaes'   => $anaesthesiaType,
            ':prediag' => $preOpDiagnosis,
            ':specreq' => $specialRequirements,
            ':id'      => $editId,
        ]);
        auditLog('UPDATE', 'case_bookings', $editId, $oldData, ['procedure_name'=>$procedureName, 'scheduled_date'=>$scheduledDate]);
        echo json_encode(['success' => true, 'message' => 'Booking updated successfully.', 'booking_id' => $editId]);
    }
} catch (PDOException $e) {
    error_log('booking save error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}
