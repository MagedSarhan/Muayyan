<?php
/** AALMAS - Forgot Password */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $token = generateResetToken($email);
        if ($token) {
            $resetLink = BASE_URL . '/reset-password.php?token=' . $token;
            $success = 'Password reset link has been generated.';
        } else {
            $error = 'No active account found with that email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | AALMAS</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-shapes">
        <div class="shape"></div><div class="shape"></div><div class="shape"></div><div class="shape"></div>
    </div>
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-icon-large"><i class="fas fa-key"></i></div>
            <h2>Forgot Password</h2>
            <p>Enter your email to receive a reset link</p>
        </div>
        
        <?php if ($error): ?>
        <div class="auth-alert error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="auth-alert success"><i class="fas fa-check-circle"></i> <?= e($success) ?></div>
        <?php if ($resetLink): ?>
        <div class="demo-credentials" style="margin-bottom:18px">
            <h6><i class="fas fa-link"></i> Demo Reset Link</h6>
            <p style="font-size:.75rem;word-break:break-all;color:var(--primary-500)">
                <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
            </p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <form class="auth-form" method="POST">
            <div class="form-floating position-relative">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <label for="email">Email Address</label>
            </div>
            <button type="submit" class="btn btn-login"><i class="fas fa-paper-plane me-2"></i> Send Reset Link</button>
        </form>
        
        <div class="auth-links">
            <a href="<?= BASE_URL ?>/login.php"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
        </div>
        
        <div class="auth-footer"><p>&copy; <?= date('Y') ?> AALMAS. All rights reserved.</p></div>
    </div>
</div>
</body>
</html>
