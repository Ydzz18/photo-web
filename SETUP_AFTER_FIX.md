# 🚀 Quick Setup Guide (After Database Fix)

## The Problem
Setup script failed with: `Invalid default value for 'expires_at'`

## The Solution
✅ **Fixed!** All database creation scripts now use correct TIMESTAMP syntax.

## Quick Start (3 Steps)

### Step 1: Run Setup (2 minutes)
```bash
php setup-gmail.php
```

What this does:
- ✓ Creates .env from .env.example
- ✓ Creates email_templates/ directory
- ✓ Creates 4 email template HTML files
- ✓ Adds email verification columns to users table
- ✓ Creates email_confirmations, password_resets, two_factor_auth tables

Expected output:
```
✓ Created .env from .env.example
✓ Created email_templates directory
✓ Created email template: email_confirmation.html
✓ Created email template: 2fa_code.html
✓ Created email template: password_reset.html
✓ Created email template: contact_form.html
✓ Added email_verified column to users table
✓ Added two_fa_enabled column to users table
✓ Database migration completed successfully!
```

### Step 2: Configure Gmail (3 minutes)
Edit `.env` file and add:
```
GMAIL_ADDRESS=your-email@gmail.com
GMAIL_APP_PASSWORD=xxxx-xxxx-xxxx-xxxx
```

**How to get Gmail App Password:**
1. Go to https://myaccount.google.com/security
2. Enable "2-Step Verification" (if needed)
3. Go to https://myaccount.google.com/apppasswords
4. Select "Mail" and "Windows Computer"
5. Copy 16-digit password to .env

### Step 3: Test (2 minutes)
```bash
php test-gmail-smtp.php
```

Check for: `✓ Email sent successfully!`

## Done! 🎉

Your system is now ready:
- ✅ Database tables created
- ✅ Gmail configured
- ✅ Email templates ready
- ✅ SMTP connection verified

## Test Features

### Test Email Confirmation
1. Go to `/auth/register.php`
2. Register new account
3. Check email for confirmation link
4. Click link
5. Login

### Test 2FA
1. Login to account
2. Go to `/email-settings.php`
3. Enable 2FA
4. Logout and login again
5. Enter 6-digit code from email

### Test Contact Form
1. Go to `/index.php` (contact section)
2. Fill form and submit
3. Admin receives email notification

## If Something Goes Wrong

### "Table already exists"
Normal! Scripts check if tables exist first.

### "SMTP Connection Failed"
```
Check:
1. GMAIL_ADDRESS correct?
2. GMAIL_APP_PASSWORD is 16 chars?
3. 2-Step Verification enabled?
4. Port 587 open?
```

### "Email Not Sending"
```
Run: php test-gmail-smtp.php
Then check error messages
```

### "Column already exists"
Normal! Columns are only added if missing.

## Files in This Setup

| File | Purpose |
|------|---------|
| `.env` | Gmail credentials (SECRET - don't commit) |
| `/config/EmailService.php` | Main email service |
| `/config/TwoFactorAuthService.php` | 2FA code generation |
| `/config/EmailConfirmationService.php` | Token management |
| `/email_templates/` | HTML email templates |
| `/migrations/add_email_2fa.php` | Database migration script |
| `/auth/register.php` | Registration with email confirmation |
| `/auth/login.php` | Login with 2FA support |
| `/confirm-email.php` | Email verification page |
| `/verify-2fa.php` | 2FA code entry page |
| `/email-settings.php` | User security settings |

## Database Changes

### Users Table (new columns)
```sql
email_verified BOOLEAN DEFAULT 0
two_fa_enabled BOOLEAN DEFAULT 0
```

### New Tables
1. **email_confirmations** - Email verification tokens
2. **password_resets** - Password reset tokens
3. **two_factor_auth** - 2FA codes

## Verification Commands

```bash
# Test database migration
php test-migration.php

# Test Gmail SMTP
php test-gmail-smtp.php

# Run database migration manually
php migrations/add_email_2fa.php

# Run full setup
php setup-gmail.php
```

## Next: Detailed Guides

For more information:
- `QUICK_START_GMAIL.md` - 5 minute guide
- `GMAIL_INTEGRATION.md` - Complete setup guide
- `DEPLOYMENT_CHECKLIST.md` - Testing procedures
- `DATABASE_MIGRATION_FIX.md` - Database fix details

## Success Indicators

✅ You're done when you see:
- `.env` file created with Gmail credentials
- `/email_templates/` directory with 4 HTML files
- Database tables created
- Test email sends successfully
- Registration flow sends confirmation email
- 2FA sends code via email

## Support

Having issues? Check:
1. `DATABASE_MIGRATION_FIX.md` - Database setup details
2. `GMAIL_INTEGRATION.md` - Troubleshooting section
3. `test-migration.php` output for database status
4. `test-gmail-smtp.php` output for SMTP status

---

**Status:** ✅ All fixes applied and tested successfully!

**Action:** Run `php setup-gmail.php` to begin!
