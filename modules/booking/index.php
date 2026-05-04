<?php
/**
 * Booking List
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Case Booking List';
$pdo       = getDBConnection();

// Filters
$statusFilter  = trim($_GET['status']        ?? '');
$deptFilter    = (int)($_GET['department_id'] ?? 0);
$surgeonFilter = (int)($_GET['surgeon_id']    ?? 0);
$dateFrom      = trim($_GET['date_from']      ?? '');
$dateTo        = trim($_GET['date_to']        ?? '');

$where  = ['1=1'];
$params = [];
if ($statusFilter)  { $where[] = 'cb.status = :status';           $params[':status']    = $statusFilter; }
if ($deptFilter)    { $where[] = 'cb.department_id = :dept';      $params[':dept']      = $deptFilter; }
if ($surgeonFilter) { $where[] = 'cb.surgeon_id = :surgeon';      $params[':surgeon']   = $surgeonFilter; }
if ($dateFrom)      { $where[] = 'cb.scheduled_date >= :dfrom';   $params[':dfrom']     = $dateFrom; }
if ($dateTo)        { $where[] = 'cb.scheduled_date <= :dto';     $params[':dto']       = $dateTo; }

$sql = "SELECT cb.id, cb.booking_number, cb.scheduled_date, cb.scheduled_time,
               cb.procedure_name, cb.status, cb.priority, cb.estimated_duration,
               p.full_name AS patient_name, p.patient_id AS patient_code,
               s.full_name AS surgeon_name,
               d.name      AS department_name,
               r.room_name
        FROM case_bookings cb
        JOIN patients    p ON cb.patient_id    = p.id
        JOIN surgeons    s ON cb.surgeon_id    = s.id
        JOIN departments d ON cb.department_id = d.id
        LEFT JOIN ot_rooms r ON cb.ot_room_id  = r.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY cb.scheduled_date DESC, cb.scheduled_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$departments = $pdo->query("SELECT id, name FROM departments WHERE is_active=1 ORDER BY name")->fetchAll();
$surgeons    = $pdo->query("SELECT id, full_name FROM surgeons WHERE is_active=1 ORDER BY full_name")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <!-- Top bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold"><i class="fas fa-calendar-plus me-2 text-primary"></i>Case Booking List</span>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>booking_new.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>New Booking
            </a>
            <a href="<?= BASE_URL ?>booking_calendar.php" class="btn btn-outline-secondary btn-sm ms-1">
                <i class="fas fa-calendar-week me-1"></i>Calendar View
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light py-2">
                <span class="fw-semibold small"><i class="fas fa-filter me-1"></i>Filters</span>
            </div>
            <div class="card-body py-3">
                <form method="GET" action="" class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Date From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                               value="<?= sanitize($dateFrom) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Date To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                               value="<?= sanitize($dateTo) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <?php foreach (['Scheduled','In Progress','Completed','Cancelled','Postponed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Department</label>
                        <select name="department_id" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $deptFilter === (int)$d['id'] ? 'selected' : '' ?>>
                                <?= sanitize($d['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Surgeon</label>
                        <select name="surgeon_id" class="form-select form-select-sm">
                            <option value="">All Surgeons</option>
                            <?php foreach ($surgeons as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $surgeonFilter === (int)$s['id'] ? 'selected' : '' ?>>
                                <?= sanitize($s['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2 mt-1">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i>Apply Filters
                        </button>
                        <a href="<?= BASE_URL ?>booking_list.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <span class="fw-semibold small">
                    <i class="fas fa-list me-1"></i>
                    <?= count($bookings) ?> Booking(s) Found
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="bookingsTable" class="table table-hover table-bordered mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Booking #</th>
                                <th>Patient</th>
                                <th>Surgeon</th>
                                <th>Department</th>
                                <th>OT Room</th>
                                <th>Procedure</th>
                                <th>Date / Time</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="fw-semibold text-primary"><?= sanitize($b['booking_number']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= sanitize($b['patient_name']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem"><?= sanitize($b['patient_code']) ?></div>
                                </td>
                                <td><?= sanitize($b['surgeon_name']) ?></td>
                                <td><?= sanitize($b['department_name']) ?></td>
                                <td><?= sanitize($b['room_name'] ?? '—') ?></td>
                                <td><?= sanitize($b['procedure_name']) ?></td>
                                <td>
                                    <div><?= formatDate($b['scheduled_date']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem"><?= sanitize(substr($b['scheduled_time'],0,5)) ?></div>
                                </td>
                                <td><?= formatDuration((int)$b['estimated_duration']) ?></td>
                                <td><?= getStatusBadge($b['status']) ?></td>
                                <td><?= getPriorityBadge($b['priority']) ?></td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= BASE_URL ?>modules/booking/view.php?id=<?= $b['id'] ?>"
                                       class="btn btn-outline-info btn-xs py-0 px-1 me-1" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (hasPermission('case_bookings','edit')): ?>
                                    <a href="<?= BASE_URL ?>modules/booking/edit.php?id=<?= $b['id'] ?>"
                                       class="btn btn-outline-warning btn-xs py-0 px-1 me-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('case_bookings','delete') && $b['status'] !== 'Cancelled'): ?>
                                    <button type="button"
                                            class="btn btn-outline-danger btn-xs py-0 px-1 btn-cancel-booking"
                                            data-id="<?= $b['id'] ?>"
                                            data-bn="<?= sanitize($b['booking_number']) ?>"
                                            title="Cancel">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                            <tr><td colspan="11" class="text-center text-muted py-4">No bookings found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /container-fluid -->
</div><!-- /page-content-wrapper -->
</div><!-- /wrapper -->

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST" action="<?= BASE_URL ?>modules/booking/delete.php">
                <input type="hidden" name="csrf_token" value="<?= sanitize($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="id" id="cancelBookingId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-times-circle text-danger me-2"></i>Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel booking <strong id="cancelBN"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Cancellation</label>
                        <textarea name="cancel_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#bookingsTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[6, 'desc']],
        columnDefs: [{ orderable: false, targets: 10 }],
    });

    $(document).on('click', '.btn-cancel-booking', function () {
        var id = $(this).data('id');
        var bn = $(this).data('bn');
        $('#cancelBookingId').val(id);
        $('#cancelBN').text(bn);
        new bootstrap.Modal(document.getElementById('cancelModal')).show();
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
