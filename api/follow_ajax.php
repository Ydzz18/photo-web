<?php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../notification_manager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['user_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$follower_id = $_SESSION['user_id'];
$following_id = (int)$_POST['user_id'];
$action = $_POST['action'];

if ($follower_id === $following_id) {
    echo json_encode(['success' => false, 'message' => 'Cannot follow yourself']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    if ($action === 'follow') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
        $result = $stmt->execute([$follower_id, $following_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            $pdo->prepare("UPDATE users SET followers_count = followers_count + 1 WHERE id = ?")->execute([$following_id]);
            $pdo->prepare("UPDATE users SET following_count = following_count + 1 WHERE id = ?")->execute([$follower_id]);
            
            $follower_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $follower_stmt->execute([$follower_id]);
            $follower = $follower_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($follower) {
                $notificationManager = new NotificationManager($pdo);
                $notificationManager->notifyFollower($following_id, $follower_id, $follower['username']);
            }
        }
        
    } elseif ($action === 'unfollow') {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        
        $pdo->prepare("UPDATE users SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = ?")->execute([$following_id]);
        $pdo->prepare("UPDATE users SET following_count = GREATEST(following_count - 1, 0) WHERE id = ?")->execute([$follower_id]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    error_log("Follow error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
