<?php
/** Muayyan - Student Contact Advisor */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'Contact Advisor';
$sid = $_SESSION['user_id'];

// Get assigned advisor
$advisor = $db->prepare("SELECT u.* FROM users u JOIN advisor_assignments aa ON u.id=aa.advisor_id WHERE aa.student_id=? AND aa.status='active' LIMIT 1");
$advisor->execute([$sid]);
$advisor = $advisor->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $advisor) {
    $action = $_POST['action'] ?? '';
    if ($action === 'new_request') {
        $stmt = $db->prepare("INSERT INTO contact_requests (student_id, advisor_id, subject, message, priority) VALUES (?,?,?,?,?)");
        $stmt->execute([$sid, $advisor['id'], $_POST['subject'], $_POST['message'], $_POST['priority'] ?? 'normal']);
        $requestId = $db->lastInsertId();
        
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/requests/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_FILE_TYPES) && $_FILES['attachment']['size'] <= MAX_UPLOAD_SIZE) {
                $fileName = 'req_' . $requestId . '_' . time() . '.' . $ext;
                $filePath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
                    $db->prepare("INSERT INTO request_attachments (request_id, file_name, file_path, file_size, file_type) VALUES (?,?,?,?,?)")
                        ->execute([$requestId, $_FILES['attachment']['name'], 'uploads/requests/' . $fileName, $_FILES['attachment']['size'], $_FILES['attachment']['type']]);
                }
            }
        }
        
        createNotification($advisor['id'], 'request', 'New Contact Request', $_SESSION['user_name'] . ' has sent a contact request: ' . $_POST['subject'], '/advisor/requests.php');
        setFlash('success', 'Your request has been sent successfully.');
        logActivity($sid, 'contact_request', 'Sent contact request to advisor');
    } elseif ($action === 'reply') {
        $db->prepare("INSERT INTO request_replies (request_id, user_id, message) VALUES (?,?,?)")->execute([$_POST['request_id'], $sid, $_POST['message']]);
        setFlash('success', 'Reply sent.');
    }
    header('Location: ' . BASE_URL . '/student/contact.php'); exit;
}

$requests = $db->query("SELECT * FROM contact_requests WHERE student_id=$sid ORDER BY created_at DESC")->fetchAll();
$flash = getFlash();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
        <i class="fas fa-<?= $flash['type']==='success'?'check':'times' ?>-circle me-2"></i><?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-bold"><i class="fas fa-headset me-2 text-primary"></i>Contact Advisor</h5>
        <?php if ($advisor): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus me-1"></i> New Request</button>
        <?php endif; ?>
    </div>

    <?php if ($advisor): ?>
    <!-- Advisor Info Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div class="student-avatar" style="width:55px;height:55px;font-size:1.1rem"><?= getInitials($advisor['name']) ?></div>
                <div>
                    <h6 class="fw-bold mb-0"><?= e($advisor['name']) ?></h6>
                    <small class="text-muted">Academic Advisor &bull; <?= e($advisor['department']) ?></small><br>
                    <small class="text-muted"><i class="fas fa-envelope me-1"></i><?= e($advisor['email']) ?> &bull; <i class="fas fa-phone me-1"></i><?= e($advisor['phone']) ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning"><i class="fas fa-info-circle me-2"></i>No advisor assigned yet. Please contact the administration.</div>
    <?php endif; ?>

    <!-- Requests History -->
    <h6 class="section-title"><i class="fas fa-history"></i> My Requests</h6>
    <?php if (empty($requests)): ?>
    <div class="empty-state"><i class="fas fa-envelope-open"></i><h5>No Requests Yet</h5><p>You haven't sent any contact requests to your advisor.</p></div>
    <?php endif; ?>

    <?php foreach ($requests as $req): 
        $sBadge = getRequestStatusBadge($req['status']);
        $replies = $db->prepare("SELECT rr.*, u.name, u.role FROM request_replies rr JOIN users u ON rr.user_id=u.id WHERE rr.request_id=? ORDER BY rr.created_at");
        $replies->execute([$req['id']]); $replies = $replies->fetchAll();
        $attachments = $db->prepare("SELECT * FROM request_attachments WHERE request_id=?");
        $attachments->execute([$req['id']]); $attachments = $attachments->fetchAll();
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <div>
                <h6 class="mb-0"><?= e($req['subject']) ?></h6>
                <small class="text-muted"><?= formatDateTime($req['created_at']) ?></small>
            </div>
            <div class="d-flex gap-2">
                <?php if ($req['priority'] === 'urgent'): ?><span class="badge bg-danger"><i class="fas fa-bolt me-1"></i>Urgent</span><?php endif; ?>
                <span class="badge <?= $sBadge['class'] ?>"><i class="fas <?= $sBadge['icon'] ?> me-1"></i><?= $sBadge['label'] ?></span>
            </div>
        </div>
        <div class="card-body">
            <p style="font-size:.88rem"><?= nl2br(e($req['message'])) ?></p>
            
            <?php if (!empty($attachments)): ?>
            <div class="mb-3">
                <?php foreach ($attachments as $att): ?>
                <span class="badge bg-light text-dark border me-1"><i class="fas fa-paperclip me-1"></i><?= e($att['file_name']) ?> <small>(<?= round($att['file_size']/1024) ?>KB)</small></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($replies)): ?>
            <hr>
            <h6 style="font-size:.82rem" class="fw-bold mb-2"><i class="fas fa-comments me-1"></i> Conversation</h6>
            <?php foreach ($replies as $rp): ?>
            <div class="d-flex gap-2 mb-2 p-2 rounded" style="background:<?= $rp['role']==='student' ? 'var(--primary-25)' : 'var(--gray-50)' ?>">
                <div class="student-avatar" style="width:28px;height:28px;font-size:.6rem"><?= getInitials($rp['name']) ?></div>
                <div>
                    <strong style="font-size:.78rem"><?= e($rp['name']) ?></strong> 
                    <small class="text-muted">(<?= ucfirst($rp['role']) ?>) &bull; <?= timeAgo($rp['created_at']) ?></small>
                    <p class="mb-0" style="font-size:.82rem"><?= nl2br(e($rp['message'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($req['status'] !== 'closed'): ?>
            <hr>
            <form method="POST" class="d-flex gap-2">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <textarea name="message" class="form-control" rows="1" placeholder="Write a reply..." required></textarea>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-reply"></i></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</main>

<!-- New Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>New Contact Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <input type="hidden" name="action" value="new_request">
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required placeholder="Brief description of your concern">
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="5" required placeholder="Describe your academic concern, question, or request in detail..."></textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Attachment (Optional)</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                    <small class="text-muted">Max 5MB. Allowed: PDF, DOC, JPG, PNG, ZIP</small>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Send Request</button>
        </div>
    </form>
</div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
