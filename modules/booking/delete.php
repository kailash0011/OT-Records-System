<?php
/**
 * Booking Delete/Cancel Handler
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

$token  = $_POST['csrf_token'] ?? '';
if (!validateCSRF($token)) {
    $_SESSION['flash_error'] = 'Invalid CSRF token. Please try again.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

if (!hasPermission('case_bookings', 'delete')) {
    $_SESSION['flash_error'] = 'You do not have permission to cancel bookings.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

$id           = (int)($_POST['id'] ?? 0);
$cancelReason = trim($_POST['cancel_reason'] ?? '');

if (!$id) {
    $_SESSION['flash_error'] = 'Invalid booking ID.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT id, booking_number, status FROM case_bookings WHERE id = :id LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $_SESSION['flash_error'] = 'Booking not found.';
        header('Location: ' . BASE_URL . 'booking_list.php');
        exit;
    }

    if ($booking['status'] === 'Cancelled') {
        $_SESSION['flash_error'] = 'Booking is already cancelled.';
        header('Location: ' . BASE_URL . 'booking_list.php');
        exit;
    }

    $oldStatus = $booking['status'];

    // Soft-delete: set status to Cancelled
    $pdo->prepare(
        "UPDATE case_bookings SET status = 'Cancelled', updated_at = NOW() WHERE id = :id"
    )->execute([':id' => $id]);

    auditLog(
        'CANCEL',
        'case_bookings',
        $id,
        ['status' => $oldStatus],
        ['status' => 'Cancelled', 'reason' => $cancelReason]
    );

    $_SESSION['flash_success'] = "Booking {$booking['booking_number']} has been cancelled.";
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
} catch (PDOException $e) {
    error_log('booking delete error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'A database error occurred. Please try again.';
    header('Location: ' . BASE_URL . 'booking_list.php');
    exit;
}
