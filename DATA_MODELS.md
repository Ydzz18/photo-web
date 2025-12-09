# LensCraft Data Models

Complete database schema and data model documentation.

---

## Table of Contents

1. [Database Overview](#database-overview)
2. [Users & Authentication](#users--authentication)
3. [Photo Management](#photo-management)
4. [Social Features](#social-features)
5. [Notifications](#notifications)
6. [Email & Security](#email--security)
7. [Admin & Logging](#admin--logging)
8. [Contact Management](#contact-management)
9. [Relationships](#relationships)
10. [Indexes & Performance](#indexes--performance)
11. [Query Examples](#query-examples)

---

## Database Overview

### Database Information

- **Database Name:** `photography_website`
- **Engine:** MySQL 10.4 (InnoDB) / MariaDB
- **Charset:** UTF-8 MB4 (supports emojis)
- **Collation:** utf8mb4_general_ci

### Table Summary

| Table | Purpose | Records |
|-------|---------|---------|
| `users` | User accounts & profiles | Growing |
| `photos` | Uploaded photos | Growing |
| `likes` | Photo likes | Growing |
| `comments` | Photo comments | Growing |
| `follows` | User relationships | Growing |
| `notifications` | User notifications | Growing |
| `email_confirmations` | Email verification tokens | Temporary |
| `password_resets` | Password reset tokens | Temporary |
| `two_factor_auth` | 2FA codes | Temporary |
| `admins` | Administrator accounts | Static |
| `user_logs` | Activity logging | Growing |
| `contact_messages` | Contact form submissions | Growing |

---

## Users & Authentication

### `users` Table

Primary table for user accounts and profile information.

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Account Credentials
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    
    -- Profile Information
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    birthday DATE,
    address VARCHAR(255),
    location VARCHAR(100),
    bio TEXT,
    
    -- Profile Media
    profile_pic VARCHAR(255),
    profile_picture VARCHAR(255),
    cover_photo VARCHAR(255),
    
    -- Social Links
    instagram VARCHAR(100),
    twitter VARCHAR(100),
    facebook VARCHAR(100),
    website VARCHAR(255),
    
    -- Statistics
    followers_count INT DEFAULT 0,
    following_count INT DEFAULT 0,
    photos_count INT DEFAULT 0,
    
    -- Privacy Settings
    is_profile_public TINYINT(1) DEFAULT 1,
    show_email TINYINT(1) DEFAULT 0,
    
    -- Security
    email_verified TINYINT(1) DEFAULT 0,
    two_fa_enabled TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Column Definitions

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | INT | No | Unique user identifier |
| `username` | VARCHAR(50) | No | Unique username for login |
| `email` | VARCHAR(100) | No | Unique email address |
| `password` | VARCHAR(255) | No | Bcrypt-hashed password |
| `first_name` | VARCHAR(50) | Yes | User's first name |
| `last_name` | VARCHAR(50) | Yes | User's last name |
| `phone` | VARCHAR(20) | Yes | Contact phone number |
| `birthday` | DATE | Yes | Date of birth |
| `address` | VARCHAR(255) | Yes | Street address |
| `location` | VARCHAR(100) | Yes | City/location |
| `bio` | TEXT | Yes | Profile biography |
| `profile_pic` | VARCHAR(255) | Yes | Profile picture filename |
| `cover_photo` | VARCHAR(255) | Yes | Cover photo filename |
| `instagram` | VARCHAR(100) | Yes | Instagram handle |
| `twitter` | VARCHAR(100) | Yes | Twitter handle |
| `facebook` | VARCHAR(100) | Yes | Facebook handle |
| `website` | VARCHAR(255) | Yes | Personal website URL |
| `followers_count` | INT | No | Count of followers (cached) |
| `following_count` | INT | No | Count of users following |
| `photos_count` | INT | No | Count of uploaded photos |
| `is_profile_public` | TINYINT(1) | No | Public/private profile (0=private, 1=public) |
| `show_email` | TINYINT(1) | No | Email visibility (0=hidden, 1=public) |
| `email_verified` | TINYINT(1) | No | Email verification status |
| `two_fa_enabled` | TINYINT(1) | No | 2FA activation status |
| `is_admin` | TINYINT(1) | No | Admin flag (0=user, 1=admin) |
| `created_at` | DATETIME | No | Account creation timestamp |
| `updated_at` | TIMESTAMP | No | Last update timestamp |

### Sample Record

```json
{
    "id": 5,
    "username": "jane_photographer",
    "email": "jane@example.com",
    "password": "$2y$10$...",
    "first_name": "Jane",
    "last_name": "Doe",
    "phone": "+1-555-123-4567",
    "birthday": "1995-06-15",
    "address": "123 Main St, New York, NY 10001",
    "location": "New York",
    "bio": "Professional photographer specializing in landscapes",
    "profile_pic": "profile_5_1704567890.jpg",
    "cover_photo": "cover_5_1704567891.jpg",
    "instagram": "jane_photography",
    "twitter": "jane_photo",
    "facebook": "jane.doe.photography",
    "website": "https://janephotography.com",
    "followers_count": 342,
    "following_count": 127,
    "photos_count": 45,
    "is_profile_public": 1,
    "show_email": 0,
    "email_verified": 1,
    "two_fa_enabled": 1,
    "is_admin": 0,
    "created_at": "2024-11-15 10:30:00",
    "updated_at": "2024-12-08 15:45:00"
}
```

---

## Photo Management

### `photos` Table

Contains all uploaded photos and metadata.

```sql
CREATE TABLE photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Photo Information
    title VARCHAR(100) NOT NULL,
    description TEXT,
    
    -- File Information
    image_path VARCHAR(255) NOT NULL,
    
    -- Timestamp
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | INT | No | Unique photo identifier |
| `user_id` | INT | No | Owner user ID (FK) |
| `title` | VARCHAR(100) | No | Photo title |
| `description` | TEXT | Yes | Photo description |
| `image_path` | VARCHAR(255) | No | Relative path to image file |
| `uploaded_at` | DATETIME | No | Upload timestamp |

### Sample Record

```json
{
    "id": 42,
    "user_id": 5,
    "title": "Sunset over the Hudson",
    "description": "Golden hour at sunset captured from Hudson River Park",
    "image_path": "6936bd79a0035_1765195129.jpg",
    "uploaded_at": "2024-12-08 19:58:49"
}
```

### Image File Naming

**Format:** `{random_hash}_{timestamp}.{extension}`

**Example:** `6936bd79a0035_1765195129.jpg`

**Generation:**
```php
$hash = substr(md5(uniqid(rand(), true)), 0, 15);
$timestamp = time();
$extension = pathinfo($filename, PATHINFO_EXTENSION);
$new_filename = "{$hash}_{$timestamp}.{$extension}";
```

### Supported Formats

- JPG/JPEG (most common)
- PNG (transparency support)
- GIF (animation support)
- WebP (modern format)

---

## Social Features

### `likes` Table

Tracks which users have liked which photos.

```sql
CREATE TABLE likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    photo_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_like (user_id, photo_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Unique like identifier |
| `user_id` | INT | User who liked |
| `photo_id` | INT | Photo that was liked |
| `created_at` | DATETIME | When like was created |

**Constraints:**
- Unique constraint on `(user_id, photo_id)` - prevents duplicate likes
- Foreign keys ensure referential integrity

### Sample Record

```json
{
    "id": 25,
    "user_id": 7,
    "photo_id": 42,
    "created_at": "2024-12-09 14:30:00"
}
```

---

### `comments` Table

Stores comments left on photos.

```sql
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Unique comment identifier |
| `photo_id` | INT | Photo being commented on |
| `user_id` | INT | Comment author |
| `comment_text` | TEXT | Comment content (max 500 chars) |
| `created_at` | DATETIME | Comment creation time |

### Sample Record

```json
{
    "id": 47,
    "photo_id": 42,
    "user_id": 3,
    "comment_text": "Absolutely stunning capture! The colors are incredible.",
    "created_at": "2024-12-09 15:20:00"
}
```

---

### `follows` Table

Represents follower/following relationships.

```sql
CREATE TABLE follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_follow (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Unique follow relationship ID |
| `follower_id` | INT | User doing the following |
| `following_id` | INT | User being followed |
| `created_at` | TIMESTAMP | When relationship started |

**Constraints:**
- Unique constraint prevents duplicate follows
- Foreign keys with CASCADE delete maintain integrity

### Sample Record

```json
{
    "id": 12,
    "follower_id": 7,
    "following_id": 5,
    "created_at": "2024-12-08 12:42:58"
}
```

---

## Notifications

### `notifications` Table

Tracks user activity notifications (likes, comments, follows).

```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Notification Type
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    -- Related Entities
    related_user_id INT,
    related_photo_id INT,
    related_comment_id INT,
    
    -- Status
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (related_photo_id) REFERENCES photos(id) ON DELETE CASCADE,
    FOREIGN KEY (related_comment_id) REFERENCES comments(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Notification ID |
| `user_id` | INT | Recipient user |
| `type` | VARCHAR(50) | Type: `like`, `comment`, `follow` |
| `title` | VARCHAR(255) | Notification title |
| `message` | TEXT | Notification message |
| `related_user_id` | INT | Who triggered (likes, follows, comments) |
| `related_photo_id` | INT | Related photo (likes, comments) |
| `related_comment_id` | INT | Related comment (comment notifications) |
| `is_read` | TINYINT(1) | Read status (0=unread, 1=read) |
| `created_at` | TIMESTAMP | Creation time |

### Notification Types

**Like Notification:**
```json
{
    "type": "like",
    "title": "New Like on Your Photo",
    "message": "john_doe liked your photo \"Sunset Beach\"",
    "related_user_id": 3,
    "related_photo_id": 42
}
```

**Comment Notification:**
```json
{
    "type": "comment",
    "title": "New Comment on Your Photo",
    "message": "jane_smith commented on your photo \"Mountain Peak\"",
    "related_user_id": 2,
    "related_photo_id": 38,
    "related_comment_id": 15
}
```

**Follow Notification:**
```json
{
    "type": "follow",
    "title": "New Follower",
    "message": "alex_photographer started following you",
    "related_user_id": 4
}
```

---

## Email & Security

### `email_confirmations` Table

Manages email verification tokens and status.

```sql
CREATE TABLE email_confirmations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    confirmed_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Record ID |
| `user_id` | INT | User being verified |
| `token` | VARCHAR(255) | SHA-256 hashed token |
| `created_at` | TIMESTAMP | When token was generated |
| `expires_at` | TIMESTAMP | Expiration time (24 hours) |
| `confirmed_at` | TIMESTAMP | When email was confirmed |

### Token Lifecycle

```
Registration
    ↓
Generate token → Hash (SHA-256) → Store in DB
    ↓
Send via email (plain token)
    ↓
User clicks link (24 hours)
    ↓
Verify: Hash input token, compare with DB
    ↓
If match: Set confirmed_at → Delete token
```

### Sample Record

```json
{
    "id": 15,
    "user_id": 11,
    "token": "645be11d185714a05a22d2695cdd2cc7f2032afd6e636760441e6516a3b34109",
    "created_at": "2024-12-09 07:50:33",
    "expires_at": "2024-12-10 07:50:33",
    "confirmed_at": "2024-12-09 07:51:54"
}
```

---

### `password_resets` Table

Manages password reset tokens.

```sql
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    used_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Record ID |
| `user_id` | INT | User resetting password |
| `token` | VARCHAR(255) | Reset token (hashed) |
| `created_at` | TIMESTAMP | When generated |
| `expires_at` | TIMESTAMP | Expiration (24 hours) |
| `used_at` | TIMESTAMP | When actually used |

---

### `two_factor_auth` Table

Stores 2FA codes for authentication.

```sql
CREATE TABLE two_factor_auth (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    code VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Record ID |
| `user_id` | INT | User logging in |
| `code` | VARCHAR(255) | SHA-256 hashed 6-digit code |
| `created_at` | TIMESTAMP | When code was generated |

**Expiration:** 10 minutes (checked in application logic)

### Code Generation

```
Generate: random_int(0, 999999)
Pad: str_pad(code, 6, '0', STR_PAD_LEFT)  // "123456"
Hash: hash('sha256', '123456')  // "a665a45920422f9d417e..."
Store: Hash in database
Send: Plain code to user via email
Verify: Hash input, compare with DB
```

---

## Admin & Logging

### `admins` Table

Administrator account management.

```sql
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_super_admin TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);
```

### Sample Record

```json
{
    "id": 1,
    "username": "admin",
    "email": "admin@lenscraft.com",
    "password": "$2y$10$...",
    "is_super_admin": 1,
    "created_at": "2024-11-27 11:29:49",
    "last_login": "2024-12-09 15:48:55"
}
```

---

### `user_logs` Table

Complete audit trail of user and admin actions.

```sql
CREATE TABLE user_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    admin_id INT,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    affected_table VARCHAR(50),
    affected_id INT,
    status ENUM('success', 'failed', 'warning') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `user_id` | INT | User performing action (NULL for admin actions) |
| `admin_id` | INT | Admin performing action (NULL for user actions) |
| `action_type` | VARCHAR(50) | Type of action (register, login, upload, etc) |
| `action_description` | TEXT | Detailed description |
| `ip_address` | VARCHAR(45) | IPv4 or IPv6 address |
| `user_agent` | TEXT | Browser user agent |
| `affected_table` | VARCHAR(50) | Database table affected |
| `affected_id` | INT | ID of affected record |
| `status` | ENUM | success/failed/warning |
| `created_at` | TIMESTAMP | When action occurred |

### Common Action Types

- `register` - User registration
- `login` - User login
- `logout` - User logout
- `email_verified` - Email confirmation
- `password_reset` - Password changed
- `photo_upload` - Photo uploaded
- `photo_delete` - Photo deleted
- `like_photo` - Photo liked
- `unlike_photo` - Photo unliked
- `comment_photo` - Comment posted
- `delete_comment` - Comment deleted
- `follow_user` - User followed
- `unfollow_user` - User unfollowed
- `admin_login` - Admin logged in
- `admin_delete_comment` - Admin deleted comment
- `admin_delete_photo` - Admin deleted photo
- `admin_delete_user` - Admin deleted user

### Sample Record

```json
{
    "id": 156,
    "user_id": 11,
    "admin_id": null,
    "action_type": "photo_upload",
    "action_description": "User uploaded photo: Sunset over the Hudson",
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) ...",
    "affected_table": "photos",
    "affected_id": 42,
    "status": "success",
    "created_at": "2024-12-09 19:58:49"
}
```

---

## Contact Management

### `contact_messages` Table

Stores contact form submissions from users.

```sql
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Column Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Message ID |
| `name` | VARCHAR(100) | Sender's name |
| `email` | VARCHAR(100) | Sender's email |
| `phone` | VARCHAR(20) | Sender's phone (optional) |
| `message` | TEXT | Message content |
| `ip_address` | VARCHAR(45) | Sender's IP address |
| `is_read` | TINYINT(1) | Admin read status |
| `created_at` | TIMESTAMP | Submission time |

### Sample Record

```json
{
    "id": 8,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1-555-123-4567",
    "message": "I'm interested in licensing some of your photos for our publication.",
    "ip_address": "203.0.113.45",
    "is_read": 0,
    "created_at": "2024-12-09 20:15:30"
}
```

---

## Relationships

### Entity Relationship Diagram

```
                    ┌─────────────┐
                    │    users    │
                    └──────┬──────┘
                           │
            ┌──────────────┼──────────────┬─────────────┐
            │              │              │             │
            ▼              ▼              ▼             ▼
        ┌───────────┐  ┌──────────┐  ┌────────┐  ┌──────────┐
        │  photos   │  │ follows  │  │  likes │  │ comments │
        └─────┬─────┘  └──────────┘  └───┬────┘  └────┬─────┘
              │                           │           │
              │                           │           │
        ┌─────▼──────────────────────────┘           │
        │         notifications ◄────────────────────┘
        │
        └─► email_confirmations
        │
        └─► password_resets
        │
        └─► two_factor_auth

    admins ──► user_logs ◄── users
               contact_messages (external)
```

### Relationship Types

**One-to-Many:**
- User → Photos (user has many photos)
- User → Comments (user has many comments)
- User → Likes (user has many likes)
- Photo → Comments (photo has many comments)
- Photo → Likes (photo has many likes)

**Many-to-Many:**
- Users → Users (follows relationship)
  - Via `follows` table

**One-to-One:**
- User → Profile info (email_verified, two_fa_enabled)
- User → Email verification (current active token)

---

## Indexes & Performance

### Recommended Indexes

```sql
-- Primary Keys (auto-created)
-- INDEX on photos
ALTER TABLE photos ADD INDEX idx_user_id (user_id);
ALTER TABLE photos ADD INDEX idx_uploaded_at (uploaded_at DESC);

-- Likes table
ALTER TABLE likes ADD INDEX idx_user_id (user_id);
ALTER TABLE likes ADD INDEX idx_photo_id (photo_id);

-- Comments table
ALTER TABLE comments ADD INDEX idx_photo_id (photo_id);
ALTER TABLE comments ADD INDEX idx_user_id (user_id);

-- Follows table
ALTER TABLE follows ADD INDEX idx_follower_id (follower_id);
ALTER TABLE follows ADD INDEX idx_following_id (following_id);

-- Users table
ALTER TABLE users ADD INDEX idx_username (username);
ALTER TABLE users ADD INDEX idx_email (email);

-- Notifications
ALTER TABLE notifications ADD INDEX idx_user_id (user_id);
ALTER TABLE notifications ADD INDEX idx_is_read (is_read);
ALTER TABLE notifications ADD INDEX idx_created_at (created_at DESC);

-- Logs
ALTER TABLE user_logs ADD INDEX idx_user_id (user_id);
ALTER TABLE user_logs ADD INDEX idx_created_at (created_at DESC);

-- Email verification
ALTER TABLE email_confirmations ADD INDEX idx_user_id (user_id);
ALTER TABLE email_confirmations ADD INDEX idx_token (token);

-- 2FA
ALTER TABLE two_factor_auth ADD INDEX idx_user_id (user_id);

-- Contact
ALTER TABLE contact_messages ADD INDEX idx_created_at (created_at DESC);
ALTER TABLE contact_messages ADD INDEX idx_is_read (is_read);
```

### Query Optimization Examples

**Get photos with engagement counts:**
```sql
SELECT 
    p.id, p.title, p.description, p.image_path,
    u.username, u.profile_pic,
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
ORDER BY p.uploaded_at DESC
LIMIT 12;
```

**Get user with follow stats:**
```sql
SELECT 
    u.id, u.username, u.bio, u.profile_pic,
    COUNT(DISTINCT f1.id) as follower_count,
    COUNT(DISTINCT f2.id) as following_count,
    COUNT(DISTINCT p.id) as photo_count
FROM users u
LEFT JOIN follows f1 ON u.id = f1.following_id
LEFT JOIN follows f2 ON u.id = f2.follower_id
LEFT JOIN photos p ON u.id = p.user_id
WHERE u.id = ?
GROUP BY u.id;
```

**Get recent unread notifications:**
```sql
SELECT * FROM notifications
WHERE user_id = ? AND is_read = 0
ORDER BY created_at DESC
LIMIT 10;
```

---

## Query Examples

### User Queries

**Get user profile:**
```php
$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE id = ?"
);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

**Get user photos:**
```php
$stmt = $pdo->prepare(
    "SELECT * FROM photos WHERE user_id = ? ORDER BY uploaded_at DESC"
);
$stmt->execute([$user_id]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Check if following:**
```php
$stmt = $pdo->prepare(
    "SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1"
);
$stmt->execute([$follower_id, $following_id]);
$is_following = $stmt->rowCount() > 0;
```

### Photo Queries

**Get photo with metadata:**
```php
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        u.username, u.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE photo_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE photo_id = p.id) as comment_count
    FROM photos p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$photo_id]);
$photo = $stmt->fetch(PDO::FETCH_ASSOC);
```

**Get comments on photo:**
```php
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.username, u.profile_pic
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.photo_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$photo_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Notification Queries

**Get unread count:**
```php
$stmt = $pdo->prepare(
    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0"
);
$stmt->execute([$user_id]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
```

**Get recent notifications:**
```php
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ?
");
$stmt->execute([$user_id, $limit]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

---

## Data Integrity

### Constraints

- **PRIMARY KEY** - Unique identifier for each table
- **FOREIGN KEY** - Referential integrity
- **UNIQUE KEY** - Prevent duplicates (username, email, follows)
- **NOT NULL** - Required fields
- **DEFAULT** - Automatic values
- **ON DELETE CASCADE** - Cascading deletes for orphaned records

### Data Validation (Application Level)

```php
// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception("Invalid email format");
}

// Username validation
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    throw new Exception("Invalid username format");
}

// Comment length
if (strlen($comment) > 500) {
    throw new Exception("Comment too long");
}
```

---

## Backup & Recovery

### Backup Commands

```bash
# Backup entire database
mysqldump -u root -p photography_website > backup_2024-12-09.sql

# Backup specific table
mysqldump -u root -p photography_website users > users_backup.sql

# Backup with all tables
mysqldump -u root -p --all-databases > full_backup.sql
```

### Recovery Commands

```bash
# Restore database
mysql -u root -p photography_website < backup_2024-12-09.sql

# Restore specific table
mysql -u root -p photography_website < users_backup.sql
```

---

**Last Updated:** December 2024
**Database Version:** 1.0
**Schema Version:** 3 (with 2FA support)
