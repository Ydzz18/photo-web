# Gmail Integration - Quick Start Guide

## ⚡ 5-Minute Setup

### Step 1: Run Setup Script (1 minute)
```bash
# From project root, run:
php setup-gmail.php
```

This will:
- ✅ Create `.env` file from `.env.example`
- ✅ Create email templates directory with HTML templates
- ✅ Add required database columns and tables
- ✅ Display next steps

### Step 2: Get Gmail Credentials (3 minutes)
1. Go to **Google Account Security**: https://myaccount.google.com/security
2. Enable **2-Step Verification** (if not already enabled)
3. Go to **App Passwords**: https://myaccount.google.com/apppasswords
4. Select "Mail" and "Windows Computer"
5. Copy the **16-character password**

### Step 3: Configure .env (1 minute)
Edit `.env` file in project root:
```env
GMAIL_ADDRESS=your-email@gmail.com
GMAIL_APP_PASSWORD=your-16-character-app-password
FROM_NAME=LensCraft Photography
REPLY_TO_EMAIL=your-email@gmail.com
```

## 🧪 Test the Integration

### Test Email Sending
Create a test script `test-email.php`:
```php
<?php
require_once 'config/EmailService.php';

$emailService = new EmailService();
$emailService->sendEmailConfirmation(
    'test@example.com',
    'Test User',
    'https://example.com/confirm-email.php?token=123'
);

if ($emailService->getErrors()) {
    print_r($emailService->getErrors());
} else {
    echo "Email sent successfully!";
}
?>
```

### Test Registration Flow
1. Go to: `http://localhost/photo-web/auth/register.php`
2. Register a new account
3. Check your email for confirmation link
4. Click link to confirm email

### Test 2FA
1. Enable 2FA for your account (in user settings - feature available after integration)
2. Log out
3. Log in again
4. Enter the 6-digit code received via email

## 📁 Files Modified/Created

### New Files:
- ✅ `config/EmailService.php` - Email service class
- ✅ `config/TwoFactorAuthService.php` - 2FA service class
- ✅ `config/EmailConfirmationService.php` - Email tokens service
- ✅ `config/email_config.php` - Email configuration
- ✅ `.env.example` - Environment variables template
- ✅ `setup-gmail.php` - Automated setup script
- ✅ `confirm-email.php` - Email confirmation page
- ✅ `verify-2fa.php` - 2FA verification page
- ✅ `email_templates/` - Directory with HTML email templates

### Modified Files:
- ✅ `auth/register.php` - Added email confirmation
- ✅ `auth/login.php` - Added 2FA support
- ✅ `contact.php` - Uses EmailService instead of mail()

## 🔄 Features Integrated

| Feature | File | Status |
|---------|------|--------|
| Email Confirmation | `register.php` | ✅ Active |
| 2FA Code Delivery | `login.php` | ✅ Active |
| Contact Form Email | `contact.php` | ✅ Active |
| Password Reset | Ready to integrate | 🔄 |

## 🛠️ Manual Setup (if needed)

If setup script doesn't work, run these manually:

### 1. Create .env:
```bash
cp .env.example .env
# Then edit with your credentials
```

### 2. Create Database Tables:
```sql
-- Add columns to users table
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT 0;
ALTER TABLE users ADD COLUMN two_fa_enabled BOOLEAN DEFAULT 0;

-- Create email confirmations table
CREATE TABLE email_confirmations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
);

-- Create password resets table
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
);

-- Create 2FA table
CREATE TABLE two_factor_auth (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    code VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

### 3. Create Email Templates Directory:
```bash
mkdir email_templates
```

## ❓ Troubleshooting

### "SMTP Connection failed"
- Check Gmail address and App Password in `.env`
- Verify 2-Step Verification is enabled in Google Account
- Ensure firewall allows outgoing port 587

### "Email not sending"
- Check `.env` file has correct credentials
- Enable `EMAIL_DEBUG=true` in `.env` for detailed logs
- Check PHP error logs

### "Table doesn't exist"
- Run `php setup-gmail.php` again
- Or manually run the SQL migration

### "Confirmation link not working"
- Verify `confirm-email.php` exists in project root
- Check the token is being generated correctly
- Ensure database tables were created

## 📚 Additional Resources

- **Full Setup Guide**: `GMAIL_INTEGRATION.md`
- **Implementation Details**: `EMAIL_2FA_IMPLEMENTATION.md`
- **PhpMailer Docs**: https://github.com/PHPMailer/PHPMailer
- **Google App Passwords**: https://support.google.com/accounts/answer/185833

## 🚀 Next Steps

After setup:
1. Customize email templates in `email_templates/` directory
2. Add a "Reset Password" page (template ready, needs routing)
3. Add user settings to enable/disable 2FA
4. Implement rate limiting for email sending
5. Add email address verification requirement before uploading photos

## 💡 Tips

- **Template Variables**: Use `{{variable_name}}` syntax in HTML templates
- **Rate Limiting**: Add `sleep(1)` between batch emails to avoid Gmail throttling
- **Testing**: Use `EMAIL_DEBUG=true` in `.env` to see SMTP logs
- **Email Templates**: Customize in `email_templates/` directory anytime
