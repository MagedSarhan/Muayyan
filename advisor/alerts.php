<?php
/** Muayyan - Advisor Alerts */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('advisor');
$db = getDBConnection();
$pageTitle = 'Academic Alerts';
$aid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    $db->prepare("UPDATE academic_alerts SET is_resolved=1, is_read=1 WHERE id=?")->execute([$_POST['resolve_id']]);
    setFlash('success', 'Alert resolved.'); header('Location: ' . BASE_URL . '/advisor/alerts.php'); exit;
}

$alerts = $db->query("SELECT aa.*, u.name as student_name, u.user_id as stu_uid, c.code as course_code FROM academic_alerts aa JOIN users u ON aa.student_id=u.id LEFT JOIN sections s ON aa.section_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE aa.student_id IN (SELECT student_id FROM advisor_assignments WHERE advisor_id=$aid) ORDER BY aa.is_resolved ASC, FIELD(aa.severity,'critical','danger','warning','info'), aa.created_at DESC")->fetchAll();
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <h5 class="mb-4 fw-bold"><i class="fas fa-bell me-2 text-primary"></i>Academic Alerts</h5>
    <?php foreach ($alerts as $al): $sev = getSeverityBadge($al['severity']); ?>
    <div class="alert-card severity-<?= $al['severity'] ?> <?= $al['is_resolved'] ? 'opacity-50' : '' ?>" style="border:1px solid var(--gray-200)">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex gap-3 align-items-start">
                <div class="student-avatar" style="width:36px;height:36px;font-size:.7rem"><?= getInitials($al['student_name']) ?></div>
                <div>
                    <div class="alert-title"><?= e($al['title']) ?></div>
                    <div class="alert-message"><?= e($al['message']) ?></div>
                    <div class="alert-time mt-1">
                        <span class="me-3"><i class="fas fa-user me-1"></i><?= e($al['student_name']) ?> (<?= e($al['stu_uid']) ?>)</span>
                        <?php if ($al['course_code']): ?><span class="me-3"><i class="fas fa-book me-1"></i><?= e($al['course_code']) ?></span><?php endif; ?>
                        <span><i class="fas fa-clock me-1"></i><?= timeAgo($al['created_at']) ?></span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge <?= $sev['class'] ?>"><i class="fas <?= $sev['icon'] ?> me-1"></i><?= ucfirst($al['severity']) ?></span>
                <?php if (!$al['is_resolved']): ?>
                <form method="POST"><input type="hidden" name="resolve_id" value="<?= $al['id'] ?>"><button class="btn btn-sm btn-outline-success" title="Resolve"><i class="fas fa-check"></i></button></form>
                <?php else: ?>
                <span class="badge bg-secondary">Resolved</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
