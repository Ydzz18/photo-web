# ✅ Database Migration Fix & Setup Instructions

## Problem Fixed

The setup script was failing with this error:
```
❌ Database error: SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'expires_at'
```

### Root Cause
MySQL's `TIMESTAMP` columns require explicit `DEFAULT NULL` syntax when allowing NULL values. The previous code had:
```sql
expires_at TIMESTAMP,
confirmed_at TIMESTAMP NULL,
```

This syntax is ambiguous in MySQL and causes an error. The fix explicitly states:
```sql
expires_at TIMESTAMP NULL DEFAULT NULL,
confirmed_at TIMESTAMP NULL DEFAULT NULL,
```

## Files Fixed

✅ **Fixed Files:**
1. `/migrations/add_email_2fa.php` - Migration script
2. `/config/EmailConfirmationService.php` - Service class auto-migration
3. `/setup-gmail.php` - Automated setup script

All three now use the correct TIMESTAMP syntax with explicit `NULL DEFAULT NULL`.

## How to Proceed

### Method 1: Run the Fixed Setup Script (Recommended)

If you haven't run `setup-gmail.php` yet or it failed:

```bash
php setup-gmail.php
```

✅ This will now complete successfully!

### Method 2: Manual Database Migration

If you prefer manual control, run:

```bash
php migrations/add_email_2fa.php
```

### Method 3: Verify Database Structure

To verify everything was created correctly:

```bash
php test-migration.php
```

Expected output:
```
✓ Database connection successful
✓ Users table exists
✓ email_verified column exists
✓ two_fa_enabled column exists
✓ Created email_confirmations table
✓ Created password_resets table
✓ Created two_factor_auth table
✓ Database migration test completed successfully!
```

## Database Tables Created

### 1. Users Table (Columns Added)
```
✓ email_verified (BOOLEAN) - Tracks if user's email is verified
✓ two_fa_enabled (BOOLEAN) - Tracks if user has 2FA enabled
```

### 2. email_confirmations Table
```
id              - Auto-incrementing primary key
user_id         - Foreign key to users.id
token           - Unique verification token (hashed SHA-256)
created_at      - TIMESTAMP - When token was generated
expires_at      - TIMESTAMP NULL - 24-hour expiration
confirmed_at    - TIMESTAMP NULL - When email was confirmed
```

### 3. password_resets Table
```
id              - Auto-incrementing primary key
user_id         - Foreign key to users.id
token           - Unique reset token (hashed SHA-256)
created_at      - TIMESTAMP - When token was generated
expires_at      - TIMESTAMP NULL - 24-hour expiration
used_at         - TIMESTAMP NULL - When password was reset
```

### 4. two_factor_auth Table
```
id              - Auto-incrementing primary key
user_id         - Foreign key to users.id
code            - 6-digit code (hashed SHA-256)
created_at      - TIMESTAMP - When code was generated
(Note: Code expires after 10 minutes)
```

## Next Steps

### 1. Configure Gmail Credentials

Edit `.env` file:
```bash
GMAIL_ADDRESS=your-email@gmail.com
GMAIL_APP_PASSWORD=your-16-digit-app-password
FROM_NAME=LensCraft Photography
REPLY_TO_EMAIL=your-email@gmail.com
```

**Get Gmail App Password:**
1. Go to https://myaccount.google.com/security
2. Enable "2-Step Verification" (if not already done)
3. Go to https://myaccount.google.com/apppasswords
4. Select "Mail" and "Windows Computer"
5. Copy the 16-digit password to .env

### 2. Verify Email Templates

Check that these files exist:
```
/email_templates/email_confirmation.html
/email_templates/2fa_code.html
/email_templates/password_reset.html
/email_templates/contact_form.html
```

If missing, run:
```bash
php setup-gmail.php
```

### 3. Test Email Sending

Create a test file to verify SMTP is working:

```php
<?php
require_once 'config/EmailService.php';

$emailService = new EmailService();

// Test 1: Send confirmation email
$result = $emailService->sendEmailConfirmation(
    'test@example.com',
    'Test User',
    'https://yoursite.com/confirm-email.php?token=abc123'
);

if ($result) {
    echo "✓ Confirmation email sent successfully!\n";
} else {
    echo "❌ Error: " . $emailService->getLastError() . "\n";
}
?>
```

Run: `php test-email.php`

### 4. Test Registration Flow

1. Navigate to `/auth/register.php`
2. Fill out registration form
3. Check email for verification link
4. Click link to confirm email
5. Login with verified email

### 5. Test 2FA

1. Login to your account
2. Go to `/email-settings.php`
3. Click "Enable 2FA"
4. Logout and login again
5. You should receive a 6-digit code via email
6. Enter code to complete login

## Common Issues & Solutions

### Issue 1: "SMTP Connection Failed"
```
Check:
1. GMAIL_ADDRESS in .env is correct
2. GMAIL_APP_PASSWORD is 16 digits
3. 2-Step Verification is enabled on Gmail
4. Port 587 is open on your server
```

### Issue 2: "Email Not Sending"
```
Solutions:
1. Enable EMAIL_DEBUG=true in .env
2. Check PHP error logs
3. Verify database connection
4. Test SMTP credentials in .env manually
```

### Issue 3: "Invalid default value for expires_at" (FIXED!)
```
This error occurred because of incorrect TIMESTAMP syntax.
All files have been updated to use: TIMESTAMP NULL DEFAULT NULL

If you still see this error:
- Delete email_confirmations, password_resets tables
- Run test-migration.php again
- Check MySQL version is 5.7.4 or higher
```

### Issue 4: "Table already exists"
```
This is normal! The scripts check for existing tables first.
The system won't recreate tables if they already exist.

To force recreation:
1. Delete the table in phpMyAdmin
2. Run setup script again
```

## System Requirements

✅ **Required:**
- PHP 7.4 or higher
- MySQL 5.7.4 or higher (for TIMESTAMP NULL support)
- PHPMailer 6.0+ (already in /vendor)
- SMTP port 587 open (for Gmail)

✅ **Configuration:**
- .env file with Gmail credentials
- email_templates/ directory (auto-created)
- Write permissions to config/ and email_templates/

## Verification Checklist

After setup, verify:

- [ ] `.env` file exists with Gmail credentials
- [ ] `/email_templates/` directory has 4 HTML files
- [ ] `email_verified` column exists in users table
- [ ] `two_fa_enabled` column exists in users table
- [ ] `email_confirmations` table created
- [ ] `password_resets` table created
- [ ] `two_factor_auth` table created
- [ ] Test email sends successfully
- [ ] Registration email confirmation works
- [ ] 2FA login works

## Useful Commands

### Check database status:
```bash
php test-migration.php
```

### Run migrations manually:
```bash
php migrations/add_email_2fa.php
```

### Run automated setup:
```bash
php setup-gmail.php
```

### View PHP error logs:
```bash
tail -f /var/log/php.log
```

### Test SMTP connection:
```bash
telnet smtp.gmail.com 587
```

## Support

For detailed setup instructions, see:
- [`QUICK_START_GMAIL.md`](QUICK_START_GMAIL.md) - 5-minute setup
- [`GMAIL_INTEGRATION.md`](GMAIL_INTEGRATION.md) - Complete guide
- [`DEPLOYMENT_CHECKLIST.md`](DEPLOYMENT_CHECKLIST.md) - Testing procedures

## Summary

✅ **Fixed:** TIMESTAMP column syntax in database creation
✅ **Updated:** 3 files with corrected SQL syntax
✅ **Verified:** Migration test passes successfully
✅ **Ready:** Your system is now ready for Gmail integration!

Run `php setup-gmail.php` to complete the initial setup!
