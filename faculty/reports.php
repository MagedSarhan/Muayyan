<?php
/** AALMAS - Faculty Reports */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('faculty');
$db = getDBConnection();
$pageTitle = 'Section Reports';
$fid = $_SESSION['user_id'];

$sections = $db->query("SELECT s.id, c.code, c.name, s.section_number, (SELECT COUNT(*) FROM section_students ss WHERE ss.section_id=s.id) as cnt, (SELECT ROUND(AVG(g.score/a.max_score*100),1) FROM grades g JOIN assessments a ON g.assessment_id=a.id WHERE a.section_id=s.id) as avg_grade FROM sections s JOIN courses c ON s.course_id=c.id WHERE s.faculty_id=$fid AND s.status='active' ORDER BY c.code")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper" id="reportContent">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Section Reports</h5>
        <button class="btn btn-outline-primary" onclick="printReport('reportContent')"><i class="fas fa-print me-1"></i>Print</button>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card"><div class="card-header"><h6>Average Performance by Section</h6></div><div class="card-body"><div style="height:300px"><canvas id="sectionChart"></canvas></div></div></div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-header"><h6>Section Summary</h6></div><div class="card-body">
                <?php foreach ($sections as $s): ?>
                <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded" style="background:var(--gray-50)">
                    <div><span class="fw-semibold" style="font-size:.85rem"><?= e($s['code']) ?></span><br><small class="text-muted"><?= $s['cnt'] ?> students</small></div>
                    <span class="grade-cell <?= ($s['avg_grade']??0)>=80?'high':(($s['avg_grade']??0)>=60?'mid':'low') ?>" style="font-size:1.1rem"><?= $s['avg_grade'] ?? 'N/A' ?>%</span>
                </div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </div>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('sectionChart'),{type:'bar',data:{labels:[<?php foreach($sections as $s) echo "'$s[code] ($s[section_number])',"; ?>],datasets:[{label:'Avg Grade %',data:[<?php foreach($sections as $s) echo ($s['avg_grade']??0).","; ?>],backgroundColor:function(ctx){const v=ctx.raw; return v>=80?'rgba(39,174,96,0.7)':v>=60?'rgba(243,156,18,0.7)':'rgba(231,76,60,0.7)';},borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100},x:{grid:{display:false}}}}});
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
