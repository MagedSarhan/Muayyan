<?php
/** AALMAS - Faculty Workload/Assessment Density */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('faculty');
$db = getDBConnection();
$pageTitle = 'Assessment Density';
$fid = $_SESSION['user_id'];

$assessments = $db->query("SELECT a.*, c.code, s.section_number FROM assessments a JOIN sections s ON a.section_id = s.id JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid AND a.due_date >= CURDATE() - INTERVAL 30 DAY ORDER BY a.due_date")->fetchAll();

// Group by week
$weeks = [];
foreach ($assessments as $a) {
    $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($a['due_date'])));
    $weeks[$weekStart][] = $a;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <h5 class="mb-4 fw-bold"><i class="fas fa-calendar-alt me-2 text-primary"></i>Assessment Density</h5>
    <p class="text-muted mb-4">View assessment distribution across weeks to identify periods of high workload for students.</p>
    <?php foreach ($weeks as $weekStart => $weekAssess): 
        $count = count($weekAssess);
        $level = $count >= 4 ? 'danger' : ($count >= 3 ? 'warning' : ($count >= 2 ? 'info' : 'success'));
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Week of <?= formatDate($weekStart) ?></h6>
            <span class="badge bg-<?= $level ?>"><?= $count ?> assessment<?= $count>1?'s':'' ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive"><table class="table mb-0">
                <thead><tr><th>Assessment</th><th>Course</th><th>Type</th><th>Due Date</th><th>Max Score</th></tr></thead>
                <tbody>
                <?php foreach ($weekAssess as $wa): ?>
                <tr>
                    <td class="fw-semibold"><?= e($wa['title']) ?></td>
                    <td><?= e($wa['code']) ?> (<?= $wa['section_number'] ?>)</td>
                    <td><span class="badge <?= getAssessmentBadge($wa['type'])['class'] ?>"><?= ucfirst($wa['type']) ?></span></td>
                    <td><?= formatDate($wa['due_date']) ?></td>
                    <td><?= $wa['max_score'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($weeks)): ?>
    <div class="empty-state"><i class="fas fa-calendar-check"></i><h5>No Upcoming Assessments</h5><p>No assessments scheduled for the next 30 days.</p></div>
    <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
