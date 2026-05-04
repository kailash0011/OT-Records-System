<?php
/**
 * Entry point – redirect to dashboard or login.
 * OT Records Management System
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';

if (validateSession()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
