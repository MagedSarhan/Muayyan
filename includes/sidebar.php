<?php
/**
 * MOEEN  - Sidebar Navigation
 * Role-based navigation menu
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? '';
$unreadNotifs = getUnreadNotificationCount($_SESSION['user_id'] ?? 0);
?>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/images/logo.png" alt="MOEEN ">
        <h1>MOEEN <small>Performance Analysis</small></h1>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <div class="nav-section">Main</div>
            <a href="<?= BASE_URL ?>/admin/" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <div class="nav-section">Management</div>
            <a href="<?= BASE_URL ?>/admin/users.php" class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i> User Management
            </a>
            <a href="<?= BASE_URL ?>/admin/courses.php"
                class="nav-link <?= $currentPage === 'courses.php' ? 'active' : '' ?>">
                <i class="fas fa-book"></i> Courses
            </a>
            <a href="<?= BASE_URL ?>/admin/sections.php"
                class="nav-link <?= $currentPage === 'sections.php' ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i> Sections
            </a>

            <div class="nav-section">Analytics</div>
            <a href="<?= BASE_URL ?>/admin/reports.php"
                class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="<?= BASE_URL ?>/admin/settings.php"
                class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Settings
            </a>

        <?php elseif ($role === 'faculty'): ?>
            <div class="nav-section">Main</div>
            <a href="<?= BASE_URL ?>/faculty/" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <div class="nav-section">Teaching</div>
            <a href="<?= BASE_URL ?>/faculty/sections.php"
                class="nav-link <?= $currentPage === 'sections.php' ? 'active' : '' ?>">
                <i class="fas fa-chalkboard"></i> My Sections
            </a>
            <a href="<?= BASE_URL ?>/faculty/assessments.php"
                class="nav-link <?= $currentPage === 'assessments.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Assessments
            </a>
            <a href="<?= BASE_URL ?>/faculty/grades.php"
                class="nav-link <?= $currentPage === 'grades.php' ? 'active' : '' ?>">
                <i class="fas fa-pen-alt"></i> Grade Entry
            </a>

            <div class="nav-section">Monitoring</div>
            <a href="<?= BASE_URL ?>/faculty/students.php"
                class="nav-link <?= $currentPage === 'students.php' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i> Student Performance
            </a>
            <a href="<?= BASE_URL ?>/faculty/workload.php"
                class="nav-link <?= $currentPage === 'workload.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Assessment Density
            </a>
            <a href="<?= BASE_URL ?>/faculty/reports.php"
                class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Reports
            </a>

        <?php elseif ($role === 'advisor'): ?>
            <div class="nav-section">Main</div>
            <a href="<?= BASE_URL ?>/advisor/" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <div class="nav-section">Advising</div>
            <a href="<?= BASE_URL ?>/advisor/students.php"
                class="nav-link <?= $currentPage === 'students.php' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i> My Students
            </a>
            <a href="<?= BASE_URL ?>/advisor/alerts.php"
                class="nav-link <?= $currentPage === 'alerts.php' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i> Alerts
                <?php
                $db = getDBConnection();
                $alertCount = $db->prepare("SELECT COUNT(*) as c FROM academic_alerts aa JOIN advisor_assignments adv ON aa.student_id = adv.student_id WHERE adv.advisor_id = ? AND aa.is_read = 0");
                $alertCount->execute([$_SESSION['user_id']]);
                $ac = $alertCount->fetch()['c'];
                if ($ac > 0): ?>
                    <span class="nav-badge"><?= $ac ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/advisor/requests.php"
                class="nav-link <?= $currentPage === 'requests.php' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Contact Requests
                <?php
                $reqCount = $db->prepare("SELECT COUNT(*) as c FROM contact_requests WHERE advisor_id = ? AND status IN ('sent','under_review')");
                $reqCount->execute([$_SESSION['user_id']]);
                $rc = $reqCount->fetch()['c'];
                if ($rc > 0): ?>
                    <span class="nav-badge"><?= $rc ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/advisor/notes.php"
                class="nav-link <?= $currentPage === 'notes.php' ? 'active' : '' ?>">
                <i class="fas fa-sticky-note"></i> Academic Notes
            </a>

            <div class="nav-section">Analytics</div>
            <a href="<?= BASE_URL ?>/advisor/reports.php"
                class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Reports
            </a>

        <?php elseif ($role === 'student'): ?>
            <div class="nav-section">Main</div>
            <a href="<?= BASE_URL ?>/student/" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <div class="nav-section">Academic</div>
            <a href="<?= BASE_URL ?>/student/courses.php"
                class="nav-link <?= $currentPage === 'courses.php' ? 'active' : '' ?>">
                <i class="fas fa-book-open"></i> My Courses
            </a>
            <a href="<?= BASE_URL ?>/student/assessments.php"
                class="nav-link <?= $currentPage === 'assessments.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Assessments
            </a>
            <a href="<?= BASE_URL ?>/student/grades.php"
                class="nav-link <?= $currentPage === 'grades.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> My Grades
            </a>
            <a href="<?= BASE_URL ?>/student/workload.php"
                class="nav-link <?= $currentPage === 'workload.php' ? 'active' : '' ?>">
                <i class="fas fa-weight-hanging"></i> Workload
            </a>

            <div class="nav-section">Support</div>
            <a href="<?= BASE_URL ?>/student/alerts.php"
                class="nav-link <?= $currentPage === 'alerts.php' ? 'active' : '' ?>">
                <i class="fas fa-exclamation-triangle"></i> Alerts
                <?php
                $db2 = getDBConnection();
                $saCount = $db2->prepare("SELECT COUNT(*) as c FROM academic_alerts WHERE student_id = ? AND is_read = 0");
                $saCount->execute([$_SESSION['user_id']]);
                $sac = $saCount->fetch()['c'];
                if ($sac > 0): ?>
                    <span class="nav-badge"><?= $sac ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/student/contact.php"
                class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                <i class="fas fa-headset"></i> Contact Advisor
            </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?= getInitials($_SESSION['user_name'] ?? 'U') ?></div>
            <div class="sidebar-user-info">
                <div class="name"><?= e($_SESSION['user_name'] ?? 'User') ?></div>
                <div class="role"><?= e(ucfirst($_SESSION['user_role'] ?? '')) ?></div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="text-white ms-auto" title="Logout"><i
                    class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</aside>