<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/settings.php';

$settings = new SiteSettings();
$site_name = $settings->get('site_name', 'LensCraft');
$site_logo = $settings->get('site_logo', 'logo.png');
$site_favicon = $settings->get('site_favicon', 'favicon.ico');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; echo htmlspecialchars($site_name); ?></title>
    <link rel="icon" type="image/png" href="assets/img/<?php echo htmlspecialchars($site_favicon); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/notifications-dropdown.css">
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($page_css); ?>">
    <?php endif; ?>
    <?php if (isset($additional_css)): ?>
        <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($additional_css); ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'home.php' : 'index.php'; ?>" class="logo"><?php echo htmlspecialchars($site_name); ?></a>
                
                <!-- Global Search -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="search-container-nav">
                        <form method="GET" action="search.php" class="search-form-nav" id="headerSearchForm">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" name="q" class="search-input-nav" placeholder="Search users, posts..." 
                                       value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                                <button type="submit" class="search-btn-nav" aria-label="Search">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="nav-menu" id="navMenu">
                    <div class="nav-links">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="home.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'class="active"' : ''; ?>>Gallery</a>
                            <a href="upload.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'upload.php') ? 'class="active"' : ''; ?>>Upload</a>
                            <a href="profile.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'class="active"' : ''; ?>>Profile</a>
                            <!-- Notification Bell Dropdown -->
                            <div class="notification-container">
                                <button class="notification-bell" id="notificationBell" aria-label="Notifications" title="Notifications">
                                    <i class="fas fa-bell"></i>
                                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                                </button>
                                <div class="notification-dropdown" id="notificationDropdown">
                                    <div class="notification-header">
                                        <h3>Notifications</h3>
                                        <button class="mark-all-read" id="markAllRead" title="Mark all as read">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    </div>
                                    <div class="notification-list" id="notificationList">
                                        <div class="loading">
                                            <div class="spinner"></div>
                                            <p>Loading notifications...</p>
                                        </div>
                                    </div>
                                    <div class="notification-footer">
                                        <a href="notifications.php" class="view-all">View all notifications</a>
                                    </div>
                                </div>
                            </div>
                            <a href="auth/logout.php">Logout</a>
                        <?php else: ?>
                            <a href="#gallery">Gallery</a>
                            <a href="#about">About</a>
                            <a href="#contact">Contact</a>
                        <?php endif; ?>
                    </div>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="auth-buttons">
                            <a href="auth/login.php" class="btn btn-secondary">Login</a>
                            <a href="auth/register.php" class="btn btn-primary">Sign Up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    <script>
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        const header = document.querySelector('header');
        
        hamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
        
        document.querySelectorAll('#navMenu a').forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });
        
        document.addEventListener('click', function(e) {
            if (!header.contains(e.target) && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });

        // Notification System
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBadge = document.getElementById('notificationBadge');
        const notificationList = document.getElementById('notificationList');
        const markAllReadBtn = document.getElementById('markAllRead');

        // Initialize notifications if user is logged in
        if (notificationBell) {
            // Load notifications on page load
            loadNotifications();
            
            // Refresh notifications every 30 seconds
            setInterval(loadNotifications, 30000);
            
            // Toggle dropdown
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('active');
                if (notificationDropdown.classList.contains('active')) {
                    loadNotifications();
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!header.contains(e.target) && notificationDropdown.classList.contains('active')) {
                    notificationDropdown.classList.remove('active');
                }
            });
            
            // Mark all as read
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    markAllAsRead();
                });
            }
        }

        function loadNotifications() {
            fetch('notification_ajax.php?action=get_notifications&limit=5&unread_only=false', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications) {
                    updateNotificationUI(data.notifications);
                    
                    // Update badge with unread count
                    fetch('notification_ajax.php?action=get_count', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(countData => {
                        if (countData.success) {
                            updateBadge(countData.count);
                        }
                    });
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
        }

        function updateNotificationUI(notifications) {
            if (!notificationList) return;
            
            if (notifications.length === 0) {
                notificationList.innerHTML = '<div class="empty-notifications"><p>No notifications yet</p></div>';
                return;
            }
            
            let html = '';
            notifications.forEach(notification => {
                const createdAt = new Date(notification.created_at);
                const timeAgo = getTimeAgo(createdAt);
                const readClass = notification.is_read ? 'read' : 'unread';
                
                let icon = 'fa-bell';
                if (notification.type === 'like') {
                    icon = 'fa-heart';
                } else if (notification.type === 'comment') {
                    icon = 'fa-comment';
                } else if (notification.type === 'follow') {
                    icon = 'fa-user-plus';
                }
                
                html += `
                    <div class="notification-item ${readClass} ${notification.type}" data-id="${notification.id}">
                        <div class="notification-icon ${notification.type}">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">${escapeHtml(notification.title)}</div>
                            <div class="notification-message">${escapeHtml(notification.message)}</div>
                            <small class="notification-time">${timeAgo}</small>
                        </div>
                        <button class="notification-close" onclick="deleteNotification(${notification.id})" title="Delete">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            notificationList.innerHTML = html;
        }

        function updateBadge(count) {
            if (count > 0) {
                notificationBadge.textContent = count > 9 ? '9+' : count;
                notificationBadge.style.display = 'flex';
            } else {
                notificationBadge.style.display = 'none';
            }
        }

        function markAllAsRead() {
            fetch('notification_ajax.php?action=mark_all_read', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            })
            .catch(error => console.error('Error marking all as read:', error));
        }

        function deleteNotification(notificationId) {
            fetch('notification_ajax.php?action=delete&id=' + notificationId, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            })
            .catch(error => console.error('Error deleting notification:', error));
        }

        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            const intervals = {
                year: 31536000,
                month: 2592000,
                week: 604800,
                day: 86400,
                hour: 3600,
                minute: 60
            };
            
            for (const [unit, secondsInUnit] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / secondsInUnit);
                if (interval >= 1) {
                    return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
                }
            }
            return 'Just now';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
