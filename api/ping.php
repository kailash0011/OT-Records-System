<?php
/**
 * API: Session ping – refreshes the session timeout.
 * Called by the client-side session-expiry warning.
 * OT Records Management System
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

// Touch the session to reset the inactivity timer
$_SESSION['last_activity'] = time();

echo json_encode(['ok' => true, 'ts' => time()]);
