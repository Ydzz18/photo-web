#!/usr/bin/env php
<?php
/**
 * Gmail SMTP Test Script
 * Verify Gmail email sending is configured correctly
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║          Gmail SMTP Configuration Test                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Check .env file
echo "📋 Step 1: Checking .env configuration...\n";
if (!file_exists(__DIR__ . '/.env')) {
    echo "❌ .env file not found!\n";
    echo "   Please create .env from .env.example first:\n";
    echo "   - Copy .env.example to .env\n";
    echo "   - Add your Gmail credentials\n";
    exit(1);
}
echo "✓ .env file found\n";

// Step 2: Load environment variables
echo "\n📋 Step 2: Loading environment variables...\n";
require_once __DIR__ . '/config/env_loader.php';
echo "✓ Configuration loaded\n";

// Step 3: Verify Gmail credentials
echo "\n📋 Step 3: Verifying Gmail credentials...\n";
$gmail_address = getenv('GMAIL_ADDRESS');
$gmail_password = getenv('GMAIL_APP_PASSWORD');

if (empty($gmail_address)) {
    echo "❌ GMAIL_ADDRESS not set in .env!\n";
    echo "   Set: GMAIL_ADDRESS=your-email@gmail.com\n";
    exit(1);
}
echo "✓ GMAIL_ADDRESS: {$gmail_address}\n";

if (empty($gmail_password)) {
    echo "❌ GMAIL_APP_PASSWORD not set in .env!\n";
    echo "   Set: GMAIL_APP_PASSWORD=your-16-digit-app-password\n";
    exit(1);
}
echo "✓ GMAIL_APP_PASSWORD: " . str_repeat("*", strlen($gmail_password)) . "\n";

// Step 4: Check PHPMailer
echo "\n📋 Step 4: Checking PHPMailer installation...\n";
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ PHPMailer not installed!\n";
    echo "   Run: composer install\n";
    exit(1);
}
echo "✓ PHPMailer found\n";

// Step 5: Test EmailService
echo "\n📋 Step 5: Testing EmailService class...\n";
try {
    require_once __DIR__ . '/config/EmailService.php';
    echo "✓ EmailService loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load EmailService: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 6: Create test email
echo "\n📋 Step 6: Preparing test email...\n";
$test_email = $_SERVER['argv'][1] ?? $gmail_address;
echo "Test recipient: {$test_email}\n";

// Step 7: Send test email
echo "\n📋 Step 7: Sending test email...\n";
try {
    $emailService = new EmailService();
    
    // Send a test email
    $result = $emailService->sendEmailConfirmation(
        $test_email,
        'Test User',
        'https://example.com/confirm-email.php?token=test-token-12345'
    );
    
    if ($result) {
        echo "✓ Email sent successfully!\n";
        echo "\n";
        echo "📧 Test Email Details:\n";
        echo "   To: {$test_email}\n";
        echo "   From: {$gmail_address}\n";
        echo "   Subject: Email Confirmation\n";
        echo "   Type: HTML with template\n";
        echo "\n";
        echo "Check your email for the test message.\n";
    } else {
        echo "❌ Failed to send email!\n";
        echo "Error: " . $emailService->getLastError() . "\n";
        
        // Try to get more detailed error info
        if (method_exists($emailService, 'getErrors')) {
            $errors = $emailService->getErrors();
            if (!empty($errors)) {
                echo "\nDetailed Errors:\n";
                foreach ($errors as $error) {
                    echo "  - {$error}\n";
                }
            }
        }
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 8: Database check
echo "\n📋 Step 8: Checking database tables...\n";
try {
    require_once __DIR__ . '/db_connect.php';
    $pdo = getDBConnection();
    
    $tables = ['users', 'email_confirmations', 'password_resets', 'two_factor_auth'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($result->rowCount() > 0) {
            echo "✓ {$table} table exists\n";
        } else {
            echo "⚠ {$table} table not found (run setup-gmail.php)\n";
        }
    }
} catch (Exception $e) {
    echo "⚠ Database check skipped: " . $e->getMessage() . "\n";
}

echo "\n════════════════════════════════════════════════════════════\n";
echo "✓ Gmail SMTP test completed successfully!\n";
echo "════════════════════════════════════════════════════════════\n\n";

echo "Next steps:\n";
echo "1. Check your email for the test message\n";
echo "2. Run setup-gmail.php if you haven't already\n";
echo "3. Test registration flow at /auth/register.php\n";
echo "4. Test 2FA at /auth/login.php\n\n";

echo "Usage:\n";
echo "  php test-gmail-smtp.php                    (send to Gmail address)\n";
echo "  php test-gmail-smtp.php your-email@test.com (send to specific address)\n\n";
?>
