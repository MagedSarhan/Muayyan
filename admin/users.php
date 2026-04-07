<?php
/** MOEEN  - Admin User Management */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$db = getDBConnection();
$pageTitle = 'User Management';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $db->prepare("INSERT INTO users (user_id, name, email, password, role, phone, department, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            trim($_POST['user_id']),
            trim($_POST['name']),
            trim($_POST['email']),
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['role'],
            trim($_POST['phone']),
            trim($_POST['department']),
            'active'
        ]);
        setFlash('success', 'User created successfully.');
        logActivity($_SESSION['user_id'], 'user_create', 'Created user: ' . $_POST['name']);
    } elseif ($action === 'edit') {
        $sql = "UPDATE users SET user_id=?, name=?, email=?, role=?, phone=?, department=?, status=? WHERE id=?";
        $params = [trim($_POST['user_id']), trim($_POST['name']), trim($_POST['email']), $_POST['role'], trim($_POST['phone']), trim($_POST['department']), $_POST['status'], $_POST['id']];
        if (!empty($_POST['password'])) {
            $sql = "UPDATE users SET user_id=?, name=?, email=?, password=?, role=?, phone=?, department=?, status=? WHERE id=?";
            array_splice($params, 3, 0, [password_hash($_POST['password'], PASSWORD_DEFAULT)]);
        }
        $db->prepare($sql)->execute($params);
        setFlash('success', 'User updated successfully.');
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $db->prepare("DELETE FROM users WHERE id = ? AND id != ?")->execute([$_POST['id'], $_SESSION['user_id']]);
        setFlash('success', 'User deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$roleFilter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];
if ($roleFilter) {
    $where .= " AND role = ?";
    $params[] = $roleFilter;
}
if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR user_id LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}
$users = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$users->execute($params);
$users = $users->fetchAll();

$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <i
                    class="fas fa-<?= $flash['type'] === 'success' ? 'check' : 'times' ?>-circle me-2"></i><?= e($flash['message']) ?><button
                    type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-bold"><i class="fas fa-users-cog me-2 text-primary"></i>User Management</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()"><i
                    class="fas fa-plus me-1"></i> Add User</button>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <form class="d-flex gap-2 flex-wrap w-100" method="GET">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="faculty" <?= $roleFilter === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="advisor" <?= $roleFilter === 'advisor' ? 'selected' : '' ?>>Advisor</option>
                    <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Student</option>
                </select>
                <input type="text" name="search" class="form-control" placeholder="Search users..."
                    value="<?= e($search) ?>" style="max-width:250px">
                <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="student-avatar" style="width:32px;height:32px;font-size:.7rem">
                                            <?= getInitials($u['name']) ?></div>
                                        <span class="fw-semibold"><?= e($u['name']) ?></span>
                                    </div>
                                </td>
                                <td><code><?= e($u['user_id']) ?></code></td>
                                <td><?= e($u['email']) ?></td>
                                <td><span class="badge bg-primary"><?= ucfirst($u['role']) ?></span></td>
                                <td><?= e($u['department']) ?></td>
                                <td><span class="status-dot <?= $u['status'] ?>"></span><?= ucfirst($u['status']) ?></td>
                                <td><?= $u['last_login'] ? formatDate($u['last_login'], 'M d, H:i') : '<span class="text-muted">Never</span>' ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick='editUser(<?= json_encode($u) ?>)'><i class="fas fa-edit"></i></button>
                                    <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id"
                                            value="<?= $u['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add User</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="formId">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label">University ID</label><input type="text"
                                name="user_id" id="f_user_id" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Role</label><select name="role" id="f_role"
                                class="form-select" required>
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                                <option value="advisor">Advisor</option>
                                <option value="admin">Admin</option>
                            </select></div>
                        <div class="col-12"><label class="form-label">Full Name</label><input type="text" name="name"
                                id="f_name" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Email</label><input type="email" name="email"
                                id="f_email" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Password</label><input type="password"
                                name="password" id="f_password" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Phone</label><input type="text" name="phone"
                                id="f_phone" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Department</label><input type="text"
                                name="department" id="f_dept" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Status</label><select name="status" id="f_status"
                                class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Cancel</button><button type="submit"
                        class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function resetForm() {
        document.getElementById('modalTitle').textContent = 'Add User';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        ['f_user_id', 'f_name', 'f_email', 'f_password', 'f_phone', 'f_dept'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('f_role').value = 'student';
        document.getElementById('f_status').value = 'active';
        document.getElementById('f_password').required = true;
    }
    function editUser(u) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = u.id;
        document.getElementById('f_user_id').value = u.user_id;
        document.getElementById('f_name').value = u.name;
        document.getElementById('f_email').value = u.email;
        document.getElementById('f_phone').value = u.phone || '';
        document.getElementById('f_dept').value = u.department || '';
        document.getElementById('f_role').value = u.role;
        document.getElementById('f_status').value = u.status;
        document.getElementById('f_password').required = false;
        document.getElementById('f_password').value = '';
        new bootstrap.Modal(document.getElementById('userModal')).show();
    }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>