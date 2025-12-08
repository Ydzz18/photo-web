<?php 
require_once 'rbac.php';
?>
<!-- Admin Sidebar -->
<div class="sidebar">
    <div class="logo">
        <i class="fas fa-camera"></i>
        <span>Lens<strong>Craft</strong></span>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard (All roles) -->
        <?php if (hasPermission('view_dashboard')): ?>
        <a href="admin_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <?php endif; ?>
        
        <!-- Users (Admin, Super Admin) -->
        <?php if (hasPermission('view_users')): ?>
        <a href="admin_users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin_users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <?php endif; ?>
        
        <!-- Admins (Super Admin only) -->
        <?php if (hasPermission('view_admins')): ?>
        <a href="admin_admins.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin_admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i>
            <span>Admins</span>
        </a>
        <?php endif; ?>
        
        <!-- Photos (Admin, Moderator, Super Admin) -->
        <?php if (hasPermission('view_photos')): ?>
        <a href="admin_photos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin_photos.php' ? 'active' : ''; ?>">
            <i class="fas fa-images"></i>
            <span>Photos</span>
        </a>
        <?php endif; ?>
        
        <!-- Comments (Admin, Moderator, Super Admin) -->
        <?php if (hasPermission('view_comments')): ?>
        <a href="admin_comments.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin_comments.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i>
            <span>Comments</span>
        </a>
        <?php endif; ?>
        
        <!-- Activity Logs (Admin, Super Admin) -->
        <?php if (hasPermission('view_logs')): ?>
        <a href="admin_logs.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin_logs.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Activity Logs</span>
        </a>
        <?php endif; ?>
        
        <!-- Settings (Super Admin only) -->
        <?php if (hasPermission('edit_settings')): ?>
        <a href="admin_settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <?php endif; ?>
        
        <!-- View Site (All) -->
        <a href="../home.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>View Site</span>
        </a>
        
        <!-- Logout (All) -->
        <a href="admin_logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>
