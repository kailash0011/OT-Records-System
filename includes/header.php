<?php
/**
 * HTML Header / <head> Section
 * OT Records Management System
 *
 * Expected variables set by the calling page:
 *   $pageTitle  string   – displayed in <title>
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/constants.php';
}

$pageTitle   = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME;
$csrfToken   = $_SESSION['csrf_token'] ?? '';
$isDarkMode  = !empty($_COOKIE['otms_dark_mode']) && $_COOKIE['otms_dark_mode'] === '1';
$htmlClass   = $isDarkMode ? 'data-bs-theme="dark"' : '';
?>
<!DOCTYPE html>
<html lang="en" <?= $htmlClass ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="OT Records Management System – Operating Theatre Management">
    <meta name="robots" content="noindex, nofollow">
    <!-- CSRF token for AJAX requests -->
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/favicon.png">

    <!-- -------------------------------------------------- -->
    <!-- Bootstrap 5.3 -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">

    <!-- -------------------------------------------------- -->
    <!-- FontAwesome 6 -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

    <!-- -------------------------------------------------- -->
    <!-- DataTables 1.13 -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet"
          href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- -------------------------------------------------- -->
    <!-- Flatpickr -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- -------------------------------------------------- -->
    <!-- Select2 -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <!-- -------------------------------------------------- -->
    <!-- SweetAlert2 -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- -------------------------------------------------- -->
    <!-- FullCalendar 6.1 -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">

    <!-- -------------------------------------------------- -->
    <!-- Custom application stylesheet -->
    <!-- -------------------------------------------------- -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">

    <!-- -------------------------------------------------- -->
    <!-- Inline: dark-mode body class applied early to
         prevent flash-of-unstyled-content                  -->
    <!-- -------------------------------------------------- -->
    <?php if ($isDarkMode): ?>
    <style>body { background-color: #1a1d21; color: #dee2e6; }</style>
    <?php endif; ?>
</head>
<body class="<?= $isDarkMode ? 'dark-mode' : '' ?>">
