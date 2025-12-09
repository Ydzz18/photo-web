# Gmail Integration - Deployment & Testing Checklist

## ✅ Pre-Deployment Checklist

### Local Development Setup
- [ ] Run `php setup-gmail.php` successfully
- [ ] `.env` file created with Gmail credentials
- [ ] All database tables created
- [ ] `email_templates/` directory exists with HTML files
- [ ] PHPMailer library available in `vendor/`

### Code Integration
- [ ] `auth/register.php` modified - email confirmation added
- [ ] `auth/login.php` modified - 2FA support added
- [ ] `contact.php` modified - uses EmailService
- [ ] `confirm-email.php` created - verification page
- [ ] `verify-2fa.php` created - 2FA page
- [ ] `email-settings.php` created - user settings
- [ ] All config files in place

### Database
- [ ] Users table has `email_verified` column
- [ ] Users table has `two_fa_enabled` column
- [ ] `email_confirmations` table created
- [ ] `password_resets` table created
- [ ] `two_factor_auth` table created
- [ ] Database indexes created

### Documentation
- [ ] README updated with Gmail setup instructions
- [ ] `.gitignore` includes `.env`
- [ ] `.env.example` has template values
- [ ] All documentation files present

## 🧪 Testing Procedures

### 1. Email Service Tests
```bash
# Test basic email sending
php -r "
require_once 'db_connect.php';
require_once 'config/EmailService.php';
\$email = new EmailService();
\$result = \$email->sendEmailConfirmation('test@example.com', 'Test User', 'https://example.com/test');
echo \$result ? 'Success' : 'Failed: ' . \$email->getLastError();
"
```

- [ ] Email sends without errors
- [ ] Email arrives in inbox within 1 minute
- [ ] Email displays correctly (not in spam)

### 2. Registration Flow Test
```
1. Go to: http://localhost/photo-web/auth/register.php
2. Fill Step 1: Personal Information
   - First Name: John
   - Last Name: Doe
   - (Other fields optional)
3. Click Next
4. Fill Step 2: Account Credentials
   - Username: johndoe123
   - Email: your-test-email@gmail.com
   - Password: TestPassword123
   - Confirm: TestPassword123
5. Click Register
```

- [ ] Registration completes without errors
- [ ] User created in database
- [ ] Confirmation email sent to inbox
- [ ] Email contains confirmation link
- [ ] Redirected to login page
- [ ] Session cleared (no auto-login)
- [ ] Click confirmation link works
- [ ] Email verified status updated
- [ ] Can see "Email verified" in settings

### 3. Login Without 2FA Test
```
1. Go to: http://localhost/photo-web/auth/login.php
2. Enter email: your-test-email@gmail.com
3. Enter password: TestPassword123
4. Click Login
```

- [ ] Login succeeds
- [ ] Redirected to home page
- [ ] Session set correctly
- [ ] Can access protected pages

### 4. 2FA Setup Test
```
1. Go to: http://localhost/photo-web/email-settings.php
2. Scroll to "Two-Factor Authentication"
3. Click "Enable 2FA"
```

- [ ] 2FA status changes to "Enabled"
- [ ] Success message displays
- [ ] User record updated in database

### 5. 2FA Login Test
```
1. Log out
2. Go to: http://localhost/photo-web/auth/login.php
3. Enter email and password
```

- [ ] Redirected to verify-2fa.php
- [ ] Email received with 6-digit code
- [ ] Code input field visible
- [ ] Enter 6-digit code
- [ ] Login completes
- [ ] Redirected to home page

### 6. Invalid 2FA Code Test
```
1. On verify-2fa.php page
2. Enter wrong code (e.g., 999999)
3. Click Verify
```

- [ ] Error message displays
- [ ] Correct error: "Invalid or expired 2FA code"
- [ ] Stay on verify-2fa page
- [ ] Can try again

### 7. 2FA Code Expiry Test
```
1. Get 2FA code via email
2. Wait 11+ minutes
3. Try to use expired code
```

- [ ] Code rejected
- [ ] Error message: "Invalid or expired 2FA code"
- [ ] User must log in again to get new code

### 8. Contact Form Test
```
1. Go to: http://localhost/photo-web/contact.php
2. Fill form:
   - Name: Test User
   - Email: sender@example.com
   - Subject: Test Message
   - Message: This is a test message
3. Click Send
```

- [ ] Success message displays
- [ ] Email received at Gmail address
- [ ] Email shows visitor info
- [ ] Can reply to sender

### 9. Email Confirmation Resend Test
```
1. Go to: http://localhost/photo-web/email-settings.php
2. If email not verified, click "Resend Confirmation Email"
```

- [ ] Success message displays
- [ ] New email arrives
- [ ] Old token invalidated
- [ ] Link in new email works

### 10. Settings Page Tests
```
1. Go to: http://localhost/photo-web/email-settings.php
2. Check all information displays
3. Test all buttons
```

- [ ] Email shows correctly
- [ ] Verification status accurate
- [ ] 2FA status accurate
- [ ] All buttons functional

## 📊 Database Verification

### Check Users Table
```sql
SELECT id, email, email_verified, two_fa_enabled FROM users WHERE email = 'test@example.com';
```
Expected: 1 row with correct values

### Check Email Confirmations
```sql
SELECT * FROM email_confirmations WHERE user_id = 1;
```
Expected: Token, expiry, and confirmed_at timestamp

### Check 2FA Table
```sql
SELECT * FROM two_factor_auth WHERE user_id = 1;
```
Expected: Code, created_at timestamp

### Check Password Resets
```sql
SELECT * FROM password_resets WHERE user_id = 1;
```
Expected: Token, expiry, used_at (null or timestamp)

## 🔍 Log Checking

### PHP Error Logs
```bash
# Check for any errors during tests
tail -100 /var/log/php-errors.log
# or
tail -100 /var/log/apache2/error.log
```

Expected: No errors related to email, database, or config

### Email Service Logs
Enable debug in `.env`:
```env
EMAIL_DEBUG=true
```

Check log output for:
- [ ] SMTP connection successful
- [ ] Authentication successful
- [ ] Email sent (250 OK response)

## 🚀 Staging Deployment

### 1. Configuration
- [ ] Copy `.env.example` to `.env` on staging
- [ ] Update credentials for staging Gmail account
- [ ] Update URLs in confirmation links (staging domain)

### 2. Database
- [ ] Run migrations on staging database
- [ ] Verify tables created
- [ ] Verify indexes created
- [ ] Check for any errors

### 3. Code Deployment
- [ ] Push all modified files to staging branch
- [ ] Verify file permissions (644 for PHP, 755 for dirs)
- [ ] Check `.env` NOT in repository
- [ ] Verify `.gitignore` includes `.env`

### 4. Testing on Staging
- [ ] Run all tests from Testing Procedures section
- [ ] Test with production-like data
- [ ] Test email with actual domain
- [ ] Verify confirmation links use staging domain

### 5. Performance Testing
- [ ] Test with 10 simultaneous registrations
- [ ] Monitor email queue (should be empty)
- [ ] Check memory usage
- [ ] Check response times

## 📤 Production Deployment

### Pre-Deployment
- [ ] All staging tests passed
- [ ] Backup production database
- [ ] Notify users of maintenance (if needed)
- [ ] Have rollback plan ready

### Deployment Steps
1. [ ] Merge staging to main branch
2. [ ] Pull latest code to production server
3. [ ] Copy `.env.example` to `.env` and update credentials
4. [ ] Run setup script: `php setup-gmail.php`
5. [ ] Verify database tables created
6. [ ] Run smoke tests on production

### Post-Deployment
- [ ] Monitor error logs for 1 hour
- [ ] Test registration with real user
- [ ] Verify emails sent and received
- [ ] Check email templates render correctly
- [ ] Monitor database performance
- [ ] Announce feature to users

## 🔒 Security Verification

### Before Going Live
- [ ] `.env` file not in repository
- [ ] `.env` permissions restricted (600)
- [ ] Tokens are hashed (SHA-256)
- [ ] Tokens expire correctly
- [ ] HTTPS enabled for all email links
- [ ] Rate limiting considered
- [ ] SQL injection tests passed
- [ ] XSS tests passed
- [ ] CSRF tokens on all forms

### Ongoing Monitoring
- [ ] Monitor failed login attempts
- [ ] Monitor invalid 2FA codes
- [ ] Check for spam registrations
- [ ] Review email logs regularly
- [ ] Update security dependencies

## 📞 Troubleshooting During Tests

### Email Not Sending
1. [ ] Check `.env` has correct credentials
2. [ ] Verify Gmail app password generated correctly
3. [ ] Check 2-Step Verification enabled
4. [ ] Enable `EMAIL_DEBUG=true` in `.env`
5. [ ] Check firewall allows port 587
6. [ ] Review email logs

### Database Errors
1. [ ] Run `php setup-gmail.php` to create tables
2. [ ] Check database connection works
3. [ ] Verify user has proper permissions
4. [ ] Check for existing tables/columns

### Link Not Working
1. [ ] Verify domain in confirmation link
2. [ ] Check token in database
3. [ ] Ensure link syntax is correct
4. [ ] Test with direct database query

### 2FA Code Not Working
1. [ ] Verify code format (6 digits)
2. [ ] Check code not expired (10 min window)
3. [ ] Verify email delivery
4. [ ] Check database code table

## ✅ Final Sign-Off

- [ ] All tests passed
- [ ] Documentation complete
- [ ] Team trained on new features
- [ ] Support tickets ready for users
- [ ] Monitoring/alerting configured
- [ ] Rollback procedure documented
- [ ] User communication prepared

---

**Ready for Production**: _______________  
**Deployment Date**: _______________  
**Deployed By**: _______________  
**Verified By**: _______________
