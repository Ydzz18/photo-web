#!/usr/bin/env php
<?php
/**
 * Gmail Integration Setup Script
 * 
 * Usage: php setup-gmail.php
 * 
 * This script will:
 * 1. Check if .env file exists, if not create it from .env.example
 * 2. Run database migrations for email confirmation and 2FA
 * 3. Create email_templates directory
 * 4. Provide instructions for Gmail configuration
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║       Gmail SMTP & 2FA Integration Setup                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$project_root = __DIR__;
$env_file = $project_root . '/.env';
$env_example = $project_root . '/.env.example';
$email_templates_dir = $project_root . '/email_templates';

// Step 1: Check and create .env file
echo "Step 1: Checking .env configuration...\n";
if (!file_exists($env_file)) {
    if (file_exists($env_example)) {
        if (copy($env_example, $env_file)) {
            echo "✓ Created .env from .env.example\n";
            echo "  ⚠ IMPORTANT: Edit .env with your Gmail credentials\n\n";
        } else {
            echo "❌ Failed to create .env file\n";
            exit(1);
        }
    } else {
        echo "❌ .env.example not found\n";
        exit(1);
    }
} else {
    echo "✓ .env file already exists\n\n";
}

// Step 2: Create email_templates directory
echo "Step 2: Creating email templates directory...\n";
if (!is_dir($email_templates_dir)) {
    if (mkdir($email_templates_dir, 0755, true)) {
        echo "✓ Created email_templates directory\n\n";
        
        // Create default templates
        createEmailTemplates($email_templates_dir);
    } else {
        echo "❌ Failed to create email_templates directory\n";
        exit(1);
    }
} else {
    echo "✓ email_templates directory already exists\n\n";
}

// Step 3: Run database migrations
echo "Step 3: Running database migrations...\n";
require_once $project_root . '/db_connect.php';

try {
    $pdo = getDBConnection();
    
    // Add columns to users table
    $columns = ['email_verified', 'two_fa_enabled'];
    foreach ($columns as $column) {
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE '{$column}'");
        if ($result->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} BOOLEAN DEFAULT 0");
            echo "✓ Added {$column} column to users table\n";
        } else {
            echo "✓ {$column} column already exists\n";
        }
    }
    
    // Create required tables
    $tables = [
        'email_confirmations' => "CREATE TABLE email_confirmations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            confirmed_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_token (token)
        )",
        'password_resets' => "CREATE TABLE password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            used_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_token (token)
        )",
        'two_factor_auth' => "CREATE TABLE two_factor_auth (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            code VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        )"
    ];
    
    foreach ($tables as $table_name => $create_sql) {
        $result = $pdo->query("SHOW TABLES LIKE '{$table_name}'");
        if ($result->rowCount() == 0) {
            $pdo->exec($create_sql);
            echo "✓ Created {$table_name} table\n";
        } else {
            echo "✓ {$table_name} table already exists\n";
        }
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Display completion message
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              Setup Completed Successfully!                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Next Steps:\n\n";
echo "1. CONFIGURE GMAIL\n";
echo "   • Visit: https://myaccount.google.com/security\n";
echo "   • Enable 2-Step Verification\n";
echo "   • Generate an App Password at:\n";
echo "     https://myaccount.google.com/apppasswords\n";
echo "   • Select 'Mail' and 'Windows Computer'\n\n";

echo "2. UPDATE .env FILE\n";
echo "   • Edit: .env\n";
echo "   • Add your Gmail address:\n";
echo "     GMAIL_ADDRESS=your-email@gmail.com\n";
echo "   • Add your App Password:\n";
echo "     GMAIL_APP_PASSWORD=your-16-char-password\n\n";

echo "3. TEST THE INTEGRATION\n";
echo "   • Register a new account on the website\n";
echo "   • Check email for confirmation link\n";
echo "   • Test 2FA during login\n\n";

echo "4. (OPTIONAL) CUSTOMIZE EMAIL TEMPLATES\n";
echo "   • Edit files in: email_templates/\n";
echo "   • Use {{variable}} syntax for dynamic content\n\n";

echo "📚 For more details, see: GMAIL_INTEGRATION.md\n\n";

/**
 * Create default email templates
 */
function createEmailTemplates($dir) {
    $templates = [
        'email_confirmation.html' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 8px; }
        .header { color: #667eea; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .content { color: #333; line-height: 1.6; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Welcome to LensCraft!</div>
        <div class="content">
            <p>Hi {{user_name}},</p>
            <p>Thank you for registering with LensCraft Photography. Please confirm your email address to complete your registration and unlock all features.</p>
            <a href="{{confirmation_link}}" class="button">Confirm Email Address</a>
            <p>If you didn\'t create this account, please ignore this email.</p>
            <p>Best regards,<br>The LensCraft Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 LensCraft Photography. All rights reserved.</p>
        </div>
    </div>
</body>
</html>',
        '2fa_code.html' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 8px; }
        .header { color: #667eea; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .content { color: #333; line-height: 1.6; }
        .code-box { background: #f9f9f9; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; }
        .code { font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
        .footer { color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Your Login Code</div>
        <div class="content">
            <p>Hi {{user_name}},</p>
            <p>Your two-factor authentication code for LensCraft Photography is:</p>
            <div class="code-box">
                <div class="code">{{2fa_code}}</div>
            </div>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn\'t request this code, please ignore this email and your account will remain secure.</p>
            <p>Best regards,<br>The LensCraft Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 LensCraft Photography. All rights reserved.</p>
        </div>
    </div>
</body>
</html>',
        'password_reset.html' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 8px; }
        .header { color: #667eea; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .content { color: #333; line-height: 1.6; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Reset Your Password</div>
        <div class="content">
            <p>Hi {{user_name}},</p>
            <p>We received a request to reset your LensCraft Photography password. Click the button below to create a new password.</p>
            <a href="{{reset_link}}" class="button">Reset Password</a>
            <p>This link will expire in 24 hours.</p>
            <p>If you didn\'t request a password reset, please ignore this email and your account will remain secure.</p>
            <p>Best regards,<br>The LensCraft Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 LensCraft Photography. All rights reserved.</p>
        </div>
    </div>
</body>
</html>',
        'contact_form.html' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 8px; }
        .header { color: #667eea; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .content { color: #333; line-height: 1.6; }
        .info-box { background: #f9f9f9; padding: 15px; border-left: 4px solid #667eea; margin: 15px 0; }
        .footer { color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">New Contact Form Submission</div>
        <div class="content">
            <div class="info-box">
                <p><strong>From:</strong> {{visitor_name}}</p>
                <p><strong>Email:</strong> {{visitor_email}}</p>
                <p><strong>Subject:</strong> {{subject}}</p>
            </div>
            <p><strong>Message:</strong></p>
            <p>{{message}}</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 LensCraft Photography. All rights reserved.</p>
        </div>
    </div>
</body>
</html>'
    ];
    
    foreach ($templates as $filename => $content) {
        $filepath = $dir . '/' . $filename;
        if (!file_exists($filepath)) {
            file_put_contents($filepath, $content);
            echo "✓ Created email template: {$filename}\n";
        }
    }
}
?>
