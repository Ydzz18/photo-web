#!/usr/bin/env php
<?php
/**
 * Test Database Migration
 * Run this to verify the database migration works correctly
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║          Testing Database Migration                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

require_once __DIR__ . '/db_connect.php';

try {
    echo "📋 Step 1: Testing database connection...\n";
    $pdo = getDBConnection();
    echo "✓ Database connection successful\n\n";

    echo "📋 Step 2: Checking users table...\n";
    $result = $pdo->query("SHOW COLUMNS FROM users");
    if ($result->rowCount() > 0) {
        echo "✓ Users table exists\n";
        
        // Check for email_verified column
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
        if ($result->rowCount() > 0) {
            echo "✓ email_verified column exists\n";
        } else {
            echo "⚠ email_verified column missing - running migration...\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT 0");
            echo "✓ Added email_verified column\n";
        }
        
        // Check for two_fa_enabled column
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'two_fa_enabled'");
        if ($result->rowCount() > 0) {
            echo "✓ two_fa_enabled column exists\n";
        } else {
            echo "⚠ two_fa_enabled column missing - running migration...\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN two_fa_enabled BOOLEAN DEFAULT 0");
            echo "✓ Added two_fa_enabled column\n";
        }
    } else {
        echo "❌ Users table not found!\n";
        exit(1);
    }

    echo "\n📋 Step 3: Creating email_confirmations table...\n";
    $result = $pdo->query("SHOW TABLES LIKE 'email_confirmations'");
    if ($result->rowCount() > 0) {
        echo "✓ email_confirmations table already exists\n";
    } else {
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
        echo "✓ Created email_confirmations table\n";
    }

    echo "\n📋 Step 4: Creating password_resets table...\n";
    $result = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    if ($result->rowCount() > 0) {
        echo "✓ password_resets table already exists\n";
    } else {
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
        echo "✓ Created password_resets table\n";
    }

    echo "\n📋 Step 5: Creating two_factor_auth table...\n";
    $result = $pdo->query("SHOW TABLES LIKE 'two_factor_auth'");
    if ($result->rowCount() > 0) {
        echo "✓ two_factor_auth table already exists\n";
    } else {
        $pdo->exec("CREATE TABLE two_factor_auth (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            code VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        )");
        echo "✓ Created two_factor_auth table\n";
    }

    echo "\n📋 Step 6: Verifying table structure...\n";
    
    // Verify email_confirmations structure
    $result = $pdo->query("DESCRIBE email_confirmations");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "\nEmail Confirmations Table Columns:\n";
    foreach ($columns as $col) {
        echo "  • {$col['Field']} ({$col['Type']}) - Null: {$col['Null']}\n";
    }

    // Verify password_resets structure
    $result = $pdo->query("DESCRIBE password_resets");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "\nPassword Resets Table Columns:\n";
    foreach ($columns as $col) {
        echo "  • {$col['Field']} ({$col['Type']}) - Null: {$col['Null']}\n";
    }

    // Verify two_factor_auth structure
    $result = $pdo->query("DESCRIBE two_factor_auth");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "\nTwo Factor Auth Table Columns:\n";
    foreach ($columns as $col) {
        echo "  • {$col['Field']} ({$col['Type']}) - Null: {$col['Null']}\n";
    }

    echo "\n════════════════════════════════════════════════════════════\n";
    echo "✓ Database migration test completed successfully!\n";
    echo "════════════════════════════════════════════════════════════\n\n";
    echo "Next steps:\n";
    echo "1. Ensure .env file has Gmail credentials\n";
    echo "2. Check email_templates directory exists with 4 HTML files\n";
    echo "3. Test email sending with test-email.php\n";
    echo "4. Run registration and login tests\n\n";

} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n\n";
    
    if (strpos($e->getMessage(), 'Invalid default value') !== false) {
        echo "💡 Tip: This is a TIMESTAMP column issue. Make sure:\n";
        echo "   - MySQL version is 5.7.4 or higher\n";
        echo "   - TIMESTAMP columns use 'DEFAULT NULL' syntax\n";
        echo "   - Check the migration file for proper syntax\n";
    }
    
    exit(1);
}
?>
