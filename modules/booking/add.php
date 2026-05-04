<?php
/**
 * New Case Booking Form
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
if (!hasPermission('case_bookings', 'create')) {
    $_SESSION['flash_error'] = 'You do not have permission to create bookings.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

$pageTitle = 'New Case Booking';
$pdo       = getDBConnection();

$surgeons    = $pdo->query("SELECT id, full_name, department_id FROM surgeons WHERE is_active=1 ORDER BY full_name")->fetchAll();
$departments = $pdo->query("SELECT id, name FROM departments WHERE is_active=1 ORDER BY name")->fetchAll();
$otRooms     = $pdo->query("SELECT id, room_name, room_number FROM ot_rooms WHERE is_active=1 ORDER BY room_name")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold"><i class="fas fa-calendar-plus me-2 text-primary"></i>New Case Booking</span>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>booking_list.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <i class="fas fa-calendar-plus me-2"></i>New Case Booking Form
            </div>
            <div class="card-body">
                <form id="bookingForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= sanitize($_SESSION['csrf_token'] ?? '') ?>">

                    <!-- Patient Search -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1 mb-3">
                                <i class="fas fa-user me-2"></i>Patient Information
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Patient <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="patientSearch" class="form-control"
                                       placeholder="Search by name, patient ID or phone…" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="clearPatient">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="hidden" name="patient_id" id="patientId" required>
                            <div id="patientDropdown" class="list-group position-absolute z-3 w-auto" style="min-width:350px;display:none;max-height:200px;overflow-y:auto;"></div>
                            <div id="patientInfo" class="alert alert-info mt-2 py-2 small" style="display:none;"></div>
                            <div class="invalid-feedback">Please select a patient.</div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1 mb-3">
                                <i class="fas fa-clipboard me-2"></i>Booking Details
                            </h6>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Surgeon <span class="text-danger">*</span></label>
                            <select name="surgeon_id" id="surgeonId" class="form-select select2" required
                                    data-placeholder="Select surgeon…">
                                <option value=""></option>
                                <?php foreach ($surgeons as $s): ?>
                                <option value="<?= $s['id'] ?>" data-dept="<?= $s['department_id'] ?>">
                                    <?= sanitize($s['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a surgeon.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select name="department_id" id="departmentId" class="form-select select2" required
                                    data-placeholder="Select department…">
                                <option value=""></option>
                                <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a department.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">OT Room</label>
                            <select name="ot_room_id" id="otRoomId" class="form-select select2"
                                    data-placeholder="Select OT Room…">
                                <option value=""></option>
                                <?php foreach ($otRooms as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize($r['room_name']) ?> (<?= sanitize($r['room_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Procedure Name <span class="text-danger">*</span></label>
                            <input type="text" name="procedure_name" class="form-control" required
                                   placeholder="e.g. Laparoscopic Cholecystectomy">
                            <div class="invalid-feedback">Please enter the procedure name.</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Procedure Type <span class="text-danger">*</span></label>
                            <select name="procedure_type" class="form-select" required>
                                <option value="Elective">Elective</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Semi-Elective">Semi-Elective</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="Routine">Routine</option>
                                <option value="Urgent">Urgent</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                        </div>
                    </div>

                    <!-- Schedule -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1 mb-3">
                                <i class="fas fa-clock me-2"></i>Schedule
                            </h6>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="text" name="scheduled_date" id="scheduledDate" class="form-control date-picker-future" required
                                   placeholder="Select date">
                            <div class="invalid-feedback">Please select a scheduled date.</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Scheduled Time <span class="text-danger">*</span></label>
                            <input type="text" name="scheduled_time" id="scheduledTime" class="form-control time-picker" required
                                   placeholder="HH:MM">
                            <div class="invalid-feedback">Please select a scheduled time.</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Estimated Duration <span class="text-danger">*</span></label>
                            <select name="estimated_duration" class="form-select" required>
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Anaesthesia Type <span class="text-danger">*</span></label>
                            <select name="anaesthesia_type" class="form-select" required>
                                <option value="General">General</option>
                                <option value="Spinal">Spinal</option>
                                <option value="Epidural">Epidural</option>
                                <option value="Local">Local</option>
                                <option value="Sedation">Sedation</option>
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
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-1 mb-3">
                                <i class="fas fa-notes-medical me-2"></i>Clinical Details
                            </h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Pre-op Diagnosis</label>
                            <textarea name="pre_op_diagnosis" class="form-control" rows="3"
                                      placeholder="Pre-operative diagnosis…"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Special Requirements</label>
                            <textarea name="special_requirements" class="form-control" rows="3"
                                      placeholder="Any special equipment, positioning, or requirements…"></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end border-top pt-3">
                        <a href="<?= BASE_URL ?>booking_list.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script>
const SAVE_URL      = '<?= BASE_URL ?>modules/booking/save.php';
const CONFLICT_URL  = '<?= BASE_URL ?>api/check_conflict.php';
const SEARCH_URL    = '<?= BASE_URL ?>api/search_patient.php';
const LIST_URL      = '<?= BASE_URL ?>booking_list.php';

$(document).ready(function () {

    // Flatpickr for date (min = today)
    flatpickr('#scheduledDate', {
        dateFormat:  'Y-m-d',
        minDate:     'today',
        allowInput:  true,
    });

    // Patient search
    let searchTimer;
    $('#patientSearch').on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val().trim();
        if (q.length < 2) { $('#patientDropdown').hide(); return; }
        searchTimer = setTimeout(function () {
            $.getJSON(SEARCH_URL, {q: q}, function (data) {
                const dd = $('#patientDropdown').empty().show();
                if (!data.length) {
                    dd.append('<a class="list-group-item list-group-item-action text-muted small">No patients found.</a>');
                    return;
                }
                data.forEach(function (p) {
                    dd.append(
                        $('<a class="list-group-item list-group-item-action small"></a>')
                          .html('<strong>' + $('<div>').text(p.full_name).html() + '</strong> '
                              + '<span class="text-muted">' + $('<div>').text(p.patient_id).html() + '</span>'
                              + ' &bull; ' + $('<div>').text(p.gender).html()
                              + (p.blood_group ? ' &bull; ' + $('<div>').text(p.blood_group).html() : ''))
                          .on('click', function () {
                              selectPatient(p);
                          })
                    );
                });
            });
        }, 300);
    });

    function selectPatient(p) {
        $('#patientSearch').val(p.full_name);
        $('#patientId').val(p.id);
        $('#patientDropdown').hide();
        const age = p.dob ? ' | Age: ' + calcAge(p.dob) : '';
        $('#patientInfo')
            .html('<i class="fas fa-user-check me-1"></i>'
                + '<strong>' + $('<div>').text(p.full_name).html() + '</strong>'
                + ' | ID: ' + $('<div>').text(p.patient_id).html()
                + ' | ' + $('<div>').text(p.gender).html()
                + age
                + (p.blood_group ? ' | Blood: ' + $('<div>').text(p.blood_group).html() : ''))
            .show();
    }

    function calcAge(dob) {
        const b = new Date(dob), t = new Date();
        let age = t.getFullYear() - b.getFullYear();
        if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) age--;
        return age + ' yrs';
    }

    $('#clearPatient').on('click', function () {
        $('#patientSearch').val('');
        $('#patientId').val('');
        $('#patientInfo').hide();
        $('#patientDropdown').hide();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#patientSearch, #patientDropdown').length) {
            $('#patientDropdown').hide();
        }
    });

    // Auto-fill department when surgeon selected
    $('#surgeonId').on('change', function () {
        const deptId = $(this).find(':selected').data('dept');
        if (deptId) {
            $('#departmentId').val(deptId).trigger('change.select2');
        }
    });

    // Conflict check
    $('#checkConflictBtn').on('click', function () {
        const room    = $('#otRoomId').val();
        const date    = $('#scheduledDate').val();
        const time    = $('#scheduledTime').val();
        const dur     = $('[name=estimated_duration]').val();

        if (!room || !date || !time) {
            $('#conflictResult').html('<span class="text-warning">Please select OT Room, Date and Time first.</span>');
            return;
        }
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Checking…');

        $.post(CONFLICT_URL, {
            csrf_token:         CSRF_TOKEN,
            ot_room_id:         room,
            scheduled_date:     date,
            scheduled_time:     time,
            estimated_duration: dur,
        }, function (data) {
            if (data.conflict) {
                $('#conflictResult').html(
                    '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>'
                    + 'Conflict: ' + data.message + ' – Booking ' + data.conflicting_booking.booking_number + '</span>'
                );
            } else {
                $('#conflictResult').html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + data.message + '</span>');
            }
        }, 'json').always(function () {
            $('#checkConflictBtn').prop('disabled', false).html('<i class="fas fa-exclamation-triangle me-1"></i>Check for Conflicts');
        });
    });

    // Form submit
    $('#bookingForm').on('submit', function (e) {
        e.preventDefault();
        if (!$('#patientId').val()) {
            Swal.fire('Validation', 'Please search and select a patient.', 'warning');
            return;
        }
        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        const btn = $(this).find('[type=submit]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving…');

        $.post(SAVE_URL, $(this).serialize(), function (res) {
            if (res.success) {
                Swal.fire({
                    icon: 'success', title: 'Booking Created',
                    text: res.message, confirmButtonText: 'View List'
                }).then(function () { window.location.href = LIST_URL; });
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Booking');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Server error. Please try again.', 'error');
            btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Booking');
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
