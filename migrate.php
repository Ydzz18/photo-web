<?php
require_once 'db_connect.php';

try {
    $pdo = getDBConnection();
    
    $migrations = [
        "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` LONGTEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "ALTER TABLE `users` ADD COLUMN `bio` TEXT DEFAULT NULL AFTER `password`",
        "ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL AFTER `bio`",
        "ALTER TABLE `users` ADD COLUMN `cover_photo` VARCHAR(255) DEFAULT NULL AFTER `profile_picture`",
        "ALTER TABLE `users` ADD COLUMN `instagram` VARCHAR(100) DEFAULT NULL AFTER `cover_photo`",
        "ALTER TABLE `users` ADD COLUMN `twitter` VARCHAR(100) DEFAULT NULL AFTER `instagram`",
        "ALTER TABLE `users` ADD COLUMN `facebook` VARCHAR(100) DEFAULT NULL AFTER `twitter`",
        "ALTER TABLE `users` ADD COLUMN `website` VARCHAR(255) DEFAULT NULL AFTER `facebook`",
        "ALTER TABLE `users` ADD COLUMN `is_profile_public` TINYINT(1) DEFAULT 1 AFTER `website`",
        "ALTER TABLE `users` ADD COLUMN `show_email` TINYINT(1) DEFAULT 0 AFTER `is_profile_public`",
        "ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(50) DEFAULT NULL AFTER `show_email`",
        "ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(50) DEFAULT NULL AFTER `first_name`",
        "ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL AFTER `last_name`",
        "ALTER TABLE `users` ADD COLUMN `location` VARCHAR(100) DEFAULT NULL AFTER `phone`",
        "ALTER TABLE `users` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`"
    ];
    
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✓ " . htmlspecialchars($sql) . "</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p style='color: orange;'>⚠ Column already exists - " . htmlspecialchars($sql) . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<p style='color: green; font-weight: bold;'>Migration completed!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
