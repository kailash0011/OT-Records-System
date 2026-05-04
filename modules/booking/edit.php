<?php
/**
 * Edit Case Booking
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
if (!hasPermission('case_bookings', 'edit')) {
    $_SESSION['flash_error'] = 'You do not have permission to edit bookings.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'booking_list.php'); exit; }

$pdo  = getDBConnection();
$stmt = $pdo->prepare(
    "SELECT cb.*, p.full_name AS patient_name, p.patient_id AS patient_code
     FROM case_bookings cb
     JOIN patients p ON cb.patient_id = p.id
     WHERE cb.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['flash_error'] = 'Booking not found.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

$pageTitle   = 'Edit Booking: ' . $booking['booking_number'];
$surgeons    = $pdo->query("SELECT id, full_name, department_id FROM surgeons WHERE is_active=1 ORDER BY full_name")->fetchAll();
$departments = $pdo->query("SELECT id, name FROM departments WHERE is_active=1 ORDER BY name")->fetchAll();
$otRooms     = $pdo->query("SELECT id, room_name, room_number FROM ot_rooms WHERE is_active=1 ORDER BY room_name")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold">
            <i class="fas fa-edit me-2 text-warning"></i>Edit Booking: <?= sanitize($booking['booking_number']) ?>
        </span>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>booking_list.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark py-2">
                <i class="fas fa-edit me-2"></i>Edit Case Booking
                <span class="float-end small"><?= getStatusBadge($booking['status']) ?></span>
            </div>
            <div class="card-body">
                <form id="bookingForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= sanitize($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                    <!-- Patient -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1 mb-3"><i class="fas fa-user me-2"></i>Patient</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Patient <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="patientSearch" class="form-control"
                                       value="<?= sanitize($booking['patient_name']) ?>" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="clearPatient">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="hidden" name="patient_id" id="patientId" value="<?= $booking['patient_id'] ?>" required>
                            <div id="patientDropdown" class="list-group position-absolute z-3" style="min-width:350px;display:none;max-height:200px;overflow-y:auto;"></div>
                            <div id="patientInfo" class="alert alert-info mt-2 py-2 small">
                                <i class="fas fa-user-check me-1"></i>
                                <strong><?= sanitize($booking['patient_name']) ?></strong>
                                | ID: <?= sanitize($booking['patient_code']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="row mb-3">
                        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-1 mb-3"><i class="fas fa-clipboard me-2"></i>Booking Details</h6></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Surgeon <span class="text-danger">*</span></label>
                            <select name="surgeon_id" id="surgeonId" class="form-select select2" required data-placeholder="Select surgeon…">
                                <option value=""></option>
                                <?php foreach ($surgeons as $s): ?>
                                <option value="<?= $s['id'] ?>" data-dept="<?= $s['department_id'] ?>"
                                    <?= $booking['surgeon_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($s['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select name="department_id" id="departmentId" class="form-select select2" required data-placeholder="Select department…">
                                <option value=""></option>
                                <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $booking['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($d['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">OT Room</label>
                            <select name="ot_room_id" id="otRoomId" class="form-select select2" data-placeholder="Select OT Room…">
                                <option value=""></option>
                                <?php foreach ($otRooms as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $booking['ot_room_id'] == $r['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($r['room_name']) ?> (<?= sanitize($r['room_number']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Procedure Name <span class="text-danger">*</span></label>
                            <input type="text" name="procedure_name" class="form-control" required
                                   value="<?= sanitize($booking['procedure_name']) ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Procedure Type</label>
                            <select name="procedure_type" class="form-select">
                                <?php foreach (['Elective','Emergency','Semi-Elective'] as $t): ?>
                                <option value="<?= $t ?>" <?= $booking['procedure_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select name="priority" class="form-select">
                                <?php foreach (['Routine','Urgent','Emergency'] as $p): ?>
                                <option value="<?= $p ?>" <?= $booking['priority'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Schedule -->
                    <div class="row mb-3">
                        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-1 mb-3"><i class="fas fa-clock me-2"></i>Schedule</h6></div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="text" name="scheduled_date" id="scheduledDate" class="form-control date-picker-future" required
                                   value="<?= sanitize($booking['scheduled_date']) ?>" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Scheduled Time <span class="text-danger">*</span></label>
                            <input type="text" name="scheduled_time" id="scheduledTime" class="form-control time-picker" required
                                   value="<?= sanitize(substr($booking['scheduled_time'],0,5)) ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Estimated Duration</label>
                            <select name="estimated_duration" class="form-select">
                                <?php foreach ([30,60,90,120,180,240] as $dur): ?>
                                <option value="<?= $dur ?>" <?= $booking['estimated_duration'] == $dur ? 'selected' : '' ?>>
                                    <?= formatDuration($dur) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Anaesthesia Type</label>
                            <select name="anaesthesia_type" class="form-select">
                                <?php foreach (['General','Spinal','Epidural','Local','Sedation'] as $a): ?>
                                <option value="<?= $a ?>" <?= $booking['anaesthesia_type'] === $a ? 'selected' : '' ?>><?= $a ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mb-2">
                            <button type="button" id="checkConflictBtn" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-exclamation-triangle me-1"></i>Check for Conflicts
                            </button>
                            <span id="conflictResult" class="ms-2"></span>
                        </div>
                    </div>

                    <!-- Clinical -->
                    <div class="row mb-3">
                        <div class="col-12"><h6 class="fw-bold text-primary border-bottom pb-1 mb-3"><i class="fas fa-notes-medical me-2"></i>Clinical Details</h6></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Pre-op Diagnosis</label>
                            <textarea name="pre_op_diagnosis" class="form-control" rows="3"><?= sanitize($booking['pre_op_diagnosis'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Special Requirements</label>
                            <textarea name="special_requirements" class="form-control" rows="3"><?= sanitize($booking['special_requirements'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end border-top pt-3">
                        <a href="<?= BASE_URL ?>booking_list.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Cancel</a>
                        <button type="submit" class="btn btn-warning text-dark"><i class="fas fa-save me-1"></i>Update Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<script>
const SAVE_URL     = '<?= BASE_URL ?>modules/booking/save.php';
const CONFLICT_URL = '<?= BASE_URL ?>api/check_conflict.php';
const SEARCH_URL   = '<?= BASE_URL ?>api/search_patient.php';
const EDIT_ID      = <?= $id ?>;

$(document).ready(function () {
    flatpickr('#scheduledDate', { dateFormat:'Y-m-d', allowInput:true });

    let searchTimer;
    $('#patientSearch').on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val().trim();
        if (q.length < 2) { $('#patientDropdown').hide(); return; }
        searchTimer = setTimeout(function () {
            $.getJSON(SEARCH_URL, {q:q}, function (data) {
                const dd = $('#patientDropdown').empty().show();
                if (!data.length) { dd.append('<a class="list-group-item small text-muted">No patients found.</a>'); return; }
                data.forEach(function (p) {
                    dd.append($('<a class="list-group-item list-group-item-action small"></a>')
                        .html('<strong>'+$('<div>').text(p.full_name).html()+'</strong> '+$('<div>').text(p.patient_id).html())
                        .on('click', function () {
                            $('#patientSearch').val(p.full_name);
                            $('#patientId').val(p.id);
                            $('#patientInfo').html('<i class="fas fa-user-check me-1"></i><strong>'+$('<div>').text(p.full_name).html()+'</strong> | ID: '+$('<div>').text(p.patient_id).html()).show();
                            $('#patientDropdown').hide();
                        }));
                });
            });
        }, 300);
    });

    $('#clearPatient').on('click', function () {
        $('#patientSearch, #patientId').val('');
        $('#patientInfo').hide();
        $('#patientDropdown').hide();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#patientSearch,#patientDropdown').length) $('#patientDropdown').hide();
    });

    $('#checkConflictBtn').on('click', function () {
        const room=  $('#otRoomId').val(), date=$('#scheduledDate').val(), time=$('#scheduledTime').val(), dur=$('[name=estimated_duration]').val();
        if (!room||!date||!time) { $('#conflictResult').html('<span class="text-warning">Please select OT Room, Date and Time first.</span>'); return; }
        $(this).prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i>Checking…');
        $.post(CONFLICT_URL, {csrf_token:CSRF_TOKEN,ot_room_id:room,scheduled_date:date,scheduled_time:time,estimated_duration:dur,exclude_id:EDIT_ID}, function (d) {
            if (d.conflict) $('#conflictResult').html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>'+d.message+'</span>');
            else $('#conflictResult').html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>'+d.message+'</span>');
        },'json').always(function(){$('#checkConflictBtn').prop('disabled',false).html('<i class="fas fa-exclamation-triangle me-1"></i>Check for Conflicts');});
    });

    $('#bookingForm').on('submit', function (e) {
        e.preventDefault();
        if (!$('#patientId').val()) { Swal.fire('Validation','Please select a patient.','warning'); return; }
        if (!this.checkValidity()) { this.classList.add('was-validated'); return; }
        const btn=$(this).find('[type=submit]');
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i>Updating…');
        $.post(SAVE_URL, $(this).serialize(), function (res) {
            if (res.success) Swal.fire({icon:'success',title:'Updated',text:res.message,confirmButtonText:'View List'}).then(function(){window.location.href='<?= BASE_URL ?>booking_list.php';});
            else { Swal.fire('Error',res.message,'error'); btn.prop('disabled',false).html('<i class="fas fa-save me-1"></i>Update Booking'); }
        },'json').fail(function(){ Swal.fire('Error','Server error.','error'); btn.prop('disabled',false).html('<i class="fas fa-save me-1"></i>Update Booking'); });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
