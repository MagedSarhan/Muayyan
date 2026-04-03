<?php
/** Muayyan - Admin Settings */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$db = getDBConnection();
$pageTitle = 'System Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $key]);
    }
    setFlash('success', 'Settings updated successfully.');
    header('Location: ' . BASE_URL . '/admin/settings.php'); exit;
}

$settings = $db->query("SELECT * FROM system_settings ORDER BY id")->fetchAll();
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/topbar.php';
?>
<main class="main-content">
<div class="content-wrapper">
    <?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <h5 class="mb-4 fw-bold"><i class="fas fa-cog me-2 text-primary"></i>System Settings</h5>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php foreach ($settings as $s): ?>
                <div class="row mb-3 align-items-center">
                    <div class="col-md-4"><label class="form-label fw-semibold"><?= e(ucwords(str_replace('_', ' ', $s['setting_key']))) ?></label><small class="d-block text-muted"><?= e($s['description']) ?></small></div>
                    <div class="col-md-8"><input type="text" name="settings[<?= e($s['setting_key']) ?>]" class="form-control" value="<?= e($s['setting_value']) ?>"></div>
                </div>
                <?php endforeach; ?>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Settings</button>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
