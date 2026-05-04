<?php
/**
 * API: Chart Data
 * Returns monthly + department data as JSON for Chart.js.
 * OT Records Management System
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

try {
    $pdo  = getDBConnection();
    $year = (int) ($_GET['year'] ?? date('Y'));

    // Monthly case counts
    $stmtM = $pdo->prepare(
        "SELECT MONTH(scheduled_date) AS m, COUNT(*) AS cnt
         FROM case_bookings
         WHERE YEAR(scheduled_date) = :y
         GROUP BY m ORDER BY m"
    );
    $stmtM->execute([':y' => $year]);
    $monthlyRaw = $stmtM->fetchAll(PDO::FETCH_KEY_PAIR);

    $monthlyLabels = [];
    $monthlyData   = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthlyLabels[] = date('M', mktime(0, 0, 0, $m, 1));
        $monthlyData[]   = (int) ($monthlyRaw[$m] ?? 0);
    }

    // Department distribution
    $stmtD = $pdo->query(
        "SELECT d.name, COUNT(cb.id) AS cnt
         FROM departments d
         LEFT JOIN case_bookings cb ON cb.department_id = d.id
         GROUP BY d.id ORDER BY cnt DESC"
    );
    $deptRows   = $stmtD->fetchAll();
    $deptLabels = array_column($deptRows, 'name');
    $deptData   = array_map('intval', array_column($deptRows, 'cnt'));
    $deptColors = getChartColors(count($deptLabels));

    echo json_encode([
        'monthly' => [
            'labels' => $monthlyLabels,
            'data'   => $monthlyData,
        ],
        'departments' => [
            'labels' => $deptLabels,
            'data'   => $deptData,
            'colors' => $deptColors,
        ],
        'year' => $year,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('get_chart_data error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}
