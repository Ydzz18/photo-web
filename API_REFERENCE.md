# LensCraft API Reference

Complete API endpoint documentation for developers.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Error Handling](#error-handling)
5. [AJAX Endpoints](#ajax-endpoints)
6. [Form Endpoints](#form-endpoints)
7. [Rate Limiting](#rate-limiting)
8. [Best Practices](#best-practices)

---

## Overview

**Base URL:** `http://localhost/photo-web/`

**API Type:** AJAX/Form-based (JSON responses)

**Authentication:** Session-based (PHP $_SESSION)

**Content-Type:** 
- Request: `application/x-www-form-urlencoded` or `multipart/form-data`
- Response: `application/json`

---

## Authentication

### Session Authentication

All protected endpoints require an active PHP session with `$_SESSION['user_id']`.

**Authentication Flow:**
1. User logs in via `/auth/login.php`
2. Session is created with user data
3. Include `<?php session_start(); ?>` on protected pages
4. Check `if (!isset($_SESSION['user_id']))` to verify authentication

**Example:**
```php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
```

### Session Variables

| Variable | Type | Description |
|----------|------|-------------|
| `user_id` | int | Authenticated user ID |
| `username` | string | User's username |
| `email` | string | User's email |
| `pending_2fa_user_id` | int | User ID awaiting 2FA verification |
| `pending_2fa_email` | string | Email for 2FA verification |
| `admin_id` | int | Admin user ID (admins only) |

---

## Response Format

### Standard Success Response

```json
{
    "success": true,
    "message": "Operation completed",
    "data": {
        "id": 123,
        "count": 5,
        "timestamp": "2024-12-09T15:30:00Z"
    }
}
```

### Standard Error Response

```json
{
    "success": false,
    "message": "Error description",
    "errors": [
        "Field validation error",
        "Another error"
    ],
    "code": "ERROR_CODE"
}
```

### HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK | Successful GET/POST |
| 201 | Created | New resource created |
| 400 | Bad Request | Invalid input data |
| 401 | Unauthorized | Not logged in |
| 403 | Forbidden | No permission |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Server Error | Database/server error |

---

## Error Handling

### Common Error Responses

**Not Authenticated:**
```json
{
    "success": false,
    "message": "Please log in to perform this action.",
    "code": "NOT_AUTHENTICATED"
}
```

**Validation Error:**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": [
        "Email is required",
        "Email must be valid"
    ],
    "code": "VALIDATION_ERROR"
}
```

**Resource Not Found:**
```json
{
    "success": false,
    "message": "Photo not found.",
    "code": "NOT_FOUND"
}
```

**Database Error:**
```json
{
    "success": false,
    "message": "Failed to process request. Please try again.",
    "code": "DATABASE_ERROR"
}
```

---

## AJAX Endpoints

### 1. Like/Unlike Photo

**Endpoint:** `/like_ajax.php`

**Method:** `GET`

**Authentication:** Required

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Photo ID |

**Request Example:**
```javascript
fetch('/like_ajax.php?id=123')
    .then(res => res.json())
    .then(data => console.log(data));
```

**Success Response (202 OK):**
```json
{
    "success": true,
    "message": "Photo liked!",
    "liked": true,
    "like_count": 5
}
```

**Unlike Response:**
```json
{
    "success": true,
    "message": "Photo unliked.",
    "liked": false,
    "like_count": 4
}
```

**Error Response (401 Unauthorized):**
```json
{
    "success": false,
    "message": "Please log in to like photos."
}
```

**Error Response (404 Not Found):**
```json
{
    "success": false,
    "message": "Photo not found."
}
```

**Side Effects:**
- Creates/deletes entry in `likes` table
- Triggers notification if photo owner is different
- Updates DOM with new like count (client-side)

---

### 2. Post Comment

**Endpoint:** `/comment_ajax.php`

**Method:** `POST`

**Authentication:** Required

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `photo_id` | int | Yes | Photo ID |
| `comment` | string | Yes | Comment text (max 500 chars) |

**Request Example:**
```javascript
const formData = new FormData();
formData.append('photo_id', 123);
formData.append('comment', 'Great photo!');

fetch('/comment_ajax.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => console.log(data));
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Comment posted successfully!",
    "comment": "Great photo!",
    "username": "johndoe",
    "comment_count": 3
}
```

**Error Response - Not Logged In:**
```json
{
    "success": false,
    "message": "Please log in to comment on photos."
}
```

**Error Response - Invalid Input:**
```json
{
    "success": false,
    "message": "Comment cannot be empty."
}
```

**Error Response - Too Long:**
```json
{
    "success": false,
    "message": "Comment is too long (maximum 500 characters)."
}
```

**Error Response - Photo Not Found:**
```json
{
    "success": false,
    "message": "Photo not found."
}
```

**Side Effects:**
- Inserts comment into `comments` table
- Triggers notification if not own photo
- Sanitizes input with `htmlspecialchars()`
- Logs action in `user_logs` table

---

### 3. Notification Management

**Endpoint:** `/notification_ajax.php`

**Method:** `GET` (AJAX header required)

**Authentication:** Required

**Header Required:**
```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest'
}
```

#### 3.1 Get Unread Count

**Parameters:**
```
action=get_count
```

**Request:**
```javascript
fetch('/notification_ajax.php?action=get_count', {
    headers: {'X-Requested-With': 'XMLHttpRequest'}
})
.then(res => res.json())
.then(data => console.log(data));
```

**Response:**
```json
{
    "success": true,
    "count": 3
}
```

---

#### 3.2 Get Notifications

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `action` | string | - | `get_notifications` |
| `limit` | int | 10 | Max results to return |
| `unread_only` | bool | false | Only unread notifications |

**Request:**
```javascript
fetch('/notification_ajax.php?action=get_notifications&limit=10&unread_only=false', {
    headers: {'X-Requested-With': 'XMLHttpRequest'}
})
.then(res => res.json())
.then(data => console.log(data));
```

**Response:**
```json
{
    "success": true,
    "notifications": [
        {
            "id": 1,
            "user_id": 5,
            "type": "like",
            "title": "New Like on Your Photo",
            "message": "john_doe liked your photo \"Sunset Beach\"",
            "related_user_id": 3,
            "related_photo_id": 42,
            "is_read": 0,
            "created_at": "2024-12-09 15:30:00"
        },
        {
            "id": 2,
            "user_id": 5,
            "type": "comment",
            "title": "New Comment on Your Photo",
            "message": "jane_smith commented on your photo \"Mountain Peak\"",
            "related_user_id": 2,
            "related_photo_id": 38,
            "related_comment_id": 15,
            "is_read": 1,
            "created_at": "2024-12-09 14:20:00"
        }
    ],
    "count": 2
}
```

**Notification Types:**
- `like` - Someone liked your photo
- `comment` - Someone commented on your photo
- `follow` - Someone started following you
- `contact` - Contact form submission

---

#### 3.3 Mark as Read

**Parameters:**
```
action=mark_read&id=NOTIFICATION_ID
```

**Request:**
```javascript
fetch('/notification_ajax.php?action=mark_read&id=1', {
    headers: {'X-Requested-With': 'XMLHttpRequest'}
})
.then(res => res.json())
.then(data => console.log(data));
```

**Response:**
```json
{
    "success": true,
    "message": "Notification marked as read"
}
```

---

#### 3.4 Mark All as Read

**Parameters:**
```
action=mark_all_read
```

**Request:**
```javascript
fetch('/notification_ajax.php?action=mark_all_read', {
    headers: {'X-Requested-With': 'XMLHttpRequest'}
})
.then(res => res.json())
.then(data => console.log(data));
```

**Response:**
```json
{
    "success": true,
    "message": "All notifications marked as read"
}
```

---

#### 3.5 Delete Notification

**Parameters:**
```
action=delete&id=NOTIFICATION_ID
```

**Request:**
```javascript
fetch('/notification_ajax.php?action=delete&id=1', {
    headers: {'X-Requested-With': 'XMLHttpRequest'}
})
.then(res => res.json())
.then(data => console.log(data));
```

**Response:**
```json
{
    "success": true,
    "message": "Notification deleted"
}
```

---

#### 3.6 Get Statistics

**Parameters:**
```
action=get_stats
```

**Request:**
```javascript
fetch('/notification_ajax.php?action=get_stats', {
    headers: {'X-Requested-With': 'XMLHttpRequest'}
})
.then(res => res.json())
.then(data => console.log(data));
```

**Response:**
```json
{
    "success": true,
    "stats": {
        "total": 10,
        "unread": 3,
        "by_type": {
            "like": 5,
            "comment": 3,
            "follow": 2,
            "contact": 0
        }
    }
}
```

---

### 4. Follow/Unfollow User

**Endpoint:** `/api/follow_ajax.php`

**Method:** `POST`

**Authentication:** Required

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | int | Yes | User ID to follow/unfollow |
| `action` | string | Yes | `follow` or `unfollow` |

**Request Example:**
```javascript
const formData = new FormData();
formData.append('user_id', 42);
formData.append('action', 'follow');

fetch('/api/follow_ajax.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => console.log(data));
```

**Success Response - Follow:**
```json
{
    "success": true
}
```

**Success Response - Unfollow:**
```json
{
    "success": true
}
```

**Error - Not Logged In:**
```json
{
    "success": false,
    "message": "Not logged in"
}
```

**Error - Missing Parameters:**
```json
{
    "success": false,
    "message": "Missing parameters"
}
```

**Error - Self-Follow:**
```json
{
    "success": false,
    "message": "Cannot follow yourself"
}
```

**Error - Invalid Action:**
```json
{
    "success": false,
    "message": "Invalid action"
}
```

**Side Effects:**
- Creates/deletes entry in `follows` table
- Updates `followers_count` on followed user
- Updates `following_count` on follower
- Triggers notification on new follow
- Logs action in `user_logs` table

---

## Form Endpoints

### 1. User Registration

**Endpoint:** `/auth/register.php`

**Method:** `POST`

**Authentication:** Not required

**Form Parameters - Step 1:**

| Parameter | Type | Required | Validation |
|-----------|------|----------|-----------|
| `first_name` | string | Yes | Max 50 chars |
| `last_name` | string | Yes | Max 50 chars |
| `phone` | string | No | Max 20 chars |
| `birthday` | date | No | Valid date |
| `address` | string | No | Max 255 chars |

**Form Parameters - Step 2:**

| Parameter | Type | Required | Validation |
|-----------|------|----------|-----------|
| `username` | string | Yes | Unique, alphanumeric, 3-50 chars |
| `email` | string | Yes | Unique, valid email |
| `password` | string | Yes | Min 6 chars, strong |
| `password_confirm` | string | Yes | Must match password |
| `agree_terms` | checkbox | Yes | Must be checked |

**Success Response:**
- Redirects to `/auth/login.php`
- Shows: "Registration successful! Check your email to verify your account."
- Sends verification email

**Error Response:**
- Redirects back to register form
- Sets `$_SESSION['errors']` array
- Common errors:
  - "Username already exists"
  - "Email already in use"
  - "Username must be 3-50 characters"
  - "Password is too weak"
  - "Passwords do not match"

---

### 2. User Login

**Endpoint:** `/auth/login.php`

**Method:** `POST`

**Authentication:** Not required

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email_or_username` | string | Yes | Email or username |
| `password` | string | Yes | Account password |

**Success Response (No 2FA):**
- Creates session with `user_id`
- Redirects to `/home.php`
- Sets `$_SESSION['user_id']`, `username`, `email`

**Success Response (With 2FA):**
- Sets `$_SESSION['pending_2fa_user_id']`
- Sends 6-digit code to email
- Redirects to `/verify-2fa.php`

**Error Response:**
- Redirects to login page
- Shows: "Invalid email/username or password"

**Error Response - Email Not Verified:**
- Shows: "Please verify your email before logging in"
- Shows link to resend verification email

---

### 3. Photo Upload

**Endpoint:** `/upload.php`

**Method:** `POST`

**Authentication:** Required

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | Photo title (max 100 chars) |
| `description` | string | No | Description (max 500 chars) |
| `photo` | file | Yes* | Image file or canvas data |
| `edited_photo_data` | string | No* | Base64 canvas data |

*Either `photo` or `edited_photo_data` required

**Supported Formats:**
- JPG, JPEG, PNG, GIF, WebP
- Max file size: Configurable (default 50MB)

**Success Response:**
- Inserts photo into `photos` table
- Saves file to `/uploads/`
- Updates user's `photos_count`
- Redirects to `/home.php`
- Shows: "Photo uploaded successfully!"

**Error Responses:**
- "Photo title is required"
- "Photo title is too long (max 100 characters)"
- "Description is too long (max 500 characters)"
- "Please select a photo to upload"
- "Invalid file type"
- "File size exceeds limit"
- "Failed to move uploaded file"

---

### 4. Email Confirmation

**Endpoint:** `/confirm-email.php`

**Method:** `GET`

**Authentication:** Not required

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | Confirmation token |

**Success Response:**
- Updates user's `email_verified` to 1
- Deletes confirmation token
- Redirects to `/auth/login.php`
- Shows: "Email verified successfully! You can now log in."

**Error Response - Invalid Token:**
- Shows: "Invalid confirmation link"

**Error Response - Expired Token:**
- Shows: "Confirmation link has expired. Please register again."

**Error Response - Already Verified:**
- Shows: "Email already verified"

---

### 5. 2FA Verification

**Endpoint:** `/verify-2fa.php`

**Method:** `POST`

**Authentication:** Partial (pending 2FA)

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `code` | string | Yes | 6-digit code |

**Success Response:**
- Sets `$_SESSION['user_id']`
- Deletes 2FA code from database
- Redirects to `/home.php`
- Shows: "Login successful!"

**Error Response - Invalid Code:**
- Shows: "Invalid or expired code"
- User stays on verify page
- Can request new code

**Error Response - Expired Code:**
- Shows: "Code has expired. Please log in again."
- Redirects to `/auth/login.php`

---

### 6. Contact Form

**Endpoint:** `/contact.php`

**Method:** `POST`

**Authentication:** Not required

**Parameters:**

| Parameter | Type | Required | Validation |
|-----------|------|----------|-----------|
| `name` | string | Yes | Max 100 chars |
| `email` | string | Yes | Valid email |
| `phone` | string | No | Max 20 chars |
| `subject` | string | Yes | Max 100 chars |
| `message` | string | Yes | Max 5000 chars |

**Success Response:**
- Inserts message into `contact_messages` table
- Sends email notification to admin
- Redirects to homepage
- Shows: "Thank you! We'll be in touch soon."

**Error Response:**
- Shows validation errors
- Stays on page
- Preserves form data

---

### 7. Password Reset

**Endpoint:** `/auth/forgot-password.php`

**Method:** `POST`

**Authentication:** Not required

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Account email |

**Success Response:**
- Generates reset token
- Stores in `password_resets` table
- Sends email with reset link
- Shows: "Check your email for password reset instructions"

**Reset Link Format:**
```
/auth/reset-password.php?token=XXXX
```

**Token Expiration:** 24 hours

---

### 8. Update Settings

**Endpoint:** `/email-settings.php`

**Method:** `POST`

**Authentication:** Required

**Parameters (Password Change):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | `change_password` |
| `current_password` | string | Yes | Current password |
| `new_password` | string | Yes | New password (min 6 chars) |
| `password_confirm` | string | Yes | Confirmation |

**Parameters (Email Change):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | `change_email` |
| `new_email` | string | Yes | New email address |

**Parameters (2FA Enable/Disable):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | `enable_2fa` or `disable_2fa` |

**Success Response:**
- Updates user record
- Redirects back to settings page
- Shows success message

**Error Responses:**
- "Current password is incorrect"
- "Email already in use"
- "Passwords do not match"
- "Passwords cannot be empty"

---

## Rate Limiting

Currently **not implemented**. Recommended limits for production:

| Endpoint | Limit | Window |
|----------|-------|--------|
| `/like_ajax.php` | 60 likes | Per minute |
| `/comment_ajax.php` | 30 comments | Per minute |
| `/api/follow_ajax.php` | 10 follows | Per minute |
| `/auth/login.php` | 5 attempts | Per 15 minutes |
| `/upload.php` | 10 uploads | Per hour |
| `/contact.php` | 5 contacts | Per hour |

**Implementation Example:**
```php
function checkRateLimit($action, $user_id, $limit, $window_seconds) {
    $cache_key = "ratelimit:{$action}:{$user_id}";
    $count = apcu_fetch($cache_key) ?: 0;
    
    if ($count >= $limit) {
        return false;
    }
    
    apcu_store($cache_key, $count + 1, $window_seconds);
    return true;
}
```

---

## Best Practices

### JavaScript Fetch Examples

**With Error Handling:**
```javascript
async function likePhoto(photoId) {
    try {
        const response = await fetch(`/like_ajax.php?id=${photoId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`Like count: ${data.like_count}`);
        } else {
            console.error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
```

**With Loading State:**
```javascript
async function postComment(photoId, comment) {
    const button = document.querySelector('[type=submit]');
    button.disabled = true;
    button.textContent = 'Posting...';
    
    try {
        const formData = new FormData();
        formData.append('photo_id', photoId);
        formData.append('comment', comment);
        
        const response = await fetch('/comment_ajax.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI
        } else {
            alert(data.message);
        }
    } finally {
        button.disabled = false;
        button.textContent = 'Post Comment';
    }
}
```

### Validation Tips

1. **Client-side validation first** (UX feedback)
2. **Always validate on server** (security)
3. **Sanitize input** (htmlspecialchars, trim)
4. **Check authentication** (session exists)
5. **Verify ownership** (user owns resource)

### Security Headers

**Add to headers:**
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

---

**Last Updated:** December 2024
**API Version:** 1.0
