# LensCraft Photography Platform

Professional photography community platform built with PHP, MySQL, and modern web technologies.

---

## 📖 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Getting Started](#getting-started)
- [Project Structure](#project-structure)
- [Documentation](#documentation)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

---

## Overview

**LensCraft** is a full-featured photography sharing platform that allows users to:
- Upload and showcase their photography portfolio
- Engage with the community through likes and comments
- Follow other photographers to discover new work
- Manage their profiles and account settings
- Secure their accounts with Two-Factor Authentication

### Technology Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP 8.2 |
| **Database** | MySQL 10.4 / MariaDB |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Web Server** | Apache 2.4 |
| **Server Environment** | XAMPP |
| **Email** | PHPMailer + Gmail SMTP |

### Live Features

✅ User registration with email verification  
✅ Two-factor authentication (2FA)  
✅ Photo upload with client-side editing  
✅ Like, comment, and follow functionality  
✅ User profiles with customizable information  
✅ Notification system  
✅ Contact form with email delivery  
✅ Admin dashboard  
✅ Activity logging and auditing  
✅ Responsive design  

---

## Features

### User Features

**Authentication**
- Registration with email verification
- Secure login with password hashing (bcrypt)
- Two-factor authentication (optional)
- Password reset functionality
- Session management

**Photo Management**
- Upload photos with metadata (JPG, PNG, GIF, WebP)
- Client-side photo editor (crop, rotate, adjust)
- Photo titles and descriptions
- Delete photos (admin managed)
- Image file storage in `/uploads/`

**Social Engagement**
- Like/unlike photos
- Leave comments on photos
- Follow/unfollow photographers
- View follower/following lists
- Engagement metrics

**User Profiles**
- Customizable profile information
- Profile pictures and cover photos
- Bio and social media links
- Privacy settings (public/private profile)
- Activity history

**Notifications**
- Real-time notification system
- Likes, comments, and follows notifications
- Mark as read/unread
- Delete notifications
- Notification count badge

### Admin Features

**Dashboard**
- System statistics and overview
- User management
- Photo moderation
- Comment management
- Activity logging
- Contact message management

**Moderation**
- Delete inappropriate photos
- Remove comments
- Manage user accounts
- View audit logs
- Contact form responses

---

## Getting Started

### System Requirements

- PHP 8.2 or higher
- MySQL 5.7 or MariaDB 10.4
- Apache 2.4
- Composer (for dependencies)
- 50MB+ disk space for uploads
- SMTP access (Gmail recommended)

### Installation Steps

#### 1. Database Setup

```bash
# Create database
mysql -u root -p
> CREATE DATABASE photography_website;
> EXIT;

# Import schema
mysql -u root -p photography_website < db/photography_website.sql

# Run migrations
php migrations/add_email_2fa.php
```

#### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Edit .env with your credentials
nano .env
```

**.env Configuration:**
```
GMAIL_ADDRESS=your-email@gmail.com
GMAIL_APP_PASSWORD=xxxx-xxxx-xxxx-xxxx
```

**Get Gmail App Password:**
1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" and "Windows Computer"
3. Copy the 16-character password
4. Paste into .env

#### 3. Install Dependencies

```bash
# Install PHPMailer and other dependencies
composer install
```

#### 4. File Permissions

```bash
# Create uploads directory
mkdir -p uploads/
chmod 755 uploads/

# Set correct permissions
chmod 644 config/*.php
chmod 644 *.php
```

#### 5. Verify Installation

```bash
# Run health check
php health-check.php

# Test SMTP configuration
php test-gmail-smtp.php

# Test database connection
php test-migration.php
```

#### 6. Access the Application

```
Browser: http://localhost/photo-web/
Admin:   http://localhost/photo-web/admin/
```

**Default Admin Credentials:**
- Username: `admin`
- Email: `admin@lenscraft.com`
- Password: (see admin setup)

---

## Project Structure

```
photo-web/
├── auth/                          # Authentication pages
│   ├── register.php              # User registration
│   ├── login.php                 # User login
│   ├── logout.php                # Logout handler
│   └── forgot-password.php        # Password recovery
│
├── admin/                         # Admin panel
│   ├── index.php                 # Admin dashboard
│   ├── admin_users.php            # User management
│   ├── admin_photos.php           # Photo management
│   ├── admin_comments.php         # Comment moderation
│   ├── admin_logs.php             # Activity logs
│   ├── admin_settings.php         # Site configuration
│   ├── sidebar.php                # Admin navigation
│   └── rbac.php                   # Role-based access control
│
├── api/                           # API endpoints (AJAX)
│   └── follow_ajax.php            # Follow/unfollow endpoint
│
├── config/                        # Configuration & Services
│   ├── EmailService.php           # Email delivery (PHPMailer)
│   ├── EmailConfirmationService.php # Email verification
│   ├── TwoFactorAuthService.php   # 2FA implementation
│   ├── email_config.php           # SMTP configuration
│   ├── env_loader.php             # Environment loader
│   └── settings.php               # Site settings
│
├── db/                            # Database files
│   ├── photography_website.sql    # Initial schema
│   └── migration_*.sql            # Migrations
│
├── email_templates/               # HTML email templates
│   ├── email_confirmation.html    # Registration email
│   ├── 2fa_code.html              # 2FA code email
│   ├── contact_notification.html  # Contact form email
│   └── password_reset.html        # Password reset email
│
├── migrations/                    # Database migrations
│   └── add_email_2fa.php          # Email & 2FA setup
│
├── uploads/                       # User-uploaded photos
│   └── [photo files]
│
├── assets/                        # Static files
│   ├── css/
│   │   ├── style.css              # Main stylesheet
│   │   ├── home.css               # Gallery styles
│   │   ├── profile.css            # Profile styles
│   │   ├── upload.css             # Upload form
│   │   └── admin.css              # Admin panel
│   └── js/
│       ├── editor.js              # Photo editor
│       └── script.js              # Common functions
│
├── Core Files
│   ├── index.php                  # Homepage
│   ├── home.php                   # Gallery/feed
│   ├── profile.php                # User profile
│   ├── upload.php                 # Photo upload
│   ├── search.php                 # Search functionality
│   ├── contact.php                # Contact form
│   ├── settings.php               # User settings
│   ├── email-settings.php         # Email & 2FA settings
│   ├── verify-2fa.php             # 2FA verification
│   ├── confirm-email.php          # Email confirmation
│   └── view_profile.php           # Profile viewing
│
├── AJAX Endpoints
│   ├── like_ajax.php              # Like/unlike handler
│   ├── comment_ajax.php           # Comment handler
│   ├── notification_ajax.php      # Notification handler
│   └── view_comments.php          # Comments display
│
├── Utilities
│   ├── db_connect.php             # Database connection
│   ├── logger.php                 # Activity logger
│   ├── notification_manager.php   # Notification management
│   ├── header.php                 # Page header template
│   ├── header_logged_in.php        # Logged-in header
│   └── footer_logged_in.php        # Page footer
│
├── Configuration Files
│   ├── .env                       # Environment variables (gitignored)
│   ├── .env.example               # Environment template
│   ├── composer.json              # PHP dependencies
│   └── vendor/                    # Composer packages
│
└── Documentation
    ├── README.md                  # This file
    ├── USER_GUIDE.md              # User documentation
    ├── QUICK_START.md             # Getting started guide
    ├── TECHNICAL_ARCHITECTURE.md  # System architecture
    ├── API_REFERENCE.md           # API endpoints
    └── DATA_MODELS.md             # Database schema
```

---

## Documentation

### User Documentation

- **[USER_GUIDE.md](USER_GUIDE.md)** - Complete user manual covering all features
- **[QUICK_START.md](QUICK_START.md)** - 5-minute getting started guide

### Developer Documentation

- **[TECHNICAL_ARCHITECTURE.md](TECHNICAL_ARCHITECTURE.md)** - System design and architecture
- **[API_REFERENCE.md](API_REFERENCE.md)** - Complete API endpoint documentation
- **[DATA_MODELS.md](DATA_MODELS.md)** - Database schema and relationships
- **[CONFIG_README.md](CONFIG_README.md)** - Configuration guide
- **[SERVICES_README.md](SERVICES_README.md)** - Service classes documentation
- **[CODE_STYLE_GUIDE.md](CODE_STYLE_GUIDE.md)** - Coding standards

---

## Development

### Code Organization

#### Service Classes (`/config/`)

Service classes handle business logic:

```php
// Email delivery
$emailService = new EmailService();
$emailService->sendEmailConfirmation($email, $name, $link);

// 2FA codes
$twoFAService = new TwoFactorAuthService($pdo);
$code = $twoFAService->generateCode($user_id);

// Email verification
$confirmService = new EmailConfirmationService($pdo);
$token = $confirmService->generateToken($user_id);
```

#### AJAX Endpoints

AJAX endpoints handle dynamic requests:

```javascript
// Like a photo
fetch('/like_ajax.php?id=42')
    .then(r => r.json())
    .then(data => console.log(data));

// Post a comment
fetch('/comment_ajax.php', {
    method: 'POST',
    body: new FormData(commentForm)
})
```

#### Database Access

Always use prepared statements:

```php
$stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ?");
$stmt->execute([$photo_id]);
$photo = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Common Development Tasks

**Adding a New Feature**

1. Create the database table (if needed)
2. Write the service class
3. Create the page/endpoint
4. Add HTML/CSS/JS
5. Write tests
6. Update documentation

**Debugging**

```php
// Log to PHP error log
error_log("Debug message: " . print_r($data, true));

// Check log file
tail -f /var/log/php-errors.log

// Enable debug mode in email_config.php
define('EMAIL_DEBUG', true);
```

**Database Debugging**

```bash
# Connect to MySQL
mysql -u root -p photography_website

# Run queries
SELECT * FROM users WHERE id = 5;
SELECT COUNT(*) FROM photos;
SHOW TABLES;

# Check indexes
SHOW INDEX FROM photos;

# Monitor slow queries
SET GLOBAL slow_query_log = 'ON';
```

### Testing Checklist

- [ ] Registration flow (email verification)
- [ ] Login with/without 2FA
- [ ] Photo upload
- [ ] Photo edit in client
- [ ] Like/unlike functionality
- [ ] Comment posting
- [ ] Follow/unfollow
- [ ] Notifications
- [ ] Search functionality
- [ ] Profile updates
- [ ] Settings changes
- [ ] Contact form
- [ ] Admin functions

---

## Troubleshooting

### Common Issues

**1. Email Not Sending**

```bash
# Test SMTP configuration
php test-gmail-smtp.php

# Check credentials in .env
cat .env | grep GMAIL

# Check error log
tail -20 /var/log/php-errors.log

# Verify Gmail settings
# - 2-Step Verification enabled?
# - App Password generated?
# - Correct password in .env?
```

**2. Database Connection Error**

```bash
# Check credentials in db_connect.php
nano db_connect.php

# Test connection
php test-migration.php

# Verify database exists
mysql -u root -p
> SHOW DATABASES;
> USE photography_website;
> SHOW TABLES;
```

**3. Photo Upload Failing**

```bash
# Check permissions
ls -la uploads/
chmod 755 uploads/

# Check file size limit in php.ini
grep -E "upload_max_filesize|post_max_size" /etc/php/8.2/apache2/php.ini

# Check disk space
df -h

# Check PHP error log
tail -20 /var/log/php-errors.log
```

**4. 2FA Code Not Received**

```bash
# Test email service
php test-gmail-smtp.php

# Check 2FA table exists
mysql -u root -p photography_website
> SELECT * FROM two_factor_auth;

# Check email_confirmations table
> SELECT * FROM email_confirmations;

# Run migration if needed
php migrations/add_email_2fa.php
```

**5. Login Loop / Session Issues**

```php
// Clear session
session_start();
session_destroy();

// Check session directory
ls -la /var/lib/php/sessions/

// Check session.save_path in php.ini
grep session.save_path /etc/php/8.2/apache2/php.ini
```

---

## Performance Optimization

### Current Optimizations

- ✅ Database connection pooling
- ✅ Prepared statements (SQL injection prevention)
- ✅ Query optimization with joins
- ✅ Pagination (12 items per page)
- ✅ Image compression before upload
- ✅ Caching static assets

### Recommended Enhancements

1. **Enable Browser Caching**
   ```php
   header('Cache-Control: public, max-age=3600');
   ```

2. **Add Database Indexes**
   ```sql
   ALTER TABLE photos ADD INDEX (user_id);
   ALTER TABLE likes ADD INDEX (photo_id);
   ```

3. **Implement Redis Caching**
   - Cache user profiles
   - Cache notification counts
   - Cache sidebar data

4. **Image Optimization**
   - Generate thumbnails
   - WebP conversion
   - CDN integration

5. **Code Minification**
   - Minify CSS/JS
   - Remove comments in production
   - Gzip compression

---

## Security Best Practices

### Implemented Security

✅ Password hashing (bcrypt)  
✅ Prepared statements (SQL injection prevention)  
✅ Input sanitization  
✅ Session-based authentication  
✅ Email verification  
✅ Two-factor authentication  
✅ CSRF tokens (via sessions)  
✅ Activity logging  
✅ Admin role-based access control  

### Recommended Additions

1. **Rate Limiting**
   ```php
   // Prevent brute force attacks
   if (attempts > 5 in 15 minutes) {
       block_login();
   }
   ```

2. **API Authentication**
   - Implement JWT tokens
   - API key management
   - CORS headers

3. **HTTPS Enforcement**
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

4. **Security Headers**
   ```php
   header('X-Content-Type-Options: nosniff');
   header('X-Frame-Options: SAMEORIGIN');
   header('X-XSS-Protection: 1; mode=block');
   ```

5. **Regular Updates**
   - Keep PHP updated
   - Update dependencies
   - Monitor security advisories

---

## Deployment

### Production Checklist

- [ ] Database backed up
- [ ] .env configured with production credentials
- [ ] HTTPS enabled
- [ ] Security headers added
- [ ] Error reporting disabled
- [ ] Admin password changed
- [ ] File permissions set correctly
- [ ] Email service tested
- [ ] Database migrations run
- [ ] Caching enabled
- [ ] Monitoring set up
- [ ] Logs configured

### Server Configuration

**Apache Configuration:**
```apache
<VirtualHost *:443>
    ServerName lenscraft.com
    DocumentRoot /var/www/html/photo-web
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/cert.pem
    SSLCertificateKeyFile /etc/ssl/private/key.pem
    
    <Directory /var/www/html/photo-web>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**PHP Configuration:**
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 256M
```

---

## Support

### Getting Help

1. **Check Documentation**
   - User Guide: [USER_GUIDE.md](USER_GUIDE.md)
   - Technical Docs: [TECHNICAL_ARCHITECTURE.md](TECHNICAL_ARCHITECTURE.md)
   - API Reference: [API_REFERENCE.md](API_REFERENCE.md)

2. **Run Diagnostics**
   ```bash
   php health-check.php          # System health
   php test-migration.php        # Database
   php test-gmail-smtp.php       # Email
   ```

3. **Check Logs**
   ```bash
   # PHP errors
   tail -100 /var/log/php-errors.log
   
   # Activity logs
   # In database: user_logs table
   ```

4. **Contact Support**
   - Use contact form on website
   - Email: admin@lenscraft.com
   - Check admin dashboard for messages

---

## License

LensCraft Photography Platform  
© 2024 All Rights Reserved

---

## Contributing

Contributions are welcome! Please:

1. Follow the [Code Style Guide](CODE_STYLE_GUIDE.md)
2. Write docstrings for all functions
3. Add inline comments for complex logic
4. Update documentation
5. Test thoroughly

---

**Last Updated:** December 2024  
**Version:** 1.0  
**Maintainer:** Development Team
