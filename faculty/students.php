<?php
/** AALMAS - Faculty Student Performance */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('faculty');
$db = getDBConnection();
$pageTitle = 'Student Performance';
$fid = $_SESSION['user_id'];

$sectionId = $_GET['section'] ?? '';
$mySections = $db->query("SELECT s.id, c.code, c.name, s.section_number FROM sections s JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid AND s.status='active' ORDER BY c.code")->fetchAll();

$students = [];
if ($sectionId) {
    $students = $db->prepare("SELECT u.id, u.name, u.user_id, u.email FROM users u JOIN section_students ss ON u.id = ss.student_id WHERE ss.section_id = ? ORDER BY u.name");
    $students->execute([$sectionId]);
    $students = $students->fetchAll();
    foreach ($students as &$st) {
        $st['risk'] = calculateRiskScore($st['id'], $sectionId);
        $gStmt = $db->prepare("SELECT g.score, a.max_score, a.title, a.type FROM grades g JOIN assessments a ON g.assessment_id = a.id WHERE g.student_id = ? AND a.section_id = ? ORDER BY a.due_date");
        $gStmt->execute([$st['id'], $sectionId]);
        $st['grades'] = $gStmt->fetchAll();
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <h5 class="mb-4 fw-bold"><i class="fas fa-user-graduate me-2 text-primary"></i>Student Performance</h5>
    <div class="filter-bar mb-4">
        <form method="GET" class="d-flex gap-2"><select name="section" class="form-select" onchange="this.form.submit()"><option value="">Select Section</option><?php foreach($mySections as $ms): ?><option value="<?= $ms['id'] ?>" <?= $sectionId==$ms['id']?'selected':'' ?>><?= e($ms['code'].' - '.$ms['name'].' (Sec '.$ms['section_number'].')') ?></option><?php endforeach; ?></select></form>
    </div>
    <?php if (!empty($students)): ?>
    <div class="table-container"><div class="table-responsive"><table class="table">
        <thead><tr><th>Student</th><th>ID</th><th>Avg Grade</th><th>Trend</th><th>Risk Level</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($students as $st): $risk = $st['risk']; $badge = getRiskBadge($risk['level']); ?>
        <tr>
            <td><div class="d-flex align-items-center gap-2"><div class="student-avatar" style="width:32px;height:32px;font-size:.7rem"><?= getInitials($st['name']) ?></div><span class="fw-semibold"><?= e($st['name']) ?></span></div></td>
            <td><code><?= e($st['user_id']) ?></code></td>
            <td><span class="grade-cell <?= $risk['avg_grade'] >= 80 ? 'high' : ($risk['avg_grade'] >= 60 ? 'mid' : 'low') ?>"><?= $risk['avg_grade'] ?>%</span></td>
            <td><span class="badge <?= $risk['trend']==='improving'?'bg-success':($risk['trend']==='declining'?'bg-danger':'bg-secondary') ?>"><i class="fas fa-<?= $risk['trend']==='improving'?'arrow-up':($risk['trend']==='declining'?'arrow-down':'minus') ?> me-1"></i><?= ucfirst($risk['trend']) ?></span></td>
            <td><span class="risk-badge risk-<?= $risk['level'] ?>"><i class="fas <?= $badge['icon'] ?> me-1"></i><?= $badge['label'] ?></span></td>
            <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#studentDetail<?= $st['id'] ?>"><i class="fas fa-eye me-1"></i>Details</button></td>
        </tr>
        <!-- Student Detail Modal -->
        <div class="modal fade" id="studentDetail<?= $st['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><?= e($st['name']) ?> — Performance Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="risk-indicator mb-3"><div class="risk-circle <?= $risk['level'] ?>"><?= round($risk['score']) ?></div><div class="risk-info"><h5><?= $badge['label'] ?></h5><p>Risk Score: <?= $risk['score'] ?>/100 | Avg: <?= $risk['avg_grade'] ?>%</p></div></div>
                <h6 class="fw-bold mb-2">Grade Breakdown</h6>
                <table class="table table-sm"><thead><tr><th>Assessment</th><th>Type</th><th>Score</th><th>%</th></tr></thead><tbody>
                <?php foreach ($st['grades'] as $g): $pct = round(($g['score']/$g['max_score'])*100); ?>
                <tr><td><?= e($g['title']) ?></td><td><span class="badge <?= getAssessmentBadge($g['type'])['class'] ?>"><?= ucfirst($g['type']) ?></span></td><td><?= $g['score'] ?>/<?= $g['max_score'] ?></td><td><span class="grade-cell <?= $pct>=80?'high':($pct>=60?'mid':'low') ?>"><?= $pct ?>%</span></td></tr>
                <?php endforeach; ?></tbody></table>
            </div>
        </div></div></div>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
    <?php elseif (!$sectionId): ?>
    <div class="empty-state"><i class="fas fa-hand-point-up"></i><h5>Select a Section</h5><p>Choose a section to view student performance.</p></div>
    <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
