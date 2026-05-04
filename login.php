<?php
/**
 * Login Page
 * OT Records Management System
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in → go to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error   = '';
$success = '';

// -------------------------------------------------------
// Session messages
// -------------------------------------------------------
if (!empty($_SESSION['timeout_message'])) {
    $error = $_SESSION['timeout_message'];
    unset($_SESSION['timeout_message']);
}
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please log in again.';
}
if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// -------------------------------------------------------
// POST: process login
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember_me']);

        if ($username === '' || $password === '') {
            $error = 'Please enter your username and password.';
        } else {
            try {
                $pdo    = getDBConnection();
                $result = login($username, $password, $remember, $pdo);

                if ($result['success']) {
                    header('Location: ' . BASE_URL . 'dashboard.php');
                    exit;
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                $error = 'An unexpected error occurred. Please try again.';
            }
        }
    }
}

// -------------------------------------------------------
// Fetch hospital name for display
// -------------------------------------------------------
$hospitalName = APP_NAME;
try {
    $pdo          = getDBConnection();
    $stmt         = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='hospital_name' LIMIT 1");
    $hospitalName = $stmt->fetchColumn() ?: APP_NAME;
} catch (Exception) {
    // DB not yet set up; use default
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login | <?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

    <style>
        body {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d6efd 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, .4);
        }
        .login-logo {
            width: 64px;
            height: 64px;
            background: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .input-group-text { cursor: pointer; }
        .form-control:focus { box-shadow: 0 0 0 .25rem rgba(13,110,253,.25); }
    </style>
</head>
<body>

<div class="login-card card border-0">
    <div class="card-body p-4 p-sm-5">

        <!-- Logo / Icon -->
        <div class="login-logo">
            <i class="fas fa-hospital-symbol fa-2x text-white"></i>
        </div>

        <h4 class="text-center fw-bold mb-0"><?= htmlspecialchars($hospitalName, ENT_QUOTES, 'UTF-8') ?></h4>
        <p class="text-center text-muted mb-4">OT Records Management System</p>

        <?php if ($error !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate autocomplete="on">
            <!-- CSRF -->
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">

            <!-- Username -->
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">
                        <i class="fas fa-user text-muted"></i>
                    </span>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Enter username"
                           autocomplete="username"
                           required
                           autofocus>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Enter password"
                           autocomplete="current-password"
                           required>
                    <button type="button"
                            class="input-group-text bg-light"
                            id="togglePassword"
                            title="Show/hide password"
                            aria-label="Toggle password visibility">
                        <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Remember me + forgot password -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           id="remember_me"
                           name="remember_me"
                           value="1"
                           <?= !empty($_POST['remember_me']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="remember_me">Remember me</label>
                </div>
                <a href="forgot_password.php" class="small text-decoration-none">Forgot password?</a>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <!-- Info box for demo -->
        <div class="alert alert-info mt-4 mb-0 py-2 px-3 small">
            <strong>Default credentials:</strong><br>
            Admin: <code>admin</code> / <code>Admin@123</code>
        </div>

    </div><!-- /.card-body -->
</div><!-- /.login-card -->

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const pwd     = document.getElementById('password');
        const icon    = document.getElementById('eyeIcon');
        const visible = pwd.type === 'text';
        pwd.type      = visible ? 'password' : 'text';
        icon.classList.toggle('fa-eye',      visible);
        icon.classList.toggle('fa-eye-slash', !visible);
    });
</script>
</body>
</html>
