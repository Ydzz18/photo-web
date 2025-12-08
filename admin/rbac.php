<?php
/**
 * Role-Based Access Control (RBAC) Configuration
 * 
 * This file defines all roles and their permissions
 */

// Define available roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_MODERATOR', 'moderator');

// Role descriptions
$ROLE_DESCRIPTIONS = [
    'super_admin' => 'Super Administrator - Full system access',
    'admin' => 'Administrator - Content moderation and user management',
    'moderator' => 'Moderator - Content moderation only',
];

// Define permissions for each role
$ROLE_PERMISSIONS = [
    'super_admin' => [
        // Dashboard
        'view_dashboard' => true,
        
        // User Management
        'view_users' => true,
        'manage_users' => true,
        'delete_users' => true,
        
        // Admin Management
        'view_admins' => true,
        'manage_admins' => true,
        'create_admins' => true,
        'delete_admins' => true,
        'promote_admins' => true,
        
        // Photo Management
        'view_photos' => true,
        'delete_photos' => true,
        'moderate_photos' => true,
        
        // Comment Management
        'view_comments' => true,
        'delete_comments' => true,
        'moderate_comments' => true,
        
        // Logs & Audit
        'view_logs' => true,
        'export_logs' => true,
        
        // Site Settings
        'view_settings' => true,
        'edit_settings' => true,
        'manage_site_config' => true,
    ],
    
    'admin' => [
        // Dashboard
        'view_dashboard' => true,
        
        // User Management
        'view_users' => true,
        'manage_users' => true,
        'delete_users' => true,
        
        // Admin Management
        'view_admins' => false,
        'manage_admins' => false,
        'create_admins' => false,
        'delete_admins' => false,
        'promote_admins' => false,
        
        // Photo Management
        'view_photos' => true,
        'delete_photos' => true,
        'moderate_photos' => true,
        
        // Comment Management
        'view_comments' => true,
        'delete_comments' => true,
        'moderate_comments' => true,
        
        // Logs & Audit
        'view_logs' => true,
        'export_logs' => false,
        
        // Site Settings
        'view_settings' => false,
        'edit_settings' => false,
        'manage_site_config' => false,
    ],
    
    'moderator' => [
        // Dashboard
        'view_dashboard' => true,
        
        // User Management
        'view_users' => false,
        'manage_users' => false,
        'delete_users' => false,
        
        // Admin Management
        'view_admins' => false,
        'manage_admins' => false,
        'create_admins' => false,
        'delete_admins' => false,
        'promote_admins' => false,
        
        // Photo Management
        'view_photos' => true,
        'delete_photos' => true,
        'moderate_photos' => true,
        
        // Comment Management
        'view_comments' => true,
        'delete_comments' => true,
        'moderate_comments' => true,
        
        // Logs & Audit
        'view_logs' => false,
        'export_logs' => false,
        
        // Site Settings
        'view_settings' => false,
        'edit_settings' => false,
        'manage_site_config' => false,
    ],
];

/**
 * Check if current user has a specific permission
 * 
 * @param string $permission Permission to check
 * @param string|null $role Role to check against (uses session role if null)
 * @return bool
 */
function hasPermission($permission, $role = null) {
    global $ROLE_PERMISSIONS;
    
    if ($role === null) {
        // Use current admin's role from session
        $role = getAdminRole();
    }
    
    if (!isset($ROLE_PERMISSIONS[$role])) {
        return false;
    }
    
    return isset($ROLE_PERMISSIONS[$role][$permission]) && $ROLE_PERMISSIONS[$role][$permission] === true;
}

/**
 * Get current admin's role
 * 
 * @return string Admin role
 */
function getAdminRole() {
    if (isset($_SESSION['admin_role'])) {
        return $_SESSION['admin_role'];
    }
    // Default to admin role if not set
    return 'admin';
}

/**
 * Check if current user is super admin
 * 
 * @return bool
 */
function isSuperAdmin() {
    return getAdminRole() === ROLE_SUPER_ADMIN;
}

/**
 * Require permission - redirect if user doesn't have it
 * 
 * @param string $permission Permission required
 * @param string $redirectTo Page to redirect to on failure
 */
function requirePermission($permission, $redirectTo = 'admin_dashboard.php') {
    if (!hasPermission($permission)) {
        $_SESSION['error'] = "You do not have permission to access this page.";
        header("Location: {$redirectTo}");
        exit();
    }
}

/**
 * Get all roles
 * 
 * @return array List of available roles
 */
function getAllRoles() {
    global $ROLE_DESCRIPTIONS;
    return array_keys($ROLE_DESCRIPTIONS);
}

/**
 * Get role description
 * 
 * @param string $role Role name
 * @return string Role description
 */
function getRoleDescription($role) {
    global $ROLE_DESCRIPTIONS;
    return $ROLE_DESCRIPTIONS[$role] ?? 'Unknown Role';
}
?>
