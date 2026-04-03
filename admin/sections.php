<?php
/** AALMAS - Admin Section Management */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$db = getDBConnection();
$pageTitle = 'Section Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO sections (course_id, section_number, faculty_id, semester, academic_year, max_students, schedule, room) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$_POST['course_id'], $_POST['section_number'], $_POST['faculty_id'], $_POST['semester'], $_POST['academic_year'], $_POST['max_students'], $_POST['schedule'], $_POST['room']]);
        setFlash('success', 'Section created.');
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM sections WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Section deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/sections.php'); exit;
}

$sections = $db->query("SELECT s.*, c.code as course_code, c.name as course_name, u.name as faculty_name, (SELECT COUNT(*) FROM section_students ss WHERE ss.section_id = s.id) as student_count FROM sections s JOIN courses c ON s.course_id = c.id JOIN users u ON s.faculty_id = u.id ORDER BY s.semester DESC, c.code")->fetchAll();
$courses = $db->query("SELECT id, code, name FROM courses WHERE status='active' ORDER BY code")->fetchAll();
$faculty = $db->query("SELECT id, name FROM users WHERE role='faculty' AND status='active' ORDER BY name")->fetchAll();
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold"><i class="fas fa-layer-group me-2 text-primary"></i>Sections</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#secModal"><i class="fas fa-plus me-1"></i> Add Section</button>
    </div>
    <div class="table-container"><div class="table-responsive"><table class="table">
        <thead><tr><th>Course</th><th>Section</th><th>Faculty</th><th>Semester</th><th>Schedule</th><th>Room</th><th>Students</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($sections as $s): ?>
        <tr>
            <td><span class="fw-semibold"><?= e($s['course_code']) ?></span> - <?= e($s['course_name']) ?></td>
            <td><span class="badge bg-primary"><?= e($s['section_number']) ?></span></td>
            <td><?= e($s['faculty_name']) ?></td>
            <td><?= e($s['semester']) ?></td>
            <td><small><?= e($s['schedule']) ?></small></td>
            <td><?= e($s['room']) ?></td>
            <td><span class="badge bg-info"><?= $s['student_count'] ?>/<?= $s['max_students'] ?></span></td>
            <td><span class="badge <?= $s['status']==='active'?'bg-success':'bg-secondary' ?>"><?= ucfirst($s['status']) ?></span></td>
            <td><form method="POST" class="d-inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
</div>
</main>
<div class="modal fade" id="secModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add Section</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST"><div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="row g-3">
            <div class="col-8"><label class="form-label">Course</label><select name="course_id" class="form-select" required><?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['code'].' - '.$c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-4"><label class="form-label">Section #</label><input type="text" name="section_number" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Faculty</label><select name="faculty_id" class="form-select" required><?php foreach($faculty as $f): ?><option value="<?= $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-6"><label class="form-label">Semester</label><input type="text" name="semester" class="form-control" value="<?= CURRENT_SEMESTER ?>" required></div>
            <div class="col-6"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= ACADEMIC_YEAR ?>" required></div>
            <div class="col-4"><label class="form-label">Max Students</label><input type="number" name="max_students" class="form-control" value="35"></div>
            <div class="col-8"><label class="form-label">Schedule</label><input type="text" name="schedule" class="form-control" placeholder="e.g. Sun-Tue 10:00-11:30"></div>
            <div class="col-12"><label class="form-label">Room</label><input type="text" name="room" class="form-control"></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div></form>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
