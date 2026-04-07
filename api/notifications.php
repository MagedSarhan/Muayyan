<?php
/** MOEEN  - Notifications API */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDBConnection();

switch ($action) {
    case 'get_unread_count':
        $count = getUnreadNotificationCount($userId);
        echo json_encode(['count' => $count]);
        break;

    case 'get_recent':
        $limit = min(20, (int) ($_GET['limit'] ?? 5));
        $notifications = getRecentNotifications($userId, $limit);
        echo json_encode(['notifications' => $notifications]);
        break;

    case 'mark_read':
        if (isset($_POST['id'])) {
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$_POST['id'], $userId]);
            echo json_encode(['success' => true]);
        }
        break;

    case 'mark_all_read':
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
