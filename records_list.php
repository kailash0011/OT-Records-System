<?php
/**
 * OT Records List – forwards to the records module.
 * OT Records Management System
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$qs = $_SERVER['QUERY_STRING'] ?? '';
$target = BASE_URL . 'modules/records/index.php' . ($qs ? '?' . $qs : '');
header('Location: ' . $target);
exit;

