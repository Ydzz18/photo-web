<?php
/**
 * Notification Manager Class
 * Handles creating, reading, and managing user notifications
 */

class NotificationManager {
    private $pdo;
    
    // Notification types
    const TYPE_LIKE = 'like';
    const TYPE_COMMENT = 'comment';
    const TYPE_FOLLOW = 'follow';
    const TYPE_PHOTO_DELETE = 'photo_delete';
    const TYPE_COMMENT_DELETE = 'comment_delete';
    const TYPE_SYSTEM = 'system';
    
    public function __construct($pdo = null) {
        try {
            if ($pdo === null) {
                require_once __DIR__ . '/db_connect.php';
                $this->pdo = getDBConnection();
            } else {
                $this->pdo = $pdo;
            }
            
            // Check if notifications table exists
            $this->checkTableExists();
        } catch (Exception $e) {
            error_log("NotificationManager initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if notifications table exists
     */
    private function checkTableExists() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($stmt->rowCount() === 0) {
                $this->createNotificationsTable();
            }
        } catch (PDOException $e) {
            error_log("Error checking notifications table: " . $e->getMessage());
        }
    }
    
    /**
     * Create notifications table if it doesn't exist
     */
    private function createNotificationsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `type` varchar(50) NOT NULL,
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `related_user_id` int(11) DEFAULT NULL,
                `related_photo_id` int(11) DEFAULT NULL,
                `related_comment_id` int(11) DEFAULT NULL,
                `is_read` tinyint(1) DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_is_read` (`is_read`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_type` (`type`),
                KEY `idx_user_unread` (`user_id`, `is_read`, `created_at`),
                CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating notifications table: " . $e->getMessage());
        }
    }
    
    /**
     * Create a new notification
     * 
     * @param int $user_id User who will receive the notification
     * @param string $type Type of notification
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int|null $related_user_id User who triggered the notification
     * @param int|null $related_photo_id Related photo ID
     * @param int|null $related_comment_id Related comment ID
     * @return bool Success status
     */
    public function create($user_id, $type, $title, $message, $related_user_id = null, $related_photo_id = null, $related_comment_id = null) {
        try {
            // Don't create notification if user is notifying themselves
            if ($user_id == $related_user_id) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, type, title, message, related_user_id, related_photo_id, related_comment_id)
                VALUES 
                (:user_id, :type, :title, :message, :related_user_id, :related_photo_id, :related_comment_id)
            ");
            
            return $stmt->execute([
                ':user_id' => $user_id,
                ':type' => $type,
                ':title' => $title,
                ':message' => $message,
                ':related_user_id' => $related_user_id,
                ':related_photo_id' => $related_photo_id,
                ':related_comment_id' => $related_comment_id
            ]);
        } catch (PDOException $e) {
            error_log("Create notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for photo like
     */
    public function notifyPhotoLike($photo_owner_id, $liker_id, $liker_username, $photo_id, $photo_title) {
        $title = "New Like on Your Photo";
        $message = "{$liker_username} liked your photo \"{$photo_title}\"";
        return $this->create($photo_owner_id, self::TYPE_LIKE, $title, $message, $liker_id, $photo_id);
    }
    
    /**
     * Create notification for photo comment
     */
    public function notifyPhotoComment($photo_owner_id, $commenter_id, $commenter_username, $photo_id, $photo_title, $comment_id) {
        $title = "New Comment on Your Photo";
        $message = "{$commenter_username} commented on your photo \"{$photo_title}\"";
        return $this->create($photo_owner_id, self::TYPE_COMMENT, $title, $message, $commenter_id, $photo_id, $comment_id);
    }
    
    /**
     * Create notification for new follower
     */
    public function notifyFollower($user_id, $follower_id, $follower_username) {
        $title = "New Follower";
        $message = "{$follower_username} started following you";
        return $this->create($user_id, self::TYPE_FOLLOW, $title, $message, $follower_id);
    }
    
    /**
     * Get user notifications
     * 
     * @param int $user_id User ID
     * @param bool $unread_only Get only unread notifications
     * @param int $limit Number of notifications to fetch
     * @param int $offset Offset for pagination
     * @return array Array of notifications
     */
    public function getUserNotifications($user_id, $unread_only = false, $limit = 20, $offset = 0) {
        try {
            $where = "WHERE n.user_id = :user_id";
            if ($unread_only) {
                $where .= " AND n.is_read = 0";
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    n.*,
                    u.username as related_username
                FROM notifications n
                LEFT JOIN users u ON n.related_user_id = u.id
                $where
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notification count
     * 
     * @param int $user_id User ID
     * @return int Number of unread notifications
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = :user_id AND is_read = 0
            ");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
        } catch (PDOException $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (for security)
     * @return bool Success status
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = :notification_id AND user_id = :user_id
            ");
            
            return $stmt->execute([
                ':notification_id' => $notification_id,
                ':user_id' => $user_id
            ]);
        } catch (PDOException $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = :user_id AND is_read = 0
            ");
            
            return $stmt->execute([':user_id' => $user_id]);
        } catch (PDOException $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a notification
     * 
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (for security)
     * @return bool Success status
     */
    public function delete($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE id = :notification_id AND user_id = :user_id
            ");
            
            return $stmt->execute([
                ':notification_id' => $notification_id,
                ':user_id' => $user_id
            ]);
        } catch (PDOException $e) {
            error_log("Delete notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old read notifications
     * 
     * @param int $days Notifications older than this will be deleted
     * @return bool Success status
     */
    public function cleanOldNotifications($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE is_read = 1 
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            
            return $stmt->execute([':days' => $days]);
        } catch (PDOException $e) {
            error_log("Clean old notifications error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notification statistics
     * 
     * @param int $user_id User ID
     * @return array Statistics
     */
    public function getStats($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN type = :type_like THEN 1 ELSE 0 END) as likes,
                    SUM(CASE WHEN type = :type_comment THEN 1 ELSE 0 END) as comments
                FROM notifications 
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                ':user_id' => $user_id,
                ':type_like' => self::TYPE_LIKE,
                ':type_comment' => self::TYPE_COMMENT
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get notification stats error: " . $e->getMessage());
            return [
                'total' => 0,
                'unread' => 0,
                'likes' => 0,
                'comments' => 0
            ];
        }
    }
}
?>