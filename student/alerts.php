<?php
/** Muayyan - Student Alerts */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'My Alerts';
$sid = $_SESSION['user_id'];

// Mark alert as read
if (isset($_GET['read'])) {
    $db->prepare("UPDATE academic_alerts SET is_read=1 WHERE id=? AND student_id=?")->execute([$_GET['read'], $sid]);
    header('Location: ' . BASE_URL . '/student/alerts.php'); exit;
}

$alerts = $db->query("SELECT aa.*, c.code as course_code, c.name as course_name FROM academic_alerts aa LEFT JOIN sections s ON aa.section_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE aa.student_id=$sid ORDER BY aa.is_read ASC, FIELD(aa.severity,'critical','danger','warning','info'), aa.created_at DESC")->fetchAll();

$risk = calculateRiskScore($sid);
$badge = getRiskBadge($risk['level']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <h5 class="mb-4 fw-bold"><i class="fas fa-exclamation-triangle me-2 text-primary"></i>Academic Alerts</h5>
    
    <!-- Risk Status Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="risk-indicator" style="border:none;padding:0;flex:1;min-width:250px">
                    <div class="risk-circle <?= $risk['level'] ?>" style="width:65px;height:65px;font-size:1.3rem"><?= round($risk['score']) ?></div>
                    <div class="risk-info">
                        <h5>Your Academic Status: <?= $badge['label'] ?></h5>
                        <p>Average: <?= $risk['avg_grade'] ?>% | Trend: <?= ucfirst($risk['trend']) ?> | Risk Score: <?= $risk['score'] ?>/100</p>
                    </div>
                </div>
                <div class="d-flex gap-2" style="font-size:.8rem">
                    <span class="risk-badge risk-stable"><i class="fas fa-check-circle me-1"></i>Stable: ≥80</span>
                    <span class="risk-badge risk-monitor"><i class="fas fa-eye me-1"></i>Monitor: 60-79</span>
                    <span class="risk-badge risk-at_risk"><i class="fas fa-exclamation-triangle me-1"></i>At Risk: 40-59</span>
                    <span class="risk-badge risk-high_risk"><i class="fas fa-times-circle me-1"></i>High Risk: &lt;40</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($alerts)): ?>
    <div class="empty-state">
        <i class="fas fa-check-circle text-success"></i>
        <h5>No Alerts</h5>
        <p>You're doing great! No academic alerts at this time.</p>
    </div>
    <?php else: ?>
    <?php foreach ($alerts as $al): $sev = getSeverityBadge($al['severity']); ?>
    <div class="alert-card severity-<?= $al['severity'] ?> <?= $al['is_read'] ? 'opacity-75' : '' ?>" style="border:1px solid var(--gray-200)">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex gap-3 align-items-start flex-fill">
                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:40px;height:40px;background:<?= $al['severity']==='critical'?'rgba(45,52,54,.1)':($al['severity']==='danger'?'rgba(231,76,60,.1)':($al['severity']==='warning'?'rgba(243,156,18,.1)':'rgba(52,152,219,.1)')) ?>">
                    <i class="fas <?= $sev['icon'] ?>" style="color:<?= $al['severity']==='critical'?'#2d3436':($al['severity']==='danger'?'var(--danger)':($al['severity']==='warning'?'var(--warning)':'var(--info)')) ?>"></i>
                </div>
                <div>
                    <div class="alert-title d-flex align-items-center gap-2">
                        <?= e($al['title']) ?>
                        <?php if (!$al['is_read']): ?><span class="badge bg-danger" style="font-size:.6rem">NEW</span><?php endif; ?>
                    </div>
                    <div class="alert-message mt-1"><?= e($al['message']) ?></div>
                    <div class="alert-time mt-2">
                        <?php if ($al['course_code']): ?><span class="me-3"><i class="fas fa-book me-1"></i><?= e($al['course_code']) ?> — <?= e($al['course_name']) ?></span><?php endif; ?>
                        <span><i class="fas fa-clock me-1"></i><?= timeAgo($al['created_at']) ?></span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 ms-3">
                <span class="badge <?= $sev['class'] ?>"><i class="fas <?= $sev['icon'] ?> me-1"></i><?= ucfirst($al['severity']) ?></span>
                <?php if (!$al['is_read']): ?>
                <a href="?read=<?= $al['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Mark as read"><i class="fas fa-check"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
