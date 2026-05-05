<?php
/**
 * Sidebar Navigation
 * OT Records Management System
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/constants.php';
}
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}

$userRole     = $_SESSION['user_role'] ?? 'viewer';
$currentFile  = basename($_SERVER['PHP_SELF']);
$hospitalName = function_exists('getSetting') ? getSetting('hospital_name', APP_NAME) : APP_NAME;

/**
 * Return 'active' if the current page matches one of the supplied filenames.
 */
function navActive(string|array $files): string {
    global $currentFile;
    $files = (array) $files;
    return in_array($currentFile, $files, true) ? 'active' : '';
}

/**
 * Return 'show' (Bootstrap collapse) if the current page is within the group.
 */
function navGroupOpen(array $files): string {
    global $currentFile;
    return in_array($currentFile, $files, true) ? 'show' : '';
}
?>
<!-- ============================================================
     Off-canvas sidebar wrapper (collapsed on mobile)
============================================================ -->
<nav id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 p-0 bg-dark">

    <!-- Brand / Logo -->
    <a href="<?= BASE_URL ?>dashboard.php"
       class="d-flex align-items-center p-3 mb-0 text-white text-decoration-none border-bottom border-secondary">
        <i class="fas fa-hospital-symbol fa-lg me-2 text-info"></i>
        <span class="fs-6 fw-semibold text-truncate"><?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></span>
    </a>

    <!-- Logged-in user info -->
    <div class="px-3 py-2 border-bottom border-secondary d-flex align-items-center gap-2">
        <i class="fas fa-user-circle fa-2x text-secondary"></i>
        <div class="overflow-hidden">
            <div class="text-white text-truncate small fw-semibold">
                <?= htmlspecialchars($_SESSION['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="text-secondary" style="font-size:.7rem;">
                <?= htmlspecialchars(ucfirst($userRole), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </div>

    <!-- Navigation list -->
    <ul class="nav nav-pills flex-column mb-auto px-2 pt-2 overflow-auto">

        <!-- Dashboard -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>dashboard.php"
               class="nav-link text-white <?= navActive('dashboard.php') ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>

        <!-- Case Booking -->
        <li class="nav-item">
            <a href="#bookingMenu" data-bs-toggle="collapse"
               class="nav-link text-white d-flex justify-content-between align-items-center
                      <?= navActive(['booking_list.php','booking_new.php','booking_calendar.php']) ?>">
                <span><i class="fas fa-calendar-plus me-2"></i> Case Booking</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse nav flex-column ps-3 <?= navGroupOpen(['booking_list.php','booking_new.php','booking_calendar.php']) ?>"
                id="bookingMenu">
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>booking_new.php"
                       class="nav-link text-white <?= navActive('booking_new.php') ?>">
                        <i class="fas fa-plus-circle me-2"></i> New Booking
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>booking_calendar.php"
                       class="nav-link text-white <?= navActive('booking_calendar.php') ?>">
                        <i class="fas fa-calendar-week me-2"></i> Calendar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>booking_list.php"
                       class="nav-link text-white <?= navActive('booking_list.php') ?>">
                        <i class="fas fa-list me-2"></i> Booking List
                    </a>
                </li>
            </ul>
        </li>

        <!-- OT Records -->
        <li class="nav-item">
            <a href="#recordsMenu" data-bs-toggle="collapse"
               class="nav-link text-white d-flex justify-content-between align-items-center
                      <?= navActive(['records_list.php','record_new.php','index.php','add.php','view.php','edit.php']) ?>">
                <span><i class="fas fa-file-medical me-2"></i> OT Records</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse nav flex-column ps-3 <?= navGroupOpen(['records_list.php','record_new.php','index.php','add.php','view.php','edit.php']) ?>"
                id="recordsMenu">
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>record_new.php"
                       class="nav-link text-white <?= navActive(['record_new.php','add.php']) ?>">
                        <i class="fas fa-plus-circle me-2"></i> New Record
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>records_list.php"
                       class="nav-link text-white <?= navActive(['records_list.php','index.php']) ?>">
                        <i class="fas fa-clipboard-list me-2"></i> Records List
                    </a>
                </li>
            </ul>
        </li>

        <!-- Patients -->
        <li class="nav-item">
            <a href="#patientsMenu" data-bs-toggle="collapse"
               class="nav-link text-white d-flex justify-content-between align-items-center
                      <?= navActive(['patients_list.php','patient_add.php','patient_search.php']) ?>">
                <span><i class="fas fa-procedures me-2"></i> Patients</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse nav flex-column ps-3 <?= navGroupOpen(['patients_list.php','patient_add.php','patient_search.php']) ?>"
                id="patientsMenu">
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>patients_list.php"
                       class="nav-link text-white <?= navActive('patients_list.php') ?>">
                        <i class="fas fa-list me-2"></i> Patient List
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>patient_add.php"
                       class="nav-link text-white <?= navActive('patient_add.php') ?>">
                        <i class="fas fa-user-plus me-2"></i> Add Patient
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>patient_search.php"
                       class="nav-link text-white <?= navActive('patient_search.php') ?>">
                        <i class="fas fa-search me-2"></i> Search
                    </a>
                </li>
            </ul>
        </li>

        <!-- Reports -->
        <li class="nav-item">
            <a href="#reportsMenu" data-bs-toggle="collapse"
               class="nav-link text-white d-flex justify-content-between align-items-center
                      <?= navActive(['report_census.php','report_surgeon.php','report_dept.php','report_analytics.php']) ?>">
                <span><i class="fas fa-chart-bar me-2"></i> Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse nav flex-column ps-3 <?= navGroupOpen(['report_census.php','report_surgeon.php','report_dept.php','report_analytics.php']) ?>"
                id="reportsMenu">
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>report_census.php"
                       class="nav-link text-white <?= navActive('report_census.php') ?>">
                        <i class="fas fa-table me-2"></i> Census Report
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>report_surgeon.php"
                       class="nav-link text-white <?= navActive('report_surgeon.php') ?>">
                        <i class="fas fa-user-md me-2"></i> Surgeon Wise
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>report_dept.php"
                       class="nav-link text-white <?= navActive('report_dept.php') ?>">
                        <i class="fas fa-hospital me-2"></i> Dept Wise
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>report_analytics.php"
                       class="nav-link text-white <?= navActive('report_analytics.php') ?>">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                </li>
            </ul>
        </li>

        <?php if ($userRole === 'admin'): ?>
        <!-- Admin section -->
        <li class="nav-item mt-2">
            <div class="text-uppercase text-secondary ps-2 pb-1" style="font-size:.65rem;letter-spacing:.1em;">
                Administration
            </div>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin_users.php"
               class="nav-link text-white <?= navActive('admin_users.php') ?>">
                <i class="fas fa-users-cog me-2"></i> User Management
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin_surgeons.php"
               class="nav-link text-white <?= navActive('admin_surgeons.php') ?>">
                <i class="fas fa-user-md me-2"></i> Surgeon Management
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin_departments.php"
               class="nav-link text-white <?= navActive('admin_departments.php') ?>">
                <i class="fas fa-sitemap me-2"></i> Departments
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin_settings.php"
               class="nav-link text-white <?= navActive('admin_settings.php') ?>">
                <i class="fas fa-cog me-2"></i> System Settings
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin_audit.php"
               class="nav-link text-white <?= navActive('admin_audit.php') ?>">
                <i class="fas fa-history me-2"></i> Audit Logs
            </a>
        </li>
        <?php endif; ?>

    </ul><!-- /nav -->

    <!-- Footer: logout -->
    <div class="border-top border-secondary p-2 mt-auto">
        <a href="<?= BASE_URL ?>logout.php"
           class="nav-link text-white d-flex align-items-center gap-2"
           onclick="return confirm('Log out?')">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

</nav><!-- /#sidebar -->
