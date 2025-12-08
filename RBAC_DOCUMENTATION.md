# Role-Based Access Control (RBAC) Implementation

## Overview
This document outlines the super admin role management system implemented to restrict certain administrative functions to super admins only.

## Changes Made

### 1. Database Schema Update
**File:** `migrations/add_super_admin_role.sql`

- Added `is_super_admin` TINYINT(1) column to the `admins` table
- Created index on `is_super_admin` for performance
- The first admin (by creation date) is automatically set as super admin

**SQL Command:**
```sql
ALTER TABLE admins ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0 AFTER password;
CREATE INDEX idx_is_super_admin ON admins(is_super_admin);
UPDATE admins SET is_super_admin = 1 WHERE id = (SELECT id FROM admins ORDER BY created_at ASC LIMIT 1);
```

### 2. Session Management
**File:** `admin/admin_login.php`

- Updated login query to fetch `is_super_admin` field
- Added `$_SESSION['is_super_admin']` to session variables upon successful login
- Super admin status is now tracked for the entire session

### 3. Admin Management Page
**File:** `admin/admin_admins.php`

**Access Control:**
- Added super admin check at page entry
- Non-super admins attempting to access are redirected to dashboard with error message

**Features:**
- Added role display in admin list (Super Admin / Admin badge with crown icon)
- Super admin accounts are protected from deletion
- Only regular admins can be deleted
- Display "Protected" status for super admin accounts
- Super admin sorting (super admins appear first in list)

### 4. Site Settings Page
**File:** `admin/admin_settings.php`

**Access Control:**
- Added super admin check at page entry
- Non-super admins attempting to access are redirected to dashboard
- Only super admins can edit site-wide settings (SMTP, contact info, etc.)

### 5. Navigation Sidebar Updates
**Files:**
- `admin/admin_dashboard.php`
- `admin/admin_users.php`
- `admin/admin_photos.php`
- `admin/admin_comments.php`

**Changes:**
- Admin Management link (Admins) only shows for super admins
- Settings link (Cog icon) only shows for super admins
- Regular admins see limited navigation menu

**Implementation:**
```php
<?php if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin']): ?>
    <a href="admin_admins.php" class="nav-item">
        <i class="fas fa-user-shield"></i>
        <span>Admins</span>
    </a>
<?php endif; ?>
```

## Permissions Matrix

| Feature | Regular Admin | Super Admin |
|---------|--------------|------------|
| View Dashboard | ✅ | ✅ |
| Manage Users | ✅ | ✅ |
| Manage Photos | ✅ | ✅ |
| Manage Comments | ✅ | ✅ |
| View Activity Logs | ✅ | ✅ |
| Manage Admins | ❌ | ✅ |
| Edit Site Settings | ❌ | ✅ |

## User Experience

### For Super Admins
- Full access to all admin features
- Can see "Admins" and "Settings" in navigation
- Can create new admin accounts
- Can delete regular admin accounts
- Cannot delete other super admin accounts
- Can edit all site settings

### For Regular Admins
- Limited to content moderation
- Cannot see "Admins" or "Settings" in navigation
- Redirected with error message if attempting direct access
- Can manage users, photos, and comments
- Can view activity logs

## Security Considerations

1. **Role-based redirection** - Users without super admin role are redirected from protected pages
2. **Protected deletion** - Super admin accounts cannot be deleted
3. **Session tracking** - Super admin status is verified on every protected page
4. **UI Hiding** - Restricted features are not shown in navigation for regular admins
5. **Fallback protection** - Even if someone tries to access URLs directly, they are redirected

## Migration Instructions

1. Run the migration SQL:
   ```bash
   mysql -u username -p database_name < migrations/add_super_admin_role.sql
   ```

2. The first admin will automatically become super admin

3. Log out and log back in for changes to take effect

4. Super admin user will see full navigation with Admins and Settings options

## Future Enhancements

- Add ability for super admin to promote/demote other admins
- Add granular permissions system for more control
- Add admin activity audit trail
- Add admin role modification history
