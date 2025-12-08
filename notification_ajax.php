<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Include required files
require_once 'db_connect.php';
require_once 'notification_manager.php';

$notificationManager = new NotificationManager();
$user_id = $_SESSION['user_id'];

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'get_count':
            // Get unread notification count
            $count = $notificationManager->getUnreadCount($user_id);
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        case 'get_notifications':
            // Get recent notifications
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $unread_only = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
            
            $notifications = $notificationManager->getUserNotifications($user_id, $unread_only, $limit);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
            break;
            
        case 'mark_read':
            // Mark notification as read
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
                exit();
            }
            
            $notification_id = (int)$_GET['id'];
            $result = $notificationManager->markAsRead($notification_id, $user_id);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark as read'
            ]);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            $result = $notificationManager->markAllAsRead($user_id);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'All notifications marked as read' : 'Failed to mark all as read'
            ]);
            break;
            
        case 'delete':
            // Delete notification
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
                exit();
            }
            
            $notification_id = (int)$_GET['id'];
            $result = $notificationManager->delete($notification_id, $user_id);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification deleted' : 'Failed to delete notification'
            ]);
            break;
            
        case 'get_stats':
            // Get notification statistics
            $stats = $notificationManager->getStats($user_id);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Notification AJAX error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
?>