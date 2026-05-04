<?php
/**
 * Session Management
 * OT Records Management System
 */

// Prevent direct execution without the application bootstrap
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/constants.php';
}

// -------------------------------------------------------
// Configure secure session cookie parameters BEFORE start
// -------------------------------------------------------
$cookieParams = [
    'lifetime' => 0,               // Session cookie; expires on browser close
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict',
];
session_set_cookie_params($cookieParams);
session_name('OTMS_SESSION');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------
// Session timeout check
// -------------------------------------------------------
$timeoutSeconds = (defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 30) * 60;

if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $timeoutSeconds) {
        // Session expired – destroy and redirect
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['timeout_message'] = 'Your session has expired. Please log in again.';
        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/') . 'login.php');
        exit;
    }
}
$_SESSION['last_activity'] = time();

// -------------------------------------------------------
// Regenerate session ID periodically (every 5 minutes)
// to mitigate session fixation attacks
// -------------------------------------------------------
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif ((time() - $_SESSION['created']) > 300) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// -------------------------------------------------------
// CSRF token – create once per session
// -------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -------------------------------------------------------
// Helper functions
// -------------------------------------------------------

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $base = defined('BASE_URL') ? BASE_URL : '/';
        header('Location: ' . $base . 'login.php');
        exit;
    }
}

/**
 * Require the logged-in user to hold one of the given roles.
 *
 * @param string|array $roles  Single role string or array of acceptable roles.
 */
function requireRole(string|array $roles): void {
    requireLogin();

    $roles      = (array) $roles;
    $userRole   = $_SESSION['user_role'] ?? '';

    if (!in_array($userRole, $roles, true)) {
        http_response_code(403);
        $base = defined('BASE_URL') ? BASE_URL : '/';
        header('Location: ' . $base . 'dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Check whether a user is currently logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Return the CSRF token stored in the session.
 */
function getCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate a hidden HTML input field containing the CSRF token.
 */
function generateCSRFField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCSRFToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate the supplied CSRF token against the session value.
 * Regenerates the token after a successful check.
 *
 * @param string $token  Token submitted by the client.
 * @return bool          True if the token is valid.
 */
function validateCSRF(string $token): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    $valid = hash_equals($_SESSION['csrf_token'], $token);

    // Always regenerate to prevent token reuse
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $valid;
}
