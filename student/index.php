<?php
/** MOEEN  - Student Dashboard */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'Home';
$sid = $_SESSION['user_id'];

$risk = calculateRiskScore($sid);
$badge = getRiskBadge($risk['level']);

$courseCount = $db->query("SELECT COUNT(*) FROM section_students WHERE student_id=$sid")->fetchColumn();
$upcomingAssess = $db->query("SELECT COUNT(*) FROM assessments a JOIN sections s ON a.section_id=s.id JOIN section_students ss ON s.id=ss.section_id WHERE ss.student_id=$sid AND a.due_date >= CURDATE() AND a.status IN ('upcoming','active')")->fetchColumn();
$alertCount = $db->query("SELECT COUNT(*) FROM academic_alerts WHERE student_id=$sid AND is_read=0")->fetchColumn();

// Performance data per course
$courses = $db->query("SELECT c.code, c.name, s.id as section_id FROM courses c JOIN sections s ON c.id=s.course_id JOIN section_students ss ON s.id=ss.section_id WHERE ss.student_id=$sid ORDER BY c.code")->fetchAll();

$courseLabels = [];
$courseAvgs = [];
foreach ($courses as $c) {
    $gStmt = $db->prepare("SELECT AVG(g.score/a.max_score*100) as avg FROM grades g JOIN assessments a ON g.assessment_id=a.id WHERE g.student_id=? AND a.section_id=?");
    $gStmt->execute([$sid, $c['section_id']]);
    $avg = $gStmt->fetchColumn();
    $courseLabels[] = $c['code'];
    $courseAvgs[] = round($avg ?: 0, 1);
}

// Recent grades
$recentGrades = $db->query("SELECT g.score, a.max_score, a.title, a.type, c.code FROM grades g JOIN assessments a ON g.assessment_id=a.id JOIN sections s ON a.section_id=s.id JOIN courses c ON s.course_id=c.id WHERE g.student_id=$sid ORDER BY g.entered_at DESC LIMIT 5")->fetchAll();

// Upcoming assessments
$upcoming = $db->query("SELECT a.title, a.type, a.due_date, a.max_score, c.code FROM assessments a JOIN sections s ON a.section_id=s.id JOIN section_students ss ON s.id=ss.section_id JOIN courses c ON s.course_id=c.id WHERE ss.student_id=$sid AND a.due_date >= CURDATE() ORDER BY a.due_date LIMIT 5")->fetchAll();

// Alerts
$alerts = $db->query("SELECT * FROM academic_alerts WHERE student_id=$sid AND is_read=0 ORDER BY FIELD(severity,'critical','danger','warning','info') LIMIT 3")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="welcome-banner">
            <h3>Welcome, <?= e($_SESSION['user_name']) ?> 👋</h3>
            <p>Track your academic performance, assessments, and workload</p>
            <div class="welcome-date"><i class="fas fa-calendar-alt me-1"></i> <?= date('l, F j, Y') ?> &bull;
                <?= CURRENT_SEMESTER ?></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card gradient-1">
                    <div class="stat-icon primary"><i class="fas fa-book-open"></i></div>
                    <div class="stat-value" data-count="<?= $courseCount ?>"><?= $courseCount ?></div>
                    <div class="stat-label">Registered Courses</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card gradient-3">
                    <div class="stat-icon warning"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-value" data-count="<?= $upcomingAssess ?>"><?= $upcomingAssess ?></div>
                    <div class="stat-label">Upcoming Assessments</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div
                        class="stat-icon <?= $risk['level'] === 'stable' ? 'success' : ($risk['level'] === 'monitor' ? 'warning' : 'danger') ?>">
                        <i class="fas <?= $badge['icon'] ?>"></i></div>
                    <div class="stat-value" data-count="<?= $risk['avg_grade'] ?>" data-decimals="1">
                        <?= $risk['avg_grade'] ?>%</div>
                    <div class="stat-label">Overall Average</div>
                    <span class="stat-change <?= $risk['trend'] === 'improving' ? 'up' : 'down' ?>"><i
                            class="fas fa-arrow-<?= $risk['trend'] === 'improving' ? 'up' : 'down' ?>"></i>
                        <?= ucfirst($risk['trend']) ?></span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card gradient-4">
                    <div class="stat-icon danger"><i class="fas fa-bell"></i></div>
                    <div class="stat-value" data-count="<?= $alertCount ?>"><?= $alertCount ?></div>
                    <div class="stat-label">Unread Alerts</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Academic Status -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-shield-alt me-2"></i>Academic Status</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="risk-indicator flex-column" style="border:none;padding:0">
                            <div class="risk-circle <?= $risk['level'] ?>"
                                style="width:80px;height:80px;font-size:1.5rem"><?= round($risk['score']) ?></div>
                            <div class="risk-info text-center mt-3">
                                <h5><?= $badge['label'] ?></h5>
                                <p>Risk Score: <?= $risk['score'] ?> / 100</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:.8rem"><span>Overall
                                    Progress</span><span><?= round($risk['score']) ?>%</span></div>
                            <div class="progress">
                                <div class="progress-bar <?= $risk['score'] >= 80 ? 'success' : ($risk['score'] >= 60 ? 'warning' : 'danger') ?>"
                                    style="width:<?= $risk['score'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Radar -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-line me-2"></i>Performance by Course</h6>
                    </div>
                    <div class="card-body">
                        <div style="height:280px"><canvas id="perfChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <!-- Recent Grades -->
            <div class="col-lg-4">
                <h6 class="section-title"><i class="fas fa-star"></i> Recent Grades</h6>
                <?php foreach ($recentGrades as $rg):
                    $pct = round(($rg['score'] / $rg['max_score']) * 100); ?>
                    <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded"
                        style="background:#fff;border:1px solid var(--gray-200)">
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem"><?= e($rg['title']) ?></div>
                            <small class="text-muted"><?= e($rg['code']) ?> &bull; <span
                                    class="badge <?= getAssessmentBadge($rg['type'])['class'] ?>"><?= ucfirst($rg['type']) ?></span></small>
                        </div>
                        <div class="text-end">
                            <div class="grade-cell <?= $pct >= 80 ? 'high' : ($pct >= 60 ? 'mid' : 'low') ?>" style="font-size:1rem">
                                <?= $rg['score'] ?>/<?= $rg['max_score'] ?></div>
                            <small class="text-muted"><?= $pct ?>%</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Upcoming -->
            <div class="col-lg-4">
                <h6 class="section-title"><i class="fas fa-calendar"></i> Upcoming Assessments</h6>
                <?php foreach ($upcoming as $ua):
                    $daysLeft = max(0, (int) ((strtotime($ua['due_date']) - time()) / (60 * 60 * 24))); ?>
                    <div class="timeline-item">
                        <div
                            class="timeline-icon <?= $daysLeft <= 3 ? 'danger' : ($daysLeft <= 7 ? 'warning' : 'primary') ?>">
                            <i class="fas <?= getAssessmentBadge($ua['type'])['icon'] ?>"></i></div>
                        <div class="timeline-content">
                            <h6><?= e($ua['title']) ?></h6>
                            <p><?= e($ua['code']) ?> &bull; <?= $ua['max_score'] ?> pts</p>
                            <span class="time"><i class="fas fa-clock me-1"></i><?= $daysLeft ?>
                                day<?= $daysLeft !== 1 ? 's' : '' ?> left — <?= formatDate($ua['due_date']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Alerts -->
            <div class="col-lg-4">
                <h6 class="section-title"><i class="fas fa-exclamation-triangle"></i> Active Alerts</h6>
                <?php if (empty($alerts)): ?>
                    <div class="text-center p-3"><i class="fas fa-check-circle text-success" style="font-size:2rem"></i>
                        <p class="text-success mt-2 fw-semibold">No active alerts!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($alerts as $al): ?>
                        <div class="alert-card severity-<?= $al['severity'] ?>">
                            <div class="d-flex justify-content-between"><span
                                    class="alert-title"><?= e($al['title']) ?></span><span
                                    class="badge <?= getSeverityBadge($al['severity'])['class'] ?>"><?= ucfirst($al['severity']) ?></span>
                            </div>
                            <div class="alert-message"><?= e(substr($al['message'], 0, 120)) ?>...</div>
                            <div class="alert-time"><?= timeAgo($al['created_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <a href="<?= BASE_URL ?>/student/alerts.php" class="btn btn-sm btn-outline-primary w-100 mt-2"><i
                            class="fas fa-bell me-1"></i>View All Alerts</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const perfChartCtx = document.getElementById('perfChart');
        if (perfChartCtx) {
            new Chart(perfChartCtx, {
                type: 'radar',
                data: {
                    labels: [<?php foreach ($courseLabels as $l)
                        echo "'$l',"; ?>],
                    datasets: [{
                        label: 'Average %',
                        data: [<?php foreach ($courseAvgs as $v)
                            echo "$v,"; ?>],
                        backgroundColor: 'rgba(30,111,160,0.12)',
                        borderColor: '#1e6fa0',
                        pointBackgroundColor: '#1e6fa0',
                        pointRadius: 5,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { stepSize: 20, font: { size: 10 } },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>