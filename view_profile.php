<?php
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/settings.php';

// Get database connection
$pdo = getDBConnection();
$settings = new SiteSettings();

$page_title = 'User Profile';
$page_css = 'style.css';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: home.php');
    exit;
}

// Fetch user profile information
$stmt = $pdo->prepare("
    SELECT id, username, email, bio, profile_pic, followers_count, following_count, photos_count, created_at
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    header('Location: home.php');
    exit;
}

// Check if current user is following this user
$is_following = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $user_id) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM follows
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $user_id]);
    $is_following = $stmt->fetch() !== false;
}

// Fetch user's photos
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.description, p.image_path as image_url, p.uploaded_at as created_at, 
           COUNT(DISTINCT l.id) as likes_count, COUNT(DISTINCT c.id) as comments_count
    FROM photos p
    LEFT JOIN likes l ON p.id = l.photo_id
    LEFT JOIN comments c ON p.id = c.photo_id
    WHERE p.user_id = ?
    GROUP BY p.id, p.title, p.description, p.image_path, p.uploaded_at
    ORDER BY p.uploaded_at DESC
");
$stmt->execute([$user_id]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's followers (limit 10)
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.profile_pic
    FROM users u
    INNER JOIN follows f ON u.id = f.follower_id
    WHERE f.following_id = ?
    LIMIT 10
");
$stmt->execute([$user_id]);
$followers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's following (limit 10)
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.profile_pic
    FROM users u
    INNER JOIN follows f ON u.id = f.following_id
    WHERE f.follower_id = ?
    LIMIT 10
");
$stmt->execute([$user_id]);
$following = $stmt->fetchAll(PDO::FETCH_ASSOC);

$profile_pic = !empty($profile_user['profile_pic']) ? 'uploads/profiles/' . htmlspecialchars($profile_user['profile_pic']) : 'assets/img/default-avatar.svg';
$created_date = date('F Y', strtotime($profile_user['created_at']));
?>

<?php include 'header.php'; ?>

<div class="container">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-cover">
                <img src="assets/img/default-cover.svg" alt="Cover photo">
            </div>
            
            <div class="profile-info-section">
                <div class="profile-avatar">
                    <img src="<?php echo $profile_pic; ?>" alt="<?php echo htmlspecialchars($profile_user['username']); ?>">
                </div>
                
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($profile_user['username']); ?></h1>
                    <p class="profile-email">@<?php echo htmlspecialchars(strtolower($profile_user['username'])); ?></p>
                    <?php if (!empty($profile_user['bio'])): ?>
                        <p class="profile-bio"><?php echo htmlspecialchars($profile_user['bio']); ?></p>
                    <?php endif; ?>
                    <p class="profile-joined">Joined <?php echo $created_date; ?></p>
                </div>
                
                <div class="profile-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_id'] === $user_id): ?>
                            <a href="profile.php" class="btn btn-primary">Edit Profile</a>
                        <?php else: ?>
                            <button class="btn <?php echo $is_following ? 'btn-secondary' : 'btn-primary'; ?>" id="followBtn" data-user-id="<?php echo $user_id; ?>">
                                <?php echo $is_following ? 'Following' : 'Follow'; ?>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-primary">Login to Follow</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="profile-stats">
            <div class="stat">
                <div class="stat-value"><?php echo (int)$profile_user['photos_count']; ?></div>
                <div class="stat-label">Photos</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo (int)$profile_user['followers_count']; ?></div>
                <div class="stat-label">Followers</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo (int)$profile_user['following_count']; ?></div>
                <div class="stat-label">Following</div>
            </div>
        </div>

        <!-- Followers/Following Lists -->
        <div class="profile-connections">
            <?php if (!empty($followers)): ?>
                <div class="connections-section">
                    <h3>Followers</h3>
                    <div class="connections-grid">
                        <?php foreach ($followers as $follower): ?>
                            <a href="view_profile.php?id=<?php echo $follower['id']; ?>" class="connection-card">
                                <img src="<?php echo !empty($follower['profile_pic']) ? 'uploads/profiles/' . htmlspecialchars($follower['profile_pic']) : 'assets/img/default-avatar.svg'; ?>" 
                                     alt="<?php echo htmlspecialchars($follower['username']); ?>">
                                <p><?php echo htmlspecialchars($follower['username']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($following)): ?>
                <div class="connections-section">
                    <h3>Following</h3>
                    <div class="connections-grid">
                        <?php foreach ($following as $user): ?>
                            <a href="view_profile.php?id=<?php echo $user['id']; ?>" class="connection-card">
                                <img src="<?php echo !empty($user['profile_pic']) ? 'uploads/profiles/' . htmlspecialchars($user['profile_pic']) : 'assets/img/default-avatar.svg'; ?>" 
                                     alt="<?php echo htmlspecialchars($user['username']); ?>">
                                <p><?php echo htmlspecialchars($user['username']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Photos Gallery Section -->
        <div class="profile-photos-section">
            <h2>Photos</h2>
            
            <?php if (empty($photos)): ?>
                <div class="empty-state">
                    <i class="fas fa-image"></i>
                    <p><?php echo htmlspecialchars($profile_user['username']); ?> hasn't uploaded any photos yet.</p>
                </div>
            <?php else: ?>
                <div class="photos-grid">
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-card" data-photo-id="<?php echo $photo['id']; ?>">
                            <div class="photo-image">
                                <img src="uploads/<?php echo htmlspecialchars($photo['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($photo['title']); ?>"
                                     onerror="this.src='assets/img/image-placeholder.svg';">
                            </div>
                            <div class="photo-overlay">
                                <div class="photo-stats">
                                    <span class="photo-likes">
                                        <i class="fas fa-heart"></i> <?php echo $photo['likes_count']; ?>
                                    </span>
                                    <span class="photo-comments">
                                        <i class="fas fa-comment"></i> <?php echo $photo['comments_count']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div class="photo-modal" id="photoModal">
    <div class="modal-content">
        <button class="modal-close" aria-label="Close">&times;</button>
        <div class="modal-body">
            <div class="modal-image">
                <img id="modalImage" src="" alt="">
            </div>
            <div class="modal-details">
                <div class="modal-header">
                    <div class="modal-user-info">
                        <img id="modalUserAvatar" src="" alt="" class="modal-user-avatar" onerror="this.src='assets/img/default-avatar.svg';">
                        <div>
                            <p class="modal-username"></p>
                            <p class="modal-date"></p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-info">
                    <h2 id="modalTitle"></h2>
                    <p id="modalDescription"></p>
                </div>

                <div class="modal-interactions">
                    <button class="like-btn" id="likeBtn" data-photo-id="" title="Like this photo">
                        <i class="far fa-heart"></i> <span class="like-count">0</span>
                    </button>
                    <button class="comment-btn" id="commentToggleBtn" title="Comment on this photo">
                        <i class="far fa-comment"></i> <span class="comment-count">0</span>
                    </button>
                </div>

                <div class="modal-comments">
                    <div class="comments-list" id="commentsList"></div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="comment-form">
                            <form id="commentForm">
                                <input type="hidden" name="photo_id" id="commentPhotoId">
                                <div class="comment-input-group">
                                    <input type="text" name="comment" id="commentInput" placeholder="Add a comment..." required>
                                    <button type="submit" class="comment-submit" aria-label="Post comment">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Profile Header */
    .profile-header {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .profile-cover {
        width: 100%;
        height: 300px;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .profile-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-info-section {
        display: flex;
        align-items: flex-start;
        padding: 30px;
        gap: 30px;
        position: relative;
        margin-top: -80px;
    }

    .profile-avatar {
        flex-shrink: 0;
    }

    .profile-avatar img {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        object-fit: cover;
    }

    .profile-details {
        flex: 1;
        padding-top: 20px;
    }

    .profile-details h1 {
        font-size: 32px;
        color: #333;
        margin-bottom: 5px;
    }

    .profile-email {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .profile-bio {
        color: #666;
        margin-bottom: 10px;
        line-height: 1.6;
    }

    .profile-joined {
        color: #999;
        font-size: 14px;
    }

    .profile-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .profile-actions .btn {
        min-width: 120px;
    }

    /* Stats Section */
    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
        padding: 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .stat {
        text-align: center;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #667eea;
    }

    .stat-label {
        color: #666;
        margin-top: 5px;
    }

    /* Connections */
    .profile-connections {
        margin-bottom: 40px;
    }

    .connections-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .connections-section h3 {
        margin-bottom: 20px;
        color: #333;
    }

    .connections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 15px;
    }

    .connection-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #333;
        transition: transform 0.3s ease;
    }

    .connection-card:hover {
        transform: translateY(-5px);
    }

    .connection-card img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
        border: 2px solid #f0f0f0;
    }

    .connection-card p {
        font-weight: 600;
        text-align: center;
        word-break: break-word;
        font-size: 14px;
    }

    /* Photos Section */
    .profile-photos-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .profile-photos-section h2 {
        margin-bottom: 30px;
        color: #333;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state i {
        font-size: 48px;
        color: #ddd;
        margin-bottom: 20px;
    }

    /* Photos Grid */
    .photos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .photo-card {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        aspect-ratio: 1;
    }

    .photo-image {
        width: 100%;
        height: 100%;
    }

    .photo-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .photo-card:hover .photo-image img {
        transform: scale(1.05);
    }

    .photo-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .photo-card:hover .photo-overlay {
        opacity: 1;
    }

    .photo-stats {
        display: flex;
        gap: 30px;
        color: white;
        font-weight: 600;
    }

    .photo-stats span {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Modal */
    .photo-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 2000;
        overflow-y: auto;
        padding: 20px;
    }

    .photo-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: 10px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }

    .modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 30px;
        cursor: pointer;
        color: #666;
        z-index: 10;
    }

    .modal-body {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 30px;
        padding: 30px;
    }

    .modal-image img {
        width: 100%;
        border-radius: 8px;
    }

    .modal-user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .modal-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .modal-username {
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .modal-date {
        color: #999;
        font-size: 12px;
        margin: 0;
    }

    .modal-info {
        margin-bottom: 20px;
    }

    .modal-info h2 {
        font-size: 20px;
        color: #333;
        margin-bottom: 10px;
    }

    .modal-info p {
        color: #666;
        line-height: 1.6;
    }

    .modal-interactions {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .like-btn, .comment-btn {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #333;
        font-weight: 600;
    }

    .like-btn:hover, .comment-btn:hover {
        border-color: #667eea;
        color: #667eea;
    }

    .like-btn.liked {
        background: #ffe0e0;
        color: #e74c3c;
        border-color: #e74c3c;
    }

    .comments-list {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .comment-item {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .comment-item:last-child {
        border-bottom: none;
    }

    .comment-author {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .comment-text {
        color: #666;
        line-height: 1.4;
        word-wrap: break-word;
    }

    .comment-time {
        color: #999;
        font-size: 12px;
        margin-top: 5px;
    }

    .comment-form {
        display: none;
    }

    .comment-form.active {
        display: block;
    }

    .comment-input-group {
        display: flex;
        gap: 8px;
    }

    .comment-input-group input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }

    .comment-submit {
        padding: 10px 15px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .comment-submit:hover {
        background: #764ba2;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .profile-info-section {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-avatar img {
            width: 100px;
            height: 100px;
        }

        .profile-actions {
            justify-content: center;
            width: 100%;
        }

        .photos-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .modal-body {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 20px;
        }

        .connections-grid {
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const photoCards = document.querySelectorAll('.photo-card');
        const modal = document.getElementById('photoModal');
        const modalClose = document.querySelector('.modal-close');

        // Open photo modal
        photoCards.forEach(card => {
            card.addEventListener('click', function() {
                const photoId = this.dataset.photoId;
                openPhotoModal(photoId);
            });
        });

        // Close modal
        modalClose.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        function openPhotoModal(photoId) {
            fetch('view_photo.php?id=' + photoId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const photo = data.photo;
                        document.getElementById('modalImage').src = 'uploads/' + photo.image_path;
                        document.getElementById('modalTitle').textContent = photo.title;
                        document.getElementById('modalDescription').textContent = photo.description || '';
                        document.querySelector('.modal-username').textContent = photo.username;
                        document.querySelector('.modal-date').textContent = new Date(photo.uploaded_at).toLocaleDateString();
                        
                        const profilePicSrc = photo.profile_pic ? 'uploads/profiles/' + photo.profile_pic : 'assets/img/default-avatar.svg';
                        document.getElementById('modalUserAvatar').src = profilePicSrc;
                        
                        const likeBtn = document.getElementById('likeBtn');
                        likeBtn.dataset.photoId = photoId;
                        document.querySelector('.like-count').textContent = photo.like_count;
                        document.querySelector('.comment-count').textContent = photo.comment_count;
                        
                        if (data.user_liked) {
                            likeBtn.classList.add('liked');
                        } else {
                            likeBtn.classList.remove('liked');
                        }

                        document.getElementById('commentPhotoId').value = photoId;
                        displayComments(data.comments || []);
                    }
                });
            
            modal.classList.add('active');
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        // Like button functionality
        <?php if (isset($_SESSION['user_id'])): ?>
        document.getElementById('likeBtn').addEventListener('click', function() {
            const photoId = this.dataset.photoId;
            
            fetch('like_ajax.php?id=' + photoId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('liked');
                    document.querySelector('.like-count').textContent = data.like_count;
                }
            });
        });

        // Comment functionality
        document.getElementById('commentToggleBtn').addEventListener('click', function() {
            document.querySelector('.comment-form').classList.toggle('active');
        });

        document.getElementById('commentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const photoId = document.getElementById('commentPhotoId').value;
            const comment = document.getElementById('commentInput').value;

            fetch('comment_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'photo_id=' + photoId + '&comment=' + encodeURIComponent(comment)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('commentInput').value = '';
                    document.querySelector('.comment-count').textContent = data.comment_count;
                    reloadPhotoData(photoId);
                }
            });
        });

        function displayComments(comments) {
            const commentsList = document.getElementById('commentsList');
            commentsList.innerHTML = '';
            
            if (comments && comments.length > 0) {
                comments.forEach(comment => {
                    const commentHtml = `
                        <div class="comment-item">
                            <div class="comment-author">${comment.username}</div>
                            <div class="comment-text">${comment.comment_text}</div>
                            <div class="comment-time">${new Date(comment.created_at).toLocaleDateString()}</div>
                        </div>
                    `;
                    commentsList.innerHTML += commentHtml;
                });
            }
        }

        function reloadPhotoData(photoId) {
            fetch('view_photo.php?id=' + photoId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayComments(data.comments || []);
                    }
                });
        }
        <?php endif; ?>

        // Follow button functionality
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $user_id): ?>
        const followBtn = document.getElementById('followBtn');
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const isFollowing = this.textContent.trim() === 'Following';
                
                fetch('api/follow_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'user_id=' + userId + '&action=' + (isFollowing ? 'unfollow' : 'follow')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.textContent = isFollowing ? 'Follow' : 'Following';
                        this.classList.toggle('btn-primary');
                        this.classList.toggle('btn-secondary');
                    }
                });
            });
        }
        <?php endif; ?>
    });
</script>

<?php include 'footer_logged_in.php'; ?>