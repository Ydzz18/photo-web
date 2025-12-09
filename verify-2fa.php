<?php
session_start();
require_once 'db_connect.php';
require_once 'config/TwoFactorAuthService.php';

$error = '';
$user_id = $_SESSION['pending_2fa_user_id'] ?? null;

// Redirect if no pending 2FA
if (!$user_id) {
    header('Location: auth/login.php');
    exit;
}

// Handle 2FA code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['2fa_code'] ?? '');

    if (empty($code)) {
        $error = 'Please enter the 2FA code.';
    } else {
        try {
            $pdo = getDBConnection();
            $twoFA = new TwoFactorAuthService($pdo);

            if ($twoFA->verifyCode($user_id, $code)) {
                // Code verified - complete login
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    unset($_SESSION['pending_2fa_user_id']);

                    // Redirect to home
                    header('Location: home.php');
                    exit;
                }
            } else {
                $error = 'Invalid or expired 2FA code.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred during verification. Please try again.';
            error_log("2FA Verification Error: " . $e->getMessage());
        }
    }
}

$page_title = 'Two-Factor Authentication';
$page_css = 'auth.css';
include 'header.php';
?>

<div class="container" style="max-width: 500px; margin: 80px auto; padding: 20px;">
    <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
        
        <h2 style="text-align: center; color: #333; margin-bottom: 10px;">Two-Factor Authentication</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Check your email for the verification code.</p>

        <?php if ($error): ?>
            <div style="background: #ffe5e5; border: 2px solid #e74c3c; color: #c0392b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">Enter 6-Digit Code</label>
                <input type="text" 
                       name="2fa_code" 
                       placeholder="000000" 
                       maxlength="6" 
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 24px; text-align: center; letter-spacing: 10px; font-weight: bold;"
                       required autofocus>
            </div>

            <button type="submit" style="width: 100%; background: #667eea; color: white; padding: 14px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px;">
                Verify Code
            </button>
        </form>

        <p style="text-align: center; color: #999; font-size: 14px; margin-top: 20px;">
            Code expires in 10 minutes
        </p>

    </div>
</div>

<?php include 'footer_logged_in.php'; ?>
