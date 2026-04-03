<?php
/** Muayyan - Faculty Sections */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('faculty');
$db = getDBConnection();
$pageTitle = 'My Sections';
$fid = $_SESSION['user_id'];
$sections = $db->query("SELECT s.*, c.code, c.name as course_name, (SELECT COUNT(*) FROM section_students ss WHERE ss.section_id = s.id) as student_count, (SELECT COUNT(*) FROM assessments a WHERE a.section_id = s.id) as assessment_count FROM sections s JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid ORDER BY s.status DESC, c.code")->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <h5 class="mb-4 fw-bold"><i class="fas fa-chalkboard me-2 text-primary"></i>My Sections</h5>
    <div class="row g-3">
    <?php foreach ($sections as $s): ?>
    <div class="col-lg-4 col-md-6">
        <div class="course-card">
            <div class="d-flex justify-content-between"><div class="course-code"><?= e($s['code']) ?> - Section <?= e($s['section_number']) ?></div><span class="badge <?= $s['status']==='active'?'bg-success':'bg-secondary' ?>"><?= ucfirst($s['status']) ?></span></div>
            <div class="course-name"><?= e($s['course_name']) ?></div>
            <div class="course-info mb-1"><i class="fas fa-users"></i> <?= $s['student_count'] ?> Students</div>
            <div class="course-info mb-1"><i class="fas fa-clipboard-list"></i> <?= $s['assessment_count'] ?> Assessments</div>
            <div class="course-info mb-1"><i class="fas fa-clock"></i> <?= e($s['schedule']) ?></div>
            <div class="course-info mb-3"><i class="fas fa-map-marker-alt"></i> <?= e($s['room']) ?></div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/faculty/assessments.php?section=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="fas fa-clipboard-list me-1"></i>Assessments</a>
                <a href="<?= BASE_URL ?>/faculty/grades.php?section=<?= $s['id'] ?>" class="btn btn-sm btn-primary flex-fill"><i class="fas fa-pen me-1"></i>Grades</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
