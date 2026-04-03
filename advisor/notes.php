<?php
/** Muayyan - Advisor Academic Notes */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('advisor');
$db = getDBConnection();
$pageTitle = 'Academic Notes';
$aid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->prepare("INSERT INTO academic_notes (student_id, author_id, note_type, content, is_private) VALUES (?,?,?,?,?)")
        ->execute([$_POST['student_id'], $aid, $_POST['note_type'], $_POST['content'], isset($_POST['is_private']) ? 1 : 0]);
    setFlash('success', 'Note added.'); header('Location: ' . BASE_URL . '/advisor/notes.php'); exit;
}

$myStudents = $db->query("SELECT u.id, u.name FROM users u JOIN advisor_assignments aa ON u.id=aa.student_id WHERE aa.advisor_id=$aid AND aa.status='active' ORDER BY u.name")->fetchAll();
$stuIds = array_column($myStudents, 'id');
$stuIdStr = implode(',', $stuIds ?: [0]);
$notes = $db->query("SELECT an.*, u.name as student_name, au.name as author_name FROM academic_notes an JOIN users u ON an.student_id=u.id JOIN users au ON an.author_id=au.id WHERE an.student_id IN ($stuIdStr) ORDER BY an.created_at DESC")->fetchAll();
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold"><i class="fas fa-sticky-note me-2 text-primary"></i>Academic Notes</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#noteModal"><i class="fas fa-plus me-1"></i>Add Note</button>
    </div>
    <?php foreach ($notes as $n): 
        $typeColors = ['general'=>'primary','warning'=>'warning','recommendation'=>'success','follow_up'=>'info'];
    ?>
    <div class="card mb-2"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex gap-2 align-items-start">
                <div class="student-avatar" style="width:32px;height:32px;font-size:.65rem"><?= getInitials($n['student_name']) ?></div>
                <div>
                    <strong style="font-size:.85rem"><?= e($n['student_name']) ?></strong>
                    <span class="badge bg-<?= $typeColors[$n['note_type']] ?? 'secondary' ?> ms-1"><?= ucfirst(str_replace('_',' ',$n['note_type'])) ?></span>
                    <?php if ($n['is_private']): ?><span class="badge bg-dark ms-1"><i class="fas fa-lock me-1"></i>Private</span><?php endif; ?>
                    <p class="mb-0 mt-1" style="font-size:.82rem"><?= nl2br(e($n['content'])) ?></p>
                    <small class="text-muted">By <?= e($n['author_name']) ?> &bull; <?= timeAgo($n['created_at']) ?></small>
                </div>
            </div>
        </div>
    </div></div>
    <?php endforeach; ?>
</div>
</main>
<div class="modal fade" id="noteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add Academic Note</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST"><div class="modal-body">
        <div class="mb-3"><label class="form-label">Student</label><select name="student_id" class="form-select" required><?php foreach($myStudents as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Type</label><select name="note_type" class="form-select"><option value="general">General</option><option value="warning">Warning</option><option value="recommendation">Recommendation</option><option value="follow_up">Follow Up</option></select></div>
        <div class="mb-3"><label class="form-label">Note</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
        <div class="form-check"><input type="checkbox" name="is_private" class="form-check-input" id="isPrivate"><label class="form-check-label" for="isPrivate">Private (only visible to faculty/advisors)</label></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Note</button></div></form>
</div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
