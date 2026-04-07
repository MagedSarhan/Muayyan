<?php
/** MOEEN  - Advisor Student List */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('advisor');
$db = getDBConnection();
$pageTitle = 'My Students';
$aid = $_SESSION['user_id'];

$students = $db->query("SELECT u.id, u.name, u.user_id, u.email, u.department, (SELECT COUNT(*) FROM section_students ss WHERE ss.student_id = u.id) as course_count FROM users u JOIN advisor_assignments aa ON u.id = aa.student_id WHERE aa.advisor_id = $aid AND aa.status='active' ORDER BY u.name")->fetchAll();
foreach ($students as &$st) {
    $st['risk'] = calculateRiskScore($st['id']);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <h5 class="mb-4 fw-bold"><i class="fas fa-user-graduate me-2 text-primary"></i>My Students</h5>
        <div class="row g-3">
            <?php foreach ($students as $st):
                $risk = $st['risk'];
                $badge = getRiskBadge($risk['level']); ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="student-avatar" style="width:50px;height:50px;font-size:1rem">
                                    <?= getInitials($st['name']) ?></div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= e($st['name']) ?></h6><small
                                        class="text-muted"><?= e($st['user_id']) ?> &bull;
                                        <?= e($st['department']) ?></small>
                                </div>
                            </div>
                            <div class="risk-indicator mb-3" style="padding:12px">
                                <div class="risk-circle <?= $risk['level'] ?>"
                                    style="width:45px;height:45px;font-size:.9rem"><?= round($risk['score']) ?></div>
                                <div class="risk-info">
                                    <h5 style="font-size:.85rem"><?= $badge['label'] ?></h5>
                                    <p style="font-size:.72rem">Avg: <?= $risk['avg_grade'] ?>% |
                                        <?= ucfirst($risk['trend']) ?></p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-2" style="font-size:.8rem"><span
                                    class="text-muted">Courses Enrolled</span><strong><?= $st['course_count'] ?></strong>
                            </div>
                            <div class="progress mb-3" style="height:6px">
                                <div class="progress-bar <?= $risk['score'] >= 80 ? 'success' : ($risk['score'] >= 60 ? 'warning' : 'danger') ?>"
                                    style="width:<?= $risk['score'] ?>%"></div>
                            </div>
                            <a href="<?= BASE_URL ?>/advisor/reports.php?student=<?= $st['id'] ?>"
                                class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-chart-line me-1"></i>View
                                Report</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>