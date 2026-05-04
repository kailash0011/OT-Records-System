<?php
/**
 * Logout
 * OT Records Management System
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logout();

// Redirect with a success flash (session is destroyed so use a query param instead)
header('Location: ' . BASE_URL . 'login.php?logged_out=1');
exit;
