# Services Documentation

Complete guide to LensCraft service classes and utilities.

---

## Table of Contents

1. [Service Classes Overview](#service-classes-overview)
2. [EmailService](#emailservice)
3. [EmailConfirmationService](#emailconfirmationservice)
4. [TwoFactorAuthService](#twofactorauthservice)
5. [NotificationManager](#notificationmanager)
6. [UserLogger](#userlogger)
7. [SiteSettings](#sitesettings)

---

## Service Classes Overview

Service classes handle business logic and provide reusable functionality.

| Service | Purpose | Location |
|---------|---------|----------|
| `EmailService` | Send emails via SMTP | `config/EmailService.php` |
| `EmailConfirmationService` | Email verification tokens | `config/EmailConfirmationService.php` |
| `TwoFactorAuthService` | 2FA code generation/verification | `config/TwoFactorAuthService.php` |
| `NotificationManager` | User notifications | `notification_manager.php` |
| `UserLogger` | Activity logging | `logger.php` |
| `SiteSettings` | Configuration management | `config/settings.php` |

### Design Patterns

- **Singleton Pattern** - Database connection reuse
- **Dependency Injection** - Pass PDO to services
- **Builder Pattern** - Email construction
- **Manager Pattern** - Notification management

---

## EmailService

**File:** `config/EmailService.php`

**Purpose:** Send emails using PHPMailer and Gmail SMTP

### Usage

**Basic Email:**
```php
require_once 'config/EmailService.php';

$service = new EmailService();
$success = $service->sendEmailConfirmation(
    'user@example.com',
    'John Doe',
    'https://site.com/confirm?token=abc123'
);

if (!$success) {
    $errors = $service->getErrors();
    error_log("Email error: " . implode(', ', $errors));
}
```

### Methods

#### `sendEmailConfirmation()`

Sends email verification email to new user.

**Parameters:**
```php
sendEmailConfirmation(
    string $recipient_email,  // User's email
    string $user_name,        // User's name
    string $confirmation_link // Verification link
): bool
```

**Example:**
```php
$service = new EmailService();
$link = "https://site.com/confirm-email.php?token=" . $token;
$success = $service->sendEmailConfirmation('user@example.com', 'Jane Doe', $link);
```

#### `send2FACode()`

Sends 2FA code to user's email.

**Parameters:**
```php
send2FACode(
    string $recipient_email,  // User's email
    string $user_name,        // User's name
    string $code              // 6-digit code
): bool
```

**Example:**
```php
$service->send2FACode('user@example.com', 'Jane Doe', '123456');
```

#### `sendPasswordReset()`

Sends password reset link to user.

**Parameters:**
```php
sendPasswordReset(
    string $recipient_email,  // User's email
    string $user_name,        // User's name
    string $reset_link        // Reset link
): bool
```

**Example:**
```php
$service->sendPasswordReset('user@example.com', 'Jane Doe', $link);
```

#### `sendContactNotification()`

Sends contact form to admin.

**Parameters:**
```php
sendContactNotification(
    string $name,     // Sender's name
    string $email,    // Sender's email
    string $message,  // Message content
    string $subject   // Message subject
): bool
```

**Example:**
```php
$service->sendContactNotification(
    'John Doe',
    'john@example.com',
    'I want to license some photos',
    'Photo Licensing'
);
```

#### `getErrors()`

Returns array of error messages.

**Returns:** `array` - Error messages

**Example:**
```php
$errors = $service->getErrors();
foreach ($errors as $error) {
    error_log($error);
}
```

### Configuration

Email service is configured in `config/email_config.php`:

```php
define('GMAIL_SMTP_HOST', 'smtp.gmail.com');
define('GMAIL_SMTP_PORT', 587);
define('FROM_EMAIL', 'noreply@gmail.com');
define('FROM_NAME', 'LensCraft');
```

---

## EmailConfirmationService

**File:** `config/EmailConfirmationService.php`

**Purpose:** Manage email verification tokens and confirmation flow

### Usage

```php
require_once 'config/EmailConfirmationService.php';
require_once 'db_connect.php';

$pdo = getDBConnection();
$service = new EmailConfirmationService($pdo);

// Generate token
$token = $service->generateToken($user_id);

// Verify token
$verified = $service->verifyToken($user_id, $token);
```

### Methods

#### `generateToken()`

Creates a new email confirmation token.

**Parameters:**
```php
generateToken(int $user_id): string
```

**Returns:** Plain token string (not hashed)

**Example:**
```php
$token = $service->generateToken(5);
// Returns: "a1b2c3d4e5f6..."

// Send to user
$link = "https://site.com/confirm-email.php?token=" . $token;
```

**Security Notes:**
- Generates cryptographically secure random token
- Hashes token before storing in database
- Returns plain token for email link
- User receives plain token, DB stores hash

#### `verifyToken()`

Verifies and marks token as confirmed.

**Parameters:**
```php
verifyToken(int $user_id, string $token): bool
```

**Returns:** `true` if verified, `false` if invalid/expired

**Example:**
```php
$token = $_GET['token'] ?? null;
if ($service->verifyToken($user_id, $token)) {
    // Email confirmed
    header('Location: /login.php?verified=1');
} else {
    // Invalid or expired token
    echo "Confirmation failed";
}
```

**Token Lifecycle:**
1. User registers → Token generated
2. Token sent via email (plain)
3. User clicks link → Token received (plain)
4. Token verified against DB hash
5. On success → marked as confirmed
6. Token deleted from DB

#### `getTokenStatus()`

Gets status of a token without confirming it.

**Parameters:**
```php
getTokenStatus(string $token): array|null
```

**Returns:** Token data or `null` if not found

**Example:**
```php
$status = $service->getTokenStatus($token);
if ($status) {
    echo "Token for user: " . $status['user_id'];
    if ($status['confirmed_at']) {
        echo "Already confirmed on: " . $status['confirmed_at'];
    }
}
```

### Token Expiration

**Expiration Time:** 24 hours

**Check Expiration:**
```sql
SELECT * FROM email_confirmations 
WHERE expires_at < NOW();  -- Expired tokens
```

**Cleanup Expired:**
```php
$service->cleanupExpiredTokens();
```

---

## TwoFactorAuthService

**File:** `config/TwoFactorAuthService.php`

**Purpose:** Handle 2FA code generation, verification, and user settings

### Usage

```php
require_once 'config/TwoFactorAuthService.php';
require_once 'db_connect.php';

$pdo = getDBConnection();
$service = new TwoFactorAuthService($pdo);

// Generate code
$code = $service->generateCode($user_id);

// Verify code
if ($service->verifyCode($user_id, $code_from_user)) {
    // Code correct - complete login
}

// Manage 2FA status
$service->enable2FA($user_id);
$service->disable2FA($user_id);
```

### Methods

#### `generateCode()`

Generates a new 6-digit 2FA code.

**Parameters:**
```php
generateCode(int $user_id): string
```

**Returns:** 6-digit code as string (e.g., "123456")

**Example:**
```php
$code = $service->generateCode(5);
// Returns: "123456"

// Send to user
$emailService->send2FACode($email, $name, $code);
```

**Code Generation:**
1. Generate: `random_int(0, 999999)`
2. Pad: `str_pad($code, 6, '0', STR_PAD_LEFT)`
3. Hash: `hash('sha256', $code)`
4. Store hash in DB
5. Return plain code to send

#### `verifyCode()`

Verifies a 2FA code entered by user.

**Parameters:**
```php
verifyCode(int $user_id, string $code): bool
```

**Returns:** `true` if valid and not expired, `false` otherwise

**Example:**
```php
$code_from_user = $_POST['code'];
if ($service->verifyCode($user_id, $code_from_user)) {
    // Code valid - login complete
    $_SESSION['user_id'] = $user_id;
} else {
    // Code invalid or expired
    echo "Invalid code. Try again.";
}
```

**Verification Process:**
1. Receive plain code from user
2. Hash received code with SHA-256
3. Query DB: `WHERE code = hashed AND user_id = ? AND created_at > NOW()-10min`
4. If match found: Delete code, return `true`
5. If no match: Return `false`

#### `is2FAEnabled()`

Checks if user has 2FA enabled.

**Parameters:**
```php
is2FAEnabled(int $user_id): bool
```

**Returns:** `true` if enabled, `false` if disabled

**Example:**
```php
if ($service->is2FAEnabled($user_id)) {
    // Send 2FA code
    $code = $service->generateCode($user_id);
    $emailService->send2FACode($email, $name, $code);
} else {
    // Complete login directly
    $_SESSION['user_id'] = $user_id;
}
```

#### `enable2FA()`

Enables 2FA for a user.

**Parameters:**
```php
enable2FA(int $user_id): bool
```

**Returns:** `true` if successful, `false` on error

**Example:**
```php
if ($service->enable2FA($user_id)) {
    echo "2FA enabled";
} else {
    echo "Failed to enable 2FA";
}
```

#### `disable2FA()`

Disables 2FA for a user.

**Parameters:**
```php
disable2FA(int $user_id): bool
```

**Returns:** `true` if successful, `false` on error

**Example:**
```php
if ($service->disable2FA($user_id)) {
    echo "2FA disabled";
}
```

### Code Expiration

**Expiration Time:** 10 minutes

**Cleanup Expired Codes:**
```sql
DELETE FROM two_factor_auth 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE);
```

---

## NotificationManager

**File:** `notification_manager.php`

**Purpose:** Manage user notifications for likes, comments, follows

### Usage

```php
require_once 'notification_manager.php';
require_once 'db_connect.php';

$manager = new NotificationManager(getDBConnection());

// Create notification
$manager->notifyPhotoLike(
    user_id: 5,
    liker_id: 3,
    liker_name: 'John Doe',
    photo_id: 42,
    photo_title: 'Sunset Beach'
);

// Get notifications
$notifications = $manager->getUserNotifications(5, false, 10);
```

### Methods

#### `notifyPhotoLike()`

Notifies user when photo is liked.

**Parameters:**
```php
notifyPhotoLike(
    int $user_id,        // Photo owner
    int $liker_id,       // Person who liked
    string $liker_name,  // Liker's username
    int $photo_id,       // Photo ID
    string $photo_title  // Photo title
): bool
```

**Example:**
```php
$manager->notifyPhotoLike(5, 3, 'jane_doe', 42, 'Mountain View');
// Creates: "jane_doe liked your photo 'Mountain View'"
```

#### `notifyPhotoComment()`

Notifies user when comment is left on photo.

**Parameters:**
```php
notifyPhotoComment(
    int $user_id,         // Photo owner
    int $commenter_id,    // Comment author
    string $commenter_name,
    int $photo_id,
    string $photo_title,
    int $comment_id
): bool
```

**Example:**
```php
$manager->notifyPhotoComment(5, 3, 'jane_doe', 42, 'Mountain View', 15);
```

#### `notifyFollower()`

Notifies user when someone follows them.

**Parameters:**
```php
notifyFollower(
    int $user_id,      // Person being followed
    int $follower_id,  // Follower
    string $follower_name
): bool
```

**Example:**
```php
$manager->notifyFollower(5, 3, 'jane_doe');
// Creates: "jane_doe started following you"
```

#### `getUserNotifications()`

Gets user's notifications.

**Parameters:**
```php
getUserNotifications(
    int $user_id,
    bool $unread_only = false,
    int $limit = 10
): array
```

**Returns:** Array of notification records

**Example:**
```php
// Get all recent notifications
$all = $manager->getUserNotifications(5, false, 20);

// Get only unread
$unread = $manager->getUserNotifications(5, true, 10);

// Display
foreach ($all as $notif) {
    echo $notif['title'] . ": " . $notif['message'];
}
```

#### `getUnreadCount()`

Gets count of unread notifications.

**Parameters:**
```php
getUnreadCount(int $user_id): int
```

**Returns:** Number of unread notifications

**Example:**
```php
$count = $manager->getUnreadCount(5);
echo "You have $count new notifications";
```

#### `markAsRead()`

Marks a notification as read.

**Parameters:**
```php
markAsRead(int $notification_id, int $user_id): bool
```

**Returns:** `true` if successful

**Example:**
```php
$manager->markAsRead(42, 5);
```

#### `markAllAsRead()`

Marks all notifications for user as read.

**Parameters:**
```php
markAllAsRead(int $user_id): bool
```

**Returns:** `true` if successful

**Example:**
```php
$manager->markAllAsRead(5);
```

#### `delete()`

Deletes a notification.

**Parameters:**
```php
delete(int $notification_id, int $user_id): bool
```

**Returns:** `true` if successful

**Example:**
```php
$manager->delete(42, 5);
```

### Notification Types

```php
const TYPE_LIKE = 'like';        // Photo liked
const TYPE_COMMENT = 'comment';  // Comment on photo
const TYPE_FOLLOW = 'follow';    // User followed
```

---

## UserLogger

**File:** `logger.php`

**Purpose:** Log user and admin actions for auditing

### Usage

```php
require_once 'logger.php';

$logger = new UserLogger();

$logger->logAction(
    user_id: $_SESSION['user_id'],
    action_type: 'photo_upload',
    action_description: 'User uploaded photo: Mountain View',
    affected_table: 'photos',
    affected_id: 42,
    status: 'success'
);
```

### Methods

#### `logAction()`

Logs an action to the database.

**Parameters:**
```php
logAction(
    int|null $user_id,          // User performing action
    string $action_type,         // Type of action
    string $action_description,  // Description
    int|null $admin_id = null,  // Admin ID if admin action
    string|null $affected_table = null,
    int|null $affected_id = null,
    string $status = 'success'   // success/failed/warning
): bool
```

**Example:**
```php
$logger->logAction(
    user_id: 5,
    action_type: 'login',
    action_description: 'User logged in successfully',
    status: 'success'
);

$logger->logAction(
    user_id: null,
    admin_id: 1,
    action_type: 'delete_photo',
    action_description: 'Admin deleted photo: Sunset (ID: 42)',
    affected_table: 'photos',
    affected_id: 42,
    status: 'success'
);
```

### Common Action Types

```
register              - User registered
login                - User logged in
logout               - User logged out
email_verified       - Email confirmed
password_reset       - Password changed
photo_upload         - Photo uploaded
photo_delete         - Photo deleted
like_photo           - Photo liked
unlike_photo         - Photo unliked
comment_photo        - Comment posted
delete_comment       - Comment deleted
follow_user          - User followed
unfollow_user        - User unfollowed
admin_login          - Admin logged in
admin_delete_comment - Admin deleted comment
admin_delete_photo   - Admin deleted photo
admin_delete_user    - Admin deleted user
```

### Querying Logs

**Get user's recent activity:**
```php
$stmt = $pdo->prepare(
    "SELECT * FROM user_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20"
);
$stmt->execute([5]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Get failed login attempts:**
```php
$stmt = $pdo->prepare(
    "SELECT * FROM user_logs WHERE action_type = 'login' AND status = 'failed' 
     ORDER BY created_at DESC LIMIT 10"
);
$stmt->execute();
```

---

## SiteSettings

**File:** `config/settings.php`

**Purpose:** Manage site-wide configuration and settings

### Usage

```php
require_once 'config/settings.php';

$settings = new SiteSettings();

// Get settings
$site_name = $settings->get('site_name', 'LensCraft');
$contact_email = $settings->get('contact_email');

// Set settings
$settings->set('site_name', 'My Photography');
$settings->set('contact_email', 'admin@example.com');

// Get all settings
$all = $settings->getAll();
```

### Methods

#### `get()`

Gets a setting value.

**Parameters:**
```php
get(string $key, mixed $default = null): mixed
```

**Returns:** Setting value or default if not found

**Example:**
```php
$site_name = $settings->get('site_name', 'LensCraft');
$contact = $settings->get('contact_email');
```

#### `set()`

Sets a configuration value.

**Parameters:**
```php
set(string $key, mixed $value): bool
```

**Returns:** `true` if successful

**Example:**
```php
$settings->set('site_name', 'My Photo Gallery');
$settings->set('contact_email', 'hello@example.com');
```

#### `getAll()`

Gets all settings as array.

**Parameters:** None

**Returns:** Associative array of all settings

**Example:**
```php
$all_settings = $settings->getAll();
foreach ($all_settings as $key => $value) {
    echo "$key: $value";
}
```

### Available Settings

```
site_name            - Site/company name
site_tagline         - Tagline or motto
contact_email        - Admin email for contact form
contact_phone        - Admin phone number
max_upload_size      - Maximum file upload size
allowed_file_types   - Comma-separated file extensions
photos_per_page      - Photos per page in gallery
require_email_verify - Require email verification
allow_comments       - Allow commenting
moderate_comments    - Require comment approval
allow_public_search  - Allow non-logged-in search
```

---

## Best Practices

### Error Handling

**Always check for errors:**
```php
$service = new EmailService();
if (!$service->send(...)) {
    $errors = $service->getErrors();
    error_log("Email failed: " . implode(', ', $errors));
}
```

### Dependency Injection

**Pass PDO to services:**
```php
$pdo = getDBConnection();
$service = new TwoFactorAuthService($pdo);
```

**Not hardcoding connections:**
```php
// ✗ Bad - hardcoded connection
class MyService {
    function __construct() {
        $this->pdo = new PDO(...);
    }
}

// ✓ Good - injected connection
class MyService {
    function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
}
```

### Validation

**Always validate user input:**
```php
$email = sanitizeInput($_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception("Invalid email");
}
```

---

**Last Updated:** December 2024
**Services Version:** 1.0
