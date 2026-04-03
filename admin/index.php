<?php
/** Muayyan - Admin Dashboard */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDBConnection();
$pageTitle = 'Admin Dashboard';

// Stats
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $db->query("SELECT COUNT(*) FROM courses WHERE status='active'")->fetchColumn();
$totalSections = $db->query("SELECT COUNT(*) FROM sections WHERE status='active'")->fetchColumn();
$activeAlerts = $db->query("SELECT COUNT(*) FROM academic_alerts WHERE is_resolved=0")->fetchColumn();
$totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalFaculty = $db->query("SELECT COUNT(*) FROM users WHERE role='faculty'")->fetchColumn();

// Users by role
$roleStats = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();

// Recent activity
$recentActivity = $db->query("SELECT al.*, u.name as user_name FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

// Alerts by severity
$alertsBySeverity = $db->query("SELECT severity, COUNT(*) as count FROM academic_alerts WHERE is_resolved=0 GROUP BY severity")->fetchAll();

// Monthly registrations (simulated)
$monthlyData = $db->query("SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count FROM users GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY created_at LIMIT 6")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>

<main class="main-content">
<div class="content-wrapper">
    
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <h3>Welcome back, <?= e($_SESSION['user_name']) ?> 👋</h3>
        <p>Here's an overview of the system status for <?= CURRENT_SEMESTER ?></p>
        <div class="welcome-date"><i class="fas fa-calendar-alt me-1"></i> <?= date('l, F j, Y') ?></div>
    </div>
    
    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card gradient-1">
                <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                <div class="stat-value" data-count="<?= $totalUsers ?>"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card gradient-2">
                <div class="stat-icon success"><i class="fas fa-book"></i></div>
                <div class="stat-value" data-count="<?= $totalCourses ?>"><?= $totalCourses ?></div>
                <div class="stat-label">Active Courses</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card gradient-3">
                <div class="stat-icon warning"><i class="fas fa-layer-group"></i></div>
                <div class="stat-value" data-count="<?= $totalSections ?>"><?= $totalSections ?></div>
                <div class="stat-label">Active Sections</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card gradient-4">
                <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value" data-count="<?= $activeAlerts ?>"><?= $activeAlerts ?></div>
                <div class="stat-label">Active Alerts</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- User Distribution Chart -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h6><i class="fas fa-chart-pie me-2"></i>Users by Role</h6></div>
                <div class="card-body">
                    <div class="chart-wrapper" style="height:250px">
                        <canvas id="userRoleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alert Distribution -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h6><i class="fas fa-shield-alt me-2"></i>Alerts by Severity</h6></div>
                <div class="card-body">
                    <div class="chart-wrapper" style="height:250px">
                        <canvas id="alertChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h6><i class="fas fa-tachometer-alt me-2"></i>Quick Overview</h6></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background:var(--primary-25)">
                        <span class="fw-semibold" style="font-size:.85rem"><i class="fas fa-user-graduate me-2 text-primary"></i>Students</span>
                        <span class="badge bg-primary"><?= $totalStudents ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background:var(--primary-25)">
                        <span class="fw-semibold" style="font-size:.85rem"><i class="fas fa-chalkboard-teacher me-2 text-success"></i>Faculty</span>
                        <span class="badge bg-success"><?= $totalFaculty ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background:var(--primary-25)">
                        <span class="fw-semibold" style="font-size:.85rem"><i class="fas fa-clipboard-list me-2 text-warning"></i>Assessments</span>
                        <span class="badge bg-warning text-dark"><?= $db->query("SELECT COUNT(*) FROM assessments")->fetchColumn() ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background:var(--primary-25)">
                        <span class="fw-semibold" style="font-size:.85rem"><i class="fas fa-envelope me-2 text-info"></i>Contact Requests</span>
                        <span class="badge bg-info"><?= $db->query("SELECT COUNT(*) FROM contact_requests")->fetchColumn() ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--primary-25)">
                        <span class="fw-semibold" style="font-size:.85rem"><i class="fas fa-pen me-2 text-danger"></i>Grades Entered</span>
                        <span class="badge bg-danger"><?= $db->query("SELECT COUNT(*) FROM grades")->fetchColumn() ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Recent Activity -->
    <div class="row g-4 mt-1">
        <div class="col-lg-4">
            <h6 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h6>
            <div class="row g-3">
                <div class="col-6"><a href="<?= BASE_URL ?>/admin/users.php?action=add" class="quick-action"><i class="fas fa-user-plus"></i><span>Add User</span></a></div>
                <div class="col-6"><a href="<?= BASE_URL ?>/admin/courses.php" class="quick-action"><i class="fas fa-plus-circle"></i><span>Add Course</span></a></div>
                <div class="col-6"><a href="<?= BASE_URL ?>/admin/reports.php" class="quick-action"><i class="fas fa-chart-bar"></i><span>View Reports</span></a></div>
                <div class="col-6"><a href="<?= BASE_URL ?>/admin/settings.php" class="quick-action"><i class="fas fa-cog"></i><span>Settings</span></a></div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-history me-2"></i>Recent Activity</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-dot <?= strpos($activity['action'], 'login') !== false ? 'primary' : (strpos($activity['action'], 'grade') !== false ? 'success' : 'warning') ?>"></div>
                        <div class="activity-content">
                            <div class="title"><?= e($activity['user_name'] ?? 'System') ?> — <?= e(ucfirst(str_replace('_', ' ', $activity['action']))) ?></div>
                            <div class="time"><?= e($activity['description']) ?> &bull; <?= timeAgo($activity['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Role Chart
    const userRoleCtx = document.getElementById('userRoleChart');
    if (userRoleCtx) {
        new Chart(userRoleCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach($roleStats as $r) echo "'" . ucfirst($r['role']) . "',"; ?>],
                datasets: [{
                    data: [<?php foreach($roleStats as $r) echo $r['count'] . ","; ?>],
                    backgroundColor: ['#0f2744','#1e6fa0','#5dade2','#85c1e9'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 11 }, padding: 12, usePointStyle: true }}}}
        });
    }

    // Alert Chart
    const alertCtx = document.getElementById('alertChart');
    if (alertCtx) {
        new Chart(alertCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach($alertsBySeverity as $a) echo "'" . ucfirst($a['severity']) . "',"; ?>],
                datasets: [{
                    label: 'Alerts',
                    data: [<?php foreach($alertsBySeverity as $a) echo $a['count'] . ","; ?>],
                    backgroundColor: ['#3498db','#f39c12','#e74c3c','#2d3436'],
                    borderRadius: 6,
                    barThickness: 35
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }}, x: { grid: { display: false }}}}
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
