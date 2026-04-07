<?php
/** MOEEN  - Advisor Contact Requests */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('advisor');
$db = getDBConnection();
$pageTitle = 'Contact Requests';
$aid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'reply') {
        $db->prepare("INSERT INTO request_replies (request_id, user_id, message) VALUES (?,?,?)")->execute([$_POST['request_id'], $aid, $_POST['message']]);
        $db->prepare("UPDATE contact_requests SET status='replied', updated_at=NOW() WHERE id=?")->execute([$_POST['request_id']]);
        $req = $db->prepare("SELECT student_id FROM contact_requests WHERE id=?");
        $req->execute([$_POST['request_id']]);
        $r = $req->fetch();
        createNotification($r['student_id'], 'request', 'Request Reply', 'Your advisor has replied to your contact request.', '/student/contact.php');
        setFlash('success', 'Reply sent.');
    } elseif ($action === 'status') {
        $db->prepare("UPDATE contact_requests SET status=?, updated_at=NOW() WHERE id=?")->execute([$_POST['status'], $_POST['request_id']]);
        setFlash('success', 'Status updated.');
    }
    header('Location: ' . BASE_URL . '/advisor/requests.php');
    exit;
}

$requests = $db->query("SELECT cr.*, u.name as student_name, u.user_id as stu_uid FROM contact_requests cr JOIN users u ON cr.student_id=u.id WHERE cr.advisor_id=$aid ORDER BY FIELD(cr.status,'sent','under_review','replied','closed'), cr.created_at DESC")->fetchAll();
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
        <h5 class="mb-4 fw-bold"><i class="fas fa-envelope me-2 text-primary"></i>Contact Requests</h5>
        <?php foreach ($requests as $req):
            $sBadge = getRequestStatusBadge($req['status']);
            $replies = $db->prepare("SELECT rr.*, u.name, u.role FROM request_replies rr JOIN users u ON rr.user_id=u.id WHERE rr.request_id=? ORDER BY rr.created_at");
            $replies->execute([$req['id']]);
            $replies = $replies->fetchAll();
            ?>
            <div class="card mb-3">
                <div class="card-header">
                    <div>
                        <h6 class="mb-0"><?= e($req['subject']) ?></h6>
                        <small class="text-muted">From: <?= e($req['student_name']) ?> (<?= e($req['stu_uid']) ?>) &bull;
                            <?= timeAgo($req['created_at']) ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($req['priority'] === 'urgent'): ?><span class="badge bg-danger"><i
                                    class="fas fa-bolt me-1"></i>Urgent</span><?php endif; ?>
                        <span class="badge <?= $sBadge['class'] ?>"><i
                                class="fas <?= $sBadge['icon'] ?> me-1"></i><?= $sBadge['label'] ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size:.88rem"><?= nl2br(e($req['message'])) ?></p>

                    <?php if (!empty($replies)): ?>
                        <hr>
                        <h6 style="font-size:.82rem" class="fw-bold mb-2"><i class="fas fa-comments me-1"></i> Conversation</h6>
                        <?php foreach ($replies as $rp): ?>
                            <div class="d-flex gap-2 mb-2 p-2 rounded"
                                style="background:<?= $rp['role'] === 'advisor' ? 'var(--primary-25)' : 'var(--gray-50)' ?>">
                                <div class="student-avatar" style="width:28px;height:28px;font-size:.6rem">
                                    <?= getInitials($rp['name']) ?></div>
                                <div><strong style="font-size:.78rem"><?= e($rp['name']) ?></strong> <small
                                        class="text-muted">(<?= ucfirst($rp['role']) ?>) &bull;
                                        <?= timeAgo($rp['created_at']) ?></small>
                                    <p class="mb-0" style="font-size:.82rem"><?= nl2br(e($rp['message'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($req['status'] !== 'closed'): ?>
                        <hr>
                        <form method="POST" class="d-flex gap-2 align-items-end">
                            <input type="hidden" name="action" value="reply"><input type="hidden" name="request_id"
                                value="<?= $req['id'] ?>">
                            <div class="flex-fill"><textarea name="message" class="form-control" rows="2"
                                    placeholder="Write your reply..." required></textarea></div>
                            <div class="d-flex flex-column gap-1">
                                <button type="submit" class="btn btn-primary btn-sm"><i
                                        class="fas fa-reply me-1"></i>Reply</button>
                            </div>
                        </form>
                        <form method="POST" class="mt-2"><input type="hidden" name="action" value="status"><input type="hidden"
                                name="request_id" value="<?= $req['id'] ?>">
                            <select name="status" class="form-select form-select-sm d-inline-block" style="width:auto"
                                onchange="this.form.submit()">
                                <option value="sent" <?= $req['status'] === 'sent' ? 'selected' : '' ?>>Sent</option>
                                <option value="under_review" <?= $req['status'] === 'under_review' ? 'selected' : '' ?>>Under Review
                                </option>
                                <option value="replied" <?= $req['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
                                <option value="closed" <?= $req['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>