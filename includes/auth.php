<?php
/**
 * AALMAS - Authentication Helpers
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Require specific role
 */
function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: ' . BASE_URL . '/login.php?unauthorized=1');
        exit;
    }
}

/**
 * Authenticate user
 */
function authenticate($email, $password) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uid'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['user_department'] = $user['department'];
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log activity
        logActivity($user['id'], 'login', $user['name'] . ' logged in successfully');
        
        return true;
    }
    return false;
}

/**
 * Get redirect URL based on role
 */
function getRoleRedirect($role) {
    switch ($role) {
        case 'admin':   return BASE_URL . '/admin/';
        case 'faculty': return BASE_URL . '/faculty/';
        case 'advisor': return BASE_URL . '/advisor/';
        case 'student': return BASE_URL . '/student/';
        default:        return BASE_URL . '/login.php';
    }
}

/**
 * Log activity
 */
function logActivity($userId, $action, $description = '') {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch (Exception $e) {
        // Silent fail for logging
    }
}

/**
 * Generate password reset token
 */
function generateResetToken($email) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expires]);
    
    return $token;
}

/**
 * Validate reset token
 */
function validateResetToken($token) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

/**
 * Reset password
 */
function resetPassword($token, $newPassword) {
    $db = getDBConnection();
    $reset = validateResetToken($token);
    if (!$reset) return false;
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $reset['user_id']]);
    
    $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $stmt->execute([$token]);
    
    return true;
}
