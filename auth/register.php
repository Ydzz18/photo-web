<?php
session_start();

// Include database connection
require_once '../db_connect.php';
require_once '../settings.php';

$settings = new SiteSettings();
$site_name = $settings->get('site_name', 'LensCraft');

// Determine current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$form_data = [];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($step == 1) {
        // Step 1: Personal Information
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $birthday = sanitizeInput($_POST['birthday'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        
        // Validation
        if (empty($first_name) || empty($last_name)) {
            $errors[] = "First name and last name are required.";
        }
        
        if (empty($errors)) {
            // Store step 1 data in session
            $_SESSION['reg_step1'] = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'birthday' => $birthday,
                'address' => $address
            ];
            
            // Redirect to step 2
            header("Location: register.php?step=2");
            exit();
        }
        
        // Reload form with submitted data
        $form_data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'birthday' => $birthday,
            'address' => $address
        ];
        
    } elseif ($step == 2) {
        // Step 2: Account Credentials
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $errors[] = "All fields are required.";
        }
        
        if (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        
        if (empty($errors)) {
            // Check if step 1 data exists
            if (!isset($_SESSION['reg_step1'])) {
                $errors[] = "Session expired. Please start registration again.";
                header("Location: register.php?step=1");
                exit();
            }
            
            try {
                // Connect to database
                $pdo = getDBConnection();
                
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Email address is already registered.";
                } else {
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                    $stmt->bindParam(':username', $username);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $errors[] = "Username is already taken.";
                    } else {
                        // Hash the password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Get step 1 data from session
                        $step1_data = $_SESSION['reg_step1'];
                        
                        // Insert new user with all info
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, birthday, address, created_at) VALUES (:username, :email, :password, :first_name, :last_name, :phone, :birthday, :address, NOW())");
                        $stmt->bindParam(':username', $username);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->bindParam(':first_name', $step1_data['first_name']);
                        $stmt->bindParam(':last_name', $step1_data['last_name']);
                        $stmt->bindParam(':phone', $step1_data['phone']);
                        $stmt->bindParam(':birthday', $step1_data['birthday']);
                        $stmt->bindParam(':address', $step1_data['address']);
                        
                        if ($stmt->execute()) {
                            $user_id = $pdo->lastInsertId();
                            
                            // Clear registration session data
                            unset($_SESSION['reg_step1']);
                            
                            // Set session
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['username'] = $username;
                            $_SESSION['email'] = $email;
                            
                            // Try to log registration (but don't fail if logger doesn't work)
                            try {
                                require_once '../logger.php';
                                $logger = new UserLogger($pdo);
                                $logger->log(
                                    UserLogger::ACTION_REGISTER,
                                    "New user registered: '{$username}' ({$email})",
                                    $user_id,
                                    null,
                                    'users',
                                    $user_id,
                                    UserLogger::STATUS_SUCCESS
                                );
                            } catch (Exception $e) {
                                error_log("Logger error: " . $e->getMessage());
                            }
                            
                            $_SESSION['success'] = "Registration successful! Welcome to " . htmlspecialchars($site_name) . ".";
                            
                            // Redirect to home page
                            header("Location: ../home.php");
                            exit();
                        } else {
                            $errors[] = "Registration failed. Please try again.";
                        }
                    }
                }
            } catch(PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $errors[] = "An error occurred. Please try again.";
            }
        }
        
        // Reload form with submitted data
        $form_data = [
            'username' => $username,
            'email' => $email
        ];
    }
}

// Load existing step 1 data if available
if ($step == 2 && isset($_SESSION['reg_step1'])) {
    $form_data = array_merge($form_data, $_SESSION['reg_step1']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="register-container">
        <a href="../index.php" class="logo"><?php echo htmlspecialchars($site_name); ?></a>
        <h2 class="register-title">Create Your Account</h2>
        
        <div class="progress-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Personal Info</div>
            </div>
            <div class="step-line <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Account Details</div>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?step=1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo isset($form_data['first_name']) ? htmlspecialchars($form_data['first_name']) : ''; ?>"
                               placeholder="Your first name">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo isset($form_data['last_name']) ? htmlspecialchars($form_data['last_name']) : ''; ?>"
                               placeholder="Your last name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo isset($form_data['phone']) ? htmlspecialchars($form_data['phone']) : ''; ?>"
                               placeholder="Your phone number">
                    </div>
                    <div class="form-group">
                        <label for="birthday">Birthday</label>
                        <input type="date" id="birthday" name="birthday"
                               value="<?php echo isset($form_data['birthday']) ? htmlspecialchars($form_data['birthday']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address"
                           value="<?php echo isset($form_data['address']) ? htmlspecialchars($form_data['address']) : ''; ?>"
                           placeholder="City, Country">
                </div>
                
                <button type="submit" class="submit-btn">Continue to Account Details</button>
            </form>
        
        <?php elseif ($step == 2): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?step=2">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($form_data['username']) ? htmlspecialchars($form_data['username']) : ''; ?>"
                           placeholder="Choose a unique username">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>"
                           placeholder="Enter your email address">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Create a strong password (min. 6 characters)">
                    <div class="password-strength-meter">
                        <div class="password-strength-bar"></div>
                    </div>
                    <p class="password-strength-text"></p>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your password">
                </div>
                
                <div class="form-actions">
                    <a href="register.php?step=1" class="back-btn">Back</a>
                    <button type="submit" class="submit-btn">Create Account</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const strengthBar = document.querySelector('.password-strength-bar');
            const strengthText = document.querySelector('.password-strength-text');
            
            if (!passwordInput || !strengthBar || !strengthText) {
                console.error('Password strength meter elements not found');
                return;
            }
            
            function calculatePasswordStrength(password) {
                let strength = 0;
                
                if (password.length >= 6) strength += 1;
                if (password.length >= 12) strength += 1;
                if (/[a-z]/.test(password)) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
                
                return strength;
            }
            
            function updatePasswordStrength() {
                const password = passwordInput.value;
                const strength = calculatePasswordStrength(password);
                
                strengthBar.className = 'password-strength-bar';
                
                if (password.length === 0) {
                    strengthBar.style.width = '0%';
                    strengthText.textContent = '';
                    strengthText.className = 'password-strength-text';
                } else if (strength <= 1) {
                    strengthBar.style.width = '16.67%';
                    strengthBar.classList.add('weak');
                    strengthText.textContent = 'Weak';
                    strengthText.className = 'password-strength-text weak';
                } else if (strength <= 2) {
                    strengthBar.style.width = '33.33%';
                    strengthBar.classList.add('weak');
                    strengthText.textContent = 'Weak';
                    strengthText.className = 'password-strength-text weak';
                } else if (strength <= 3) {
                    strengthBar.style.width = '50%';
                    strengthBar.classList.add('fair');
                    strengthText.textContent = 'Fair';
                    strengthText.className = 'password-strength-text fair';
                } else if (strength <= 4) {
                    strengthBar.style.width = '75%';
                    strengthBar.classList.add('good');
                    strengthText.textContent = 'Good';
                    strengthText.className = 'password-strength-text good';
                } else {
                    strengthBar.style.width = '100%';
                    strengthBar.classList.add('strong');
                    strengthText.textContent = 'Strong';
                    strengthText.className = 'password-strength-text strong';
                }
            }
            
            passwordInput.addEventListener('input', updatePasswordStrength);
        });
    </script>
</body>
</html>