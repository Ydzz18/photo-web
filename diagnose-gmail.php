#!/usr/bin/env php
<?php
/**
 * Gmail SMTP Authentication Diagnostic
 * Helps identify why Gmail authentication is failing
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     Gmail SMTP Authentication Diagnostic                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Check .env file
echo "📋 Step 1: Checking .env file...\n";
if (!file_exists(__DIR__ . '/.env')) {
    echo "❌ .env file not found!\n";
    exit(1);
}
echo "✓ .env file found\n\n";

// Step 2: Load and verify credentials
echo "📋 Step 2: Loading credentials...\n";
$env = parse_ini_file(__DIR__ . '/.env');
$gmail_address = $env['GMAIL_ADDRESS'] ?? null;
$gmail_password = $env['GMAIL_APP_PASSWORD'] ?? null;

if (!$gmail_address || !$gmail_password) {
    echo "❌ Missing credentials!\n";
    echo "   GMAIL_ADDRESS: " . ($gmail_address ? "✓" : "❌ MISSING") . "\n";
    echo "   GMAIL_APP_PASSWORD: " . ($gmail_password ? "✓" : "❌ MISSING") . "\n";
    exit(1);
}

echo "✓ GMAIL_ADDRESS: {$gmail_address}\n";
echo "✓ GMAIL_APP_PASSWORD: " . str_repeat("*", strlen($gmail_password)) . "\n";
echo "  Length: " . strlen($gmail_password) . " characters\n\n";

// Step 3: Verify credentials format
echo "📋 Step 3: Verifying credential format...\n";

if (strlen($gmail_address) < 5 || strpos($gmail_address, '@') === false) {
    echo "❌ GMAIL_ADDRESS format invalid: {$gmail_address}\n";
    exit(1);
}
echo "✓ GMAIL_ADDRESS format valid\n";

if (strlen($gmail_password) < 10) {
    echo "❌ GMAIL_APP_PASSWORD too short (got " . strlen($gmail_password) . ", need at least 10)\n";
    echo "   App passwords are typically 16 characters (4 groups of 4)\n";
    exit(1);
}
echo "✓ GMAIL_APP_PASSWORD format looks valid\n";

// Check for spaces in password
if (strpos($gmail_password, ' ') !== false) {
    echo "⚠ WARNING: GMAIL_APP_PASSWORD contains spaces!\n";
    echo "  App passwords should not have spaces (even after dashes)\n";
    echo "  Current: " . substr($gmail_password, 0, 5) . "...\n\n";
}

// Step 4: Test PHPMailer directly
echo "\n📋 Step 4: Testing PHPMailer SMTP connection...\n";

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Enable debugging
    $mail->SMTPDebug = 2; // Verbose output
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    
    echo "SMTP Server: smtp.gmail.com:587\n";
    echo "SMTP Security: TLS\n";
    echo "SMTP Auth: Enabled\n\n";
    
    // Credentials
    $mail->Username = $gmail_address;
    $mail->Password = $gmail_password;
    
    echo "Attempting SMTP connection...\n";
    echo "Using credentials:\n";
    echo "  Email: {$gmail_address}\n";
    echo "  Password: " . str_repeat("*", strlen($gmail_password)) . "\n\n";
    
    // Try to connect
    if ($mail->smtpConnect()) {
        echo "✅ SMTP CONNECTION SUCCESSFUL!\n";
        echo "   Connection to smtp.gmail.com:587 established\n";
        echo "   Authentication: PASSED\n\n";
        
        $mail->smtpClose();
        
        // If we got here, credentials are valid
        echo "🎉 Your Gmail credentials are correct!\n\n";
        echo "Next steps:\n";
        echo "1. Make sure 2-Step Verification is ENABLED on your Gmail account\n";
        echo "2. Verify you're using an APP PASSWORD (not your Gmail password)\n";
        echo "3. App Password should be 16 characters\n";
        echo "4. Run: php test-gmail-smtp.php\n";
        
    } else {
        echo "❌ SMTP CONNECTION FAILED\n";
        echo "   Could not connect to smtp.gmail.com:587\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    
    // Provide specific guidance based on error
    $error_msg = strtolower($e->getMessage());
    
    if (strpos($error_msg, 'authenticate') !== false) {
        echo "📍 Authentication Issue Detected!\n\n";
        echo "Possible causes:\n";
        echo "1. ❌ Using Gmail password instead of App Password\n";
        echo "   → Use: https://myaccount.google.com/apppasswords\n\n";
        echo "2. ❌ 2-Step Verification not enabled\n";
        echo "   → Enable: https://myaccount.google.com/security\n\n";
        echo "3. ❌ Spaces in password (copy-paste issue)\n";
        echo "   → Remove any spaces from password\n\n";
        echo "4. ❌ Wrong email/password in .env\n";
        echo "   → Edit .env and verify credentials\n\n";
        
    } else if (strpos($error_msg, 'connection') !== false) {
        echo "📍 Connection Issue Detected!\n\n";
        echo "Possible causes:\n";
        echo "1. ❌ Port 587 blocked by firewall\n";
        echo "   → Check server firewall settings\n\n";
        echo "2. ❌ Server hosting blocks SMTP\n";
        echo "   → Contact hosting provider\n\n";
        echo "3. ❌ Network issue\n";
        echo "   → Check internet connection\n\n";
        
    } else if (strpos($error_msg, 'ssl') !== false || strpos($error_msg, 'certificate') !== false) {
        echo "📍 SSL/Certificate Issue Detected!\n\n";
        echo "Possible causes:\n";
        echo "1. ❌ PHP OpenSSL extension not enabled\n";
        echo "   → Check phpinfo() for OpenSSL support\n\n";
        echo "2. ❌ Invalid SSL certificate\n";
        echo "   → May need to disable SSL verification (not recommended)\n\n";
    }
}

// Step 5: Check PHP SSL/OpenSSL
echo "\n📋 Step 5: Checking PHP SSL/OpenSSL support...\n";

if (extension_loaded('openssl')) {
    echo "✓ OpenSSL extension is loaded\n";
    
    // Try to get OpenSSL version
    if (function_exists('openssl_get_cert_locations')) {
        $cert_locations = openssl_get_cert_locations();
        echo "  OpenSSL version: " . OPENSSL_VERSION_TEXT . "\n";
    }
} else {
    echo "❌ OpenSSL extension is NOT loaded!\n";
    echo "   This is required for SMTP TLS connection\n";
    echo "   Enable in php.ini: extension=php_openssl.dll\n";
}

// Step 6: Check firewall/port
echo "\n📋 Step 6: Checking network connectivity...\n";

if (function_exists('fsockopen')) {
    echo "Testing connection to smtp.gmail.com:587...\n";
    
    $handle = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 5);
    
    if ($handle) {
        echo "✓ Port 587 is accessible\n";
        fclose($handle);
    } else {
        echo "❌ Cannot connect to smtp.gmail.com:587\n";
        echo "   Error: {$errstr} (Code: {$errno})\n";
        echo "   Port 587 may be blocked by firewall\n";
    }
} else {
    echo "⚠ fsockopen() not available - skipping port test\n";
}

// Step 7: Recommendations
echo "\n════════════════════════════════════════════════════════════\n";
echo "TROUBLESHOOTING CHECKLIST\n";
echo "════════════════════════════════════════════════════════════\n\n";

echo "☐ 1. Enable 2-Step Verification\n";
echo "     → https://myaccount.google.com/security\n\n";

echo "☐ 2. Generate App Password\n";
echo "     → https://myaccount.google.com/apppasswords\n";
echo "     → Select: Mail & Windows Computer\n";
echo "     → Should be 16 characters (spaces are just formatting)\n\n";

echo "☐ 3. Copy App Password WITHOUT spaces\n";
echo "     → App password appears as: xxxx xxxx xxxx xxxx\n";
echo "     → Copy as: xxxxxxxxxxxxxxxx (remove spaces)\n\n";

echo "☐ 4. Update .env file\n";
echo "     GMAIL_ADDRESS=your-email@gmail.com\n";
echo "     GMAIL_APP_PASSWORD=xxxxxxxxxxxxxxxx\n\n";

echo "☐ 5. Verify .env was saved\n";
echo "     → Check: cat .env | grep GMAIL\n\n";

echo "☐ 6. Test SMTP connection\n";
echo "     → Run: php diagnose-gmail.php\n\n";

echo "☐ 7. Test email sending\n";
echo "     → Run: php test-gmail-smtp.php\n\n";

echo "════════════════════════════════════════════════════════════\n";
echo "COMMON MISTAKES\n";
echo "════════════════════════════════════════════════════════════\n\n";

echo "❌ Mistake 1: Using Gmail password\n";
echo "   Use App Password from apppasswords\n\n";

echo "❌ Mistake 2: App password has spaces\n";
echo "   App password shows as: xxxx xxxx xxxx xxxx\n";
echo "   But should be entered as: xxxxxxxxxxxxxxxx\n\n";

echo "❌ Mistake 3: 2-Step Verification not enabled\n";
echo "   App passwords only work WITH 2-Step Verification\n\n";

echo "❌ Mistake 4: Wrong email address\n";
echo "   Use full email: yourname@gmail.com (not just name)\n\n";

echo "❌ Mistake 5: Copy-paste includes extra characters\n";
echo "   App password might have leading/trailing spaces\n\n";

?>
