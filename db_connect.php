<?php
/**
 * Database Connection and Utility Functions
 *
 * This file handles database connection setup and common utility functions
 * for input sanitization. It uses PDO for secure database access with
 * prepared statements to prevent SQL injection attacks.
 *
 * @file db_connect.php
 * @package LensCraft
 * @version 1.0
 */

// ============================================================================
// DATABASE CONFIGURATION
// ============================================================================

// Database credentials (configurable for different environments)
// Local development defaults - change for production
$host = 'localhost';                    // MySQL server hostname
$dbname = 'photography_website';         // Database name
$db_username = 'root';                  // MySQL username
$db_password = '';                      // MySQL password

// ============================================================================
// DATABASE CONNECTION FUNCTION
// ============================================================================

/**
 * Get PDO database connection (Singleton pattern)
 *
 * Uses the Singleton pattern to ensure only one database connection
 * exists per request, improving performance and reducing overhead.
 * Connection is created on first call and reused for subsequent calls.
 *
 * @global string $host MySQL server hostname
 * @global string $dbname Database name
 * @global string $db_username MySQL username
 * @global string $db_password MySQL password
 *
 * @return PDO Returns active PDO database connection object
 *
 * @throws PDOException If connection fails, throws exception with friendly message
 *
 * @example
 * // Get database connection
 * $pdo = getDBConnection();
 * 
 * // Use with prepared statement
 * $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 * $stmt->execute([$user_id]);
 * $user = $stmt->fetch(PDO::FETCH_ASSOC);
 */
function getDBConnection() {
    global $host, $dbname, $db_username, $db_password;
    
    // Static variable persists across function calls (Singleton pattern)
    static $pdo = null;
    
    // Only create connection if it doesn't exist
    if ($pdo === null) {
        try {
            // Create new PDO connection with UTF-8 charset support
            // DSN format: mysql:host=localhost;dbname=database;charset=utf8
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8",
                $db_username,
                $db_password
            );
            
            // Error mode: Throw exceptions on errors (not silent failures)
            // This makes errors easier to catch and debug
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Fetch mode: Return data as associative arrays
            // SELECT queries return ['id' => 1, 'name' => 'John'] instead of [0 => 1, 1 => 'John']
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Log the detailed error message for debugging
            error_log("Database connection failed: " . $e->getMessage());
            
            // Throw generic message to avoid exposing database details to users
            throw new PDOException("Unable to connect to database. Please try again later.");
        }
    }
    
    return $pdo;
}

// ============================================================================
// INPUT SANITIZATION FUNCTION
// ============================================================================

/**
 * Sanitize user input data
 *
 * Removes whitespace, escapes special characters, and prevents HTML/XSS attacks.
 * IMPORTANT: This sanitizes for DISPLAY purposes. Always use prepared statements
 * for database queries to prevent SQL injection.
 *
 * Sanitization steps:
 * 1. trim() - Remove leading/trailing whitespace
 * 2. stripslashes() - Remove backslashes (legacy magic quotes)
 * 3. htmlspecialchars() - Escape HTML special characters for safe display
 *
 * @param string $data Raw input data from user (from $_POST, $_GET, etc.)
 *
 * @return string Sanitized data safe for display in HTML
 *
 * @example
 * // Sanitize form input
 * $username = sanitizeInput($_POST['username']);
 * $comment = sanitizeInput($_POST['comment']);
 * 
 * // Safe to display in HTML
 * echo "Welcome, " . $username;
 * echo "Comment: " . $comment;
 *
 * @note SQL Injection Protection:
 * Use prepared statements for database queries:
 * $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (?)");
 * $stmt->execute([$username]);  // Parameter binding prevents SQL injection
 *
 * @note XSS Protection:
 * This function escapes HTML entities when displaying user data:
 * - < becomes &lt;
 * - > becomes &gt;
 * - " becomes &quot;
 * - ' becomes &#039;
 */
function sanitizeInput($data) {
    // Step 1: Remove leading and trailing whitespace
    // Example: "  hello  " becomes "hello"
    $data = trim($data);
    
    // Step 2: Remove backslashes (from legacy PHP magic_quotes feature)
    // Example: "O\'Reilly" becomes "O'Reilly"
    // Note: Modern PHP doesn't add these, but function kept for compatibility
    $data = stripslashes($data);
    
    // Step 3: Convert HTML special characters to entities
    // ENT_QUOTES: Converts both double and single quotes
    // UTF-8: Proper encoding for international characters
    // Example: <script>alert('XSS')</script> 
    //          becomes &lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

// ============================================================================
// USAGE GUIDELINES
// ============================================================================

/*
 * BEST PRACTICES:
 *
 * 1. Database Queries:
 *    ALWAYS use prepared statements with parameter binding
 *    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 *    $stmt->execute([$user_id]);
 *
 * 2. Input Handling:
 *    Sanitize for display purposes
 *    Validate for business logic (email format, length, etc)
 *    Use prepared statements for database operations (separate from display sanitization)
 *
 * 3. Error Handling:
 *    Catch PDOException for database errors
 *    Log detailed errors for debugging
 *    Show generic messages to users (security)
 *
 * 4. Data Types:
 *    Cast data types explicitly: (int)$id, (string)$name
 *    Validate data before use (email, dates, lengths)
 *
 * EXAMPLE - Proper user registration:
 * try {
 *     $pdo = getDBConnection();
 *     
 *     // Sanitize for display
 *     $username = sanitizeInput($_POST['username']);
 *     
 *     // Validate
 *     if (strlen($username) < 3 || strlen($username) > 50) {
 *         throw new Exception("Username must be 3-50 characters");
 *     }
 *     
 *     // Use prepared statement (prevents SQL injection)
 *     $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (?)");
 *     $stmt->execute([$_POST['username']]);  // Original value to database
 *     
 * } catch (PDOException $e) {
 *     error_log("Database error: " . $e->getMessage());
 *     $_SESSION['error'] = "Registration failed. Please try again.";
 * } catch (Exception $e) {
 *     $_SESSION['error'] = $e->getMessage();
 * }
 */

?>
