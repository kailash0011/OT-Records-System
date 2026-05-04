<?php
/**
 * Dashboard
 * OT Records Management System
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo       = getDBConnection();
$pageTitle = 'Dashboard';
$today     = date('Y-m-d');
$thisMonth = date('Y-m');

// -------------------------------------------------------
// Quick stat queries
// -------------------------------------------------------
function dashboardStat(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

$statTodayCases = dashboardStat(
    $pdo,
    "SELECT COUNT(*) FROM case_bookings WHERE scheduled_date = :d",
    [':d' => $today]
);
$statMonthCases = dashboardStat(
    $pdo,
    "SELECT COUNT(*) FROM case_bookings WHERE DATE_FORMAT(scheduled_date,'%Y-%m') = :m",
    [':m' => $thisMonth]
);
$statTotalPatients = dashboardStat($pdo, "SELECT COUNT(*) FROM patients");
$statPendingBookings = dashboardStat(
    $pdo,
    "SELECT COUNT(*) FROM case_bookings WHERE status = 'Scheduled'"
);
$statCompletedToday = dashboardStat(
    $pdo,
    "SELECT COUNT(*) FROM case_bookings WHERE scheduled_date = :d AND status = 'Completed'",
    [':d' => $today]
);
$statEmergencies = dashboardStat(
    $pdo,
    "SELECT COUNT(*) FROM case_bookings WHERE priority = 'Emergency' AND scheduled_date = :d",
    [':d' => $today]
);

// -------------------------------------------------------
// Today's schedule (upcoming + in-progress)
// -------------------------------------------------------
$stmtSchedule = $pdo->prepare(
    "SELECT cb.booking_number, cb.scheduled_time, cb.procedure_name, cb.status,
            cb.priority, cb.anaesthesia_type, cb.estimated_duration,
            p.full_name  AS patient_name,
            s.full_name  AS surgeon_name,
            r.room_name
     FROM   case_bookings cb
     JOIN   patients    p ON p.id = cb.patient_id
     JOIN   surgeons    s ON s.id = cb.surgeon_id
     LEFT JOIN ot_rooms r ON r.id = cb.ot_room_id
     WHERE  cb.scheduled_date = :d
     ORDER BY cb.scheduled_time ASC
     LIMIT 15"
);
$stmtSchedule->execute([':d' => $today]);
$todaySchedule = $stmtSchedule->fetchAll();

// -------------------------------------------------------
// Recent OT records
// -------------------------------------------------------
$stmtRecent = $pdo->prepare(
    "SELECT ot.record_number, ot.operation_date, ot.procedure_performed,
            ot.outcome, ot.start_time, ot.end_time,
            p.full_name AS patient_name,
            s.full_name AS surgeon_name,
            d.name      AS dept_name
     FROM   ot_records ot
     JOIN   patients    p ON p.id = ot.patient_id
     JOIN   surgeons    s ON s.id = ot.surgeon_id
     JOIN   departments d ON d.id = ot.department_id
     ORDER BY ot.created_at DESC
     LIMIT 8"
);
$stmtRecent->execute();
$recentRecords = $stmtRecent->fetchAll();

// -------------------------------------------------------
// Status summary for today
// -------------------------------------------------------
$stmtStatus = $pdo->prepare(
    "SELECT status, COUNT(*) AS cnt
     FROM   case_bookings
     WHERE  scheduled_date = :d
     GROUP BY status"
);
$stmtStatus->execute([':d' => $today]);
$statusSummary = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

require_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================
     Page layout: sidebar + main content
============================================================ -->
<div class="d-flex" id="wrapper">

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<!-- ============================================================
     Main content
============================================================ -->
<div id="page-content-wrapper" class="flex-grow-1 d-flex flex-column min-vh-100">

    <!-- Top navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom px-3">
        <!-- Sidebar toggle (mobile) -->
        <button class="btn btn-sm btn-outline-secondary me-2" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <span class="navbar-brand fw-semibold mb-0 h1">
            <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
        </span>

        <div class="ms-auto d-flex align-items-center gap-2">
            <!-- Dark mode toggle -->
            <button class="btn btn-sm btn-outline-secondary" id="darkModeToggle" title="Toggle dark mode">
                <i class="fas <?= !empty($_COOKIE['otms_dark_mode']) && $_COOKIE['otms_dark_mode'] === '1' ? 'fa-sun' : 'fa-moon' ?>"></i>
            </button>

            <span class="text-muted small d-none d-md-inline">
                <i class="fas fa-clock me-1"></i><?= date('D, d M Y') ?>
            </span>

            <!-- User dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-1"></i>
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav><!-- /.navbar -->

    <!-- Page body -->
    <main class="flex-grow-1 p-3 p-md-4">

        <!-- ---- Quick stats cards ---- -->
        <div class="row g-3 mb-4">

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-calendar-day fa-lg text-primary"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold" id="stat-today"><?= $statTodayCases ?></div>
                            <div class="text-muted small">Today's Cases</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-calendar-alt fa-lg text-success"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold" id="stat-month"><?= $statMonthCases ?></div>
                            <div class="text-muted small">This Month</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fas fa-procedures fa-lg text-info"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold" id="stat-patients"><?= $statTotalPatients ?></div>
                            <div class="text-muted small">Total Patients</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-clock fa-lg text-warning"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold" id="stat-pending"><?= $statPendingBookings ?></div>
                            <div class="text-muted small">Pending Bookings</div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.row (stats) -->

        <!-- ---- Charts row ---- -->
        <div class="row g-3 mb-4">

            <!-- Monthly cases bar chart -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-chart-bar me-2 text-primary"></i>Monthly Cases (This Year)
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyCasesChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Department-wise pie chart -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-chart-pie me-2 text-success"></i>Dept. Distribution
                        </h6>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="deptPieChart" height="220"></canvas>
                    </div>
                </div>
            </div>

        </div><!-- /.row (charts) -->

        <!-- ---- Status summary + today schedule ---- -->
        <div class="row g-3 mb-4">

            <!-- Status mini-cards -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle me-2 text-secondary"></i>Today's Status Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php
                            $statusList = ['Scheduled', 'In Progress', 'Completed', 'Cancelled', 'Postponed'];
                            foreach ($statusList as $s):
                                $count = $statusSummary[$s] ?? 0;
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <?= getStatusBadge($s) ?>
                                <span class="fw-semibold"><?= $count ?></span>
                            </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="badge bg-danger">Emergency</span>
                                <span class="fw-semibold"><?= $statEmergencies ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Today's schedule table -->
            <div class="col-12 col-md-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-list-alt me-2 text-primary"></i>Today's Schedule
                        </h6>
                        <a href="booking_list.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Procedure</th>
                                        <th>Surgeon</th>
                                        <th>Room</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($todaySchedule)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            No cases scheduled for today.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($todaySchedule as $case): ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars(substr($case['scheduled_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars($case['patient_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-truncate" style="max-width:160px;"
                                            title="<?= htmlspecialchars($case['procedure_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($case['procedure_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars($case['surgeon_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($case['room_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= getStatusBadge($case['status']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.row (status + schedule) -->

        <!-- ---- Recent records ---- -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between">
                <h6 class="fw-semibold mb-0">
                    <i class="fas fa-file-medical me-2 text-info"></i>Recent OT Records
                </h6>
                <a href="records_list.php" class="btn btn-sm btn-outline-info">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Record #</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Procedure</th>
                                <th>Surgeon</th>
                                <th>Dept</th>
                                <th>Outcome</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentRecords)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No OT records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentRecords as $rec): ?>
                            <tr>
                                <td>
                                    <a href="record_view.php?id=<?= urlencode($rec['record_number']) ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($rec['record_number'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= formatDate($rec['operation_date']) ?></td>
                                <td><?= htmlspecialchars($rec['patient_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-truncate" style="max-width:160px;"
                                    title="<?= htmlspecialchars($rec['procedure_performed'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($rec['procedure_performed'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= htmlspecialchars($rec['surgeon_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($rec['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= getStatusBadge($rec['outcome']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /.recent records card -->

    </main><!-- /main -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
// -------------------------------------------------------
// Fetch chart data via inline PHP for initial render
// (a full AJAX refresh is wired up in footer JS)
// -------------------------------------------------------

// Monthly cases this year
$stmtMonthly = $pdo->prepare(
    "SELECT MONTH(scheduled_date) AS m, COUNT(*) AS cnt
     FROM case_bookings
     WHERE YEAR(scheduled_date) = :y
     GROUP BY m ORDER BY m"
);
$stmtMonthly->execute([':y' => date('Y')]);
$monthlyRaw = $stmtMonthly->fetchAll(PDO::FETCH_KEY_PAIR);

$monthlyLabels = [];
$monthlyData   = [];
for ($m = 1; $m <= 12; $m++) {
    $monthlyLabels[] = date('M', mktime(0, 0, 0, $m, 1));
    $monthlyData[]   = (int) ($monthlyRaw[$m] ?? 0);
}

// Dept distribution (all time)
$stmtDept = $pdo->query(
    "SELECT d.name, COUNT(cb.id) AS cnt
     FROM   departments d
     LEFT JOIN case_bookings cb ON cb.department_id = d.id
     GROUP BY d.id
     ORDER BY cnt DESC"
);
$deptRows   = $stmtDept->fetchAll();
$deptLabels = array_column($deptRows, 'name');
$deptData   = array_map('intval', array_column($deptRows, 'cnt'));
$deptColors = getChartColors(count($deptLabels));
?>

<script>
// ---- Monthly cases bar chart ----
(function () {
    const ctx = document.getElementById('monthlyCasesChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels:   <?= json_encode($monthlyLabels) ?>,
            datasets: [{
                label:           'Cases',
                data:            <?= json_encode($monthlyData) ?>,
                backgroundColor: 'rgba(13,110,253,0.7)',
                borderColor:     'rgba(13,110,253,1)',
                borderWidth:     1,
                borderRadius:    4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.raw + ' cases' } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
})();

// ---- Dept pie chart ----
(function () {
    const ctx = document.getElementById('deptPieChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels:   <?= json_encode($deptLabels) ?>,
            datasets: [{
                data:            <?= json_encode($deptData) ?>,
                backgroundColor: <?= json_encode($deptColors) ?>,
                hoverOffset:     6,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } }
            }
        }
    });
})();

// ---- Sidebar toggle (mobile) ----
document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    document.getElementById('sidebar')?.classList.toggle('d-none');
});
</script>

</div><!-- /#page-content-wrapper -->
</div><!-- /#wrapper -->
