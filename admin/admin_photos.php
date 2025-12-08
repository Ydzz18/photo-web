<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: admin_login.php");
    exit();
}

require_once '../db_connect.php';
require_once '../logger.php';
require_once 'rbac.php';

// Check permission to view/manage photos
requirePermission('view_photos');

$logger = new UserLogger();

// Handle photo deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $photo_id = (int)$_GET['delete'];
    
    try {
        $pdo = getDBConnection();
        
        // Get photo info
        $stmt = $pdo->prepare("SELECT p.image_path, p.title, u.username FROM photos p JOIN users u ON p.user_id = u.id WHERE p.id = :photo_id");
        $stmt->bindParam(':photo_id', $photo_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            $photo_title = $photo['title'];
            $photo_owner = $photo['username'];
            
            // Delete photo record
            $stmt = $pdo->prepare("DELETE FROM photos WHERE id = :photo_id");
            $stmt->bindParam(':photo_id', $photo_id);
            
            if ($stmt->execute()) {
                // Delete file
                $file_path = '../uploads/' . $photo['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $_SESSION['success'] = "Photo deleted successfully.";
                
                // Log admin action
                $logger->log(
                    UserLogger::ACTION_ADMIN_DELETE_PHOTO,
                    "Admin '{$_SESSION['admin_username']}' deleted photo '{$photo_title}' by '{$photo_owner}' (ID: {$photo_id})",
                    null,
                    $_SESSION['admin_id'],
                    'photos',
                    $photo_id,
                    UserLogger::STATUS_SUCCESS
                );
            } else {
                $_SESSION['error'] = "Failed to delete photo.";
                
                // Log failure
                $logger->log(
                    UserLogger::ACTION_ADMIN_DELETE_PHOTO,
                    "Failed to delete photo ID: {$photo_id}",
                    null,
                    $_SESSION['admin_id'],
                    null,
                    null,
                    UserLogger::STATUS_FAILED
                );
            }
        } else {
            $_SESSION['error'] = "Photo not found.";
            
            // Log not found
            $logger->log(
                UserLogger::ACTION_ADMIN_DELETE_PHOTO,
                "Photo not found for deletion (ID: {$photo_id})",
                null,
                $_SESSION['admin_id'],
                null,
                null,
                UserLogger::STATUS_FAILED
            );
        }
        
    } catch(PDOException $e) {
        error_log("Delete photo error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete photo.";
        
        // Log error
        $logger->log(
            UserLogger::ACTION_ADMIN_DELETE_PHOTO,
            "Database error deleting photo ID {$photo_id}: " . $e->getMessage(),
            null,
            $_SESSION['admin_id'],
            null,
            null,
            UserLogger::STATUS_FAILED
        );
    }
    
    header("Location: admin_photos.php");
    exit();
}

// Get all photos with user info and stats
try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.title, 
            p.description,
            p.image_path, 
            p.uploaded_at,
            u.username,
            COUNT(DISTINCT l.id) as like_count,
            COUNT(DISTINCT c.id) as comment_count
        FROM photos p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN likes l ON p.id = l.photo_id
        LEFT JOIN comments c ON p.id = c.photo_id
        GROUP BY p.id
        ORDER BY p.uploaded_at DESC
    ");
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Admin photos error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load photos.";
    $photos = [];
}

// Handle session messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="assets/img/admin.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header class="top-bar">
            <h1>Photo Management</h1>
            <div class="admin-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            </div>
        </header>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-images"></i> All Photos (<?php echo count($photos); ?>)
            </h2>
            
            <div class="photos-gallery">
                <?php foreach ($photos as $photo): ?>
                    <div class="admin-photo-card">
                        <div class="admin-photo-image">
                            <img src="../uploads/<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($photo['title']); ?>">
                        </div>
                        <div class="admin-photo-info">
                            <h3><?php echo htmlspecialchars($photo['title']); ?></h3>
                            <p class="photo-author">By: <?php echo htmlspecialchars($photo['username']); ?></p>
                            <p class="photo-description">
                                <?php 
                                $desc = htmlspecialchars($photo['description']);
                                echo strlen($desc) > 80 ? substr($desc, 0, 77) . '...' : $desc; 
                                ?>
                            </p>
                            <div class="photo-stats">
                                <span><i class="fas fa-heart"></i> <?php echo $photo['like_count']; ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo $photo['comment_count']; ?></span>
                            </div>
                            <p class="photo-date">
                                <i class="fas fa-clock"></i> 
                                <?php echo date('M d, Y', strtotime($photo['uploaded_at'])); ?>
                            </p>
                            <div class="photo-actions">
                                <button type="button" class="btn btn-small btn-primary" 
                                        onclick="openPhotoModal(<?php echo htmlspecialchars(json_encode($photo)); ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <a href="admin_photos.php?delete=<?php echo $photo['id']; ?>" 
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Delete this photo? This cannot be undone.');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($photos)): ?>
                <p style="text-align: center; padding: 40px; color: #999;">No photos found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Photo Details Modal -->
    <div id="photoModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="modalTitle">Photo Details</h2>
                <button type="button" class="modal-close" onclick="closePhotoModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-photo-grid">
                    <div class="modal-photo-image">
                        <img id="modalPhotoImg" src="" alt="">
                    </div>
                    <div class="modal-photo-details">
                        <div class="detail-group">
                            <label><i class="fas fa-heading"></i> Title</label>
                            <p id="modalPhotoTitle"></p>
                        </div>
                        <div class="detail-group">
                            <label><i class="fas fa-user"></i> Uploaded By</label>
                            <p id="modalPhotoAuthor"></p>
                        </div>
                        <div class="detail-group">
                            <label><i class="fas fa-calendar"></i> Upload Date</label>
                            <p id="modalPhotoDate"></p>
                        </div>
                        <div class="detail-group">
                            <label><i class="fas fa-align-left"></i> Description</label>
                            <p id="modalPhotoDescription"></p>
                        </div>
                        <div class="detail-group">
                            <label><i class="fas fa-chart-bar"></i> Statistics</label>
                            <div class="modal-stats">
                                <div class="stat-item">
                                    <i class="fas fa-heart"></i>
                                    <div>
                                        <span class="stat-label">Likes</span>
                                        <span class="stat-value" id="modalLikeCount">0</span>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-comment"></i>
                                    <div>
                                        <span class="stat-label">Comments</span>
                                        <span class="stat-value" id="modalCommentCount">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePhotoModal()">Close</button>
                <a id="modalDeleteBtn" href="" class="btn btn-danger" 
                   onclick="return confirm('Delete this photo? This cannot be undone.');">
                    <i class="fas fa-trash"></i> Delete Photo
                </a>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        .modal-content.modal-lg {
            max-width: 900px;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            transition: color 0.3s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            color: #333;
            background: #f5f5f5;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-photo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .modal-photo-image {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 1 / 1;
        }

        .modal-photo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-photo-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .detail-group {
            border-left: 3px solid #007bff;
            padding-left: 15px;
        }

        .detail-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .detail-group p {
            margin: 0;
            color: #333;
            line-height: 1.6;
            word-break: break-word;
        }

        .modal-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 5px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
        }

        .stat-item i {
            font-size: 1.3rem;
            color: #007bff;
        }

        .stat-label {
            display: block;
            font-size: 0.85rem;
            color: #999;
        }

        .stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-footer .btn {
            padding: 10px 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
            }

            .modal-photo-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .modal-photo-image {
                aspect-ratio: 4 / 3;
            }

            .modal-footer {
                flex-direction: column;
            }

            .modal-footer .btn {
                width: 100%;
            }
        }
    </style>

    <script>
        function openPhotoModal(photo) {
            const modal = document.getElementById('photoModal');
            
            // Populate modal with photo data
            document.getElementById('modalTitle').textContent = photo.title;
            document.getElementById('modalPhotoImg').src = '../uploads/' + photo.image_path;
            document.getElementById('modalPhotoTitle').textContent = photo.title;
            document.getElementById('modalPhotoAuthor').textContent = photo.username;
            document.getElementById('modalPhotoDescription').textContent = photo.description || 'No description provided';
            
            // Format date
            const date = new Date(photo.uploaded_at);
            const formattedDate = date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('modalPhotoDate').textContent = formattedDate;
            
            // Update statistics
            document.getElementById('modalLikeCount').textContent = photo.like_count;
            document.getElementById('modalCommentCount').textContent = photo.comment_count;
            
            // Set delete button link
            document.getElementById('modalDeleteBtn').href = 'admin_photos.php?delete=' + photo.id;
            
            // Show modal
            modal.classList.add('active');
        }

        function closePhotoModal() {
            const modal = document.getElementById('photoModal');
            modal.classList.remove('active');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('photoModal');
            if (event.target === modal) {
                closePhotoModal();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePhotoModal();
            }
        });
    </script>
</body>
</html>