<?php
/** MOEEN  - Admin Reports */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$db = getDBConnection();
$pageTitle = 'Reports & Statistics';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';

$gradeAvg = $db->query("SELECT AVG(g.score/a.max_score*100) as avg FROM grades g JOIN assessments a ON g.assessment_id = a.id")->fetchColumn();
$atRiskCount = $db->query("SELECT COUNT(DISTINCT student_id) FROM academic_alerts WHERE severity IN ('danger','critical') AND is_resolved=0")->fetchColumn();
$pendingRequests = $db->query("SELECT COUNT(*) FROM contact_requests WHERE status IN ('sent','under_review')")->fetchColumn();
?>
<main class="main-content">
    <div class="content-wrapper" id="reportContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i>System Reports</h5>
            <button class="btn btn-outline-primary no-print" onclick="printReport('reportContent')"><i
                    class="fas fa-print me-1"></i> Print</button>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fas fa-percentage"></i></div>
                    <div class="stat-value"><?= round($gradeAvg, 1) ?>%</div>
                    <div class="stat-label">Overall Grade Average</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-value"><?= $atRiskCount ?></div>
                    <div class="stat-label">At-Risk Students</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fas fa-envelope-open"></i></div>
                    <div class="stat-value"><?= $pendingRequests ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Grade Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div style="height:300px"><canvas id="gradeDist"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Assessments by Type</h6>
                    </div>
                    <div class="card-body">
                        <div style="height:300px"><canvas id="assessTypes"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6>Course Performance Comparison</h6>
                    </div>
                    <div class="card-body">
                        <div style="height:300px"><canvas id="coursePerf"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php
        $dist = $db->query("SELECT CASE WHEN g.score/a.max_score >= 0.9 THEN 'A+/A' WHEN g.score/a.max_score >= 0.8 THEN 'B+/B' WHEN g.score/a.max_score >= 0.7 THEN 'C+/C' WHEN g.score/a.max_score >= 0.6 THEN 'D+/D' ELSE 'F' END as grade_letter, COUNT(*) as cnt FROM grades g JOIN assessments a ON g.assessment_id = a.id GROUP BY grade_letter ORDER BY grade_letter")->fetchAll();
        $aTypes = $db->query("SELECT type, COUNT(*) as cnt FROM assessments GROUP BY type ORDER BY cnt DESC")->fetchAll();
        $cPerf = $db->query("SELECT c.code, ROUND(AVG(g.score/a.max_score*100),1) as avg_pct FROM grades g JOIN assessments a ON g.assessment_id = a.id JOIN sections s ON a.section_id = s.id JOIN courses c ON s.course_id = c.id GROUP BY c.code ORDER BY c.code")->fetchAll();
        ?>
        new Chart(document.getElementById('gradeDist'), { type: 'bar', data: { labels: [<?php foreach ($dist as $d)
            echo "'$d[grade_letter]',"; ?>], datasets: [{ label: 'Students', data: [<?php foreach ($dist as $d)
                  echo "$d[cnt],"; ?>], backgroundColor: ['#27ae60', '#2ecc71', '#f39c12', '#e67e22', '#e74c3c'], borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } } });
        new Chart(document.getElementById('assessTypes'), { type: 'doughnut', data: { labels: [<?php foreach ($aTypes as $t)
            echo "'" . ucfirst($t['type']) . "',"; ?>], datasets: [{ data: [<?php foreach ($aTypes as $t)
                  echo "$t[cnt],"; ?>], backgroundColor: ['#1e6fa0', '#27ae60', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#95a5a6'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { family: 'Inter', size: 11 }, usePointStyle: true } } } } });
        new Chart(document.getElementById('coursePerf'), { type: 'bar', data: { labels: [<?php foreach ($cPerf as $cp)
            echo "'$cp[code]',"; ?>], datasets: [{ label: 'Avg Grade %', data: [<?php foreach ($cPerf as $cp)
                  echo "$cp[avg_pct],"; ?>], backgroundColor: 'rgba(30,111,160,0.7)', borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { max: 100, grid: { color: '#f0f0f0' } }, y: { grid: { display: false } } } } });
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>