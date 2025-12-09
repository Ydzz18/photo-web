# Code Style Guide

Coding standards and conventions for LensCraft.

---

## Table of Contents

1. [PHP Coding Standards](#php-coding-standards)
2. [Naming Conventions](#naming-conventions)
3. [Documentation Standards](#documentation-standards)
4. [Function Guidelines](#function-guidelines)
5. [Class Guidelines](#class-guidelines)
6. [Database Guidelines](#database-guidelines)
7. [Security Best Practices](#security-best-practices)
8. [Error Handling](#error-handling)

---

## PHP Coding Standards

### File Structure

**Opening and Closing Tags:**
```php
<?php
// Code here
?>
```

**Always use** `<?php` and close with `?>` - never use short tags

### Indentation

Use **4 spaces** for indentation (not tabs):

```php
<?php
function example() {
    if ($condition) {
        // Code indented 8 spaces (2 levels)
        $var = 1;
    }
}
```

### Line Length

Maximum **120 characters** per line:

```php
<?php
// ✓ Good - under 120 chars
$long_variable = $service->verifyEmailAndNotifyUser($email, $name);

// ✗ Bad - over 120 chars
$very_long_variable_name = $service->verifyEmailAndNotifyUserAndLogActionAndSendConfirmationEmail($email, $name);
```

Break long lines:

```php
<?php
$result = $db->query(
    "SELECT * FROM users WHERE email = ? AND is_active = ?",
    [$email, true]
);
```

### Spacing

**One blank line between functions:**
```php
<?php
function first() {
    return 1;
}

function second() {
    return 2;
}
```

**No blank lines in method bodies:**
```php
<?php
// ✓ Good
public function validate() {
    if (empty($this->email)) {
        throw new Exception("Email required");
    }
    return true;
}

// ✗ Bad - unnecessary blank lines
public function validate() {

    if (empty($this->email)) {

        throw new Exception("Email required");

    }

    return true;

}
```

**Spaces around operators:**
```php
<?php
// ✓ Good
$total = $price + $tax * $quantity;
if ($count > 0 && $active === true) {

// ✗ Bad
$total=$price+$tax*$quantity;
if($count>0&&$active===true){
```

### Brackets and Braces

**Opening brace on same line:**
```php
<?php
// ✓ Good
if ($condition) {
    // code
}

function example() {
    // code
}

// ✗ Bad
if ($condition)
{
    // code
}

function example()
{
    // code
}
```

**One-line conditions (if short):**
```php
<?php
// ✓ OK for simple statements
if ($active) return $user;
```

---

## Naming Conventions

### Variables

Use **snake_case** for variables:

```php
<?php
// ✓ Good
$user_id = 5;
$is_active = true;
$photo_title = "Sunset";

// ✗ Bad
$userId = 5;
$isActive = true;
$PhotoTitle = "Sunset";
```

### Functions

Use **snake_case** for function names:

```php
<?php
// ✓ Good
function get_user_by_id($id) {
    // function body
}

function is_email_verified() {
    // function body
}

// ✗ Bad
function getUserById($id) {
    // function body
}

function IsEmailVerified() {
    // function body
}
```

### Classes

Use **PascalCase** for class names:

```php
<?php
// ✓ Good
class EmailService {
    // class body
}

class UserLogger {
    // class body
}

// ✗ Bad
class emailService {
    // class body
}

class user_logger {
    // class body
}
```

### Constants

Use **UPPER_SNAKE_CASE** for constants:

```php
<?php
// ✓ Good
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024);
define('GMAIL_SMTP_HOST', 'smtp.gmail.com');
const DATABASE_PORT = 3306;

// ✗ Bad
define('maxUploadSize', 50 * 1024 * 1024);
define('gmail_smtp_host', 'smtp.gmail.com');
```

### Database Columns

Use **snake_case** for database columns:

```sql
-- ✓ Good
CREATE TABLE users (
    id INT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email_verified BOOLEAN,
    created_at DATETIME
);

-- ✗ Bad
CREATE TABLE users (
    id INT PRIMARY KEY,
    firstName VARCHAR(50),
    lastName VARCHAR(50),
    emailVerified BOOLEAN,
    createdAt DATETIME
);
```

---

## Documentation Standards

### Docstrings

**All functions must have docstrings:**

```php
<?php
/**
 * Verify user email with confirmation token
 *
 * @param int $user_id User ID
 * @param string $token Confirmation token
 * @return bool True if verified successfully
 * @throws Exception If token is invalid or expired
 */
public function verifyEmail($user_id, $token) {
    // function body
}
```

**Class docstrings:**

```php
<?php
/**
 * EmailService Class
 *
 * Handles email delivery using PHPMailer and Gmail SMTP.
 * Manages email templates, SMTP configuration, and error handling.
 *
 * @package LensCraft
 * @author Development Team
 * @version 1.0
 */
class EmailService {
    // class body
}
```

### Inline Comments

Use comments to explain **why**, not **what**:

```php
<?php
// ✓ Good - explains the reasoning
// Hash the plain token before storing (security)
$hashed_token = hash('sha256', $plain_token);

// ✓ Good - explains non-obvious logic
// Use GREATEST to prevent negative numbers after deletion
$stmt = $pdo->prepare(
    "UPDATE users SET followers_count = GREATEST(followers_count - 1, 0)"
);

// ✗ Bad - obvious from code
// Set variable to 5
$count = 5;

// ✗ Bad - states what code does
// Increment the counter
$counter++;
```

### File Headers

Include header comment at top of file:

```php
<?php
/**
 * User Authentication Module
 *
 * Handles user login, registration, and session management.
 * Includes password hashing, token verification, and 2FA support.
 *
 * @file /auth/login.php
 * @author Development Team
 * @version 1.0
 * @since 2024-11-01
 */

session_start();
require_once 'db_connect.php';

// Rest of file...
```

---

## Function Guidelines

### Single Responsibility

Functions should do **one thing well**:

```php
<?php
// ✓ Good - single responsibility
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function send_verification_email($email, $token) {
    $service = new EmailService();
    return $service->sendEmailConfirmation($email, 'User', $token);
}

// ✗ Bad - multiple responsibilities
function validate_and_send_email($email, $token) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $service = new EmailService();
    return $service->sendEmailConfirmation($email, 'User', $token);
}
```

### Return Types

Use explicit return types (PHP 7.0+):

```php
<?php
// ✓ Good
public function getUserById(int $id): array {
    // returns array
}

public function isActive(int $user_id): bool {
    // returns boolean
}

public function getErrorMessages(): ?array {
    // returns array or null
}

// ✗ Bad - no type hints
public function getUserById($id) {
    // unclear what is returned
}
```

### Parameter Types

Use type hints for all parameters:

```php
<?php
// ✓ Good
public function updateUser(int $id, string $email, bool $active) {
    // clearly shows parameter types
}

// ✗ Bad - no type hints
public function updateUser($id, $email, $active) {
    // unclear what types are expected
}
```

### Function Length

Keep functions **under 50 lines**:

```php
<?php
// ✓ Good - short and focused
public function generateCode(int $user_id): string {
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $this->save2FACode($user_id, $code);
    return $code;
}

// ✗ Bad - too long, multiple responsibilities
public function handleUserRegistration($data) {
    // 100+ lines of validation, database operations, email sending, logging
}
```

---

## Class Guidelines

### Constructor Dependency Injection

Use constructor to inject dependencies:

```php
<?php
class EmailService {
    private $pdo;
    private $config;

    // ✓ Good - dependencies injected
    public function __construct(PDO $pdo, array $config) {
        $this->pdo = $pdo;
        $this->config = $config;
    }
}

// Usage
$pdo = getDBConnection();
$config = include 'config/email.php';
$service = new EmailService($pdo, $config);
```

### Property Visibility

Use appropriate visibility modifiers:

```php
<?php
class UserService {
    // ✓ Good - private for internal use
    private $pdo;
    private $logger;

    // ✓ Good - public for API
    public function getUserById(int $id): ?array {
        return $this->fetchUser($id);
    }

    // ✓ Good - private for internal methods
    private function fetchUser(int $id): ?array {
        // database query
    }
}
```

### Class Constants

Define constants in class:

```php
<?php
class NotificationManager {
    // Class-level constants
    const TYPE_LIKE = 'like';
    const TYPE_COMMENT = 'comment';
    const TYPE_FOLLOW = 'follow';

    // Usage in methods
    public function create($type) {
        if ($type === self::TYPE_LIKE) {
            // handle like
        }
    }
}
```

---

## Database Guidelines

### Prepared Statements

**Always use prepared statements:**

```php
<?php
// ✓ Good - prevents SQL injection
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// ✓ Good - named parameters
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

// ✗ Bad - SQL injection vulnerability
$result = $pdo->query("SELECT * FROM users WHERE id = $user_id");

// ✗ Bad - string concatenation
$query = "SELECT * FROM users WHERE email = '" . $email . "'";
$result = $pdo->query($query);
```

### Query Formatting

Format SQL for readability:

```php
<?php
// ✓ Good - formatted for readability
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        COUNT(p.id) as photo_count,
        COUNT(DISTINCT f.id) as follower_count
    FROM users u
    LEFT JOIN photos p ON u.id = p.user_id
    LEFT JOIN follows f ON u.id = f.following_id
    WHERE u.email_verified = 1
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ?
");

// ✗ Bad - hard to read
$stmt = $pdo->prepare("SELECT u.id, u.username, COUNT(p.id) as photo_count, COUNT(DISTINCT f.id) as follower_count FROM users u LEFT JOIN photos p ON u.id = p.user_id LEFT JOIN follows f ON u.id = f.following_id WHERE u.email_verified = 1 GROUP BY u.id ORDER BY u.created_at DESC LIMIT ?");
```

### Transaction Handling

Use transactions for multiple related operations:

```php
<?php
try {
    $pdo->beginTransaction();

    // First operation
    $stmt = $pdo->prepare("INSERT INTO photos (user_id, title) VALUES (?, ?)");
    $stmt->execute([$user_id, $title]);
    $photo_id = $pdo->lastInsertId();

    // Second operation
    $stmt = $pdo->prepare("UPDATE users SET photos_count = photos_count + 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();
    return true;
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Transaction failed: " . $e->getMessage());
    return false;
}
```

---

## Security Best Practices

### Input Sanitization

**Always sanitize user input:**

```php
<?php
// ✓ Good - sanitize and validate
$email = sanitizeInput($_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception("Invalid email");
}

$title = sanitizeInput($_POST['title']);
if (strlen($title) > 100) {
    throw new Exception("Title too long");
}

// ✗ Bad - no sanitization
$email = $_POST['email'];
$title = $_POST['title'];
```

### Output Escaping

**Always escape output:**

```php
<?php
// ✓ Good - escaped output
echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
echo htmlspecialchars($comment['text'], ENT_QUOTES, 'UTF-8');

// ✗ Bad - unescaped output (XSS vulnerability)
echo $user['username'];
echo $comment['text'];
```

### Password Handling

```php
<?php
// ✓ Good - use password_hash and password_verify
$hashed = password_hash($password, PASSWORD_BCRYPT);
if (password_verify($input_password, $hashed)) {
    // Password correct
}

// ✗ Bad - weak hashing
$hashed = md5($password);
$hashed = sha1($password);
$hashed = hash('sha256', $password);
```

### Session Security

```php
<?php
// ✓ Good - check session exists
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// ✓ Good - verify session ownership
if ($_SESSION['user_id'] != $user_id) {
    throw new Exception("Unauthorized");
}

// ✗ Bad - no session validation
$user_id = $_GET['user_id'];  // Trusting URL parameter
```

---

## Error Handling

### Try-Catch Blocks

```php
<?php
// ✓ Good - specific error handling
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    throw new Exception("Failed to fetch user");
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    throw $e;
}
```

### Error Messages

**Log detailed, show generic:**

```php
<?php
try {
    // operation
} catch (PDOException $e) {
    // ✓ Good - log details, show generic message
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred. Please try again.";
}

// ✗ Bad - expose internal details
error_log("Database error: " . $e->getMessage());
$_SESSION['error'] = "SQL Error: " . $e->getMessage();
```

---

## Code Review Checklist

Before submitting code:

- [ ] Follows naming conventions (snake_case variables, PascalCase classes)
- [ ] All functions have docstrings
- [ ] Using prepared statements (no SQL injection)
- [ ] Input is sanitized
- [ ] Output is escaped (no XSS)
- [ ] No hardcoded credentials
- [ ] Error handling with try-catch
- [ ] Session validation on protected pages
- [ ] No commented-out code
- [ ] Lines under 120 characters
- [ ] Functions under 50 lines
- [ ] Proper indentation (4 spaces)
- [ ] Appropriate visibility modifiers
- [ ] Type hints on parameters and returns
- [ ] No TODO comments (should be in issues/tasks)

---

**Last Updated:** December 2024
**Version:** 1.0
