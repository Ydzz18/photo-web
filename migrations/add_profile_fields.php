<?php
require_once '../db_connect.php';

try {
    $pdo = getDBConnection();
    
    $queries = [
        "ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL"
    ];
    
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "✓ Executed: $query<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠ Column already exists: $query<br>";
            } else {
                throw $e;
            }
        }
    }
    
    echo "<p>Migration completed successfully!</p>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
