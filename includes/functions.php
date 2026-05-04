<?php
/**
 * Helper Functions
 * OT Records Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// -------------------------------------------------------
// Input / output sanitisation
// -------------------------------------------------------

/**
 * Sanitise a string for safe HTML output.
 *
 * @param mixed $input  Value to sanitise.
 * @return string       HTML-safe string.
 */
function sanitize(mixed $input): string {
    return htmlspecialchars((string) $input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -------------------------------------------------------
// CSRF helpers (proxies to config/session.php)
// -------------------------------------------------------

/**
 * Return the current session CSRF token, generating one if absent.
 */
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token and regenerate it immediately after.
 *
 * @param string $token  Token submitted by the client.
 * @return bool
 */
function validateCSRF(string $token): bool {
    $valid = !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $valid;
}

// -------------------------------------------------------
// Reference number generators
// -------------------------------------------------------

/**
 * Generate a unique booking number: BK-YYYYMMDD-XXXX
 */
function generateBookingNumber(PDO $pdo): string {
    $prefix = 'BK-' . date('Ymd') . '-';
    $stmt   = $pdo->prepare(
        "SELECT COUNT(*) FROM case_bookings WHERE booking_number LIKE :prefix"
    );
    $stmt->execute([':prefix' => $prefix . '%']);
    $count = (int) $stmt->fetchColumn() + 1;
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate a unique OT record number: OR-YYYYMMDD-XXXX
 */
function generateRecordNumber(PDO $pdo): string {
    $prefix = 'OR-' . date('Ymd') . '-';
    $stmt   = $pdo->prepare(
        "SELECT COUNT(*) FROM ot_records WHERE record_number LIKE :prefix"
    );
    $stmt->execute([':prefix' => $prefix . '%']);
    $count = (int) $stmt->fetchColumn() + 1;
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate a unique patient ID: PT-YYYYXXXX
 */
function generatePatientId(PDO $pdo): string {
    $prefix = 'PT-' . date('Y');
    $stmt   = $pdo->prepare(
        "SELECT COUNT(*) FROM patients WHERE patient_id LIKE :prefix"
    );
    $stmt->execute([':prefix' => $prefix . '%']);
    $count = (int) $stmt->fetchColumn() + 1;
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// -------------------------------------------------------
// Audit logging
// -------------------------------------------------------

/**
 * Write an entry to the audit_logs table.
 *
 * @param string     $action     Short action label, e.g. 'CREATE', 'UPDATE'.
 * @param string     $module     Module name, e.g. 'case_bookings'.
 * @param int|null   $recordId   Primary key of the affected record.
 * @param mixed      $oldValues  Previous state (will be JSON-encoded).
 * @param mixed      $newValues  New state (will be JSON-encoded).
 */
function auditLog(
    string   $action,
    string   $module,
    ?int     $recordId  = null,
    mixed    $oldValues = null,
    mixed    $newValues = null
): void {
    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs
                (user_id, action, module, record_id, old_values, new_values, ip_address, user_agent)
             VALUES
                (:uid, :action, :module, :rid, :old, :new, :ip, :ua)"
        );
        $stmt->execute([
            ':uid'    => $_SESSION['user_id'] ?? null,
            ':action' => $action,
            ':module' => $module,
            ':rid'    => $recordId,
            ':old'    => $oldValues !== null ? json_encode($oldValues) : null,
            ':new'    => $newValues !== null ? json_encode($newValues) : null,
            ':ip'     => $_SERVER['REMOTE_ADDR']     ?? null,
            ':ua'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

// -------------------------------------------------------
// Date helpers
// -------------------------------------------------------

/**
 * Format a date string using a PHP date format.
 *
 * @param string $date    Date string parseable by strtotime().
 * @param string $format  Output format (default: d/m/Y).
 * @return string         Formatted date or original string on failure.
 */
function formatDate(string $date, string $format = DATE_FORMAT_DISPLAY): string {
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts !== false ? date($format, $ts) : $date;
}

/**
 * Validate a date string against a given format.
 *
 * @param string $date    Date string to validate.
 * @param string $format  Expected format (default: Y-m-d).
 * @return bool
 */
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d !== false && $d->format($format) === $date;
}

/**
 * Calculate age in years from a date of birth string.
 *
 * @param string $dob  Date of birth (Y-m-d).
 * @return int         Age in years, or 0 on failure.
 */
function calculateAge(string $dob): int {
    if (empty($dob) || $dob === '0000-00-00') {
        return 0;
    }
    try {
        $birthDate = new DateTime($dob);
        $today     = new DateTime('today');
        return (int) $birthDate->diff($today)->y;
    } catch (Exception) {
        return 0;
    }
}

/**
 * Convert a duration in minutes to a human-readable string.
 *
 * @param int $minutes
 * @return string  e.g. "2 hr 30 min"
 */
function formatDuration(int $minutes): string {
    if ($minutes <= 0) {
        return '0 min';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    $parts = [];
    if ($h > 0) {
        $parts[] = $h . ' hr';
    }
    if ($m > 0) {
        $parts[] = $m . ' min';
    }
    return implode(' ', $parts);
}

// -------------------------------------------------------
// Bootstrap badge helpers
// -------------------------------------------------------

/**
 * Return a Bootstrap 5 badge span for the given status.
 *
 * @param string $status
 * @return string  HTML string.
 */
function getStatusBadge(string $status): string {
    $colours = defined('STATUS_COLOURS') ? STATUS_COLOURS : [];
    $colour  = $colours[$status] ?? 'secondary';
    return '<span class="badge bg-' . $colour . '">' . sanitize($status) . '</span>';
}

/**
 * Return a Bootstrap 5 badge span for the given priority.
 *
 * @param string $priority
 * @return string  HTML string.
 */
function getPriorityBadge(string $priority): string {
    $colours = defined('PRIORITY_COLOURS') ? PRIORITY_COLOURS : [];
    $colour  = $colours[$priority] ?? 'secondary';
    return '<span class="badge bg-' . $colour . '">' . sanitize($priority) . '</span>';
}

// -------------------------------------------------------
// Login attempt / lockout helpers
// -------------------------------------------------------

/**
 * Check whether a username is currently locked out.
 * Returns an array with keys 'locked' (bool) and 'remaining' (seconds).
 *
 * @param string $username
 * @param PDO    $pdo
 * @return array{locked: bool, remaining: int}
 */
function checkLoginAttempts(string $username, PDO $pdo): array {
    $stmt = $pdo->prepare(
        "SELECT failed_attempts, locked_until FROM users WHERE username = :u LIMIT 1"
    );
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['locked' => false, 'remaining' => 0];
    }

    if (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()) {
        $remaining = strtotime($row['locked_until']) - time();
        return ['locked' => true, 'remaining' => $remaining];
    }

    return ['locked' => false, 'remaining' => 0];
}

/**
 * Record a login attempt (success or failure).
 * On failure, increments the counter and applies a lockout if the threshold is reached.
 *
 * @param string $username
 * @param bool   $success
 * @param PDO    $pdo
 */
function recordLoginAttempt(string $username, bool $success, PDO $pdo): void {
    if ($success) {
        $pdo->prepare(
            "UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW()
             WHERE username = :u"
        )->execute([':u' => $username]);
        return;
    }

    $maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
    $lockMinutes = defined('LOCKOUT_TIME')        ? LOCKOUT_TIME        : 15;

    // Increment counter
    $pdo->prepare(
        "UPDATE users SET failed_attempts = failed_attempts + 1 WHERE username = :u"
    )->execute([':u' => $username]);

    // Check if threshold reached
    $stmt = $pdo->prepare(
        "SELECT failed_attempts FROM users WHERE username = :u LIMIT 1"
    );
    $stmt->execute([':u' => $username]);
    $attempts = (int) ($stmt->fetchColumn() ?: 0);

    if ($attempts >= $maxAttempts) {
        $lockUntil = date('Y-m-d H:i:s', time() + $lockMinutes * 60);
        $pdo->prepare(
            "UPDATE users SET locked_until = :lu WHERE username = :u"
        )->execute([':lu' => $lockUntil, ':u' => $username]);
    }
}

// -------------------------------------------------------
// Remember-me cookie helpers
// -------------------------------------------------------

/**
 * Issue a "remember me" cookie tied to the given user.
 *
 * @param int $userId
 * @param PDO $pdo
 */
function sendRememberMeCookie(int $userId, PDO $pdo): void {
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (defined('REMEMBER_ME_DAYS') ? REMEMBER_ME_DAYS : 30) * 86400);

    $pdo->prepare(
        "UPDATE users SET remember_token = :t, token_expires = :e WHERE id = :id"
    )->execute([':t' => $token, ':e' => $expires, ':id' => $userId]);

    setcookie(
        'otms_remember',
        $token,
        [
            'expires'  => time() + (defined('REMEMBER_ME_DAYS') ? REMEMBER_ME_DAYS : 30) * 86400,
            'path'     => '/',
            'httponly' => true,
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'samesite' => 'Strict',
        ]
    );
}

/**
 * Attempt to authenticate using a remember-me cookie.
 * Returns the user row on success, or null on failure.
 *
 * @param PDO $pdo
 * @return array|null
 */
function checkRememberMeCookie(PDO $pdo): ?array {
    if (empty($_COOKIE['otms_remember'])) {
        return null;
    }

    $token = $_COOKIE['otms_remember'];
    $stmt  = $pdo->prepare(
        "SELECT * FROM users
         WHERE remember_token = :t
           AND token_expires > NOW()
           AND is_active = 1
         LIMIT 1"
    );
    $stmt->execute([':t' => $token]);
    $user = $stmt->fetch();

    return $user ?: null;
}

// -------------------------------------------------------
// Pagination
// -------------------------------------------------------

/**
 * Generate a Bootstrap 5 pagination component.
 *
 * @param int    $total    Total number of records.
 * @param int    $perPage  Items per page.
 * @param int    $page     Current page (1-based).
 * @param string $url      Base URL; the page number is appended as ?page=N.
 * @return string          HTML pagination nav element.
 */
function paginate(int $total, int $perPage, int $page, string $url): string {
    if ($total <= $perPage) {
        return '';
    }

    $totalPages = (int) ceil($total / $perPage);
    $page       = max(1, min($page, $totalPages));

    $separator = str_contains($url, '?') ? '&' : '?';
    $html      = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">';

    // Previous
    $prevClass = $page === 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $prevClass . '">'
           . '<a class="page-link" href="' . sanitize($url . $separator . 'page=' . ($page - 1)) . '">‹</a></li>';

    // Page numbers (show at most 7 around the current page)
    $start = max(1, $page - 3);
    $end   = min($totalPages, $page + 3);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sanitize($url . $separator . 'page=1') . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? ' active' : '';
        $html  .= '<li class="page-item' . $active . '">'
                . '<a class="page-link" href="' . sanitize($url . $separator . 'page=' . $i) . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . sanitize($url . $separator . 'page=' . $totalPages) . '">' . $totalPages . '</a></li>';
    }

    // Next
    $nextClass = $page === $totalPages ? ' disabled' : '';
    $html .= '<li class="page-item' . $nextClass . '">'
           . '<a class="page-link" href="' . sanitize($url . $separator . 'page=' . ($page + 1)) . '">›</a></li>';

    $html .= '</ul></nav>';
    return $html;
}

// -------------------------------------------------------
// Chart helpers
// -------------------------------------------------------

/**
 * Generate an array of distinct RGBA colour strings for Chart.js datasets.
 *
 * @param int $count  Number of colours needed.
 * @return array<string>
 */
function getChartColors(int $count): array {
    $palette = [
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 99, 132, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(255, 206, 86, 0.8)',
        'rgba(153, 102, 255, 0.8)',
        'rgba(255, 159, 64, 0.8)',
        'rgba(201, 203, 207, 0.8)',
        'rgba(0, 204, 150, 0.8)',
        'rgba(255, 70, 131, 0.8)',
        'rgba(50, 168, 82, 0.8)',
        'rgba(232, 65, 24, 0.8)',
        'rgba(0, 120, 212, 0.8)',
    ];

    $colours = [];
    for ($i = 0; $i < $count; $i++) {
        $colours[] = $palette[$i % count($palette)];
    }
    return $colours;
}

/**
 * Retrieve a system setting value from the database.
 *
 * @param string $key      Setting key.
 * @param mixed  $default  Value to return if the key is not found.
 * @return mixed
 */
function getSetting(string $key, mixed $default = null): mixed {
    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare(
            "SELECT setting_value FROM system_settings WHERE setting_key = :k LIMIT 1"
        );
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (PDOException) {
        return $default;
    }
}
