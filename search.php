<?php
session_start();

require_once 'db_connect.php';
require_once 'settings.php';

$settings = new SiteSettings();
$page_title = 'Search Results';
$page_css = 'home.css';
$additional_css = 'view_photo.css';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
$users_results = [];
$photos_results = [];

if (!empty($search_query)) {
    try {
        $pdo = getDBConnection();
        
        // Search users
        $stmt = $pdo->prepare("
            SELECT id, username, email
            FROM users
            WHERE username LIKE :query OR email LIKE :query
            LIMIT 10
        ");
        $searchTerm = '%' . $search_query . '%';
        $stmt->bindParam(':query', $searchTerm);
        $stmt->execute();
        $users_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Search photos
        $stmt = $pdo->prepare("
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
            WHERE p.title LIKE :query OR p.description LIKE :query
            ORDER BY p.uploaded_at DESC
            LIMIT 20
        ");
        $stmt->bindParam(':query', $searchTerm);
        $stmt->execute();
        $photos_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
    }
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Function to check if user liked a photo
function userLikedPhoto($pdo, $photo_id, $user_id) {
    if (!$user_id) return false;
    
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE photo_id = :photo_id AND user_id = :user_id");
    $stmt->bindParam(':photo_id', $photo_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

include 'header.php';
?>

    <!-- Search Results Section -->
    <section class="search-results-section">
        <div class="container">
            <div class="search-results-header">
                <h1>Search Results</h1>
                <?php if (!empty($search_query)): ?>
                    <p class="search-query">for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
                <?php else: ?>
                    <p class="no-query">Enter a search term to find users and photos</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($search_query)): ?>
                <!-- Users Section -->
                <?php if (!empty($users_results)): ?>
                    <div class="results-section">
                        <h2 class="results-title">
                            <i class="fas fa-users"></i> Users
                        </h2>
                        <div class="users-grid">
                            <?php foreach ($users_results as $user): ?>
                                <div class="user-card">
                                    <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                    <h3 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h3>
                                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <!-- FIXED: Changed from user_id to id to match view_profile.php -->
                                    <a href="view_profile.php?id=<?php echo $user['id']; ?>" class="btn btn-small">View Profile</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Photos Section -->
                <?php if (!empty($photos_results)): ?>
                    <div class="results-section">
                        <h2 class="results-title">
                            <i class="fas fa-images"></i> Photos
                        </h2>
                        <div class="gallery">
                            <?php foreach ($photos_results as $photo): ?>
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
                    </div>
                <?php endif; ?>
                
                <!-- No Results -->
                <?php if (empty($users_results) && empty($photos_results)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h2>No results found</h2>
                        <p>We couldn't find any users or photos matching your search. Try a different search term.</p>
                        <a href="home.php" class="btn btn-primary">Back to Gallery</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-search">
                    <i class="fas fa-search"></i>
                    <p>Use the search box in the header to find users and photos</p>
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
$page_scripts = <<<'SCRIPTS'
    <script>
        function openPhotoModal(photoId) {
            const modal = document.getElementById('photoModal');
            const modalBody = document.getElementById('modalBody');
            
            modalBody.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p class="loading-text">Loading photo...</p>
                </div>
            `;
            
            modal.classList.add('active');
            
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
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function closePhotoModal() {
            document.getElementById('photoModal').classList.remove('active');
        }
        
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
                    } else {
                        button.classList.remove('liked');
                    }
                    showMessage(data.message, 'success');
                }
            });
        }
        
        function submitModalComment(event, photoId) {
            event.preventDefault();
            const form = event.target;
            const textarea = form.querySelector('.comment-input');
            const button = form.querySelector('.comment-submit');
            
            button.disabled = true;
            button.textContent = 'Posting...';
            
            const formData = new FormData();
            formData.append('photo_id', photoId);
            formData.append('comment', textarea.value);
            
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
                    openPhotoModal(photoId);
                }
                button.disabled = false;
                button.textContent = 'Post';
            });
        }
        
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
                    const likeBtn = document.querySelector(`#photo-${photoId} .like-btn`);
                    const likeCount = likeBtn.querySelector('span');
                    likeCount.textContent = data.like_count;
                    if (data.liked) {
                        likeBtn.classList.add('liked');
                    } else {
                        likeBtn.classList.remove('liked');
                    }
                    showMessage(data.message, 'success');
                }
            });
            return false;
        }
        
        function showMessage(message, type) {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'messages';
            msgDiv.innerHTML = `
                <div class="container">
                    <div class="${type}-message">
                        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}
                    </div>
                </div>
            `;
            document.body.appendChild(msgDiv);
            setTimeout(() => msgDiv.remove(), 3000);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
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
        
        document.getElementById('photoModal').addEventListener('click', function(event) {
            if (event.target === this) closePhotoModal();
        });
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closePhotoModal();
        });
    </script>
    
    <style>
        .search-results-section {
            padding: 60px 0;
            min-height: calc(100vh - 200px);
        }
        
        .search-results-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .search-results-header h1 {
            font-size: 2.5rem;
            color: white;
            font-family: 'Playfair Display', serif;
            margin-bottom: 10px;
        }
        
        .search-query {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }
        
        .no-query {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }
        
        .results-section {
            margin-bottom: 50px;
        }
        
        .results-title {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Playfair Display', serif;
        }
        
        .results-title i {
            color: #667eea;
        }
        
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .user-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin: 0 auto 20px;
        }
        
        .user-name {
            font-size: 1.3rem;
            color: #333;
            margin: 15px 0;
            font-weight: 600;
        }
        
        .user-email {
            color: #666;
            font-size: 0.9rem;
            margin: 10px 0 20px;
        }
        
        .btn-small {
            display: inline-block;
            padding: 10px 25px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-small:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        
        .no-results {
            text-align: center;
            padding: 80px 20px;
            color: white;
        }
        
        .no-results i {
            font-size: 80px;
            color: rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }
        
        .no-results h2 {
            font-size: 2rem;
            margin: 20px 0;
            font-family: 'Playfair Display', serif;
        }
        
        .no-results p {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
        }
        
        .empty-search {
            text-align: center;
            padding: 100px 20px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .empty-search i {
            font-size: 80px;
            color: rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .search-results-header h1 {
                font-size: 2rem;
            }
            
            .users-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 20px;
            }
            
            .user-card {
                padding: 20px;
            }
            
            .user-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
SCRIPTS;

include 'footer_logged_in.php';
?>