<?php
/**
 * Email Configuration for Gmail SMTP
 * 
 * To use Gmail with PHPMailer:
 * 1. Enable 2-Step Verification in your Google Account
 * 2. Generate an App Password at https://myaccount.google.com/apppasswords
 * 3. Store the app password in the GMAIL_APP_PASSWORD constant
 */

require_once __DIR__ . '/env_loader.php';

// Gmail SMTP Configuration
define('GMAIL_SMTP_HOST', 'smtp.gmail.com');
define('GMAIL_SMTP_PORT', 587);
define('GMAIL_SMTP_ENCRYPTION', 'tls');

// Gmail Account Credentials
define('GMAIL_ADDRESS', getenv('GMAIL_ADDRESS') ?: 'your-email@gmail.com');
define('GMAIL_APP_PASSWORD', getenv('GMAIL_APP_PASSWORD') ?: 'your-app-password');

// Email Settings
define('FROM_EMAIL', GMAIL_ADDRESS);
define('FROM_NAME', 'LensCraft Photography');
define('REPLY_TO_EMAIL', GMAIL_ADDRESS);

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../email_templates/');

// Enable email debugging (set to false in production)
define('EMAIL_DEBUG', false);
?>
