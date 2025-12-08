<?php
session_start();

// Include database connection and settings
require_once 'db_connect.php';
require_once 'settings.php';

// Initialize settings
$settings = new SiteSettings();

// Set page-specific variables
$page_title = 'Gallery';
$page_css = 'home.css';
$additional_css = 'view_photo.css';

// Function to get photos with like and comment counts
function getPhotos($pdo, $limit = 12, $search = '', $sort = 'latest') {
    $query = "
        SELECT 
            p.id, 
            p.title, 
            p.description, 
            p.image_path, 
            p.uploaded_at,
            u.username,
            u.id as user_id,
            u.profile_pic,
            COALESCE(l.like_count, 0) as like_count,
            COALESCE(c.comment_count, 0) as comment_count
        FROM photos p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN (
            SELECT photo_id, COUNT(*) as like_count 
            FROM likes 
            GROUP BY photo_id
        ) l ON p.id = l.photo_id
        LEFT JOIN (
            SELECT photo_id, COUNT(*) as comment_count 
            FROM comments 
            GROUP BY photo_id
        ) c ON p.id = c.photo_id
    ";
    
    // Add search filter
    if (!empty($search)) {
        $query .= " WHERE p.title LIKE :search OR p.description LIKE :search OR u.username LIKE :search";
    }
    
    // Add sorting
    switch ($sort) {
        case 'popular':
            $query .= " ORDER BY l.like_count DESC, p.uploaded_at DESC";
            break;
        case 'trending':
            $query .= " ORDER BY (l.like_count + c.comment_count) DESC, p.uploaded_at DESC";
            break;
        case 'oldest':
            $query .= " ORDER BY p.uploaded_at ASC";
            break;
        case 'latest':
        default:
            $query .= " ORDER BY p.uploaded_at DESC";
    }
    
    $query .= " LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $stmt->bindParam(':search', $searchTerm);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get trending hashtags
function getTrendingHashtags($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, '#', numbers.n), ' ', -1) as hashtag,
            COUNT(*) as count
        FROM photos p
        JOIN (
            SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
        ) numbers ON CHAR_LENGTH(p.description) - CHAR_LENGTH(REPLACE(p.description, '#', '')) >= numbers.n - 1
        WHERE p.description LIKE '%#%'
        GROUP BY hashtag
        HAVING hashtag LIKE '#%'
        ORDER BY count DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get comments for a photo
function getPhotoComments($pdo, $photo_id, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.comment_text,
            c.created_at,
            u.username,
            u.id as user_id
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.photo_id = :photo_id
        ORDER BY c.created_at DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':photo_id', $photo_id);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to check if user liked a photo
function userLikedPhoto($pdo, $photo_id, $user_id) {
    if (!$user_id) return false;
    
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE photo_id = :photo_id AND user_id = :user_id");
    $stmt->bindParam(':photo_id', $photo_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Get current user ID
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$hashtag = isset($_GET['hashtag']) ? trim($_GET['hashtag']) : '';

// If hashtag is provided, use it as search
if (!empty($hashtag)) {
    $search = $hashtag;
}

// Get photos from database
try {
    $pdo = getDBConnection();
    $photos = getPhotos($pdo, 12, $search, $sort);
    $trending_hashtags = getTrendingHashtags($pdo, 8);
} catch(PDOException $e) {
    $photos = [];
    $trending_hashtags = [];
    error_log("Database error: " . $e->getMessage());
}

// Handle session messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Clear session messages
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Capture. Share. Inspire.</h1>
            <p>Join the world's most passionate photography community. Upload your photos, connect with fellow photographers, and showcase your talent to the world.</p>
            <?php if ($user_id): ?>
                <a href="upload.php" class="btn btn-primary">Upload Your Photo</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Join Now</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="gallery-section">
        <div class="container">
            <h2 class="section-title">Latest Gallery</h2>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-container">
                    <form method="GET" class="filter-form">
                        <label for="sort-select">Sort By:</label>
                        <select id="sort-select" name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            <option value="trending" <?php echo $sort === 'trending' ? 'selected' : ''; ?>>Trending</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- Trending Hashtags -->
            <?php if (!empty($trending_hashtags)): ?>
                <div class="trending-section">
                    <h3 class="trending-title">
                        <i class="fas fa-fire"></i> Trending Hashtags
                    </h3>
                    <div class="hashtags-container">
                        <?php foreach ($trending_hashtags as $tag): ?>
                            <a href="?hashtag=<?php echo urlencode($tag['hashtag']); ?>" class="hashtag-item">
                                <span class="hashtag-text"><?php echo htmlspecialchars($tag['hashtag']); ?></span>
                                <span class="hashtag-count"><?php echo $tag['count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($photos)): ?>
                <div style="text-align: center; color: white; margin: 40px 0;">
                    <?php if (!empty($search)): ?>
                        <p>No photos found for "<strong><?php echo htmlspecialchars($search); ?></strong>". Try a different search term.</p>
                    <?php else: ?>
                        <p>No photos uploaded yet. Be the first to share your photography!</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="gallery">
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-card" id="photo-<?php echo $photo['id']; ?>">
                            <div class="photo-image-container">
                                <img src="uploads/<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($photo['title']); ?>" 
                                     class="photo-image"
                                     onerror="this.src='assets/img/image-placeholder.svg';"
                                     onclick="openPhotoModal(<?php echo $photo['id']; ?>); return false;">
                            </div>
                            <div class="photo-info">
                                <h3 class="photo-title"><?php echo htmlspecialchars($photo['title']); ?></h3>
                                <div class="photo-meta">
                                    <span class="photo-user">By <?php echo htmlspecialchars($photo['username']); ?></span>
                                    <span><?php echo date('M j, Y', strtotime($photo['uploaded_at'])); ?></span>
                                </div>
                                <?php if (!empty($photo['description'])): ?>
                                    <p class="photo-description"><?php echo htmlspecialchars(substr($photo['description'], 0, 150)); ?><?php echo strlen($photo['description']) > 150 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                <div class="photo-actions">
                                    <?php if ($user_id): ?>
                                        <a href="#" 
                                           class="like-btn <?php echo userLikedPhoto($pdo, $photo['id'], $user_id) ? 'liked' : ''; ?>"
                                           onclick="return likePhoto(<?php echo $photo['id']; ?>)">
                                            <i class="fas fa-heart"></i>
                                            <span><?php echo $photo['like_count']; ?></span>
                                        </a>
                                        <button type="button" class="comment-btn" onclick="openPhotoModal(<?php echo $photo['id']; ?>); return false;">
                                            <i class="fas fa-comment"></i>
                                            <span><?php echo $photo['comment_count']; ?></span>
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="like-btn">
                                            <i class="fas fa-heart"></i>
                                            <span><?php echo $photo['like_count']; ?></span>
                                        </a>
                                        <a href="login.php" class="comment-btn">
                                            <i class="fas fa-comment"></i>
                                            <span><?php echo $photo['comment_count']; ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Photo Modal -->
    <div id="photoModal" class="photo-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Photo Details</h2>
                <button class="modal-close" onclick="closePhotoModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading">
                    <div class="spinner"></div>
                    <p class="loading-text">Loading photo...</p>
                </div>
            </div>
        </div>
    </div>

<?php
// Page-specific scripts
$page_scripts = <<<'SCRIPTS'
    <script>
        // Open Photo Modal
        function openPhotoModal(photoId) {
            const modal = document.getElementById('photoModal');
            const modalBody = document.getElementById('modalBody');
            
            // Show loading state
            modalBody.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p class="loading-text">Loading photo...</p>
                </div>
            `;
            
            modal.classList.add('active');
            
            // Fetch photo details
            fetch('view_photo.php?id=' + photoId, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const photo = data.photo;
                    const comments = data.comments || [];
                    const userLiked = data.user_liked || false;
                    const isLoggedIn = data.is_logged_in || false;
                    
                    let commentsHTML = '';
                    if (comments.length > 0) {
                        commentsHTML = comments.map(comment => `
                            <div class="comment-item">
                                <div class="comment-header">
                                    <span class="comment-author">${escapeHtml(comment.username)}</span>
                                    <span class="comment-time">${formatDate(comment.created_at)}</span>
                                </div>
                                <div class="comment-text">${escapeHtml(comment.comment_text)}</div>
                            </div>
                        `).join('');
                    } else {
                        commentsHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
                    }
                    
                    const commentFormHTML = isLoggedIn ? `
                        <div class="comment-form-section">
                            <form onsubmit="submitModalComment(event, ${photoId})">
                                <div class="comment-input-group">
                                    <textarea class="comment-input" placeholder="Add a comment..." maxlength="500" required></textarea>
                                    <button type="submit" class="comment-submit">Post</button>
                                </div>
                            </form>
                        </div>
                    ` : `
                        <div class="comment-form-section">
                            <div class="login-prompt">
                                <a href="login.php">Login</a> to comment on this photo
                            </div>
                        </div>
                    `;
                    
                    const likeButtonHTML = isLoggedIn ? `
                        <button class="action-btn like-btn ${userLiked ? 'liked' : ''}" onclick="likePhotoFromModal(${photoId}, this)">
                            <i class="fas fa-heart"></i>
                            <span>${photo.like_count}</span>
                        </button>
                    ` : `
                        <a href="login.php" class="action-btn like-btn">
                            <i class="fas fa-heart"></i>
                            <span>${photo.like_count}</span>
                        </a>
                    `;
                    
                    const uploadDate = new Date(photo.uploaded_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    
                    modalBody.innerHTML = `
                        <div class="photo-display">
                            <img src="uploads/${escapeHtml(photo.image_path)}" alt="${escapeHtml(photo.title)}" class="photo-main-image" onerror="this.src='assets/img/image-placeholder.svg';">
                            <div class="photo-stats">
                                <div class="stat">
                                    <i class="fas fa-heart"></i>
                                    <span>
                                        <span class="stat-value">${photo.like_count}</span>
                                        <span class="stat-label">Likes</span>
                                    </span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-comment"></i>
                                    <span>
                                        <span class="stat-value">${photo.comment_count}</span>
                                        <span class="stat-label">Comments</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="photo-details">
                            <div class="photo-info-section">
                                <div class="info-label">Title</div>
                                <div class="info-value">${escapeHtml(photo.title)}</div>
                            </div>
                            
                            <div class="photo-info-section">
                                <div class="info-label">Description</div>
                                <div class="info-value">${photo.description ? escapeHtml(photo.description) : '<em>No description provided</em>'}</div>
                            </div>
                            
                            <div class="photo-info-section">
                                <div class="info-label">Photographer</div>
                                <div class="photo-author">
                                    <img src="${photo.profile_pic ? 'uploads/profiles/' + escapeHtml(photo.profile_pic) : 'assets/img/default-avatar.svg'}" alt="${escapeHtml(photo.username)}" class="author-avatar" onerror="this.src='assets/img/default-avatar.svg';">
                                    <div class="author-info">
                                        <strong>${escapeHtml(photo.username)}</strong>
                                        <small>Posted on ${uploadDate}</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="photo-actions">
                                ${likeButtonHTML}
                            </div>
                            
                            <div class="comments-section">
                                <h3 class="comments-title">Comments</h3>
                                <div class="comments-list">
                                    ${commentsHTML}
                                </div>
                                ${commentFormHTML}
                            </div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <div class="error-state">
                            <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
                            <div class="error-message">${data.message || 'Failed to load photo'}</div>
                            <a href="#" onclick="closePhotoModal(); return false;">Close Modal</a>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="error-message">An error occurred while loading the photo</div>
                        <a href="#" onclick="closePhotoModal(); return false;">Close Modal</a>
                    </div>
                `;
            });
        }
        
        // Close Photo Modal
        function closePhotoModal() {
            const modal = document.getElementById('photoModal');
            modal.classList.remove('active');
        }
        
        // Like photo from modal
        function likePhotoFromModal(photoId, button) {
            fetch('like_ajax.php?id=' + photoId, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeCount = button.querySelector('span');
                    likeCount.textContent = data.like_count;
                    
                    if (data.liked) {
                        button.classList.add('liked');
                        const heart = button.querySelector('i');
                        heart.style.animation = 'heartBeat 0.3s ease';
                        setTimeout(() => heart.style.animation = '', 300);
                    } else {
                        button.classList.remove('liked');
                    }
                    
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message || 'Failed to like photo', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        }
        
        // Submit comment from modal
        function submitModalComment(event, photoId) {
            event.preventDefault();
            
            const form = event.target;
            const textarea = form.querySelector('.comment-input');
            const button = form.querySelector('.comment-submit');
            const comment = textarea.value;
            
            if (!comment.trim()) return;
            
            button.disabled = true;
            button.textContent = 'Posting...';
            
            const formData = new FormData();
            formData.append('photo_id', photoId);
            formData.append('comment', comment);
            
            fetch('comment_ajax.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    showMessage(data.message, 'success');
                    // Reload modal to show new comment
                    openPhotoModal(photoId);
                } else {
                    showMessage(data.message || 'Failed to post comment', 'error');
                }
                
                button.disabled = false;
                button.textContent = 'Post';
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
                button.disabled = false;
                button.textContent = 'Post';
            });
        }
        
        // Escape HTML special characters
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // AJAX Like Handler (for gallery view)
        function likePhoto(photoId) {
            fetch('like_ajax.php?id=' + photoId, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update like count
                    const likeBtn = document.querySelector(`#photo-${photoId} .like-btn`);
                    const likeCount = likeBtn.querySelector('span');
                    likeCount.textContent = data.like_count;
                    
                    // Toggle liked class with animation
                    if (data.liked) {
                        likeBtn.classList.add('liked');
                        // Heart animation
                        const heart = likeBtn.querySelector('i');
                        heart.style.animation = 'heartBeat 0.3s ease';
                        setTimeout(() => heart.style.animation = '', 300);
                    } else {
                        likeBtn.classList.remove('liked');
                    }
                    
                    // Show success message
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message || 'Failed to like photo', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
            
            return false;
        }

        // Show message notification
        function showMessage(message, type) {
            // Remove existing messages
            const existingMsg = document.querySelector('.messages');
            if (existingMsg) {
                existingMsg.remove();
            }
            
            // Create new message
            const msgDiv = document.createElement('div');
            msgDiv.className = 'messages';
            msgDiv.style.animation = 'slideDown 0.3s ease';
            msgDiv.innerHTML = `
                <div class="container">
                    <div class="${type}-message">
                        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}
                    </div>
                </div>
            `;
            
            document.body.appendChild(msgDiv);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                msgDiv.style.transition = 'opacity 0.5s';
                msgDiv.style.opacity = '0';
                setTimeout(() => msgDiv.remove(), 500);
            }, 3000);
        }

        // Close modal when clicking outside
        document.getElementById('photoModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closePhotoModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePhotoModal();
            }
        });
        
        // Auto-hide initial success messages after 3 seconds
        setTimeout(function() {
            const messages = document.querySelector('.messages');
            if (messages) {
                messages.style.transition = 'opacity 0.5s';
                messages.style.opacity = '0';
                setTimeout(function() {
                    messages.style.display = 'none';
                }, 500);
            }
        }, 3000);
    </script>
    
    <style>
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .like-btn.liked i {
            color: #ff4757;
            animation: heartBeat 0.3s ease;
        }
        
        .comment-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Search and Filter Styles */
        .search-filter-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .search-container {
            flex: 1;
        }
        
        .search-form {
            width: 100%;
        }
        
        .search-input-group {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 0 15px;
            transition: all 0.3s ease;
        }
        
        .search-input-group:focus-within {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
        }
        
        .search-input-group i {
            color: rgba(255, 255, 255, 0.6);
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .search-input {
            flex: 1;
            background: transparent;
            border: none;
            color: white;
            padding: 12px 0;
            font-size: 1rem;
            outline: none;
        }
        
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-left: 10px;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .filter-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .filter-form label {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
        }
        
        .sort-select {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 250px;
        }
        
        .sort-select:hover,
        .sort-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            outline: none;
        }
        
        .sort-select option {
            background: #1a1a2e;
            color: white;
        }
        
        /* Active Filters */
        .active-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(102, 126, 234, 0.2);
            color: rgba(255, 255, 255, 0.9);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            border: 1px solid rgba(102, 126, 234, 0.4);
        }
        
        .remove-filter {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
            margin-left: 5px;
        }
        
        .remove-filter:hover {
            color: white;
        }
        
        /* Trending Section */
        .trending-section {
            background: rgba(102, 126, 234, 0.1);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .trending-title {
            color: white;
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .trending-title i {
            color: #ff6b6b;
        }
        
        .hashtags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .hashtag-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .hashtag-item:hover {
            background: rgba(102, 126, 234, 0.3);
            color: white;
            border-color: rgba(102, 126, 234, 0.5);
            transform: translateY(-2px);
        }
        
        .hashtag-text {
            color: inherit;
        }
        
        .hashtag-count {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .hashtag-item:hover .hashtag-count {
            background: rgba(102, 126, 234, 0.5);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .search-filter-section {
                padding: 15px;
                gap: 15px;
            }
            
            .search-input-group {
                flex-direction: column;
                align-items: stretch;
                padding: 10px;
                gap: 10px;
            }
            
            .search-input-group i {
                margin-right: 0;
                text-align: center;
            }
            
            .search-input {
                padding: 10px 0;
            }
            
            .search-btn {
                margin-left: 0;
                width: 100%;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-form label {
                margin-bottom: 5px;
            }
            
            .sort-select {
                max-width: 100%;
            }
            
            .hashtags-container {
                gap: 8px;
            }
            
            .hashtag-item {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
SCRIPTS;

include 'footer_logged_in.php';
?>