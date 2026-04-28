<?php
/** MOEEN  - Student Excuses */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$db = getDBConnection();
$pageTitle = 'My Excuses';
$sid = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_excuse'])) {
    $excuseType = $_POST['excuse_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $courseId = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;
    $description = trim($_POST['description'] ?? '');

    $validTypes = ['health', 'financial', 'family', 'personal', 'other'];
    $errors = [];

    if (!in_array($excuseType, $validTypes)) {
        $errors[] = 'Please select a valid excuse type.';
    }
    if (empty($startDate) || empty($endDate)) {
        $errors[] = 'Please specify both start and end dates.';
    }
    if (!empty($startDate) && !empty($endDate) && strtotime($endDate) < strtotime($startDate)) {
        $errors[] = 'End date cannot be before start date.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO student_excuses (student_id, excuse_type, description, start_date, end_date, course_id, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$sid, $excuseType, $description ?: null, $startDate, $endDate, $courseId]);
        setFlash('success', 'Your excuse has been submitted successfully.');
        header('Location: ' . BASE_URL . '/student/excuses.php');
        exit;
    }
}

// Get student's courses for the dropdown
$myCourses = $db->query("SELECT c.id, c.code, c.name FROM courses c JOIN sections s ON c.id=s.course_id JOIN section_students ss ON s.id=ss.section_id WHERE ss.student_id=$sid ORDER BY c.code")->fetchAll();

// Get existing excuses
$excuses = $db->query("SELECT se.*, c.code as course_code, c.name as course_name FROM student_excuses se LEFT JOIN courses c ON se.course_id=c.id WHERE se.student_id=$sid ORDER BY se.created_at DESC")->fetchAll();

$flash = getFlash();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show"
                role="alert">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-bold"><i class="fas fa-file-medical me-2 text-primary"></i>My Excuses</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#excuseModal">
                <i class="fas fa-plus me-1"></i>Submit New Excuse
            </button>
        </div>

        <!-- Excuses Stats -->
        <div class="row g-3 mb-4">
            <?php
            $totalExcuses = count($excuses);
            $pendingExcuses = count(array_filter($excuses, fn($e) => $e['status'] === 'pending'));
            $approvedExcuses = count(array_filter($excuses, fn($e) => $e['status'] === 'approved'));
            $rejectedExcuses = count(array_filter($excuses, fn($e) => $e['status'] === 'rejected'));
            ?>
            <div class="col-md-3 col-6">
                <div class="stat-card gradient-1">
                    <div class="stat-icon primary"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-value"><?= $totalExcuses ?></div>
                    <div class="stat-label">Total Excuses</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card gradient-3">
                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?= $pendingExcuses ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card gradient-2">
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?= $approvedExcuses ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card gradient-4">
                    <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?= $rejectedExcuses ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Excuses List -->
        <?php if (empty($excuses)): ?>
            <div class="card">
                <div class="card-body text-center p-5">
                    <i class="fas fa-file-medical text-muted" style="font-size:3rem"></i>
                    <h5 class="mt-3 text-muted">No Excuses Yet</h5>
                    <p class="text-muted">You haven't submitted any excuses. Click the button above to submit a new
                        excuse.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Period</th>
                                    <th>Course</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($excuses as $exc):
                                    $typeIcons = [
                                        'health' => ['icon' => 'fa-heartbeat', 'color' => 'text-danger', 'bg' => '#ffeaea'],
                                        'financial' => ['icon' => 'fa-money-bill-wave', 'color' => 'text-success', 'bg' => '#e8f8f0'],
                                        'family' => ['icon' => 'fa-users', 'color' => 'text-info', 'bg' => '#e8f4fd'],
                                        'personal' => ['icon' => 'fa-user', 'color' => 'text-warning', 'bg' => '#fff8e1'],
                                        'other' => ['icon' => 'fa-ellipsis-h', 'color' => 'text-secondary', 'bg' => '#f5f5f5'],
                                    ];
                                    $ti = $typeIcons[$exc['excuse_type']] ?? $typeIcons['other'];
                                    $statusBadges = [
                                        'pending' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock'],
                                        'approved' => ['class' => 'bg-success', 'icon' => 'fa-check-circle'],
                                        'rejected' => ['class' => 'bg-danger', 'icon' => 'fa-times-circle'],
                                    ];
                                    $sb = $statusBadges[$exc['status']] ?? $statusBadges['pending']; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center justify-content-center rounded-circle"
                                                    style="width:32px;height:32px;background:<?= $ti['bg'] ?>">
                                                    <i class="fas <?= $ti['icon'] ?> <?= $ti['color'] ?>"
                                                        style="font-size:.8rem"></i>
                                                </div>
                                                <span
                                                    class="fw-semibold"><?= ucfirst($exc['excuse_type']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size:.82rem">
                                                <i
                                                    class="fas fa-calendar-alt me-1 text-muted"></i><?= formatDate($exc['start_date']) ?>
                                                <br>
                                                <i
                                                    class="fas fa-arrow-right me-1 text-muted"></i><?= formatDate($exc['end_date']) ?>
                                            </div>
                                        </td>
                                        <td><?= $exc['course_code'] ? '<span class="badge bg-primary">' . e($exc['course_code']) . '</span>' : '<span class="text-muted" style="font-size:.82rem">All Courses</span>' ?>
                                        </td>
                                        <td style="max-width:250px;font-size:.85rem">
                                            <?= e($exc['description'] ?? '—') ?></td>
                                        <td><span class="badge <?= $sb['class'] ?>"><i
                                                    class="fas <?= $sb['icon'] ?> me-1"></i><?= ucfirst($exc['status']) ?></span>
                                        </td>
                                        <td style="font-size:.82rem"><?= timeAgo($exc['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Submit Excuse Modal -->
<div class="modal fade" id="excuseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header" style="background:var(--primary-50,#e8f0fe);border-bottom:none">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-medical me-2 text-primary"></i>Submit New
                        Excuse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Excuse Type -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="fas fa-tag me-1"></i>Excuse Type <span
                                class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-4 col-6">
                                <input type="radio" class="btn-check" name="excuse_type" id="type_health"
                                    value="health" required>
                                <label class="btn btn-outline-danger w-100 text-start" for="type_health">
                                    <i class="fas fa-heartbeat me-1"></i> Health
                                </label>
                            </div>
                            <div class="col-md-4 col-6">
                                <input type="radio" class="btn-check" name="excuse_type" id="type_financial"
                                    value="financial">
                                <label class="btn btn-outline-success w-100 text-start" for="type_financial">
                                    <i class="fas fa-money-bill-wave me-1"></i> Financial
                                </label>
                            </div>
                            <div class="col-md-4 col-6">
                                <input type="radio" class="btn-check" name="excuse_type" id="type_family"
                                    value="family">
                                <label class="btn btn-outline-info w-100 text-start" for="type_family">
                                    <i class="fas fa-users me-1"></i> Family
                                </label>
                            </div>
                            <div class="col-md-4 col-6">
                                <input type="radio" class="btn-check" name="excuse_type" id="type_personal"
                                    value="personal">
                                <label class="btn btn-outline-warning w-100 text-start" for="type_personal">
                                    <i class="fas fa-user me-1"></i> Personal
                                </label>
                            </div>
                            <div class="col-md-4 col-6">
                                <input type="radio" class="btn-check" name="excuse_type" id="type_other" value="other">
                                <label class="btn btn-outline-secondary w-100 text-start" for="type_other">
                                    <i class="fas fa-ellipsis-h me-1"></i> Other
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fas fa-calendar-alt me-1"></i>From Date
                                <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fas fa-calendar-alt me-1"></i>To Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>

                    <!-- Course Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="fas fa-book me-1"></i>Course
                            <small class="text-muted">(Optional — leave empty for all courses)</small></label>
                        <select class="form-select" name="course_id">
                            <option value="">All Courses (General Excuse)</option>
                            <?php foreach ($myCourses as $mc): ?>
                                <option value="<?= $mc['id'] ?>"><?= e($mc['code'] . ' — ' . $mc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="fas fa-pen me-1"></i>Description
                            <small class="text-muted">(Optional)</small></label>
                        <textarea class="form-control" name="description" rows="3"
                            placeholder="Provide additional details about your excuse..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_excuse" class="btn btn-primary"><i
                            class="fas fa-paper-plane me-1"></i>Submit Excuse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
