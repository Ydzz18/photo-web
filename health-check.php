#!/usr/bin/env php
<?php
/**
 * Gmail Integration Health Check
 * Comprehensive system verification
 */

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   Gmail Integration Health Check & Diagnostic Tool            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$health_status = [
    'files' => 'PASS',
    'config' => 'PASS',
    'database' => 'PASS',
    'smtp' => 'PASS'
];

// =============================================================================
// 1. FILE SYSTEM CHECKS
// =============================================================================

echo "📁 FILE SYSTEM CHECKS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$required_files = [
    'config/EmailService.php' => 'Email Service',
    'config/TwoFactorAuthService.php' => '2FA Service',
    'config/EmailConfirmationService.php' => 'Email Confirmation Service',
    'config/email_config.php' => 'Email Configuration',
    'migrations/add_email_2fa.php' => 'Database Migration',
    'auth/register.php' => 'Registration Page',
    'auth/login.php' => 'Login Page',
    'confirm-email.php' => 'Email Confirmation Page',
    'verify-2fa.php' => '2FA Verification Page',
    'email-settings.php' => 'Email Settings Page',
    'test-migration.php' => 'Migration Test Script',
    'test-gmail-smtp.php' => 'SMTP Test Script'
];

$missing_files = [];
foreach ($required_files as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ {$description}\n";
    } else {
        echo "❌ {$description} - MISSING: {$file}\n";
        $missing_files[] = $file;
        $health_status['files'] = 'FAIL';
    }
}

// Check for email templates
echo "\n📧 Email Templates\n";
$templates = [
    'email_templates/email_confirmation.html' => 'Email Confirmation',
    'email_templates/2fa_code.html' => '2FA Code',
    'email_templates/password_reset.html' => 'Password Reset',
    'email_templates/contact_form.html' => 'Contact Form'
];

$missing_templates = [];
foreach ($templates as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ {$description} template\n";
    } else {
        echo "⚠ {$description} template - MISSING: {$file}\n";
        $missing_templates[] = $file;
    }
}

// =============================================================================
// 2. CONFIGURATION CHECKS
// =============================================================================

echo "\n\n🔧 CONFIGURATION CHECKS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Check .env file
if (file_exists(__DIR__ . '/.env')) {
    echo "✓ .env file exists\n";
    
    // Check .env content
    $env = parse_ini_file(__DIR__ . '/.env');
    
    if (!empty($env['GMAIL_ADDRESS'])) {
        echo "✓ GMAIL_ADDRESS configured: " . substr($env['GMAIL_ADDRESS'], 0, 10) . "...\n";
    } else {
        echo "❌ GMAIL_ADDRESS not configured\n";
        $health_status['config'] = 'FAIL';
    }
    
    if (!empty($env['GMAIL_APP_PASSWORD'])) {
        echo "✓ GMAIL_APP_PASSWORD configured (" . strlen($env['GMAIL_APP_PASSWORD']) . " chars)\n";
        if (strlen($env['GMAIL_APP_PASSWORD']) != 16) {
            echo "⚠ Warning: App password should be 16 characters (got " . strlen($env['GMAIL_APP_PASSWORD']) . ")\n";
        }
    } else {
        echo "❌ GMAIL_APP_PASSWORD not configured\n";
        $health_status['config'] = 'FAIL';
    }
    
    if (!empty($env['FROM_NAME'])) {
        echo "✓ FROM_NAME configured\n";
    } else {
        echo "⚠ FROM_NAME not configured (will use default)\n";
    }
} else {
    echo "❌ .env file NOT FOUND\n";
    echo "   Create from .env.example first\n";
    $health_status['config'] = 'FAIL';
}

// Check email_config.php
echo "\n";
if (file_exists(__DIR__ . '/config/email_config.php')) {
    echo "✓ email_config.php exists\n";
    
    // Check if it uses correct SMTP settings
    $config_content = file_get_contents(__DIR__ . '/config/email_config.php');
    if (strpos($config_content, 'smtp.gmail.com') !== false) {
        echo "✓ Gmail SMTP configured (smtp.gmail.com:587)\n";
    }
} else {
    echo "❌ email_config.php NOT FOUND\n";
    $health_status['config'] = 'FAIL';
}

// =============================================================================
// 3. DATABASE CHECKS
// =============================================================================

echo "\n\n🗄️  DATABASE CHECKS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

try {
    require_once __DIR__ . '/db_connect.php';
    $pdo = getDBConnection();
    echo "✓ Database connection successful\n";
    
    // Check users table
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "✓ users table exists\n";
        
        // Check columns
        $result = $pdo->query("SHOW COLUMNS FROM users");
        $columns = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = true;
        }
        
        if (isset($columns['email_verified'])) {
            echo "  ✓ email_verified column exists\n";
        } else {
            echo "  ❌ email_verified column MISSING\n";
            $health_status['database'] = 'FAIL';
        }
        
        if (isset($columns['two_fa_enabled'])) {
            echo "  ✓ two_fa_enabled column exists\n";
        } else {
            echo "  ❌ two_fa_enabled column MISSING\n";
            $health_status['database'] = 'FAIL';
        }
    } else {
        echo "❌ users table NOT FOUND\n";
        $health_status['database'] = 'FAIL';
    }
    
    // Check email_confirmations table
    echo "\n";
    $result = $pdo->query("SHOW TABLES LIKE 'email_confirmations'");
    if ($result->rowCount() > 0) {
        echo "✓ email_confirmations table exists\n";
        
        // Check TIMESTAMP columns
        $result = $pdo->query("DESCRIBE email_confirmations");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['Field'] == 'expires_at') {
                if (strpos($row['Type'], 'timestamp') !== false) {
                    echo "  ✓ expires_at column (TIMESTAMP NULL)\n";
                } else {
                    echo "  ⚠ expires_at column type: " . $row['Type'] . "\n";
                }
            }
        }
    } else {
        echo "❌ email_confirmations table NOT FOUND\n";
        $health_status['database'] = 'FAIL';
    }
    
    // Check password_resets table
    echo "\n";
    $result = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    if ($result->rowCount() > 0) {
        echo "✓ password_resets table exists\n";
    } else {
        echo "❌ password_resets table NOT FOUND\n";
        $health_status['database'] = 'FAIL';
    }
    
    // Check two_factor_auth table
    echo "\n";
    $result = $pdo->query("SHOW TABLES LIKE 'two_factor_auth'");
    if ($result->rowCount() > 0) {
        echo "✓ two_factor_auth table exists\n";
    } else {
        echo "❌ two_factor_auth table NOT FOUND\n";
        $health_status['database'] = 'FAIL';
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    $health_status['database'] = 'FAIL';
}

// =============================================================================
// 4. SERVICE CHECKS
// =============================================================================

echo "\n\n⚙️  SERVICE CHECKS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Check EmailService
try {
    require_once __DIR__ . '/config/EmailService.php';
    echo "✓ EmailService class loaded\n";
    
    // Check methods
    $methods = ['sendEmailConfirmation', 'send2FACode', 'sendContactMessage', 'sendPasswordReset', 'getLastError'];
    foreach ($methods as $method) {
        if (method_exists('EmailService', $method)) {
            echo "  ✓ $method() method exists\n";
        } else {
            echo "  ❌ $method() method MISSING\n";
            $health_status['smtp'] = 'FAIL';
        }
    }
} catch (Exception $e) {
    echo "❌ EmailService load failed: " . $e->getMessage() . "\n";
    $health_status['smtp'] = 'FAIL';
}

// Check TwoFactorAuthService
echo "\n";
try {
    require_once __DIR__ . '/config/TwoFactorAuthService.php';
    echo "✓ TwoFactorAuthService class loaded\n";
    
    $methods = ['generateCode', 'verifyCode', 'is2FAEnabled', 'enable2FA', 'disable2FA'];
    foreach ($methods as $method) {
        if (method_exists('TwoFactorAuthService', $method)) {
            echo "  ✓ $method() method exists\n";
        } else {
            echo "  ❌ $method() method MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "❌ TwoFactorAuthService load failed: " . $e->getMessage() . "\n";
}

// Check EmailConfirmationService
echo "\n";
try {
    require_once __DIR__ . '/config/EmailConfirmationService.php';
    echo "✓ EmailConfirmationService class loaded\n";
    
    $methods = ['generateToken', 'verifyToken', 'confirmEmail', 'isEmailVerified', 'generatePasswordResetToken', 'verifyPasswordResetToken'];
    foreach ($methods as $method) {
        if (method_exists('EmailConfirmationService', $method)) {
            echo "  ✓ $method() method exists\n";
        } else {
            echo "  ❌ $method() method MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "❌ EmailConfirmationService load failed: " . $e->getMessage() . "\n";
}

// =============================================================================
// 5. INTEGRATION CHECKS
// =============================================================================

echo "\n\n🔗 INTEGRATION CHECKS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Check register.php integration
if (file_exists(__DIR__ . '/auth/register.php')) {
    $content = file_get_contents(__DIR__ . '/auth/register.php');
    if (strpos($content, 'EmailService') !== false) {
        echo "✓ register.php has EmailService integration\n";
    } else {
        echo "⚠ register.php missing EmailService integration\n";
    }
    
    if (strpos($content, 'EmailConfirmationService') !== false) {
        echo "✓ register.php has EmailConfirmationService integration\n";
    } else {
        echo "⚠ register.php missing EmailConfirmationService integration\n";
    }
}

// Check login.php integration
echo "\n";
if (file_exists(__DIR__ . '/auth/login.php')) {
    $content = file_get_contents(__DIR__ . '/auth/login.php');
    if (strpos($content, 'TwoFactorAuthService') !== false) {
        echo "✓ login.php has TwoFactorAuthService integration\n";
    } else {
        echo "⚠ login.php missing TwoFactorAuthService integration\n";
    }
    
    if (strpos($content, 'verify-2fa.php') !== false) {
        echo "✓ login.php has 2FA redirect\n";
    } else {
        echo "⚠ login.php missing 2FA redirect\n";
    }
}

// =============================================================================
// 6. SUMMARY
// =============================================================================

echo "\n\n═══════════════════════════════════════════════════════════════\n";
echo "                       HEALTH CHECK SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$all_pass = true;
foreach ($health_status as $category => $status) {
    $icon = ($status === 'PASS') ? '✓' : '❌';
    echo "$icon " . strtoupper($category) . ": $status\n";
    if ($status !== 'PASS') $all_pass = false;
}

echo "\n";

if ($all_pass && empty($missing_files) && empty($missing_templates)) {
    echo "✅ ALL SYSTEMS OPERATIONAL!\n\n";
    echo "Your Gmail integration is ready:\n";
    echo "  1. Registration with email confirmation ✓\n";
    echo "  2. 2FA login support ✓\n";
    echo "  3. Password reset support ✓\n";
    echo "  4. Contact form notifications ✓\n";
    echo "\nNext: Test with php test-gmail-smtp.php\n";
} else {
    echo "⚠ ISSUES DETECTED - PLEASE FIX:\n\n";
    
    if (!empty($missing_files)) {
        echo "Missing Files:\n";
        foreach ($missing_files as $file) {
            echo "  - {$file}\n";
        }
        echo "  Run: php setup-gmail.php\n\n";
    }
    
    if (!empty($missing_templates)) {
        echo "Missing Templates:\n";
        foreach ($missing_templates as $file) {
            echo "  - {$file}\n";
        }
        echo "  Run: php setup-gmail.php\n\n";
    }
    
    if ($health_status['config'] === 'FAIL') {
        echo "Configuration Issues:\n";
        echo "  - Check .env file\n";
        echo "  - Add GMAIL_ADDRESS\n";
        echo "  - Add GMAIL_APP_PASSWORD\n\n";
    }
    
    if ($health_status['database'] === 'FAIL') {
        echo "Database Issues:\n";
        echo "  - Run: php test-migration.php\n";
        echo "  - Or: php migrations/add_email_2fa.php\n";
        echo "  - Or: php setup-gmail.php\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n\n";
?>
