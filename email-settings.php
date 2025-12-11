<?php
session_start();
require_once 'db_connect.php';
require_once 'config/EmailService.php';
require_once 'config/TwoFactorAuthService.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

try {
    $pdo = getDBConnection();
    
    // Get user info
    $stmt = $pdo->prepare("SELECT id, email, email_verified, two_fa_enabled FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: auth/login.php');
        exit;
    }
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        // Resend email confirmation
        if ($action === 'resend_confirmation' && !$user['email_verified']) {
            try {
                require_once 'config/EmailConfirmationService.php';
                
                $emailService = new EmailService();
                $confirmService = new EmailConfirmationService($pdo);
                
                // Delete old tokens
                $pdo->prepare("DELETE FROM email_confirmations WHERE user_id = ?")->execute([$user_id]);
                
                // Generate new token
                $token = $confirmService->generateToken($user_id);
                
                // Build link
                $confirmation_link = 'https://lenscraft.fwh.is/photo-web/confirm-email.php?token=' . $token;
                
                // Send email
                if ($emailService->sendEmailConfirmation($user['email'], $_SESSION['username'], $confirmation_link)) {
                    $success_message = 'Confirmation email sent! Please check your inbox.';
                } else {
                    $error_message = 'Failed to send confirmation email. Please try again later.';
                }
            } catch (Exception $e) {
                $error_message = 'An error occurred. Please try again.';
                error_log("Resend confirmation error: " . $e->getMessage());
            }
        }
        
        // Enable 2FA
        elseif ($action === 'enable_2fa') {
            try {
                $twoFA = new TwoFactorAuthService($pdo);
                if ($twoFA->enable2FA($user_id)) {
                    $success_message = '2FA enabled successfully! You will receive a code on your next login.';
                    $user['two_fa_enabled'] = 1;
                } else {
                    $error_message = 'Failed to enable 2FA. Please try again.';
                }
            } catch (Exception $e) {
                $error_message = 'An error occurred. Please try again.';
                error_log("Enable 2FA error: " . $e->getMessage());
            }
        }
        
        // Disable 2FA
        elseif ($action === 'disable_2fa' && $user['two_fa_enabled']) {
            try {
                $twoFA = new TwoFactorAuthService($pdo);
                if ($twoFA->disable2FA($user_id)) {
                    $success_message = '2FA disabled. You will no longer need a code to log in.';
                    $user['two_fa_enabled'] = 0;
                } else {
                    $error_message = 'Failed to disable 2FA. Please try again.';
                }
            } catch (Exception $e) {
                $error_message = 'An error occurred. Please try again.';
                error_log("Disable 2FA error: " . $e->getMessage());
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Settings error: " . $e->getMessage());
    $error_message = 'An error occurred. Please try again later.';
}

$page_title = 'Email & Security Settings';
$page_css = 'style.css';
include 'header.php';
?>

<div class="container" style="max-width: 800px; margin: 100px auto; padding: 20px;">
    
    <h1 style="color: #333; margin-bottom: 30px;">Email & Security Settings</h1>
    
    <?php if ($success_message): ?>
        <div style="background: #e8f5e9; border: 2px solid #4caf50; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div style="background: #ffebee; border: 2px solid #f44336; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Email Verification Section -->
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-envelope" style="color: #667eea;"></i>
            Email Verification
        </h2>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <p style="margin: 0; color: #555;">
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </p>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                <?php if ($user['email_verified']): ?>
                    <span style="color: #4caf50;">
                        <i class="fas fa-check-circle"></i> Email verified
                    </span>
                <?php else: ?>
                    <span style="color: #ff9800;">
                        <i class="fas fa-exclamation-triangle"></i> Email not verified
                    </span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (!$user['email_verified']): ?>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="resend_confirmation">
                <button type="submit" style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-redo"></i> Resend Confirmation Email
                </button>
            </form>
            <p style="font-size: 13px; color: #666; margin-top: 10px;">
                Don't see the email? Check your spam folder or click the button above to resend it.
            </p>
        <?php else: ?>
            <p style="color: #4caf50; font-weight: 600;">
                <i class="fas fa-check"></i> Your email address has been verified!
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Two-Factor Authentication Section -->
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-shield-alt" style="color: #667eea;"></i>
            Two-Factor Authentication (2FA)
        </h2>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <p style="margin: 0; color: #555;">
                <strong>Status:</strong> 
                <?php if ($user['two_fa_enabled']): ?>
                    <span style="color: #4caf50; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> Enabled
                    </span>
                <?php else: ?>
                    <span style="color: #ff9800; font-weight: 600;">
                        <i class="fas fa-times-circle"></i> Disabled
                    </span>
                <?php endif; ?>
            </p>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                Two-factor authentication adds an extra layer of security to your account.
            </p>
        </div>
        
        <div style="background: #f0f4ff; padding: 15px; border-left: 4px solid #667eea; border-radius: 4px; margin-bottom: 20px;">
            <p style="margin: 0; color: #333; font-size: 14px;">
                <strong>How it works:</strong> When you log in, you'll receive a 6-digit code via email that you must enter to complete the login.
            </p>
        </div>
        
        <form method="POST" style="margin: 0;">
            <?php if ($user['two_fa_enabled']): ?>
                <input type="hidden" name="action" value="disable_2fa">
                <button type="submit" style="background: #f44336; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-times"></i> Disable 2FA
                </button>
            <?php else: ?>
                <input type="hidden" name="action" value="enable_2fa">
                <button type="submit" style="background: #4caf50; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-check"></i> Enable 2FA
                </button>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Info Box -->
    <div style="background: #e3f2fd; padding: 20px; border-radius: 12px; border-left: 4px solid #2196f3;">
        <h3 style="margin-top: 0; color: #1565c0;">
            <i class="fas fa-info-circle"></i> Security Tips
        </h3>
        <ul style="margin: 10px 0; padding-left: 20px; color: #333;">
            <li>Keep your password strong and unique</li>
            <li>Enable 2FA for enhanced account security</li>
            <li>Verify your email address to unlock all features</li>
            <li>Never share your 2FA codes with anyone</li>
        </ul>
    </div>

</div>

<?php include 'footer_logged_in.php'; ?>
