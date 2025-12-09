<?php
session_start();

// Include database connection
require_once '../db_connect.php';
require_once '../settings.php';
require_once '../config/EmailService.php';
require_once '../config/TwoFactorAuthService.php';

$settings = new SiteSettings();
$site_name = $settings->get('site_name', 'LensCraft');

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($login) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Connect to database
            $pdo = getDBConnection();
            
            // Prepare SQL statement to check both email and username
            $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE email = :login OR username = :login");
            $stmt->bindParam(':login', $login);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Check if 2FA is enabled
                    $twoFA = new TwoFactorAuthService($pdo);
                    
                    if ($twoFA->is2FAEnabled($user['id'])) {
                        // Generate and send 2FA code
                        try {
                            $code = $twoFA->generateCode($user['id']);
                            $emailService = new EmailService();
                            
                            if ($emailService->send2FACode($user['email'], $user['username'], $code)) {
                                // Store user ID temporarily for 2FA verification
                                $_SESSION['pending_2fa_user_id'] = $user['id'];
                                $_SESSION['pending_2fa_email'] = $user['email'];
                                
                                // Redirect to 2FA verification page
                                header("Location: ../verify-2fa.php");
                                exit();
                            } else {
                                $error = "Failed to send 2FA code. Please try again.";
                                error_log("2FA Email Error: " . $emailService->getLastError());
                            }
                        } catch (Exception $e) {
                            $error = "An error occurred during 2FA. Please try again.";
                            error_log("2FA Error: " . $e->getMessage());
                        }
                    } else {
                        // Login successful without 2FA - Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        
                        // Try to log the action (but don't fail if logger doesn't work)
                        try {
                            require_once '../logger.php';
                            $logger = new UserLogger($pdo);
                            $logger->log(
                                UserLogger::ACTION_LOGIN,
                                "User '{$user['username']}' logged in successfully",
                                $user['id'],
                                null,
                                'users',
                                $user['id'],
                                UserLogger::STATUS_SUCCESS
                            );
                        } catch (Exception $e) {
                            error_log("Logger error: " . $e->getMessage());
                        }
                        
                        // Redirect to home page
                        header("Location: ../home.php");
                        exit();
                    }
                } else {
                    $error = "Invalid email or password.";
                    
                    // Try to log failed login
                    try {
                        require_once '../logger.php';
                        $logger = new UserLogger($pdo);
                        $logger->log(
                            UserLogger::ACTION_FAILED_LOGIN,
                            "Failed login attempt for: {$login}",
                            null,
                            null,
                            null,
                            null,
                            UserLogger::STATUS_FAILED
                        );
                    } catch (Exception $e) {
                        error_log("Logger error: " . $e->getMessage());
                    }
                }
            } else {
                $error = "Invalid email/username or password.";
                
                // Try to log failed login
                try {
                    require_once '../logger.php';
                    $logger = new UserLogger($pdo);
                    $logger->log(
                        UserLogger::ACTION_FAILED_LOGIN,
                        "Failed login attempt for: {$login}",
                        null,
                        null,
                        null,
                        null,
                        UserLogger::STATUS_FAILED
                    );
                } catch (Exception $e) {
                    error_log("Logger error: " . $e->getMessage());
                }
            }
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="login-container">
        <a href="../index.php" class="logo"><?php echo htmlspecialchars($site_name); ?></a>
        <h2 class="login-title">Welcome Back</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Email or Username</label>
                <input type="text" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <p style="text-align: center; margin-top: 20px;">
                    <a href="forgot-password.php" style="color: #667eea; text-decoration: none;">Forgot your password?</a>
                </p>
            </div>
            
            <button type="submit" class="submit-btn">Sign In</button>
        </form>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
        </div>
    </div>
</body>
</html>