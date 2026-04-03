<?php
/** Muayyan - Advisor Dashboard */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('advisor');
$db = getDBConnection();
$pageTitle = 'Advisor Dashboard';
$aid = $_SESSION['user_id'];

$assignedStudents = $db->query("SELECT COUNT(*) FROM advisor_assignments WHERE advisor_id = $aid AND status='active'")->fetchColumn();
$pendingRequests = $db->query("SELECT COUNT(*) FROM contact_requests WHERE advisor_id = $aid AND status IN ('sent','under_review')")->fetchColumn();
$unreadAlerts = $db->query("SELECT COUNT(*) FROM academic_alerts aa JOIN advisor_assignments adv ON aa.student_id = adv.student_id WHERE adv.advisor_id = $aid AND aa.is_read = 0")->fetchColumn();

// Get assigned student IDs
$stuIds = $db->query("SELECT student_id FROM advisor_assignments WHERE advisor_id = $aid AND status='active'")->fetchAll(PDO::FETCH_COLUMN);
$stuIdStr = implode(',', $stuIds ?: [0]);

$atRiskCount = $db->query("SELECT COUNT(DISTINCT student_id) FROM academic_alerts WHERE student_id IN ($stuIdStr) AND severity IN ('danger','critical') AND is_resolved=0")->fetchColumn();

// Student risk distribution
$students = [];
foreach ($stuIds as $sid) {
    $stData = $db->prepare("SELECT name, user_id FROM users WHERE id=?");
    $stData->execute([$sid]);
    $st = $stData->fetch();
    $st['risk'] = calculateRiskScore($sid);
    $st['id'] = $sid;
    $students[] = $st;
}

$riskDist = ['stable'=>0, 'monitor'=>0, 'at_risk'=>0, 'high_risk'=>0];
foreach ($students as $s) { $riskDist[$s['risk']['level']]++; }

// Recent requests
$requests = $db->query("SELECT cr.*, u.name as student_name FROM contact_requests cr JOIN users u ON cr.student_id = u.id WHERE cr.advisor_id = $aid ORDER BY cr.created_at DESC LIMIT 5")->fetchAll();

// Recent alerts
$alerts = $db->query("SELECT aa.*, u.name as student_name FROM academic_alerts aa JOIN users u ON aa.student_id = u.id WHERE aa.student_id IN ($stuIdStr) AND aa.is_resolved=0 ORDER BY FIELD(aa.severity,'critical','danger','warning','info'), aa.created_at DESC LIMIT 6")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <div class="welcome-banner">
        <h3>Welcome, <?= e($_SESSION['user_name']) ?> 👋</h3>
        <p>Monitor your students' academic progress and respond to their needs</p>
        <div class="welcome-date"><i class="fas fa-calendar-alt me-1"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="stat-card gradient-1"><div class="stat-icon primary"><i class="fas fa-user-graduate"></i></div><div class="stat-value" data-count="<?= $assignedStudents ?>"><?= $assignedStudents ?></div><div class="stat-label">Assigned Students</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card gradient-4"><div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-value" data-count="<?= $atRiskCount ?>"><?= $atRiskCount ?></div><div class="stat-label">At-Risk Students</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card gradient-3"><div class="stat-icon warning"><i class="fas fa-envelope"></i></div><div class="stat-value" data-count="<?= $pendingRequests ?>"><?= $pendingRequests ?></div><div class="stat-label">Pending Requests</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card gradient-2"><div class="stat-icon success"><i class="fas fa-bell"></i></div><div class="stat-value" data-count="<?= $unreadAlerts ?>"><?= $unreadAlerts ?></div><div class="stat-label">Unread Alerts</div></div></div>
    </div>

    <div class="row g-4">
        <!-- Risk Distribution -->
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-header"><h6><i class="fas fa-chart-pie me-2"></i>Student Risk Distribution</h6></div><div class="card-body"><div style="height:250px"><canvas id="riskChart"></canvas></div></div></div>
        </div>
        <!-- Student List -->
        <div class="col-lg-8">
            <div class="card h-100"><div class="card-header"><h6><i class="fas fa-users me-2"></i>My Students</h6><a href="<?= BASE_URL ?>/advisor/students.php" class="btn btn-sm btn-outline-primary">View All</a></div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Student</th><th>ID</th><th>Avg Grade</th><th>Trend</th><th>Risk</th></tr></thead><tbody>
            <?php foreach ($students as $st): $risk = $st['risk']; $badge = getRiskBadge($risk['level']); ?>
            <tr>
                <td><div class="d-flex align-items-center gap-2"><div class="student-avatar" style="width:30px;height:30px;font-size:.65rem"><?= getInitials($st['name']) ?></div><span class="fw-semibold"><?= e($st['name']) ?></span></div></td>
                <td><code><?= e($st['user_id']) ?></code></td>
                <td><span class="grade-cell <?= $risk['avg_grade']>=80?'high':($risk['avg_grade']>=60?'mid':'low') ?>"><?= $risk['avg_grade'] ?>%</span></td>
                <td><i class="fas fa-<?= $risk['trend']==='improving'?'arrow-up text-success':($risk['trend']==='declining'?'arrow-down text-danger':'minus text-secondary') ?>"></i></td>
                <td><span class="risk-badge risk-<?= $risk['level'] ?>"><i class="fas <?= $badge['icon'] ?> me-1"></i><?= $badge['label'] ?></span></td>
            </tr>
            <?php endforeach; ?></tbody></table></div></div></div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Priority Alerts -->
        <div class="col-lg-6">
            <h6 class="section-title"><i class="fas fa-bell"></i> Priority Alerts</h6>
            <?php foreach ($alerts as $al): ?>
            <div class="alert-card severity-<?= $al['severity'] ?>">
                <div class="d-flex justify-content-between"><span class="alert-title"><?= e($al['title']) ?></span><span class="badge <?= getSeverityBadge($al['severity'])['class'] ?>"><?= ucfirst($al['severity']) ?></span></div>
                <div class="alert-message"><?= e($al['student_name']) ?> — <?= e(substr($al['message'],0,100)) ?>...</div>
                <div class="alert-time"><?= timeAgo($al['created_at']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Recent Requests -->
        <div class="col-lg-6">
            <h6 class="section-title"><i class="fas fa-envelope"></i> Recent Requests</h6>
            <?php foreach ($requests as $req): $sBadge = getRequestStatusBadge($req['status']); ?>
            <div class="request-item">
                <div class="request-header">
                    <span class="request-subject"><?= e($req['subject']) ?></span>
                    <span class="badge <?= $sBadge['class'] ?>"><i class="fas <?= $sBadge['icon'] ?> me-1"></i><?= $sBadge['label'] ?></span>
                </div>
                <div class="request-message"><?= e($req['message']) ?></div>
                <div class="request-meta"><span><i class="fas fa-user me-1"></i><?= e($req['student_name']) ?></span><span><i class="fas fa-clock me-1"></i><?= timeAgo($req['created_at']) ?></span></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const riskCtx = document.getElementById('riskChart');
    if (riskCtx) {
        new Chart(riskCtx, {
            type: 'doughnut',
            data: {
                labels: ['Stable', 'Monitor', 'At Risk', 'High Risk'],
                datasets: [{
                    data: [<?= $riskDist['stable'] ?>, <?= $riskDist['monitor'] ?>, <?= $riskDist['at_risk'] ?>, <?= $riskDist['high_risk'] ?>],
                    backgroundColor: ['#27ae60', '#f39c12', '#e67e22', '#e74c3c'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Inter', size: 11 },
                            usePointStyle: true,
                            padding: 12
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
