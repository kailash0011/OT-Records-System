<?php
/**
 * View OT Record – forwards to the records module.
 * Accepts either ?id=<numeric_id> or ?id=<record_number>.
 * OT Records Management System
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$raw = trim($_GET['id'] ?? '');

if ($raw === '') {
    header('Location: ' . BASE_URL . 'records_list.php');
    exit;
}

// If the value is a plain integer forward directly
if (ctype_digit($raw)) {
    header('Location: ' . BASE_URL . 'modules/records/view.php?id=' . (int)$raw);
    exit;
}

// Otherwise treat it as a record_number string and look up the numeric id
try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM ot_records WHERE record_number = :rn LIMIT 1");
    $stmt->execute([':rn' => $raw]);
    $row = $stmt->fetch();
    if ($row) {
        header('Location: ' . BASE_URL . 'modules/records/view.php?id=' . (int)$row['id']);
        exit;
    }
} catch (PDOException $e) {
    error_log('record_view redirect error: ' . $e->getMessage());
}

// Not found – go to the list
$_SESSION['flash_error'] = 'Record not found.';
header('Location: ' . BASE_URL . 'records_list.php');
exit;

