# Gmail Integration - Architecture & Diagrams

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     LensCraft Photo Web                          │
│                   Gmail Integration System                       │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────┐         ┌──────────────────────┐
│   User Browser       │◄───────►│   PHP Application    │
│                      │         │                      │
│ - Register Form      │         │ - register.php       │
│ - Login Form         │         │ - login.php          │
│ - Email Confirmation │         │ - contact.php        │
│ - 2FA Verification   │         │ - email-settings.php │
└──────────────────────┘         └──────────────────────┘
                                         │
                        ┌────────────────┼────────────────┐
                        │                │                │
                   ┌────▼──────┐  ┌─────▼────┐  ┌────────▼────┐
                   │  EmailService  │  2FAService  │  ConfirmService │
                   │  (PHPMailer)   │  (Database)  │  (Database)    │
                   └────┬──────┘  └─────┬────┘  └────────┬────┘
                        │                │                │
                   ┌────▼──────────────────▼────────────────▼────┐
                   │          Gmail SMTP Server                  │
                   │      (smtp.gmail.com:587 TLS)              │
                   └────┬───────────────────────────────────────┘
                        │
                   ┌────▼──────┐
                   │   Gmail    │
                   │   Inbox    │
                   └────────────┘

                        Database Layer
                   ┌────────────────────┐
                   │     MySQL DB       │
                   ├────────────────────┤
                   │ users              │
                   │ email_confirmations│
                   │ password_resets    │
                   │ two_factor_auth    │
                   └────────────────────┘
```

## Registration Flow

```
START
  │
  ├─► User visits /auth/register.php
  │
  ├─► Step 1: Personal Information
  │   ├─ First Name, Last Name
  │   ├─ Phone, Birthday, Address
  │   └─ Data stored in SESSION
  │
  ├─► Step 2: Account Credentials
  │   ├─ Username validation
  │   ├─ Email validation (unique)
  │   ├─ Password validation
  │   └─ Data stored in SESSION
  │
  ├─► User Creation
  │   ├─ Hash password
  │   ├─ Insert into DB
  │   ├─ Get user_id from lastInsertId()
  │   └─ Clear SESSION data
  │
  ├─► Email Confirmation
  │   ├─ EmailConfirmationService::generateToken()
  │   │  ├─ Create random token
  │   │  └─ Hash with SHA-256
  │   │
  │   ├─ Build confirmation link
  │   │  └─ /confirm-email.php?token=XXX
  │   │
  │   └─ EmailService::sendEmailConfirmation()
  │      ├─ Load HTML template
  │      ├─ Replace {{variables}}
  │      ├─ Send via Gmail SMTP
  │      └─ Log any errors
  │
  ├─► Redirect to Login
  │   └─ Show success message
  │
  └─► END - User checks email

LATER:
  ├─► User clicks email link
  │   └─ /confirm-email.php?token=XXX
  │
  ├─► Verify Token
  │   ├─ Get token from URL
  │   ├─ Hash token for comparison
  │   ├─ Query email_confirmations table
  │   ├─ Check token exists
  │   ├─ Check token not expired
  │   └─ Get user_id
  │
  ├─► Confirm Email
  │   ├─ Update email_confirmations.confirmed_at
  │   ├─ Update users.email_verified = 1
  │   └─ Delete confirmation token
  │
  └─► Show Success Message & Redirect to Login
```

## Login with 2FA Flow

```
START
  │
  ├─► User visits /auth/login.php
  │
  ├─► Enter Email/Username & Password
  │
  ├─► Validate Input
  │   ├─ Check not empty
  │   └─ Basic validation
  │
  ├─► Database Lookup
  │   ├─ SELECT FROM users
  │   ├─ WHERE email = ? OR username = ?
  │   └─ Get user record
  │
  ├─► Password Verification
  │   ├─ password_verify(input, hash)
  │   ├─ If FAIL:
  │   │  ├─ Log failed login
  │   │  ├─ Show error
  │   │  └─ REDIRECT to login.php
  │   │
  │   └─ If SUCCESS: Continue
  │
  ├─► Check 2FA Status
  │   ├─ TwoFactorAuthService::is2FAEnabled(user_id)
  │   ├─ SELECT two_fa_enabled FROM users WHERE id = ?
  │   │
  │   ├─ If 2FA DISABLED:
  │   │  ├─ Set SESSION user_id
  │   │  ├─ Log successful login
  │   │  ├─ REDIRECT to /home.php
  │   │  └─ END - Login Complete
  │   │
  │   └─ If 2FA ENABLED: Continue
  │
  ├─► Generate 2FA Code
  │   ├─ TwoFactorAuthService::generateCode(user_id)
  │   ├─ Generate random(0-999999)
  │   ├─ Pad to 6 digits
  │   ├─ Hash with SHA-256
  │   ├─ Insert into two_factor_auth table
  │   └─ Return code for email
  │
  ├─► Send 2FA Email
  │   ├─ EmailService::send2FACode()
  │   ├─ Load 2fa_code.html template
  │   ├─ Replace {{2fa_code}} with code
  │   ├─ Send via Gmail SMTP
  │   └─ Log email status
  │
  ├─► Store in Session
  │   ├─ $_SESSION['pending_2fa_user_id'] = user_id
  │   └─ $_SESSION['pending_2fa_email'] = email
  │
  ├─► Redirect to 2FA Page
  │   └─ header('Location: ../verify-2fa.php')
  │
  │
  ├─► REDIRECT TO: /verify-2fa.php
  │   │
  │   ├─► Check Session
  │   │   ├─ Verify pending_2fa_user_id exists
  │   │   └─ If not, redirect to login
  │   │
  │   ├─► Display Code Input Form
  │   │   ├─ 6-digit numeric input
  │   │   ├─ inputmode="numeric"
  │   │   ├─ pattern="[0-9]{6}"
  │   │   └─ maxlength="6"
  │   │
  │   ├─► User Enters Code
  │   │   └─ e.g., 123456
  │   │
  │   ├─► Verify Code
  │   │   ├─ Get user_id from SESSION
  │   │   ├─ Get code from POST
  │   │   ├─ Hash code with SHA-256
  │   │   ├─ Query two_factor_auth table
  │   │   ├─ Check: code = hashed_input
  │   │   ├─ Check: user_id matches
  │   │   ├─ Check: created_at > NOW()-10min
  │   │   │
  │   │   ├─ If FAIL:
  │   │   │  ├─ Show error message
  │   │   │  └─ Stay on verify-2fa.php
  │   │   │
  │   │   └─ If SUCCESS: Continue
  │   │
  │   ├─► Complete Login
  │   │   ├─ Delete 2FA code from table
  │   │   ├─ Set SESSION user_id
  │   │   ├─ Set SESSION username
  │   │   ├─ Set SESSION email
  │   │   ├─ Unset pending_2fa_user_id
  │   │   └─ Log successful login
  │   │
  │   └─► Redirect to Home
  │       └─ REDIRECT to /home.php
  │
  └─► END - Login Complete with 2FA
```

## Email Flow

```
┌──────────────────────────────────────────────────────┐
│         Application initiates email send             │
└──────┬───────────────────────────────────────────────┘
       │
       ├─► EmailService class instantiated
       │   └─ configureSMTP() method called
       │
       ├─► SMTP Configuration
       │   ├─ Host: smtp.gmail.com
       │   ├─ Port: 587
       │   ├─ Encryption: TLS
       │   ├─ Username: GMAIL_ADDRESS from .env
       │   └─ Password: GMAIL_APP_PASSWORD from .env
       │
       ├─► Load Email Template
       │   ├─ Check email_templates/ directory
       │   ├─ Load HTML file (e.g., email_confirmation.html)
       │   ├─ OR use fallback template
       │   └─ Replace {{variables}} with values
       │
       ├─► Build Email
       │   ├─ setFrom(FROM_EMAIL, FROM_NAME)
       │   ├─ addAddress(recipient_email, recipient_name)
       │   ├─ addReplyTo(REPLY_TO_EMAIL)
       │   ├─ setSubject(subject_line)
       │   ├─ isHTML(true)
       │   ├─ Body = HTML content
       │   └─ AltBody = plain text version
       │
       ├─► Send Email
       │   ├─ $mailer->send()
       │   │
       │   ├─ SMTP Connection
       │   │  ├─ Connect to Gmail servers
       │   │  ├─ Authenticate with app password
       │   │  └─ Check credentials
       │   │
       │   ├─ Send Message
       │   │  ├─ Transmit email data
       │   │  ├─ Wait for 250 OK response
       │   │  └─ Confirm delivery
       │   │
       │   └─ Handle Response
       │      ├─ If SUCCESS (250 OK):
       │      │  └─ Return true
       │      │
       │      └─ If FAIL:
       │         ├─ Capture error message
       │         ├─ Log to errors array
       │         └─ Return false
       │
       ├─► Handle Errors (if any)
       │   ├─ Store in errors array
       │   ├─ Log to error_log()
       │   └─ Return error to caller
       │
       └─► Email in User Inbox
           ├─ Gmail receives email
           ├─ Spam filtering applied
           ├─ User receives notification
           └─ User can click links
```

## 2FA Code Lifecycle

```
CODE GENERATION:
┌─────────────────────────────────────┐
│ generateCode(user_id)               │
│                                     │
│ 1. Generate: random_int(0, 999999) │
│ 2. Pad: str_pad(code, 6, '0')      │
│ 3. Hash: hash('sha256', code)      │
│ 4. Store: Insert into DB           │
│ 5. Return: Plain code to send      │
│                                     │
│ Generated Code: 123456              │
│ Hashed in DB:   a1b2c3d4...        │
│ Sent to User:   123456              │
└─────────────────────────────────────┘
                  │
                  ├─► Send via Email
                  │
                  ├─► User Receives Code
                  │
CODE VERIFICATION:
                  │
                  ├─► User Enters Code
                  │   └─ Input: 123456
                  │
                  ├─► Verify Code
                  │   ├─ Hash input: hash('sha256', 123456)
                  │   ├─ Query DB
                  │   ├─ SELECT FROM two_factor_auth
                  │   ├─ WHERE user_id = ? AND code = ?
                  │   ├─ AND created_at > NOW()-10min
                  │   │
                  │   ├─ If Match: Return true
                  │   └─ If No Match: Return false
                  │
                  ├─► On Success
                  │   ├─ Delete code from table
                  │   ├─ Complete login
                  │   └─ Code becomes invalid
                  │
CODE EXPIRATION:
                  │
                  ├─► After 10 Minutes
                  │   └─ Code no longer valid
                  │
                  ├─► If Not Used
                  │   ├─ Can delete old codes
                  │   ├─ User must login again
                  │   └─ New code will be sent
                  │
                  └─► Expired codes stay in DB
                      (optional cleanup scheduled task)
```

## Database Relationships

```
users (existing table)
├── id (PK)
├── email
├── username
├── password
├── first_name
├── last_name
├── [NEW] email_verified (0/1)
└── [NEW] two_fa_enabled (0/1)
    │
    ├──────────────────┬──────────────────┬─────────────────┐
    │                  │                  │                 │
    ▼                  ▼                  ▼                 ▼
email_confirmations  password_resets   two_factor_auth   (other)
├── id (PK)         ├── id (PK)       ├── id (PK)
├── user_id (FK)    ├── user_id (FK)  ├── user_id (FK)
├── token           ├── token         ├── code
├── created_at      ├── created_at    └── created_at
├── expires_at      ├── expires_at
└── confirmed_at    └── used_at
```

## Configuration Flow

```
1. USER CONFIGURATION
   │
   ├─► .env.example exists in repo
   │   └─ Contains template values
   │
   ├─► cp .env.example .env
   │   └─ Create local .env
   │
   └─► Edit .env
       ├─ GMAIL_ADDRESS=user@gmail.com
       └─ GMAIL_APP_PASSWORD=16charpassword

2. APPLICATION LOADS
   │
   ├─► config/email_config.php
   │   ├─ Reads from .env using getenv()
   │   ├─ Defines constants
   │   ├─ GMAIL_SMTP_HOST = 'smtp.gmail.com'
   │   └─ GMAIL_ADDRESS = .env value
   │
   ├─► EmailService instantiated
   │   └─ Uses constants from email_config.php
   │
   └─► Ready to send emails
```

## Security Flow

```
TOKEN SECURITY:
┌─────────────────────────────────┐
│ Plain Token Generated           │
│ Example: a1b2c3d4e5f6...        │
├─────────────────────────────────┤
│ Hashed for Storage              │
│ hash('sha256', token)           │
│ Example: x9y8z7w6v5u4...        │
├─────────────────────────────────┤
│ Stored in Database              │
│ Only hash stored, not plain text│
├─────────────────────────────────┤
│ User Receives Plain Token       │
│ In email: /confirm?token=a1b2c3 │
├─────────────────────────────────┤
│ Verification                    │
│ 1. Get token from URL           │
│ 2. Hash it                      │
│ 3. Compare with DB hash         │
│ 4. If match, verify email       │
└─────────────────────────────────┘

PASSWORD SECURITY:
┌─────────────────────────────────┐
│ User Password Input             │
│ Validation: >= 6 chars          │
├─────────────────────────────────┤
│ Hash Password                   │
│ password_hash(pwd, PASSWORD...) │
│ Uses bcrypt hashing             │
├─────────────────────────────────┤
│ Store Hash                      │
│ Only hash in database           │
├─────────────────────────────────┤
│ Verification                    │
│ password_verify(input, hash)    │
│ Secure comparison               │
└─────────────────────────────────┘
```

## Error Handling Flow

```
Email Service Error:
│
├─► Try Block
│   └─ Attempt email send
│
├─► Catch Exception
│   ├─ Capture error message
│   ├─ Add to errors array
│   ├─ Log to error_log()
│   └─ Return false
│
├─► Caller Checks
│   ├─ if ($service->send()) { success }
│   └─ else { show error }
│
└─► Get Error Details
    ├─ $service->getErrors()
    └─ $service->getLastError()
```

---

## Quick Reference: Key URLs

```
Registration:        /auth/register.php
Login:              /auth/login.php
Email Confirmation: /confirm-email.php?token=XXX
2FA Verification:   /verify-2fa.php
Email Settings:     /email-settings.php
Contact Form:       /contact.php
Forgot Password:    /auth/forgot-password.php (to create)
Reset Password:     /auth/reset-password.php?token=XXX (to create)
```

---

**Version**: 1.0.0  
**Last Updated**: December 9, 2025
