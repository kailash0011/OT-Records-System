<?php
/**
 * OT Records List
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'OT Records';
$pdo       = getDBConnection();

$dateFrom      = trim($_GET['date_from']     ?? '');
$dateTo        = trim($_GET['date_to']       ?? '');
$surgeonFilter = (int)($_GET['surgeon_id']   ?? 0);
$deptFilter    = (int)($_GET['department_id']?? 0);

$where  = ['1=1'];
$params = [];
if ($dateFrom)      { $where[] = 'r.operation_date >= :dfrom'; $params[':dfrom']  = $dateFrom; }
if ($dateTo)        { $where[] = 'r.operation_date <= :dto';   $params[':dto']    = $dateTo; }
if ($surgeonFilter) { $where[] = 'r.surgeon_id = :sid';        $params[':sid']    = $surgeonFilter; }
if ($deptFilter)    { $where[] = 'r.department_id = :did';     $params[':did']    = $deptFilter; }

$stmt = $pdo->prepare(
    "SELECT r.id, r.record_number, r.operation_date, r.procedure_performed,
            r.outcome, r.start_time, r.end_time,
            p.full_name AS patient_name, p.patient_id AS patient_code,
            s.full_name AS surgeon_name,
            d.name      AS department_name
     FROM ot_records r
     JOIN patients    p ON r.patient_id    = p.id
     JOIN surgeons    s ON r.surgeon_id    = s.id
     JOIN departments d ON r.department_id = d.id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY r.operation_date DESC, r.start_time DESC"
);
$stmt->execute($params);
$records = $stmt->fetchAll();

$departments = $pdo->query("SELECT id, name FROM departments WHERE is_active=1 ORDER BY name")->fetchAll();
$surgeons    = $pdo->query("SELECT id, full_name FROM surgeons WHERE is_active=1 ORDER BY full_name")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold"><i class="fas fa-file-medical me-2 text-success"></i>OT Records</span>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>modules/records/add.php" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i>New Record
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light py-2"><span class="fw-semibold small"><i class="fas fa-filter me-1"></i>Filters</span></div>
            <div class="card-body py-3">
                <form method="GET" class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Date From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= sanitize($dateFrom) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Date To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= sanitize($dateTo) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Surgeon</label>
                        <select name="surgeon_id" class="form-select form-select-sm">
                            <option value="">All Surgeons</option>
                            <?php foreach ($surgeons as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $surgeonFilter == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Department</label>
                        <select name="department_id" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="<?= BASE_URL ?>modules/records/index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Records Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-2">
                <span class="fw-semibold small"><i class="fas fa-clipboard-list me-1"></i><?= count($records) ?> Record(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="recordsTable" class="table table-hover table-bordered mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Record #</th>
                                <th>Patient</th>
                                <th>Surgeon</th>
                                <th>Department</th>
                                <th>Operation Date</th>
                                <th>Procedure</th>
                                <th>Outcome</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                            <tr>
                                <td class="fw-semibold text-success"><?= sanitize($r['record_number']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= sanitize($r['patient_name']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem"><?= sanitize($r['patient_code']) ?></div>
                                </td>
                                <td><?= sanitize($r['surgeon_name']) ?></td>
                                <td><?= sanitize($r['department_name']) ?></td>
                                <td><?= formatDate($r['operation_date']) ?></td>
                                <td><?= sanitize(mb_strimwidth($r['procedure_performed'], 0, 50, '…')) ?></td>
                                <td><?= getStatusBadge($r['outcome'] ?? 'Satisfactory') ?></td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= BASE_URL ?>modules/records/view.php?id=<?= $r['id'] ?>"
                                       class="btn btn-outline-info btn-xs py-0 px-1 me-1" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (hasPermission('ot_records','edit')): ?>
                                    <a href="<?= BASE_URL ?>modules/records/edit.php?id=<?= $r['id'] ?>"
                                       class="btn btn-outline-warning btn-xs py-0 px-1 me-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>modules/records/print.php?id=<?= $r['id'] ?>"
                                       class="btn btn-outline-secondary btn-xs py-0 px-1" title="Print" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($records)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
$(document).ready(function(){
    $('#recordsTable').DataTable({
        responsive: true, pageLength: 25,
        order: [[4,'desc']],
        columnDefs: [{orderable:false, targets:7}],
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
