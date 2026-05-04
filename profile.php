<?php
/**
 * My Profile – stub page (to be implemented)
 * OT Records Management System
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 p-4">
  <div class="alert alert-info d-flex align-items-center gap-3">
    <i class="fas fa-tools fa-2x"></i>
    <div>
      <strong>My Profile</strong><br>
      <span class="small">This module is under construction and will be available in a future release.</span>
    </div>
  </div>
  <a href="dashboard.php" class="btn btn-primary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
