<?php
/**
 * Muayyan - Application Configuration
 */

// Dynamic BASE_URL detection - supports both local and hosting
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

// Get document root and project root, standardizing slashes
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$project_root = str_replace('\\', '/', dirname(__DIR__));

// The base directory is the difference between project root and doc root
$base_dir = str_replace($doc_root, '', $project_root);
$base_url = $protocol . "://" . $host . $base_dir;

define('BASE_URL', $base_url);
define('SITE_NAME', 'Muayyan');
define('SITE_FULL_NAME', 'Academic Assessment Load & Performance Analysis System');

// Current Academic Period
define('CURRENT_SEMESTER', 'Spring 2026');
define('ACADEMIC_YEAR', '2025-2026');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip']);

// Risk Thresholds
define('RISK_HIGH', 40);
define('RISK_ATRISK', 60);
define('RISK_MONITOR', 70);
define('RISK_STABLE', 80);

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/database.php';
