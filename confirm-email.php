<?php
session_start();
require_once 'db_connect.php';
require_once 'config/EmailConfirmationService.php';

$success = false;
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        $pdo = getDBConnection();
        $confirmService = new EmailConfirmationService($pdo);

        // Verify token and get user ID
        $user_id = $confirmService->verifyToken($token);

        if ($user_id !== false) {
            // Confirm email in database
            if ($confirmService->confirmEmail($user_id)) {
                $success = true;
                // Optionally set session
                $_SESSION['email_confirmed'] = true;
            } else {
                $error = 'Failed to confirm email. Please try again.';
            }
        } else {
            $error = 'Invalid or expired confirmation link.';
        }
    } catch (Exception $e) {
        $error = 'An error occurred during email confirmation. Please try again later.';
        error_log("Email Confirmation Error: " . $e->getMessage());
    }
} else {
    $error = 'No confirmation token provided.';
}

$page_title = 'Confirm Email';
$page_css = 'auth.css';
include 'header.php';
?>

<div class="container" style="max-width: 600px; margin: 80px auto; padding: 20px;">
    <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
        
        <?php if ($success): ?>
            <div style="text-align: center; color: #2ecc71;">
                <i style="font-size: 60px; color: #2ecc71;" class="fas fa-check-circle"></i>
                <h2 style="color: #333; margin-top: 20px;">Email Confirmed!</h2>
                <p style="color: #666; font-size: 16px;">Your email has been successfully verified.</p>
                <a href="auth/login.php" style="display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin-top: 20px;">
                    Go to Login
                </a>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #e74c3c;">
                <i style="font-size: 60px; color: #e74c3c;" class="fas fa-times-circle"></i>
                <h2 style="color: #333; margin-top: 20px;">Confirmation Failed</h2>
                <p style="color: #666; font-size: 16px;"><?php echo htmlspecialchars($error); ?></p>
                <a href="index.php" style="display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin-top: 20px;">
                    Go to Home
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'footer_logged_in.php'; ?>
