<?php
/**
 * New OT Record – forwards to the records module.
 * OT Records Management System
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Pass through any query-string parameters (e.g. booking_id)
$qs = $_SERVER['QUERY_STRING'] ?? '';
$target = BASE_URL . 'modules/records/add.php' . ($qs ? '?' . $qs : '');
header('Location: ' . $target);
exit;

