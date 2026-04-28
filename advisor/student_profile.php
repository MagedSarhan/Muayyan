<?php
/** MOEEN  - Advisor: Full Student Profile */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('advisor');
$db = getDBConnection();
$aid = $_SESSION['user_id'];

$studentId = intval($_GET['id'] ?? 0);
if (!$studentId) {
    header('Location: ' . BASE_URL . '/advisor/students.php');
    exit;
}

// Verify this student is assigned to this advisor
$check = $db->prepare("SELECT COUNT(*) FROM advisor_assignments WHERE advisor_id=? AND student_id=? AND status='active'");
$check->execute([$aid, $studentId]);
if ($check->fetchColumn() == 0) {
    header('Location: ' . BASE_URL . '/advisor/students.php');
    exit;
}

// Student info
$stStmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();
if (!$student) {
    header('Location: ' . BASE_URL . '/advisor/students.php');
    exit;
}

$pageTitle = $student['name'] . ' — Student Profile';
$risk = calculateRiskScore($studentId);
$badge = getRiskBadge($risk['level']);

// Courses and grades
$courses = $db->query("SELECT c.code, c.name, s.id as section_id FROM courses c JOIN sections s ON c.id=s.course_id JOIN section_students ss ON s.id=ss.section_id WHERE ss.student_id=$studentId ORDER BY c.code")->fetchAll();
foreach ($courses as &$c) {
    $gStmt = $db->prepare("SELECT g.score, a.max_score, a.title, a.type, a.due_date FROM grades g JOIN assessments a ON g.assessment_id=a.id WHERE g.student_id=? AND a.section_id=? ORDER BY a.due_date");
    $gStmt->execute([$studentId, $c['section_id']]);
    $c['grades'] = $gStmt->fetchAll();
    $total = 0;
    $cnt = 0;
    foreach ($c['grades'] as $g) {
        $total += ($g['score'] / $g['max_score']) * 100;
        $cnt++;
    }
    $c['avg'] = $cnt > 0 ? round($total / $cnt, 1) : 0;
}
unset($c);

// Alerts
$alerts = $db->query("SELECT aa.*, c.code as course_code FROM academic_alerts aa LEFT JOIN sections s ON aa.section_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE aa.student_id=$studentId ORDER BY aa.created_at DESC")->fetchAll();

// Academic notes
$notes = $db->query("SELECT an.*, u.name as author_name FROM academic_notes an JOIN users u ON an.author_id=u.id WHERE an.student_id=$studentId ORDER BY an.created_at DESC")->fetchAll();

// Contact requests
$requests = $db->query("SELECT cr.*, (SELECT COUNT(*) FROM request_replies rr WHERE rr.request_id=cr.id) as reply_count FROM contact_requests cr WHERE cr.student_id=$studentId AND cr.advisor_id=$aid ORDER BY cr.created_at DESC")->fetchAll();

// Excuses
$excuses = $db->query("SELECT se.*, c.code as course_code, c.name as course_name FROM student_excuses se LEFT JOIN courses c ON se.course_id=c.id WHERE se.student_id=$studentId ORDER BY se.created_at DESC")->fetchAll();

// Upcoming assessments
$upcoming = $db->query("SELECT a.title, a.type, a.due_date, a.max_score, c.code FROM assessments a JOIN sections s ON a.section_id=s.id JOIN section_students ss ON s.id=ss.section_id JOIN courses c ON s.course_id=c.id WHERE ss.student_id=$studentId AND a.due_date >= CURDATE() ORDER BY a.due_date LIMIT 5")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <!-- Back Button -->
        <a href="<?= BASE_URL ?>/advisor/students.php" class="btn btn-sm btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-1"></i>Back to Students
        </a>

        <!-- Student Header -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="student-avatar mx-auto mb-3" style="width:70px;height:70px;font-size:1.4rem">
                            <?= getInitials($student['name']) ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?= e($student['name']) ?></h5>
                        <p class="text-muted mb-1"><code><?= e($student['user_id']) ?></code></p>
                        <p class="text-muted mb-3" style="font-size:.85rem">
                            <i class="fas fa-building me-1"></i><?= e($student['department']) ?>
                            <br><i class="fas fa-envelope me-1"></i><?= e($student['email']) ?>
                            <?php if ($student['phone']): ?>
                                <br><i class="fas fa-phone me-1"></i><?= e($student['phone']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="risk-indicator flex-column" style="border:none;padding:0">
                            <div class="risk-circle <?= $risk['level'] ?>"
                                style="width:65px;height:65px;font-size:1.3rem"><?= round($risk['score']) ?></div>
                            <div class="risk-info text-center mt-2">
                                <h5 style="font-size:.9rem"><?= $badge['label'] ?></h5>
                                <p style="font-size:.75rem">Avg: <?= $risk['avg_grade'] ?>% |
                                    <?= ucfirst($risk['trend']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-line me-2"></i>Performance by Course</h6>
                    </div>
                    <div class="card-body">
                        <div style="height:260px"><canvas id="perfChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-grades"><i
                        class="fas fa-star me-1"></i>Grades</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-alerts"><i
                        class="fas fa-bell me-1"></i>Alerts <span
                        class="badge bg-danger ms-1"><?= count(array_filter($alerts, fn($a) => !$a['is_resolved'])) ?></span></a>
            </li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-notes"><i
                        class="fas fa-sticky-note me-1"></i>Notes</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-requests"><i
                        class="fas fa-envelope me-1"></i>Contact Requests</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-excuses"><i
                        class="fas fa-file-medical me-1"></i>Excuses</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-upcoming"><i
                        class="fas fa-calendar me-1"></i>Upcoming</a></li>
        </ul>

        <div class="tab-content">
            <!-- Grades Tab -->
            <div class="tab-pane fade show active" id="tab-grades">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-star me-2"></i>Detailed Grade Breakdown</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Assessment</th>
                                        <th>Type</th>
                                        <th>Score</th>
                                        <th>%</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $c):
                                        foreach ($c['grades'] as $g):
                                            $pct = round(($g['score'] / $g['max_score']) * 100); ?>
                                            <tr>
                                                <td><?= e($c['code']) ?></td>
                                                <td><?= e($g['title']) ?></td>
                                                <td><span
                                                        class="badge <?= getAssessmentBadge($g['type'])['class'] ?>"><?= ucfirst($g['type']) ?></span>
                                                </td>
                                                <td><?= $g['score'] ?>/<?= $g['max_score'] ?></td>
                                                <td><span
                                                        class="grade-cell <?= $pct >= 80 ? 'high' : ($pct >= 60 ? 'mid' : 'low') ?>"><?= $pct ?>%</span>
                                                </td>
                                                <td><?= formatDate($g['due_date']) ?></td>
                                            </tr>
                                    <?php endforeach;
                                    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Course Averages Summary -->
                    <div class="card-footer">
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($courses as $c): ?>
                                <div class="d-flex align-items-center gap-2 px-3 py-2 rounded"
                                    style="background:var(--primary-25,#f0f7ff)">
                                    <strong style="font-size:.82rem"><?= e($c['code']) ?></strong>
                                    <span
                                        class="grade-cell <?= $c['avg'] >= 80 ? 'high' : ($c['avg'] >= 60 ? 'mid' : 'low') ?>"
                                        style="font-size:.8rem"><?= $c['avg'] ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts Tab -->
            <div class="tab-pane fade" id="tab-alerts">
                <?php if (empty($alerts)): ?>
                    <div class="text-center p-5"><i class="fas fa-check-circle text-success"
                            style="font-size:2.5rem"></i>
                        <p class="text-success mt-2 fw-semibold">No academic alerts for this student</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($alerts as $al): ?>
                        <div
                            class="alert-card severity-<?= $al['severity'] ?> mb-3 <?= $al['is_resolved'] ? 'opacity-50' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="alert-title fw-bold"><?= e($al['title']) ?></span>
                                    <?php if ($al['course_code']): ?>
                                        <span class="badge bg-secondary ms-2"><?= e($al['course_code']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <span
                                        class="badge <?= getSeverityBadge($al['severity'])['class'] ?>"><?= ucfirst($al['severity']) ?></span>
                                    <?php if ($al['is_resolved']): ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="alert-message mb-1"><?= e($al['message']) ?></div>
                            <div class="alert-time"><i class="fas fa-clock me-1"></i><?= timeAgo($al['created_at']) ?>
                                — <span class="badge bg-light text-dark"><?= ucfirst(str_replace('_', ' ', $al['alert_type'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Notes Tab -->
            <div class="tab-pane fade" id="tab-notes">
                <?php if (empty($notes)): ?>
                    <div class="text-center p-5"><i class="fas fa-sticky-note text-muted" style="font-size:2.5rem"></i>
                        <p class="text-muted mt-2">No academic notes for this student</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notes as $note):
                        $noteColors = ['general' => 'primary', 'warning' => 'warning', 'recommendation' => 'success', 'follow_up' => 'info']; ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span
                                        class="badge bg-<?= $noteColors[$note['note_type']] ?? 'secondary' ?>"><?= ucfirst(str_replace('_', ' ', $note['note_type'])) ?></span>
                                    <small class="text-muted"><?= timeAgo($note['created_at']) ?></small>
                                </div>
                                <p class="mb-1" style="font-size:.88rem"><?= e($note['content']) ?></p>
                                <small class="text-muted"><i
                                        class="fas fa-user me-1"></i><?= e($note['author_name']) ?>
                                    <?= $note['is_private'] ? '<span class="badge bg-dark ms-1"><i class="fas fa-lock me-1"></i>Private</span>' : '' ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Contact Requests Tab -->
            <div class="tab-pane fade" id="tab-requests">
                <?php if (empty($requests)): ?>
                    <div class="text-center p-5"><i class="fas fa-envelope text-muted" style="font-size:2.5rem"></i>
                        <p class="text-muted mt-2">No contact requests from this student</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $req):
                        $sBadge = getRequestStatusBadge($req['status']); ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="fw-bold mb-0" style="font-size:.9rem"><?= e($req['subject']) ?></h6>
                                    <span class="badge <?= $sBadge['class'] ?>"><i
                                            class="fas <?= $sBadge['icon'] ?> me-1"></i><?= $sBadge['label'] ?></span>
                                </div>
                                <p class="text-muted mb-2" style="font-size:.85rem"><?= e($req['message']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i><?= timeAgo($req['created_at']) ?>
                                        <?php if ($req['priority'] === 'urgent'): ?>
                                            <span class="badge bg-danger ms-1">Urgent</span>
                                        <?php endif; ?>
                                    </small>
                                    <small class="text-muted"><i
                                            class="fas fa-reply me-1"></i><?= $req['reply_count'] ?> replies</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Excuses Tab -->
            <div class="tab-pane fade" id="tab-excuses">
                <?php if (empty($excuses)): ?>
                    <div class="text-center p-5"><i class="fas fa-file-medical text-muted"
                            style="font-size:2.5rem"></i>
                        <p class="text-muted mt-2">No excuses submitted by this student</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Period</th>
                                    <th>Course</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($excuses as $exc):
                                    $typeIcons = ['health' => 'fa-heartbeat text-danger', 'financial' => 'fa-money-bill-wave text-success', 'family' => 'fa-users text-info', 'personal' => 'fa-user text-warning', 'other' => 'fa-ellipsis-h text-secondary'];
                                    $statusBadges = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'rejected' => 'bg-danger']; ?>
                                    <tr>
                                        <td><i
                                                class="fas <?= $typeIcons[$exc['excuse_type']] ?? 'fa-file' ?> me-1"></i><?= ucfirst($exc['excuse_type']) ?>
                                        </td>
                                        <td><?= formatDate($exc['start_date']) ?> — <?= formatDate($exc['end_date']) ?>
                                        </td>
                                        <td><?= $exc['course_code'] ? e($exc['course_code']) : '<span class="text-muted">All Courses</span>' ?>
                                        </td>
                                        <td style="max-width:250px"><?= e($exc['description'] ?? '—') ?></td>
                                        <td><span
                                                class="badge <?= $statusBadges[$exc['status']] ?? 'bg-secondary' ?>"><?= ucfirst($exc['status']) ?></span>
                                        </td>
                                        <td><?= timeAgo($exc['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Tab -->
            <div class="tab-pane fade" id="tab-upcoming">
                <?php if (empty($upcoming)): ?>
                    <div class="text-center p-5"><i class="fas fa-calendar-check text-success"
                            style="font-size:2.5rem"></i>
                        <p class="text-success mt-2 fw-semibold">No upcoming assessments</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($upcoming as $ua):
                        $daysLeft = max(0, (int)(( strtotime($ua['due_date']) - time()) / (60 * 60 * 24))); ?>
                        <div class="timeline-item">
                            <div
                                class="timeline-icon <?= $daysLeft <= 3 ? 'danger' : ($daysLeft <= 7 ? 'warning' : 'primary') ?>">
                                <i class="fas <?= getAssessmentBadge($ua['type'])['icon'] ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h6><?= e($ua['title']) ?></h6>
                                <p><?= e($ua['code']) ?> &bull; <?= $ua['max_score'] ?> pts</p>
                                <span class="time"><i class="fas fa-clock me-1"></i><?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?> left
                                    — <?= formatDate($ua['due_date']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const perfCtx = document.getElementById('perfChart');
        if (perfCtx) {
            new Chart(perfCtx, {
                type: 'radar',
                data: {
                    labels: [<?php foreach ($courses as $c) echo "'$c[code]',"; ?>],
                    datasets: [{
                        label: 'Avg %',
                        data: [<?php foreach ($courses as $c) echo "$c[avg],"; ?>],
                        backgroundColor: 'rgba(30,111,160,0.12)',
                        borderColor: '#1e6fa0',
                        pointBackgroundColor: '#1e6fa0',
                        pointRadius: 5,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { stepSize: 20, font: { size: 10 } },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
