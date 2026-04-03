<?php
/** Muayyan - Advisor Reports */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('advisor');
$db = getDBConnection();
$pageTitle = 'Analytical Reports';
$aid = $_SESSION['user_id'];

$stuIds = $db->query("SELECT student_id FROM advisor_assignments WHERE advisor_id=$aid AND status='active'")->fetchAll(PDO::FETCH_COLUMN);
$stuIdStr = implode(',', $stuIds ?: [0]);

$studentId = $_GET['student'] ?? '';
$students = $db->query("SELECT id, name, user_id FROM users WHERE id IN ($stuIdStr) ORDER BY name")->fetchAll();

$studentData = null;
$grades = [];
$courses = [];
if ($studentId) {
    $stStmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stStmt->execute([$studentId]);
    $studentData = $stStmt->fetch();
    $studentData['risk'] = calculateRiskScore($studentId);
    
    $courses = $db->query("SELECT c.code, c.name, s.id as section_id FROM courses c JOIN sections s ON c.id=s.course_id JOIN section_students ss ON s.id=ss.section_id WHERE ss.student_id=$studentId")->fetchAll();
    foreach ($courses as &$c) {
        $gStmt = $db->prepare("SELECT g.score, a.max_score, a.title, a.type, a.due_date FROM grades g JOIN assessments a ON g.assessment_id=a.id WHERE g.student_id=? AND a.section_id=? ORDER BY a.due_date");
        $gStmt->execute([$studentId, $c['section_id']]);
        $c['grades'] = $gStmt->fetchAll();
        $total = 0; $cnt = 0;
        foreach ($c['grades'] as $g) { $total += ($g['score']/$g['max_score'])*100; $cnt++; }
        $c['avg'] = $cnt > 0 ? round($total/$cnt, 1) : 0;
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper" id="reportContent">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Student Reports</h5>
        <?php if ($studentData): ?><button class="btn btn-outline-primary" onclick="printReport('reportContent')"><i class="fas fa-print me-1"></i>Print</button><?php endif; ?>
    </div>
    <div class="filter-bar mb-4">
        <form method="GET" class="d-flex gap-2"><select name="student" class="form-select" onchange="this.form.submit()"><option value="">Select Student</option><?php foreach($students as $s): ?><option value="<?= $s['id'] ?>" <?= $studentId==$s['id']?'selected':'' ?>><?= e($s['name'].' ('.$s['user_id'].')') ?></option><?php endforeach; ?></select></form>
    </div>
    <?php if ($studentData): $risk = $studentData['risk']; $badge = getRiskBadge($risk['level']); ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body text-center">
                <div class="student-avatar mx-auto mb-3" style="width:60px;height:60px;font-size:1.2rem"><?= getInitials($studentData['name']) ?></div>
                <h5 class="fw-bold"><?= e($studentData['name']) ?></h5>
                <p class="text-muted mb-3"><?= e($studentData['user_id']) ?> &bull; <?= e($studentData['department']) ?></p>
                <div class="risk-indicator justify-content-center"><div class="risk-circle <?= $risk['level'] ?>"><?= round($risk['score']) ?></div><div class="risk-info text-start"><h5><?= $badge['label'] ?></h5><p>Avg: <?= $risk['avg_grade'] ?>% | <?= ucfirst($risk['trend']) ?></p></div></div>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card h-100"><div class="card-header"><h6>Performance by Course</h6></div><div class="card-body"><div style="height:250px"><canvas id="courseChart"></canvas></div></div></div>
        </div>
        <div class="col-12">
            <div class="card"><div class="card-header"><h6>Detailed Grade Breakdown</h6></div><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Course</th><th>Assessment</th><th>Type</th><th>Score</th><th>%</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($courses as $c): foreach ($c['grades'] as $g): $pct = round(($g['score']/$g['max_score'])*100); ?>
            <tr><td><?= e($c['code']) ?></td><td><?= e($g['title']) ?></td><td><span class="badge <?= getAssessmentBadge($g['type'])['class'] ?>"><?= ucfirst($g['type']) ?></span></td><td><?= $g['score'] ?>/<?= $g['max_score'] ?></td><td><span class="grade-cell <?= $pct>=80?'high':($pct>=60?'mid':'low') ?>"><?= $pct ?>%</span></td><td><?= formatDate($g['due_date']) ?></td></tr>
            <?php endforeach; endforeach; ?></tbody></table></div></div></div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseCtx = document.getElementById('courseChart');
        if (courseCtx) {
            new Chart(courseCtx, {
                type: 'radar',
                data: {
                    labels: [<?php foreach($courses as $c) echo "'$c[code]',"; ?>],
                    datasets: [{
                        label: 'Avg %',
                        data: [<?php foreach($courses as $c) echo "$c[avg],"; ?>],
                        backgroundColor: 'rgba(30,111,160,0.15)',
                        borderColor: '#1e6fa0',
                        pointBackgroundColor: '#1e6fa0',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { font: { size: 10 } }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
    </script>
    <?php else: ?>
    <div class="empty-state"><i class="fas fa-user-graduate"></i><h5>Select a Student</h5><p>Choose a student to view their detailed performance report.</p></div>
    <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
