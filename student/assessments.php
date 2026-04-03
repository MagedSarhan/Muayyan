<?php
/** AALMAS - Student Assessments */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'Assessments';
$sid = $_SESSION['user_id'];
$assessments = $db->query("SELECT a.*, c.code, c.name as course_name, s.section_number, g.score FROM assessments a JOIN sections s ON a.section_id=s.id JOIN courses c ON s.course_id=c.id JOIN section_students ss ON s.id=ss.section_id LEFT JOIN grades g ON g.assessment_id=a.id AND g.student_id=$sid WHERE ss.student_id=$sid ORDER BY a.due_date DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <h5 class="mb-4 fw-bold"><i class="fas fa-clipboard-list me-2 text-primary"></i>My Assessments</h5>
    <div class="table-container"><div class="table-responsive"><table class="table">
        <thead><tr><th>Assessment</th><th>Course</th><th>Type</th><th>Due Date</th><th>Max</th><th>My Score</th><th>%</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($assessments as $a): 
            $pct = ($a['score'] !== null && $a['max_score'] > 0) ? round(($a['score']/$a['max_score'])*100) : null;
            $daysLeft = (strtotime($a['due_date']) - time()) / 86400;
        ?>
        <tr>
            <td class="fw-semibold"><?= e($a['title']) ?></td>
            <td><?= e($a['code']) ?> (<?= $a['section_number'] ?>)</td>
            <td><span class="badge <?= getAssessmentBadge($a['type'])['class'] ?>"><i class="fas <?= getAssessmentBadge($a['type'])['icon'] ?> me-1"></i><?= ucfirst($a['type']) ?></span></td>
            <td>
                <?= formatDate($a['due_date']) ?>
                <?php if ($daysLeft > 0 && $daysLeft <= 7 && $a['status'] !== 'graded'): ?>
                <br><small class="text-danger"><i class="fas fa-clock"></i> <?= ceil($daysLeft) ?> days left</small>
                <?php endif; ?>
            </td>
            <td><?= $a['max_score'] ?></td>
            <td><?php if ($a['score'] !== null): ?><span class="grade-cell <?= $pct>=80?'high':($pct>=60?'mid':'low') ?>"><?= $a['score'] ?></span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
            <td><?php if ($pct !== null): ?><span class="grade-cell <?= $pct>=80?'high':($pct>=60?'mid':'low') ?>"><?= $pct ?>%</span><?php else: ?>—<?php endif; ?></td>
            <td><span class="badge <?= $a['status']==='graded'?'bg-success':($a['status']==='active'?'bg-warning text-dark':'bg-info') ?>"><?= ucfirst($a['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
