<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Include notification manager and settings
require_once __DIR__ . '/notification_manager.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/settings.php';

$notificationManager = new NotificationManager();
$unread_count = $notificationManager->getUnreadCount($_SESSION['user_id']);

$settings = new SiteSettings();
$site_name = $settings->get('site_name', 'LensCraft');
$site_favicon = $settings->get('site_favicon', 'favicon.ico');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; echo htmlspecialchars($site_name); ?></title>
    <link rel="icon" type="image/png" href="assets/img/<?php echo htmlspecialchars($site_favicon); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="assets/css/<?php echo $page_css; ?>">
    <?php endif; ?>
    <?php if (isset($additional_css)): ?>
        <link rel="stylesheet" href="assets/css/<?php echo $additional_css; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="home.php" class="logo"><?php echo htmlspecialchars($site_name); ?></a>
                <div class="nav-links">
                    <a href="home.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'class="active"' : ''; ?>>Gallery</a>
                    <a href="upload.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'upload.php') ? 'class="active"' : ''; ?>>Upload</a>
                    <a href="profile.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'class="active"' : ''; ?>>Profile</a>
                    <a href="notifications.php" class="notifications-link <?php echo (basename($_SERVER['PHP_SELF']) == 'notifications.php') ? 'active' : ''; ?>" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="auth/logout.php">Logout</a>
                </div>
            </nav>
        </div>
    </header>