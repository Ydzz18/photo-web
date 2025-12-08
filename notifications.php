<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Include required files
require_once 'db_connect.php';
require_once 'notification_manager.php';

// Set page-specific variables
$page_title = 'Notifications';
$page_css = 'notifications.css';

$user_id = $_SESSION['user_id'];
$notificationManager = new NotificationManager();

// Handle mark as read action
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notificationManager->markAsRead((int)$_GET['mark_read'], $user_id);
    header("Location: notifications.php");
    exit();
}

// Handle mark all as read action
if (isset($_GET['mark_all_read'])) {
    $notificationManager->markAllAsRead($user_id);
    $_SESSION['success'] = "All notifications marked as read.";
    header("Location: notifications.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notificationManager->delete((int)$_GET['delete'], $user_id);
    $_SESSION['success'] = "Notification deleted.";
    header("Location: notifications.php");
    exit();
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$unread_only = ($filter === 'unread');

// Get notifications
$notifications = $notificationManager->getUserNotifications($user_id, $unread_only, 50);
$unread_count = $notificationManager->getUnreadCount($user_id);
$stats = $notificationManager->getStats($user_id);

// Handle session messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Include header
include 'header.php';
?>

<!-- Messages -->
<?php if ($success_message): ?>
    <div class="messages">
        <div class="container">
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="messages">
        <div class="container">
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Notifications Section -->
<section class="notifications-section">
    <div class="container">
        <div class="notifications-header">
            <h1 class="page-title">
                <i class="fas fa-bell"></i> Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </h1>
            
            <div class="notifications-actions">
                <?php if ($unread_count > 0): ?>
                    <a href="notifications.php?mark_all_read=1" class="btn-mark-all">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="notification-filters">
            <a href="notifications.php?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                All (<?php echo $stats['total']; ?>)
            </a>
            <a href="notifications.php?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                Unread (<?php echo $unread_count; ?>)
            </a>
        </div>
        
        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <div class="no-notifications-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h3>No notifications yet</h3>
                    <p>When someone likes or comments on your photos, you'll see it here.</p>
                    <a href="home.php" class="btn-primary">Browse Gallery</a>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="notification-icon <?php echo $notification['type']; ?>">
                            <?php
                            $icon = 'fa-bell';
                            if ($notification['type'] === NotificationManager::TYPE_LIKE) {
                                $icon = 'fa-heart';
                            } elseif ($notification['type'] === NotificationManager::TYPE_COMMENT) {
                                $icon = 'fa-comment';
                            } elseif ($notification['type'] === NotificationManager::TYPE_FOLLOW) {
                                $icon = 'fa-user-plus';
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-time">
                                <i class="far fa-clock"></i>
                                <?php
                                $time = strtotime($notification['created_at']);
                                $diff = time() - $time;
                                
                                if ($diff < 60) {
                                    echo "Just now";
                                } elseif ($diff < 3600) {
                                    echo floor($diff / 60) . " minutes ago";
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . " hours ago";
                                } elseif ($diff < 604800) {
                                    echo floor($diff / 86400) . " days ago";
                                } else {
                                    echo date('M j, Y', $time);
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="notification-actions">
                            <?php if ($notification['related_photo_id']): ?>
                                <a href="home.php#photo-<?php echo $notification['related_photo_id']; ?>" 
                                   class="btn-view" 
                                   title="View Photo"
                                   onclick="<?php echo !$notification['is_read'] ? "fetch('notifications.php?mark_read={$notification['id']}');" : ''; ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!$notification['is_read']): ?>
                                <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" 
                                   class="btn-mark-read" 
                                   title="Mark as Read">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php endif; ?>
                            
                            <a href="notifications.php?delete=<?php echo $notification['id']; ?>" 
                               class="btn-delete" 
                               title="Delete"
                               onclick="return confirm('Delete this notification?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$page_scripts = <<<'SCRIPTS'
<script>
    // Auto-hide messages after 3 seconds
    setTimeout(function() {
        const messages = document.querySelector('.messages');
        if (messages) {
            messages.style.transition = 'opacity 0.5s';
            messages.style.opacity = '0';
            setTimeout(() => messages.remove(), 500);
        }
    }, 3000);
</script>
SCRIPTS;

include 'footer_logged_in.php';
?>