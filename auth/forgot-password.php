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