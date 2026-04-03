<?php
/** Muayyan - Student Workload */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'My Workload';
$sid = $_SESSION['user_id'];

// Get assessments for next 4 weeks
$assessments = $db->query("SELECT a.*, c.code, c.name as course_name FROM assessments a JOIN sections s ON a.section_id=s.id JOIN section_students ss ON s.id=ss.section_id JOIN courses c ON s.course_id=c.id WHERE ss.student_id=$sid AND a.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 28 DAY) ORDER BY a.due_date")->fetchAll();

// Group by week
$weeks = [];
foreach ($assessments as $a) {
    $weekNum = ceil((strtotime($a['due_date']) - strtotime('monday this week')) / (7*86400)) + 1;
    $weekStart = date('Y-m-d', strtotime("monday this week +".($weekNum-1)." weeks"));
    $weeks[$weekStart][] = $a;
}

// Daily distribution for current week
$dayAssessments = [];
$dayNames = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$mondayThisWeek = date('Y-m-d', strtotime('monday this week'));
for ($i = 0; $i < 7; $i++) {
    $day = date('Y-m-d', strtotime($mondayThisWeek . " +$i days"));
    $dayAssessments[$dayNames[$i]] = $db->query("SELECT COUNT(*) FROM assessments a JOIN sections s ON a.section_id=s.id JOIN section_students ss ON s.id=ss.section_id WHERE ss.student_id=$sid AND a.due_date='$day'")->fetchColumn();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <h5 class="mb-4 fw-bold"><i class="fas fa-weight-hanging me-2 text-primary"></i>Weekly Workload Overview</h5>
    
    <!-- This Week Daily View -->
    <div class="card mb-4">
        <div class="card-header"><h6><i class="fas fa-calendar-week me-2"></i>This Week's Assessment Load</h6></div>
        <div class="card-body">
            <div class="row g-3">
            <?php foreach ($dayAssessments as $day => $count): 
                $level = $count >= 3 ? 'high' : ($count >= 2 ? 'medium' : 'low');
            ?>
            <div class="col">
                <div class="workload-day <?= $level ?>">
                    <div class="day-name"><?= $day ?></div>
                    <div class="day-count"><?= $count ?></div>
                    <small class="text-muted"><?= $count === 0 ? 'Free' : ($count === 1 ? 'assessment' : 'assessments') ?></small>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <div style="height:200px" class="mt-3"><canvas id="weekChart"></canvas></div>
        </div>
    </div>

    <!-- Upcoming Weeks -->
    <h6 class="section-title"><i class="fas fa-calendar-alt"></i> Upcoming 4 Weeks</h6>
    <?php foreach ($weeks as $weekStart => $weekAssess): 
        $count = count($weekAssess);
        $level = $count >= 4 ? 'danger' : ($count >= 3 ? 'warning' : ($count >= 2 ? 'info' : 'success'));
        $weekEnd = date('M d', strtotime($weekStart . ' +6 days'));
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><?= formatDate($weekStart, 'M d') ?> — <?= $weekEnd ?></h6>
            <span class="badge bg-<?= $level ?>"><?= $count ?> assessment<?= $count>1?'s':'' ?></span>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Assessment</th><th>Course</th><th>Type</th><th>Due Date</th><th>Points</th></tr></thead>
            <tbody>
            <?php foreach ($weekAssess as $wa): ?>
            <tr>
                <td class="fw-semibold"><?= e($wa['title']) ?></td>
                <td><?= e($wa['code']) ?></td>
                <td><span class="badge <?= getAssessmentBadge($wa['type'])['class'] ?>"><?= ucfirst($wa['type']) ?></span></td>
                <td><?= formatDate($wa['due_date'], 'D, M d') ?></td>
                <td><?= $wa['max_score'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div></div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($weeks)): ?>
    <div class="empty-state"><i class="fas fa-calendar-check"></i><h5>No Upcoming Assessments</h5><p>You have no assessments in the next 4 weeks.</p></div>
    <?php endif; ?>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const weekChartCtx = document.getElementById('weekChart');
    if (weekChartCtx) {
        new Chart(weekChartCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach($dayAssessments as $d=>$c) echo "'$d',"; ?>],
                datasets: [{
                    label: 'Assessments',
                    data: [<?php echo implode(',',array_values($dayAssessments)); ?>],
                    backgroundColor: function(ctx) {
                        const v = ctx.raw;
                        return v >= 3 ? 'rgba(231,76,60,0.7)' : v >= 2 ? 'rgba(243,156,18,0.7)' : 'rgba(30,111,160,0.7)';
                    },
                    borderRadius: 8,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
