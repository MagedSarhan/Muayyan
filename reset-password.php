<?php
/** AALMAS - Reset Password */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token) || !validateResetToken($token)) {
    header('Location: ' . BASE_URL . '/forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        if (resetPassword($token, $password)) {
            header('Location: ' . BASE_URL . '/login.php?reset=1');
            exit;
        }
        $error = 'Failed to reset password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | AALMAS</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-shapes"><div class="shape"></div><div class="shape"></div><div class="shape"></div><div class="shape"></div></div>
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-icon-large"><i class="fas fa-lock-open"></i></div>
            <h2>Reset Password</h2>
            <p>Enter your new password below</p>
        </div>
        <?php if ($error): ?>
        <div class="auth-alert error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
        <?php endif; ?>
        <form class="auth-form" method="POST">
            <div class="form-floating position-relative">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required minlength="6">
                <label for="password">New Password</label>
            </div>
            <div class="form-floating position-relative">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm" required>
                <label for="confirm_password">Confirm Password</label>
            </div>
            <button type="submit" class="btn btn-login"><i class="fas fa-save me-2"></i> Reset Password</button>
        </form>
        <div class="auth-footer"><p>&copy; <?= date('Y') ?> AALMAS.</p></div>
    </div>
</div>
</body>
</html>
