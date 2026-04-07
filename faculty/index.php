<?php
/** MOEEN  - Faculty Dashboard */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('faculty');
$db = getDBConnection();
$pageTitle = 'Faculty Dashboard';
$fid = $_SESSION['user_id'];

$mySections = $db->query("SELECT COUNT(*) FROM sections WHERE faculty_id = $fid AND status='active'")->fetchColumn();
$myStudents = $db->query("SELECT COUNT(DISTINCT ss.student_id) FROM section_students ss JOIN sections s ON ss.section_id = s.id WHERE s.faculty_id = $fid")->fetchColumn();
$myAssessments = $db->query("SELECT COUNT(*) FROM assessments a JOIN sections s ON a.section_id = s.id WHERE s.faculty_id = $fid AND a.status IN ('upcoming','active')")->fetchColumn();
$pendingGrades = $db->query("SELECT COUNT(*) FROM assessments a JOIN sections s ON a.section_id = s.id WHERE s.faculty_id = $fid AND a.status = 'active'")->fetchColumn();

// My sections with details
$sections = $db->query("SELECT s.*, c.code, c.name as course_name, (SELECT COUNT(*) FROM section_students ss WHERE ss.section_id = s.id) as student_count FROM sections s JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid AND s.status='active' ORDER BY c.code")->fetchAll();

// At-risk students
$atRisk = $db->query("SELECT DISTINCT u.id, u.name, u.user_id, aa.severity, aa.title, c.code as course_code FROM academic_alerts aa JOIN users u ON aa.student_id = u.id LEFT JOIN sections s ON aa.section_id = s.id LEFT JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid AND aa.is_resolved = 0 AND aa.severity IN ('danger','critical') ORDER BY FIELD(aa.severity,'critical','danger') LIMIT 6")->fetchAll();

// Upcoming assessments
$upcoming = $db->query("SELECT a.*, c.code as course_code FROM assessments a JOIN sections s ON a.section_id = s.id JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid AND a.due_date >= CURDATE() ORDER BY a.due_date LIMIT 6")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="welcome-banner">
            <h3>Welcome, <?= e($_SESSION['user_name']) ?> 👋</h3>
            <p>Manage your sections, assessments, and monitor student performance</p>
            <div class="welcome-date"><i class="fas fa-calendar-alt me-1"></i> <?= date('l, F j, Y') ?> &bull;
                <?= CURRENT_SEMESTER ?></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fas fa-chalkboard"></i></div>
                    <div class="stat-value" data-count="<?= $mySections ?>"><?= $mySections ?></div>
                    <div class="stat-label">My Sections</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-value" data-count="<?= $myStudents ?>"><?= $myStudents ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-value" data-count="<?= $myAssessments ?>"><?= $myAssessments ?></div>
                    <div class="stat-label">Active Assessments</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="fas fa-pen"></i></div>
                    <div class="stat-value" data-count="<?= $pendingGrades ?>"><?= $pendingGrades ?></div>
                    <div class="stat-label">Pending Grade Entry</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- My Sections -->
            <div class="col-lg-8">
                <h6 class="section-title"><i class="fas fa-chalkboard-teacher"></i> My Sections</h6>
                <div class="row g-3">
                    <?php foreach ($sections as $s): ?>
                        <div class="col-md-6">
                            <div class="course-card">
                                <div class="course-code"><?= e($s['code']) ?> — Section <?= e($s['section_number']) ?></div>
                                <div class="course-name"><?= e($s['course_name']) ?></div>
                                <div class="course-info mb-1"><i class="fas fa-users"></i> <?= $s['student_count'] ?>
                                    Students</div>
                                <div class="course-info mb-1"><i class="fas fa-clock"></i> <?= e($s['schedule']) ?></div>
                                <div class="course-info"><i class="fas fa-map-marker-alt"></i> <?= e($s['room']) ?></div>
                                <div class="mt-3 d-flex gap-2">
                                    <a href="<?= BASE_URL ?>/faculty/grades.php?section=<?= $s['id'] ?>"
                                        class="btn btn-sm btn-primary flex-fill"><i class="fas fa-pen me-1"></i>Grades</a>
                                    <a href="<?= BASE_URL ?>/faculty/students.php?section=<?= $s['id'] ?>"
                                        class="btn btn-sm btn-outline-primary flex-fill"><i
                                            class="fas fa-users me-1"></i>Students</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- At-Risk & Upcoming -->
            <div class="col-lg-4">
                <h6 class="section-title"><i class="fas fa-exclamation-triangle"></i> At-Risk Students</h6>
                <?php if (empty($atRisk)): ?>
                    <div class="empty-state p-3"><i class="fas fa-check-circle text-success" style="font-size:2rem"></i>
                        <p class="mt-2 mb-0 text-success fw-semibold">All students performing well!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($atRisk as $ar): ?>
                        <div class="alert-card severity-<?= $ar['severity'] ?> mb-2">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="student-avatar" style="width:28px;height:28px;font-size:.6rem">
                                    <?= getInitials($ar['name']) ?></div>
                                <span class="alert-title mb-0"><?= e($ar['name']) ?></span>
                            </div>
                            <div class="alert-message"><?= e($ar['title']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h6 class="section-title mt-4"><i class="fas fa-calendar"></i> Upcoming Assessments</h6>
                <?php foreach ($upcoming as $ua): ?>
                    <div class="timeline-item">
                        <div
                            class="timeline-icon <?= strtotime($ua['due_date']) < strtotime('+3 days') ? 'danger' : 'primary' ?>">
                            <i class="fas <?= getAssessmentBadge($ua['type'])['icon'] ?>"></i></div>
                        <div class="timeline-content">
                            <h6><?= e($ua['title']) ?></h6>
                            <p><?= e($ua['course_code']) ?> &bull; <span
                                    class="badge <?= getAssessmentBadge($ua['type'])['class'] ?>"><?= ucfirst($ua['type']) ?></span>
                            </p>
                            <span class="time"><i
                                    class="fas fa-calendar-day me-1"></i><?= formatDate($ua['due_date']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>