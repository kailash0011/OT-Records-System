<?php
/**
 * OT Record Save Handler
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRF($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$editId = (int)($_POST['record_id'] ?? 0);
$action = $editId > 0 ? 'edit' : 'create';

if ($action === 'create' && !hasPermission('ot_records', 'create')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}
if ($action === 'edit' && !hasPermission('ot_records', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

// Collect fields
$patientId         = (int)   ($_POST['patient_id']          ?? 0);
$surgeonId         = (int)   ($_POST['surgeon_id']           ?? 0);
$departmentId      = (int)   ($_POST['department_id']        ?? 0);
$otRoomId          = (int)   ($_POST['ot_room_id']           ?? 0) ?: null;
$bookingId         = (int)   ($_POST['booking_id']           ?? 0) ?: null;
$operationDate     = trim(    $_POST['operation_date']       ?? '');
$startTime         = trim(    $_POST['start_time']           ?? '') ?: null;
$endTime           = trim(    $_POST['end_time']             ?? '') ?: null;
$procedurePerformed= trim(    $_POST['procedure_performed']  ?? '');
$postOpDiagnosis   = trim(    $_POST['post_op_diagnosis']    ?? '');
$anaesthesiaType   = trim(    $_POST['anaesthesia_type']     ?? 'General');
$anaesthetistName  = trim(    $_POST['anaesthetist_name']    ?? '');
$scrubNurse        = trim(    $_POST['scrub_nurse']          ?? '');
$circulatingNurse  = trim(    $_POST['circulating_nurse']    ?? '');
$assistantSurgeon  = trim(    $_POST['assistant_surgeon']    ?? '');
$bloodLoss         = (int)   ($_POST['blood_loss_ml']        ?? 0);
$urineOutput       = (int)   ($_POST['urine_output_ml']      ?? 0);
$fluidInput        = (int)   ($_POST['fluid_input_ml']       ?? 0);
$complications     = trim(    $_POST['complications']        ?? '');
$postOpInstructions= trim(    $_POST['post_op_instructions'] ?? '');
$woundClass        = trim(    $_POST['wound_classification'] ?? 'Clean');
$outcome           = trim(    $_POST['outcome']              ?? 'Satisfactory');
$icuRequired       = isset($_POST['icu_required']) ? 1 : 0;

// Case types
$caseTypeEchs      = isset($_POST['case_type_echs'])  ? 1 : 0;
$caseTypeSsf       = isset($_POST['case_type_ssf'])   ? 1 : 0;
$caseTypeMlc       = isset($_POST['case_type_mlc'])   ? 1 : 0;
$caseTypeOther     = isset($_POST['case_type_other']) ? 1 : 0;
$caseTypeOtherText = trim($_POST['case_type_other_text'] ?? '');

// Dynamic rows
$transfusions      = $_POST['transfusions']  ?? [];
$catheters         = $_POST['catheters']     ?? [];
$specimens         = $_POST['specimens']     ?? [];
$implants          = $_POST['implants']      ?? [];

$errors = [];
if (!$patientId)         $errors[] = 'Patient is required.';
if (!$surgeonId)         $errors[] = 'Surgeon is required.';
if (!$departmentId)      $errors[] = 'Department is required.';
if (!$operationDate)     $errors[] = 'Operation date is required.';
if (!$procedurePerformed)$errors[] = 'Procedure performed is required.';
if (!validateDate($operationDate)) $errors[] = 'Invalid operation date.';

$allowedAnaes    = ['General','Spinal','Epidural','Local','Sedation'];
$allowedWound    = ['Clean','Clean-Contaminated','Contaminated','Dirty'];
$allowedOutcome  = ['Satisfactory','Guarded','Critical','Deceased'];
if (!in_array($anaesthesiaType, $allowedAnaes,   true)) $errors[] = 'Invalid anaesthesia type.';
if (!in_array($woundClass,      $allowedWound,   true)) $errors[] = 'Invalid wound classification.';
if (!in_array($outcome,         $allowedOutcome, true)) $errors[] = 'Invalid outcome.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    if ($action === 'create') {
        $recordNumber = generateRecordNumber($pdo);

        $stmt = $pdo->prepare(
            "INSERT INTO ot_records
                (record_number, booking_id, patient_id, surgeon_id, department_id, ot_room_id,
                 operation_date, start_time, end_time, procedure_performed, post_op_diagnosis,
                 anaesthesia_type, anaesthetist_name, scrub_nurse, circulating_nurse, assistant_surgeon,
                 blood_loss_ml, urine_output_ml, fluid_input_ml, complications, post_op_instructions,
                 wound_classification, outcome, icu_required,
                 case_type_echs, case_type_ssf, case_type_mlc, case_type_other, case_type_other_text,
                 created_by, created_at)
             VALUES
                (:rn, :bid, :pid, :sid, :did, :rid,
                 :opdate, :stime, :etime, :proc, :postdiag,
                 :anaes, :anaesname, :scrub, :circ, :asst,
                 :blood, :urine, :fluid, :comp, :postinstr,
                 :wound, :outcome, :icu,
                 :echs, :ssf, :mlc, :ctother, :ctothertext,
                 :uid, NOW())"
        );
        $stmt->execute([
            ':rn'          => $recordNumber,
            ':bid'         => $bookingId,
            ':pid'         => $patientId,
            ':sid'         => $surgeonId,
            ':did'         => $departmentId,
            ':rid'         => $otRoomId,
            ':opdate'      => $operationDate,
            ':stime'       => $startTime,
            ':etime'       => $endTime,
            ':proc'        => $procedurePerformed,
            ':postdiag'    => $postOpDiagnosis,
            ':anaes'       => $anaesthesiaType,
            ':anaesname'   => $anaesthetistName,
            ':scrub'       => $scrubNurse,
            ':circ'        => $circulatingNurse,
            ':asst'        => $assistantSurgeon,
            ':blood'       => $bloodLoss,
            ':urine'       => $urineOutput,
            ':fluid'       => $fluidInput,
            ':comp'        => $complications,
            ':postinstr'   => $postOpInstructions,
            ':wound'       => $woundClass,
            ':outcome'     => $outcome,
            ':icu'         => $icuRequired,
            ':echs'        => $caseTypeEchs,
            ':ssf'         => $caseTypeSsf,
            ':mlc'         => $caseTypeMlc,
            ':ctother'     => $caseTypeOther,
            ':ctothertext' => $caseTypeOtherText,
            ':uid'         => $_SESSION['user_id'],
        ]);
        $newId = (int)$pdo->lastInsertId();

    } else {
        $old = $pdo->prepare("SELECT * FROM ot_records WHERE id=:id LIMIT 1");
        $old->execute([':id' => $editId]);
        $oldData = $old->fetch();
        if (!$oldData) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>'Record not found.']);
            exit;
        }

        $pdo->prepare(
            "UPDATE ot_records SET
                patient_id=:pid, surgeon_id=:sid, department_id=:did, ot_room_id=:rid,
                operation_date=:opdate, start_time=:stime, end_time=:etime,
                procedure_performed=:proc, post_op_diagnosis=:postdiag,
                anaesthesia_type=:anaes, anaesthetist_name=:anaesname,
                scrub_nurse=:scrub, circulating_nurse=:circ, assistant_surgeon=:asst,
                blood_loss_ml=:blood, urine_output_ml=:urine, fluid_input_ml=:fluid,
                complications=:comp, post_op_instructions=:postinstr,
                wound_classification=:wound, outcome=:outcome, icu_required=:icu,
                case_type_echs=:echs, case_type_ssf=:ssf, case_type_mlc=:mlc,
                case_type_other=:ctother, case_type_other_text=:ctothertext,
                updated_at=NOW()
             WHERE id=:id"
        )->execute([
            ':pid'         => $patientId,    ':sid'         => $surgeonId,
            ':did'         => $departmentId, ':rid'         => $otRoomId,
            ':opdate'      => $operationDate,':stime'       => $startTime,
            ':etime'       => $endTime,      ':proc'        => $procedurePerformed,
            ':postdiag'    => $postOpDiagnosis, ':anaes'    => $anaesthesiaType,
            ':anaesname'   => $anaesthetistName, ':scrub'   => $scrubNurse,
            ':circ'        => $circulatingNurse, ':asst'    => $assistantSurgeon,
            ':blood'       => $bloodLoss,    ':urine'       => $urineOutput,
            ':fluid'       => $fluidInput,   ':comp'        => $complications,
            ':postinstr'   => $postOpInstructions, ':wound' => $woundClass,
            ':outcome'     => $outcome,      ':icu'         => $icuRequired,
            ':echs'        => $caseTypeEchs, ':ssf'         => $caseTypeSsf,
            ':mlc'         => $caseTypeMlc,  ':ctother'     => $caseTypeOther,
            ':ctothertext' => $caseTypeOtherText,
            ':id'          => $editId,
        ]);
        $newId = $editId;

        // Delete existing sub-tables and re-insert
        foreach (['blood_transfusions','catheters','specimens','implants'] as $tbl) {
            $pdo->prepare("DELETE FROM `$tbl` WHERE record_id=:id")->execute([':id'=>$newId]);
        }
    }

    // Insert blood transfusions
    if (!empty($transfusions) && is_array($transfusions)) {
        $ts = $pdo->prepare(
            "INSERT INTO blood_transfusions (record_id,blood_type,units_transfused,transfusion_time,reaction,notes)
             VALUES (:rid,:bt,:units,:ttime,:rxn,:notes)"
        );
        foreach ($transfusions as $t) {
            if (empty($t['blood_type'])) continue;
            $ts->execute([
                ':rid'  => $newId,
                ':bt'   => $t['blood_type']       ?? '',
                ':units'=> $t['units_transfused']  ?? null,
                ':ttime'=> $t['transfusion_time']  ?? null,
                ':rxn'  => isset($t['reaction']) ? 1 : 0,
                ':notes'=> $t['notes']             ?? '',
            ]);
        }
    }

    // Insert catheters
    if (!empty($catheters) && is_array($catheters)) {
        $cs = $pdo->prepare(
            "INSERT INTO catheters (record_id,catheter_type,size,insertion_time,removal_time,site,notes)
             VALUES (:rid,:ct,:sz,:ins,:rem,:site,:notes)"
        );
        foreach ($catheters as $c) {
            if (empty($c['catheter_type'])) continue;
            $cs->execute([
                ':rid'  => $newId,
                ':ct'   => $c['catheter_type']  ?? '',
                ':sz'   => $c['size']           ?? '',
                ':ins'  => $c['insertion_time'] ?? null,
                ':rem'  => $c['removal_time']   ?? null,
                ':site' => $c['site']           ?? '',
                ':notes'=> $c['notes']          ?? '',
            ]);
        }
    }

    // Insert specimens
    if (!empty($specimens) && is_array($specimens)) {
        $ss = $pdo->prepare(
            "INSERT INTO specimens (record_id,specimen_type,specimen_site,sent_to_lab,lab_reference,notes)
             VALUES (:rid,:st,:ss,:lab,:labref,:notes)"
        );
        foreach ($specimens as $s) {
            if (empty($s['specimen_type'])) continue;
            $ss->execute([
                ':rid'   => $newId,
                ':st'    => $s['specimen_type']  ?? '',
                ':ss'    => $s['specimen_site']  ?? '',
                ':lab'   => isset($s['sent_to_lab']) ? 1 : 0,
                ':labref'=> $s['lab_reference']  ?? '',
                ':notes' => $s['notes']          ?? '',
            ]);
        }
    }

    // Insert implants
    if (!empty($implants) && is_array($implants)) {
        $is = $pdo->prepare(
            "INSERT INTO implants (record_id,implant_name,brand,serial_number,lot_number,expiry_date,notes)
             VALUES (:rid,:iname,:brand,:serial,:lot,:expiry,:notes)"
        );
        foreach ($implants as $i) {
            if (empty($i['implant_name'])) continue;
            $is->execute([
                ':rid'   => $newId,
                ':iname' => $i['implant_name']  ?? '',
                ':brand' => $i['brand']         ?? '',
                ':serial'=> $i['serial_number'] ?? '',
                ':lot'   => $i['lot_number']    ?? '',
                ':expiry'=> $i['expiry_date']   ?? null,
                ':notes' => $i['notes']         ?? '',
            ]);
        }
    }

    // Update linked booking to Completed
    if ($bookingId && $action === 'create') {
        $pdo->prepare(
            "UPDATE case_bookings SET status='Completed', updated_at=NOW() WHERE id=:id"
        )->execute([':id' => $bookingId]);
    }

    $pdo->commit();

    auditLog(
        strtoupper($action),
        'ot_records',
        $newId,
        $action === 'edit' ? ($oldData ?? null) : null,
        ['record_number' => $action === 'create' ? ($recordNumber ?? '') : 'updated']
    );

    $msg = $action === 'create'
        ? "Record {$recordNumber} created successfully."
        : "Record updated successfully.";
    echo json_encode(['success' => true, 'message' => $msg, 'record_id' => $newId]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('records save error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}
