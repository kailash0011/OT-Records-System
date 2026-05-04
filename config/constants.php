<?php
/**
 * Application Constants
 * OT Records Management System
 */

// -------------------------------------------------------
// Application identity
// -------------------------------------------------------
define('APP_NAME',    'OT Records System');
define('APP_VERSION', '1.0.0');

// -------------------------------------------------------
// Base URL (auto-detect from server variables)
// -------------------------------------------------------
if (!defined('BASE_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST']    ?? 'localhost';
    $script   = $_SERVER['SCRIPT_NAME'] ?? '';
    // Strip filename to get directory path
    $basePath = rtrim(dirname($script), '/\\');
    define('BASE_URL', $scheme . '://' . $host . ($basePath !== '' ? $basePath . '/' : '/'));
}

// -------------------------------------------------------
// File paths
// -------------------------------------------------------
define('ROOT_PATH',   dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('LOG_PATH',    ROOT_PATH . 'logs'    . DIRECTORY_SEPARATOR);

// -------------------------------------------------------
// Security & session
// -------------------------------------------------------
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME',       15);   // minutes
define('SESSION_TIMEOUT',    30);   // minutes
define('REMEMBER_ME_DAYS',   30);   // days

// -------------------------------------------------------
// Date / time formats
// -------------------------------------------------------
define('DATE_FORMAT_DISPLAY', 'd/m/Y');
define('DATE_FORMAT_DB',      'Y-m-d');
define('DATETIME_FORMAT',     'd/m/Y H:i');
define('TIME_FORMAT',         'H:i');

// -------------------------------------------------------
// Pagination
// -------------------------------------------------------
define('DEFAULT_PER_PAGE', 25);

// -------------------------------------------------------
// Status → Bootstrap badge colour mappings
// -------------------------------------------------------
define('STATUS_COLOURS', [
    // Case booking / OT record statuses
    'Scheduled'   => 'primary',
    'In Progress' => 'warning',
    'Completed'   => 'success',
    'Cancelled'   => 'danger',
    'Postponed'   => 'secondary',
    // Outcome
    'Satisfactory' => 'success',
    'Guarded'      => 'warning',
    'Critical'     => 'danger',
    'Deceased'     => 'dark',
    // Generic
    'Active'       => 'success',
    'Inactive'     => 'secondary',
]);

// -------------------------------------------------------
// Priority → Bootstrap colour mappings
// -------------------------------------------------------
define('PRIORITY_COLOURS', [
    'Routine'   => 'secondary',
    'Urgent'    => 'warning',
    'Emergency' => 'danger',
]);

// -------------------------------------------------------
// Allowed file upload types (for documents / logos)
// -------------------------------------------------------
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_UPLOAD_SIZE',     5 * 1024 * 1024); // 5 MB
