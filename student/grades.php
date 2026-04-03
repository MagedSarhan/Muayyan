<?php
/** Muayyan - Student Grades */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'My Grades';
$sid = $_SESSION['user_id'];

$courses = $db->query("SELECT c.code, c.name, s.id as section_id FROM courses c JOIN sections s ON c.id=s.course_id JOIN section_students ss ON s.id=ss.section_id WHERE ss.student_id=$sid ORDER BY c.code")->fetchAll();

$courseData = [];
foreach ($courses as $c) {
    $gStmt = $db->prepare("SELECT g.score, a.max_score, a.title, a.type, a.weight_percentage, a.due_date FROM grades g JOIN assessments a ON g.assessment_id=a.id WHERE g.student_id=? AND a.section_id=? ORDER BY a.due_date");
    $gStmt->execute([$sid, $c['section_id']]);
    $grades = $gStmt->fetchAll();
    $total = 0; $cnt = 0;
    foreach ($grades as $g) { $total += ($g['score']/$g['max_score'])*100; $cnt++; }
    $c['grades'] = $grades;
    $c['avg'] = $cnt > 0 ? round($total/$cnt, 1) : 0;
    $courseData[] = $c;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper" id="reportContent">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>My Grades & Performance</h5>
        <button class="btn btn-outline-primary" onclick="printReport('reportContent')"><i class="fas fa-print me-1"></i>Print</button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8"><div class="card"><div class="card-header"><h6>Grade Trend</h6></div><div class="card-body"><div style="height:280px"><canvas id="trendChart"></canvas></div></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h6>Course Averages</h6></div><div class="card-body">
            <?php foreach ($courseData as $cd): ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1" style="font-size:.8rem"><span class="fw-semibold"><?= e($cd['code']) ?></span><span class="grade-cell <?= $cd['avg']>=80?'high':($cd['avg']>=60?'mid':'low') ?>"><?= $cd['avg'] ?>%</span></div>
                <div class="progress" style="height:6px"><div class="progress-bar <?= $cd['avg']>=80?'success':($cd['avg']>=60?'warning':'danger') ?>" style="width:<?= $cd['avg'] ?>%"></div></div>
            </div>
            <?php endforeach; ?>
        </div></div></div>
    </div>

    <?php foreach ($courseData as $cd): if (empty($cd['grades'])) continue; ?>
    <div class="card mb-3">
        <div class="card-header"><h6><i class="fas fa-book me-2"></i><?= e($cd['code']) ?> — <?= e($cd['name']) ?></h6><span class="badge <?= $cd['avg']>=80?'bg-success':($cd['avg']>=60?'bg-warning text-dark':'bg-danger') ?>">Avg: <?= $cd['avg'] ?>%</span></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Assessment</th><th>Type</th><th>Weight</th><th>Score</th><th>%</th></tr></thead><tbody>
        <?php foreach ($cd['grades'] as $g): $pct = round(($g['score']/$g['max_score'])*100); ?>
        <tr>
            <td class="fw-semibold"><?= e($g['title']) ?></td>
            <td><span class="badge <?= getAssessmentBadge($g['type'])['class'] ?>"><?= ucfirst($g['type']) ?></span></td>
            <td><?= $g['weight_percentage'] ?>%</td>
            <td><?= $g['score'] ?>/<?= $g['max_score'] ?></td>
            <td><span class="grade-cell <?= $pct>=80?'high':($pct>=60?'mid':'low') ?>"><?= $pct ?>%</span></td>
        </tr>
        <?php endforeach; ?></tbody></table></div></div>
    </div>
    <?php endforeach; ?>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
<?php
// Collect all grades chronologically for trend chart
$allGrades = $db->query("SELECT g.score, a.max_score, a.title, a.due_date FROM grades g JOIN assessments a ON g.assessment_id=a.id WHERE g.student_id=$sid ORDER BY a.due_date")->fetchAll();
$labels = []; $values = [];
foreach ($allGrades as $ag) { $labels[] = substr($ag['title'],0,15); $values[] = round(($ag['score']/$ag['max_score'])*100,1); }
?>
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach($labels as $l) echo "'".addslashes($l)."',"; ?>],
                datasets: [{
                    label: 'Score %',
                    data: [<?= implode(',',$values) ?>],
                    borderColor: '#1e6fa0',
                    backgroundColor: 'rgba(30,111,160,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1e6fa0',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } }
                }
            }
        });
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
