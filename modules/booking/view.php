<?php
/**
 * View Case Booking
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'booking_list.php'); exit; }

$pdo  = getDBConnection();
$stmt = $pdo->prepare(
    "SELECT cb.*,
            p.full_name AS patient_name, p.patient_id AS patient_code,
            p.date_of_birth, p.gender, p.blood_group, p.phone AS patient_phone,
            s.full_name AS surgeon_name, s.surgeon_id AS surgeon_code, s.specialization,
            d.name AS department_name,
            r.room_name
     FROM case_bookings cb
     JOIN patients p    ON cb.patient_id    = p.id
     JOIN surgeons s    ON cb.surgeon_id    = s.id
     JOIN departments d ON cb.department_id = d.id
     LEFT JOIN ot_rooms r ON cb.ot_room_id  = r.id
     WHERE cb.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$b = $stmt->fetch();

if (!$b) {
    $_SESSION['flash_error'] = 'Booking not found.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

$pageTitle = 'Booking: ' . $b['booking_number'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold">
            <i class="fas fa-eye me-2 text-info"></i>Booking: <?= sanitize($b['booking_number']) ?>
        </span>
        <div class="ms-auto d-flex gap-2">
            <?php if ($b['status'] !== 'Completed' && $b['status'] !== 'Cancelled' && hasPermission('ot_records','create')): ?>
            <a href="<?= BASE_URL ?>modules/records/add.php?booking_id=<?= $id ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-medical-alt me-1"></i>Create OT Record
            </a>
            <?php endif; ?>
            <?php if (hasPermission('case_bookings','edit')): ?>
            <a href="<?= BASE_URL ?>modules/booking/edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>booking_list.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="row g-3">
            <!-- Status Banner -->
            <div class="col-12">
                <div class="alert alert-<?= STATUS_COLOURS[$b['status']] ?? 'secondary' ?> d-flex align-items-center gap-3 py-2">
                    <i class="fas fa-info-circle fa-lg"></i>
                    <div>
                        <strong><?= sanitize($b['booking_number']) ?></strong>
                        &nbsp;·&nbsp; <?= getStatusBadge($b['status']) ?>
                        &nbsp;·&nbsp; <?= getPriorityBadge($b['priority']) ?>
                        &nbsp;·&nbsp; <span class="text-muted small">Booked: <?= formatDate($b['created_at'], DATETIME_FORMAT) ?></span>
                    </div>
                </div>
            </div>

            <!-- Patient Card -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light fw-semibold py-2">
                        <i class="fas fa-user me-2 text-primary"></i>Patient Information
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="w-40 text-muted">Name</th><td><?= sanitize($b['patient_name']) ?></td></tr>
                            <tr><th class="text-muted">Patient ID</th><td><?= sanitize($b['patient_code']) ?></td></tr>
                            <tr><th class="text-muted">Date of Birth</th><td><?= formatDate($b['date_of_birth']) ?> (Age: <?= calculateAge($b['date_of_birth']) ?>)</td></tr>
                            <tr><th class="text-muted">Gender</th><td><?= sanitize($b['gender']) ?></td></tr>
                            <tr><th class="text-muted">Blood Group</th><td><?= sanitize($b['blood_group'] ?? '—') ?></td></tr>
                            <tr><th class="text-muted">Phone</th><td><?= sanitize($b['patient_phone'] ?? '—') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Booking Card -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light fw-semibold py-2">
                        <i class="fas fa-clipboard me-2 text-primary"></i>Booking Details
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="w-40 text-muted">Surgeon</th><td><?= sanitize($b['surgeon_name']) ?> <small class="text-muted">(<?= sanitize($b['surgeon_code']) ?>)</small></td></tr>
                            <tr><th class="text-muted">Department</th><td><?= sanitize($b['department_name']) ?></td></tr>
                            <tr><th class="text-muted">OT Room</th><td><?= sanitize($b['room_name'] ?? 'Not assigned') ?></td></tr>
                            <tr><th class="text-muted">Procedure</th><td><?= sanitize($b['procedure_name']) ?></td></tr>
                            <tr><th class="text-muted">Type</th><td><?= sanitize($b['procedure_type']) ?></td></tr>
                            <tr><th class="text-muted">Anaesthesia</th><td><?= sanitize($b['anaesthesia_type']) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Schedule Card -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light fw-semibold py-2">
                        <i class="fas fa-clock me-2 text-primary"></i>Schedule
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="w-40 text-muted">Date</th><td><?= formatDate($b['scheduled_date']) ?></td></tr>
                            <tr><th class="text-muted">Time</th><td><?= sanitize(substr($b['scheduled_time'],0,5)) ?></td></tr>
                            <tr><th class="text-muted">Duration</th><td><?= formatDuration((int)$b['estimated_duration']) ?></td></tr>
                            <tr><th class="text-muted">Priority</th><td><?= getPriorityBadge($b['priority']) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Clinical Card -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light fw-semibold py-2">
                        <i class="fas fa-notes-medical me-2 text-primary"></i>Clinical Details
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-1 fw-semibold">Pre-op Diagnosis</p>
                        <p class="mb-3"><?= nl2br(sanitize($b['pre_op_diagnosis'] ?? '—')) ?></p>
                        <p class="small text-muted mb-1 fw-semibold">Special Requirements</p>
                        <p class="mb-0"><?= nl2br(sanitize($b['special_requirements'] ?? '—')) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
