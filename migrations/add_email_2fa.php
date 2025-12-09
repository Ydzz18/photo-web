<?php
/**
 * Database Migration for Email Confirmation and 2FA
 * This script adds the necessary columns to the users table for email verification and 2FA
 */

require_once __DIR__ . '/../db_connect.php';

echo "Starting database migration...\n\n";

try {
    $pdo = getDBConnection();
    
    // Check and add email_verified column
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
    if ($result->rowCount() == 0) {
        echo "Adding email_verified column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT 0");
        echo "✓ email_verified column added successfully\n\n";
    } else {
        echo "✓ email_verified column already exists\n\n";
    }
    
    // Check and add two_fa_enabled column
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'two_fa_enabled'");
    if ($result->rowCount() == 0) {
        echo "Adding two_fa_enabled column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN two_fa_enabled BOOLEAN DEFAULT 0");
        echo "✓ two_fa_enabled column added successfully\n\n";
    } else {
        echo "✓ two_fa_enabled column already exists\n\n";
    }
    
    // Create email_confirmations table
    $result = $pdo->query("SHOW TABLES LIKE 'email_confirmations'");
    if ($result->rowCount() == 0) {
        echo "Creating email_confirmations table...\n";
        $pdo->exec("CREATE TABLE email_confirmations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            confirmed_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_token (token)
        )");
        echo "✓ email_confirmations table created successfully\n\n";
    } else {
        echo "✓ email_confirmations table already exists\n\n";
    }
    
    // Create password_resets table
    $result = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    if ($result->rowCount() == 0) {
        echo "Creating password_resets table...\n";
        $pdo->exec("CREATE TABLE password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            used_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_token (token)
        )");
        echo "✓ password_resets table created successfully\n\n";
    } else {
        echo "✓ password_resets table already exists\n\n";
    }
    
    // Create two_factor_auth table
    $result = $pdo->query("SHOW TABLES LIKE 'two_factor_auth'");
    if ($result->rowCount() == 0) {
        echo "Creating two_factor_auth table...\n";
        $pdo->exec("CREATE TABLE two_factor_auth (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            code VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        )");
        echo "✓ two_factor_auth table created successfully\n\n";
    } else {
        echo "✓ two_factor_auth table already exists\n\n";
    }
    
    echo "================================\n";
    echo "✓ Database migration completed successfully!\n";
    echo "================================\n";
    echo "\nNext steps:\n";
    echo "1. Copy .env.example to .env\n";
    echo "2. Add your Gmail credentials to .env\n";
    echo "3. Test the registration and login flows\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
