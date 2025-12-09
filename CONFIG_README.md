# Configuration Guide

Complete configuration documentation for LensCraft.

---

## Table of Contents

1. [Environment Variables](#environment-variables)
2. [Email Configuration](#email-configuration)
3. [Database Configuration](#database-configuration)
4. [Server Configuration](#server-configuration)
5. [Security Settings](#security-settings)
6. [Email Templates](#email-templates)

---

## Environment Variables

### .env File Location

```
photo-web/
├── .env              ← Your configuration (never commit!)
├── .env.example      ← Template with examples
└── .gitignore        ← Includes .env
```

### Setup Instructions

**Step 1: Copy Template**
```bash
cp .env.example .env
```

**Step 2: Edit Configuration**
```bash
nano .env
```

**Step 3: Add Your Credentials**
```env
# Gmail SMTP
GMAIL_ADDRESS=your-email@gmail.com
GMAIL_APP_PASSWORD=xxxx-xxxx-xxxx-xxxx

# Optional: Database (if not using localhost/root)
DB_HOST=localhost
DB_USER=root
DB_PASS=password
DB_NAME=photography_website
```

### Available Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `GMAIL_ADDRESS` | Email for sending messages | `noreply@gmail.com` |
| `GMAIL_APP_PASSWORD` | Gmail app password (16 chars) | `xxxx-xxxx-xxxx-xxxx` |
| `DB_HOST` | Database host | `localhost` |
| `DB_USER` | Database username | `root` |
| `DB_PASS` | Database password | `password123` |
| `DB_NAME` | Database name | `photography_website` |

---

## Email Configuration

### Gmail Setup (Recommended)

Gmail is the recommended email provider due to high deliverability.

**Step 1: Enable 2-Step Verification**

1. Go to https://myaccount.google.com/
2. Click "Security" in left sidebar
3. Scroll to "2-Step Verification"
4. Click "Get Started"
5. Follow Google's verification process

**Step 2: Generate App Password**

1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" from dropdown
3. Select "Windows Computer" (or your OS)
4. Click "Generate"
5. Copy the 16-character password
6. Paste into `.env` as `GMAIL_APP_PASSWORD`

**Step 3: Update .env**

```env
GMAIL_ADDRESS=your-email@gmail.com
GMAIL_APP_PASSWORD=wxyz-wxyz-wxyz-wxyz
```

**Step 4: Test Configuration**

```bash
php test-gmail-smtp.php
```

Expected output:
```
✓ SMTP Connection: Success
✓ Authentication: Success
✓ Test Email Sent: Success
```

### Email Configuration File

**Location:** `config/email_config.php`

```php
<?php
// SMTP Server Settings
define('GMAIL_SMTP_HOST', 'smtp.gmail.com');      // Gmail SMTP server
define('GMAIL_SMTP_PORT', 587);                   // TLS port
define('GMAIL_SMTP_ENCRYPTION', 'tls');           // TLS encryption

// Gmail Credentials (from .env)
define('GMAIL_ADDRESS', getenv('GMAIL_ADDRESS'));
define('GMAIL_APP_PASSWORD', getenv('GMAIL_APP_PASSWORD'));

// Email Headers
define('FROM_EMAIL', GMAIL_ADDRESS);              // Sender email
define('FROM_NAME', 'LensCraft Photography');     // Sender name
define('REPLY_TO_EMAIL', GMAIL_ADDRESS);          // Reply-to address

// Email Templates
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../email_templates/');

// Debugging (disable in production)
define('EMAIL_DEBUG', false);
```

### Email Service Usage

**Sending Email Confirmation:**
```php
require_once 'config/EmailService.php';

$service = new EmailService();
$success = $service->sendEmailConfirmation(
    'user@example.com',
    'John Doe',
    'https://site.com/confirm-email.php?token=abc123'
);

if ($success) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email";
    print_r($service->getErrors());
}
```

**Sending 2FA Code:**
```php
$service = new EmailService();
$success = $service->send2FACode(
    'user@example.com',
    'John Doe',
    '123456'
);
```

### Troubleshooting Email Issues

**Issue: "SMTP authentication failed"**
- Verify app password is correct (not regular password)
- Check 2-Step Verification is enabled
- Ensure 16-character password (with dashes)

**Issue: "Connection refused"**
- Check internet connection
- Verify SMTP server: smtp.gmail.com
- Check firewall allows port 587

**Issue: "Emails going to spam"**
- Add SPF record to DNS
- Add DKIM signature
- Use a verified domain
- Clean email list (remove bounces)

---

## Database Configuration

### MySQL/MariaDB Setup

**Location:** `db_connect.php`

**Default Configuration:**
```php
$host = 'localhost';
$dbname = 'photography_website';
$db_username = 'root';
$db_password = '';
```

### Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE photography_website;
CREATE USER 'photo_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON photography_website.* TO 'photo_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Initialize Schema

```bash
# Import initial schema
mysql -u photo_user -p photography_website < db/photography_website.sql

# Run migrations
php migrations/add_email_2fa.php

# Verify tables
mysql -u photo_user -p photography_website
> SHOW TABLES;
> DESC users;
> EXIT;
```

### Connection Configuration

**For Local Development:**
```php
$host = 'localhost';
$db_username = 'root';
$db_password = '';
```

**For Production Server:**
```php
$host = 'db.example.com';
$db_username = 'photo_user';
$db_password = 'strong_password_here';
```

### Database Backup

**Backup Database:**
```bash
mysqldump -u root -p photography_website > backup_2024-12-09.sql
```

**Restore Database:**
```bash
mysql -u root -p photography_website < backup_2024-12-09.sql
```

**Backup with Credentials:**
```bash
mysqldump -u photo_user -ppassword photography_website > backup.sql
```

---

## Server Configuration

### Apache Configuration

**Enable Modules:**
```bash
a2enmod rewrite          # URL rewriting
a2enmod ssl              # HTTPS support
a2enmod headers          # Custom headers
a2enmod proxy_fcgi       # FastCGI
```

**Virtual Host (.htaccess):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect to HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Block access to sensitive files
    RewriteRule ^(config|migrations|db)/ - [F]
    RewriteRule ^\.env$ - [F]
    RewriteRule ^\.git - [F]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### PHP Configuration

**File:** `/etc/php/8.2/apache2/php.ini`

**Critical Settings:**
```ini
; Display settings (NEVER show errors in production!)
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
error_reporting = E_ALL

; File uploads
upload_max_filesize = 50M
post_max_size = 50M
file_uploads = On
upload_tmp_dir = /tmp

; Performance
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

; Session
session.save_path = /var/lib/php/sessions
session.gc_maxlifetime = 86400
session.cookie_httponly = On
session.cookie_secure = On        ; HTTPS only
session.cookie_samesite = Strict

; Database
mysqli.max_connections = 100
default_charset = "utf-8"
```

### Restart Services

```bash
# Restart Apache
sudo systemctl restart apache2

# Verify Apache
sudo apache2ctl -t      # Should output "Syntax OK"

# Check PHP version
php -v

# Check loaded modules
php -m | grep pdo
```

---

## Security Settings

### File Permissions

```bash
# Make uploads directory writable
chmod 755 uploads/

# Restrict config files
chmod 644 config/*.php

# Restrict database credentials
chmod 600 db_connect.php
chmod 600 .env

# Allow only reading templates
chmod 644 email_templates/*.html

# Restrict sensitive directories
chmod 000 migrations/
chmod 000 db/
```

### Disable Directory Listing

```apache
<IfModule mod_autoindex.c>
    Options -Indexes
</IfModule>
```

### Block Direct Access to Sensitive Files

```apache
<FilesMatch "\.php$">
    <IfModule mod_fcgid.c>
        SetHandler fcgid-script
    </IfModule>
    <IfModule mod_ssl.c>
        # Require SSL
    </IfModule>
</FilesMatch>

# Block .env files
<Files ".env*">
    Order allow,deny
    Deny from all
</Files>

# Block git directory
<DirectoryMatch "^/.git">
    Order allow,deny
    Deny from all
</DirectoryMatch>
```

### HTTPS Configuration

**Generate Self-Signed Certificate (Testing):**
```bash
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365
```

**Use Let's Encrypt (Production):**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Generate certificate
sudo certbot certonly --apache -d example.com

# Auto-renew
sudo certbot renew --dry-run
```

**Enable HTTPS Redirect:**
```php
// In header.php or config file
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

---

## Email Templates

### Template System

Email templates are stored in `/email_templates/` as HTML files.

**Template Variables:**
- `{{variable_name}}` - Replaced with actual values

**Example Template:**
```html
<!-- email_templates/welcome.html -->
<html>
<body>
    <h1>Welcome {{user_name}}!</h1>
    <p>Thank you for joining {{site_name}}.</p>
    <p>Click here to verify: <a href="{{verification_link}}">Verify Email</a></p>
</body>
</html>
```

### Available Templates

**1. Email Confirmation** (`email_confirmation.html`)
- Variables: `{{user_name}}`, `{{confirmation_link}}`, `{{site_name}}`
- Used: User registration
- Sent to: User's registered email

**2. 2FA Code** (`2fa_code.html`)
- Variables: `{{2fa_code}}`, `{{user_name}}`
- Used: Login with 2FA
- Sent to: User's registered email

**3. Contact Notification** (`contact_notification.html`)
- Variables: `{{name}}`, `{{email}}`, `{{message}}`, `{{subject}}`
- Used: Contact form submission
- Sent to: Admin email

**4. Password Reset** (`password_reset.html`)
- Variables: `{{user_name}}`, `{{reset_link}}`
- Used: Password recovery
- Sent to: User's email

### Customizing Templates

**Step 1: Edit HTML File**
```bash
nano email_templates/email_confirmation.html
```

**Step 2: Update Variables**
```html
<!-- Change sender name, colors, links, etc -->
<p>Welcome to {{site_name}}!</p>
```

**Step 3: Test Email**
```bash
php test-gmail-smtp.php
```

### Troubleshooting Template Issues

**Issue: Variables not being replaced**
- Ensure syntax is `{{variable_name}}`
- Check variable name matches in code
- Verify template file exists

**Issue: Email not formatted correctly**
- Check HTML syntax
- Ensure closing tags
- Test in email client

**Issue: Links broken in email**
- Use absolute URLs: `https://example.com/...`
- Test links in email client
- Check domain is correct

---

## Admin Configuration

### Default Admin Account

**File:** `admin/admin_login.php`

**Default Credentials:**
```
Username: admin
Email: admin@lenscraft.com
Password: (set during setup)
```

**Change Admin Password:**

1. Go to Admin Dashboard
2. Click Settings
3. Enter old password
4. Enter new password
5. Click Save

**Or via Database:**
```bash
# Generate new hash
php -r "echo password_hash('newpassword', PASSWORD_BCRYPT);"

# Update database
mysql -u root -p photography_website
> UPDATE admins SET password='<new_hash>' WHERE username='admin';
```

### Admin Settings

**File:** `admin/admin_settings.php`

Admins can configure:
- Site name and tagline
- Contact email
- Contact phone
- Admin email
- Email templates
- User permissions
- Photo settings
- Comment moderation

---

## Monitoring & Logging

### Application Logs

**PHP Error Log:**
```bash
tail -100 /var/log/php-errors.log
```

**Activity Log (Database):**
```sql
SELECT * FROM user_logs ORDER BY created_at DESC LIMIT 50;
```

**Query Log:**
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### Monitoring Checklist

- [ ] Monitor disk space: `df -h`
- [ ] Monitor database size: `du -sh uploads/`
- [ ] Check error logs daily
- [ ] Monitor email delivery
- [ ] Check SMTP connectivity
- [ ] Monitor database performance

---

## Environment-Specific Configuration

### Development

```env
ENVIRONMENT=development
DEBUG=true
EMAIL_DEBUG=true
DISPLAY_ERRORS=true
```

### Production

```env
ENVIRONMENT=production
DEBUG=false
EMAIL_DEBUG=false
DISPLAY_ERRORS=false
REQUIRE_HTTPS=true
```

---

**Last Updated:** December 2024
**Configuration Version:** 1.0
