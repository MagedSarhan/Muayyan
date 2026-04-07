<?php
/** MOEEN  - Faculty Grade Entry */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('faculty');
$db = getDBConnection();
$pageTitle = 'Grade Entry';
$fid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grades'])) {
    $assessmentId = $_POST['assessment_id'];
    foreach ($_POST['grades'] as $studentId => $score) {
        if ($score === '')
            continue;
        $db->prepare("INSERT INTO grades (assessment_id, student_id, score, entered_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score), entered_by=VALUES(entered_by), updated_at=NOW()")
            ->execute([$assessmentId, $studentId, $score, $fid]);
    }
    $db->prepare("UPDATE assessments SET status='graded' WHERE id=?")->execute([$assessmentId]);
    setFlash('success', 'Grades saved successfully.');
    logActivity($fid, 'grade_entry', 'Entered grades for assessment #' . $assessmentId);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$sectionId = $_GET['section'] ?? '';
$assessmentId = $_GET['assessment'] ?? '';
$mySections = $db->query("SELECT s.id, c.code, c.name, s.section_number FROM sections s JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid AND s.status='active' ORDER BY c.code")->fetchAll();

$assessments = [];
$students = [];
$existingGrades = [];

if ($sectionId) {
    $assessments = $db->prepare("SELECT * FROM assessments WHERE section_id = ? ORDER BY due_date");
    $assessments->execute([$sectionId]);
    $assessments = $assessments->fetchAll();

    if ($assessmentId) {
        $students = $db->prepare("SELECT u.* FROM users u JOIN section_students ss ON u.id = ss.student_id WHERE ss.section_id = ? ORDER BY u.name");
        $students->execute([$sectionId]);
        $students = $students->fetchAll();

        $gradeStmt = $db->prepare("SELECT student_id, score FROM grades WHERE assessment_id = ?");
        $gradeStmt->execute([$assessmentId]);
        $existingGrades = array_column($gradeStmt->fetchAll(), 'score', 'student_id');
    }
}

$currentAssessment = $assessmentId ? $db->prepare("SELECT * FROM assessments WHERE id=?") : null;
if ($currentAssessment) {
    $currentAssessment->execute([$assessmentId]);
    $currentAssessment = $currentAssessment->fetch();
}

$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <?php if ($flash): ?>
            <div class="alert alert-success alert-dismissible fade show"><i
                    class="fas fa-check-circle me-2"></i><?= e($flash['message']) ?><button type="button" class="btn-close"
                    data-bs-dismiss="alert"></button></div><?php endif; ?>
        <h5 class="mb-4 fw-bold"><i class="fas fa-pen-alt me-2 text-primary"></i>Grade Entry</h5>

        <div class="filter-bar mb-4">
            <form method="GET" class="d-flex gap-3 flex-wrap w-100">
                <div>
                    <label class="form-label mb-1" style="font-size:.75rem">Section</label>
                    <select name="section" class="form-select" onchange="this.form.submit()">
                        <option value="">Select Section</option>
                        <?php foreach ($mySections as $ms): ?>
                            <option value="<?= $ms['id'] ?>" <?= $sectionId == $ms['id'] ? 'selected' : '' ?>>
                                <?= e($ms['code'] . ' - ' . $ms['name'] . ' (Sec ' . $ms['section_number'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($sectionId && !empty($assessments)): ?>
                    <div>
                        <label class="form-label mb-1" style="font-size:.75rem">Assessment</label>
                        <select name="assessment" class="form-select" onchange="this.form.submit()">
                            <option value="">Select Assessment</option>
                            <?php foreach ($assessments as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $assessmentId == $a['id'] ? 'selected' : '' ?>>
                                    <?= e($a['title']) ?> (<?= ucfirst($a['type']) ?> - <?= $a['max_score'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($currentAssessment && !empty($students)): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h6 class="mb-0"><?= e($currentAssessment['title']) ?></h6>
                        <small class="text-muted">Max Score: <?= $currentAssessment['max_score'] ?> | Weight:
                            <?= $currentAssessment['weight_percentage'] ?>% | Due:
                            <?= formatDate($currentAssessment['due_date']) ?></small>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="assessment_id" value="<?= $assessmentId ?>">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>ID</th>
                                        <th>Score (Max: <?= $currentAssessment['max_score'] ?>)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $i => $st):
                                        $existing = $existingGrades[$st['id']] ?? '';
                                        $pct = $existing !== '' ? ($existing / $currentAssessment['max_score']) * 100 : -1;
                                        ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="student-avatar" style="width:30px;height:30px;font-size:.65rem">
                                                        <?= getInitials($st['name']) ?></div><span
                                                        class="fw-semibold"><?= e($st['name']) ?></span>
                                                </div>
                                            </td>
                                            <td><code><?= e($st['user_id']) ?></code></td>
                                            <td><input type="number" name="grades[<?= $st['id'] ?>]" class="form-control"
                                                    style="max-width:120px" step="0.5" min="0"
                                                    max="<?= $currentAssessment['max_score'] ?>" value="<?= e($existing) ?>"
                                                    placeholder="—"></td>
                                            <td>
                                                <?php if ($pct >= 0): ?>
                                                    <span
                                                        class="grade-cell <?= $pct >= 80 ? 'high' : ($pct >= 60 ? 'mid' : 'low') ?>"><?= round($pct) ?>%</span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not graded</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-1"></i> Save
                            Grades</button>
                    </form>
                </div>
            </div>
        <?php elseif (!$sectionId): ?>
            <div class="empty-state"><i class="fas fa-hand-point-up"></i>
                <h5>Select a Section</h5>
                <p>Choose a section and assessment to start entering grades.</p>
            </div>
        <?php elseif (!$assessmentId): ?>
            <div class="empty-state"><i class="fas fa-clipboard-list"></i>
                <h5>Select an Assessment</h5>
                <p>Choose an assessment to enter grades for.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>