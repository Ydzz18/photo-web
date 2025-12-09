# Gmail SMTP Integration Guide

## Overview
This guide explains how to set up Gmail SMTP with PHPMailer for email confirmation and 2FA (Two-Factor Authentication).

## Features Integrated
- ✅ Email Confirmation for new registrations
- ✅ Two-Factor Authentication (2FA) code delivery
- ✅ Password Reset emails
- ✅ Contact Form emails
- ✅ Secure Gmail App Password authentication

## Setup Instructions

### Step 1: Enable 2-Step Verification on Google Account
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Scroll down to "How you sign in to Google"
3. Click "2-Step Verification" and follow the prompts
4. Complete verification with your phone number

### Step 2: Generate Google App Password
1. After enabling 2-Step Verification, go to [App Passwords](https://myaccount.google.com/apppasswords)
2. Select "Mail" and "Windows Computer" (or your device type)
3. Google will generate a 16-character app password
4. Copy this password

### Step 3: Configure Email Settings
1. Copy `.env.example` to `.env` in the project root:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` and add your Gmail credentials:
   ```env
   GMAIL_ADDRESS=your-email@gmail.com
   GMAIL_APP_PASSWORD=your-16-character-app-password
   FROM_NAME=LensCraft Photography
   REPLY_TO_EMAIL=your-email@gmail.com
   ```

### Step 4: Verify Database Tables
The system will automatically create these tables:
- `email_confirmations` - Stores email verification tokens
- `password_resets` - Stores password reset tokens
- `two_factor_auth` - Stores 2FA codes

Make sure your `users` table has these columns:
```sql
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT 0;
ALTER TABLE users ADD COLUMN two_fa_enabled BOOLEAN DEFAULT 0;
```

## Usage Examples

### In Registration Flow
```php
require_once 'config/EmailService.php';
require_once 'config/EmailConfirmationService.php';

$emailService = new EmailService();
$confirmService = new EmailConfirmationService($pdo);

// Generate confirmation token
$token = $confirmService->generateToken($user_id);

// Send confirmation email
$confirmation_link = "https://yoursite.com/confirm-email.php?token=" . $token;
$emailService->sendEmailConfirmation(
    $user_email,
    $user_name,
    $confirmation_link
);
```

### In Login Flow (with 2FA)
```php
require_once 'config/EmailService.php';
require_once 'config/TwoFactorAuthService.php';

$emailService = new EmailService();
$twoFA = new TwoFactorAuthService($pdo);

// Generate 2FA code
$code = $twoFA->generateCode($user_id);

// Send 2FA email
$emailService->send2FACode($user_email, $user_name, $code);

// Later, verify the code
$is_valid = $twoFA->verifyCode($user_id, $_POST['2fa_code']);
```

### In Contact Form
```php
require_once 'config/EmailService.php';

$emailService = new EmailService();
$emailService->sendContactMessage(
    $visitor_email,
    $visitor_name,
    $subject,
    $message
);
```

## Email Templates

Email templates are stored in `/email_templates/` directory:
- `email_confirmation.html` - Email verification email
- `2fa_code.html` - 2FA code email
- `contact_form.html` - Contact form notification
- `password_reset.html` - Password reset email

Template variables are replaced with `{{variable_name}}` syntax.

### Example Template
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .button { background: #667eea; color: white; padding: 12px 30px; }
    </style>
</head>
<body>
    <h2>Welcome, {{user_name}}!</h2>
    <p>Please confirm your email:</p>
    <a href="{{confirmation_link}}" class="button">Confirm Email</a>
</body>
</html>
```

## Error Handling

The EmailService includes comprehensive error handling:
```php
$emailService = new EmailService();

if ($emailService->sendEmailConfirmation(...)) {
    echo "Email sent successfully";
} else {
    $errors = $emailService->getErrors();
    foreach ($errors as $error) {
        error_log($error);
    }
}
```

## Security Considerations

1. **App Passwords Only**: Never use your actual Gmail password. Always use App Passwords.
2. **Environment Variables**: Store sensitive data in `.env` file (not in version control).
3. **Token Hashing**: All tokens are hashed with SHA-256 before storage.
4. **Token Expiration**: 
   - Email confirmation tokens expire in 24 hours
   - 2FA codes expire in 10 minutes
   - Password reset tokens expire in 24 hours
5. **Rate Limiting**: Consider implementing rate limiting for email sending.

## Troubleshooting

### "Authentication failed" Error
- Check Gmail credentials in `.env`
- Verify 2-Step Verification is enabled
- Confirm App Password is correct
- Try regenerating App Password

### "SMTP Connection failed"
- Ensure Gmail SMTP settings in `config/email_config.php`:
  - Host: `smtp.gmail.com`
  - Port: `587`
  - Encryption: `tls`
- Check firewall settings

### Emails Not Sending
- Enable `EMAIL_DEBUG` in `.env` for detailed logs
- Check PHP error logs
- Verify `.env` file has correct variables
- Test SMTP connection directly

### "Table doesn't exist" Error
- Run registration/2FA/password reset functions once to auto-create tables
- Or manually run SQL migrations

## Advanced Configuration

### Custom Email Templates
1. Create HTML template files in `/email_templates/`
2. Use `{{variable_name}}` syntax for variables
3. Templates are auto-loaded from filesystem

### Batch Email Sending
```php
$emailService = new EmailService();
for ($i = 0; $i < count($users); $i++) {
    $emailService->sendEmailConfirmation(
        $users[$i]['email'],
        $users[$i]['name'],
        $confirmation_links[$i]
    );
    sleep(1); // Rate limit to avoid Gmail throttling
}
```

## Support

For issues or questions:
1. Check error logs in `/logs/` directory
2. Enable `EMAIL_DEBUG` for detailed SMTP logs
3. Verify all configuration steps are complete
4. Test SMTP connection manually

## References
- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)
- [Google App Passwords](https://support.google.com/accounts/answer/185833)
- [Gmail SMTP Settings](https://support.google.com/mail/answer/7126229)
