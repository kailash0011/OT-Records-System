<?php
/**
 * View OT Record
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'records_list.php'); exit; }

$pdo  = getDBConnection();
$stmt = $pdo->prepare(
    "SELECT r.*,
            p.full_name AS patient_name, p.patient_id AS patient_code,
            p.date_of_birth, p.gender, p.blood_group, p.phone AS patient_phone,
            p.allergies,
            s.full_name AS surgeon_name, s.surgeon_id AS surgeon_code,
            d.name AS department_name,
            room.room_name
     FROM ot_records r
     JOIN patients p ON r.patient_id = p.id
     JOIN surgeons s ON r.surgeon_id = s.id
     JOIN departments d ON r.department_id = d.id
     LEFT JOIN ot_rooms room ON r.ot_room_id = room.id
     WHERE r.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$rec = $stmt->fetch();

if (!$rec) {
    $_SESSION['flash_error'] = 'Record not found.';
    header('Location: ' . BASE_URL . 'records_list.php');
    exit;
}

// Fetch sub-tables
$transfusions = $pdo->prepare("SELECT * FROM blood_transfusions WHERE record_id=:id");
$transfusions->execute([':id' => $id]);
$transfusions = $transfusions->fetchAll();

$catheters = $pdo->prepare("SELECT * FROM catheters WHERE record_id=:id");
$catheters->execute([':id' => $id]);
$catheters = $catheters->fetchAll();

$specimens = $pdo->prepare("SELECT * FROM specimens WHERE record_id=:id");
$specimens->execute([':id' => $id]);
$specimens = $specimens->fetchAll();

$implants = $pdo->prepare("SELECT * FROM implants WHERE record_id=:id");
$implants->execute([':id' => $id]);
$implants = $implants->fetchAll();

$pageTitle = 'Record: ' . $rec['record_number'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold">
            <i class="fas fa-file-medical me-2 text-success"></i>Record: <?= sanitize($rec['record_number']) ?>
        </span>
        <div class="ms-auto d-flex gap-2">
            <a href="<?= BASE_URL ?>modules/records/print.php?id=<?= $id ?>" target="_blank"
               class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-print me-1"></i>Print
            </a>
            <?php if (hasPermission('ot_records','edit')): ?>
            <a href="<?= BASE_URL ?>modules/records/edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>records_list.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">

        <!-- Patient Info Card -->
        <div class="card shadow-sm mb-3 border-start border-success border-4">
            <div class="card-body py-3">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="text-muted small">Patient</div>
                        <div class="fw-bold"><?= sanitize($rec['patient_name']) ?></div>
                        <div class="text-muted small"><?= sanitize($rec['patient_code']) ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">DOB / Age</div>
                        <div><?= formatDate($rec['date_of_birth']) ?></div>
                        <div class="text-muted small">Age: <?= calculateAge($rec['date_of_birth']) ?></div>
                    </div>
                    <div class="col-md-1">
                        <div class="text-muted small">Gender</div>
                        <div><?= sanitize($rec['gender']) ?></div>
                    </div>
                    <div class="col-md-1">
                        <div class="text-muted small">Blood</div>
                        <div><?= sanitize($rec['blood_group'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Allergies</div>
                        <div class="text-danger small"><?= sanitize($rec['allergies'] ?? 'None') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Record #</div>
                        <div class="fw-bold text-success"><?= sanitize($rec['record_number']) ?></div>
                        <div class="text-muted small"><?= formatDate($rec['operation_date']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs (read-only) -->
        <div class="card shadow-sm">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><a class="nav-link active" href="#vTab1" data-bs-toggle="tab"><i class="fas fa-info-circle me-1"></i>Basic Info</a></li>
                    <li class="nav-item"><a class="nav-link" href="#vTab2" data-bs-toggle="tab"><i class="fas fa-users me-1"></i>Surgical Team</a></li>
                    <li class="nav-item"><a class="nav-link" href="#vTab3" data-bs-toggle="tab"><i class="fas fa-tint me-1"></i>Intraoperative</a></li>
                    <li class="nav-item"><a class="nav-link" href="#vTab4" data-bs-toggle="tab"><i class="fas fa-flask me-1"></i>Specimens/Implants</a></li>
                    <li class="nav-item"><a class="nav-link" href="#vTab5" data-bs-toggle="tab"><i class="fas fa-heartbeat me-1"></i>Outcome</a></li>
                </ul>
                <div class="tab-content">

                    <!-- Basic Info -->
                    <div class="tab-pane fade show active" id="vTab1">
                        <table class="table table-sm table-borderless">
                            <tr><th class="w-25 text-muted">Surgeon</th><td><?= sanitize($rec['surgeon_name']) ?> <small class="text-muted">(<?= sanitize($rec['surgeon_code']) ?>)</small></td></tr>
                            <tr><th class="text-muted">Department</th><td><?= sanitize($rec['department_name']) ?></td></tr>
                            <tr><th class="text-muted">OT Room</th><td><?= sanitize($rec['room_name'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Operation Date</th><td><?= formatDate($rec['operation_date']) ?></td></tr>
                            <tr><th class="text-muted">Start / End Time</th>
                                <td><?= sanitize(substr($rec['start_time'] ?? '', 0, 5)) ?> – <?= sanitize(substr($rec['end_time'] ?? '', 0, 5)) ?></td></tr>
                            <tr><th class="text-muted">Anaesthesia</th><td><?= sanitize($rec['anaesthesia_type'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Procedure Performed</th><td><?= nl2br(sanitize($rec['procedure_performed'])) ?></td></tr>
                            <tr><th class="text-muted">Post-op Diagnosis</th><td><?= nl2br(sanitize($rec['post_op_diagnosis'] ?? '—')) ?></td></tr>
                            <?php
                            $caseTypes = [];
                            if (!empty($rec['case_type_echs']))  $caseTypes[] = 'ECHS';
                            if (!empty($rec['case_type_ssf']))   $caseTypes[] = 'SSF';
                            if (!empty($rec['case_type_mlc']))   $caseTypes[] = 'MLC';
                            if (!empty($rec['case_type_other'])) {
                                $otherLabel = 'Other';
                                if (!empty($rec['case_type_other_text'])) {
                                    $otherLabel .= ' (' . sanitize($rec['case_type_other_text']) . ')';
                                }
                                $caseTypes[] = $otherLabel;
                            }
                            ?>
                            <?php if ($caseTypes): ?>
                            <tr><th class="text-muted">Case Type</th>
                                <td><?php foreach ($caseTypes as $ct): ?><span class="badge bg-secondary me-1"><?= $ct ?></span><?php endforeach; ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <!-- Surgical Team -->
                    <div class="tab-pane fade" id="vTab2">
                        <table class="table table-sm table-borderless">
                            <tr><th class="w-25 text-muted">Anaesthetist</th><td><?= sanitize($rec['anaesthetist_name'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Scrub Nurse</th><td><?= sanitize($rec['scrub_nurse'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Circulating Nurse</th><td><?= sanitize($rec['circulating_nurse'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Assistant Surgeon</th><td><?= sanitize($rec['assistant_surgeon'] ?? '—') ?></td></tr>
                        </table>
                    </div>

                    <!-- Intraoperative -->
                    <div class="tab-pane fade" id="vTab3">
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Blood Loss:</strong> <?= (int)$rec['blood_loss_ml'] ?> ml</div>
                            <div class="col-md-4"><strong>Urine Output:</strong> <?= (int)$rec['urine_output_ml'] ?> ml</div>
                            <div class="col-md-4"><strong>Fluid Input:</strong> <?= (int)$rec['fluid_input_ml'] ?> ml</div>
                        </div>
                        <?php if ($transfusions): ?>
                        <h6 class="fw-bold">Blood Transfusions</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Type</th><th>Units</th><th>Time</th><th>Reaction</th><th>Notes</th></tr></thead>
                            <tbody>
                                <?php foreach ($transfusions as $t): ?>
                                <tr>
                                    <td><?= sanitize($t['blood_type']) ?></td>
                                    <td><?= sanitize($t['units_transfused']) ?></td>
                                    <td><?= sanitize(substr($t['transfusion_time'] ?? '', 0, 5)) ?></td>
                                    <td><?= $t['reaction'] ? '<span class="badge bg-danger">Yes</span>' : '<span class="badge bg-success">No</span>' ?></td>
                                    <td><?= sanitize($t['notes'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted small">No transfusions recorded.</p>
                        <?php endif; ?>

                        <?php if ($catheters): ?>
                        <h6 class="fw-bold mt-3">Catheters / Lines</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Type</th><th>Size</th><th>Insertion</th><th>Removal</th><th>Site</th><th>Notes</th></tr></thead>
                            <tbody>
                                <?php foreach ($catheters as $c): ?>
                                <tr>
                                    <td><?= sanitize($c['catheter_type']) ?></td>
                                    <td><?= sanitize($c['size'] ?? '') ?></td>
                                    <td><?= sanitize(substr($c['insertion_time'] ?? '', 0, 5)) ?></td>
                                    <td><?= sanitize(substr($c['removal_time'] ?? '', 0, 5)) ?></td>
                                    <td><?= sanitize($c['site'] ?? '') ?></td>
                                    <td><?= sanitize($c['notes'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <!-- Specimens/Implants -->
                    <div class="tab-pane fade" id="vTab4">
                        <?php if ($specimens): ?>
                        <h6 class="fw-bold">Specimens</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Type</th><th>Site</th><th>Sent to Lab</th><th>Lab Ref</th><th>Notes</th></tr></thead>
                            <tbody>
                                <?php foreach ($specimens as $s): ?>
                                <tr>
                                    <td><?= sanitize($s['specimen_type']) ?></td>
                                    <td><?= sanitize($s['specimen_site'] ?? '') ?></td>
                                    <td><?= $s['sent_to_lab'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                    <td><?= sanitize($s['lab_reference'] ?? '') ?></td>
                                    <td><?= sanitize($s['notes'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?><p class="text-muted small">No specimens recorded.</p><?php endif; ?>

                        <?php if ($implants): ?>
                        <h6 class="fw-bold mt-3">Implants</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Name</th><th>Brand</th><th>Serial #</th><th>Lot #</th><th>Expiry</th><th>Notes</th></tr></thead>
                            <tbody>
                                <?php foreach ($implants as $i): ?>
                                <tr>
                                    <td><?= sanitize($i['implant_name']) ?></td>
                                    <td><?= sanitize($i['brand'] ?? '') ?></td>
                                    <td><?= sanitize($i['serial_number'] ?? '') ?></td>
                                    <td><?= sanitize($i['lot_number'] ?? '') ?></td>
                                    <td><?= $i['expiry_date'] ? formatDate($i['expiry_date']) : '—' ?></td>
                                    <td><?= sanitize($i['notes'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?><p class="text-muted small">No implants recorded.</p><?php endif; ?>
                    </div>

                    <!-- Outcome -->
                    <div class="tab-pane fade" id="vTab5">
                        <table class="table table-sm table-borderless">
                            <tr><th class="w-25 text-muted">Wound Classification</th><td><?= sanitize($rec['wound_classification'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Outcome</th><td><?= getStatusBadge($rec['outcome'] ?? 'Satisfactory') ?></td></tr>
                            <tr><th class="text-muted">ICU Required</th><td><?= $rec['icu_required'] ? '<span class="badge bg-warning text-dark">Yes</span>' : '<span class="badge bg-success">No</span>' ?></td></tr>
                            <tr><th class="text-muted">Complications</th><td><?= nl2br(sanitize($rec['complications'] ?? '—')) ?></td></tr>
                            <tr><th class="text-muted">Post-op Instructions</th><td><?= nl2br(sanitize($rec['post_op_instructions'] ?? '—')) ?></td></tr>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
