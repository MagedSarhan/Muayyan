<?php
/** AALMAS - Login Page */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (authenticate($email, $password)) {
        header('Location: ' . getRoleRedirect($_SESSION['user_role']));
        exit;
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}

if (isset($_GET['timeout']))
    $error = 'Your session has expired. Please login again.';
if (isset($_GET['unauthorized']))
    $error = 'You do not have permission to access that page.';
$success = isset($_GET['reset']) ? 'Password reset successful. Please login.' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AALMAS</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="auth-card">
            <div class="auth-logo">
                <img src="<?= BASE_URL ?>/images/logo.png" alt="AALMAS">
                <h2>AALMAS</h2>
                <p>Academic Assessment Load & Performance Analysis</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="auth-alert success"><i class="fas fa-check-circle"></i> <?= e($success) ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" autocomplete="off">
                <div class="form-floating position-relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
                        value="<?= e($_POST['email'] ?? '') ?>">
                    <label for="email">Email Address</label>
                </div>

                <div class="form-floating position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                        required>
                    <label for="password">Password</label>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
            </form>

            <div class="auth-links">
                <a href="<?= BASE_URL ?>/forgot-password.php"><i class="fas fa-key me-1"></i> Forgot Password?</a>
            </div>

            <div class="demo-credentials">
                <h6><i class="fas fa-info-circle"></i> Demo Accounts</h6>
                <div class="cred-item"><span><strong>Admin:</strong> admin@aalmas.edu</span></div>
                <div class="cred-item"><span><strong>Faculty:</strong> sara.faculty@aalmas.edu</span></div>
                <div class="cred-item"><span><strong>Advisor:</strong> nora.advisor@aalmas.edu</span></div>
                <div class="cred-item"><span><strong>Student:</strong> mohammed.stu@aalmas.edu</span></div>
                <div class="cred-item"><span><strong>Password:</strong> password</span></div>
            </div>

            <div class="auth-footer">
                <p>&copy; <?= date('Y') ?> AALMAS. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>