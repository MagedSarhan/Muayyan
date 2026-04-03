<?php
/** Muayyan - Admin Course Management */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$db = getDBConnection();
$pageTitle = 'Course Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO courses (code, name, credit_hours, department, description) VALUES (?,?,?,?,?)")->execute([trim($_POST['code']), trim($_POST['name']), $_POST['credit_hours'], trim($_POST['department']), trim($_POST['description'])]);
        setFlash('success', 'Course created.');
    } elseif ($action === 'edit') {
        $db->prepare("UPDATE courses SET code=?, name=?, credit_hours=?, department=?, description=?, status=? WHERE id=?")->execute([trim($_POST['code']), trim($_POST['name']), $_POST['credit_hours'], trim($_POST['department']), trim($_POST['description']), $_POST['status'], $_POST['id']]);
        setFlash('success', 'Course updated.');
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM courses WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Course deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/courses.php'); exit;
}
$courses = $db->query("SELECT c.*, (SELECT COUNT(*) FROM sections s WHERE s.course_id = c.id AND s.status='active') as section_count FROM courses c ORDER BY c.code")->fetchAll();
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold"><i class="fas fa-book me-2 text-primary"></i>Course Management</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal" onclick="resetCourseForm()"><i class="fas fa-plus me-1"></i> Add Course</button>
    </div>
    <div class="row g-3">
    <?php foreach ($courses as $c): ?>
        <div class="col-lg-4 col-md-6">
            <div class="course-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="course-code"><?= e($c['code']) ?></div>
                    <span class="badge <?= $c['status']==='active' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($c['status']) ?></span>
                </div>
                <div class="course-name"><?= e($c['name']) ?></div>
                <div class="course-info mb-1"><i class="fas fa-clock me-1"></i> <?= $c['credit_hours'] ?> Credit Hours</div>
                <div class="course-info mb-1"><i class="fas fa-building me-1"></i> <?= e($c['department']) ?></div>
                <div class="course-info mb-3"><i class="fas fa-layer-group me-1"></i> <?= $c['section_count'] ?> Active Sections</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick='editCourse(<?= json_encode($c) ?>)'><i class="fas fa-edit me-1"></i>Edit</button>
                    <form method="POST" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
</main>
<div class="modal fade" id="courseModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="cModalTitle">Add Course</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST"><div class="modal-body">
        <input type="hidden" name="action" id="cAction" value="add"><input type="hidden" name="id" id="cId">
        <div class="mb-3"><label class="form-label">Course Code</label><input type="text" name="code" id="c_code" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Course Name</label><input type="text" name="name" id="c_name" class="form-control" required></div>
        <div class="row g-3 mb-3"><div class="col-6"><label class="form-label">Credit Hours</label><input type="number" name="credit_hours" id="c_ch" class="form-control" value="3" min="1" max="6"></div><div class="col-6"><label class="form-label">Status</label><select name="status" id="c_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div>
        <div class="mb-3"><label class="form-label">Department</label><input type="text" name="department" id="c_dept" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="c_desc" class="form-control" rows="3"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div></form>
</div></div></div>
<script>
function resetCourseForm(){document.getElementById('cModalTitle').textContent='Add Course';document.getElementById('cAction').value='add';['c_code','c_name','c_dept','c_desc'].forEach(id=>document.getElementById(id).value='');document.getElementById('c_ch').value='3';document.getElementById('c_status').value='active';}
function editCourse(c){document.getElementById('cModalTitle').textContent='Edit Course';document.getElementById('cAction').value='edit';document.getElementById('cId').value=c.id;document.getElementById('c_code').value=c.code;document.getElementById('c_name').value=c.name;document.getElementById('c_ch').value=c.credit_hours;document.getElementById('c_dept').value=c.department||'';document.getElementById('c_desc').value=c.description||'';document.getElementById('c_status').value=c.status;new bootstrap.Modal(document.getElementById('courseModal')).show();}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
