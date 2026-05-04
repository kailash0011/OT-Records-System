<?php
/**
 * Authentication Functions
 * OT Records Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';

// -------------------------------------------------------
// Permission map: module => [role => [allowed actions]]
// -------------------------------------------------------
const PERMISSIONS = [
    'case_bookings' => [
        'admin'   => ['view', 'create', 'edit', 'delete', 'export'],
        'surgeon' => ['view', 'create', 'edit'],
        'staff'   => ['view', 'create', 'edit'],
        'viewer'  => ['view'],
    ],
    'ot_records' => [
        'admin'   => ['view', 'create', 'edit', 'delete', 'export'],
        'surgeon' => ['view', 'create', 'edit'],
        'staff'   => ['view', 'create', 'edit'],
        'viewer'  => ['view'],
    ],
    'patients' => [
        'admin'   => ['view', 'create', 'edit', 'delete', 'export'],
        'surgeon' => ['view', 'create', 'edit'],
        'staff'   => ['view', 'create', 'edit'],
        'viewer'  => ['view'],
    ],
    'reports' => [
        'admin'   => ['view', 'export'],
        'surgeon' => ['view'],
        'staff'   => ['view'],
        'viewer'  => ['view'],
    ],
    'users' => [
        'admin'   => ['view', 'create', 'edit', 'delete'],
        'surgeon' => [],
        'staff'   => [],
        'viewer'  => [],
    ],
    'surgeons' => [
        'admin'   => ['view', 'create', 'edit', 'delete'],
        'surgeon' => ['view'],
        'staff'   => ['view'],
        'viewer'  => ['view'],
    ],
    'departments' => [
        'admin'   => ['view', 'create', 'edit', 'delete'],
        'surgeon' => ['view'],
        'staff'   => ['view'],
        'viewer'  => ['view'],
    ],
    'settings' => [
        'admin'   => ['view', 'edit'],
        'surgeon' => [],
        'staff'   => [],
        'viewer'  => [],
    ],
    'audit_logs' => [
        'admin'   => ['view', 'export'],
        'surgeon' => [],
        'staff'   => [],
        'viewer'  => [],
    ],
];

// -------------------------------------------------------
// Public functions
// -------------------------------------------------------

/**
 * Attempt to authenticate a user.
 *
 * @param string $username   Submitted username.
 * @param string $password   Plain-text password.
 * @param bool   $remember   Whether to issue a remember-me cookie.
 * @param PDO    $pdo        Database connection.
 * @return array{success: bool, message: string}
 */
function login(string $username, string $password, bool $remember, PDO $pdo): array {
    // Sanitise inputs
    $username = trim($username);

    if ($username === '' || $password === '') {
        return ['success' => false, 'message' => 'Username and password are required.'];
    }

    // Check for account lockout BEFORE querying with the password
    $lockStatus = checkLoginAttempts($username, $pdo);
    if ($lockStatus['locked']) {
        $remaining = (int) ceil($lockStatus['remaining'] / 60);
        return [
            'success' => false,
            'message' => "Account is locked. Try again in {$remaining} minute(s).",
        ];
    }

    // Fetch the user record
    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE username = :u AND is_active = 1 LIMIT 1"
    );
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        recordLoginAttempt($username, false, $pdo);
        auditLog('LOGIN_FAILED', 'auth', null, null, ['username' => $username]);
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Successful authentication
    recordLoginAttempt($username, true, $pdo);

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    // Populate session
    $_SESSION['user_id']       = (int) $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['full_name']     = $user['full_name'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['last_activity'] = time();
    $_SESSION['created']       = time();

    if ($remember) {
        sendRememberMeCookie((int) $user['id'], $pdo);
    }

    auditLog('LOGIN_SUCCESS', 'auth', (int) $user['id'], null, ['username' => $username]);

    return ['success' => true, 'message' => 'Login successful.'];
}

/**
 * Log the current user out, destroy the session and clear cookies.
 */
function logout(): void {
    if (!empty($_SESSION['user_id'])) {
        auditLog('LOGOUT', 'auth', (int) $_SESSION['user_id']);
    }

    // Clear remember-me cookie from DB
    if (!empty($_COOKIE['otms_remember'])) {
        try {
            $pdo = getDBConnection();
            $pdo->prepare(
                "UPDATE users SET remember_token = NULL, token_expires = NULL
                 WHERE remember_token = :t"
            )->execute([':t' => $_COOKIE['otms_remember']]);
        } catch (PDOException) {
            // Non-fatal
        }

        // Expire the cookie
        setcookie('otms_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'samesite' => 'Strict',
        ]);
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly'  => $params['httponly'],
            'samesite' => 'Strict',
        ]);
    }

    session_destroy();
}

/**
 * Validate the current session and, if not set, attempt a remember-me login.
 * Returns true if a valid session exists after the check.
 */
function validateSession(): bool {
    if (!empty($_SESSION['user_id'])) {
        return true;
    }

    // Try remember-me cookie
    try {
        $pdo  = getDBConnection();
        $user = checkRememberMeCookie($pdo);

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = (int) $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['last_activity'] = time();
            $_SESSION['created']       = time();

            // Rotate the token
            sendRememberMeCookie((int) $user['id'], $pdo);
            return true;
        }
    } catch (PDOException) {
        // Non-fatal; fall through
    }

    return false;
}

/**
 * Check whether the logged-in user has permission to perform an action on a module.
 *
 * @param string $module  Module key from PERMISSIONS.
 * @param string $action  Action string, e.g. 'view', 'create', 'edit', 'delete'.
 * @return bool
 */
function hasPermission(string $module, string $action): bool {
    $role = $_SESSION['user_role'] ?? '';

    if ($role === '') {
        return false;
    }

    return isset(PERMISSIONS[$module][$role])
        && in_array($action, PERMISSIONS[$module][$role], true);
}
