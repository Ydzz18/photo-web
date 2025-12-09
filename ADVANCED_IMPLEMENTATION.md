# Additional Integration Guide - Password Reset & Future Features

## Password Reset Implementation

The framework for password reset is already built. Here's how to implement it:

### Create Reset Password Request Page
Create file: `auth/forgot-password.php`

```php
<?php
session_start();
require_once '../db_connect.php';
require_once '../config/EmailService.php';
require_once '../config/EmailConfirmationService.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Find user by email
            $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate reset token
                $confirmService = new EmailConfirmationService($pdo);
                $token = $confirmService->generatePasswordResetToken($user['id']);
                
                // Build reset link
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $reset_link = $protocol . $host . '/auth/reset-password.php?token=' . $token;
                
                // Send email
                $emailService = new EmailService();
                if ($emailService->sendPasswordReset($email, $user['first_name'], $reset_link)) {
                    $message = 'Password reset link has been sent to your email.';
                } else {
                    $error = 'Failed to send reset email. Please try again.';
                }
            } else {
                // Don't reveal if email exists (security best practice)
                $message = 'If an account exists with this email, a reset link will be sent.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}

$page_title = 'Forgot Password';
$page_css = 'auth.css';
include '../header.php';
?>

<div class="container" style="max-width: 500px; margin: 100px auto;">
    <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
        
        <h2 style="text-align: center; color: #333; margin-bottom: 10px;">Reset Your Password</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Enter your email address and we'll send you a reset link.</p>
        
        <?php if ($message): ?>
            <div style="background: #e8f5e9; border: 2px solid #4caf50; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #ffebee; border: 2px solid #f44336; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">Email Address</label>
                <input type="email" name="email" placeholder="your@example.com" required 
                       style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
            </div>
            
            <button type="submit" style="width: 100%; background: #667eea; color: white; padding: 14px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                Send Reset Link
            </button>
        </form>
        
        <p style="text-align: center; color: #999; font-size: 14px; margin-top: 20px;">
            Remember your password? <a href="login.php" style="color: #667eea; text-decoration: none;">Log in</a>
        </p>
        
    </div>
</div>

<?php include '../footer_logged_in.php'; ?>
```

### Create Reset Password Page
Create file: `auth/reset-password.php`

```php
<?php
session_start();
require_once '../db_connect.php';
require_once '../config/EmailConfirmationService.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDBConnection();
            $confirmService = new EmailConfirmationService($pdo);
            
            // Verify token
            $user_id = $confirmService->verifyPasswordResetToken($token);
            
            if ($user_id === false) {
                $error = 'Invalid or expired reset link.';
            } else {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $user_id])) {
                    // Mark token as used
                    $confirmService->usePasswordResetToken($token);
                    
                    $success = 'Password reset successfully! You can now log in with your new password.';
                    $_SESSION['success'] = $success;
                    
                    // Redirect to login
                    header("Location: login.php");
                    exit;
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Reset password error: " . $e->getMessage());
        }
    }
}

$page_title = 'Reset Password';
$page_css = 'auth.css';
include '../header.php';
?>

<div class="container" style="max-width: 500px; margin: 100px auto;">
    <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
        
        <h2 style="text-align: center; color: #333; margin-bottom: 10px;">Create New Password</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Enter your new password below.</p>
        
        <?php if ($error): ?>
            <div style="background: #ffebee; border: 2px solid #f44336; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$error && !empty($token)): ?>
        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">New Password</label>
                <input type="password" name="password" required 
                       style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                <small style="color: #999;">Minimum 6 characters</small>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">Confirm Password</label>
                <input type="password" name="confirm_password" required 
                       style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
            </div>
            
            <button type="submit" style="width: 100%; background: #667eea; color: white; padding: 14px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                Reset Password
            </button>
        </form>
        <?php endif; ?>
        
    </div>
</div>

<?php include '../footer_logged_in.php'; ?>
```

## Add Login Link to Forgot Password
Update `auth/login.php` to add link:
```php
<p style="text-align: center; margin-top: 20px;">
    <a href="forgot-password.php" style="color: #667eea; text-decoration: none;">Forgot your password?</a>
</p>
```

## Future Enhancement Ideas

### 1. Email Delivery Tracking
```php
// Log email sends
CREATE TABLE email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    email_type VARCHAR(50),
    recipient_email VARCHAR(255),
    sent_at TIMESTAMP,
    delivered BOOLEAN DEFAULT 0,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 2. SMS 2FA
```php
// Add SMS support
require_once 'config/SMSService.php';

$smsService = new SMSService();
$smsService->send2FACode($phone_number, $code);
```

### 3. Email Preferences
```sql
-- User email notification preferences
CREATE TABLE email_preferences (
    user_id INT PRIMARY KEY,
    notifications_enabled BOOLEAN DEFAULT 1,
    digest_emails BOOLEAN DEFAULT 1,
    marketing_emails BOOLEAN DEFAULT 0,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 4. Rate Limiting
```php
// Prevent email spam
class RateLimiter {
    public static function checkLimit($identifier, $limit = 5, $window = 3600) {
        // Implementation
    }
}

// Usage
if (!RateLimiter::checkLimit('email:' . $user_id)) {
    $error = 'Too many emails sent. Please try again later.';
}
```

### 5. Email Verification Before Action
```php
// Require verified email for uploads
if (!$emailConfirmationService->isEmailVerified($user_id)) {
    header('Location: /email-settings.php?action=verify');
    exit;
}
```

### 6. Notification System Integration
```php
// Send in-app notifications instead of/in addition to email
$notificationManager = new NotificationManager($pdo);
$notificationManager->create(
    $user_id,
    'Email confirmed',
    'Your email has been verified.',
    'success'
);
```

## Testing Commands

### Test Email Sending
```bash
php -r "
require_once 'config/EmailService.php';
\$email = new EmailService();
\$email->sendEmailConfirmation('test@example.com', 'Test User', 'https://example.com/test');
"
```

### Check Database
```bash
mysql -u root -p photography_website -e "
SELECT * FROM email_confirmations;
SELECT * FROM two_factor_auth;
SELECT * FROM password_resets;
"
```

### View Logs
```bash
tail -f /var/log/php-errors.log
```

## Security Checklist

- [ ] `.env` added to `.gitignore`
- [ ] Never commit real credentials
- [ ] HTTPS enabled for all email links
- [ ] Rate limiting implemented
- [ ] Token expiration working
- [ ] Tokens hashed in database
- [ ] User email verified before sensitive operations
- [ ] 2FA optional but recommended
- [ ] Error messages don't reveal user existence

---

## Quick Reference - Class Methods

### EmailService
```php
$service = new EmailService();
$service->sendEmailConfirmation($email, $name, $link);
$service->send2FACode($email, $name, $code);
$service->sendContactMessage($email, $name, $subject, $message);
$service->sendPasswordReset($email, $name, $link);
$service->getErrors();  // Returns array of errors
$service->getLastError();  // Returns last error message
```

### EmailConfirmationService
```php
$service = new EmailConfirmationService($pdo);
$token = $service->generateToken($user_id);
$user_id = $service->verifyToken($token);
$service->confirmEmail($user_id);
$service->isEmailVerified($user_id);

// Password Reset
$token = $service->generatePasswordResetToken($user_id);
$user_id = $service->verifyPasswordResetToken($token);
$service->usePasswordResetToken($token);
```

### TwoFactorAuthService
```php
$service = new TwoFactorAuthService($pdo);
$code = $service->generateCode($user_id);
$is_valid = $service->verifyCode($user_id, $code);
$service->enable2FA($user_id);
$service->disable2FA($user_id);
$service->is2FAEnabled($user_id);
```
