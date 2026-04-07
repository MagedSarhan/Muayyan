<?php
/** MOEEN  - Faculty Assessments */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('faculty');
$db = getDBConnection();
$pageTitle = 'Assessments';
$fid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO assessments (section_id, title, type, max_score, weight_percentage, due_date, description, status, created_by) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$_POST['section_id'], $_POST['title'], $_POST['type'], $_POST['max_score'], $_POST['weight'], $_POST['due_date'], $_POST['description'], 'upcoming', $fid]);
        setFlash('success', 'Assessment created.');
        logActivity($fid, 'assessment_create', 'Created: ' . $_POST['title']);
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM assessments WHERE id=? AND created_by=?")->execute([$_POST['id'], $fid]);
        setFlash('success', 'Assessment deleted.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$sectionFilter = $_GET['section'] ?? '';
$where = "WHERE s.faculty_id = ?";
$params = [$fid];
if ($sectionFilter) {
    $where .= " AND a.section_id = ?";
    $params[] = $sectionFilter;
}
$assessments = $db->prepare("SELECT a.*, c.code as course_code, c.name as course_name, s.section_number FROM assessments a JOIN sections s ON a.section_id = s.id JOIN courses c ON s.course_id = c.id $where ORDER BY a.due_date DESC");
$assessments->execute($params);
$assessments = $assessments->fetchAll();

$mySections = $db->query("SELECT s.id, c.code, c.name, s.section_number FROM sections s JOIN courses c ON s.course_id = c.id WHERE s.faculty_id = $fid AND s.status='active' ORDER BY c.code")->fetchAll();
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <?php if ($flash): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= e($flash['message']) ?><button type="button"
                    class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-bold"><i class="fas fa-clipboard-list me-2 text-primary"></i>Assessments</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assessModal"><i
                    class="fas fa-plus me-1"></i> New Assessment</button>
        </div>
        <div class="filter-bar mb-3">
            <form method="GET" class="d-flex gap-2">
                <select name="section" class="form-select" onchange="this.form.submit()">
                    <option value="">All Sections</option>
                    <?php foreach ($mySections as $ms): ?>
                        <option value="<?= $ms['id'] ?>" <?= $sectionFilter == $ms['id'] ? 'selected' : '' ?>>
                            <?= e($ms['code'] . ' - ' . $ms['name'] . ' (Sec ' . $ms['section_number'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Assessment</th>
                            <th>Course</th>
                            <th>Type</th>
                            <th>Max Score</th>
                            <th>Weight</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessments as $a): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($a['title']) ?></td>
                                <td><?= e($a['course_code']) ?> (<?= $a['section_number'] ?>)</td>
                                <td><span class="badge <?= getAssessmentBadge($a['type'])['class'] ?>"><i
                                            class="fas <?= getAssessmentBadge($a['type'])['icon'] ?> me-1"></i><?= ucfirst($a['type']) ?></span>
                                </td>
                                <td><?= $a['max_score'] ?></td>
                                <td><?= $a['weight_percentage'] ?>%</td>
                                <td><?= formatDate($a['due_date']) ?></td>
                                <td><span
                                        class="badge <?= $a['status'] === 'graded' ? 'bg-success' : ($a['status'] === 'active' ? 'bg-warning text-dark' : 'bg-info') ?>"><?= ucfirst($a['status']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/faculty/grades.php?section=<?= $a['section_id'] ?>&assessment=<?= $a['id'] ?>"
                                        class="btn btn-sm btn-outline-primary" title="Grade"><i class="fas fa-pen"></i></a>
                                    <form method="POST" class="d-inline" onsubmit="return confirmDelete()"><input
                                            type="hidden" name="action" value="delete"><input type="hidden" name="id"
                                            value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i
                                                class="fas fa-trash"></i></button></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<div class="modal fade" id="assessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Assessment</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body"><input type="hidden" name="action" value="add">
                    <div class="mb-3"><label class="form-label">Section</label><select name="section_id"
                            class="form-select" required><?php foreach ($mySections as $ms): ?>
                                <option value="<?= $ms['id'] ?>" <?= $sectionFilter == $ms['id'] ? 'selected' : '' ?>>
                                    <?= e($ms['code'] . ' - ' . $ms['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title"
                            class="form-control" required></div>
                    <div class="row g-3 mb-3">
                        <div class="col-4"><label class="form-label">Type</label><select name="type"
                                class="form-select">
                                <option>quiz</option>
                                <option>midterm</option>
                                <option>final</option>
                                <option>project</option>
                                <option>assignment</option>
                                <option>presentation</option>
                                <option>lab</option>
                                <option>participation</option>
                            </select></div>
                        <div class="col-4"><label class="form-label">Max Score</label><input type="number"
                                name="max_score" class="form-control" value="10" step="0.5" required></div>
                        <div class="col-4"><label class="form-label">Weight %</label><input type="number" name="weight"
                                class="form-control" value="5" step="0.5" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Due Date</label><input type="date" name="due_date"
                            class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description"
                            class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Cancel</button><button type="submit"
                        class="btn btn-primary">Create</button></div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>