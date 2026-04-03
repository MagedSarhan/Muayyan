<?php
/**
 * Muayyan - Top Navigation Bar
 */
$notifications = getRecentNotifications($_SESSION['user_id'] ?? 0, 5);
$unreadCount = getUnreadNotificationCount($_SESSION['user_id'] ?? 0);
?>

<!-- Topbar -->
<header class="topbar">
    <div class="topbar-left">
        <button class="topbar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="page-title">
            <?= e($pageTitle ?? 'Dashboard') ?>
            <small><?= CURRENT_SEMESTER ?> &bull; <?= ACADEMIC_YEAR ?></small>
        </div>
    </div>
    
    <div class="topbar-right">
        <!-- Notifications -->
        <div class="position-relative">
            <button class="notification-bell" id="notifBtn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                <span class="badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                <?php endif; ?>
            </button>
            
            <div class="notification-dropdown" id="notifDropdown">
                <div class="notification-header">
                    <h6>Notifications</h6>
                    <?php if ($unreadCount > 0): ?>
                    <a href="#" onclick="markAllRead()" style="font-size:.75rem">Mark all read</a>
                    <?php endif; ?>
                </div>
                <div class="notification-list">
                    <?php if (empty($notifications)): ?>
                    <div class="text-center p-4">
                        <i class="fas fa-bell-slash text-muted" style="font-size:1.5rem"></i>
                        <p class="text-muted mt-2 mb-0" style="font-size:.8rem">No notifications</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                    <div class="notification-item <?= $n['is_read'] ? '' : 'unread' ?>">
                        <div class="notification-icon <?= e($n['type']) ?>">
                            <i class="fas <?= $n['type']==='alert' ? 'fa-exclamation-triangle' : ($n['type']==='grade' ? 'fa-star' : ($n['type']==='request' ? 'fa-envelope' : 'fa-info-circle')) ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="title"><?= e($n['title']) ?></div>
                            <div class="text"><?= e($n['message']) ?></div>
                            <div class="time"><?= timeAgo($n['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- User Menu -->
        <div class="dropdown">
            <div class="user-menu" data-bs-toggle="dropdown">
                <div class="user-menu-info">
                    <div class="name"><?= e($_SESSION['user_name'] ?? '') ?></div>
                    <div class="role"><?= e(ucfirst($_SESSION['user_role'] ?? '')) ?></div>
                </div>
                <div class="user-menu-avatar"><?= getInitials($_SESSION['user_name'] ?? 'U') ?></div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</header>
