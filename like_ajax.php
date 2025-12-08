<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to like photos.']);
    exit();
}

// Include database connection and notification manager
require_once 'db_connect.php';
require_once 'notification_manager.php';

$notificationManager = new NotificationManager();

// Check if photo ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid photo ID.']);
    exit();
}

$photo_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Check if photo exists and get owner info
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.user_id as photo_owner_id FROM photos p WHERE p.id = :photo_id");
    $stmt->bindParam(':photo_id', $photo_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Photo not found.']);
        exit();
    }
    
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get current user's username for notification
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_username = $current_user['username'] ?? 'User';
    
    // Check if user already liked this photo
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE photo_id = :photo_id AND user_id = :user_id");
    $stmt->bindParam(':photo_id', $photo_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $liked = false;
    $message = '';
    
    if ($stmt->rowCount() > 0) {
        // Unlike - remove the like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE photo_id = :photo_id AND user_id = :user_id");
        $stmt->bindParam(':photo_id', $photo_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $message = "Photo unliked.";
        $liked = false;
    } else {
        // Like - add the like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, photo_id, created_at) VALUES (:user_id, :photo_id, NOW())");
        $stmt->bindParam(':photo_id', $photo_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $message = "Photo liked!";
        $liked = true;
        
        // Create notification for photo owner if not liking own photo
        if ($photo['photo_owner_id'] != $user_id) {
            $notificationManager->notifyPhotoLike(
                $photo['photo_owner_id'],
                $user_id,
                $current_username,
                $photo_id,
                $photo['title']
            );
        }
    }
    
    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) as like_count FROM likes WHERE photo_id = :photo_id");
    $stmt->bindParam(':photo_id', $photo_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'liked' => $liked,
        'like_count' => $result['like_count']
    ]);
    
} catch(PDOException $e) {
    error_log("Like error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to process like. Please try again.']);
}
?>