<?php
/**
 * New OT Record Form (Tabbed)
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
if (!hasPermission('ot_records', 'create')) {
    $_SESSION['flash_error'] = 'You do not have permission to create records.';
    header('Location: ' . BASE_URL . 'records_list.php');
    exit;
}

$pageTitle = 'New OT Record';
$pdo       = getDBConnection();

$surgeons    = $pdo->query("SELECT id, full_name, department_id FROM surgeons WHERE is_active=1 ORDER BY full_name")->fetchAll();
$departments = $pdo->query("SELECT id, name FROM departments WHERE is_active=1 ORDER BY name")->fetchAll();
$otRooms     = $pdo->query("SELECT id, room_name, room_number FROM ot_rooms WHERE is_active=1 ORDER BY room_name")->fetchAll();

// Pre-fill from booking if booking_id provided
$bookingData = null;
if (!empty($_GET['booking_id'])) {
    $bid = (int)$_GET['booking_id'];
    $bs  = $pdo->prepare(
        "SELECT cb.*, p.full_name AS patient_name, p.patient_id AS patient_code
         FROM case_bookings cb
         JOIN patients p ON cb.patient_id = p.id
         WHERE cb.id = :id LIMIT 1"
    );
    $bs->execute([':id' => $bid]);
    $bookingData = $bs->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold"><i class="fas fa-file-medical-alt me-2 text-success"></i>New OT Record</span>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>records_list.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white py-2">
                <i class="fas fa-file-medical-alt me-2"></i>New Operative Record
            </div>
            <div class="card-body">
                <form id="recordForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= sanitize($_SESSION['csrf_token'] ?? '') ?>">
                    <?php if ($bookingData): ?>
                    <input type="hidden" name="booking_id" value="<?= (int)$bookingData['id'] ?>">
                    <?php endif; ?>

                    <!-- TABS -->
                    <ul class="nav nav-tabs mb-3" id="recordTabs">
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab"><i class="fas fa-info-circle me-1"></i>Basic Info</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab"><i class="fas fa-users me-1"></i>Surgical Team</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab3" data-bs-toggle="tab"><i class="fas fa-tint me-1"></i>Intraoperative</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab4" data-bs-toggle="tab"><i class="fas fa-flask me-1"></i>Specimens/Implants</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab5" data-bs-toggle="tab"><i class="fas fa-heartbeat me-1"></i>Outcome</a></li>
                    </ul>

                    <div class="tab-content">

                        <!-- TAB 1: Basic Info -->
                        <div class="tab-pane fade show active" id="tab1">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Patient <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" id="patientSearch" class="form-control"
                                               value="<?= $bookingData ? sanitize($bookingData['patient_name']) : '' ?>"
                                               placeholder="Search by name, ID or phone…" autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary" id="clearPatient"><i class="fas fa-times"></i></button>
                                    </div>
                                    <input type="hidden" name="patient_id" id="patientId"
                                           value="<?= $bookingData ? (int)$bookingData['patient_id'] : '' ?>" required>
                                    <div id="patientDropdown" class="list-group position-absolute z-3" style="min-width:350px;display:none;max-height:200px;overflow-y:auto;"></div>
                                    <div id="patientInfo" class="alert alert-info mt-2 py-2 small" <?= $bookingData ? '' : 'style="display:none;"' ?>>
                                        <?php if ($bookingData): ?>
                                        <i class="fas fa-user-check me-1"></i>
                                        <strong><?= sanitize($bookingData['patient_name']) ?></strong> | <?= sanitize($bookingData['patient_code']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">Surgeon <span class="text-danger">*</span></label>
                                    <select name="surgeon_id" id="surgeonId" class="form-select select2" required data-placeholder="Select surgeon…">
                                        <option value=""></option>
                                        <?php foreach ($surgeons as $s): ?>
                                        <option value="<?= $s['id'] ?>" data-dept="<?= $s['department_id'] ?>"
                                            <?= $bookingData && $bookingData['surgeon_id'] == $s['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($s['full_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                    <select name="department_id" id="departmentId" class="form-select select2" required data-placeholder="Select…">
                                        <option value=""></option>
                                        <?php foreach ($departments as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= $bookingData && $bookingData['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($d['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">OT Room</label>
                                    <select name="ot_room_id" class="form-select select2" data-placeholder="Select room…">
                                        <option value=""></option>
                                        <?php foreach ($otRooms as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= $bookingData && $bookingData['ot_room_id'] == $r['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($r['room_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">Operation Date <span class="text-danger">*</span></label>
                                    <input type="date" name="operation_date" class="form-control" required
                                           value="<?= $bookingData ? sanitize($bookingData['scheduled_date']) : date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">Start Time</label>
                                    <input type="text" name="start_time" class="form-control time-picker"
                                           value="<?= $bookingData ? sanitize(substr($bookingData['scheduled_time'],0,5)) : '' ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">End Time</label>
                                    <input type="text" name="end_time" class="form-control time-picker">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">Anaesthesia Type</label>
                                    <select name="anaesthesia_type" class="form-select">
                                        <?php foreach (['General','Spinal','Epidural','Local','Sedation'] as $a): ?>
                                        <option value="<?= $a ?>" <?= $bookingData && $bookingData['anaesthesia_type'] === $a ? 'selected' : '' ?>><?= $a ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Procedure Performed <span class="text-danger">*</span></label>
                                    <textarea name="procedure_performed" class="form-control" rows="3" required
                                              placeholder="Describe the procedure(s) performed…"><?= $bookingData ? sanitize($bookingData['procedure_name']) : '' ?></textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Post-op Diagnosis</label>
                                    <textarea name="post_op_diagnosis" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-primary btn-sm" id="goTab2">Next <i class="fas fa-arrow-right ms-1"></i></button>
                            </div>
                        </div>

                        <!-- TAB 2: Surgical Team -->
                        <div class="tab-pane fade" id="tab2">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Anaesthetist Name</label>
                                    <input type="text" name="anaesthetist_name" class="form-control" placeholder="Dr. …">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Assistant Surgeon</label>
                                    <input type="text" name="assistant_surgeon" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Scrub Nurse</label>
                                    <input type="text" name="scrub_nurse" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Circulating Nurse</label>
                                    <input type="text" name="circulating_nurse" class="form-control">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="goTab1Back"><i class="fas fa-arrow-left me-1"></i>Back</button>
                                <button type="button" class="btn btn-primary btn-sm" id="goTab3">Next <i class="fas fa-arrow-right ms-1"></i></button>
                            </div>
                        </div>

                        <!-- TAB 3: Intraoperative -->
                        <div class="tab-pane fade" id="tab3">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Blood Loss (ml)</label>
                                    <input type="number" name="blood_loss_ml" class="form-control" value="0" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Urine Output (ml)</label>
                                    <input type="number" name="urine_output_ml" class="form-control" value="0" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Fluid Input (ml)</label>
                                    <input type="number" name="fluid_input_ml" class="form-control" value="0" min="0">
                                </div>
                            </div>

                            <h6 class="fw-bold mt-3">Blood Transfusions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr><th>Blood Type</th><th>Units</th><th>Time</th><th>Reaction</th><th>Notes</th><th></th></tr>
                                    </thead>
                                    <tbody id="transfusionRows"></tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addTransfusion">
                                <i class="fas fa-plus me-1"></i>Add Transfusion
                            </button>

                            <h6 class="fw-bold mt-2">Catheters / Lines</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr><th>Type</th><th>Size</th><th>Insertion</th><th>Removal</th><th>Site</th><th>Notes</th><th></th></tr>
                                    </thead>
                                    <tbody id="catheterRows"></tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addCatheter">
                                <i class="fas fa-plus me-1"></i>Add Catheter
                            </button>

                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="goTab2Back"><i class="fas fa-arrow-left me-1"></i>Back</button>
                                <button type="button" class="btn btn-primary btn-sm" id="goTab4">Next <i class="fas fa-arrow-right ms-1"></i></button>
                            </div>
                        </div>

                        <!-- TAB 4: Specimens/Implants -->
                        <div class="tab-pane fade" id="tab4">
                            <h6 class="fw-bold">Specimens Sent to Lab</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr><th>Type</th><th>Site</th><th>Sent to Lab</th><th>Lab Ref</th><th>Notes</th><th></th></tr>
                                    </thead>
                                    <tbody id="specimenRows"></tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addSpecimen">
                                <i class="fas fa-plus me-1"></i>Add Specimen
                            </button>

                            <h6 class="fw-bold mt-2">Implants Used</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr><th>Name</th><th>Brand</th><th>Serial #</th><th>Lot #</th><th>Expiry</th><th>Notes</th><th></th></tr>
                                    </thead>
                                    <tbody id="implantRows"></tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addImplant">
                                <i class="fas fa-plus me-1"></i>Add Implant
                            </button>

                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="goTab3Back"><i class="fas fa-arrow-left me-1"></i>Back</button>
                                <button type="button" class="btn btn-primary btn-sm" id="goTab5">Next <i class="fas fa-arrow-right ms-1"></i></button>
                            </div>
                        </div>

                        <!-- TAB 5: Outcome -->
                        <div class="tab-pane fade" id="tab5">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Wound Classification</label>
                                    <select name="wound_classification" class="form-select">
                                        <option value="Clean">Clean</option>
                                        <option value="Clean-Contaminated">Clean-Contaminated</option>
                                        <option value="Contaminated">Contaminated</option>
                                        <option value="Dirty">Dirty</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Outcome</label>
                                    <select name="outcome" class="form-select">
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Guarded">Guarded</option>
                                        <option value="Critical">Critical</option>
                                        <option value="Deceased">Deceased</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold d-block">ICU Required</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="icu_required" id="icuRequired" value="1">
                                        <label class="form-check-label" for="icuRequired">Yes, transfer to ICU</label>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Complications</label>
                                    <textarea name="complications" class="form-control" rows="3"
                                              placeholder="Describe any intraoperative or immediate post-op complications…"></textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Post-op Instructions</label>
                                    <textarea name="post_op_instructions" class="form-control" rows="3"
                                              placeholder="Pain management, diet, wound care, follow-up…"></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 border-top pt-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="goTab4Back"><i class="fas fa-arrow-left me-1"></i>Back</button>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>records_list.php" class="btn btn-secondary btn-sm">Cancel</a>
                                    <button type="submit" name="action" value="save" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Save Record
                                    </button>
                                    <button type="submit" name="action" value="save_print" class="btn btn-outline-success">
                                        <i class="fas fa-print me-1"></i>Save &amp; Print
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div><!-- /tab-content -->
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script>
const SAVE_URL   = '<?= BASE_URL ?>modules/records/save.php';
const SEARCH_URL = '<?= BASE_URL ?>api/search_patient.php';
const PRINT_BASE = '<?= BASE_URL ?>modules/records/print.php';

function switchTab(targetId) {
    const el = document.querySelector('a[href="' + targetId + '"]');
    if (el) new bootstrap.Tab(el).show();
}

function addDynRow(tbodyId, rowHtml) {
    document.getElementById(tbodyId).insertAdjacentHTML('beforeend', rowHtml);
    document.querySelectorAll('.time-picker-dyn:not([data-fp])').forEach(function(el) {
        flatpickr(el, {enableTime:true, noCalendar:true, dateFormat:'H:i', time_24hr:true});
        el.setAttribute('data-fp','1');
    });
}

$(document).ready(function () {
    // Tab nav buttons
    $('#goTab2').on('click', function(){ switchTab('#tab2'); });
    $('#goTab3').on('click', function(){ switchTab('#tab3'); });
    $('#goTab4').on('click', function(){ switchTab('#tab4'); });
    $('#goTab5').on('click', function(){ switchTab('#tab5'); });
    $('#goTab1Back').on('click', function(){ switchTab('#tab1'); });
    $('#goTab2Back').on('click', function(){ switchTab('#tab2'); });
    $('#goTab3Back').on('click', function(){ switchTab('#tab3'); });
    $('#goTab4Back').on('click', function(){ switchTab('#tab4'); });

    // Patient search
    let searchTimer;
    $('#patientSearch').on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val().trim();
        if (q.length < 2) { $('#patientDropdown').hide(); return; }
        searchTimer = setTimeout(function () {
            $.getJSON(SEARCH_URL, {q: q}, function (data) {
                const dd = $('#patientDropdown').empty().show();
                if (!data.length) { dd.append('<a class="list-group-item small text-muted">No patients found.</a>'); return; }
                data.forEach(function (p) {
                    dd.append($('<a class="list-group-item list-group-item-action small"></a>')
                        .html('<strong>' + $('<div>').text(p.full_name).html() + '</strong> ' + $('<div>').text(p.patient_id).html())
                        .on('click', function () {
                            $('#patientSearch').val(p.full_name);
                            $('#patientId').val(p.id);
                            $('#patientInfo').html('<i class="fas fa-user-check me-1"></i><strong>' + $('<div>').text(p.full_name).html() + '</strong> | ' + $('<div>').text(p.patient_id).html()).show();
                            $('#patientDropdown').hide();
                        }));
                });
            });
        }, 300);
    });
    $('#clearPatient').on('click', function () { $('#patientSearch, #patientId').val(''); $('#patientInfo').hide(); $('#patientDropdown').hide(); });
    $(document).on('click', function (e) { if (!$(e.target).closest('#patientSearch, #patientDropdown').length) $('#patientDropdown').hide(); });

    $('#surgeonId').on('change', function () {
        const d = $(this).find(':selected').data('dept');
        if (d) $('#departmentId').val(d).trigger('change.select2');
    });

    // Dynamic row add buttons
    $('#addTransfusion').on('click', function () {
        const i = Date.now();
        addDynRow('transfusionRows',
            '<tr>' +
            '<td><input type="text" name="transfusions['+i+'][blood_type]" class="form-control form-control-sm" placeholder="A+"></td>' +
            '<td><input type="number" name="transfusions['+i+'][units_transfused]" class="form-control form-control-sm" min="0" step="0.5"></td>' +
            '<td><input type="text" name="transfusions['+i+'][transfusion_time]" class="form-control form-control-sm time-picker-dyn"></td>' +
            '<td class="text-center"><input type="checkbox" name="transfusions['+i+'][reaction]" value="1"></td>' +
            '<td><input type="text" name="transfusions['+i+'][notes]" class="form-control form-control-sm"></td>' +
            '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'tr\').remove()"><i class="fas fa-times"></i></button></td>' +
            '</tr>'
        );
    });
    $('#addCatheter').on('click', function () {
        const i = Date.now();
        addDynRow('catheterRows',
            '<tr>' +
            '<td><input type="text" name="catheters['+i+'][catheter_type]" class="form-control form-control-sm" placeholder="Foley, CVC…"></td>' +
            '<td><input type="text" name="catheters['+i+'][size]" class="form-control form-control-sm" placeholder="14 Fr"></td>' +
            '<td><input type="text" name="catheters['+i+'][insertion_time]" class="form-control form-control-sm time-picker-dyn"></td>' +
            '<td><input type="text" name="catheters['+i+'][removal_time]" class="form-control form-control-sm time-picker-dyn"></td>' +
            '<td><input type="text" name="catheters['+i+'][site]" class="form-control form-control-sm"></td>' +
            '<td><input type="text" name="catheters['+i+'][notes]" class="form-control form-control-sm"></td>' +
            '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'tr\').remove()"><i class="fas fa-times"></i></button></td>' +
            '</tr>'
        );
    });
    $('#addSpecimen').on('click', function () {
        const i = Date.now();
        addDynRow('specimenRows',
            '<tr>' +
            '<td><input type="text" name="specimens['+i+'][specimen_type]" class="form-control form-control-sm" placeholder="Tissue…"></td>' +
            '<td><input type="text" name="specimens['+i+'][specimen_site]" class="form-control form-control-sm"></td>' +
            '<td class="text-center"><input type="checkbox" name="specimens['+i+'][sent_to_lab]" value="1" checked></td>' +
            '<td><input type="text" name="specimens['+i+'][lab_reference]" class="form-control form-control-sm"></td>' +
            '<td><input type="text" name="specimens['+i+'][notes]" class="form-control form-control-sm"></td>' +
            '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'tr\').remove()"><i class="fas fa-times"></i></button></td>' +
            '</tr>'
        );
    });
    $('#addImplant').on('click', function () {
        const i = Date.now();
        addDynRow('implantRows',
            '<tr>' +
            '<td><input type="text" name="implants['+i+'][implant_name]" class="form-control form-control-sm" placeholder="Name…"></td>' +
            '<td><input type="text" name="implants['+i+'][brand]" class="form-control form-control-sm"></td>' +
            '<td><input type="text" name="implants['+i+'][serial_number]" class="form-control form-control-sm"></td>' +
            '<td><input type="text" name="implants['+i+'][lot_number]" class="form-control form-control-sm"></td>' +
            '<td><input type="date" name="implants['+i+'][expiry_date]" class="form-control form-control-sm"></td>' +
            '<td><input type="text" name="implants['+i+'][notes]" class="form-control form-control-sm"></td>' +
            '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'tr\').remove()"><i class="fas fa-times"></i></button></td>' +
            '</tr>'
        );
    });

    // Form submit
    $('#recordForm').on('submit', function (e) {
        e.preventDefault();
        const printAfter = $(document.activeElement).val() === 'save_print';
        if (!$('#patientId').val()) { Swal.fire('Validation', 'Please select a patient.', 'warning'); switchTab('#tab1'); return; }
        if (!this.checkValidity()) { this.classList.add('was-validated'); switchTab('#tab1'); return; }
        const btn = $(this).find('[type=submit]').prop('disabled', true);

        $.post(SAVE_URL, $(this).serialize(), function (res) {
            if (res.success) {
                Swal.fire({icon: 'success', title: 'Saved', text: res.message}).then(function () {
                    if (printAfter) window.open(PRINT_BASE + '?id=' + res.record_id, '_blank');
                    window.location.href = '<?= BASE_URL ?>records_list.php';
                });
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.prop('disabled', false);
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Server error. Please try again.', 'error');
            btn.prop('disabled', false);
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
