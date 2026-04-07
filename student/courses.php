<?php
/** MOEEN  - Student Courses */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'My Courses';
$sid = $_SESSION['user_id'];
$courses = $db->query("SELECT c.*, s.section_number, s.schedule, s.room, s.semester, u.name as faculty_name, (SELECT COUNT(*) FROM assessments a WHERE a.section_id=s.id) as assess_count, (SELECT COUNT(*) FROM assessments a WHERE a.section_id=s.id AND a.status='graded') as graded_count FROM courses c JOIN sections s ON c.id=s.course_id JOIN section_students ss ON s.id=ss.section_id JOIN users u ON s.faculty_id=u.id WHERE ss.student_id=$sid ORDER BY c.code")->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <h5 class="mb-4 fw-bold"><i class="fas fa-book-open me-2 text-primary"></i>My Courses — <?= CURRENT_SEMESTER ?>
        </h5>
        <div class="row g-3">
            <?php foreach ($courses as $c):
                $progress = $c['assess_count'] > 0 ? round(($c['graded_count'] / $c['assess_count']) * 100) : 0; ?>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <div class="course-code"><?= e($c['code']) ?> — Section <?= e($c['section_number']) ?></div>
                        <div class="course-name"><?= e($c['name']) ?></div>
                        <div class="course-info mb-1"><i class="fas fa-user-tie"></i> <?= e($c['faculty_name']) ?></div>
                        <div class="course-info mb-1"><i class="fas fa-clock"></i> <?= e($c['schedule']) ?></div>
                        <div class="course-info mb-1"><i class="fas fa-map-marker-alt"></i> <?= e($c['room']) ?></div>
                        <div class="course-info mb-3"><i class="fas fa-star"></i> <?= $c['credit_hours'] ?> Credit Hours
                        </div>
                        <div class="d-flex justify-content-between mb-1" style="font-size:.75rem">
                            <span>Progress</span><span><?= $c['graded_count'] ?>/<?= $c['assess_count'] ?> assessed</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar primary" style="width:<?= $progress ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>