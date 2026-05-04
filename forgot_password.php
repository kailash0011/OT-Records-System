<?php
/**
 * Forgot Password – placeholder
 * OT Records Management System
 */
require_once __DIR__ . '/config/constants.php';
$pageTitle = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?> – Forgot Password</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="card shadow-sm p-4 text-center" style="max-width:400px;width:100%">
    <h5 class="mb-3">Password Reset</h5>
    <p class="text-muted small">Self-service password reset is not yet configured. Please contact your system administrator to reset your password.</p>
    <a href="login.php" class="btn btn-primary btn-sm">Back to Login</a>
</div>
</body>
</html>
